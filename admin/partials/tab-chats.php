<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: CHATS ===== -->
<div id="tab-chats" class="aif-tab-content" role="tabpanel" aria-labelledby="tab-btn-chats">

    <!-- Toolbar -->
    <div class="aif-toolbar aif-toolbar-enhanced">
        <div class="aif-toolbar-left">
            <div class="aif-toolbar-section-label">
                <span class="dashicons dashicons-filter"></span> Fanpage:
            </div>
            <div id="aif-filter-page-group" class="aif-filter-btn-group">
                <button class="aif-filter-btn active" data-page="">Tất cả</button>
            </div>
        </div>
        <div class="aif-toolbar-right">
            <span class="aif-toolbar-hint">
                <span class="aif-toolbar-hint-icon">
                    <span class="dashicons dashicons-info-outline"></span>
                </span>
                Lọc theo fanpage nhận tin nhắn.
            </span>
        </div>
    </div>

    <!-- Data table -->
    <div class="aif-card aif-card-table">
        <table class="aif-table">
            <thead>
                <tr>
                    <th style="width:240px;">
                        <span class="aif-th-inner">
                            <span class="dashicons dashicons-admin-users"></span> Khách hàng
                        </span>
                    </th>
                    <th style="text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-format-chat"></span> Tin nhắn cuối
                        </span>
                    </th>
                    <th style="width:110px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-tag"></span> Ý định
                        </span>
                    </th>
                    <th style="width:160px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-chart-bar"></span> Điểm Lead
                            <span class="aif-lead-score-info">
                                <span class="dashicons dashicons-info-outline"></span>
                            </span>
                        </span>
                    </th>
                    <th style="width:100px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-flag"></span> Mức độ
                        </span>
                    </th>
                    <th style="width:110px; text-align:center;">
                        <span class="aif-th-inner aif-th-center">
                            <span class="dashicons dashicons-clock"></span> Cập nhật
                        </span>
                    </th>
                    <th style="width:140px; text-align:right;">
                        <span class="aif-th-inner aif-th-right">Thao tác</span>
                    </th>
                </tr>
            </thead>
            <tbody id="aif-n8n-chats-body">
                <tr>
                    <td colspan="7" class="aif-loading-cell">
                        <div class="aif-loading-state">
                            <div class="aif-spinner-lg"></div>
                            <p class="aif-loading-text">Đang tải dữ liệu...</p>
                            <p class="aif-loading-subtext">Vui lòng đợi trong giây lát</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>