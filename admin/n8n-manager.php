<?php

/**
 * N8N Management Page: Chat Sessions & Product Knowledge
 * 
 * Tabs are split into partials under admin/partials/ for maintainability.
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
    <nav class="aif-n8n-tabs" role="tablist" aria-label="Quản lý AI Chat">
        <button class="aif-n8n-tab active" data-tab="chats" role="tab" aria-selected="true" aria-controls="tab-chats">
            <span class="dashicons dashicons-format-chat"></span>
            <span class="aif-tab-label">Phiên Chat</span>
            <span id="kpi-badge-chats" class="aif-tab-badge">0</span>
        </button>
        <button class="aif-n8n-tab" data-tab="products" role="tab" aria-selected="false" aria-controls="tab-products">
            <span class="dashicons dashicons-cart"></span>
            <span class="aif-tab-label">Kho Sản phẩm</span>
            <span id="kpi-badge-products" class="aif-tab-badge">0</span>
        </button>
        <button class="aif-n8n-tab" data-tab="leads" role="tab" aria-selected="false" aria-controls="tab-leads">
            <span class="dashicons dashicons-groups"></span>
            <span class="aif-tab-label">Khách hàng tiềm năng</span>
            <span id="kpi-badge-leads" class="aif-tab-badge">0</span>
            <span class="aif-lead-criteria-info" title="Tiêu chí đánh giá khách hàng tiềm năng">
                <span class="dashicons dashicons-info-outline"></span>
            </span>
        </button>
        <button class="aif-n8n-tab" data-tab="settings" role="tab" aria-selected="false" aria-controls="tab-settings">
            <span class="dashicons dashicons-admin-settings"></span>
            <span class="aif-tab-label">Cài đặt</span>
        </button>
        <button class="aif-n8n-tab" data-tab="policies" role="tab" aria-selected="false" aria-controls="tab-policies">
            <span class="dashicons dashicons-media-document"></span>
            <span class="aif-tab-label">Chính sách</span>
            <span id="kpi-badge-policies" class="aif-tab-badge">0</span>
        </button>
    </nav>

    <!-- ===== TAB CONTENTS (partials) ===== -->
    <?php
    $partials_dir = AIF_PATH . 'admin/partials/';
    include $partials_dir . 'tab-chats.php';
    include $partials_dir . 'tab-products.php';
    include $partials_dir . 'tab-leads.php';
    include $partials_dir . 'tab-settings.php';
    include $partials_dir . 'tab-policies.php';
    ?>

</div>

<!-- ===== MODALS (partials) ===== -->
<?php
include $partials_dir . 'modal-policy.php';
include $partials_dir . 'modal-product.php';
include $partials_dir . 'modal-chat-details.php';
?>

<!-- ===== SCOPED STYLES ===== -->
<?php /* CSS: assets/css/n8n-manager.css | JS: assets/js/n8n-manager.js */ ?>