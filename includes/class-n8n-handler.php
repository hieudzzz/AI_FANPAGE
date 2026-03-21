<?php
/**
 * Handler Class for N8N Integration
 */
class AIF_N8N_Handler
{
    private $db;
    private $ai;
    private $fb;

    public function __construct()
    {
        $this->db = new AIF_N8N_DB();
        $this->ai = new AIF_N8N_AI();
        $this->fb = new AIF_Facebook_Manager();
    }

    /**
     * Handle incoming N8N Chat request (7-Step Flow)
     */
    public function handle_chat($request)
    {
        $payload = $request->get_json_params();

        if (empty($payload)) {
            $json = file_get_contents('php://input');
            $payload = json_decode($json, true);
        }

        if (empty($payload)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Empty payload'], 400);
        }

        $item = isset($payload[0]) ? $payload[0] : $payload;
        $sender_psid = isset($item['sender_psid']) ? $item['sender_psid'] : '';
        $recipient_id = isset($item['recipient_id']) ? $item['recipient_id'] : '';
        $user_text = isset($item['text']) ? $item['text'] : '';

        if (empty($sender_psid) || empty($recipient_id) || empty($user_text)) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'Missing required fields'], 400);
        }

        // --- STEP 1: Get/Create Session & Save Incoming Message ---
        $session = $this->db->get_chat_session($sender_psid, $recipient_id);

        // --- NEW: Fetch Facebook Profile if missing ---
        if (empty($session->customer_name)) {
            $profile = $this->get_facebook_profile($recipient_id, $sender_psid);
            if ($profile && !empty($profile['name'])) {
                $session->customer_name = $profile['name'];
                $session->customer_pic = isset($profile['profile_pic']) ? $profile['profile_pic'] : '';

                $this->db->update_chat_session($sender_psid, $recipient_id, [
                    'customer_name' => $session->customer_name,
                    'customer_pic' => $session->customer_pic
                ]);
            }
        }

        $this->db->save_message($session->id, 'user', $user_text);

        // --- STEP 2: Retrieve Context (History) ---
        $context_limit = get_option('aif_n8n_context_limit', 5);
        $history = $this->db->get_recent_messages($session->id, $context_limit);

        // --- STEP 3: Load Product Database ---
        $products = $this->db->get_products();

        // --- STEP 4: Generate AI Response ---
        $ai_res = $this->ai->generate_response($user_text, $session, $products, $history);

        if (!$ai_res) {
            return new WP_REST_Response(['status' => 'error', 'message' => 'AI generation failed'], 500);
        }

        // --- STEP 5: Save AI Response & Update Session Metadata ---
        $intent = isset($ai_res['intent']) ? sanitize_text_field($ai_res['intent']) : 'unknown';
        $reply_text = isset($ai_res['reply']) ? $ai_res['reply'] : '';

        $this->db->save_message($session->id, 'ai', $reply_text, $intent);

        $update_data = [
            'last_message' => $user_text,
            'lead_score' => isset($ai_res['lead_score']) ? intval($ai_res['lead_score']) : 0,
            'lead_level' => isset($ai_res['lead_level']) ? sanitize_text_field($ai_res['lead_level']) : 'Low',
            'collected_data' => isset($ai_res['customer_info']) ? json_encode($ai_res['customer_info']) : $session->collected_data,
            'current_field' => isset($ai_res['current_field']) ? sanitize_text_field($ai_res['current_field']) : 'none',
            'needs_support' => (isset($ai_res['need_human']) && $ai_res['need_human']) ? 1 : 0,
            'ai_state' => json_encode($ai_res)
        ];
        $this->db->update_chat_session($sender_psid, $recipient_id, $update_data);

        // --- STEP 6: Execute Lead Capture Logic ---
        $lead_id = 0;
        $customer_info = isset($ai_res['customer_info']) ? $ai_res['customer_info'] : [];

        // ONLY save as lead if BOTH phone and address are provided
        if (!empty($customer_info['phone']) && !empty($customer_info['address'])) {
            $notes = '';
            if (!empty($ai_res['order_items'])) {
                $notes = "Sản phẩm quan tâm: \n";
                foreach ($ai_res['order_items'] as $item) {
                    $notes .= "- " . $item['product_name'] . " (x" . $item['quantity'] . ")\n";
                }
            }

            $lead_id = $this->db->save_lead($session->id, $customer_info, $notes);
        }

        // --- STEP 7: Reply to Facebook & Return Response ---
        $fb_success = false;
        if (!empty($reply_text)) {
            $fb_success = $this->send_facebook_reply($recipient_id, $sender_psid, $reply_text);
        }

        return new WP_REST_Response([
            'status' => 'success',
            'reply' => $reply_text,
            'intent' => $intent,
            'lead_score' => $update_data['lead_score'],
            'lead_level' => $update_data['lead_level'],
            'lead_id' => $lead_id,
            'need_human' => $update_data['needs_support'] ? true : false,
            'fb_sent' => $fb_success
        ], 200);
    }

    /**
     * Get Facebook user profile (Name & Picture)
     */
    private function get_facebook_profile($page_id, $psid)
    {
        global $wpdb;
        $table_pages = $wpdb->prefix . 'aif_facebook_pages';
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT access_token, iv FROM {$table_pages} WHERE page_id = %s",
            $page_id
        ));

        if (!$page)
            return null;

        $access_token = $this->fb->decrypt_token($page->access_token, $page->iv);
        if (!$access_token)
            return null;

        $url = "https://graph.facebook.com/v23.0/{$psid}?fields=name,first_name,last_name,profile_pic&access_token=" . $access_token;

        $response = wp_remote_get($url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            error_log('AIF FB Profile Error: ' . $response->get_error_message());
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Send reply via Facebook Graph API
     */
    private function send_facebook_reply($page_id, $psid, $message)
    {
        global $wpdb;
        $table_pages = $wpdb->prefix . 'aif_facebook_pages';
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT access_token, iv FROM {$table_pages} WHERE page_id = %s",
            $page_id
        ));

        if (!$page) {
            error_log("AIF N8N Handler: Page token not found for Page ID $page_id");
            return false;
        }

        $access_token = $this->fb->decrypt_token($page->access_token, $page->iv);
        if (!$access_token) {
            error_log("AIF N8N Handler: Failed to decrypt token for Page ID $page_id");
            return false;
        }

        $url = "https://graph.facebook.com/v23.0/{$page_id}/messages/";

        $payload = [
            'recipient' => ['id' => $psid],
            'message' => ['text' => $message],
            'messaging_type' => 'RESPONSE'
        ];

        $args = [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ],
            'timeout' => 30
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('AIF Facebook Send Error: ' . $response->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            error_log('AIF Facebook Send Error (HTTP ' . $code . '): ' . wp_remote_retrieve_body($response));
            return false;
        }

        return true;
    }
}
