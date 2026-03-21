<?php

/**
 * Database Helper Class for N8N Integration
 */
class AIF_N8N_DB
{
    private $table_chats;
    private $table_messages;
    private $table_products;
    private $table_leads;

    public function __construct()
    {
        global $wpdb;
        $this->table_chats = $wpdb->prefix . 'aif_n8n_chats';
        $this->table_messages = $wpdb->prefix . 'aif_n8n_messages';
        $this->table_products = $wpdb->prefix . 'aif_n8n_products';
        $this->table_leads = $wpdb->prefix . 'aif_n8n_leads';
    }

    /**
     * Get or create a chat session
     */
    public function get_chat_session($sender_psid, $page_id)
    {
        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_chats WHERE sender_psid = %s AND page_id = %s",
            $sender_psid,
            $page_id
        ));

        if (!$session) {
            $wpdb->insert($this->table_chats, [
                'sender_psid' => $sender_psid,
                'page_id' => $page_id,
                'collected_data' => json_encode([
                    'name' => '',
                    'phone' => '',
                    'address' => ''
                ]),
                'ai_state' => json_encode([]),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);
            $session = $this->get_chat_session($sender_psid, $page_id);
        }

        return $session;
    }

    /**
     * Update chat session
     */
    public function update_chat_session($sender_psid, $page_id, $data)
    {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        $data['is_viewed'] = 0; // Reset: đánh dấu chưa xem khi có cập nhật mới
        return $wpdb->update(
            $this->table_chats,
            $data,
            ['sender_psid' => $sender_psid, 'page_id' => $page_id]
        );
    }

    /**
     * Get all products for AI context
     */
    public function get_products($all = false)
    {
        global $wpdb;
        $where = $all ? '' : "WHERE status = 'active'";
        return $wpdb->get_results("SELECT * FROM $this->table_products $where ORDER BY id DESC");
    }

    /**
     * Save a message to history
     */
    public function save_message($session_id, $sender, $message, $intent = 'unknown')
    {
        global $wpdb;
        return $wpdb->insert($this->table_messages, [
            'session_id' => $session_id,
            'sender' => $sender,
            'message' => $message,
            'intent' => $intent,
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Get recent messages for context
     */
    public function get_recent_messages($session_id, $limit = 5)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT sender, message, intent FROM $this->table_messages 
             WHERE session_id = %d 
             ORDER BY created_at DESC LIMIT %d",
            $session_id,
            $limit
        ));
    }

    /**
     * Save or update a lead from AI detection
     */
    public function save_lead($session_id, $customer_data, $notes = '')
    {
        global $wpdb;

        // Check if lead already exists for this session
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $this->table_leads WHERE session_id = %d",
            $session_id
        ));

        $data = [
            'session_id' => $session_id,
            'name' => isset($customer_data['name']) ? $customer_data['name'] : '',
            'phone' => isset($customer_data['phone']) ? $customer_data['phone'] : '',
            'address' => isset($customer_data['address']) ? $customer_data['address'] : '',
            'notes' => $notes,
            'created_at' => current_time('mysql')
        ];

        if ($existing) {
            unset($data['created_at']); // Don't change creation time
            $data['is_viewed'] = 0; // Reset: đánh dấu chưa xem khi lead được cập nhật
            $wpdb->update($this->table_leads, $data, ['id' => $existing]);
            return $existing;
        } else {
            $wpdb->insert($this->table_leads, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Get all chat sessions
     */
    public function get_chat_sessions()
    {
        global $wpdb;
        return $wpdb->get_results("
            SELECT c.*, COALESCE(p.page_name, c.page_id) as page_name
            FROM $this->table_chats c
            LEFT JOIN {$wpdb->prefix}aif_facebook_pages p ON p.page_id = c.page_id
            ORDER BY c.updated_at DESC
        ");
    }

    /**
     * Delete chat session and its history
     */
    public function delete_chat_session($id)
    {
        global $wpdb;
        $wpdb->delete($this->table_chats, ['id' => $id]);
        $wpdb->delete($this->table_messages, ['session_id' => $id]);
        return true;
    }

    /**
     * Get all meaningful leads (joined with chat profile)
     */
    public function get_leads()
    {
        global $wpdb;
        $sql = "SELECT l.*, c.customer_name as fb_name, c.customer_pic as fb_pic, c.ai_state,
                       c.page_id, COALESCE(p.page_name, c.page_id) as page_name
                FROM $this->table_leads l
                LEFT JOIN $this->table_chats c ON l.session_id = c.id
                LEFT JOIN {$wpdb->prefix}aif_facebook_pages p ON p.page_id = c.page_id
                WHERE (l.phone IS NOT NULL AND l.phone != '' AND l.phone != '---')
                   OR (l.address IS NOT NULL AND l.address != '' AND l.address != '---')
                ORDER BY l.created_at DESC";
        return $wpdb->get_results($sql);
    }

    /**
     * Insert or update product (for maintenance)
     */
    public function upsert_product($data)
    {
        global $wpdb;

        $id = isset($data['id']) ? intval($data['id']) : 0;

        // Remove product_id if it somehow arrived in $data
        unset($data['product_id']);
        unset($data['stock_quantity']);

        // Check exists by primary id
        $exists = false;
        if ($id > 0) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $this->table_products WHERE id = %d", $id));
        }

        if ($exists) {
            $data['updatedAt'] = current_time('mysql');
            return $wpdb->update($this->table_products, $data, ['id' => $id]);
        } else {
            unset($data['id']); // Ensure we don't try to insert with id if it's 0/null
            $data['createdAt'] = current_time('mysql');
            $data['updatedAt'] = current_time('mysql');
            return $wpdb->insert($this->table_products, $data);
        }
    }

    /**
     * Mark a chat session as viewed
     */
    public function mark_chat_viewed($chat_id)
    {
        global $wpdb;
        return $wpdb->update(
            $this->table_chats,
            ['is_viewed' => 1, 'viewed_at' => current_time('mysql')],
            ['id' => $chat_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Mark a lead as viewed
     */
    public function mark_lead_viewed($lead_id)
    {
        global $wpdb;
        return $wpdb->update(
            $this->table_leads,
            ['is_viewed' => 1, 'viewed_at' => current_time('mysql')],
            ['id' => $lead_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Get unread counts for badges
     */
    public function get_unread_counts()
    {
        global $wpdb;
        return [
            'chats' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->table_chats WHERE is_viewed = 0"),
            'leads' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->table_leads WHERE is_viewed = 0")
        ];
    }
}
