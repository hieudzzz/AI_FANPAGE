<?php
/**
 * Queue List Page — Redesigned
 */
$manager = new AIF_Facebook_Manager();
$items = $manager->get_queue_items();

$count_pending = 0;
$count_scheduled = 0;
$count_processing = 0;
$count_failed = 0;

foreach ((array) $items as $item) {
    $s = strtolower($item->status);
    if ($s === 'pending')
        $count_pending++;
    elseif ($s === 'scheduled')
        $count_scheduled++;
    elseif ($s === 'processing')
        $count_processing++;
    elseif (str_starts_with($s, 'failed'))
        $count_failed++;
}
?>
<style>
    :root {
        --q-primary: #6366f1;
        --q-success: #10b981;
        --q-warning: #f59e0b;
        --q-danger: #ef4444;
        --q-blue: #3b82f6;
        --q-border: #e2e8f0;
        --q-bg: #f8fafc;
        --q-text: #1e293b;
        --q-muted: #64748b;
    }

    .aif-q-wrap {
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--q-text);
        padding: 20px 20px 40px 0;
        max-width: 1600px;
    }

    /* ── HEADER ── */
    .aif-q-header {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 55%, #a855f7 100%);
        border-radius: 20px;
        padding: 28px 36px;
        margin-bottom: 24px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 12px 40px -8px rgba(99, 102, 241, .45);
    }

    .aif-q-header-decor {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .aif-q-header-decor-1 {
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, .06);
        top: -70px;
        right: 130px;
    }

    .aif-q-header-decor-2 {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, .08);
        bottom: -40px;
        right: 60px;
    }

    .aif-q-header-left {
        display: flex;
        align-items: center;
        gap: 18px;
        position: relative;
        z-index: 1;
    }

    .aif-q-header-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .18);
        border: 1.5px solid rgba(255, 255, 255, .25);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .aif-q-header-icon .dashicons {
        font-size: 26px;
        width: 26px;
        height: 26px;
        color: #fff;
    }

    .aif-q-title {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -.4px;
        color: #fff !important;
    }

    .aif-q-subtitle {
        margin: 4px 0 0;
        opacity: .82;
        font-size: 13.5px;
    }

    .aif-q-header-right {
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .aif-q-time-badge {
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 10px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    /* ── KPI ROW ── */
    .aif-q-kpi-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 22px;
    }

    .aif-q-kpi {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--q-border);
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .04);
        transition: transform .2s, box-shadow .2s;
    }

    .aif-q-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px -6px rgba(0, 0, 0, .1);
    }

    .aif-q-kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .aif-q-kpi-icon .dashicons {
        font-size: 22px;
        width: 22px;
        height: 22px;
    }

    .kpi-indigo {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
    }

    .kpi-amber {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #d97706;
    }

    .kpi-blue {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #2563eb;
    }

    .kpi-red {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #dc2626;
    }

    .aif-q-kpi-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--q-muted);
        font-weight: 700;
    }

    .aif-q-kpi-value {
        font-size: 30px;
        font-weight: 800;
        color: var(--q-text);
        line-height: 1.1;
        letter-spacing: -1px;
        margin-top: 2px;
    }

    /* ── TOOLBAR ── */
    .aif-q-toolbar {
        background: #fff;
        border: 1px solid var(--q-border);
        border-radius: 14px;
        padding: 14px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        flex-wrap: wrap;
    }

    .aif-q-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .aif-q-toolbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Localhost cron toggle */
    .aif-q-cron-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--q-bg);
        border: 1px solid var(--q-border);
        border-radius: 10px;
        padding: 8px 14px;
    }

    .aif-q-cron-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--q-text);
    }

    .aif-q-cron-status {
        font-size: 11px;
        color: var(--q-muted);
    }

    /* Toggle switch */
    .aif-switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
    }

    .aif-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .aif-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #cbd5e1;
        transition: .3s;
        border-radius: 22px;
    }

    .aif-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background: #fff;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, .15);
    }

    input:checked+.aif-slider {
        background: var(--q-primary);
    }

    input:checked+.aif-slider:before {
        transform: translateX(18px);
    }

    /* Buttons */
    .aif-q-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 18px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        transition: all .2s;
        text-decoration: none;
        white-space: nowrap;
        font-family: inherit;
    }

    .aif-q-btn .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .aif-q-btn-primary {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        box-shadow: 0 4px 12px -3px rgba(99, 102, 241, .4);
    }

    .aif-q-btn-primary:hover {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        transform: translateY(-1px);
        color: #fff;
    }

    .aif-q-btn-outline {
        background: #fff;
        border: 1.5px solid var(--q-border);
        color: var(--q-text);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .aif-q-btn-outline:hover {
        background: var(--q-bg);
        border-color: #6366f1;
        color: #6366f1;
    }

    .aif-q-btn-ghost {
        background: #f1f5f9;
        border: none;
        color: var(--q-muted);
    }

    .aif-q-btn-ghost:hover {
        background: #e2e8f0;
        color: var(--q-text);
    }

    .aif-q-btn-danger {
        background: #fff;
        border: 1.5px solid #fee2e2;
        color: var(--q-danger);
    }

    .aif-q-btn-danger:hover {
        background: #fee2e2;
        border-color: #fecaca;
    }

    .aif-q-btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* ── TABLE CARD ── */
    .aif-q-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--q-border);
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .04), 0 4px 16px rgba(0, 0, 0, .03);
    }

    .aif-q-table {
        width: 100%;
        border-collapse: collapse;
    }

    .aif-q-table thead th {
        background: linear-gradient(180deg, #f8fafc, #f1f5f9);
        color: var(--q-muted);
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .7px;
        padding: 14px 18px;
        border-bottom: 1.5px solid #e8edf4;
        white-space: nowrap;
    }

    .aif-q-table tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
        font-size: 14px;
        color: var(--q-text);
    }

    .aif-q-table tbody tr:last-child td {
        border-bottom: none;
    }

    .aif-q-table tbody tr {
        transition: background .15s;
    }

    .aif-q-table tbody tr:hover {
        background: linear-gradient(90deg, #fafaff, #f9f6ff);
    }

    /* Post thumbnail */
    .aif-q-thumb {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid var(--q-border);
        flex-shrink: 0;
    }

    .aif-q-thumb-empty {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px dashed #cbd5e1;
        flex-shrink: 0;
        color: #94a3b8;
    }

    /* Post title block */
    .aif-q-post-block {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .aif-q-post-title {
        font-weight: 700;
        font-size: 13.5px;
        color: var(--q-text);
        text-decoration: none;
        display: block;
        line-height: 1.35;
    }

    .aif-q-post-title:hover {
        color: var(--q-primary);
    }

    .aif-q-post-meta {
        font-size: 11.5px;
        color: var(--q-muted);
        margin-top: 3px;
    }

    .aif-q-post-content {
        font-size: 11.5px;
        color: #475569;
        margin-top: 4px;
        max-width: 280px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Platform badge */
    .aif-q-platform {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 8px;
    }

    .aif-q-platform .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
    }

    .q-plat-facebook {
        background: #e7f0fd;
        color: #1877F2;
    }

    .q-plat-website {
        background: #d1fae5;
        color: #059669;
    }

    /* Target name */
    .aif-q-target {
        font-weight: 600;
        font-size: 13px;
        color: var(--q-text);
    }

    .aif-q-target-id {
        font-size: 11px;
        color: var(--q-muted);
        margin-top: 2px;
    }

    /* Status badge */
    .aif-q-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 11px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .3px;
        white-space: nowrap;
    }

    .qst-pending {
        background: #f1f5f9;
        color: #64748b;
    }

    .qst-scheduled {
        background: #fffbeb;
        color: #b45309;
    }

    .qst-processing {
        background: #dbeafe;
        color: #1d4ed8;
        animation: q-blink 1.8s infinite;
    }

    .qst-done {
        background: #dcfce7;
        color: #15803d;
    }

    .qst-failed {
        background: #fee2e2;
        color: #dc2626;
        cursor: help;
    }

    /* Error detail under status badge */
    .aif-q-error-detail {
        font-size: 10.5px;
        color: #b91c1c;
        margin-top: 5px;
        max-width: 160px;
        line-height: 1.4;
        background: #fff1f1;
        border: 1px solid #fecaca;
        border-radius: 6px;
        padding: 3px 7px;
        word-break: break-word;
    }

    /* Tooltip for full error message */
    .aif-q-tooltip-wrap {
        position: relative;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .aif-q-tooltip {
        visibility: hidden;
        opacity: 0;
        background: #1e293b;
        color: #fff;
        font-size: 12px;
        line-height: 1.5;
        padding: 8px 12px;
        border-radius: 9px;
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        white-space: pre-wrap;
        min-width: 220px;
        max-width: 300px;
        word-break: break-word;
        z-index: 9999;
        box-shadow: 0 8px 24px rgba(0,0,0,.25);
        transition: opacity .18s, visibility .18s;
        pointer-events: none;
    }

    .aif-q-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #1e293b;
    }

    .aif-q-tooltip-wrap:hover .aif-q-tooltip {
        visibility: visible;
        opacity: 1;
    }

    /* Retry button */
    .aif-q-btn-retry {
        background: #fff7ed;
        border: 1.5px solid #fed7aa;
        color: #ea580c;
    }

    .aif-q-btn-retry:hover {
        background: #ffedd5;
        border-color: #fb923c;
    }

    @keyframes q-blink {

        0%,
        100% {
            opacity: .7
        }

        50% {
            opacity: 1
        }
    }

    /* Schedule time */
    .aif-q-sched {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .aif-q-sched .dashicons {
        font-size: 15px;
        width: 15px;
        height: 15px;
        color: var(--q-muted);
    }

    .aif-q-sched-now {
        color: var(--q-success);
        font-weight: 700;
    }

    /* Queue ID chip */
    .aif-q-id-chip {
        display: inline-block;
        background: #f1f5f9;
        color: var(--q-muted);
        border-radius: 6px;
        padding: 2px 7px;
        font-size: 11px;
        font-weight: 700;
    }

    /* Empty state */
    .aif-q-empty {
        padding: 70px 40px;
        text-align: center;
    }

    .aif-q-empty .dashicons {
        font-size: 52px;
        width: 52px;
        height: 52px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }

    .aif-q-empty h3 {
        margin: 0 0 8px;
        font-size: 18px;
        color: var(--q-text);
    }

    .aif-q-empty p {
        margin: 0;
        color: var(--q-muted);
        font-size: 14px;
    }

    /* Spinning */
    @keyframes aif-q-spin {
        to {
            transform: rotate(360deg);
        }
    }

    .aif-q-spin {
        animation: aif-q-spin .65s linear infinite;
    }
</style>

<div class="aif-q-wrap">

    <!-- HEADER -->
    <div class="aif-q-header">
        <div class="aif-q-header-decor aif-q-header-decor-1"></div>
        <div class="aif-q-header-decor aif-q-header-decor-2"></div>
        <div class="aif-q-header-left">
            <div class="aif-q-header-icon">
                <span class="dashicons dashicons-playlist-audio"></span>
            </div>
            <div>
                <h1 class="aif-q-title">Hàng Chờ Đăng Bài</h1>
                <p class="aif-q-subtitle">Theo dõi & kiểm soát toàn bộ lịch đăng tự động</p>
            </div>
        </div>
        <div class="aif-q-header-right">
            <div class="aif-q-time-badge">
                <span class="dashicons dashicons-clock" style="font-size:14px;width:14px;height:14px;"></span>
                Server: <?php echo current_time('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>

    <!-- KPI -->
    <div class="aif-q-kpi-row">
        <div class="aif-q-kpi">
            <div class="aif-q-kpi-icon kpi-indigo">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div>
                <div class="aif-q-kpi-label">Chờ đăng</div>
                <div class="aif-q-kpi-value"><?php echo $count_pending; ?></div>
            </div>
        </div>
        <div class="aif-q-kpi">
            <div class="aif-q-kpi-icon kpi-amber">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div>
                <div class="aif-q-kpi-label">Đã lên lịch</div>
                <div class="aif-q-kpi-value"><?php echo $count_scheduled; ?></div>
            </div>
        </div>
        <div class="aif-q-kpi">
            <div class="aif-q-kpi-icon kpi-blue">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div>
                <div class="aif-q-kpi-label">Đang xử lý</div>
                <div class="aif-q-kpi-value"><?php echo $count_processing; ?></div>
            </div>
        </div>
        <div class="aif-q-kpi">
            <div class="aif-q-kpi-icon kpi-red">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div>
                <div class="aif-q-kpi-label">Thất bại</div>
                <div class="aif-q-kpi-value"><?php echo $count_failed; ?></div>
            </div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="aif-q-toolbar">
        <div class="aif-q-toolbar-left">
            <button type="button" class="aif-q-btn aif-q-btn-primary" id="btn-run-cron">
                <span class="dashicons dashicons-update"></span> Chạy ngay
            </button>
            <button type="button" class="aif-q-btn aif-q-btn-outline" onclick="location.reload()">
                <span class="dashicons dashicons-image-rotate"></span> Làm mới
            </button>
        </div>
        <div class="aif-q-toolbar-right">
            <div class="aif-q-cron-pill">
                <div>
                    <div class="aif-q-cron-label">Localhost Mode</div>
                    <div class="aif-q-cron-status" id="aif-cron-status">Đang tắt</div>
                </div>
                <label class="aif-switch">
                    <input type="checkbox" id="aif-auto-cron-toggle">
                    <span class="aif-slider"></span>
                </label>
            </div>
            <span style="font-size:12px;color:var(--q-muted);">Tự động chạy mỗi 60s</span>
        </div>
    </div>

    <!-- TABLE -->
    <div class="aif-q-card">
        <table class="aif-q-table">
            <thead>
                <tr>
                    <th style="width:44px;text-align:center;">STT</th>
                    <th>Bài viết</th>
                    <th style="width:120px;text-align:center;">Nền tảng</th>
                    <th style="width:170px;text-align:center;">Fanpage / Nơi đăng</th>
                    <th style="width:130px;text-align:center;">Trạng thái</th>
                    <th style="width:170px;text-align:center;">Lịch đăng</th>
                    <th style="width:90px;text-align:center;">Thêm lúc</th>
                    <th style="width:80px;text-align:center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)):
                    $stt = 1;
                    foreach ($items as $item):
                        // Status class
                        $s = strtolower($item->status);
                        if ($s === 'pending')
                            $st_class = 'qst-pending';
                        elseif ($s === 'scheduled')
                            $st_class = 'qst-scheduled';
                        elseif ($s === 'processing')
                            $st_class = 'qst-processing';
                        elseif ($s === 'done' || $s === 'success')
                            $st_class = 'qst-done';
                        else
                            $st_class = 'qst-failed';

                        // Status label
                        $st_labels = [
                            'pending' => '⏳ Chờ đăng',
                            'scheduled' => '🗓 Đã lên lịch',
                            'processing' => '🔄 Đang xử lý',
                            'done' => '✅ Hoàn thành',
                            'success' => '✅ Hoàn thành',
                        ];
                        $is_failed = !isset($st_labels[$s]);
                        // Extract error reason (status format: "failed: <reason>" or "failed_no_token" etc.)
                        $error_reason = '';
                        if ($is_failed) {
                            if (strpos($item->status, 'failed: ') === 0) {
                                $error_reason = trim(substr($item->status, 8));
                            } elseif ($item->status === 'failed_no_token') {
                                $error_reason = 'Token không hợp lệ hoặc đã hết hạn';
                            } elseif ($item->status === 'failed_no_post') {
                                $error_reason = 'Không tìm thấy bài viết trong DB';
                            } else {
                                $error_reason = $item->status;
                            }
                        }
                        $st_label = isset($st_labels[$s]) ? $st_labels[$s] : '❌ Thất bại';

                        // Platform
                        $plat = strtolower($item->platform ?? 'facebook');
                        $plat_class = ($plat === 'website') ? 'q-plat-website' : 'q-plat-facebook';
                        $plat_icon = ($plat === 'website') ? 'dashicons-admin-site' : 'dashicons-facebook';
                        $plat_label = ($plat === 'website') ? 'Website' : 'Facebook';

                        // Thumbnail
                        $db_post = (new AIF_DB())->get($item->post_id);
                        $thumb_html = '';
                        if ($db_post && !empty($db_post->images)) {
                            $imgs = json_decode($db_post->images, true);
                            if (!empty($imgs[0])) {
                                $thumb_url = AIF_URL . 'upload/' . $imgs[0];
                                $thumb_html = '<img src="' . esc_url($thumb_url) . '" class="aif-q-thumb" loading="lazy">';
                            }
                        }
                        if (!$thumb_html) {
                            $thumb_html = '<div class="aif-q-thumb-empty"><span class="dashicons dashicons-format-image" style="font-size:18px;"></span></div>';
                        }

                        // Content preview
                        $content_preview = ($db_post && !empty($db_post->content))
                            ? wp_trim_words($db_post->content, 10, '…')
                            : '';

                        // Schedule
                        $has_schedule = !empty($item->time_posting) && $item->time_posting !== '0000-00-00 00:00:00';
                        $sched_html = $has_schedule
                            ? '<span class="dashicons dashicons-calendar-alt"></span>' . date('d/m/Y', strtotime($item->time_posting)) . ' <span style="color:var(--q-muted);">' . date('H:i', strtotime($item->time_posting)) . '</span>'
                            : '<span class="dashicons dashicons-controls-play"></span><span class="aif-q-sched-now">Ngay lập tức</span>';

                        // Added time
                        $added = !empty($item->created_at) ? date('d/m H:i', strtotime($item->created_at)) : '—';

                        // Edit link
                        $edit_url = admin_url('admin.php?page=ai-fanpage-post-detail&id=' . $item->post_id);
                        ?>
                        <tr>
                            <td style="text-align:center;font-weight:800;color:var(--q-primary);font-size:13px;">
                                <?php echo $stt++; ?>
                                <div class="aif-q-id-chip" style="margin-top:4px;">Q<?php echo $item->id; ?></div>
                            </td>
                            <td>
                                <div class="aif-q-post-block">
                                    <?php echo $thumb_html; ?>
                                    <div style="min-width:0;">
                                        <a href="<?php echo esc_url($edit_url); ?>" class="aif-q-post-title">
                                            <?php echo esc_html($item->post_title ?: '(Chưa có tiêu đề)'); ?>
                                        </a>
                                        <!-- <div class="aif-q-post-meta">ID #<?php echo $item->post_id; ?></div> -->
                                        <?php if ($content_preview): ?>
                                            <div class="aif-q-post-content"><?php echo esc_html($content_preview); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <span class="aif-q-platform <?php echo $plat_class; ?>">
                                    <span class="dashicons <?php echo $plat_icon; ?>"></span>
                                    <?php echo $plat_label; ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div class="aif-q-target">
                                    <?php echo $plat === 'website' ? '🌐 Website' : esc_html($item->page_name ?: 'Fanpage'); ?>
                                </div>
                                <!-- <?php if ($item->page_id && $plat !== 'website'): ?>
                        <div class="aif-q-target-id">Page ID: <?php echo esc_html($item->page_id); ?></div>
                        <?php endif; ?> -->
                            </td>
                            <td style="text-align:center;">
                                <?php if ($is_failed && $error_reason): ?>
                                    <div class="aif-q-tooltip-wrap">
                                        <span class="aif-q-status <?php echo $st_class; ?>"><?php echo $st_label; ?></span>
                                        <div class="aif-q-tooltip"><?php echo esc_html($error_reason); ?></div>
                                        <div class="aif-q-error-detail"><?php echo esc_html(mb_strimwidth($error_reason, 0, 55, '…')); ?></div>
                                    </div>
                                <?php else: ?>
                                    <span class="aif-q-status <?php echo $st_class; ?>"><?php echo $st_label; ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="aif-q-sched"><?php echo $sched_html; ?></div>
                            </td>
                            <td style="text-align:center;font-size:12px;color:var(--q-muted);"><?php echo $added; ?></td>
                            <td style="text-align:center;">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:5px;">
                                    <?php if ($is_failed): ?>
                                        <button type="button" class="aif-q-btn aif-q-btn-retry aif-q-btn-sm btn-retry-queue"
                                            data-id="<?php echo $item->id; ?>" title="Thử đăng lại">
                                            <span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="aif-q-btn aif-q-btn-danger aif-q-btn-sm btn-delete-queue"
                                        data-id="<?php echo $item->id; ?>" title="Xóa khỏi hàng chờ">
                                        <span class="dashicons dashicons-trash"
                                            style="font-size:14px;width:14px;height:14px;"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="aif-q-empty">
                                <span class="dashicons dashicons-playlist-audio"></span>
                                <h3>Hàng chờ đang trống</h3>
                                <p>Chưa có bài viết nào đang chờ đăng. Khi bạn chuyển bài sang <strong>Done</strong> và chọn
                                    Fanpage, bài viết sẽ xuất hiện tại đây.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /.aif-q-card -->

</div><!-- /.aif-q-wrap -->

<script>
    jQuery(document).ready(function ($) {

        // Retry failed item — reset về pending để cron xử lý lại
        $(document).on('click', '.btn-retry-queue', function () {
            const $btn = $(this);
            const id = $btn.data('id');
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update aif-q-spin" style="font-size:14px;width:14px;height:14px;"></span>');

            $.post(aif_ajax.ajax_url, { action: 'aif_retry_queue_item', nonce: aif_ajax.nonce, id: id }, function (res) {
                if (res.success) {
                    if (window.AIF_Toast) AIF_Toast.show('Đã đặt lại để thử đăng lại!', 'success');
                    setTimeout(() => location.reload(), 900);
                } else {
                    alert('Lỗi: ' + res.data);
                    $btn.prop('disabled', false).html(orig);
                }
            }).fail(function () {
                alert('Lỗi kết nối');
                $btn.prop('disabled', false).html(orig);
            });
        });

        // Xóa item khỏi hàng chờ
        $(document).on('click', '.btn-delete-queue', function () {
            if (!confirm('Xóa bài viết này khỏi hàng chờ?')) return;
            const $btn = $(this);
            const id = $btn.data('id');
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update aif-q-spin" style="font-size:14px;width:14px;height:14px;"></span>');

            $.post(aif_ajax.ajax_url, { action: 'aif_delete_queue_item', nonce: aif_ajax.nonce, id: id }, function (res) {
                if (res.success) {
                    if (window.AIF_Toast) AIF_Toast.show('Đã xóa khỏi hàng chờ', 'success');
                    $btn.closest('tr').fadeOut(400, function () { $(this).remove(); });
                } else {
                    alert('Lỗi: ' + res.data);
                    $btn.prop('disabled', false).html(orig);
                }
            }).fail(function () {
                alert('Lỗi kết nối');
                $btn.prop('disabled', false).html(orig);
            });
        });

        // Chạy cron thủ công
        $('#btn-run-cron').on('click', function () {
            const $btn = $(this);
            const orig = $btn.html();
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update aif-q-spin"></span> Đang chạy...');

            $.post(aif_ajax.ajax_url, { action: 'aif_force_run_cron', nonce: aif_ajax.nonce }, function (res) {
                if (res.success) {
                    if (window.AIF_Toast) AIF_Toast.show('Đã kích hoạt xử lý hàng chờ!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    alert('Lỗi: ' + res.data);
                    $btn.prop('disabled', false).html(orig);
                }
            }).fail(function () {
                alert('Lỗi kết nối');
                $btn.prop('disabled', false).html(orig);
            });
        });

        // Localhost auto-cron
        const $toggle = $('#aif-auto-cron-toggle');
        const $status = $('#aif-cron-status');
        let cronInterval;

        if (localStorage.getItem('aif_auto_cron') === 'true') {
            $toggle.prop('checked', true);
            startAutoCron();
        }

        $toggle.on('change', function () {
            if ($(this).is(':checked')) {
                localStorage.setItem('aif_auto_cron', 'true');
                startAutoCron();
            } else {
                localStorage.setItem('aif_auto_cron', 'false');
                stopAutoCron();
            }
        });

        function startAutoCron() {
            $status.text('Đang hoạt động (60s)').css('color', '#10b981');
            triggerCron();
            cronInterval = setInterval(triggerCron, 60000);
        }

        function stopAutoCron() {
            clearInterval(cronInterval);
            $status.text('Đã dừng').css('color', 'var(--q-muted)');
        }

        function triggerCron() {
            $status.text('Đang đồng bộ...').css('color', '#f59e0b');
            $.post(aif_ajax.ajax_url, { action: 'aif_force_run_cron', nonce: aif_ajax.nonce }, function (res) {
                $status.text(res.success
                    ? 'Cập nhật: ' + new Date().toLocaleTimeString()
                    : 'Lỗi đồng bộ'
                ).css('color', res.success ? '#10b981' : '#ef4444');
            }).fail(function () {
                $status.text('Mất kết nối').css('color', '#ef4444');
            });
        }
    });
</script>