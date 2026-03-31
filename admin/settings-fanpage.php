<?php
if (!defined('ABSPATH'))
    exit;
$manager = new AIF_Facebook_Manager();
$pages = $manager->get_pages();
// Tính số bài đã đăng thành công cho mỗi page
$posted_counts = [];
if ($pages) {
    foreach ($pages as $page) {
        $posted_counts[$page->id] = $manager->get_posted_count($page->id);
    }
}
?>
<style>
    :root {
        --aif-primary: #3b82f6;
        --aif-primary-hover: #2563eb;
        --aif-success: #10b981;
        --aif-warning: #f59e0b;
        --aif-danger: #ef4444;
        --aif-bg-subtle: #f8fafc;
        --aif-border-light: #e2e8f0;
        --aif-text-main: #1e293b;
        --aif-text-muted: #64748b;
    }

    .aif-premium-settings {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--aif-text-main);
        padding: 20px;
        max-width: 1200px;
    }

    .aif-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .aif-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .aif-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
    }

    .aif-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--aif-border-light);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 25px;
    }

    .aif-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--aif-border-light);
        background: var(--aif-bg-subtle);
    }

    .aif-card-header h2 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .aif-card-body {
        padding: 24px;
    }

    /* Modern Form Controls */
    .aif-form-group {
        margin-bottom: 20px;
    }

    .aif-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--aif-text-main);
        margin-bottom: 8px;
    }

    .aif-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--aif-border-light);
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .aif-input:focus {
        border-color: var(--aif-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .aif-help-text {
        font-size: 12px;
        color: var(--aif-text-muted);
        margin-top: 6px;
        line-height: 1.4;
    }

    /* Premium Table */
    .aif-table-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--aif-border-light);
    }

    .aif-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .aif-table th {
        background: var(--aif-bg-subtle);
        padding: 14px 20px;
        font-size: 12px;
        font-weight: 700;
        color: var(--aif-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--aif-border-light);
    }

    .aif-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--aif-border-light);
        font-size: 14px;
        vertical-align: middle;
    }

    .aif-table tr:last-child td {
        border-bottom: none;
    }

    .aif-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .aif-badge-success {
        background: #dcfce7;
        color: #166534;
    }

    .aif-badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .aif-badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .aif-badge-info {
        background: #e0f2fe;
        color: #075985;
    }

    .aif-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        text-decoration: none;
    }

    .aif-btn-primary {
        background: var(--aif-primary);
        color: #fff;
    }

    .aif-btn-primary:hover {
        background: var(--aif-primary-hover);
    }

    .aif-btn-outline {
        background: #fff;
        border-color: var(--aif-border-light);
        color: var(--aif-text-main);
    }

    .aif-btn-outline:hover {
        background: var(--aif-bg-subtle);
        border-color: #cbd5e1;
    }

    .aif-btn-danger {
        color: var(--aif-danger);
    }

    .aif-btn-danger:hover {
        background: #fef2f2;
    }

    @keyframes rotation {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(359deg);
        }
    }
</style>

