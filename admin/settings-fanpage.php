<?php
if (!defined('ABSPATH'))
    exit;
$manager = new AIF_Facebook_Manager();
$pages = $manager->get_pages();
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
                                <th style="width: 25%;">Thời Hạn</th>
                                <th style="width: 35%; text-align: right;">Hành Động</th>
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

                                    $has_app = !empty($page->app_id);
                                    $app_badge = $has_app ? '<span class="aif-badge aif-badge-success" style="padding: 2px 6px; font-size: 9px; margin-top: 5px;">APP OK</span>' : '<span class="aif-badge aif-badge-danger" style="padding: 2px 6px; font-size: 9px; margin-top: 5px;">NO APP</span>';

                                    echo '<tr>';
                                    echo '<td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--aif-primary);">
                                                    <span class="dashicons dashicons-facebook" style="font-size: 20px;"></span>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--aif-text-main);">' . esc_html($page->page_name) . '</div>
                                                    <div style="font-size: 11px; color: var(--aif-text-muted); margin-top: 2px;">ID: ' . esc_html($page->page_id) . '</div>
                                                    ' . $app_badge . '
                                                </div>
                                            </div>
                                          </td>';
                                    echo '<td>' . $expiry_text . '</td>';
                                    echo '<td style="text-align: right;">
                                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                <button class="aif-btn aif-btn-outline btn-update-token" 
                                                        data-id="' . $page->id . '" 
                                                        data-name="' . esc_attr($page->page_name) . '" 
                                                        data-has-app="' . ($has_app ? '1' : '0') . '"
                                                        title="Cập nhật Token">
                                                    <span class="dashicons dashicons-update" style="font-size: 16px;"></span>
                                                </button>
                                                <button class="aif-btn aif-btn-outline aif-btn-danger btn-delete-fanpage" 
                                                        data-id="' . $page->id . '" 
                                                        title="Xóa kết nối">
                                                    <span class="dashicons dashicons-trash" style="font-size: 16px;"></span>
                                                </button>
                                            </div>
                                          </td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3" style="text-align: center; padding: 40px; color: var(--aif-text-muted);">
                                        <span class="dashicons dashicons-database" style="font-size: 32px; width: 32px; height: 32px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;"></span>
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

<script>
    jQuery(document).ready(function ($) {
        // Save Fanpage
        $('#aif-fanpage-form').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var $btn = $('#btn-save-fanpage');
            $btn.prop('disabled', true).css('opacity', '0.7').html('<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Đang lưu...');

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aif_save_fanpage',
                    nonce: aif_ajax.nonce,
                    data: formData
                },
                success: function (response) {
                    if (response.success) {
                        if (window.AIF_Toast) AIF_Toast.show('Kết nối thành công!', 'success');
                        else $('#fanpage-message').html('<div class="notice notice-success inline"><p>Kết nối thành công!</p></div>');

                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        if (window.AIF_Toast) AIF_Toast.show(response.data || 'Có lỗi xảy ra', 'error');
                        else $('#fanpage-message').html('<div class="notice notice-error inline"><p>' + (response.data || 'Có lỗi xảy ra') + '</p></div>');
                        $btn.prop('disabled', false).text('Kết Nối Fanpage');
                    }
                },
                error: function () {
                    if (window.AIF_Toast) AIF_Toast.show('Lỗi kết nối server', 'error');
                    else $('#fanpage-message').html('<div class="notice notice-error inline"><p>Lỗi kết nối server</p></div>');
                    $btn.prop('disabled', false).text('Kết Nối Fanpage');
                }
            });
        });

        // Update Token
        $('.btn-update-token').on('click', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            var hasApp = $(this).data('has-app') == '1';

            var newToken = prompt('1. Nhập Page Access Token mới (mã ngắn hạn) cho "' + name + '":');
            if (!newToken) return;

            var appId = '';
            var appSecret = '';

            if (!hasApp) {
                appId = prompt('2. Fanpage này chưa lưu App ID. Nhập App ID:');
                if (!appId) return;

                appSecret = prompt('3. Nhập App Secret:');
                if (!appSecret) return;
            } else {
                // Info for user
                if (!confirm('Hệ thống sẽ sử dụng App ID/Secret đã lưu để đổi Token dài hạn. Tiếp tục?')) return;
            }

            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).css('opacity', '0.7').html('<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span>');

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aif_update_fanpage_token',
                    nonce: aif_ajax.nonce,
                    id: id,
                    access_token: newToken,
                    app_id: appId,
                    app_secret: appSecret
                },
                success: function (res) {
                    if (res.success) {
                        if (window.AIF_Toast) AIF_Toast.show('Cập nhật Token thành công!', 'success');
                        else alert('Cập nhật Token thành công!');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        if (window.AIF_Toast) AIF_Toast.show('Lỗi: ' + res.data, 'error');
                        else alert('Lỗi: ' + res.data);
                        $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                    }
                },
                error: function () {
                    if (window.AIF_Toast) AIF_Toast.show('Lỗi kết nối server', 'error');
                    else alert('Lỗi kết nối server');
                    $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                }
            });
        });

        // Delete Fanpage
        $('.btn-delete-fanpage').on('click', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            if (!confirm('Bạn có chắc chắn muốn xóa kết nối Fanpage này không?')) return;

            var $btn = $(this);
            var originalHtml = $btn.html();
            $btn.prop('disabled', true).css('opacity', '0.7').html('<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span>');

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aif_delete_fanpage',
                    nonce: aif_ajax.nonce,
                    id: id
                },
                success: function (res) {
                    if (res.success) {
                        if (window.AIF_Toast) AIF_Toast.show('Đã xóa Fanpage thành công!', 'success');
                        else alert('Đã xóa Fanpage thành công!');

                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        if (window.AIF_Toast) AIF_Toast.show('Lỗi: ' + res.data, 'error');
                        else alert('Lỗi: ' + res.data);
                        $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                    }
                },
                error: function () {
                    if (window.AIF_Toast) AIF_Toast.show('Lỗi kết nối server', 'error');
                    else alert('Lỗi kết nối server');
                    $btn.prop('disabled', false).css('opacity', '1').html(originalHtml);
                }
            });
        });
    });
</script>