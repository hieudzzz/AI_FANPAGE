<?php if (!defined('ABSPATH')) exit; ?>

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
