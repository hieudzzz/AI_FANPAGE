<?php

/**
 * Fired during plugin activation
 */
class AIF_Activator
{

    public static function deactivate()
    {
        $timestamp = wp_next_scheduled('aif_cron_process_queue');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aif_cron_process_queue');
        }
        wp_clear_scheduled_hook('aif_cron_process_queue');
        wp_clear_scheduled_hook('aif_cron_publish_event');
    }

    public static function activate()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_posts';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            industry varchar(100) DEFAULT '' NOT NULL,
            description text NOT NULL,
            status varchar(50) DEFAULT 'To do' NOT NULL,
            title text,
            content longtext,
            option_platform varchar(50) DEFAULT 'Facebook' NOT NULL,
            images text,
            image_website varchar(255) DEFAULT '',
            time_posting datetime DEFAULT '0000-00-00 00:00:00',
            post_type text,
            owner varchar(100) DEFAULT '',
            note text,
            feedback text,
            targets text,
            slug_category text,
            wp_author_id bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Table for Facebook Pages
        $table_pages = $wpdb->prefix . 'aif_facebook_pages';
        $sql .= "CREATE TABLE $table_pages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            page_name varchar(255) NOT NULL,
            page_id varchar(100) NOT NULL,
            access_token text NOT NULL,
            iv varchar(255) NOT NULL,
            expires_at datetime DEFAULT NULL,
            app_id varchar(100) DEFAULT NULL,
            app_secret text DEFAULT NULL,
            app_iv varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        $sql .= "CREATE TABLE $table_queue (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            page_id mediumint(9) NOT NULL,
            platform varchar(50) DEFAULT 'facebook' NOT NULL,
            status varchar(50) DEFAULT 'pending' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Results Table
        $table_results = $wpdb->prefix . 'aif_post_results';
        $sql .= "CREATE TABLE $table_results (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            platform varchar(50) NOT NULL,
            target_id varchar(100) DEFAULT '',
            platform_post_id varchar(255) DEFAULT '',
            link text,
            likes_count int DEFAULT 0,
            comments_count int DEFAULT 0,
            shares_count int DEFAULT 0,
            views_count int DEFAULT 0,
            reach_count int DEFAULT 0,
            metrics_updated_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Industries Table
        $table_industries = $wpdb->prefix . 'aif_industries';
        $sql .= "CREATE TABLE $table_industries (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // N8N Chat Sessions Table
        $table_n8n_chats = $wpdb->prefix . 'aif_n8n_chats';
        $sql .= "CREATE TABLE $table_n8n_chats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_psid varchar(100) NOT NULL,
            page_id varchar(100) NOT NULL,
            last_message text,
            customer_name varchar(255),
            customer_pic text,
            lead_score int DEFAULT 0,
            lead_level varchar(50) DEFAULT 'Low',
            urgency_level varchar(50) DEFAULT 'low',
            collecting_info tinyint(1) DEFAULT 0,
            needs_support tinyint(1) DEFAULT 0,
            collected_data text,
            current_field varchar(50) DEFAULT 'none',
            ai_state longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            is_viewed tinyint(1) DEFAULT 0,
            viewed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY sender_psid (sender_psid)
        ) $charset_collate;";

        // N8N Chat Messages Table (History)
        $table_n8n_messages = $wpdb->prefix . 'aif_n8n_messages';
        $sql .= "CREATE TABLE $table_n8n_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            sender varchar(20) NOT NULL, -- 'user' or 'ai'
            message text NOT NULL,
            intent varchar(100) DEFAULT 'unknown',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        // N8N Products Table (Knowledge Base for AI)
        $table_n8n_products = $wpdb->prefix . 'aif_n8n_products';
        $sql .= "CREATE TABLE $table_n8n_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_name varchar(255) NOT NULL,
            category varchar(100),
            sku varchar(100),
            description text,
            price decimal(15,2) DEFAULT 0,
            status varchar(50) DEFAULT 'active',
            createdAt datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updatedAt datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // N8N Leads Table (Capture potential customers)
        $table_n8n_leads = $wpdb->prefix . 'aif_n8n_leads';
        $sql .= "CREATE TABLE $table_n8n_leads (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id bigint(20) NOT NULL,
            name varchar(255),
            phone varchar(50),
            address text,
            notes text,
            source_page varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            is_viewed tinyint(1) DEFAULT 0,
            viewed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY session_id (session_id)
        ) $charset_collate;";

        $table_policies = $wpdb->prefix . 'aif_policies';
        $sql .= "CREATE TABLE $table_policies (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $table_media_folders    = $wpdb->prefix . 'aif_media_folders';
        $table_media_meta       = $wpdb->prefix . 'aif_media_meta';
        $table_media_file_fldrs = $wpdb->prefix . 'aif_media_file_folders';

        $sql .= "CREATE TABLE $table_media_folders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        $sql .= "CREATE TABLE $table_media_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY filename (filename)
        ) $charset_collate;";

        $sql .= "CREATE TABLE $table_media_file_fldrs (
            file_id bigint(20) NOT NULL,
            folder_id bigint(20) NOT NULL,
            PRIMARY KEY  (file_id, folder_id),
            KEY folder_id (folder_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Bảng settings riêng cho plugin (API keys, config...)
        AIF_Settings::create_table();

        // Bảng tones (giọng văn AI)
        AIF_Tones_DB::create_table();
        AIF_Tones_DB::seed_defaults();
    }

    public static function migrate_data()
    {
        global $wpdb;
        $table_posts = $wpdb->prefix . 'aif_posts';
        $table_results = $wpdb->prefix . 'aif_post_results';

        // Migrate post_type column: varchar(50) → text, and convert legacy single values to JSON array
        $col_info = $wpdb->get_row("SHOW COLUMNS FROM $table_posts WHERE Field = 'post_type'");
        if ($col_info && stripos($col_info->Type, 'varchar') !== false) {
            $wpdb->query("ALTER TABLE $table_posts MODIFY post_type text");
            // Convert existing single string values to JSON arrays (e.g. "post" → '["post"]')
            $rows = $wpdb->get_results("SELECT id, post_type FROM $table_posts WHERE post_type IS NOT NULL AND post_type != '' AND post_type NOT LIKE '[%'");
            foreach ($rows as $row) {
                $json_val = json_encode([$row->post_type]);
                $wpdb->update($table_posts, ['post_type' => $json_val], ['id' => $row->id]);
            }
        }

        // Check if table exists before migrating
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_results'") != $table_results) {
            return;
        }

        // 1. Migrate Facebook Links
        $fb_posts = $wpdb->get_results("SELECT id, link_facebook_post, targets FROM $table_posts WHERE link_facebook_post IS NOT NULL AND link_facebook_post != ''");
        foreach ($fb_posts as $p) {
            $targets = json_decode($p->targets, true);
            $target_id = '';
            if (is_array($targets)) {
                foreach ($targets as $t) {
                    if (isset($t['platform']) && $t['platform'] === 'facebook') {
                        $target_id = isset($t['id']) ? $t['id'] : '';
                        break;
                    }
                }
            }

            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_results WHERE post_id = %d AND platform = 'facebook' AND link = %s", $p->id, $p->link_facebook_post));
            if (!$exists) {
                $wpdb->insert($table_results, [
                    'post_id' => $p->id,
                    'platform' => 'facebook',
                    'target_id' => $target_id,
                    'link' => $p->link_facebook_post,
                    'created_at' => current_time('mysql')
                ]);
            }
        }

        // 2. Migrate Website Links
        $web_posts = $wpdb->get_results("SELECT id, link_web_post FROM $table_posts WHERE link_web_post IS NOT NULL AND link_web_post != ''");
        foreach ($web_posts as $p) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_results WHERE post_id = %d AND platform = 'website' AND link = %s", $p->id, $p->link_web_post));
            if (!$exists) {
                $wpdb->insert($table_results, [
                    'post_id' => $p->id,
                    'platform' => 'website',
                    'target_id' => '0',
                    'link' => $p->link_web_post,
                    'created_at' => current_time('mysql')
                ]);
            }
        }
    }
}
