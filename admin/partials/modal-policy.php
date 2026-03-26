<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== MODAL: Policy Add/Edit ===== -->
<div id="aif-policy-modal" class="aif-modal">
    <div class="aif-modal-content aif-modal-policy-size">
        <!-- Header -->
        <div class="aif-modal-header aif-modal-header-emerald">
            <div class="aif-modal-header-decor-1"></div>
            <div class="aif-modal-header-decor-2"></div>
            <div class="aif-modal-header-inner">
                <div class="aif-modal-header-icon aif-modal-icon-emerald">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div>
                    <h3 id="policy-modal-title">Thêm chính sách</h3>
                    <p>Nội dung này sẽ được inject vào context của chatbot</p>
                </div>
            </div>
            <button type="button" class="aif-modal-close" id="policy-modal-close">&times;</button>
        </div>

        <!-- Body -->
        <div class="aif-modal-body aif-modal-body-scroll">
            <input type="hidden" id="policy-edit-id" value="">

            <!-- Title field -->
            <div class="aif-form-group">
                <label class="aif-form-label">
                    <span class="aif-form-label-icon aif-form-label-icon-emerald">
                        <span class="dashicons dashicons-edit"></span>
                    </span>
                    Tiêu đề chính sách <span class="aif-form-required">*</span>
                </label>
                <input type="text" id="policy-title-input"
                    placeholder="VD: Chính sách đổi trả, Chính sách bảo hành, Quy trình đặt hàng...">
            </div>

            <!-- Content field -->
            <div class="aif-form-group">
                <label class="aif-form-label">
                    <span class="aif-form-label-icon aif-form-label-icon-blue">
                        <span class="dashicons dashicons-text-page"></span>
                    </span>
                    Nội dung <span class="aif-form-required">*</span>
                </label>
                <p class="aif-form-desc">
                    Viết rõ ràng, súc tích. Chatbot sẽ đọc và trả lời dựa trên nội dung này.
                    Hỗ trợ xuống dòng và danh sách (dấu -).
                </p>
                <textarea id="policy-content-input" rows="12"
                    placeholder="VD:
- Sản phẩm được đổi trả trong vòng 7 ngày kể từ ngày nhận hàng.
- Điều kiện: còn nguyên tem, hộp, chưa qua sử dụng.
- Khách hàng chịu phí vận chuyển chiều về, shop chịu phí gửi lại.
- Liên hệ hotline để được hỗ trợ đổi trả."></textarea>
                <div class="aif-form-char-count">
                    <span id="policy-char-count">0 ký tự</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="aif-modal-footer aif-modal-footer-bar">
            <button type="button" id="policy-modal-cancel" class="aif-btn aif-btn-outline">
                <span class="dashicons dashicons-no-alt"></span> Hủy
            </button>
            <button type="button" id="policy-modal-save" class="aif-btn aif-btn-primary aif-btn-emerald">
                <span class="dashicons dashicons-saved"></span>
                Lưu chính sách
            </button>
        </div>
    </div>
</div>