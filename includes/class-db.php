<?php

/**
 * Database Helper Class
 */
class AIF_DB
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aif_posts';
    }

    /**
     * Insert a new post
     */
    /**
     * Insert a new post
     */
    public function insert($data)
    {
        global $wpdb;
        $defaults = [
            'industry' => '',
            'description' => '',
            'status' => 'To do',
            'title' => '',
            'content' => '',
            'option_platform' => 'Facebook',
            'images' => '[]',
            'image_website' => '',
            'time_posting' => '',
            'post_type' => '["post"]',
            'owner' => '',
            'note' => '',
            'feedback' => '',
            'targets' => '',
            'slug_category' => '[]',
            'wp_author_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        $data = wp_parse_args($data, $defaults);

        return $wpdb->insert($this->table_name, $data);
    }

    /**
     * Update a post
     */
    public function update($id, $data)
    {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        return $wpdb->update($this->table_name, $data, ['id' => $id]);
    }

    /**
     * Update status with log (Conceptual log)
     */
    public function update_status($id, $new_status)
    {
        global $wpdb;

        // Allow only valid statuses
        $allowed = ['To do', 'Content updated', 'Done', 'Posted successfully'];
        if (!in_array($new_status, $allowed)) {
            return false;
        }

        return $this->update($id, ['status' => $new_status]);
    }

    /**
     * Delete a post
     */
    public function delete($id)
    {
        global $wpdb;
        return $wpdb->delete($this->table_name, ['id' => $id]);
    }

    /**
     * Get single row
     */
    public function get($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
    }

    /**
     * Get all posts with filtering
     */
    public function get_posts($args = [])
    {
        global $wpdb;
        $defaults = [
            'status' => 'all',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        $args = wp_parse_args($args, $defaults);

        $where = '1=1';
        if ($args['status'] !== 'all') {
            // Handle array of statuses if needed, for simplicity string check
            $status = esc_sql($args['status']);
            $where .= " AND status = '$status'";
        }

        // Future/Scheduled check logic could go here

        $limit = intval($args['limit']);
        $offset = intval($args['offset']);
        $order = $args['order'] === 'ASC' ? 'ASC' : 'DESC';
        $orderby = esc_sql($args['orderby']);

        $sql = "SELECT * FROM $this->table_name WHERE $where ORDER BY $orderby $order LIMIT $limit OFFSET $offset";

        return $wpdb->get_results($sql);
    }

    /**
     * Get counts by status
     */
    public function get_counts()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $this->table_name GROUP BY status");

        // Initialize with default statuses ensuring 0 if no posts
        $counts = [
            'all' => 0,
            'To do' => 0,
            'Content updated' => 0,
            'Done' => 0,
            'Posted successfully' => 0
        ];

        foreach ($results as $row) {
            // Assign count to specific status key
            $counts[$row->status] = (int) $row->count;

            // Sum for 'all'
            $counts['all'] += (int) $row->count;
        }
        return (object) $counts;
    }
    public function get_results($post_id)
    {
        global $wpdb;
        $table        = $wpdb->prefix . 'aif_post_results';
        $table_pages  = $wpdb->prefix . 'aif_facebook_pages';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, fp.page_name
             FROM $table r
             LEFT JOIN $table_pages fp ON r.platform = 'facebook' AND fp.id = r.target_id
             WHERE r.post_id = %d
             ORDER BY r.created_at DESC",
            $post_id
        ));
    }

    /**
     * Industry Helpers
     */
    public function get_industries()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aif_industries ORDER BY name ASC");
    }

    public function ensure_industry($name)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aif_industries';
        $slug = sanitize_title($name);
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));

        if (!$id) {
            $wpdb->insert($table, [
                'name' => $name,
                'slug' => $slug
            ]);
            $id = $wpdb->insert_id;
        }
        return $id;
    }
}
