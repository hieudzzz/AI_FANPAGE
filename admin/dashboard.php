<?php
/**
 * AI Fanpage Dashboard - Statistics
 */
global $wpdb;

$table_posts = $wpdb->prefix . 'aif_posts';
$table_pages = $wpdb->prefix . 'aif_facebook_pages';
$table_queue = $wpdb->prefix . 'aif_posting_queue';

// === Date Filter Logic ===
$from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');

$where_date = $wpdb->prepare(" AND DATE(created_at) BETWEEN %s AND %s", $from_date, $to_date);
$where_date_results = $wpdb->prepare(" AND DATE(created_at) BETWEEN %s AND %s", $from_date, $to_date);

// === KPI Counts ===
$total_posts = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_posts WHERE 1=1 $where_date");
$todo_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_posts WHERE status = 'To do' $where_date");
$updated_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_posts WHERE status = 'Content updated' $where_date");
$done_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_posts WHERE status = 'Done' $where_date");
$posted_count = (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}aif_post_results WHERE 1=1 $where_date_results");
$pages_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_pages");
$industry_count = (int) $wpdb->get_var("SELECT COUNT(DISTINCT industry) FROM $table_posts WHERE industry != '' $where_date");
// Đếm từ aif_posting_queue vì lỗi đăng được lưu ở đó, không phải aif_posts
$failed_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_queue WHERE status LIKE 'failed%'");
$queue_pending = (int) $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table_queue WHERE status IN ('pending', 'scheduled', 'processing')");

