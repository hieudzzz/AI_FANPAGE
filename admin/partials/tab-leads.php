<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: LEADS ===== -->
<div id="tab-leads" class="aif-tab-content" style="display:none;" role="tabpanel" aria-labelledby="tab-btn-leads">

    <!-- Toolbar -->
    <div class="aif-toolbar aif-toolbar-enhanced">
        <div class="aif-toolbar-left">
            <button id="btn-export-leads-excel" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-download"></span> Xuất Excel
            </button>
            <div class="aif-toolbar-divider"></div>
            <div class="aif-toolbar-section-label">
                <span class="dashicons dashicons-filter"></span> Fanpage:
            </div>
            <div id="aif-filter-leads-page-group" class="aif-filter-btn-group">
                <button class="aif-filter-btn active" data-page="">Tất cả</button>
            </div>
        </div>
        <div class="aif-toolbar-right">
            <span class="aif-toolbar-hint">
                <span class="aif-toolbar-hint-icon">
                    <span class="dashicons dashicons-info-outline"></span>
                </span>
                Danh sách khách hàng đã để lại SĐT hoặc địa chỉ.
            </span>
        </div>
    </div>

    <!-- Data table -->
    <div class="aif-card aif-card-table">
        <table class="aif-table">
            <thead>
                <tr>
                    <th style="width:45px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">STT</span>
                    </th>
                    <th style="width:160px;">
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-admin-users"></span> Khách hàng
                        </span>
                    </th>
                    <th style="width:140px;">
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-phone"></span> Số điện thoại
                        </span>
                    </th>
                    <th style="width:180px;">
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-heart"></span> Sản phẩm quan tâm
                        </span>
                    </th>
                    <th style="width:180px;">
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-location"></span> Địa chỉ giao hàng
                        </span>
                    </th>
                    <th>
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-edit"></span> Ghi chú
                        </span>
                    </th>
                    <th style="width:110px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-clock"></span> Thời gian
                        </span>
                    </th>
                    <th style="width:90px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-share"></span> Nguồn
                        </span>
                    </th>
                    <th style="width:100px; text-align:right;">
                        <span class="aif-th-inner aif-th-right">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody id="aif-n8n-leads-body">
                <tr>
                    <td colspan="9" class="aif-loading-cell">
                        <div class="aif-loading-state">
                            <div class="aif-spinner-lg"></div>
                            <p class="aif-loading-text">Đang tải dữ liệu...</p>
                            <p class="aif-loading-subtext">Tổng hợp khách hàng tiềm năng</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>