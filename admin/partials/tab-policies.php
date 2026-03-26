<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: POLICIES ===== -->
<div id="tab-policies" class="aif-tab-content" style="display:none;" role="tabpanel" aria-labelledby="tab-btn-policies">
    <div class="aif-card aif-card-policies">

        <!-- Header -->
        <div class="aif-policy-header">
            <div class="aif-policy-header-left">
                <div class="aif-policy-header-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="aif-policy-header-text">
                    <h2>Chính sách &amp; Quy định</h2>
                    <p>Các mục <strong class="aif-text-success">đang bật</strong> sẽ được inject vào context của chatbot khi trả lời khách.</p>
                </div>
            </div>
            <div class="aif-policy-header-right">
                <div id="policy-stats-bar" class="aif-policy-stats"></div>
                <button id="btn-add-policy" class="aif-btn aif-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    Thêm chính sách
                </button>
            </div>
        </div>

        <!-- List -->
        <div id="policy-list" class="aif-policy-list">
            <div class="aif-loading-state" style="padding:60px;">
                <div class="aif-spinner-lg"></div>
                <p class="aif-loading-text">Đang tải...</p>
                <p class="aif-loading-subtext">Tải danh sách chính sách</p>
            </div>
        </div>

    </div>
</div>