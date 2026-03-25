<?php
$db = new AIF_DB();
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';

// Status Management Logic (Simplified for V2 strict flow - mainly for Delete)
if (isset($_GET['action']) && isset($_GET['id']) && check_admin_referer('aif_status_change')) {
    $action_post_id = intval($_GET['id']);

    if ($_GET['action'] === 'trash') {
        $p = $db->get($action_post_id);
        $fb_mgr_trash = new AIF_Facebook_Manager();
        if ($p && ($p->status === 'Posted successfully' || $fb_mgr_trash->is_post_queued($action_post_id))) {
            $_SESSION['aif_message'] = 'Không thể xóa bài viết đã đăng hoặc đang trong hàng chờ.';
        } else {
            $db->delete($action_post_id);
        }
        echo "<script>window.location.href='admin.php?page=ai-fanpage-posts';</script>";
        exit;
    }
}

// Fetch Posts
$limit = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $limit;

$posts = $db->get_posts([
    'status' => $status_filter,
    'limit' => $limit,
    'offset' => $offset
]);

$counts = $db->get_counts();
?>

<div class="wrap aif-container">
    <?php if (isset($_SESSION['aif_message'])): ?>
        <div class="notice notice-info is-dismissible">
            <p><?php echo esc_html($_SESSION['aif_message']); ?></p>
        </div>
        <?php unset($_SESSION['aif_message']); ?>
    <?php endif; ?>
    <div class="aif-header-card"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 25px 30px; border-radius: 16px; border: 1px solid var(--aif-border-light); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);">
        <div>
            <h1 class="wp-heading-inline"
                style="margin: 0; font-size: 26px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-admin-page"
                    style="font-size: 24px; width: 24px; height: 24px; color: var(--aif-primary);"></span>
                Quản lý Bài viết
            </h1>
            <p style="color: #64748b; margin: 8px 0 0; font-size: 14px;">Quy trình tự động hóa nội dung đa kênh với sức
                mạnh AI.</p>
        </div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <button type="button" id="btn-open-tones" title="Phong cách viết"
                style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;border:1.5px solid #c7d2fe;border-radius:12px;background:#fff;font-size:14px;font-weight:700;color:#4f46e5;cursor:pointer;transition:all .15s;white-space:nowrap;box-shadow:0 4px 12px rgba(79,70,229,0.08);">
                <span class="dashicons dashicons-art" style="font-size:16px;width:16px;height:16px;"></span>
                Phong cách viết
            </button>
            <a href="<?php echo admin_url('admin.php?page=ai-fanpage-post-detail&action=new'); ?>"
                class="aif-btn-premium" style="text-decoration: none;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span>Viết bài mới</span>
            </a>
        </div>
    </div>

    <div class="aif-filter-wrapper">
        <div class="bulkactions" style="display: flex; gap: 10px; align-items: center;">
            <div class="aif-bulk-wrapper">
                <select name="action" id="bulk-action-selector-top" class="aif-bulk-select">
                    <option value="-1">Hành động hàng loạt</option>
                    <option value="generate"><?php echo AIF_Status::label('To do'); ?> → Generate (AI soạn)</option>
                    <option value="publish"><?php echo AIF_Status::label('Done'); ?> → Publish (Đăng bài)</option>
                </select>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <button type="button" id="aif-doaction" class="aif-btn-bulk-apply">
                <span class="dashicons dashicons-yes"></span>
                Thực hiện
            </button>
        </div>

        <ul class="aif-pills">
            <li>
                <a href="admin.php?page=ai-fanpage-posts&status=all"
                    class="<?php echo $status_filter == 'all' ? 'current' : ''; ?>">
                    Tất cả <span class="count"><?php echo $counts->all; ?></span>
                </a>
            </li>
            <li>
                <a href="admin.php?page=ai-fanpage-posts&status=To do"
                    class="<?php echo $status_filter == 'To do' ? 'current' : ''; ?>">
                    <?php echo AIF_Status::label('To do'); ?>
                    <span class="count"><?php echo isset($counts->{'To do'}) ? $counts->{'To do'} : 0; ?></span>
                </a>
            </li>
            <li>
                <a href="admin.php?page=ai-fanpage-posts&status=Content updated"
                    class="<?php echo $status_filter == 'Content updated' ? 'current' : ''; ?>">
                    <?php echo AIF_Status::label('Content updated'); ?>
                    <span
                        class="count"><?php echo isset($counts->{'Content updated'}) ? $counts->{'Content updated'} : 0; ?></span>
                </a>
            </li>
            <li>
                <a href="admin.php?page=ai-fanpage-posts&status=Done"
                    class="<?php echo $status_filter == 'Done' ? 'current' : ''; ?>">
                    <?php echo AIF_Status::label('Done'); ?>
                    <span class="count"><?php echo isset($counts->{'Done'}) ? $counts->{'Done'} : 0; ?></span>
                </a>
            </li>
            <li>
                <a href="admin.php?page=ai-fanpage-posts&status=Posted successfully"
                    class="<?php echo $status_filter == 'Posted successfully' ? 'current' : ''; ?>">
                    <?php echo AIF_Status::label('Posted successfully'); ?>
                    <span
                        class="count"><?php echo isset($counts->{'Posted successfully'}) ? $counts->{'Posted successfully'} : 0; ?></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Bulk Action Progress Container -->
    <div id="bulk-action-progress" style="display:none;">
        <div
            style="background: #fff; border: 1px solid var(--aif-border-light); border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-update"
                        style="color: var(--aif-primary); font-size: 20px; width: 20px; height: 20px;"></span>
                    <h3 id="bulk-progress-header"
                        style="margin: 0; font-size: 14px; font-weight: 700; color: var(--aif-text-main);">Đang xử lý...
                    </h3>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="bulk-progress-count"
                        style="font-size: 13px; font-weight: 700; color: var(--aif-primary); background: rgba(59,130,246,0.1); padding: 3px 10px; border-radius: 20px;">0/0</span>
                    <button type="button" id="bulk-progress-dismiss"
                        onclick="jQuery('#bulk-action-progress').slideUp(200);"
                        style="display:none; background: none; border: 1px solid var(--aif-border-light); border-radius: 6px; padding: 4px 10px; font-size: 11px; cursor: pointer; color: var(--aif-text-muted);">Đóng</button>
                </div>
            </div>
            <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                <div id="bulk-progress-bar"
                    style="width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #6366f1); transition: width 0.4s ease; border-radius: 3px;">
                </div>
            </div>
            <div id="bulk-log"
                style="max-height: 120px; overflow-y: auto; margin-top: 12px; font-size: 12px; color: var(--aif-text-muted);">
            </div>
        </div>
    </div>

    <div class="aif-table-responsive"
        style="background: #fff; border-radius: 12px; border: 1px solid var(--aif-border-light); overflow-x: auto;">
        <table class="wp-list-table widefat fixed striped table-view-list posts aif-premium-table"
            style="min-width: 1300px; border: none !important; box-shadow: none !important;">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="aif-text-center" style="width: 40px; color: var(--aif-text-muted);">STT</th>
                    <th scope="col" class="aif-text-center" style="width: 80px;">Media</th>
                    <th scope="col" style="width: 130px;">Đề tài / Topic</th>
                    <th scope="col" style="width: 200px;">Tiêu đề & Hành động</th>
                    <th scope="col" style="width: 180px;">Yêu cầu (Description)</th>
                    <th scope="col" style="width: 130px;">Nội dung</th>
                    <th scope="col" style="width: 140px;">Góp ý AI</th>
                    <th scope="col" class="aif-text-center" style="width: 80px;">Platform</th>
                    <th scope="col" class="aif-text-center" style="width: 160px;">Trạng thái / Lịch</th>
                    <th scope="col" class="aif-text-center" style="width: 120px;">Ghi chú</th>
                    <th scope="col" class="aif-text-center" style="width: 140px;">Người phụ trách</th>
                    <th scope="col" style="text-align: right; width: 90px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $fb_manager = new AIF_Facebook_Manager();

                // Lấy tất cả failed queue items của các bài trong trang này — 1 query, không N+1
                $post_ids_on_page = array_column($posts, 'id');
                $failed_map = []; // post_id => [ {id, status, platform, page_name}, ... ]
                if (!empty($post_ids_on_page)) {
                    global $wpdb;
                    $placeholders = implode(',', array_fill(0, count($post_ids_on_page), '%d'));
                    $failed_rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT q.id, q.post_id, q.status, q.platform, f.page_name
                         FROM {$wpdb->prefix}aif_posting_queue q
                         LEFT JOIN {$wpdb->prefix}aif_facebook_pages f ON q.page_id = f.id
                         WHERE q.post_id IN ($placeholders) AND q.status LIKE 'failed%'",
                        ...$post_ids_on_page
                    ));
                    foreach ($failed_rows as $fr) {
                        $failed_map[$fr->post_id][] = $fr;
                    }
                }

                if (!empty($posts)): ?>
                    <?php foreach ($posts as $post):
                        $post_id = $post->id;
                        $is_queued = $fb_manager->is_post_queued($post_id);
                        $edit_link = admin_url('admin.php?page=ai-fanpage-post-detail&id=' . $post_id);
                        $trash_link = wp_nonce_url(admin_url("admin.php?page=ai-fanpage-posts&action=trash&id=$post_id"), 'aif_status_change');

                        // Status styling
                        $status_label = AIF_Status::label($post->status);
                        $status_class = AIF_Status::badge_class($post->status);
                    ?>
                        <tr id="post-<?php echo $post_id; ?>"
                            class="<?php echo ($post->status === 'Posted successfully' || $is_queued) ? 'locked' : ''; ?>"
                            data-status="<?php echo esc_attr($post->status); ?>" data-stt="<?php echo $offset + 1; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post[]" value="<?php echo $post_id; ?>">
                            </th>
                            <td class="aif-text-center"><span
                                    style="color: var(--aif-text-muted); font-size: 13px; font-weight: 500;"><?php echo ++$offset; ?></span>
                            </td>

                            <td class="aif-text-center">
                                <?php
                                $img_url = '';
                                $img_list = json_decode($post->images ?? '[]', true);
                                
                                // Try images gallery first
                                if (!empty($img_list) && is_array($img_list)) {
                                    $first_img = $img_list[0];
                                } elseif (!empty($post->image_website)) {
                                    // Fallback to website image
                                    $first_img = $post->image_website;
                                } else {
                                    $first_img = '';
                                }

                                if (!empty($first_img)) {
                                    if (strpos($first_img, 'http') === 0) {
                                        $img_url = $first_img;
                                    } elseif (strpos($first_img, 'wp-att-') === 0) {
                                        $att_id  = intval(substr($first_img, 7));
                                        $img_url = wp_get_attachment_image_url($att_id, 'thumbnail') ?: wp_get_attachment_url($att_id);
                                    } else {
                                        // Standard upload - strip folder prefix
                                        $basename = basename(str_replace('\\', '/', $first_img));
                                        $img_url  = AIF_URL . 'upload/' . $basename;
                                    }
                                }

                                if ($img_url):
                                    echo '<a href="' . esc_url($edit_link) . '"><img src="' . esc_url($img_url) . '" class="aif-post-thumbnail" loading="lazy" style="cursor:pointer;"></a>';
                                else:
                                    echo '<div class="aif-no-thumbnail" onclick="location.href=\'' . esc_url($edit_link) . '\'" style="cursor:pointer;"><span class="dashicons dashicons-images-alt2"></span></div>';
                                endif;
                                ?>
                            </td>

                            <td class="editable-cell" data-field="industry">
                                <div class="view-mode">
                                    <div style="font-weight: 600; color: var(--aif-primary);">
                                        <?php echo !empty($post->industry) ? esc_html($post->industry) : 'Trống'; ?>
                                    </div>
                                    <?php
                                    $cat_ids = json_decode($post->slug_category, true);
                                    if (!empty($cat_ids) && is_array($cat_ids)) {
                                        $cat_names = [];
                                        foreach ($cat_ids as $cat_id) {
                                            $term = is_numeric($cat_id) ? get_term(intval($cat_id)) : null;
                                            $cat_names[] = ($term && !is_wp_error($term)) ? $term->name : $cat_id;
                                        }
                                        echo '<div style="font-size: 10px; color: var(--aif-text-muted); margin-top:2px;">' . esc_html(implode(', ', $cat_names)) . '</div>';
                                    } ?>
                                </div>
                                <div class="edit-mode" style="display:none;">
                                    <input type="text" class="inline-edit-input"
                                        value="<?php echo esc_attr($post->industry); ?>">
                                </div>
                            </td>

                            <td class="editable-cell" data-field="title">
                                <div class="view-mode">
                                    <div style="font-weight: 500; font-size: 13px;">
                                        <?php echo esc_html($post->title ?: 'Trống'); ?>
                                    </div>
                                </div>
                                <div class="edit-mode" style="display:none;">
                                    <textarea class="inline-edit-input"
                                        rows="2"><?php echo esc_textarea($post->title); ?></textarea>
                                </div>
                            </td>

                            <td class="column-primary editable-cell" data-field="description">
                                <div class="view-mode">
                                    <div style="font-size: 12px; color: #000000ff; line-height: 1.5;">
                                        <?php echo !empty($post->description) ? wp_trim_words($post->description, 12) : 'Trống'; ?>
                                    </div>
                                </div>
                                <div class="edit-mode" style="display:none;">
                                    <textarea class="inline-edit-input"
                                        rows="3"><?php echo esc_textarea($post->description); ?></textarea>
                                </div>
                            </td>

                            <td class="content-cell" data-field="content">
                                <div class="view-mode">
                                    <div style="font-size: 11px; color: #6b7280; max-height: 48px; overflow: hidden;">
                                        <?php echo wp_trim_words($post->content, 12); ?>
                                    </div>
                                    <button type="button" class="button button-small btn-edit-content-modal"
                                        style="margin-top: 5px; height: 24px; line-height: 22px; font-size: 10px;">🔍 Soạn
                                        thảo</button>
                                </div>
                                <textarea class="full-content-store"
                                    style="display:none;"><?php echo esc_textarea($post->content); ?></textarea>
                            </td>

                            <td class="feedback-cell">
                                <div class="feedback-container">
                                    <textarea class="feedback-input auto-expand" placeholder="Yêu cầu chỉnh sửa..."
                                        rows="1"></textarea>
                                    <button type="button" class="btn-apply-feedback">
                                        <span class="dashicons dashicons-superhero"></span>
                                        Apply AI
                                    </button>
                                </div>
                            </td>

                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                    <span class="aif-platform-badge aif-fb-badge">
                                        <?php echo esc_html($post->option_platform); ?>
                                    </span>
                                    <div style="display: flex; gap: 4px; margin-top: 4px;">
                                        <?php
                                        $results = $db->get_results($post_id);
                                        foreach ($results as $res):
                                            $icon = ($res->platform === 'facebook') ? 'dashicons-facebook icon-fb' : 'dashicons-admin-site icon-web';
                                            $link = $res->link;
                                            if ($res->platform === 'facebook' && strpos($link, 'http') === false && strpos($link, '_') !== false) {
                                                $parts = explode('_', $link);
                                                $link = "https://www.facebook.com/{$parts[0]}/posts/{$parts[1]}";
                                            }
                                        ?>
                                            <a href="<?php echo esc_url($link); ?>" target="_blank" class="platform-icon">
                                                <span class="dashicons <?php echo $icon; ?>"
                                                    style="font-size: 18px; width: 18px; height: 18px;"></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </td>

                            <td class="column-status aif-text-center">
                                <div class="editable-cell" data-field="status">
                                    <div class="view-mode">
                                        <span
                                            class="aif-status-pill <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span>
                                    </div>
                                    <div class="edit-mode" style="display:none;">
                                        <div class="edit-mode-select-wrapper">
                                            <select class="inline-edit-input">
                                                <option value="To do" <?php selected($post->status, 'To do'); ?>>
                                                    <?php echo AIF_Status::label('To do'); ?>
                                                </option>
                                                <option value="Content updated" <?php selected($post->status, 'Content updated'); ?>><?php echo AIF_Status::label('Content updated'); ?></option>
                                                <option value="Done" <?php selected($post->status, 'Done'); ?>>
                                                    <?php echo AIF_Status::label('Done'); ?>
                                                </option>
                                            </select>
                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // --- FAILED INDICATOR ---
                                if (!empty($failed_map[$post_id])):
                                    $fail_count = count($failed_map[$post_id]);
                                    // Build tooltip text
                                    $tip_lines = [];
                                    foreach ($failed_map[$post_id] as $fi) {
                                        $fs = $fi->status;
                                        if (strpos($fs, 'failed: ') === 0) {
                                            $freason = trim(substr($fs, 8));
                                        } elseif ($fs === 'failed_no_token') {
                                            $freason = 'Token hết hạn / không hợp lệ';
                                        } elseif ($fs === 'failed_no_post') {
                                            $freason = 'Không tìm thấy bài trong DB';
                                        } else {
                                            $freason = $fs;
                                        }
                                        $plat_label = ($fi->platform === 'website') ? '🌐' : '📘';
                                        $target = $fi->page_name ?: ($fi->platform === 'website' ? 'Website' : 'Fanpage');
                                        $tip_lines[] = $plat_label . ' ' . $target . ': ' . mb_strimwidth($freason, 0, 60, '…');
                                    }
                                    $tip_text = implode("\n", $tip_lines);
                                ?>
                                    <div style="margin-top:5px;">
                                        <span class="aif-failed-dot" title="">
                                            <span class="dashicons dashicons-warning"></span>
                                            Lỗi đăng (<?php echo $fail_count; ?>)
                                            <span class="aif-failed-tip"><?php echo esc_html($tip_text); ?></span>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="editable-cell" data-field="time_posting" style="margin-top: 6px;">
                                    <div class="view-mode"
                                        style="font-size: 10px; color: var(--aif-text-muted); display: flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-clock"
                                            style="font-size: 12px; width: 12px; height: 12px;"></span>
                                        <?php if ($post->time_posting && $post->time_posting != '0000-00-00 00:00:00'): ?>
                                            <span><?php echo date('d/m/y H:i', strtotime($post->time_posting)); ?></span>
                                        <?php else: ?>
                                            <span style="font-style: italic; opacity: 0.6;">Chưa hẹn</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="edit-mode" style="display:none;">
                                        <input type="datetime-local" class="inline-edit-input"
                                            value="<?php echo ($post->time_posting && $post->time_posting != '0000-00-00 00:00:00') ? date('Y-m-d\TH:i', strtotime($post->time_posting)) : ''; ?>">
                                    </div>
                                </div>
                            </td>

                            <td class="editable-cell aif-text-center" data-field="note">
                                <div class="view-mode"
                                    style="font-size: 11px; color: var(--aif-text-muted); max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0 auto;">
                                    <?php echo $post->note ? esc_html($post->note) : '<span style="opacity:0.4;">...</span>'; ?>
                                </div>
                                <div class="edit-mode" style="display:none;">
                                    <textarea class="inline-edit-input" rows="2"
                                        style="font-size: 11px;"><?php echo esc_textarea($post->note); ?></textarea>
                                </div>
                            </td>

                            <td class="editable-cell aif-text-center" data-field="owner">
                                <div class="view-mode"
                                    style="font-size: 11px; display: flex; align-items: center; gap: 4px; justify-content: center;">
                                    <span class="dashicons dashicons-admin-users"
                                        style="font-size: 14px; width: 14px; height: 14px; color: #cbd5e1;"></span>
                                    <?php
                                    $display_owner = '';
                                    if (!empty($post->owner)) {
                                        $display_owner = $post->owner;
                                    } elseif (!empty($post->wp_author_id)) {
                                        $author_data = get_userdata($post->wp_author_id);
                                        $display_owner = $author_data ? $author_data->display_name : 'Trống';
                                    } else {
                                        $display_owner = 'Trống';
                                    }
                                    echo esc_html($display_owner);
                                    ?>
                                </div>
                                <div class="edit-mode" style="display:none;">
                                    <input type="text" class="inline-edit-input" value="<?php echo esc_attr($post->owner); ?>"
                                        style="font-size: 11px;">
                                </div>
                            </td>

                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo $edit_link; ?>"
                                        class="button button-small <?php echo !empty($failed_map[$post_id]) ? 'aif-action-warn' : ''; ?>"
                                        style="border-radius: 6px; <?php echo empty($failed_map[$post_id]) ? 'border-color: var(--aif-border-light); color: var(--aif-primary);' : ''; ?>"
                                        title="<?php echo !empty($failed_map[$post_id]) ? 'Có lỗi đăng bài — nhấn để xem chi tiết' : (($post->status === 'Posted successfully' || $is_queued) ? 'Xem Chi tiết' : 'Sửa Chi tiết'); ?>">
                                        <span class="dashicons <?php echo !empty($failed_map[$post_id]) ? 'dashicons-warning' : (($post->status === 'Posted successfully' || $is_queued) ? 'dashicons-visibility' : 'dashicons-edit'); ?>" style="font-size: 14px; margin-top:3px;"></span>
                                    </a>
                                    <a href="<?php echo ($post->status === 'Posted successfully' || $is_queued) ? '#' : $trash_link; ?>"
                                        class="button button-small"
                                        style="border-radius: 6px; border-color: <?php echo ($post->status === 'Posted successfully' || $is_queued) ? '#eee' : '#fee2e2'; ?>; color: <?php echo ($post->status === 'Posted successfully' || $is_queued) ? '#ccc' : '#ef4444'; ?>; <?php echo ($post->status === 'Posted successfully' || $is_queued) ? 'pointer-events: none; opacity: 0.5;' : ''; ?>"
                                        title="<?php echo ($post->status === 'Posted successfully' || $is_queued) ? 'Bài viết đã đăng hoặc đang chờ không thể xóa' : 'Thùng rác'; ?>"
                                        onclick="<?php echo ($post->status === 'Posted successfully' || $is_queued) ? 'return false;' : "return confirm('Xóa bài này?');"; ?>">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; margin-top:3px;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 40px;">
                            <p style="font-size: 16px; margin: 0;">Chưa có dữ liệu.</p>
                            <p style="color: #666;">Hãy Sync từ Google Sheet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    $total_posts = $counts->{$status_filter === 'all' ? 'all' : $status_filter} ?: 0;
    $total_pages = ceil($total_posts / $limit);

    if ($total_pages > 1): ?>
        <div class="aif-pagination">
            <a href="<?php echo add_query_arg('paged', max(1, $paged - 1)); ?>"
                class="aif-page-link <?php echo ($paged <= 1) ? 'disabled' : ''; ?>">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>

            <?php
            $range = 2;
            for ($i = 1; $i <= $total_pages; $i++):
                if ($i == 1 || $i == $total_pages || ($i >= $paged - $range && $i <= $paged + $range)): ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>"
                        class="aif-page-link <?php echo ($i == $paged) ? 'current' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif ($i == $paged - $range - 1 || $i == $paged + $range + 1): ?>
                    <span style="color: var(--aif-text-muted);">...</span>
            <?php endif;
            endfor; ?>

            <a href="<?php echo add_query_arg('paged', min($total_pages, $paged + 1)); ?>"
                class="aif-page-link <?php echo ($paged >= $total_pages) ? 'disabled' : ''; ?>">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Content Edit Modal -->
    <div id="aif-content-modal" class="aif-modal" style="display:none;">
        <div class="aif-modal-content">
            <div class="aif-modal-header">
                <h3>Chỉnh sửa nội dung</h3>
                <span class="close-aif-modal">&times;</span>
            </div>
            <div class="aif-modal-body">
                <textarea id="aif-modal-textarea" style="width:100%; height: 350px; font-family: monospace;"></textarea>
            </div>
            <div class="aif-modal-footer">
                <button type="button" class="button button-secondary close-aif-modal">Hủy</button>
                <button type="button" id="btn-save-modal-content" class="button button-primary">Lưu nội dung</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: Phong cách viết ===== -->
