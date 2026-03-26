<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: SETTINGS ===== -->
<div id="tab-settings" class="aif-tab-content" style="display:none;" role="tabpanel" aria-labelledby="tab-btn-settings">
    <div class="aif-settings-layout">
        <!-- Sidebar Navigation -->
        <div class="aif-settings-sidebar">
            <div class="aif-settings-sidebar-header">
                <span class="dashicons dashicons-admin-settings"></span>
                Cài đặt hệ thống
            </div>
            <div class="aif-settings-nav-item active" data-section="general">
                <div class="aif-settings-nav-icon aif-settings-nav-icon-indigo">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <div class="aif-settings-nav-text">
                    <div class="title">Cấu hình chung</div>
                    <div class="desc">Prompt &amp; Giới hạn</div>
                </div>
                <span class="aif-nav-arrow dashicons dashicons-arrow-right-alt2"></span>
            </div>
            <div class="aif-settings-nav-item" data-section="contact">
                <div class="aif-settings-nav-icon aif-settings-nav-icon-violet">
                    <span class="dashicons dashicons-phone"></span>
                </div>
                <div class="aif-settings-nav-text">
                    <div class="title">CSKH &amp; Liên hệ</div>
                    <div class="desc">Thông tin hỗ trợ</div>
                </div>
                <span class="aif-nav-arrow dashicons dashicons-arrow-right-alt2"></span>
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
                        <label class="aif-settings-label">
                            <span class="aif-label-icon aif-label-icon-indigo">
                                <span class="dashicons dashicons-editor-code"></span>
                            </span>
                            System Prompt
                        </label>
                        <p class="aif-settings-desc">Định nghĩa tính cách và quy tắc của AI. Bỏ trống để dùng mặc
                            định.</p>
                        <textarea name="system_prompt" id="set-system-prompt" class="aif-settings-textarea" rows="8"
                            placeholder="VD: Bạn là một trợ lý bán hàng vui vẻ, xưng hô là Em và gọi khách là Anh/Chị..."></textarea>
                    </div>

                    <div class="aif-settings-group">
                        <label class="aif-settings-label">
                            <span class="aif-label-icon aif-label-icon-violet">
                                <span class="dashicons dashicons-database"></span>
                            </span>
                            Context Window
                        </label>
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
                        <label class="aif-settings-label">
                            <span class="aif-label-icon aif-label-icon-emerald">
                                <span class="dashicons dashicons-phone"></span>
                            </span>
                            Thông tin liên hệ
                        </label>
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
                    <button type="button" id="btn-flush-ai-cache" class="aif-btn aif-btn-outline">
                        <span class="dashicons dashicons-trash"></span>
                        Xóa cache AI
                    </button>
                    <span id="settings-save-msg" class="aif-settings-msg"></span>
                </div>
            </form>
        </div>
    </div>
</div>