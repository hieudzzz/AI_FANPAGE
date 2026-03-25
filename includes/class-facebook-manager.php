<?php

class AIF_Facebook_Manager
{
    private $encryption_method = 'AES-256-CBC';

    // --- Encryption / Decryption ---

    private function get_key()
    {
        // Use AUTH_KEY plus a customized salt string for this plugin
        return hash('sha256', wp_salt('auth') . 'aif_facebook_secret');
    }

    public function encrypt_token($token)
    {
        $key = $this->get_key();
        $iv_length = openssl_cipher_iv_length($this->encryption_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($token, $this->encryption_method, $key, 0, $iv);

        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
    }

    public function decrypt_token($encrypted_base64, $iv_base64)
    {
        $key = $this->get_key();
        $iv = base64_decode($iv_base64);
        $encrypted = base64_decode($encrypted_base64);

        return openssl_decrypt($encrypted, $this->encryption_method, $key, 0, $iv);
    }

    // --- Database Operations ---

    public function save_page($data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';

        $app_id = isset($data['app_id']) ? sanitize_text_field($data['app_id']) : '';
        $app_secret = isset($data['app_secret']) ? sanitize_text_field($data['app_secret']) : '';
        $page_id = isset($data['page_id']) ? sanitize_text_field($data['page_id']) : '';

        if (!empty($page_id) && !is_numeric($page_id)) {
            return ['success' => false, 'message' => 'Page ID phải là định dạng số.'];
        }

        // Exchange for long-lived token
        $token_data = $this->exchange_token($data['access_token'], $app_id, $app_secret);

        if (isset($token_data['error'])) {
            return ['success' => false, 'message' => $token_data['error']];
        }

        $access_token = $token_data['access_token'];
        $expires_at = $token_data['expires_at'];

        // Encrypt everything
        $enc_token = $this->encrypt_token($access_token);
        $enc_app_secret = $this->encrypt_token($app_secret);

        $inserted = $wpdb->insert(
            $table_name,
            [
                'page_name' => sanitize_text_field($data['page_name']),
                'page_id' => sanitize_text_field($data['page_id']),
                'access_token' => $enc_token['encrypted'],
                'iv' => $enc_token['iv'],
                'expires_at' => $expires_at,
                'app_id' => $app_id,
                'app_secret' => $enc_app_secret['encrypted'],
                'app_iv' => $enc_app_secret['iv'],
            ]
        );

        if ($inserted !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Lỗi Database khi lưu Fanpage.'];
    }

    public function update_token($id, $new_token, $app_id = '', $app_secret = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';

        // If app credentials not provided, try to get from DB
        if (empty($app_id) || empty($app_secret)) {
            $saved = $this->get_page_app_credentials($id);
            if ($saved) {
                $app_id = $saved['app_id'];
                $app_secret = $saved['app_secret'];
            }
        }

        // Exchange for long-lived token
        $token_data = $this->exchange_token($new_token, $app_id, $app_secret);

        if (isset($token_data['error'])) {
            return ['success' => false, 'message' => $token_data['error']];
        }

        $access_token = $token_data['access_token'];
        $expires_at = $token_data['expires_at'];

        $enc_token = $this->encrypt_token($access_token);

        $updated = $wpdb->update(
            $table_name,
            [
                'access_token' => $enc_token['encrypted'],
                'iv' => $enc_token['iv'],
                'expires_at' => $expires_at,
            ],
            ['id' => $id]
        );

        if ($updated !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Lỗi Database khi cập nhật Token.'];
    }

    public function get_pages()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';
        return $wpdb->get_results("SELECT id, page_name, page_id, expires_at, app_id, created_at FROM $table_name ORDER BY id DESC");
    }

    public function get_page_app_credentials($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';
        $row = $wpdb->get_row($wpdb->prepare("SELECT app_id, app_secret, app_iv FROM $table_name WHERE id = %d", $id));

        if ($row && !empty($row->app_id) && !empty($row->app_secret)) {
            return [
                'app_id' => $row->app_id,
                'app_secret' => $this->decrypt_token($row->app_secret, $row->app_iv)
            ];
        }
        return false;
    }

    public function get_page_token($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';
        $row = $wpdb->get_row($wpdb->prepare("SELECT access_token, iv FROM $table_name WHERE id = %d", $id));

        if ($row) {
            return $this->decrypt_token($row->access_token, $row->iv);
        }
        return false;
    }

    public function has_pending_items($page_id)
    {
        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        // Check for pending or processing items for this page
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_queue WHERE page_id = %d AND status IN ('pending', 'processing')",
            $page_id
        ));
        return $count > 0;
    }

