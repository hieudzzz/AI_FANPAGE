<?php
/**
 * Facebook Webhook Handler Class
 */
class AIF_Webhook_Handler
{
    private $db;
    private $fb_manager;

    public function __construct()
    {
        $this->db = new AIF_DB();
        $this->fb_manager = new AIF_Facebook_Manager();
    }

    /**
     * Handle GET request (Verification)
     */
    public function handle_verification($request)
    {
        $params = $request->get_query_params();

        // Facebook sends params with dots, but some server environments/WP might convert them to underscores
        $mode = isset($params['hub_mode']) ? $params['hub_mode'] : (isset($params['hub.mode']) ? $params['hub.mode'] : '');
        $token = isset($params['hub_verify_token']) ? $params['hub_verify_token'] : (isset($params['hub.verify_token']) ? $params['hub.verify_token'] : '');
        $challenge = isset($params['hub_challenge']) ? $params['hub_challenge'] : (isset($params['hub.challenge']) ? $params['hub.challenge'] : '');

        $verify_token = get_option('aif_fb_verify_token', 'abc123');

        if ($mode === 'subscribe' && $token === $verify_token) {
            // FB expects raw plain text. Exit to avoid WP REST API JSON encoding.
            header('Content-Type: text/plain');
            echo $challenge;
            exit;
        }

        error_log('AIF Webhook Verification Failed: ' . print_r($params, true));
        return new WP_REST_Response('Forbidden', 403);
    }

    /**
     * Handle POST request (Incoming Messages)
     */
    public function handle_message($request)
    {
        $payload = $request->get_json_params();

        if (empty($payload)) {
            // Fallback to php://input if get_json_params fails
            $json = file_get_contents('php://input');
            $payload = json_decode($json, true);
        }

        if (isset($payload['object']) && $payload['object'] === 'page') {
            foreach ($payload['entry'] as $entry) {
                if (!isset($entry['messaging']))
                    continue;

                foreach ($entry['messaging'] as $messaging) {
                    if (isset($messaging['message'])) {
                        $this->process_single_message($messaging, $entry['id']);
                    }
                }
            }
        }

        return new WP_REST_Response(['status' => 'success'], 200);
    }

    /**
     * Process and save single message
     */
    private function process_single_message($messaging, $page_id)
    {
        global $wpdb;
        $psid = $messaging['sender']['id'];
        $message_text = isset($messaging['message']['text']) ? $messaging['message']['text'] : '';

        if (empty($message_text))
            return;

        // Find the page access token in our DB
        $table_pages = $wpdb->prefix . 'aif_facebook_pages';
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT access_token, iv FROM {$table_pages} WHERE page_id = %s",
            $page_id
        ));

        $name = '';
        $profile_pic = '';

        if ($page) {
            $access_token = $this->fb_manager->decrypt_token($page->access_token, $page->iv);
            $user_profile = $this->fb_manager->get_user_profile($psid, $access_token);

            if ($user_profile && !isset($user_profile['error'])) {
                $name = isset($user_profile['name']) ? $user_profile['name'] : '';
                $profile_pic = isset($user_profile['profile_pic']) ? $user_profile['profile_pic'] : '';
            }
        }

        // Save to DB
        $wpdb->insert($wpdb->prefix . 'aif_messages', [
            'psid' => $psid,
            'name' => $name,
            'profile_pic' => $profile_pic,
            'message' => $message_text,
            'page_id' => $page_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ]);
    }
}
