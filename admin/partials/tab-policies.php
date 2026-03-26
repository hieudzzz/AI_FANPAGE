<?php if (!defined('ABSPATH')) exit; ?>

<!-- ===== TAB: POLICIES ===== -->
<div id="tab-policies" class="aif-tab-content" style="display:none;">
    <div class="aif-card" style="overflow:hidden;">

        <!-- Header -->
        <div
            style="padding:20px 24px; background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%); border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">

                <!-- Left: icon + title -->
                <div style="display:flex; align-items:center; gap:14px;">
                    <div
                        style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(16,185,129,0.3);">
                        <span class="dashicons dashicons-media-document"
                            style="color:#fff;font-size:22px;width:22px;height:22px;"></span>
                    </div>
                    <div>
                        <h2 style="margin:0;font-size:17px;font-weight:800;color:#0f172a;">Chính sách &amp; Quy định
                        </h2>
                        <p style="margin:3px 0 0;font-size:12px;color:#64748b;">Các mục <b
                                style="color:#10b981;">đang
                                bật</b> sẽ được inject vào context của chatbot khi trả lời khách.</p>
                    </div>
                </div>

                <!-- Right: stats + button -->
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div id="policy-stats-bar" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    </div>
                    <button id="btn-add-policy" class="aif-btn aif-btn-primary"
                        style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;">
                        <span class="dashicons dashicons-plus-alt2"
                            style="font-size:15px;width:15px;height:15px;"></span>
                        Thêm chính sách
                    </button>
                </div>

            </div>
        </div>

        <!-- List -->
        <div id="policy-list" style="display:flex; flex-direction:column;">
            <div style="text-align:center; padding:60px; color:#94a3b8;">
                <div class="spinner is-active" style="float:none;margin:0 auto 12px;"></div>
                Đang tải...
            </div>
        </div>

    </div>
</div>
