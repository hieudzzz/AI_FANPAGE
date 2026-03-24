<?php

/**
 * N8N Management Page: Chat Sessions & Product Knowledge
 */
if (!defined('ABSPATH'))
    exit;
?>

<div class="wrap aif-container">
    <!-- ===== HEADER ===== -->
    <div class="aif-n8n-header">
        <div class="aif-n8n-header-decor aif-n8n-header-decor-1"></div>
        <div class="aif-n8n-header-decor aif-n8n-header-decor-2"></div>
        <div class="aif-n8n-header-decor aif-n8n-header-decor-3"></div>
        <div class="aif-n8n-header-left">
            <div class="aif-n8n-header-icon">
                <span class="dashicons dashicons-rest-api"></span>
            </div>
            <div>
                <h1 class="aif-n8n-title">AI Chat Manager</h1>
                <p class="aif-n8n-subtitle">Giám sát hội thoại AI &bull; Quản lý sản phẩm &bull; Chăm sóc khách hàng
                    tiềm năng</p>
            </div>
        </div>
        <div class="aif-n8n-header-stats">
            <div class="aif-header-stat">
                <span class="aif-header-stat-dot aif-header-stat-dot-green"></span>
                <span>Hệ thống đang chạy</span>
            </div>

        </div>
    </div>

    <!-- ===== KPI CARDS (dynamic per tab) ===== -->
    <div id="aif-kpi-row" class="aif-kpi-row">
        <!-- Populated by JS -->
    </div>

    <!-- ===== TABS NAVIGATION ===== -->
    <div class="aif-n8n-tabs">
        <button class="aif-n8n-tab active" data-tab="chats">
            <span class="dashicons dashicons-format-chat"></span>
            Phiên Chat
            <span id="kpi-badge-chats" class="aif-tab-badge">0</span>
        </button>
        <button class="aif-n8n-tab" data-tab="products">
            <span class="dashicons dashicons-cart"></span>
            Kho Sản phẩm
            <span id="kpi-badge-products" class="aif-tab-badge">0</span>
        </button>
        <button class="aif-n8n-tab" data-tab="leads">
            <span class="dashicons dashicons-groups"></span>
            Khách hàng tiềm năng
            <span id="kpi-badge-leads" class="aif-tab-badge">0</span>
            <span class="aif-lead-criteria-info" style="display: inline-flex; align-items: center; margin-left: 4px;">
                <span class="dashicons dashicons-info-outline"
                    style="font-size:14px; width:14px; height:14px; cursor:help; opacity:0.75;"></span>
            </span>
        </button>
        <button class="aif-n8n-tab" data-tab="settings">
            <span class="dashicons dashicons-admin-settings"></span>
            Cài đặt
        </button>
        <button class="aif-n8n-tab" data-tab="policies">
            <span class="dashicons dashicons-media-document"></span>
            Chính sách
            <span id="kpi-badge-policies" class="aif-tab-badge">0</span>
        </button>
    </div>

    <!-- ===== TAB: CHATS ===== -->
    <div id="tab-chats" class="aif-tab-content">
        <div class="aif-toolbar" style="margin-bottom:16px;">
            <div class="aif-toolbar-left">
                <div id="aif-filter-page-group" class="aif-filter-btn-group">
                    <button class="aif-filter-btn active" data-page="">Tất cả</button>
                </div>
            </div>
            <div class="aif-toolbar-right">
                <span class="aif-toolbar-hint"><span class="dashicons dashicons-info-outline"
                        style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:4px;"></span>Lọc
                    theo fanpage nhận tin nhắn.</span>
            </div>
        </div>
        <div class="aif-card">
            <table class="aif-table">
                <thead>
                    <tr>
                        <th style="width:240px;">Khách hàng</th>
                        <th style="text-align:center;">Tin nhắn cuối</th>
                        <th style="width:110px; text-align:center;">Ý định</th>
                        <th style="width:160px; text-align:center;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                Điểm Lead
                                <span class="aif-lead-score-info" style="display: flex; align-items: center;">
                                    <span class="dashicons dashicons-info-outline"
                                        style="font-size:15px; width:15px; height:15px; cursor:help; opacity:0.8;"></span>
                                </span>
                            </span>
                        </th>
                        <th style="width:100px; text-align:center;">Mức độ</th>
                        <th style="width:110px; text-align:center;">Cập nhật</th>
                        <th style="width:140px; text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="aif-n8n-chats-body">
                    <tr>
                        <td colspan="7" class="aif-loading-cell">
                            <div class="aif-spinner"></div> Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: PRODUCTS ===== -->
    <div id="tab-products" class="aif-tab-content" style="display:none;">
        <div class="aif-toolbar">
            <div class="aif-toolbar-left">
                <button id="btn-add-product" class="aif-btn aif-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> Thêm SP mới
                </button>
                <button id="btn-export-csv" class="aif-btn aif-btn-outline">
                    <span class="dashicons dashicons-download"></span> Xuất CSV
                </button>
                <button id="btn-import-csv" class="aif-btn aif-btn-outline">
                    <span class="dashicons dashicons-upload"></span> Nhập CSV
                </button>
                <input type="file" id="aif-import-file" style="display:none;" accept=".csv">
            </div>
            <div class="aif-toolbar-right">
                <span class="aif-toolbar-hint"><span class="dashicons dashicons-info-outline"
                        style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:4px;"></span>Dữ
                    liệu sản phẩm dùng cho AI tư vấn khách tự động.</span>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="aif-product-filter-bar">
            <!-- Search -->
            <div class="aif-product-search-wrap">
                <span class="dashicons dashicons-search aif-product-search-icon"></span>
                <input type="text" id="aif-product-search" placeholder="Tìm tên sản phẩm, SKU..." autocomplete="off">
                <button type="button" id="aif-product-search-clear" class="aif-product-search-clear" style="display:none;" title="Xóa">&#10005;</button>
            </div>

            <!-- Status filter -->
            <div class="aif-filter-btn-group" id="aif-filter-prod-status">
                <button class="aif-filter-btn active" data-status="">Tất cả</button>
                <button class="aif-filter-btn" data-status="active">Đang bán</button>
                <button class="aif-filter-btn" data-status="inactive">Ngừng bán</button>
            </div>

            <!-- Category filter (dynamic) -->
            <div class="aif-filter-btn-group" id="aif-filter-prod-category">
                <button class="aif-filter-btn active" data-cat="">Mọi danh mục</button>
            </div>
        </div>

        <div class="aif-card">
            <table class="aif-table">
                <thead>
                    <tr>
                        <th style="width:70px; text-align:center;">ID</th>
                        <th style="text-align:center;">Tên sản phẩm</th>
                        <th style="width:130px; text-align:center;">Danh mục</th>
                        <th style="width:100px; text-align:center;">SKU</th>
                        <th style="width:120px; text-align:center;">Giá bán</th>
                        <th style="width:110px; text-align:center;">Trạng thái</th>
                        <th style="width:130px; text-align:center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="aif-n8n-products-body">
                    <tr>
                        <td colspan="7" class="aif-loading-cell">
                            <div class="aif-spinner"></div> Đang tải sản phẩm...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: LEADS ===== -->
    <div id="tab-leads" class="aif-tab-content" style="display:none;">
        <div class="aif-toolbar">
            <div class="aif-toolbar-left">
                <button id="btn-export-leads-excel" class="aif-btn aif-btn-primary">
                    <span class="dashicons dashicons-download"></span> Xuất Excel
                </button>
                <div id="aif-filter-leads-page-group" class="aif-filter-btn-group">
                    <button class="aif-filter-btn active" data-page="">Tất cả</button>
                </div>
            </div>
            <div class="aif-toolbar-right">
                <span class="aif-toolbar-hint"><span class="dashicons dashicons-info-outline"
                        style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:4px;"></span>Danh
                    sách khách hàng đã để lại SĐT hoặc địa chỉ.</span>
            </div>
        </div>
        <div class="aif-card">
            <table class="aif-table">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">STT</th>
                        <th style="width:160px;">Khách hàng</th>
                        <th style="width:140px;">Số điện thoại</th>
                        <th style="width:180px;">Sản phẩm quan tâm</th>
                        <th style="width:180px;">Địa chỉ giao hàng</th>
                        <th>Ghi chú</th>
                        <th style="width:110px; text-align:center;">Thời gian</th>
                        <th style="width:90px; text-align:center;">Nguồn</th>
                        <th style="width:100px; text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="aif-n8n-leads-body">
                    <tr>
                        <td colspan="7" class="aif-loading-cell">
                            <div class="aif-spinner"></div> Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== TAB: SETTINGS ===== -->
    <div id="tab-settings" class="aif-tab-content" style="display:none;">
        <div class="aif-settings-layout">
            <!-- Sidebar Navigation -->
            <div class="aif-settings-sidebar">
                <div class="aif-settings-sidebar-header">Cài đặt hệ thống</div>
                <div class="aif-settings-nav-item active" data-section="general">
                    <div class="aif-settings-nav-icon aif-settings-nav-icon-indigo">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </div>
                    <div class="aif-settings-nav-text">
                        <div class="title">Cấu hình chung</div>
                        <div class="desc">Prompt &amp; Giới hạn</div>
                    </div>
                </div>
                <div class="aif-settings-nav-item" data-section="contact">
                    <div class="aif-settings-nav-icon aif-settings-nav-icon-violet">
                        <span class="dashicons dashicons-phone"></span>
                    </div>
                    <div class="aif-settings-nav-text">
                        <div class="title">CSKH &amp; Liên hệ</div>
                        <div class="desc">Thông tin hỗ trợ</div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="aif-settings-main">
                <form id="aif-n8n-settings-form">
                    <!-- Section: General -->
                    <div id="section-general" class="aif-settings-section active">
                        <div class="aif-section-header">
                            <div class="aif-section-header-icon">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div>
                                <h3>Cấu hình chung</h3>
                                <p>Thiết lập hành vi cốt lõi của trợ lý AI.</p>
                            </div>
                        </div>

                        <div class="aif-settings-group">
                            <label class="aif-settings-label">System Prompt</label>
                            <p class="aif-settings-desc">Định nghĩa tính cách và quy tắc của AI. Bỏ trống để dùng mặc
                                định.</p>
                            <textarea name="system_prompt" id="set-system-prompt" class="aif-settings-textarea" rows="8"
                                placeholder="VD: Bạn là một trợ lý bán hàng vui vẻ, xưng hô là Em và gọi khách là Anh/Chị..."></textarea>
                        </div>

                        <div class="aif-settings-group">
                            <label class="aif-settings-label">Context Window</label>
                            <p class="aif-settings-desc">Số lượng tin nhắn gần nhất AI sẽ ghi nhớ để hiểu ngữ cảnh.</p>
                            <div class="aif-settings-input-row">
                                <input type="number" name="context_limit" id="set-context-limit"
                                    class="aif-settings-input-sm" min="1" max="50">
                                <span class="aif-settings-input-suffix">tin nhắn lịch sử</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Contact -->
                    <div id="section-contact" class="aif-settings-section">
                        <div class="aif-section-header">
                            <div class="aif-section-header-icon aif-section-header-icon-violet">
                                <span class="dashicons dashicons-phone"></span>
                            </div>
                            <div>
                                <h3>CSKH &amp; Liên hệ</h3>
                                <p>Thông tin để AI cung cấp cho khách khi cần hỗ trợ trực tiếp.</p>
                            </div>
                        </div>

                        <div class="aif-settings-group">
                            <label class="aif-settings-label">Thông tin liên hệ</label>
                            <p class="aif-settings-desc">Hotline, địa chỉ văn phòng, hoặc khung giờ hỗ trợ.</p>
                            <textarea name="cs_info" id="set-cs-info" class="aif-settings-textarea" rows="5"
                                placeholder="VD: Hotline: 0901234567. Giờ làm việc: 8h-18h hàng ngày..."></textarea>
                        </div>
                    </div>

                    <!-- Form Footer -->
                    <div class="aif-settings-footer">
                        <button type="submit" class="aif-btn aif-btn-primary aif-btn-save-full">
                            <span class="dashicons dashicons-saved"></span> Lưu tất cả thay đổi
                        </button>
                        <button type="button" id="btn-flush-ai-cache" class="aif-btn aif-btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
                            <span class="dashicons dashicons-trash" style="font-size:15px;width:15px;height:15px;"></span>
                            Xóa cache AI
                        </button>
                        <span id="settings-save-msg" class="aif-settings-msg"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== TAB: POLICIES ===== -->
    <div id="tab-policies" class="aif-tab-content" style="display:none;">
        <div class="aif-card" style="overflow:hidden;">

            <!-- Header -->
            <div
                style="padding:20px 24px; background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%); border-bottom:1px solid #e2e8f0;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">

                    <!-- Left: icon + title -->
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div
                            style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(16,185,129,0.3);">
                            <span class="dashicons dashicons-media-document"
                                style="color:#fff;font-size:22px;width:22px;height:22px;"></span>
                        </div>
                        <div>
                            <h2 style="margin:0;font-size:17px;font-weight:800;color:#0f172a;">Chính sách &amp; Quy định
                            </h2>
                            <p style="margin:3px 0 0;font-size:12px;color:#64748b;">Các mục <b
                                    style="color:#10b981;">đang
                                    bật</b> sẽ được inject vào context của chatbot khi trả lời khách.</p>
                        </div>
                    </div>

                    <!-- Right: stats + button -->
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <div id="policy-stats-bar" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                        </div>
                        <button id="btn-add-policy" class="aif-btn aif-btn-primary"
                            style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;">
                            <span class="dashicons dashicons-plus-alt2"
                                style="font-size:15px;width:15px;height:15px;"></span>
                            Thêm chính sách
                        </button>
                    </div>

                </div>
            </div>

            <!-- List -->
            <div id="policy-list" style="display:flex; flex-direction:column;">
                <div style="text-align:center; padding:60px; color:#94a3b8;">
                    <div class="spinner is-active" style="float:none;margin:0 auto 12px;"></div>
                    Đang tải...
                </div>
            </div>

        </div>
    </div>

