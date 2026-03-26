<?php if (!defined('ABSPATH')) exit; ?>

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
