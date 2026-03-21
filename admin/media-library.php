<?php
if (!defined('ABSPATH')) exit;
?>

<!-- =============================== MAIN LAYOUT =============================== -->
<div class="wrap" style="margin-top:0; padding: 12px 16px 0;">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
        <span class="dashicons dashicons-format-gallery" style="font-size:26px;width:26px;height:26px;color:#3b82f6;"></span>
        <h1 style="margin:0; font-size:20px; font-weight:800;">Kho Media</h1>
    </div>

    <div class="ml-wrap">
        <!-- ── Sidebar ── -->
        <div class="ml-sidebar">
            <div class="ml-sidebar-header">
                <p class="ml-sidebar-title">Chuyên mục</p>
                <button class="ml-btn-new-folder" id="ml-btn-new-folder">
                    <span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;"></span>
                    Thêm chuyên mục
                </button>
            </div>
            <div class="ml-folder-list" id="ml-folder-list">
                <div style="padding:20px; text-align:center; color:#94a3b8; font-size:13px;">Đang tải...</div>
            </div>
        </div>

        <!-- ── Main ── -->
        <div class="ml-main">
            <!-- Toolbar -->
            <div class="ml-toolbar">
                <div class="ml-toolbar-left">
                    <div class="ml-current-folder">
                        <span class="dashicons dashicons-portfolio" id="ml-folder-icon"></span>
                        <span id="ml-folder-breadcrumb">Tất cả</span>
                    </div>
                    <input type="text" class="ml-search" id="ml-search" placeholder="🔍 Tìm file...">
                </div>
                <div class="ml-toolbar-right">
                    <span id="ml-file-count" style="font-size:12px;color:#64748b;"></span>
                    <div class="ml-view-toggle">
                        <button class="ml-view-btn active" id="ml-view-grid" title="Lưới">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button class="ml-view-btn" id="ml-view-list" title="Danh sách">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                    <button class="ml-btn ml-btn-primary" id="ml-btn-upload">
                        <span class="dashicons dashicons-upload"></span> Tải lên
                    </button>
                    <input type="file" id="ml-file-input" multiple accept="image/*,video/mp4">
                </div>
            </div>

            <!-- Drop zone -->
            <div class="ml-dropzone" id="ml-dropzone">
                <p><strong>Kéo thả file vào đây</strong> hoặc nhấn <strong>Tải lên</strong> — JPG, PNG, GIF, WEBP, MP4</p>
            </div>

            <!-- Progress -->
            <div class="ml-progress" id="ml-progress">
                <div class="ml-progress-bar" id="ml-progress-bar"></div>
            </div>

            <!-- Grid / List -->
            <div class="ml-content">
                <div class="ml-grid" id="ml-grid"></div>
            </div>
        </div>
    </div>

    <!-- Bulk action bar -->
    <div class="ml-bulk-bar" id="ml-bulk-bar">
        <span id="ml-bulk-count">0 file được chọn</span>
        <button class="ml-bulk-btn ml-bulk-btn-assign" id="ml-bulk-assign">
            <span class="dashicons dashicons-category" style="font-size:14px;width:14px;height:14px;"></span> Phân loại
        </button>
        <button class="ml-bulk-btn ml-bulk-btn-del" id="ml-bulk-delete">
            <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;"></span> Xóa
        </button>
        <button class="ml-bulk-btn ml-bulk-btn-cancel" id="ml-bulk-cancel">Bỏ chọn</button>
    </div>
</div>

<!-- ── Lightbox ── -->
<div id="ml-lightbox">
    <button class="ml-lb-close" id="ml-lb-close">&#10005;</button>
    <div id="ml-lb-media"></div>
    <div class="ml-lb-name" id="ml-lb-name"></div>
</div>

<!-- ── Modal: New folder ── -->
<div class="ml-modal-backdrop" id="ml-modal-folder">
    <div class="ml-modal">
        <h3>📁 Thêm chuyên mục</h3>
        <p>Tên chuyên mục (chữ thường, gạch ngang)</p>
        <input type="text" id="ml-folder-name-input" placeholder="vd: san-pham, su-kien, banner" maxlength="60">
        <div class="ml-modal-actions">
            <button class="ml-modal-cancel" id="ml-folder-modal-cancel">Hủy</button>
            <button class="ml-modal-confirm" id="ml-folder-modal-confirm">Tạo chuyên mục</button>
        </div>
    </div>
</div>

<!-- ── Modal: Delete confirm ── -->
<div class="ml-modal-backdrop" id="ml-modal-delete">
    <div class="ml-modal">
        <h3>🗑 Xác nhận xóa</h3>
        <p id="ml-delete-msg">Bạn có chắc muốn xóa?</p>
        <div class="ml-modal-actions">
            <button class="ml-modal-cancel" id="ml-delete-modal-cancel">Hủy</button>
            <button class="ml-modal-confirm danger" id="ml-delete-modal-confirm">Xóa vĩnh viễn</button>
        </div>
    </div>
</div>

<!-- ── Modal: Assign folder ── -->
<div class="ml-modal-backdrop" id="ml-modal-assign">
    <div class="ml-modal">
        <h3>📂 Chọn chuyên mục</h3>
        <p>Gán file <strong id="ml-assign-filename"></strong> vào chuyên mục:</p>
        <div id="ml-assign-folder-body"></div>
        <div class="ml-modal-actions" style="margin-top:16px;">
            <button class="ml-modal-cancel" id="ml-assign-modal-cancel">Hủy</button>
            <button class="ml-modal-confirm" id="ml-assign-modal-confirm">Lưu</button>
        </div>
    </div>
</div>

<!-- ── Modal: Rename folder ── -->
<div class="ml-modal-backdrop" id="ml-modal-rename">
    <div class="ml-modal">
        <h3>✏️ Đổi tên chuyên mục</h3>
        <p>Nhập tên mới cho chuyên mục <strong id="ml-rename-old-name"></strong></p>
        <input type="text" id="ml-rename-input" placeholder="Tên mới" maxlength="60">
        <div class="ml-modal-actions">
            <button class="ml-modal-cancel" id="ml-rename-modal-cancel">Hủy</button>
            <button class="ml-modal-confirm" id="ml-rename-modal-confirm">Đổi tên</button>
        </div>
    </div>
</div>