<div class="aif-premium-settings">
    <div class="aif-header">
        <h1>
            <span class="dashicons dashicons-facebook-alt"
                style="color: #1877F2; font-size: 32px; width: 32px; height: 32px;"></span>
            Kết Nối Fanpage
        </h1>
        <div style="font-size: 13px; color: var(--aif-text-muted);">
            Phiên bản 2.0 • Hoạt động ổn định
        </div>
    </div>

    <div class="aif-grid">
        <!-- Sidebar: Add Form -->
        <div class="aif-sidebar">
            <div class="aif-card">
                <div class="aif-card-header">
                    <h2><span class="dashicons dashicons-plus-alt2"></span> Thêm Fanpage</h2>
                </div>
                <div class="aif-card-body">
                    <form id="aif-fanpage-form">
                        <div class="aif-form-group">
                            <label for="page_name">Tên Fanpage</label>
                            <input type="text" name="page_name" id="page_name" class="aif-input"
                                placeholder="Ví dụ: AI News" required>
                        </div>
                        <div class="aif-form-group">
                            <label for="page_id">Page ID</label>
                            <input type="text" name="page_id" id="page_id" class="aif-input"
                                placeholder="Ví dụ: 10293..." required>
                            <p class="aif-help-text">Tìm ID trong phần Giới thiệu của Fanpage.</p>
                        </div>
                        <div class="aif-form-group">
                            <label for="access_token">Page Access Token</label>
                            <input type="password" name="access_token" id="access_token" class="aif-input"
                                placeholder="Nhập mã token..." required>
                            <p class="aif-help-text">Sử dụng Token ngắn hạn từ Facebook Graph API.</p>
                        </div>
                        <div class="aif-form-group">
                            <label for="app_id">App ID</label>
                            <input type="text" name="app_id" id="app_id" class="aif-input"
                                placeholder="App ID của bạn..." required>
                        </div>
                        <div class="aif-form-group">
                            <label for="app_secret">App Secret</label>
                            <input type="password" name="app_secret" id="app_secret" class="aif-input"
                                placeholder="App Secret của bạn..." required>
                            <p class="aif-help-text">Dùng để gia hạn Token lên 60 ngày tự động.</p>
                        </div>

                        <button type="submit" class="aif-btn aif-btn-primary" id="btn-save-fanpage"
                            style="width: 100%; justify-content: center; margin-top: 10px;">
                            <span class="dashicons dashicons-saved"></span> Kết Nối Ngay
                        </button>
                    </form>
                    <div id="fanpage-message" style="margin-top: 15px;"></div>
                </div>
            </div>

            <div class="aif-card" style="background: var(--aif-bg-subtle); border-style: dashed;">
                <div class="aif-card-body" style="font-size: 13px; color: var(--aif-text-muted);">
                    <p style="margin-top: 0;"><span class="dashicons dashicons-info"
                            style="font-size: 16px; width: 16px; height: 16px; color: var(--aif-primary);"></span>
                        <strong>Lưu ý:</strong>
                    </p>
                    <p>Đảm bảo Fanpage của bạn đã được cấp quyền `pages_manage_posts` và `pages_read_engagement` trong
                        App Facebook.</p>
                </div>
            </div>
        </div>

        <!-- Main: Lists -->
        <div class="aif-main">
            <div class="aif-card">
                <div class="aif-card-header"
                    style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><span class="dashicons dashicons-list-view"></span> Fanpage Đã Kết Nối</h2>
                    <span class="aif-badge aif-badge-info"><?php echo count($pages ?: []); ?> Trang</span>
                </div>
                <div class="aif-card-body" style="padding: 0;">
                    <table class="aif-table">
                        <thead>
                            <tr>
                                <th style="width: 40%;">Fanpage</th>
                                <th style="width: 20%; text-align:center;">Bài đã đăng</th>
                                <th style="width: 20%;">Thời Hạn</th>
                                <th style="width: 20%; text-align: right;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($pages) {
                                foreach ($pages as $page) {
                                    $expiry_text = '<span class="aif-badge aif-badge-info">Vô thời hạn</span>';
                                    if (!empty($page->expires_at)) {
                                        $expires = strtotime($page->expires_at);
                                        $now = time();
                                        $diff = $expires - $now;
                                        $days = floor($diff / (60 * 60 * 24));

                                        if ($days <= 0) {
                                            $expiry_text = '<span class="aif-badge aif-badge-danger">Hết hạn</span>';
                                        } elseif ($days < 7) {
                                            $expiry_text = '<span class="aif-badge aif-badge-warning">Còn ' . $days . ' ngày</span>';
                                        } else {
                                            $expiry_text = '<span class="aif-badge aif-badge-success">Còn ' . $days . ' ngày</span>';
                                        }
                                    }

                                    $has_app    = !empty($page->app_id);
                                    $app_badge  = $has_app
                                        ? '<span class="aif-badge aif-badge-success" style="padding:2px 6px;font-size:9px;margin-top:5px;">APP OK</span>'
                                        : '<span class="aif-badge aif-badge-danger"  style="padding:2px 6px;font-size:9px;margin-top:5px;">NO APP</span>';

                                    $posted = $posted_counts[$page->id] ?? 0;
                                    $can_delete = ($posted === 0);

                                    echo '<tr>';

                                    // Cột Fanpage
                                    echo '<td>
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                <div style="width:40px;height:40px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:var(--aif-primary);">
                                                    <span class="dashicons dashicons-facebook" style="font-size:20px;"></span>
                                                </div>
                                                <div>
                                                    <div style="font-weight:700;color:var(--aif-text-main);">' . esc_html($page->page_name) . '</div>
                                                    <div style="font-size:11px;color:var(--aif-text-muted);margin-top:2px;">ID: ' . esc_html($page->page_id) . '</div>
                                                    ' . $app_badge . '
                                                </div>
                                            </div>
                                          </td>';

                                    // Cột bài đã đăng
                                    echo '<td style="text-align:center;">
                                            ' . ($posted > 0
                                        ? '<span class="aif-badge aif-badge-success">' . $posted . ' bài</span>'
                                        : '<span class="aif-badge" style="background:#f1f5f9;color:#94a3b8;">Chưa có</span>') . '
                                          </td>';

                                    // Cột thời hạn
                                    echo '<td>' . $expiry_text . '</td>';

                                    // Cột hành động
                                    echo '<td style="text-align:right;">
                                            <div style="display:flex;justify-content:flex-end;gap:8px;">
                                                <button class="aif-btn aif-btn-outline btn-edit-fanpage"
                                                        data-id="'       . $page->id . '"
                                                        data-name="'     . esc_attr($page->page_name) . '"
                                                        data-pageid="'   . esc_attr($page->page_id) . '"
                                                        data-appid="'    . esc_attr($page->app_id ?? '') . '"
                                                        data-has-app="'  . ($has_app ? '1' : '0') . '"
                                                        title="Chỉnh sửa">
                                                    <span class="dashicons dashicons-edit" style="font-size:16px;"></span>
                                                </button>
                                                <button class="aif-btn aif-btn-outline btn-update-token"
                                                        data-id="'      . $page->id . '"
                                                        data-name="'    . esc_attr($page->page_name) . '"
                                                        data-has-app="' . ($has_app ? '1' : '0') . '"
                                                        title="Cập nhật Token">
                                                    <span class="dashicons dashicons-update" style="font-size:16px;"></span>
                                                </button>
                                                <button class="aif-btn aif-btn-outline aif-btn-danger btn-delete-fanpage"
                                                        data-id="'       . $page->id . '"
                                                        data-name="'     . esc_attr($page->page_name) . '"
                                                        data-posted="'   . $posted . '"
                                                        data-can-delete="' . ($can_delete ? '1' : '0') . '"
                                                        title="' . ($can_delete ? 'Xóa kết nối' : 'Không thể xóa: đã có bài đăng') . '"
                                                        ' . (!$can_delete ? 'style="opacity:0.4;cursor:not-allowed;"' : '') . '>
                                                    <span class="dashicons dashicons-trash" style="font-size:16px;"></span>
                                                </button>
                                            </div>
                                          </td>';

                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--aif-text-muted);">
                                        <span class="dashicons dashicons-database" style="font-size:32px;width:32px;height:32px;margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;"></span>
                                        Chưa có Fanpage nào được kết nối.
                                      </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: Edit Fanpage ===== -->
