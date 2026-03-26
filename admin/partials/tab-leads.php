<?php if (!defined('ABSPATH')) exit; ?>

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
