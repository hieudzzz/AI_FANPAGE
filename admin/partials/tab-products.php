<?php if (!defined('ABSPATH')) exit; ?>

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