<div id="aif-edit-fanpage-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);margin:20px;overflow:hidden;">
        <div style="padding:20px 24px;background:linear-gradient(135deg,#3b82f6,#6366f1);position:relative;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <span class="dashicons dashicons-edit" style="color:#fff;font-size:18px;width:18px;height:18px;"></span>
                </div>
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#fff;">Chỉnh sửa Fanpage</h3>
                    <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,0.75);" id="edit-modal-subtitle">Cập nhật tên hiển thị và App ID</p>
                </div>
            </div>
            <button type="button" id="edit-fanpage-modal-close" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
        </div>
        <div style="padding:24px;">
            <input type="hidden" id="edit-fanpage-id">
            <div style="margin-bottom:16px;padding:10px 14px;background:#f0f9ff;border-radius:8px;border:1px solid #bae6fd;display:flex;align-items:center;gap:8px;">
                <span class="dashicons dashicons-lock" style="color:#0284c7;font-size:15px;width:15px;height:15px;flex-shrink:0;"></span>
                <span style="font-size:12px;color:#0369a1;">Page ID: <b id="edit-fanpage-page-id-display"></b> — không thể thay đổi</span>
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">Tên Fanpage <span style="color:#ef4444;">*</span></label>
                <input type="text" id="edit-fanpage-name" class="aif-input" placeholder="Tên hiển thị của Fanpage">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">App ID</label>
                <input type="text" id="edit-fanpage-appid" class="aif-input" placeholder="App ID Facebook">
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                    App Secret <span style="font-weight:400;color:#94a3b8;font-size:11px;">(để trống nếu không đổi)</span>
                </label>
                <div style="position:relative;">
                    <input type="password" id="edit-fanpage-appsecret" class="aif-input" placeholder="••••••••••••••••" style="padding-right:44px;" autocomplete="new-password">
                    <button type="button" id="edit-appsecret-toggle" title="Hiện/ẩn"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                        <span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;"></span>
                    </button>
                </div>
                <p class="aif-help-text">App Secret được mã hóa AES-256 trước khi lưu.</p>
            </div>
        </div>
        <div style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
            <button type="button" id="edit-fanpage-cancel" class="aif-btn aif-btn-outline">Hủy</button>
            <button type="button" id="edit-fanpage-save" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-saved" style="font-size:15px;width:15px;height:15px;"></span> Lưu thay đổi
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Update Token ===== -->
<div id="aif-token-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.2);margin:20px;overflow:hidden;">
        <!-- Header -->
        <div style="padding:20px 24px;background:linear-gradient(135deg,#f59e0b,#d97706);position:relative;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <span class="dashicons dashicons-update" style="color:#fff;font-size:18px;width:18px;height:18px;"></span>
                </div>
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:800;color:#fff;">Cập nhật Access Token</h3>
                    <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,0.85);" id="token-modal-subtitle">Gia hạn token cho fanpage</p>
                </div>
            </div>
            <button type="button" id="token-modal-close" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
        </div>
        <!-- Body -->
        <div style="padding:24px;">
            <input type="hidden" id="token-fanpage-id">

            <!-- Hướng dẫn -->
            <div style="padding:12px 14px;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;margin-bottom:20px;font-size:12px;color:#92400e;line-height:1.6;">
                <div style="font-weight:700;margin-bottom:4px;">📋 Cách lấy token mới:</div>
                <ol style="margin:0;padding-left:16px;">
                    <li>Truy cập <b>Facebook Graph API Explorer</b></li>
                    <li>Chọn App → Generate Token → Copy Page Access Token</li>
                    <li>Dán vào ô bên dưới (token ngắn hạn, hệ thống tự đổi dài hạn)</li>
                </ol>
            </div>

            <!-- Token input -->
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                    Page Access Token mới <span style="color:#ef4444;">*</span>
                </label>
                <div style="position:relative;">
                    <input type="password" id="token-new-value" class="aif-input" placeholder="Dán token vào đây..." style="padding-right:44px;">
                    <button type="button" id="token-toggle-visibility" title="Hiện/ẩn token"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                        <span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;"></span>
                    </button>
                </div>
                <p class="aif-help-text">Token ngắn hạn ~1h. Hệ thống sẽ tự đổi sang token 60 ngày nếu có App.</p>
            </div>

            <!-- App credentials (chỉ hiện khi chưa có App) -->
            <div id="token-app-section" style="display:none;">
                <div style="padding:10px 14px;background:#fef2f2;border-radius:8px;border:1px solid #fecaca;margin-bottom:16px;font-size:12px;color:#991b1b;display:flex;align-items:center;gap:8px;">
                    <span class="dashicons dashicons-warning" style="font-size:15px;width:15px;height:15px;flex-shrink:0;"></span>
                    Fanpage này chưa có App credentials. Vui lòng điền để đổi sang token dài hạn.
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">App ID</label>
                        <input type="text" id="token-app-id" class="aif-input" placeholder="App ID Facebook">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">App Secret</label>
                        <div style="position:relative;">
                            <input type="password" id="token-app-secret" class="aif-input" placeholder="App Secret" style="padding-right:44px;">
                            <button type="button" id="appsecret-toggle-visibility" title="Hiện/ẩn secret"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;">
                                <span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer -->
        <div style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
            <button type="button" id="token-modal-cancel" class="aif-btn aif-btn-outline">Hủy</button>
            <button type="button" id="token-modal-save" class="aif-btn aif-btn-primary" style="background:linear-gradient(135deg,#f59e0b,#d97706);border-color:transparent;">
                <span class="dashicons dashicons-update" style="font-size:15px;width:15px;height:15px;"></span> Cập nhật Token
            </button>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {

        // ══════════════════════════════════════════════════════
        // HELPER: toggle password visibility
        // ══════════════════════════════════════════════════════
        function bindToggle(btnId, inputId) {
            $('#' + btnId).on('click', function() {
                var $input = $('#' + inputId);
                var $icon  = $(this).find('.dashicons');
                var isPass = $input.attr('type') === 'password';
                $input.attr('type', isPass ? 'text' : 'password');
                $icon.toggleClass('dashicons-visibility', !isPass)
                     .toggleClass('dashicons-hidden',     isPass);
            });
        }
        bindToggle('token-toggle-visibility',    'token-new-value');
        bindToggle('appsecret-toggle-visibility','token-app-secret');
        bindToggle('edit-appsecret-toggle',      'edit-fanpage-appsecret');

        // ══════════════════════════════════════════════════════
        // MODAL: EDIT FANPAGE
        // ══════════════════════════════════════════════════════
        function openEditModal(btn) {
            var $b = $(btn);
            $('#edit-fanpage-id').val($b.data('id'));
            $('#edit-fanpage-name').val($b.data('name'));
            $('#edit-fanpage-appid').val($b.data('appid') || '');
            $('#edit-fanpage-appsecret').val('').attr('type','password');
            $('#edit-fanpage-page-id-display').text($b.data('pageid'));
            $('#edit-appsecret-toggle .dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            $('#edit-modal-subtitle').text('Đang sửa: ' + $b.data('name'));
            $('#aif-edit-fanpage-modal').css('display', 'flex');
            setTimeout(function() { $('#edit-fanpage-name').focus().select(); }, 100);
        }
        function closeEditModal() { $('#aif-edit-fanpage-modal').css('display', 'none'); }

        $(document).on('click', '.btn-edit-fanpage', function(e) { e.preventDefault(); openEditModal(this); });
        $('#edit-fanpage-modal-close, #edit-fanpage-cancel').on('click', closeEditModal);
        $('#aif-edit-fanpage-modal').on('click', function(e) { if (e.target === this) closeEditModal(); });

        $('#edit-fanpage-save').on('click', function() {
            var id        = $('#edit-fanpage-id').val();
            var name      = $('#edit-fanpage-name').val().trim();
            var appId     = $('#edit-fanpage-appid').val().trim();
            var appSecret = $('#edit-fanpage-appsecret').val().trim();

            if (!name) { AIF_Toast && AIF_Toast.show('Vui lòng nhập tên Fanpage.', 'error'); $('#edit-fanpage-name').focus(); return; }

            var $btn = $(this).prop('disabled', true);
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-update" style="font-size:15px;width:15px;height:15px;display:inline-block;animation:rotation 1s linear infinite;"></span> Đang lưu...');

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                timeout: 8000,
                data: { action: 'aif_edit_fanpage', nonce: aif_ajax.nonce, id: id, page_name: name, app_id: appId, app_secret: appSecret },
                success: function(res) {
                    $btn.prop('disabled', false).html(orig);
                    if (res.success) {
                        AIF_Toast && AIF_Toast.show('Đã cập nhật!', 'success');
                        // Cập nhật DOM trực tiếp — không reload cả trang
                        var $row = $('.btn-edit-fanpage[data-id="' + id + '"]').closest('tr');
                        $row.find('td:first .font-weight-700, td:first div[style*="font-weight:700"]').text(name);
                        $row.find('.btn-edit-fanpage[data-id="' + id + '"]')
                            .data('name', name)
                            .data('appid', appId || $row.find('.btn-edit-fanpage').data('appid'));
                        if (appId) {
                            $row.find('.aif-badge-danger').replaceWith('<span class="aif-badge aif-badge-success" style="padding:2px 6px;font-size:9px;margin-top:5px;">APP OK</span>');
                        }
                        closeEditModal();
                    } else {
                        AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error');
                    }
                },
                error: function(xhr, status) {
                    $btn.prop('disabled', false).html(orig);
                    var msg = status === 'timeout' ? 'Timeout — server phản hồi quá chậm.' : 'Lỗi kết nối server.';
                    AIF_Toast && AIF_Toast.show(msg, 'error');
                }
            });
        });

        // ══════════════════════════════════════════════════════
        // MODAL: UPDATE TOKEN
        // ══════════════════════════════════════════════════════
        function openTokenModal(btn) {
            var $b    = $(btn);
            var hasApp = $b.data('has-app') == '1';
            $('#token-fanpage-id').val($b.data('id'));
            $('#token-modal-subtitle').text('Fanpage: ' + $b.data('name'));
            $('#token-new-value').val('').attr('type', 'password');
            $('#token-app-id').val('');
            $('#token-app-secret').val('').attr('type', 'password');
            $('#token-toggle-visibility .dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            $('#appsecret-toggle-visibility .dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
            $('#token-app-section').toggle(!hasApp);
            $('#aif-token-modal').css('display', 'flex');
            setTimeout(function() { $('#token-new-value').focus(); }, 100);
        }
        function closeTokenModal() { $('#aif-token-modal').css('display', 'none'); }

        $(document).on('click', '.btn-update-token', function(e) { e.preventDefault(); openTokenModal(this); });
        $('#token-modal-close, #token-modal-cancel').on('click', closeTokenModal);
        $('#aif-token-modal').on('click', function(e) { if (e.target === this) closeTokenModal(); });

        $('#token-modal-save').on('click', function() {
            var id     = $('#token-fanpage-id').val();
            var token  = $('#token-new-value').val().trim();
            var appId  = $('#token-app-id').val().trim();
            var secret = $('#token-app-secret').val().trim();
            var needApp = $('#token-app-section').is(':visible');

            if (!token) { AIF_Toast && AIF_Toast.show('Vui lòng nhập Access Token mới.', 'error'); $('#token-new-value').focus(); return; }
            if (needApp && !appId) { AIF_Toast && AIF_Toast.show('Vui lòng nhập App ID.', 'error'); $('#token-app-id').focus(); return; }
            if (needApp && !secret) { AIF_Toast && AIF_Toast.show('Vui lòng nhập App Secret.', 'error'); $('#token-app-secret').focus(); return; }

            var $btn = $(this).prop('disabled', true);
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-update" style="font-size:15px;width:15px;height:15px;display:inline-block;animation:rotation 1s linear infinite;"></span> Đang cập nhật...');

            $.ajax({
                url: aif_ajax.ajax_url, type: 'POST',
                data: { action:'aif_update_fanpage_token', nonce:aif_ajax.nonce, id:id, access_token:token, app_id:appId, app_secret:secret },
                success: function(res) {
                    $btn.prop('disabled', false).html(orig);
                    if (res.success) {
                        AIF_Toast && AIF_Toast.show('Cập nhật Token thành công!', 'success');
                        closeTokenModal();
                        setTimeout(function() { location.reload(); }, 800);
                    } else { AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error'); }
                },
                error: function() {
                    $btn.prop('disabled', false).html(orig);
                    AIF_Toast && AIF_Toast.show('Lỗi kết nối server', 'error');
                }
            });
        });

        // ══════════════════════════════════════════════════════
        // FORM: THÊM FANPAGE MỚI
        // ══════════════════════════════════════════════════════
        $('#aif-fanpage-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $('#btn-save-fanpage');
            $btn.prop('disabled', true).css('opacity', '0.7').html('<span class="dashicons dashicons-update" style="animation:rotation 2s infinite linear;"></span> Đang lưu...');

            $.ajax({
                url: aif_ajax.ajax_url, type: 'POST',
                data: { action:'aif_save_fanpage', nonce:aif_ajax.nonce, data:$(this).serialize() },
                success: function(res) {
                    if (res.success) {
                        AIF_Toast && AIF_Toast.show('Kết nối thành công!', 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        AIF_Toast && AIF_Toast.show(res.data || 'Có lỗi xảy ra', 'error');
                        $btn.prop('disabled', false).css('opacity', '1').html('<span class="dashicons dashicons-saved"></span> Kết Nối Ngay');
                    }
                },
                error: function() {
                    AIF_Toast && AIF_Toast.show('Lỗi kết nối server', 'error');
                    $btn.prop('disabled', false).css('opacity', '1').html('<span class="dashicons dashicons-saved"></span> Kết Nối Ngay');
                }
            });
        });

        // ══════════════════════════════════════════════════════
        // XÓA FANPAGE
        // ══════════════════════════════════════════════════════
        $(document).on('click', '.btn-delete-fanpage', function(e) {
            e.preventDefault();
            var $btn     = $(this);
            var canDelete = $btn.data('can-delete') == '1';
            var posted   = parseInt($btn.data('posted')) || 0;
            var name     = $btn.data('name');
            var id       = $btn.data('id');

            if (!canDelete) {
                AIF_Toast && AIF_Toast.show('Không thể xóa "' + name + '": đã có ' + posted + ' bài đăng thành công.', 'error');
                return;
            }
            if (!confirm('Xóa kết nối Fanpage "' + name + '"?\nFanpage này chưa có bài nào được đăng.')) return;

            var orig = $btn.html();
            $btn.prop('disabled', true).css('opacity', '0.7').html('<span class="dashicons dashicons-update" style="animation:rotation 2s infinite linear;"></span>');

            $.ajax({
                url: aif_ajax.ajax_url, type: 'POST',
                data: { action:'aif_delete_fanpage', nonce:aif_ajax.nonce, id:id },
                success: function(res) {
                    if (res.success) {
                        AIF_Toast && AIF_Toast.show('Đã xóa Fanpage thành công!', 'success');
                        setTimeout(function() { location.reload(); }, 800);
                    } else {
                        AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error');
                        $btn.prop('disabled', false).css('opacity', '1').html(orig);
                    }
                },
                error: function() {
                    AIF_Toast && AIF_Toast.show('Lỗi kết nối server', 'error');
                    $btn.prop('disabled', false).css('opacity', '1').html(orig);
                }
            });
        });

    });
</script>