    public function has_successful_posts($id)
    {
        global $wpdb;
        $table_results = $wpdb->prefix . 'aif_post_results';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_results WHERE platform = 'facebook' AND target_id = %d",
            $id
        ));
        return intval($count) > 0;
    }

    public function get_posted_count($id)
    {
        global $wpdb;
        $table_results = $wpdb->prefix . 'aif_post_results';
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_results WHERE platform = 'facebook' AND target_id = %d",
            $id
        )));
    }

    public function update_page_info($id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';

        $update = [];
        if (!empty($data['page_name'])) {
            $update['page_name'] = sanitize_text_field($data['page_name']);
        }
        if (isset($data['app_id'])) {
            $update['app_id'] = sanitize_text_field($data['app_id']);
        }

        if (empty($update)) {
            return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật.'];
        }

        $result = $wpdb->update($table_name, $update, ['id' => $id]);
        if ($result !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Lỗi Database khi cập nhật.'];
    }

    public function delete_page($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_facebook_pages';

        if ($this->has_pending_items($id)) {
            return ['success' => false, 'message' => 'Không thể xóa: Còn bài viết đang chờ đăng trên Fanpage này.'];
        }

        if ($this->has_successful_posts($id)) {
            return ['success' => false, 'message' => 'Không thể xóa: Fanpage này đã có bài đăng thành công.'];
        }

        $result = $wpdb->delete($table_name, ['id' => $id]);
        if ($result !== false) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Lỗi Database'];
    }

    /**
     * Check if a post is currently in the queue (pending/scheduled/processing)
     */
    public function is_post_queued($post_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aif_posting_queue';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND status IN ('pending', 'scheduled', 'processing')",
            $post_id
        ));
        return $count > 0;
    }

    // --- Facebook Graph API ---

    public function exchange_token($short_lived_token, $app_id = '', $app_secret = '')
    {
        if (empty($app_id) || empty($app_secret)) {
            return ['access_token' => $short_lived_token, 'expires_at' => null];
        }

        $url = "https://graph.facebook.com/v19.0/oauth/access_token";
        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $app_id,
            'client_secret' => $app_secret,
            'fb_exchange_token' => $short_lived_token
        ];

        $response = wp_remote_get(add_query_arg($params, $url));

        if (is_wp_error($response)) {
            return ['error' => 'Lỗi kết nối Facebook: ' . $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || isset($body['error'])) {
            $msg = isset($body['error']['message']) ? $body['error']['message'] : 'Không thể đổi token dài hạn.';
            return ['error' => 'Facebook API Error: ' . $msg];
        }

        if (isset($body['access_token'])) {
            $expires_at = null;
            if (isset($body['expires_in'])) {
                $expires_at = date('Y-m-d H:i:s', time() + intval($body['expires_in']));
            }
            return [
                'access_token' => $body['access_token'],
                'expires_at' => $expires_at
            ];
        }

        return ['error' => 'Phản hồi từ Facebook không hợp lệ.'];
    }

    // --- Queue & Posting ---

    public function add_to_queue($post_id, $page_id, $status = 'pending', $platform = 'facebook')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_posting_queue';

        return $wpdb->insert(
            $table_name,
            [
                'post_id' => $post_id,
                'page_id' => $page_id,
                'platform' => $platform,
                'status' => $status
            ]
        );
    }

    public function get_queue_items()
    {
        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        $table_posts = $wpdb->prefix . 'aif_posts';
        $table_pages = $wpdb->prefix . 'aif_facebook_pages';

        // Join to get clear names
        $sql = "SELECT q.*, p.title as post_title, p.time_posting, f.page_name 
                FROM $table_queue q
                LEFT JOIN $table_posts p ON q.post_id = p.id
                LEFT JOIN $table_pages f ON q.page_id = f.id
                ORDER BY q.created_at ASC";

        return $wpdb->get_results($sql);
    }

    public function delete_queue_item($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_posting_queue';
        return $wpdb->delete($table_name, ['id' => $id]);
    }

    public function delete_post_from_queue($post_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_posting_queue';
        return $wpdb->delete($table_name, ['post_id' => $post_id]);
    }

    public function check_scheduled_posts()
    {
        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        $table_posts = $wpdb->prefix . 'aif_posts';

        // So sánh trực tiếp bằng NOW() của MySQL để tránh lệch timezone PHP vs DB
        $sql = "SELECT q.id 
                FROM $table_queue q
                JOIN $table_posts p ON q.post_id = p.id
                WHERE q.status = 'scheduled' 
                AND p.time_posting != '0000-00-00 00:00:00'
                AND p.time_posting IS NOT NULL";

        $results = $wpdb->get_results($sql);

        if ($results) {
            $now_php   = current_time('mysql');       // WP local time
            $now_utc   = current_time('mysql', true); // UTC
            error_log('[AIF] check_scheduled_posts: wp_local=' . $now_php . ' utc=' . $now_utc . ' found_scheduled=' . count($results));

            foreach ($results as $row) {
                // Lấy time_posting của item này
                $item = $wpdb->get_row($wpdb->prepare(
                    "SELECT p.time_posting FROM $table_queue q JOIN $table_posts p ON q.post_id = p.id WHERE q.id = %d",
                    $row->id
                ));

                if (!$item) continue;

                error_log('[AIF] check_scheduled_posts: queue_id=' . $row->id . ' time_posting=' . $item->time_posting . ' now_php=' . $now_php);

                // Thử cả 2: so sánh với giờ local và UTC
                if ($item->time_posting <= $now_php || $item->time_posting <= $now_utc) {
                    $wpdb->update(
                        $table_queue,
                        ['status' => 'pending'],
                        ['id' => $row->id]
                    );
                    error_log('[AIF] check_scheduled_posts: queue_id=' . $row->id . ' -> changed to pending');
                }
            }
        } else {
            error_log('[AIF] check_scheduled_posts: no scheduled items found');
        }
    }

    public function process_queue()
    {
        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        $table_posts = $wpdb->prefix . 'aif_posts';

        // Get Pending Items
        $items = $wpdb->get_results("SELECT * FROM $table_queue WHERE status = 'pending' LIMIT 5");

        error_log('[AIF] process_queue: found ' . count((array)$items) . ' pending items');

        if (empty($items))
            return;

        foreach ($items as $item) {
            error_log('[AIF] process_queue: processing queue id=' . $item->id . ' post_id=' . $item->post_id . ' platform=' . $item->platform . ' page_id=' . $item->page_id);

            // Mark as processing
            $wpdb->update($table_queue, ['status' => 'processing'], ['id' => $item->id]);

            // Get Post Content
            $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_posts WHERE id = %d", $item->post_id));
            if (!$post) {
                $wpdb->update($table_queue, ['status' => 'failed_no_post'], ['id' => $item->id]);
                continue;
            }

            if ($item->platform === 'website') {
                require_once AIF_PATH . 'includes/class-publisher.php';
                $publisher = new AIF_Publisher();
                $result = $publisher->publish_to_website($item->post_id);

                if ($result['success']) {
                    $wpdb->update($table_posts, [
                        'status' => 'Posted successfully',
                        'updated_at' => current_time('mysql')
                    ], ['id' => $item->post_id]);

                    // Save each post type result as a separate row
                    if (!empty($result['results'])) {
                        foreach ($result['results'] as $r) {
                            if (!empty($r['success']) && !empty($r['link'])) {
                                $pt_label = isset($r['post_type']) ? $r['post_type'] : '';
                                $wpdb->insert($wpdb->prefix . 'aif_post_results', [
                                    'post_id' => $item->post_id,
                                    'platform' => 'website',
                                    'target_id' => $pt_label,
                                    'link' => $r['link'],
                                    'created_at' => current_time('mysql')
                                ]);
                            }
                        }
                    } else {
                        // Fallback: single link (legacy)
                        $wpdb->insert($wpdb->prefix . 'aif_post_results', [
                            'post_id' => $item->post_id,
                            'platform' => 'website',
                            'target_id' => '0',
                            'link' => $result['link'],
                            'created_at' => current_time('mysql')
                        ]);
                    }

                    $wpdb->delete($table_queue, ['id' => $item->id]);
                } else {
                    $wpdb->update($table_queue, ['status' => 'failed: ' . substr($result['message'], 0, 40)], ['id' => $item->id]);
                }
                continue;
            }

            // Get Page Token
            $token = $this->get_page_token($item->page_id);
            if (!$token) {
                $wpdb->update($table_queue, ['status' => 'failed_no_token'], ['id' => $item->id]);
                continue;
            }

            // Get Images
            $images_list = [];
            if (!empty($post->images)) {
                $decoded = json_decode($post->images, true);
                if (is_array($decoded)) {
                    $images_list = $decoded;
                }
            }

            // Call API (Multi or Single)
            if (count($images_list) > 1) {
                $result = $this->post_multi_photos_to_fb_api($token, $post->content, $item->page_id, $images_list);
            } else {
                $image_url = '';
                $image_path = '';
                if (!empty($images_list)) {
                    $filename = $images_list[0];
                    list($image_url, $image_path) = $this->resolve_image($filename);
                }
                $result = $this->post_to_fb_api($token, $post->content, $item->page_id, $image_url, $image_path);
            }

            if ($result['success']) {
                // Success
                // Format Link
                $fb_post_id = $result['id'];
                $final_link = "https://www.facebook.com/" . $fb_post_id; // Fallback

                if (strpos($fb_post_id, '_') !== false) {
                    $parts = explode('_', $fb_post_id);
                    if (count($parts) == 2) {
                        $final_link = "https://www.facebook.com/" . $parts[0] . "/posts/" . $parts[1];
                    }
                }

                // 1. Update Main Post Status
                $wpdb->update(
                    $table_posts,
                    [
                        'status' => 'Posted successfully',
                        'updated_at' => current_time('mysql')
                    ],
                    ['id' => $item->post_id]
                );

                // 2. Save Result to New Table
                $wpdb->insert($wpdb->prefix . 'aif_post_results', [
                    'post_id' => $item->post_id,
                    'platform' => 'facebook',
                    'target_id' => $item->page_id,
                    'platform_post_id' => $fb_post_id,
                    'link' => $final_link,
                    'created_at' => current_time('mysql')
                ]);

                // 3. Remove from Queue (as requested by user)
                $wpdb->delete($table_queue, ['id' => $item->id]);
            } else {
                // Fail
                $wpdb->update($table_queue, ['status' => 'failed: ' . substr($result['message'], 0, 40)], ['id' => $item->id]);
            }
        }
    }

    private function post_to_fb_api($access_token, $message, $page_id_db_ref, $image_url = '', $image_path = '')
    {
        global $wpdb;
        // Get actual Page ID string
        $page_row = $wpdb->get_row($wpdb->prepare("SELECT page_id FROM {$wpdb->prefix}aif_facebook_pages WHERE id = %d", $page_id_db_ref));
        if (!$page_row)
            return ['success' => false, 'message' => 'Page not found'];

        $fb_page_id = $page_row->page_id;

        // --- METHOD 1: Binary Upload (Preferred for Localhost) ---
        if ($image_path && file_exists($image_path)) {
            $url = "https://graph.facebook.com/v19.0/{$fb_page_id}/photos";

            // Use Raw cURL for valid Multipart/form-data with file
            $ch = curl_init();
            $cfile = new CURLFile($image_path, mime_content_type($image_path), basename($image_path));

            $data = [
                'caption' => $message,
                'source' => $cfile,
                'access_token' => $access_token
            ];

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Handle local cert issues if any

            $response_body = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error_msg = curl_error($ch);
            curl_close($ch);

            if ($error_msg) {
                return ['success' => false, 'message' => 'cURL Error: ' . $error_msg];
            }

            $body_response = json_decode($response_body, true);

            if ($http_code == 200 && (isset($body_response['id']) || isset($body_response['post_id']))) {
                $final_id = isset($body_response['post_id']) ? $body_response['post_id'] : $body_response['id'];
                return ['success' => true, 'id' => $final_id];
            } else {
                return ['success' => false, 'message' => isset($body_response['error']['message']) ? $body_response['error']['message'] : 'FB API Error'];
            }
        }

        // --- METHOD 2: URL Upload (Fallback) ---
        if ($image_url) {
            $url = "https://graph.facebook.com/v19.0/{$fb_page_id}/photos";
            $body = [
                'caption' => $message,
                'url' => $image_url,
                'access_token' => $access_token
            ];
        } else {
            $url = "https://graph.facebook.com/v19.0/{$fb_page_id}/feed";
            $body = [
                'message' => $message,
                'access_token' => $access_token
            ];
        }

        $response = wp_remote_post($url, [
            'body' => $body,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if ($code == 200 && (isset($body_response['id']) || isset($body_response['post_id']))) {
            // Photos endpoint returns 'id' and 'post_id'. We want 'post_id' if available for linking to feed.
            $final_id = isset($body_response['post_id']) ? $body_response['post_id'] : $body_response['id'];
            return ['success' => true, 'id' => $final_id];
        } else {
            return ['success' => false, 'message' => isset($body_response['error']['message']) ? $body_response['error']['message'] : 'Unknown Error'];
        }
    }

    public function fetch_post_metrics($result_id)
    {
        global $wpdb;
        $table_results = $wpdb->prefix . 'aif_post_results';

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_results WHERE id = %d", $result_id));
        if (!$row || $row->platform !== 'facebook' || empty($row->platform_post_id)) {
            return ['success' => false, 'message' => 'Valid result or platform post ID not found'];
        }

        $token = $this->get_page_token($row->target_id);
        if (!$token) {
            return ['success' => false, 'message' => 'Page token not found'];
        }

        $fb_post_id = $row->platform_post_id;

        // 1. Fetch Interaction Summary (likes, comments, shares)
        $url_summary = "https://graph.facebook.com/v19.0/{$fb_post_id}?fields=likes.summary(true),shares,comments.summary(true)&access_token={$token}";
        $response_summary = wp_remote_get($url_summary);

        if (is_wp_error($response_summary)) {
            return ['success' => false, 'message' => 'API Error (Summary): ' . $response_summary->get_error_message()];
        }

        $data_summary = json_decode(wp_remote_retrieve_body($response_summary), true);

        $likes = isset($data_summary['likes']['summary']['total_count']) ? intval($data_summary['likes']['summary']['total_count']) : 0;
        $comments = isset($data_summary['comments']['summary']['total_count']) ? intval($data_summary['comments']['summary']['total_count']) : 0;
        $shares = isset($data_summary['shares']['count']) ? intval($data_summary['shares']['count']) : 0;

        // 2. Fetch Insights (Impressions/Views and Reach)
        $url_insights = "https://graph.facebook.com/v19.0/{$fb_post_id}/insights?metric=post_impressions,post_impressions_unique&access_token={$token}";
        $response_insights = wp_remote_get($url_insights);

        $views = 0;
        $reach = 0;

        if (!is_wp_error($response_insights)) {
            $data_insights = json_decode(wp_remote_retrieve_body($response_insights), true);
            if (isset($data_insights['data'])) {
                foreach ($data_insights['data'] as $metric) {
                    if ($metric['name'] === 'post_impressions') {
                        $views = intval($metric['values'][0]['value']);
                    } elseif ($metric['name'] === 'post_impressions_unique') {
                        $reach = intval($metric['values'][0]['value']);
                    }
                }
            }
        }

        // Update Database
        $wpdb->update($table_results, [
            'likes_count' => $likes,
            'comments_count' => $comments,
            'shares_count' => $shares,
            'views_count' => $views,
            'reach_count' => $reach,
            'metrics_updated_at' => current_time('mysql')
        ], ['id' => $result_id]);

        return [
            'success' => true,
            'data' => [
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'views' => $views,
                'reach' => $reach,
                'updated_at' => current_time('mysql')
            ]
        ];
    }

    /**
     * Post multiple photos to Facebook gallery
     */
    private function post_multi_photos_to_fb_api($access_token, $message, $page_id_db_ref, $images_list)
    {
        global $wpdb;
        $page_row = $wpdb->get_row($wpdb->prepare("SELECT page_id FROM {$wpdb->prefix}aif_facebook_pages WHERE id = %d", $page_id_db_ref));
        if (!$page_row)
            return ['success' => false, 'message' => 'Page not found'];
        $fb_page_id = $page_row->page_id;

        $attached_media = [];
        $errors = [];

        foreach ($images_list as $filename) {
            list($image_url, $image_path) = $this->resolve_image($filename);

            // Try binary upload first (local file), fallback to URL upload
            if ($image_path && file_exists($image_path)) {
                $url_api = "https://graph.facebook.com/v19.0/{$fb_page_id}/photos";
                $ch = curl_init();
                $cfile = new CURLFile($image_path, mime_content_type($image_path), basename($image_path));

                $data = [
                    'source' => $cfile,
                    'published' => 'false',
                    'access_token' => $access_token
                ];

                curl_setopt($ch, CURLOPT_URL, $url_api);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                curl_close($ch);

                $res_data = json_decode($response, true);
                if (isset($res_data['id'])) {
                    $attached_media[] = ['media_fbid' => $res_data['id']];
                } else {
                    $errors[] = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'Upload failed (binary)';
                }
            } elseif ($image_url) {
                // URL upload (WP Media or remote)
                $url_api = "https://graph.facebook.com/v19.0/{$fb_page_id}/photos";
                $fb_resp = wp_remote_post($url_api, [
                    'body' => [
                        'url'          => $image_url,
                        'published'    => 'false',
                        'access_token' => $access_token,
                    ],
                    'timeout' => 60,
                ]);
                $res_data = !is_wp_error($fb_resp) ? json_decode(wp_remote_retrieve_body($fb_resp), true) : [];
                if (isset($res_data['id'])) {
                    $attached_media[] = ['media_fbid' => $res_data['id']];
                } else {
                    $errors[] = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'Upload failed (url)';
                }
            } else {
                $errors[] = 'File not found: ' . $filename;
            }
        }

        if (empty($attached_media)) {
            return ['success' => false, 'message' => 'No images uploaded: ' . implode(', ', $errors)];
        }

        // Create main feed post with attached media
        $feed_url = "https://graph.facebook.com/v19.0/{$fb_page_id}/feed";
        $feed_data = [
            'message' => $message,
            'attached_media' => json_encode($attached_media),
            'access_token' => $access_token
        ];

        $response = wp_remote_post($feed_url, [
            'body' => $feed_data,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['id'])) {
            return ['success' => true, 'id' => $body['id']];
        } else {
            return ['success' => false, 'message' => isset($body['error']['message']) ? $body['error']['message'] : 'Failed to create feed post'];
        }
    }

    /**
     * Get user profile info from PSID
     */
    public function get_user_profile($psid, $access_token)
    {
        $url = "https://graph.facebook.com/v19.0/{$psid}";
        $url = add_query_arg([
            'fields' => 'name,profile_pic',
            'access_token' => $access_token
        ], $url);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Resolve a stored image value to [url, local_path].
     * Handles two cases:
     *   - 'wp-att-{id}'  → WordPress Media Library attachment
     *   - 'filename.jpg' → Plugin's own /upload/ folder
     *
     * @param  string $value  The value stored in aif_posts.images JSON array
     * @return array  [ string $url, string|null $local_path ]
     */
    private function resolve_image($value)
    {
        if (strpos($value, 'wp-att-') === 0) {
            $att_id = intval(substr($value, 7));
            $url    = wp_get_attachment_url($att_id);
            // For local installs, try to get the real file path so binary upload works
            $path   = get_attached_file($att_id);
            return [$url ?: '', ($path && file_exists($path)) ? $path : null];
        }

        // Plugin upload folder
        $url  = AIF_URL  . 'upload/' . $value;
        $path = AIF_PATH . 'upload/' . $value;
        return [$url, $path];
    }
}
