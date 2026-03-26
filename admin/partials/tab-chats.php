<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: CHATS ===== -->
<div id="tab-chats" class="aif-tab-content">
    <div class="aif-toolbar" style="margin-bottom:16px;">
        <div class="aif-toolbar-left">
            <div id="aif-filter-page-group" class="aif-filter-btn-group">
                <button class="aif-filter-btn active" data-page="">Tất cả</button>
            </div>
        </div>
        <div class="aif-toolbar-right">
            <span class="aif-toolbar-hint"><span class="dashicons dashicons-info-outline"
                    style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:4px;"></span>Lọc
                theo fanpage nhận tin nhắn.</span>
        </div>
    </div>
    <div class="aif-card">
        <table class="aif-table">
            <thead>
                <tr>
                    <th style="width:240px;">Khách hàng</th>
                    <th style="text-align:center;">Tin nhắn cuối</th>
                    <th style="width:110px; text-align:center;">Ý định</th>
                    <th style="width:160px; text-align:center;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                            Điểm Lead
                            <span class="aif-lead-score-info" style="display: flex; align-items: center;">
                                <span class="dashicons dashicons-info-outline"
                                    style="font-size:15px; width:15px; height:15px; cursor:help; opacity:0.8;"></span>
                            </span>
                        </span>
                    </th>
                    <th style="width:100px; text-align:center;">Mức độ</th>
                    <th style="width:110px; text-align:center;">Cập nhật</th>
                    <th style="width:140px; text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody id="aif-n8n-chats-body">
                <tr>
                    <td colspan="7" class="aif-loading-cell">
                        <div class="aif-spinner"></div> Đang tải dữ liệu...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
