<?php
$db = new AIF_DB();
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'edit';
$is_new = ($action === 'new');

$post = null;
$is_locked = false;
$lock_reason = ''; // 'queued' or 'posted'

if (!$is_new && $post_id) {
    $post = $db->get($post_id);
    if (!$post) {
        $is_new = true;
        $post_id = 0;
    } else {
        // Check if post is in queue
        $fb_manager_lock = new AIF_Facebook_Manager();
        if ($fb_manager_lock->is_post_queued($post_id)) {
            $is_locked = true;
            $lock_reason = 'queued';
        } elseif ($post->status === 'Posted successfully') {
            $is_locked = true;
            $lock_reason = 'posted';
        }

        // Check for failed queue items for this post
        global $wpdb;
        $failed_queue_items = $wpdb->get_results($wpdb->prepare(
            "SELECT q.id, q.status, q.platform, f.page_name
             FROM {$wpdb->prefix}aif_posting_queue q
             LEFT JOIN {$wpdb->prefix}aif_facebook_pages f ON q.page_id = f.id
             WHERE q.post_id = %d AND q.status LIKE 'failed%'",
            $post_id
        ));
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('aif_save_post')) {
    // Block if locked - just set error, don't process form
    $lock_error_message = '';
    if ($is_locked) {
        if ($lock_reason === 'queued') {
            $lock_error_message = 'Không thể lưu: Bài viết đang trong hàng chờ. Vui lòng gỡ khỏi hàng chờ trước.';
        } else {
            $lock_error_message = 'Không thể lưu: Bài viết đã được đăng thành công, không cho phép chỉnh sửa.';
        }
    }
    if (!$lock_error_message) {
        // V2 Fields
        $description = isset($_POST['aif_description']) ? wp_kses_post(wp_unslash($_POST['aif_description'])) : '';
        $industry = sanitize_text_field(wp_unslash($_POST['aif_industry']));
        $option_platform = sanitize_text_field(wp_unslash($_POST['aif_option']));
        $title = sanitize_text_field(wp_unslash($_POST['post_title']));
        $content = wp_kses_post(wp_unslash($_POST['post_content']));
        $owner = sanitize_text_field(wp_unslash($_POST['aif_owner']));
        if (isset($_POST['aif_images_order']) && !empty($_POST['aif_images_order'])) {
            $images = wp_unslash($_POST['aif_images_order']);
        } else {
            $images = isset($_POST['aif_images']) ? json_encode(wp_unslash($_POST['aif_images'])) : '[]';
        }
        $image_website = isset($_POST['aif_image_website']) ? sanitize_text_field(wp_unslash($_POST['aif_image_website'])) : '';
        $post_type = isset($_POST['aif_post_type']) ? array_map('sanitize_text_field', $_POST['aif_post_type']) : ['post'];
        $post_type_json = json_encode(array_values($post_type));
        $wp_category_ids = isset($_POST['aif_wp_category']) ? array_map('intval', $_POST['aif_wp_category']) : [];
        $wp_category_json = json_encode($wp_category_ids);
        $feedback = isset($_POST['aif_feedback']) ? sanitize_textarea_field(wp_unslash($_POST['aif_feedback'])) : '';

        // Status Action
        $status_action = isset($_POST['aif_status_action']) ? $_POST['aif_status_action'] : '';
        $manual_status = isset($_POST['aif_status_manual']) ? sanitize_text_field($_POST['aif_status_manual']) : '';

        $current_db_status = $post ? $post->status : 'To do';
        $new_status = $current_db_status;

        // Use manual status if selected in dropdown
        if ($manual_status && $manual_status !== $current_db_status) {
            $new_status = $manual_status;
        }

        // Strict Workflow Transition logic - Overridden by button actions if present
        if ($status_action === 'generate') {
            $new_status = 'Content updated';
        }
        if ($status_action === 'done') {
            $new_status = 'Done';
        }
        // Check valid target pages (Multi-select)
        $fb_manager = new AIF_Facebook_Manager();
        $target_page_ids = isset($_POST['aif_target_pages']) ? $_POST['aif_target_pages'] : [];
        $target_website = isset($_POST['aif_target_website']) ? 1 : 0;

        // Construct Targets JSON
        $targets_data = [];
        if (is_array($target_page_ids)) {
            foreach ($target_page_ids as $pid) {
                $targets_data[] = [
                    'platform' => 'facebook',
                    'id' => intval($pid)
                ];
            }
        }
        if ($target_website) {
            $targets_data[] = [
                'platform' => 'website',
                'id' => 0
            ];
        }
        $targets_json = json_encode($targets_data);

        // Schedule
        $schedule_raw = sanitize_text_field($_POST['aif_schedule']);
        $schedule = $schedule_raw ? str_replace('T', ' ', $schedule_raw) . ':00' : '0000-00-00 00:00:00';

        // Check if valid future date
        $is_scheduled_future = false;
        if ($schedule && $schedule !== '0000-00-00 00:00:00' && $schedule > current_time('mysql')) {
            $is_scheduled_future = true;
        }

        // 1. VALIDATION for 'Done' Status
        if ($status_action === 'done' || $new_status === 'Done') {
            $errors = [];
            if (empty($title)) $errors[] = 'Tiêu đề không được để trống khi hoàn tất.';
            if (empty($content)) $errors[] = 'Nội dung không được để trống khi hoàn tất.';
            if (empty($targets_data)) $errors[] = 'Vui lòng chọn ít nhất một nơi đăng bài (Fanpage hoặc Website).';

            if (!empty($errors)) {
                $_SESSION['aif_message'] = 'Lỗi: ' . implode(' ', $errors);
                // Redirect back to same page to show error
                $redirect_url = admin_url('admin.php?page=ai-fanpage-post-detail' . ($is_new ? '&action=new' : '&id=' . $post_id));
                echo "<script>window.location.href='$redirect_url';</script>";
                exit;
            }
        }

        // 2. PREPARE TRIGGER FLAGS
        $trigger_add_to_queue = false;
        $trigger_queue_status = 'pending';
        $trigger_process_now = false;

        if ($new_status === 'Done' && $current_db_status !== 'Done') {
            if ($is_scheduled_future) {
                // Future
                $trigger_add_to_queue = true;
                $trigger_queue_status = 'scheduled';
                $_SESSION['aif_message'] = 'Đã chuyển sang Done & Lên lịch đăng: ' . $schedule;
            } else {
                // Immediate
                $trigger_add_to_queue = true;
                $trigger_queue_status = 'pending';
                $trigger_process_now = true;
                $_SESSION['aif_message'] = 'Đã chuyển sang Done & Đang đăng bài...';
            }
        }

        if ($industry) {
            $db->ensure_industry($industry);
        }

        $data = [
            'description' => $description,
            'industry' => $industry,
            'option_platform' => $option_platform,
            'title' => $title,
            'content' => $content,
            'status' => $new_status,
            'owner' => $owner,
            'images' => $images,
            'image_website' => $image_website,
            'post_type' => $post_type_json,
            'slug_category' => $wp_category_json,
            'feedback' => $feedback,
            'time_posting' => $schedule,
            'targets' => $targets_json,
            'wp_author_id' => get_current_user_id()
        ];

        // 3. DATABASE SAVE
        if ($is_new) {
            $data['created_at'] = current_time('mysql');
            if (!$manual_status && !$status_action) {
                $data['status'] = 'To do';
            }
            $result = $db->insert($data);
            if ($result) {
                global $wpdb;
                $post_id = $wpdb->insert_id;
            }
        } else {
            $result = $db->update($post_id, $data);
        }

        // 4. POST SAVE ACTIONS (Unified)
        if ($result && $post_id) {
            if ($trigger_add_to_queue && !empty($targets_data)) {
                // Ensure we don't double queue if somehow triggered twice
                foreach ($targets_data as $target) {
                    $pid = isset($target['id']) ? $target['id'] : 0;
                    $platform = isset($target['platform']) ? $target['platform'] : 'facebook';
                    $fb_manager->add_to_queue($post_id, $pid, $trigger_queue_status, $platform);
                }
            }

            if ($trigger_process_now) {
                $fb_manager->process_queue();
                $updated_post = $db->get($post_id);
                if ($updated_post && $updated_post->status === 'Posted successfully') {
                    // Determine which platforms were targeted for a more accurate message
                    $has_fb = false;
                    $has_web = false;
                    if (!empty($targets_data)) {
                        foreach ($targets_data as $target) {
                            if (($target['platform'] ?? 'facebook') === 'website') $has_web = true;
                            else $has_fb = true;
                        }
                    }

                    if ($has_fb && $has_web) {
                        $_SESSION['aif_message'] = 'Đã đăng bài thành công lên Fanpage và Website!';
                    } elseif ($has_web) {
                        $_SESSION['aif_message'] = 'Đã đăng bài thành công lên Website!';
                    } else {
                        $_SESSION['aif_message'] = 'Đã đăng bài thành công lên Fanpage!';
                    }
                } elseif ($updated_post && strpos($updated_post->status, 'failed') !== false) {
                    $_SESSION['aif_message'] = 'Lỗi khi đăng bài: ' . $updated_post->status;
                }
            }

            // Final Redirect - avoid double success msg
            $msg_param = $trigger_process_now ? '' : '&aif_msg=saved';
            $redirect_url = admin_url('admin.php?page=ai-fanpage-post-detail&id=' . $post_id . $msg_param);
            echo "<script>window.location.href='$redirect_url';</script>";
            exit;
        } else {
            // Handle save error
            $_SESSION['aif_message'] = 'Lỗi: Không thể lưu bài viết vào Database.';
        }
    } // end if (!$lock_error_message)
}

// Defaults
$current_option = $post ? $post->option_platform : 'Facebook';
$current_schedule = ($post && $post->time_posting && $post->time_posting != '0000-00-00 00:00:00')
    ? date('Y-m-d\TH:i', strtotime($post->time_posting)) : '';
$current_title = $post ? $post->title : '';
$current_content = $post ? $post->content : '';
$current_status = $post ? $post->status : 'To do';

// Parse JSON content if AI returned a JSON object with title/content keys
$json_data = json_decode($current_content, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($json_data) && isset($json_data['content'])) {
    if (!empty($json_data['title'])) {
        $current_title = $json_data['title'];
    }
    $current_content = $json_data['content'];
    if (!empty($json_data['hashtags'])) {
        $current_content .= "\n\n" . $json_data['hashtags'];
    }
}
?>


<div class="aif-premium-detail">
    <div class="aif-header-section">
        <h1>
            <span class="dashicons dashicons-edit-page" style="color: var(--aif-primary); font-size: 24px;"></span>
            <?php echo $post_id ? 'Chi tiết bài viết' . (isset($post->stt) ? ' #' . $post->stt : '')    : 'Tạo bài viết mới'; ?>
        </h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php
            $status_class = AIF_Status::badge_class($current_status);
            ?>
            <span class="aif-status-pill <?php echo $status_class; ?>">
                <?php echo AIF_Status::label($current_status); ?>
            </span>
            <div class="aif-form-group" style="margin: 0; position: relative;">
                <select name="aif_status_manual" id="aif_manual_status" form="aif-post-form" class="aif-select"
                    style="padding: 6px 30px 6px 12px; font-weight: 700; border-radius: 8px; border: 1px solid var(--aif-border-light); background: #fff; appearance: none; cursor: <?php echo $is_locked ? 'not-allowed' : 'pointer'; ?>; color: var(--aif-text-main); font-size: 13px; min-width: 140px;"
                    <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <option value="To do" <?php selected($current_status, 'To do'); ?>>To do (Chờ soạn)</option>
                    <option value="Content updated" <?php selected($current_status, 'Content updated'); ?>>Content updated (Đã soạn)</option>
                    <option value="Done" <?php selected($current_status, 'Done'); ?>>Done (Chuyển đăng)</option>
                    <?php if ($current_status === 'Posted successfully'): ?>
                        <option value="Posted successfully" selected>Posted successfully (Đã đăng)</option>
                    <?php endif; ?>
                </select>
                <span class="dashicons dashicons-arrow-down-alt2"
                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 14px; color: #94a3b8; <?php echo $is_locked ? 'display:none;' : ''; ?>"></span>
            </div>
        </div>
    </div>

    <?php
    // Thu thập tất cả thông báo cần hiển thị
    $aif_toast_messages = [];

    if (isset($_GET['aif_msg']) && $_GET['aif_msg'] === 'saved') {
        $aif_toast_messages[] = ['msg' => 'Đã lưu thay đổi bài viết thành công!', 'type' => 'success'];
    }
    if (isset($_SESSION['aif_message'])) {
        $msg  = $_SESSION['aif_message'];
        $type = (strpos($msg, 'Lỗi') !== false) ? 'error' : 'success';
        $aif_toast_messages[] = ['msg' => $msg, 'type' => $type];
        unset($_SESSION['aif_message']);
    }
    if (!empty($aif_toast_messages)):
    ?>
        <script>
            (function waitForToast() {
                if (window.AIF_Toast) {
                    <?php foreach ($aif_toast_messages as $i => $t): ?>
                        setTimeout(function() {
                            AIF_Toast.show(<?php echo json_encode($t['msg']); ?>, <?php echo json_encode($t['type']); ?>);
                        }, <?php echo $i * 600; ?>);
                    <?php endforeach; ?>
                } else {
                    setTimeout(waitForToast, 80);
                }
            })();
        </script>
    <?php endif; ?>

    <?php if ($is_locked && $lock_reason === 'queued'): ?>
        <div class="aif-status-banner banner-warning">
            <div class="banner-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <div class="banner-content">
                <span class="banner-title">Bài viết đang trong hàng chờ</span>
                <p class="banner-desc">Nội dung hiện đang bị khóa để chuẩn bị đăng. Nếu bạn muốn chỉnh sửa, vui lòng xóa bài viết khỏi hàng chờ bên dưới.</p>
            </div>
        </div>
    <?php elseif ($is_locked && $lock_reason === 'posted'): ?>
        <div class="aif-status-banner banner-info">
            <div class="banner-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="banner-content">
                <span class="banner-title">Bài viết đã được đăng thành công</span>
                <p class="banner-desc">Trạng thái này không cho phép chỉnh sửa nội dung để bảo toàn dữ liệu đồng bộ với Fanpage.</p>
                <div class="banner-actions">
                    <?php
                    $results = $db->get_results($post_id);
                    foreach ($results as $res):
                        $platform_icon = ($res->platform === 'facebook') ? 'dashicons-facebook' : 'dashicons-admin-site';
                    ?>
                        <a href="<?php echo esc_url($res->link); ?>" target="_blank" class="banner-link">
                            <span class="dashicons <?php echo $platform_icon; ?>"></span>
                            <?php
                            if ($res->platform === 'facebook' && !empty($res->page_name)) {
                                echo 'Xem trên ' . esc_html($res->page_name);
                            } elseif ($res->platform === 'website' && !empty($res->target_id) && $res->target_id !== '0') {
                                $pt_obj_banner = get_post_type_object($res->target_id);
                                $pt_banner_name = $pt_obj_banner ? $pt_obj_banner->labels->singular_name : $res->target_id;
                                echo 'Xem trên Website — ' . esc_html($pt_banner_name);
                            } else {
                                echo 'Xem trên ' . esc_html(ucfirst($res->platform));
                            }
                            ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($failed_queue_items)): ?>
        <div class="aif-status-banner banner-error">
            <div class="banner-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="banner-content">
                <span class="banner-title">⚠️ Đăng bài thất bại — <?php echo count($failed_queue_items); ?> nền tảng bị lỗi</span>
                <p class="banner-desc">Bài viết này đã gặp lỗi khi đăng lên một số nền tảng. Kiểm tra lỗi bên dưới và thử đăng lại.</p>
                <ul class="aif-failed-list">
                    <?php foreach ($failed_queue_items as $fitem):
                        // Parse error reason
                        $fstatus = $fitem->status;
                        if (strpos($fstatus, 'failed: ') === 0) {
                            $freason = trim(substr($fstatus, 8));
                        } elseif ($fstatus === 'failed_no_token') {
                            $freason = 'Token không hợp lệ hoặc đã hết hạn — vào Kết nối Fanpage để cập nhật';
                        } elseif ($fstatus === 'failed_no_post') {
                            $freason = 'Không tìm thấy bài viết trong database';
                        } else {
                            $freason = $fstatus;
                        }
                        $fplat = $fitem->platform ?? 'facebook';
                        $fplat_class = ($fplat === 'website') ? 'plat-web' : 'plat-fb';
                        $fplat_label = ($fplat === 'website') ? '🌐 Website' : '📘 Facebook';
                        $ftarget = $fitem->page_name ?: ($fplat === 'website' ? 'Website' : 'Fanpage');
                    ?>
                        <li class="aif-failed-item">
                            <div class="aif-failed-item-left">
                                <span class="aif-failed-platform <?php echo $fplat_class; ?>"><?php echo $fplat_label; ?></span>
                                <div>
                                    <div class="aif-failed-page"><?php echo esc_html($ftarget); ?></div>
                                    <div class="aif-failed-reason" title="<?php echo esc_attr($freason); ?>">
                                        <?php echo esc_html(mb_strimwidth($freason, 0, 80, '…')); ?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="aif-btn-retry-inline btn-retry-queue"
                                data-id="<?php echo intval($fitem->id); ?>">
                                <span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span>
                                Thử lại
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" id="aif-post-form">
        <?php wp_nonce_field('aif_save_post'); ?>
        <fieldset <?php echo $is_locked ? 'disabled' : ''; ?>>
            <div class="aif-grid">
                <!-- COL 1: Input Setup -->
                <div class="aif-col-left">
                    <div class="aif-card">
                        <div class="aif-card-header">
                            <h2><span class="dashicons dashicons-admin-settings"></span> Cài đặt đầu vào</h2>
                        </div>
                        <div class="aif-card-body">
                            <div class="aif-form-group">
                                <label>Đề tài (Topic)</label>
                                <input type="text" name="aif_industry" id="aif-industry" class="aif-input"
                                    value="<?php echo esc_attr($post ? $post->industry : ''); ?>"
                                    placeholder="Ví dụ: Bất động sản">
                            </div>

                            <div class="aif-form-group">
                                <label>Người phụ trách</label>
                                <?php
                                $display_owner = $post ? ($post->owner ?: (get_userdata($post->wp_author_id)->display_name ?? '')) : wp_get_current_user()->display_name;
                                ?>
                                <input type="text" name="aif_owner" class="aif-input"
                                    value="<?php echo esc_attr($display_owner); ?>">
                            </div>

                            <div class="aif-form-group">
                                <label>Post Type (WordPress)</label>
                                <?php
                                $all_post_types = get_post_types(['public' => true], 'objects');
                                unset($all_post_types['page'], $all_post_types['attachment']);
                                // Support multi post types (JSON array) or legacy single string
                                $saved_post_types = [];
                                if ($post && !empty($post->post_type)) {
                                    $decoded = json_decode($post->post_type, true);
                                    if (is_array($decoded)) {
                                        $saved_post_types = $decoded;
                                    } else {
                                        $saved_post_types = [$post->post_type];
                                    }
                                } else {
                                    $saved_post_types = ['post'];
                                }
                                ?>
                                <div id="aif-post-type-list" style="max-height: 180px; overflow-y: auto; border: 1px solid var(--aif-border-light); border-radius: 8px; padding: 12px; background: var(--aif-bg-subtle);">
                                    <?php foreach ($all_post_types as $pt):
                                        $checked = in_array($pt->name, $saved_post_types) ? 'checked' : '';
                                    ?>
                                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; cursor:pointer; font-size:13px;">
                                            <input type="checkbox" name="aif_post_type[]" value="<?php echo esc_attr($pt->name); ?>" class="aif-post-type-checkbox" <?php echo $checked; ?>>
                                            <?php echo esc_html($pt->labels->singular_name); ?> <code style="font-size:11px; color:#94a3b8;">(<?php echo esc_html($pt->name); ?>)</code>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="aif-form-group">
                                <label>Danh mục</label>
                                <div id="aif-category-list"
                                    style="max-height: 250px; overflow-y: auto; border: 1px solid var(--aif-border-light); border-radius: 8px; padding: 12px; background: var(--aif-bg-subtle);">
                                    <?php
                                    $saved_cats = ($post && !empty($post->slug_category)) ? (json_decode($post->slug_category, true) ?: [intval($post->slug_category)]) : [];
                                    $has_any_cat = false;
                                    foreach ($saved_post_types as $spt) {
                                        $spt_obj = get_post_type_object($spt);
                                        $spt_label = $spt_obj ? $spt_obj->labels->singular_name : $spt;
                                        $taxonomies = get_object_taxonomies($spt, 'objects');
                                        $tax_name = '';
                                        foreach ($taxonomies as $tax) {
                                            if ($tax->hierarchical) {
                                                $tax_name = $tax->name;
                                                break;
                                            }
                                        }
                                        if ($tax_name) {
                                            $terms = get_terms(['taxonomy' => $tax_name, 'hide_empty' => false]);
                                            if (!is_wp_error($terms) && !empty($terms)) {
                                                $has_any_cat = true;
                                                echo '<div class="aif-cat-group" style="margin-bottom:10px;">';
                                                echo '<div style="font-size:11px; font-weight:700; color:#6366f1; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; padding-bottom:4px; border-bottom:1px dashed #e2e8f0;">';
                                                echo esc_html($spt_label) . ' <code style="font-size:10px;color:#94a3b8;">(' . esc_html($spt) . ')</code>';
                                                echo '</div>';
                                                foreach ($terms as $term) {
                                                    $checked = in_array($term->term_id, $saved_cats) ? 'checked' : '';
                                                    echo '<label style="display:flex; align-items:center; gap:8px; margin-bottom:6px; cursor:pointer; font-size:13px; padding-left:4px;">';
                                                    echo '<input type="checkbox" name="aif_wp_category[]" value="' . esc_attr($term->term_id) . '" ' . $checked . '> ' . esc_html($term->name);
                                                    echo '</label>';
                                                }
                                                echo '</div>';
                                            }
                                        }
                                    }
                                    if (!$has_any_cat) {
                                        echo '<em>Không có danh mục cho các Post Type đã chọn.</em>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="aif-form-group">
                                <label>Nền tảng chính</label>
                                <select name="aif_option" id="aif-platform" class="aif-select">
                                    <option value="Facebook" <?php selected($current_option, 'Facebook'); ?>>Facebook
                                    </option>
                                    <option value="LinkedIn" <?php selected($current_option, 'LinkedIn'); ?>>LinkedIn
                                    </option>
                                    <option value="Website" <?php selected($current_option, 'Website'); ?>>Website
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COL 2: AI & Content Editor -->
                <div class="aif-col-center">
                    <div class="aif-card">
                        <div class="aif-card-header">
                            <h2><span class="dashicons dashicons-superhero" style="color: #581c87;"></span> AI Generator
                                & Editor</h2>
                        </div>
                        <div class="aif-card-body">
                            <div class="aif-ai-panel" id="aif-ai-generation-container">
                                <div class="aif-ai-loading" id="aif-ai-loader">
                                    <span class="spinner"></span>
                                    <span class="aif-loading-text">AI đang suy nghĩ...</span>
                                </div>

                                <!-- Tone Selector -->
                                <div style="margin-bottom:12px;">
                                    <label style="display:block;font-size:12px;font-weight:700;color:#64748b;margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px;">
                                        <span class="dashicons dashicons-art" style="font-size:13px;vertical-align:middle;margin-right:3px;"></span> Phong cách viết
                                    </label>
                                    <div class="aif-tone-grid" id="aif-tone-grid">
                                        <?php
                                        $tones = AIF_AI_Generator::get_all_tones();
                                        $current_tone = $post ? ($post->tone ?? '') : '';
                                        foreach ($tones as $key => $info):
                                            $is_custom = !empty($info['custom']);
                                        ?>
                                            <button type="button"
                                                class="aif-tone-btn <?php echo ($current_tone === $key) ? 'active' : ''; ?> <?php echo $is_custom ? 'aif-tone-custom' : ''; ?>"
                                                data-tone="<?php echo esc_attr($key); ?>"
                                                data-desc="<?php echo esc_attr($info['description'] ?? $info['desc'] ?? ''); ?>"
                                                data-style="<?php echo esc_attr($info['style'] ?? ''); ?>"
                                                data-custom="<?php echo $is_custom ? '1' : '0'; ?>"
                                                data-id="<?php echo esc_attr($info['id'] ?? ''); ?>"
                                                data-label="<?php echo esc_attr($info['label']); ?>">
                                                <?php echo esc_html($info['label']); ?>
                                                <?php if ($is_custom): ?><span class="aif-tone-custom-del" data-key="<?php echo esc_attr($key); ?>" data-id="<?php echo esc_attr($info['id'] ?? ''); ?>" title="Xóa phong cách này">×</span><?php endif; ?>
                                            </button>
                                        <?php endforeach; ?>
                                        <!-- Button thêm mới -->
                                        <button type="button" class="aif-tone-btn aif-tone-add-btn" id="aif-tone-add-btn" title="Thêm phong cách viết mới">
                                            <span class="dashicons dashicons-plus-alt2" style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:3px;"></span> Thêm mới
                                        </button>
                                    </div>
                                    <input type="hidden" name="aif_tone" id="aif-tone-input" value="<?php echo esc_attr($current_tone); ?>">
                                </div>

                                <!-- Tooltip phong cách viết (dùng chung) -->
                                <div id="aif-tone-tooltip" style="display:none;position:fixed;z-index:99999;max-width:260px;background:#1e293b;color:#f1f5f9;padding:12px 14px;border-radius:12px;font-size:12px;line-height:1.6;pointer-events:none;box-shadow:0 8px 24px rgba(0,0,0,0.3);">
                                    <div id="aif-tone-tooltip-label" style="font-weight:700;font-size:13px;margin-bottom:6px;color:#fff;"></div>
                                    <div id="aif-tone-tooltip-desc" style="color:#94a3b8;margin-bottom:0;"></div>
                                    <div id="aif-tone-tooltip-style-wrap" style="display:none;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.1);">
                                        <div style="font-size:10px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:4px;">🤖 Hướng dẫn AI</div>
                                        <div id="aif-tone-tooltip-style" style="color:#cbd5e1;font-size:11.5px;line-height:1.6;"></div>
                                    </div>
                                    <div style="position:absolute;bottom:-6px;left:50%;transform:translateX(-50%);width:12px;height:12px;background:#1e293b;clip-path:polygon(0 0,100% 0,50% 100%);"></div>
                                </div>

                                <!-- Modal thêm phong cách viết mới -->
                                <div id="aif-tone-modal" style="display:none;position:fixed;inset:0;z-index:999999;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
                                    <div style="background:#fff;border-radius:16px;width:100%;max-width:440px;margin:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                                        <div style="padding:18px 22px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;gap:12px;position:relative;">
                                            <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <span class="dashicons dashicons-art" style="color:#fff;font-size:18px;width:18px;height:18px;"></span>
                                            </div>
                                            <div>
                                                <h3 style="margin:0;font-size:15px;font-weight:800;color:#fff;">Thêm phong cách viết</h3>
                                                <p style="margin:2px 0 0;font-size:11px;color:rgba(255,255,255,0.8);">Tùy chỉnh phong cách AI theo nhu cầu của bạn</p>
                                            </div>
                                            <button type="button" id="aif-tone-modal-close" style="position:absolute;right:14px;top:14px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:7px;cursor:pointer;font-size:18px;line-height:28px;text-align:center;">&times;</button>
                                        </div>
                                        <div style="padding:22px;">
                                            <div style="margin-bottom:16px;">
                                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Tên phong cách <span style="color:#ef4444;">*</span></label>
                                                <input type="text" id="tone-modal-label" placeholder="VD: 🌟 Truyền cảm hứng" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;transition:border-color .2s;">
                                                <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">Nên thêm emoji để dễ nhận biết.</p>
                                            </div>
                                            <div style="margin-bottom:16px;">
                                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Mô tả ngắn <span style="color:#94a3b8;font-weight:400;">(hiện khi hover)</span></label>
                                                <input type="text" id="tone-modal-desc" placeholder="VD: Truyền động lực, kể câu chuyện vươn lên..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;transition:border-color .2s;">
                                            </div>
                                            <div style="margin-bottom:8px;">
                                                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">Hướng dẫn viết cho AI <span style="color:#ef4444;">*</span></label>
                                                <textarea id="tone-modal-style" rows="4" placeholder="VD: Giọng văn truyền cảm hứng mạnh mẽ, dùng câu chuyện thực tế, kêu gọi hành động tích cực. Ngôn ngữ tích cực, đầy năng lượng..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;resize:vertical;line-height:1.6;transition:border-color .2s;"></textarea>
                                                <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">AI sẽ đọc hướng dẫn này để viết đúng phong cách của bạn.</p>
                                            </div>
                                        </div>
                                        <div style="padding:14px 22px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
                                            <button type="button" id="aif-tone-modal-cancel" class="aif-btn aif-btn-outline">Hủy</button>
                                            <button type="button" id="aif-tone-modal-save" class="aif-btn aif-btn-primary">
                                                <span class="dashicons dashicons-saved" style="font-size:14px;width:14px;height:14px;"></span> Lưu phong cách
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="aif-form-group" style="margin-bottom: 12px;">
                                    <label>Yêu cầu cho AI (Prompt/Description)</label>
                                    <textarea name="aif_description" id="aif-description" class="aif-textarea" rows="4"
                                        placeholder="Ví dụ: Viết một bài giới thiệu căn hộ 3 phòng ngủ đầy đủ tiện nghi, phong cách hiện đại..."
                                        style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom: none;"><?php echo esc_textarea($post ? $post->description : ''); ?></textarea>
                                    <div
                                        style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid var(--aif-border-light); border-top: 1px dashed #e2e8f0; padding: 10px 14px; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                        <div style="flex: 1; display: flex; align-items: center;">
                                            <div class="aif-suggestion-chips" style="margin-top: 0;">
                                                <span class="aif-chip ai-suggestion-chip" style="margin-bottom: 0;">Ngắn gọn</span>
                                                <span class="aif-chip ai-suggestion-chip" style="margin-bottom: 0;">Hài hước</span>
                                                <span class="aif-chip ai-suggestion-chip" style="margin-bottom: 0;">Chuyên nghiệp</span>
                                            </div>
                                        </div>
                                        <div style="display:flex;gap:8px;align-items:center;">
                                            <button type="button" id="btn-generate-variations" class="aif-btn aif-btn-outline"
                                                style="padding:8px 13px;font-size:12px;white-space:nowrap;"
                                                title="Tạo 3 phiên bản để chọn">
                                                <span class="dashicons dashicons-images-alt2" style="font-size:14px;width:14px;height:14px;margin-right:3px;vertical-align:middle;"></span>
                                                3 phiên bản
                                            </button>
                                            <button type="button" id="btn-generate-v2" class="aif-btn aif-btn-primary"
                                                style="padding: 8px 16px; min-width: 100px; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);">
                                                <span class="dashicons dashicons-marker"
                                                    style="font-size: 16px; width: 16px; height: 16px; margin-right: 5px;"></span>
                                                Generate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Smart Check Bar (hiển thị sau khi có content) -->
                            <div id="aif-smart-check-bar" style="display:none; margin-bottom:14px; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div id="aif-check-grade-badge" style="width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:12px;font-weight:700;color:#374151;" id="aif-check-label">Chất lượng nội dung</div>
                                            <div id="aif-check-score-bar" style="width:160px;height:5px;background:#e2e8f0;border-radius:3px;margin-top:3px;overflow:hidden;">
                                                <div id="aif-check-score-fill" style="height:100%;border-radius:3px;transition:width 0.5s;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="btn-smart-check" class="aif-btn aif-btn-outline" style="font-size:12px;padding:6px 12px;">
                                        <span class="dashicons dashicons-search" style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:3px;"></span>
                                        Kiểm tra ngay
                                    </button>
                                </div>
                                <div id="aif-check-issues" style="margin-top:8px;display:none;"></div>
                            </div>

                            <!-- 3 Variations Panel -->
                            <div id="aif-variations-panel" style="display:none; margin-bottom:14px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                    <span style="font-size:13px;font-weight:700;color:#1e293b;">🎯 Chọn 1 trong 3 phiên bản AI:</span>
                                    <button type="button" id="btn-close-variations" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;">&times;</button>
                                </div>
                                <div id="aif-variations-list" style="display:flex;flex-direction:column;gap:10px;"></div>
                            </div>

                            <div class="aif-form-group">
                                <label>Tiêu đề bài viết</label>
                                <input type="text" name="post_title" id="aif-title" class="aif-input"
                                    value="<?php echo esc_attr($current_title); ?>"
                                    style="font-size: 16px; font-weight: 700;">
                            </div>

                            <div class="aif-form-group">
                                <label>Nội dung chi tiết</label>
                                <textarea name="post_content" id="aif-caption" class="aif-textarea" rows="18"
                                    style="font-family: 'Inter', sans-serif; line-height: 1.6;"><?php echo esc_textarea($current_content); ?></textarea>
                            </div>

                            <!-- Revision Box -->
                            <div
                                style="background: #f8fafc; border: 1px solid var(--aif-border-light); border-radius: 12px; padding: 15px; margin-top: 20px;">
                                <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 8px;">
                                    <span class="dashicons dashicons-update"
                                        style="font-size: 14px; margin-right: 5px;"></span> Tối ưu nội dung:
                                </label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" id="aif-feedback" name="aif_feedback" class="aif-input"
                                        value="<?php echo esc_attr($post ? $post->feedback : ''); ?>"
                                        placeholder="Góp ý sửa đổi (ví dụ: thêm emoji, viết ngắn lại...)">
                                    <button type="button" id="btn-revise-content" class="aif-btn aif-btn-outline"
                                        style="white-space: nowrap;">
                                        ✏️ Sửa ngay
                                        <span id="aif-revise-spinner" class="spinner"
                                            style="float:none; margin:0 0 0 5px;"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COL 3: Media & Publishing -->
                <div class="aif-col-right">
                    <div class="aif-card">
                        <div class="aif-card-header">
                            <h2><span class="dashicons dashicons-images-alt2"></span> Media & Nền tảng</h2>
                        </div>
                        <div class="aif-card-body">
                            <div class="aif-media-section-title">
                                <span class="dashicons dashicons-facebook" style="font-size: 16px;"></span> Facebook Gallery (Tối đa 10 ảnh)
                            </div>
                            <div id="aif-preview-container-fb" class="aif-media-preview-grid">
                                <!-- Populated by JS -->
                            </div>
                            <input type="hidden" name="aif_images_order" id="aif-images-order-input" value='<?php echo esc_attr($post ? $post->images : "[]"); ?>'>

                            <div class="aif-media-section-title" style="margin-top: 25px;">
                                <span class="dashicons dashicons-admin-site" style="font-size: 16px;"></span> Website Thumbnail (Chọn 1)
                            </div>
                            <div id="aif-preview-container-web" class="aif-media-preview-grid" style="grid-template-columns: repeat(1, 120px);">
                                <!-- Populated by JS -->
                            </div>

                            <input type="hidden" name="aif_image_website" id="aif-image-website-input" value="<?php echo esc_attr($post ? $post->image_website : ''); ?>">

                            <button type="button" class="aif-btn aif-btn-outline" id="aif-open-media-modal"
                                style="width: 100%; margin-top: 20px; justify-content: center;">
                                <span class="dashicons dashicons-cloud-upload"></span> Quản lý ảnh & video
                            </button>

                            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--aif-border-light);">

                            <div class="aif-form-group">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                    <label style="margin-bottom:0;">Lên lịch đăng bài</label>
                                    <button type="button" id="btn-suggest-time" class="aif-btn-ai-mini" title="AI gợi ý thời giờ tối ưu" style="padding:4px 10px; font-size:11px; font-weight:700; background:#f0f7ff; color:#3b82f6; border:1px solid #bfdbfe; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:4px;">
                                        <span class="dashicons dashicons-calendar-alt" style="font-size:13px; width:13px; height:13px;"></span>
                                        ✨ AI Gợi ý
                                    </button>
                                </div>
                                <input type="datetime-local" name="aif_schedule" id="aif-schedule-input"
                                    value="<?php echo $current_schedule; ?>" class="aif-input">
                                <div id="aif-time-suggestions" style="margin-top:8px; display:none; flex-wrap:wrap; gap:6px;"></div>
                            </div>

                            <div class="aif-form-group">
                                <label>Nơi đăng bài</label>
                                <div
                                    style="border: 1px solid var(--aif-border-light); border-radius: 12px; padding: 15px; background: var(--aif-bg-subtle);">
                                    <?php
                                    $fb_manager = new AIF_Facebook_Manager();
                                    $connected_pages = $fb_manager->get_pages();
                                    $saved_targets = ($post && !empty($post->targets)) ? json_decode($post->targets, true) : [];
                                    $checked_ids = [];
                                    $has_website = false;
                                    if (is_array($saved_targets))
                                        foreach ($saved_targets as $t) {
                                            if (isset($t['id']))
                                                $checked_ids[] = $t['id'];
                                            if (isset($t['platform']) && $t['platform'] === 'website')
                                                $has_website = true;
                                        }

                                    echo '<label style="display:flex; align-items:center; gap:8px; margin-bottom:12px; font-weight:700; color:var(--aif-primary);">';
                                    echo '<input type="checkbox" name="aif_target_website" value="1" ' . ($has_website ? 'checked' : '') . '> Website';
                                    echo '</label>';

                                    if ($connected_pages) {
                                        foreach ($connected_pages as $page) {
                                            $is_checked = in_array($page->id, $checked_ids) ? 'checked' : '';
                                            echo '<label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:13px;">';
                                            echo '<input type="checkbox" name="aif_target_pages[]" value="' . esc_attr($page->id) . '" ' . $is_checked . '> ' . esc_html($page->page_name);
                                            echo '</label>';
                                        }
                                    } else {
                                        echo '<div style="font-size: 13px; color: #64748b; margin-top: 5px;">';
                                        echo 'Chưa có Fanpage kết nối. <a href="' . admin_url('admin.php?page=ai-fanpage-settings') . '" style="color: var(--aif-primary); font-weight: 600; text-decoration: none;">Kết nối ngay &rarr;</a>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Post Results -->
                    <?php
                    $results = $db->get_results($post_id);
                    if (!empty($results)): ?>
                        <div class="aif-card">
                            <div class="aif-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <h2><span class="dashicons dashicons-chart-bar"></span> Tương tác</h2>
                                <button type="button" id="btn-refresh-metrics" class="aif-btn-icon" title="Cập nhật chỉ số">
                                    <span class="dashicons dashicons-update"></span>
                                </button>
                            </div>
                            <div class="aif-card-body" style="padding: 0;">
                                <?php foreach ($results as $res): ?>
                                    <div class="aif-metric-row" data-result-id="<?php echo $res->id; ?>" style="padding: 15px; border-bottom: 1px solid var(--aif-border-light);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                            <?php
                                            $p_class = ($res->platform === 'facebook') ? 'aif-fb-badge' : 'aif-web-badge';
                                            $p_icon = ($res->platform === 'facebook') ? 'dashicons-facebook' : 'dashicons-admin-site';
                                            // Show post type label for website results
                                            $platform_display = ucfirst($res->platform);
                                            if ($res->platform === 'website' && !empty($res->target_id) && $res->target_id !== '0') {
                                                $pt_obj_display = get_post_type_object($res->target_id);
                                                $pt_name = $pt_obj_display ? $pt_obj_display->labels->singular_name : $res->target_id;
                                                $platform_display = 'Website — ' . $pt_name;
                                            }
                                            ?>
                                            <span class="aif-platform-badge <?php echo $p_class; ?>">
                                                <span class="dashicons <?php echo $p_icon; ?>" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
                                                <?php echo esc_html($platform_display); ?>
                                            </span>
                                            <a href="<?php echo esc_url($res->link); ?>" target="_blank"
                                                style="font-size: 11px; text-decoration: none;"><span
                                                    class="dashicons dashicons-external" style="font-size: 14px;"></span>
                                                Link</a>
                                        </div>
                                        <div style="display: flex; gap: 15px;">
                                            <div style="font-size: 13px;">
                                                <strong class="count-likes"><?php echo intval($res->likes_count); ?></strong> <span
                                                    style="color:var(--aif-text-muted)">Likes</span>
                                            </div>
                                            <div style="font-size: 13px;">
                                                <strong class="count-comments"><?php echo intval($res->comments_count); ?></strong> <span
                                                    style="color:var(--aif-text-muted)">Cmt</span>
                                            </div>
                                            <div style="font-size: 13px;">
                                                <strong class="count-shares"><?php echo intval($res->shares_count); ?></strong> <span
                                                    style="color:var(--aif-text-muted)">Share</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </fieldset>

        <!-- Sticky Action Bar -->
        <div class="aif-action-bar">
            <div style="display: flex; gap: 12px;">
                <?php if ($is_locked && $lock_reason === 'queued'): ?>
                    <button type="button" id="btn-remove-from-queue" class="aif-btn aif-btn-outline"
                        style="color: var(--aif-danger); border-color: var(--aif-danger);">
                        <span class="dashicons dashicons-dismiss"></span> Hủy hàng chờ
                    </button>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="admin.php?page=ai-fanpage-posts" class="aif-btn aif-btn-outline">Quay lại</a>
                <button type="submit" class="aif-btn aif-btn-outline" style="border-width: 2px; <?php echo $is_locked ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>" <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-saved"></span> Lưu nháp
                </button>
                <?php if ($current_status !== 'Done' && $current_status !== 'Posted successfully'): ?>
                    <button type="submit" name="aif_status_action" value="done" class="aif-btn aif-btn-primary">
                        Hoàn tất & Đăng
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Confirm Done Modal -->
    <div id="aif-confirm-done-modal" style="display:none; position:fixed; inset:0; z-index:99998; background:rgba(15,23,42,0.55); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:16px; width:100%; max-width:420px; margin:20px; overflow:hidden; box-shadow:0 25px 60px rgba(0,0,0,0.25);">
            <!-- Header -->
            <div style="padding:20px 24px; background:linear-gradient(135deg,#6366f1,#4f46e5); position:relative;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px; height:40px; background:rgba(255,255,255,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <span class="dashicons dashicons-cloud-upload" style="color:#fff; font-size:20px; width:20px; height:20px;"></span>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:15px; font-weight:800; color:#fff;">Xác nhận hoàn tất & đăng bài</h3>
                        <p style="margin:3px 0 0; font-size:12px; color:rgba(255,255,255,0.8);">Bài viết sẽ được chuyển sang hàng chờ đăng</p>
                    </div>
                </div>
                <button type="button" id="aif-confirm-done-close" style="position:absolute; top:14px; right:16px; background:rgba(255,255,255,0.2); border:none; color:#fff; width:28px; height:28px; border-radius:6px; cursor:pointer; font-size:16px; line-height:28px; text-align:center;">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding:20px 24px;">
                <div id="aif-confirm-done-summary" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; font-size:13px; color:#374151; line-height:1.7;"></div>
                <p style="font-size:12px; color:#64748b; margin:12px 0 0;">Sau khi xác nhận, bạn sẽ không thể chỉnh sửa nội dung cho đến khi hủy hàng chờ.</p>
            </div>
            <!-- Footer -->
            <div style="padding:14px 24px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; background:#fafafa;">
                <button type="button" id="aif-confirm-done-cancel" class="aif-btn aif-btn-outline">Hủy</button>
                <button type="button" id="aif-confirm-done-submit" class="aif-btn aif-btn-primary">
                    <span class="dashicons dashicons-cloud-upload" style="font-size:15px; width:15px; height:15px;"></span>
                    Xác nhận đăng
                </button>
            </div>
        </div>
    </div>

    <!-- Media Selection Modal -->
    <div id="aif-media-modal"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:99999; justify-content:center; align-items:center; backdrop-filter:blur(4px);">
        <div class="aif-modal-content"
            style="background:#fff; width:92%; max-width:1100px; max-height:88vh; border-radius:16px; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);">

            <!-- Header -->
            <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:#fafafa; flex-shrink:0;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <h3 style="margin:0; font-size:17px; font-weight:700;">Thư viện Media</h3>
                    <button type="button" id="aif-btn-inline-upload"
                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:600;border:1px solid #e2e8f0;border-radius:7px;background:#fff;cursor:pointer;">
                        <span class="dashicons dashicons-upload" style="font-size:14px;width:14px;height:14px;"></span> Tải lên
                    </button>
                    <button type="button" id="aif-btn-wp-media"
                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;font-size:12px;font-weight:600;border:1px solid #7c3aed;border-radius:7px;background:#fff;color:#7c3aed;cursor:pointer;">
                        <span class="dashicons dashicons-images-alt2" style="font-size:14px;width:14px;height:14px;"></span> WP Media
                    </button>
                    <input type="file" id="aif-inline-upload-input" style="display:none;" accept="image/*,video/*" multiple>
                    <span id="aif-inline-upload-spinner" class="spinner" style="float:none;margin:0;"></span>
                </div>
                <button type="button" id="aif-close-modal-x"
                    style="background:none;border:none;font-size:24px;cursor:pointer;color:#94a3b8;line-height:1;">&times;</button>
            </div>

            <!-- Body: sidebar + grid -->
            <div style="display:flex; flex:1; overflow:hidden;">

                <!-- Sidebar chuyên mục -->
                <div id="aif-modal-sidebar"
                    style="width:190px;flex-shrink:0;border-right:1px solid #f1f5f9;background:#fafafa;overflow-y:auto;padding:10px 8px;">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:#94a3b8;padding:4px 8px 8px;">Chuyên mục</div>
                    <div style="text-align:center;padding:20px;color:#94a3b8;font-size:12px;">Đang tải...</div>
                </div>

                <!-- Grid ảnh -->
                <div style="flex:1;overflow-y:auto;padding:16px;">
                    <div style="margin-bottom:12px;">
                        <input type="text" id="aif-modal-search"
                            placeholder="🔍 Tìm tên file..."
                            style="width:100%;padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;">
                    </div>
                    <div class="aif-image-grid"
                        style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px,1fr)); gap:14px;">
                        <div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">
                            <div class="spinner is-active" style="float:none;margin:0 auto 10px;"></div>
                            Đang tải ảnh...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding:14px 20px; border-top:1px solid #f1f5f9; text-align:right; background:#fafafa; flex-shrink:0;">
                <span id="aif-modal-selected-count" style="font-size:12px;color:#64748b;margin-right:12px;"></span>
                <button type="button" class="aif-btn aif-btn-primary" id="aif-close-media-modal">
                    Xác nhận lựa chọn
                </button>
            </div>
        </div>
    </div>

    <!-- Lightbox Overlay -->
    <div id="aif-lightbox"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.98); z-index:999999; justify-content:center; align-items:center; backdrop-filter:blur(10px);">
        <span id="aif-lightbox-close"
            style="position:absolute; top:25px; right:35px; color:#fff; font-size:44px; cursor:pointer;">&times;</span>
        <div id="aif-lightbox-media-container"
            style="max-width:90%; max-height:90vh; display:flex; justify-content:center; align-items:center;">
        </div>
    </div>
</div>