<?php
class AIF_Publisher
{
    public function __construct()
    {
        // Init
    }

    /**
     * Publish a post to its designated platform
     * 
     * @param object $post The DB row object
     * @return array ['success' => bool, 'message' => string]
     */
    public function publish_post($post)
    {
        // Mocking the publishing process
        // In real life, we would use $post->content, $post->media_url, etc.

        $platform = $post->option_platform; // Correct column name is option_platform
        $content = $post->content;

        // Handle Images (JSON)
        $media = [];
        if (!empty($post->images)) {
            $images = json_decode($post->images, true);
            if (is_array($images) && !empty($images)) {
                // If local upload, create full URL
                $upload_url = AIF_URL . 'upload/';
                foreach ($images as $img) {
                    $media[] = $upload_url . $img;
                }
            }
        }

        switch ($platform) {
            case 'facebook':
                return $this->publish_to_facebook($content, $media);
            case 'linkedin':
                return $this->publish_to_linkedin($content, $media);
            case 'website':
                return $this->publish_to_website($post->id); // Self-publish?
            default:
                return ['success' => false, 'message' => 'Unknown platform'];
        }
    }

    private function publish_to_facebook($text, $media)
    {
        // MOCK API Call
        // $fb = new Facebook(...);
        // ...
        return ['success' => true, 'message' => 'Posted to FB Page ID 12345'];
    }

    private function publish_to_linkedin($text, $media)
    {
        // MOCK API Call
        return ['success' => true, 'message' => 'Posted to LinkedIn Profile'];
    }

    public function publish_to_website($post_id)
    {
        $db = new AIF_DB();
        $ai_post = $db->get($post_id);

        if (!$ai_post) {
            return ['success' => false, 'message' => 'AI Post not found'];
        }

        // Support multi post types (JSON array) or legacy single string
        $post_types = ['post'];
        if (!empty($ai_post->post_type)) {
            $decoded = json_decode($ai_post->post_type, true);
            if (is_array($decoded) && !empty($decoded)) {
                $post_types = $decoded;
            } else {
                $post_types = [$ai_post->post_type];
            }
        }

        // Parse categories once
        $cat_ids = [];
        if (!empty($ai_post->slug_category)) {
            $cat_ids = json_decode($ai_post->slug_category, true);
            if (!is_array($cat_ids)) {
                $cat_ids = is_numeric($ai_post->slug_category) ? [intval($ai_post->slug_category)] : [];
            }
        }

        // Resolve featured image attachment ID once (reuse for all post types)
        $featured_attach_id = null;
        if (!empty($ai_post->image_website)) {
            $image_val = $ai_post->image_website;
            if (strpos($image_val, 'wp-att-') === 0) {
                $featured_attach_id = intval(substr($image_val, 7));
            } else {
                $image_path = AIF_PATH . 'upload/' . $image_val;
                if (file_exists($image_path)) {
                    $filename = basename($image_path);
                    global $wpdb;
                    $featured_attach_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
                        '%' . $filename
                    ));
                    if (!$featured_attach_id) {
                        $file_content = file_get_contents($image_path);
                        $upload = wp_upload_bits($filename, null, $file_content);
                        if (!$upload['error']) {
                            $attachment = array(
                                'post_mime_type' => mime_content_type($upload['file']),
                                'post_title' => sanitize_file_name($filename),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            );
                            $att_id = wp_insert_attachment($attachment, $upload['file']);
                            if (!is_wp_error($att_id)) {
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                $attach_data = wp_generate_attachment_metadata($att_id, $upload['file']);
                                wp_update_attachment_metadata($att_id, $attach_data);
                                $featured_attach_id = $att_id;
                            }
                        }
                    }
                }
            }
        }

        $results = [];
        $all_success = true;
        $first_link = '';

        foreach ($post_types as $wp_post_type) {
            $post_data = [
                'post_title' => $ai_post->title,
                'post_content' => $ai_post->content,
                'post_status' => 'publish',
                'post_author' => get_current_user_id() ?: 1,
                'post_type' => $wp_post_type
            ];

            $new_post_id = wp_insert_post($post_data);

            if (is_wp_error($new_post_id)) {
                $all_success = false;
                $results[] = ['post_type' => $wp_post_type, 'success' => false, 'message' => $new_post_id->get_error_message()];
                continue;
            }

            // Assign categories for this post type
            if (!empty($cat_ids)) {
                $taxonomies = get_object_taxonomies($wp_post_type, 'objects');
                $tax_name = '';
                foreach ($taxonomies as $tax) {
                    if ($tax->hierarchical) {
                        $tax_name = $tax->name;
                        break;
                    }
                }
                if ($tax_name) {
                    wp_set_object_terms($new_post_id, array_map('intval', $cat_ids), $tax_name);
                }
            }

            // Set Featured Image
            if ($featured_attach_id && !is_wp_error($featured_attach_id)) {
                set_post_thumbnail($new_post_id, $featured_attach_id);
            }

            $link = get_permalink($new_post_id);
            if (empty($first_link)) {
                $first_link = $link;
            }
            $results[] = ['post_type' => $wp_post_type, 'success' => true, 'id' => $new_post_id, 'link' => $link];
        }

        if ($all_success) {
            $type_names = implode(', ', $post_types);
            return ['success' => true, 'message' => 'Published to website (' . $type_names . ')', 'results' => $results, 'link' => $first_link];
        } else {
            $failed = array_filter($results, function ($r) {
                return !$r['success'];
            });
            $failed_types = array_column($failed, 'post_type');
            return ['success' => false, 'message' => 'Failed for: ' . implode(', ', $failed_types), 'results' => $results, 'link' => $first_link];
        }
    }
}