<div id="aif-tones-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.55);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:780px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,0.25);margin:20px;overflow:hidden;">

        <!-- Header -->
        <div style="padding:20px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);position:relative;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <span class="dashicons dashicons-art" style="color:#fff;font-size:20px;width:20px;height:20px;"></span>
                </div>
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#fff;">Phong cách viết</h3>
                    <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,0.8);">Định nghĩa giọng văn AI dùng khi soạn bài</p>
                </div>
            </div>
            <button type="button" id="btn-close-tones-modal" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
        </div>

        <!-- Toolbar -->
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fafafa;">
            <span style="font-size:12px;color:#64748b;" id="tones-count-label">Đang tải...</span>
            <button type="button" id="btn-tone-add" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                <span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;"></span>
                Thêm phong cách
            </button>
        </div>

        <!-- List -->
        <div id="tones-list" style="flex:1;overflow-y:auto;padding:0;">
            <div style="text-align:center;padding:50px;color:#94a3b8;">
                <div class="spinner is-active" style="float:none;margin:0 auto 12px;"></div>Đang tải...
            </div>
        </div>

        <!-- Form thêm/sửa -->
        <div id="tones-form-area" style="display:none;padding:20px 24px;border-top:2px solid #e0e7ff;background:#f8f7ff;flex-shrink:0;">
            <input type="hidden" id="tone-edit-id" value="">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Tên phong cách <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="tone-label" placeholder="VD: 😊 Thân thiện" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Mô tả ngắn</label>
                    <input type="text" id="tone-description" placeholder="VD: Gần gũi, vui vẻ, dễ tiếp cận" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;">
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:5px;">Hướng dẫn cho AI <span style="color:#ef4444;">*</span></label>
                <textarea id="tone-style" rows="3" placeholder="VD: Viết theo phong cách thân thiện, dùng ngôn ngữ tự nhiên, thêm emoji vừa phải..." style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;outline:none;resize:vertical;font-family:inherit;line-height:1.5;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button type="button" id="btn-tone-cancel" style="padding:8px 18px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;">Hủy</button>
                <button type="button" id="btn-tone-save" style="padding:8px 18px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                    <span class="dashicons dashicons-saved" style="font-size:14px;width:14px;height:14px;"></span>Lưu
                </button>
            </div>
        </div>
    </div>
</div>