// === Posts by Industry (Standardized) ===
$table_industries = $wpdb->prefix . 'aif_industries';
$where_date_p = $wpdb->prepare(" AND DATE(p.created_at) BETWEEN %s AND %s", $from_date, $to_date);
$industry_stats = $wpdb->get_results("
    SELECT industry as name, COUNT(*) as cnt 
    FROM $table_posts 
    WHERE industry != '' $where_date
    GROUP BY industry 
    HAVING cnt > 0
    ORDER BY cnt DESC 
    LIMIT 5
");

// === Posts by Platform (Normalized) ===
$platform_stats = $wpdb->get_results("
    SELECT platform, COUNT(*) as cnt 
    FROM {$wpdb->prefix}aif_post_results 
    WHERE 1=1 $where_date_results
    GROUP BY platform
");

// === Recent Posts with Page Info ===
$recent_limit = isset($_GET['recent_limit']) ? intval($_GET['recent_limit']) : 5;
if (!in_array($recent_limit, [5, 10, 20])) $recent_limit = 5;

$recent_posts = $wpdb->get_results($wpdb->prepare("
    SELECT id, title, content, status, updated_at, targets
    FROM $table_posts
    ORDER BY updated_at DESC 
    LIMIT %d
", $recent_limit));

// Fetch Fanpages for lookup
$fanpages_raw = $wpdb->get_results("SELECT id, page_name FROM $table_pages");
$all_fanpages = [];
if ($fanpages_raw) {
    foreach ($fanpages_raw as $fp) {
        $all_fanpages[$fp->id] = $fp;
    }
}

// removed recent feedbacks

// === Activity for selected range ===
$activity_data = $wpdb->get_results($wpdb->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as cnt
    FROM $table_posts
    WHERE DATE(created_at) BETWEEN %s AND %s
    GROUP BY DATE(created_at)
    ORDER BY day ASC
", $from_date, $to_date));

// Build labels and values dynamically based on range
$labels = [];
$values = [];
$activity_map = [];
foreach ($activity_data as $row) {
    $activity_map[$row->day] = (int) $row->cnt;
}

$current = strtotime($from_date);
$end = strtotime($to_date);
while ($current <= $end) {
    $date = date('Y-m-d', $current);
    $labels[] = date('d/m', $current);
    $values[] = isset($activity_map[$date]) ? $activity_map[$date] : 0;
    $current = strtotime('+1 day', $current);
}

// === Prepare Chart Data ===
$industry_labels = [];
$industry_values = [];
foreach ($industry_stats as $stat) {
    $industry_labels[] = !empty($stat->name) ? $stat->name : 'Khác';
    $industry_values[] = (int) $stat->cnt;
}

$platform_labels = [];
$platform_values = [];
foreach ($platform_stats as $stat) {
    $platform_labels[] = !empty($stat->platform) ? ucfirst($stat->platform) : 'Khác';
    $platform_values[] = (int) $stat->cnt;
}

// Status helper
function aif_status_color($status)
{
    switch ($status) {
        case 'To do':
            return '#6B7280';
        case 'Content updated':
            return '#3B82F6';
        case 'Done':
            return '#F59E0B';
        case 'Posted successfully':
            return '#10B981';
        default:
            return '#9CA3AF';
    }
}
// The aif_status_icon function is removed from the new code.
?>

<div class="wrap aif-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 class="wp-heading-inline" style="margin: 0; font-size: 28px;">👋 Xin chào,
                <?php $user = wp_get_current_user();
                echo esc_html($user->display_name); ?>
            </h1>
            <p style="margin: 5px 0 0; color: var(--aif-text-secondary); font-size: 14px;">Chúc bạn một ngày làm việc
                hiệu quả với AI Fanpage.</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <form method="GET" class="aif-date-filter-form"
                style="display: flex; gap: 8px; align-items: center; background: #fff; padding: 8px 16px; border-radius: 12px; border: 1px solid var(--aif-border); box-shadow: var(--aif-shadow);">
                <input type="hidden" name="page" value="ai-fanpage">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 600; color: var(--aif-text-secondary);">Từ</span>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>"
                        style="border: none; background: transparent; font-size: 13px; font-weight: 600; color: var(--aif-text-main);">
                </div>
                <div style="width: 1px; height: 16px; background: var(--aif-border);"></div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; font-weight: 600; color: var(--aif-text-secondary);">Đến</span>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>"
                        style="border: none; background: transparent; font-size: 13px; font-weight: 600; color: var(--aif-text-main);">
                </div>
                <button type="submit"
                    style="background: var(--aif-primary); color: white; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                    <span class="dashicons dashicons-filter"
                        style="font-size: 16px; width: 16px; height: 16px; margin-top: 0;"></span>
                </button>
            </form>
            <div
                style="background: #fff; padding: 10px 18px; border-radius: 12px; border: 1px solid var(--aif-border); font-size: 13px; color: var(--aif-text-secondary); display: flex; align-items: center; gap: 10px; box-shadow: var(--aif-shadow);">
                <span class="dashicons dashicons-calendar-alt"
                    style="font-size: 20px; width: 20px; height: 20px; color: var(--aif-primary);"></span>
                <span><?php echo date('H:i'); ?> • <strong><?php echo date('d/m/Y'); ?></strong></span>
            </div>
            <a href="admin.php?page=ai-fanpage-post-detail&action=new" class="aif-btn-premium-action"
                style="text-decoration: none;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span>Tạo bài mới</span>
            </a>
        </div>
    </div>

    <!-- Quick Actions & Pipeline Info -->
    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 24px; margin-bottom: 30px;">
        <div class="aif-chart-container aif-activity-feed-wrap" style="padding: 0; border: none; overflow: hidden;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding: 16px 20px; background: linear-gradient(135deg, #4F46E5, #818cf8);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-size:18px;">📋</span>
                    <div>
                        <div style="font-weight:600; font-size:14px; color:#fff;">Hoạt động gần đây</div>
                        <div style="font-size:11px; color:rgba(255,255,255,0.75); margin-top:1px;">Lịch sử đăng bài tự động</div>
                    </div>
                </div>
                <button id="aif-refresh-feed" style="background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.25); color:#fff; border-radius:8px; padding:6px 12px; cursor:pointer; font-size:12px; font-weight:600; display:flex; align-items:center; gap:5px; font-family:inherit;">
                    <span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span> Làm mới
                </button>
            </div>
            <div id="aif-activity-list" style="max-height:220px; overflow-y:auto;">
                <div style="text-align:center; padding:30px; color:#94a3b8; font-size:13px;">
                    <span class="aif-feed-spinner"></span> Đang tải...
                </div>
            </div>
        </div>
        <div class="aif-chart-container"
            style="padding: 20px; text-align: center; display: flex; flex-direction: column; justify-content: center;">
            <span
                style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--aif-text-secondary); letter-spacing: 0.5px;">Tổng
                Fanpage</span>
            <div style="font-size: 32px; font-weight: 800; color: var(--aif-primary); margin: 5px 0;">
                <?php echo number_format($pages_count); ?>
            </div>
            <a href="admin.php?page=ai-fanpage-settings"
                style="font-size: 12px; color: var(--aif-primary); text-decoration: none; font-weight: 600;">Quản lý kết
                nối →</a>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="aif-dashboard-grid" style="grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <!-- Card 1: Total -->
        <div class="aif-dashboard-card clickable-kpi" data-type="total" style="align-items: flex-start; padding: 25px;">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(79, 70, 229, 0.1); border: 1px solid rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-media-document"
                    style="color: var(--aif-primary); font-size: 20px; width: 20px; height: 20px;"></span>
            </div>
            <h3 style="margin: 0; text-align: left; font-size: 13px;">Tổng bài viết</h3>
            <div class="aif-kpi-number"
                style="font-size: 32px; margin: 5px 0; text-align: left; -webkit-text-fill-color: var(--aif-text-main); background: none;">
                <?php echo number_format($total_posts); ?>
            </div>
            <span class="description" style="margin: 0; font-size: 12px; padding: 2px 8px;">Chi tiết danh sách</span>
        </div>

        <!-- Card 2: Queue -->
        <div class="aif-dashboard-card clickable-kpi" data-type="queue" style="align-items: flex-start; padding: 25px;">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(20, 184, 166, 0.1); border: 1px solid rgba(20, 184, 166, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-clock"
                    style="color: #14B8A6; font-size: 20px; width: 20px; height: 20px;"></span>
            </div>
            <h3 style="margin: 0; text-align: left; font-size: 13px;">Đã duyệt, chờ đăng bài</h3>
            <div class="aif-kpi-number"
                style="font-size: 32px; margin: 5px 0; text-align: left; -webkit-text-fill-color: #14B8A6; background: none;">
                <?php echo number_format($queue_pending); ?>
            </div>
            <span class="description" style="margin: 0; font-size: 12px; padding: 2px 8px;">Đang chờ đăng bài</span>
        </div>

        <!-- Card 3: Posted -->
        <div class="aif-dashboard-card clickable-kpi" data-type="posted"
            style="align-items: flex-start; padding: 25px;">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-cloud-upload"
                    style="color: #10B981; font-size: 20px; width: 20px; height: 20px;"></span>
            </div>
            <h3 style="margin: 0; text-align: left; font-size: 13px;">Đã đăng thành công</h3>
            <div class="aif-kpi-number"
                style="font-size: 32px; margin: 5px 0; text-align: left; -webkit-text-fill-color: #10B981; background: none;">
                <?php echo number_format($posted_count); ?>
            </div>
            <span class="description" style="margin: 0; font-size: 12px; padding: 2px 8px;">Trên Fanpage</span>
        </div>

        <!-- Card 4: Failed -->
        <div class="aif-dashboard-card clickable-kpi" data-type="failed"
            style="align-items: flex-start; padding: 25px;">
            <div
                style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-warning"
                    style="color: #EF4444; font-size: 20px; width: 20px; height: 20px;"></span>
            </div>
            <h3 style="margin: 0; text-align: left; font-size: 13px;">Lỗi đăng bài</h3>
            <div class="aif-kpi-number"
                style="font-size: 32px; margin: 5px 0; text-align: left; -webkit-text-fill-color: #EF4444; background: none;">
                <?php echo number_format($failed_count); ?>
            </div>
            <?php if ($failed_count > 0): ?>
                <a href="admin.php?page=ai-fanpage-queue" style="font-size: 12px; color: #EF4444; text-decoration: none; font-weight: 700; display:inline-flex; align-items:center; gap:4px;">
                    Xem hàng chờ <span class="dashicons dashicons-arrow-right-alt2" style="font-size:14px;width:14px;height:14px;"></span>
                </a>
            <?php else: ?>
                <span class="description" style="margin: 0; font-size: 12px; padding: 2px 8px;">Cần kiểm tra lại</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Secondary Stats -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="aif-dashboard-card clickable-kpi" data-type="todo"
            style="flex-direction: row; justify-content: space-between; padding: 20px 25px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div
                    style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                    <span class="dashicons dashicons-edit"
                        style="color: #64748b; font-size: 18px; width: 18px; height: 18px;"></span>
                </div>
                <div>
                    <h4
                        style="margin: 0; font-size: 12px; color: var(--aif-text-secondary); text-transform: uppercase;">
                        <?php echo AIF_Status::label('To do'); ?>
                    </h4>
                    <div style="font-size: 20px; font-weight: 700;"><?php echo number_format($todo_count); ?> <small
                            style="font-size: 12px; font-weight: normal; color: var(--aif-text-secondary);">bài</small>
                    </div>
                </div>
            </div>
            <span class="dashicons dashicons-arrow-right-alt2" style="color: #cbd5e1;"></span>
        </div>

        <div class="aif-dashboard-card clickable-kpi" data-type="updated"
            style="flex-direction: row; justify-content: space-between; padding: 20px 25px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div
                    style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center;">
                    <span class="dashicons dashicons-yes-alt"
                        style="color: #3b82f6; font-size: 18px; width: 18px; height: 18px;"></span>
                </div>
                <div>
                    <h4
                        style="margin: 0; font-size: 12px; color: var(--aif-text-secondary); text-transform: uppercase;">
                        Đã cập nhật AI</h4>
                    <div style="font-size: 20px; font-weight: 700;"><?php echo number_format($updated_count); ?> <small
                            style="font-size: 12px; font-weight: normal; color: var(--aif-text-secondary);">bài</small>
                    </div>
                </div>
            </div>
            <span class="dashicons dashicons-arrow-right-alt2" style="color: #cbd5e1;"></span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Activity Chart -->
        <div class="aif-chart-container">
            <?php
            $diff = abs(strtotime($to_date) - strtotime($from_date));
            $days_count = floor($diff / (60 * 60 * 24)) + 1;
            $range_label = ($days_count > 1) ? $days_count . ' ngày qua' : 'trong ngày';
            ?>
            <h3 style="margin: 0 0 20px; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-chart-area" style="color: var(--aif-primary);"></span>
                Bài viết tạo mới (<?php echo $range_label; ?>)
            </h3>
            <canvas id="aifActivityChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Status Pipeline -->
        <div class="aif-chart-container">
            <h3 style="margin: 0 0 20px; font-size: 16px;">Tiến độ Workflow</h3>
            <?php
            $pipeline = [
                ['label' => AIF_Status::label('To do'), 'count' => $todo_count, 'color' => '#6B7280'],
                ['label' => AIF_Status::label('Content updated'), 'count' => $updated_count, 'color' => '#3B82F6'],
                ['label' => AIF_Status::label('Done'), 'count' => $done_count, 'color' => '#F59E0B'],
                ['label' => AIF_Status::label('Posted successfully'), 'count' => $posted_count, 'color' => '#10B981'],
            ];
            foreach ($pipeline as $item):
                $pct = $total_posts > 0 ? ($item['count'] / $total_posts) * 100 : 0;
                ?>
                <div style="margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px;">
                        <span style="font-weight: 600;"><?php echo esc_html($item['label']); ?></span>
                        <span style="color: var(--aif-text-secondary);"><?php echo $item['count']; ?> bài</span>
                    </div>
                    <div style="height: 8px; background: #F3F4F6; border-radius: 4px; overflow: hidden;">
                        <div
                            style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $item['color']; ?>; border-radius: 4px; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <!-- Industry Chart -->
        <div class="aif-chart-container">
            <h3 style="margin: 0 0 20px; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-category" style="color: var(--aif-primary);"></span>
                Bài viết theo Topic
            </h3>
            <div style="position: relative; height: 260px;">
                <canvas id="aifIndustryChart"></canvas>
            </div>
            <div style="text-align: center; margin-top: 15px; font-size: 13px; color: var(--aif-text-secondary);">
                Tổng cộng <strong><?php echo $industry_count; ?> </strong>Topic
            </div>
        </div>

        <!-- Platform Chart -->
        <div class="aif-chart-container">
            <h3 style="margin: 0 0 20px; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-share" style="color: var(--aif-primary);"></span>
                Theo Nền tảng MXH
            </h3>
            <div style="position: relative; height: 260px;">
                <canvas id="aifPlatformChart"></canvas>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
        <!-- Recent Posts Table -->
        <div class="aif-chart-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 16px;">Bài viết mới nhất</h3>
                <div class="aif-pill-group" style="background: #f1f5f9; padding: 3px; border-radius: 8px; display: flex; gap: 2px;">
                    <?php foreach ([5, 10, 20] as $limit): ?>
                        <a href="<?php echo add_query_arg('recent_limit', $limit); ?>#recent-table" 
                           class="aif-pill <?php echo $recent_limit == $limit ? 'active' : ''; ?>"
                           style="padding: 4px 10px; font-size: 11px; text-decoration: none; font-weight: 600; border-radius: 6px; color: <?php echo $recent_limit == $limit ? 'var(--aif-primary)' : '#64748b'; ?>; background: <?php echo $recent_limit == $limit ? '#fff' : 'transparent'; ?>; box-shadow: <?php echo $recent_limit == $limit ? '0 1px 2px rgba(0,0,0,0.05)' : 'none'; ?>;">
                            <?php echo $limit; ?> bài
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <table id="recent-table" class="wp-list-table widefat fixed striped posts" style="border: none; box-shadow: none;">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px; width: 50px; padding-left: 30px;">STT</th>
                        <th style="padding: 12px 10px;">Thông tin bài viết</th>
                        <th style="padding: 12px 10px; width: 150px;">Fanpage</th>
                        <th style="padding: 12px 10px; width: 120px;">Trạng thái</th>
                        <th style="padding: 12px 10px; width: 120px; padding-right: 30px; text-align: right;">Cập nhật
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_posts): ?>
                        <?php
                        $stt = 1;
                        foreach ($recent_posts as $post):
                            $status_label = AIF_Status::label($post->status);
                            $status_class = AIF_Status::badge_class($post->status);
                            $display_title = !empty($post->title) ? $post->title : '(Không có tiêu đề)';
                            ?>
                            <tr>
                                <td
                                    style="padding-left: 30px; color: var(--aif-text-secondary); font-size: 13px; font-weight: 500;">
                                    <?php echo $stt++; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <a href="admin.php?page=ai-fanpage-post-detail&id=<?php echo $post->id; ?>&action=edit"
                                            style="text-decoration: none; color: var(--aif-text-main); font-weight: 700; font-size: 14.5px; transition: color 0.2s;"
                                            onmouseover="this.style.color='var(--aif-primary)'"
                                            onmouseout="this.style.color='var(--aif-text-main)'">
                                            <?php echo esc_html(wp_trim_words($display_title, 15)); ?>
                                        </a>
                                        <div
                                            style="color: var(--aif-text-secondary); font-size: 12.5px; line-height: 1.5; max-width: 500px;">
                                            <?php echo esc_html(wp_trim_words($post->content, 25)); ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        <?php
                                        $targets = json_decode($post->targets, true);
                                        if (!empty($targets) && is_array($targets)):
                                            foreach ($targets as $target):
                                                if ($target['platform'] === 'facebook'):
                                                    $pname = isset($all_fanpages[$target['id']]) ? $all_fanpages[$target['id']]->page_name : 'Trống';
                                                    ?>
                                                    <span class="aif-platform-badge aif-fb-badge">
                                                        <span class="dashicons dashicons-facebook"
                                                            style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
                                                        <?php echo esc_html($pname); ?>
                                                    </span>
                                                <?php elseif ($target['platform'] === 'website'): ?>
                                                    <span class="aif-platform-badge aif-web-badge">
                                                        <span class="dashicons dashicons-admin-site"
                                                            style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span>
                                                        Website
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span style="color: #9ca3af; font-style: italic; font-size: 12px;">Chưa chọn
                                                fanpage</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="aif-status-badge <?php echo $status_class; ?>">
                                        <?php echo esc_html($status_label); ?>
                                    </span>
                                </td>
                                <td
                                    style="padding-right: 30px; text-align: right; color: var(--aif-text-secondary); font-size: 13px;">
                                    <?php echo date('H:i d/m/y', strtotime($post->updated_at)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 50px; color: #9ca3af;">
                                <span class="dashicons dashicons-info"
                                    style="font-size: 30px; width: 30px; height: 30px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;"></span>
                                Chưa có dữ liệu bài viết nào.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <script>
        // Register the datalabels plugin to all charts
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('aifActivityChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Bài viết mới',
                        data: <?php echo json_encode($values); ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.15)',
                        borderColor: '#4F46E5',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        hoverBackgroundColor: 'rgba(79, 70, 229, 0.3)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            top: 25
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#4F46E5',
                            font: { weight: 'bold', size: 12 },
                            formatter: function (value) { return value > 0 ? value : ''; }
                        },
                        tooltip: {
                            backgroundColor: '#1F2937',
                            titleFont: { size: 13 },
                            bodyFont: { size: 12 },
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grace: '10%',
                            ticks: { stepSize: 1, font: { size: 12 }, color: '#9CA3AF' },
                            grid: { color: '#F3F4F6' }
                        },
                        x: {
                            ticks: { font: { size: 12 }, color: '#9CA3AF' },
                            grid: { display: false }
                        }
                    }
                }
            });

            // Chart Ngành hàng (Doughnut)
            const ctxInd = document.getElementById('aifIndustryChart');
            if (ctxInd) {
                new Chart(ctxInd, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($industry_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($industry_values); ?>,
                            backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#14B8A6'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { font: { size: 12 }, padding: 15 } },
                            datalabels: {
                                color: '#fff',
                                font: { weight: 'bold', size: 12 },
                                formatter: function (value) { return value > 0 ? value : ''; }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }

            // Chart Nền tảng (Pie)
            const ctxPlat = document.getElementById('aifPlatformChart');
            if (ctxPlat) {
                new Chart(ctxPlat, {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($platform_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($platform_values); ?>,
                            backgroundColor: ['#1877F2', '#E1306C', '#1DA1F2', '#FF0000', '#000000', '#0A66C2'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { font: { size: 12 }, padding: 15 } },
                            datalabels: {
                                color: '#fff',
                                font: { weight: 'bold', size: 12 },
                                formatter: function (value) { return value > 0 ? value : ''; }
                            }
                        }
                    }
                });
            }
        });
    </script>

    <!-- KPI List Modal -->
    <div id="aif-kpi-modal" class="aif-modal" style="display:none;">
        <div class="aif-modal-content" style="max-width: 900px; width: 90%;">
            <div class="aif-modal-header">
                <h3 id="aif-kpi-modal-title">Danh sách</h3>
                <span class="close-aif-modal" onclick="jQuery('#aif-kpi-modal').hide();">&times;</span>
            </div>
            <div class="aif-modal-body" style="max-height: 500px; overflow-y: auto; padding: 0;">
                <div id="aif-kpi-modal-loading" style="padding: 40px; text-align: center; display: none;">
                    <span class="dashicons dashicons-update spin"
                        style="font-size: 32px; width: 32px; height: 32px;"></span>
                    <p>Đang tải dữ liệu...</p>
                </div>
                <table class="wp-list-table widefat fixed striped" id="aif-kpi-table" style="border: none;">
                    <thead>
                        <tr>
                            <th style="width: 60px; padding-left: 15px;">STT</th>
                            <th>Tiêu đề</th>
                            <th style="width: 120px;">Trạng thái</th>
                            <th style="width: 100px; text-align: right; padding-right: 15px;">Ngày</th>
                        </tr>
                    </thead>
                    <tbody id="aif-kpi-modal-results">
                        <!-- Results will be injected here -->
                    </tbody>
                </table>
            </div>
            <div class="aif-modal-footer">
                <button type="button" class="button button-secondary"
                    onclick="jQuery('#aif-kpi-modal').hide();">Đóng</button>
            </div>
        </div>
    </div>

    <style>
        /* Premium Button Style */
        .aif-btn-premium-action {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white !important;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .aif-btn-premium-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            filter: brightness(1.1);
        }

        .aif-btn-premium-action:active {
            transform: translateY(0);
        }

        .aif-btn-premium-action .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        .aif-btn-premium-action:hover .dashicons {
            transform: rotate(90deg);
        }

        .clickable-kpi:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .clickable-kpi {
            transition: all 0.3s ease;
        }

        /* Modal Styles */
        .aif-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .aif-modal-content {
            background-color: #fff;
            margin: 5% auto;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            animation: aifModalFade 0.3s ease-out;
        }

        @keyframes aifModalFade {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .aif-modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .aif-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .close-aif-modal {
            font-size: 24px;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
            line-height: 1;
        }

        .close-aif-modal:hover {
            color: #374151;
        }

        .aif-modal-footer {
            padding: 12px 20px;
            border-top: 1px solid #f3f4f6;
            text-align: right;
        }

        .spin {
            animation: aifSpin 1s linear infinite;
        }

        @keyframes aifSpin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Activity Feed */
        .aif-activity-feed-wrap {
            border-radius: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        #aif-activity-list::-webkit-scrollbar { width: 4px; }
        #aif-activity-list::-webkit-scrollbar-track { background: #f8fafc; }
        #aif-activity-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

        .aif-activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid #f8fafc;
            transition: background 0.15s ease;
            text-decoration: none;
        }

        .aif-activity-item:hover { background: #fafaff; }
        .aif-activity-item:last-child { border-bottom: none; }

        .aif-activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .aif-activity-icon-success { background: #dcfce7; }
        .aif-activity-icon-fail    { background: #fee2e2; }
        .aif-activity-icon-pending { background: #fef9c3; }
        .aif-activity-icon-queue   { background: #dbeafe; }

        .aif-activity-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 260px;
        }

        .aif-activity-meta {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .aif-activity-time {
            margin-left: auto;
            font-size: 11px;
            color: #cbd5e1;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .aif-feed-spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid #e2e8f0;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: aif-feed-spin 0.65s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }

        @keyframes aif-feed-spin {
            to { transform: rotate(360deg); }
        }
    </style>

<script>
jQuery(document).ready(function($) {
    // Safety check
    if (typeof aif_ajax === 'undefined') {
        $('#aif-activity-list').html('<div style="text-align:center;padding:30px;color:#ef4444;font-size:13px;">Lỗi: không tải được cấu hình JS.</div>');
        return;
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '—';
        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60) return diff + 'g trước';
        if (diff < 3600) return Math.floor(diff/60) + 'p trước';
        if (diff < 86400) return Math.floor(diff/3600) + 'h trước';
        return Math.floor(diff/86400) + 'n trước';
    }

    function statusIcon(status) {
        if (status === 'Posted successfully' || status === 'completed')
            return { icon: '✅', cls: 'aif-activity-icon-success', label: 'Đã đăng' };
        if (status && status.startsWith('failed'))
            return { icon: '❌', cls: 'aif-activity-icon-fail', label: 'Thất bại' };
        if (status === 'processing')
            return { icon: '⚙️', cls: 'aif-activity-icon-queue', label: 'Đang đăng' };
        if (status === 'scheduled')
            return { icon: '🕐', cls: 'aif-activity-icon-pending', label: 'Đã lên lịch' };
        return { icon: '⏳', cls: 'aif-activity-icon-pending', label: 'Chờ đăng' };
    }

    function loadFeed() {
        $('#aif-activity-list').html('<div style="text-align:center;padding:30px;color:#94a3b8;font-size:13px;"><span class="aif-feed-spinner"></span> Đang tải...</div>');

        $.post(ajaxurl, { action: 'aif_get_activity_feed', nonce: aif_ajax.nonce }, function(res) {
            if (!res.success || !res.data.length) {
                $('#aif-activity-list').html('<div style="text-align:center;padding:30px;color:#94a3b8;font-size:13px;">Chưa có hoạt động nào.</div>');
                return;
            }

            let html = '';
            res.data.forEach(function(item) {
                const s = statusIcon(item.status);
                const url = item.link
                    ? item.link
                    : '<?php echo admin_url("admin.php?page=ai-fanpage-post-detail&id="); ?>' + item.post_id;

                html += `<a class="aif-activity-item" href="${url}" target="_blank">
                    <div class="aif-activity-icon ${s.cls}">${s.icon}</div>
                    <div style="min-width:0;flex:1;">
                        <div class="aif-activity-title">${item.title}</div>
                        <div class="aif-activity-meta">
                            <span>${s.label}</span>
                            <span style="color:#e2e8f0;">•</span>
                            <span>${item.page_name}</span>
                            <span style="color:#e2e8f0;">•</span>
                            <span style="text-transform:capitalize;">${item.platform}</span>
                        </div>
                    </div>
                    <div class="aif-activity-time">${timeAgo(item.time)}</div>
                </a>`;
            });

            $('#aif-activity-list').html(html);
        });
    }

    // Load lúc đầu
    loadFeed();

    // Nút làm mới
    $('#aif-refresh-feed').on('click', function() {
        const $icon = $(this).find('.dashicons');
        $icon.css('animation', 'aif-feed-spin 0.65s linear infinite');
        loadFeed();
        setTimeout(() => $icon.css('animation', ''), 1000);
    });

    // Auto refresh mỗi 30s
    setInterval(loadFeed, 30000);

});
</script>