</div>


<!-- ===== MODAL: Policy Add/Edit ===== -->
<div id="aif-policy-modal" class="aif-modal">
    <div class="aif-modal-content" style="max-width:700px;">
        <div class="aif-modal-header">
            <div class="aif-modal-header-decor-1"></div>
            <div class="aif-modal-header-decor-2"></div>
            <div class="aif-modal-header-inner">
                <div class="aif-modal-header-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div>
                    <h3 id="policy-modal-title" class="aif-modal-title">Thêm chính sách</h3>
                    <p class="aif-modal-subtitle">Nội dung này sẽ được inject vào context của chatbot</p>
                </div>
            </div>
            <button type="button" class="aif-modal-close" id="policy-modal-close">&times;</button>
        </div>
        <div class="aif-modal-body" style="padding:24px; overflow-y:auto; max-height:65vh;">
            <input type="hidden" id="policy-edit-id" value="">

            <div class="aif-form-group" style="margin-bottom:20px;">
                <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">
                    Tiêu đề chính sách <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" id="policy-title-input"
                    style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border-color 0.2s;"
                    placeholder="VD: Chính sách đổi trả, Chính sách bảo hành, Quy trình đặt hàng...">
            </div>

            <div class="aif-form-group">
                <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">
                    Nội dung <span style="color:#ef4444;">*</span>
                </label>
                <p style="font-size:12px;color:#64748b;margin:0 0 8px;">
                    Viết rõ ràng, súc tích. Chatbot sẽ đọc và trả lời dựa trên nội dung này.
                    Hỗ trợ xuống dòng và danh sách (dấu -).
                </p>
                <textarea id="policy-content-input" rows="12"
                    style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box;outline:none;resize:vertical;line-height:1.6;transition:border-color 0.2s;"
                    placeholder="VD:
