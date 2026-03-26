<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: PRODUCTS ===== -->
<div id="tab-products" class="aif-tab-content" style="display:none;" role="tabpanel" aria-labelledby="tab-btn-products">

    <!-- Toolbar -->
    <div class="aif-toolbar aif-toolbar-enhanced">
        <div class="aif-toolbar-left">
            <button id="btn-add-product" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> Thêm SP mới
            </button>
            <div class="aif-toolbar-divider"></div>
            <button id="btn-export-csv" class="aif-btn aif-btn-outline">
                <span class="dashicons dashicons-download"></span> Xuất CSV
            </button>
            <button id="btn-import-csv" class="aif-btn aif-btn-outline">
                <span class="dashicons dashicons-upload"></span> Nhập CSV
            </button>
            <input type="file" id="aif-import-file" style="display:none;" accept=".csv">
        </div>
        <div class="aif-toolbar-right">
            <span class="aif-toolbar-hint">
                <span class="aif-toolbar-hint-icon">
                    <span class="dashicons dashicons-info-outline"></span>
                </span>
                Dữ liệu sản phẩm dùng cho AI tư vấn khách tự động.
            </span>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="aif-product-filter-bar aif-filter-bar-enhanced">
        <!-- Search -->
        <div class="aif-product-search-wrap">
            <span class="dashicons dashicons-search aif-product-search-icon"></span>
            <input type="text" id="aif-product-search" placeholder="Tìm tên sản phẩm, SKU..." autocomplete="off">
            <button type="button" id="aif-product-search-clear" class="aif-product-search-clear" style="display:none;" title="Xóa">&#10005;</button>
        </div>

        <div class="aif-filter-separator"></div>

        <!-- Status filter -->
        <div class="aif-filter-btn-group" id="aif-filter-prod-status">
            <span class="aif-filter-group-label">Trạng thái:</span>
            <button class="aif-filter-btn active" data-status="">Tất cả</button>
            <button class="aif-filter-btn" data-status="active">Đang bán</button>
            <button class="aif-filter-btn" data-status="inactive">Ngừng bán</button>
        </div>

        <div class="aif-filter-separator"></div>

        <!-- Category filter (dynamic) -->
        <div class="aif-filter-btn-group" id="aif-filter-prod-category">
            <span class="aif-filter-group-label">Danh mục:</span>
            <button class="aif-filter-btn active" data-cat="">Mọi danh mục</button>
        </div>
    </div>

    <!-- Data table -->
    <div class="aif-card aif-card-table">
        <table class="aif-table">
            <thead>
                <tr>
                    <th style="width:70px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">ID</span>
                    </th>
                    <th style="text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-cart"></span> Tên sản phẩm
                        </span>
                    </th>
                    <th style="width:130px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-category"></span> Danh mục
                        </span>
                    </th>
                    <th style="width:100px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">SKU</span>
                    </th>
                    <th style="width:120px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-money-alt"></span> Giá bán
                        </span>
                    </th>
                    <th style="width:110px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">Trạng thái</span>
                    </th>
                    <th style="width:130px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody id="aif-n8n-products-body">
                <tr>
                    <td colspan="7" class="aif-loading-cell">
                        <div class="aif-loading-state">
                            <div class="aif-spinner-lg"></div>
                            <p class="aif-loading-text">Đang tải sản phẩm...</p>
                            <p class="aif-loading-subtext">Đồng bộ dữ liệu từ kho</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>