- Sản phẩm được đổi trả trong vòng 7 ngày kể từ ngày nhận hàng.
- Điều kiện: còn nguyên tem, hộp, chưa qua sử dụng.
- Khách hàng chịu phí vận chuyển chiều về, shop chịu phí gửi lại.
- Liên hệ hotline để được hỗ trợ đổi trả."></textarea>
                <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                    <span id="policy-char-count" style="font-size:11px;color:#94a3b8;">0 ký tự</span>
                </div>
            </div>
        </div>
        <div class="aif-modal-footer"
            style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
            <button type="button" id="policy-modal-cancel" class="aif-btn aif-btn-outline">Hủy</button>
            <button type="button" id="policy-modal-save" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-saved" style="font-size:15px;width:15px;height:15px;"></span>
                Lưu chính sách
            </button>
        </div>
    </div>
</div>


<div id="aif-product-modal" class="aif-modal">
    <div class="aif-modal-content">
        <div class="aif-modal-header">
            <div class="aif-modal-header-decor-1"></div>
            <div class="aif-modal-header-decor-2"></div>
            <div class="aif-modal-header-inner">
                <div class="aif-modal-header-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div>
                    <h3 id="modal-title">Thêm sản phẩm mới</h3>
                    <p>Thông tin sản phẩm giúp AI tư vấn khách hàng chuyên nghiệp hơn.</p>
                </div>
            </div>
            <button type="button" class="aif-modal-close"
                onclick="jQuery('#aif-product-modal').removeClass('active');">&#10005;</button>
        </div>
        <div class="aif-modal-body">
            <form id="aif-product-form">
                <input type="hidden" name="id" id="prod-id">

                <div class="aif-form-group">
                    <label class="aif-form-label">Tên sản phẩm <span class="aif-form-required">*</span></label>
                    <input type="text" name="product_name" id="prod-name" required
                        placeholder="Ví dụ: Áo thun nam Cotton Premium">
                </div>

                <div class="aif-form-row">
                    <div>
                        <label class="aif-form-label">Danh mục</label>
                        <input type="text" name="category" id="prod-cat" placeholder="Thời trang nam">
                    </div>
                    <div>
                        <label class="aif-form-label">SKU</label>
                        <input type="text" name="sku" id="prod-sku" placeholder="TSHIRT-001">
                    </div>
                </div>

                <div class="aif-form-row">
                    <div>
                        <label class="aif-form-label">Giá bán (VNĐ)</label>
                        <input type="text" name="price" id="prod-price" placeholder="250.000" class="aif-input-price">
                    </div>
                    <div>
                        <label class="aif-form-label">Trạng thái</label>
                        <input type="hidden" name="status" id="prod-status" value="active">
                        <div class="aif-status-toggle">
                            <button type="button" class="aif-status-btn aif-status-btn-active active" data-value="active">
                                <span class="aif-status-btn-dot"></span>
                                Đang kinh doanh
                            </button>
                            <button type="button" class="aif-status-btn aif-status-btn-inactive" data-value="inactive">
                                <span class="aif-status-btn-dot"></span>
                                Ngừng kinh doanh
                            </button>
                        </div>
                    </div>
                </div>

                <div class="aif-form-group">
                    <label class="aif-form-label">Mô tả đặc điểm <span class="aif-form-hint">(Dành cho
                            AI)</span></label>
                    <textarea name="description" id="prod-desc"
                        placeholder="Nhập thông số, ưu điểm nổi bật để AI tư vấn khách tốt hơn..."></textarea>
                </div>

                <div class="aif-modal-footer">
                    <button type="button" class="aif-btn aif-btn-ghost"
                        onclick="jQuery('#aif-product-modal').removeClass('active');">Hủy bỏ</button>
                    <button type="submit" class="aif-btn aif-btn-primary">
                        <span class="dashicons dashicons-saved"></span> Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== MODAL: Chat Details ===== -->
<div id="aif-chat-details-modal" class="aif-modal">
    <div class="aif-modal-content aif-modal-lg">
        <div class="aif-modal-header">
            <div class="aif-modal-header-decor-1"></div>
            <div class="aif-modal-header-decor-2"></div>
            <div class="aif-modal-header-inner">
                <div class="aif-modal-header-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div>
                    <h3>Chi tiết phiên chat</h3>
                    <p>Phân tích AI và thông tin khách hàng thu thập được.</p>
                </div>
            </div>
            <button type="button" id="btn-close-chat-modal" class="aif-modal-close">&#10005;</button>
        </div>
        <div class="aif-modal-body aif-modal-body-scroll">
            <div id="chat-details-content">
                <!-- Dynamic Content -->
            </div>
            <div class="aif-modal-footer">
                <button type="button" class="aif-btn aif-btn-primary" id="btn-close-chat-modal-footer">
                    <span class="dashicons dashicons-no-alt" style="font-size:15px;width:15px;height:15px;"></span>
                    Đóng lại
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== SCOPED STYLES ===== -->
<?php /* CSS: assets/css/n8n-manager.css | JS: assets/js/n8n-manager.js */ ?>