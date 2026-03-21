<?php
if (!defined('ABSPATH'))
    exit;

global $wpdb;
$table_posts = $wpdb->prefix . 'aif_posts';
$table_results = $wpdb->prefix . 'aif_post_results';
$table_pages = $wpdb->prefix . 'aif_facebook_pages';

// --- Handle Filters ---
$from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');

$orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'created_at';
$order = isset($_GET['order']) ? sanitize_key($_GET['order']) : 'DESC';
$platform_filter = isset($_GET['platform']) ? sanitize_key($_GET['platform']) : 'all';
$page_filter = isset($_GET['fanpage_id']) ? intval($_GET['fanpage_id']) : 0;

// Load all active Facebook pages for the filter dropdown
$all_pages = $wpdb->get_results("SELECT id, page_name FROM $table_pages ORDER BY page_name ASC");

$allowed_sort = ['likes_count', 'comments_count', 'shares_count', 'created_at', 'id'];
if (!in_array($orderby, $allowed_sort))
    $orderby = 'created_at';

// Dynamic where based on platform, fanpage and date
$where_p = "p.status = 'Posted successfully'";

// Platform filter logic
if ($platform_filter === 'facebook') {
    $where_p .= " AND EXISTS (SELECT 1 FROM $table_results r2 WHERE r2.post_id = p.id AND r2.platform = 'facebook')";
} elseif ($platform_filter === 'website') {
    $where_p .= " AND EXISTS (SELECT 1 FROM $table_results r2 WHERE r2.post_id = p.id AND r2.platform = 'website')";
}

// Fanpage filter logic
if ($page_filter > 0) {
    $where_p .= $wpdb->prepare(
        " AND EXISTS (SELECT 1 FROM $table_results r4 WHERE r4.post_id = p.id AND r4.platform = 'facebook' AND r4.target_id = %d)",
        $page_filter
    );
}

$where_p .= $wpdb->prepare(" AND (DATE(p.updated_at) BETWEEN %s AND %s OR EXISTS (SELECT 1 FROM $table_results r3 WHERE r3.post_id = p.id AND DATE(r3.created_at) BETWEEN %s AND %s))", $from_date, $to_date, $from_date, $to_date);

// --- Handle Pagination for Table ---
$per_page = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($paged - 1) * $per_page;

// Unified query using aif_posts as the master list
$unified_sql = "SELECT 
                p.id as post_db_id,
                p.id as post_id,
                p.title,
                p.status as post_status,
                p.wp_author_id,
                p.images,
                p.image_website,
                MAX(p.updated_at) as created_at,
                MAX(CASE WHEN r.platform = 'facebook' THEN r.id ELSE NULL END) as sync_result_id,
                MAX(CASE WHEN r.platform = 'facebook' THEN r.link ELSE NULL END) as fb_link,
                MAX(CASE WHEN r.platform = 'website' THEN r.link ELSE NULL END) as web_link,
                SUM(COALESCE(r.likes_count, 0)) as likes_count, 
                SUM(COALESCE(r.comments_count, 0)) as comments_count, 
                SUM(COALESCE(r.shares_count, 0)) as shares_count, 
                SUM(COALESCE(r.views_count, 0)) as views_count, 
                SUM(COALESCE(r.reach_count, 0)) as reach_count, 
                MAX(r.metrics_updated_at) as metrics_updated_at,
                " . ($page_filter > 0
                    ? $wpdb->prepare("MAX(CASE WHEN r.target_id = %d THEN f.page_name ELSE NULL END) as fanpage_name", $page_filter)
                    : "MAX(CASE WHEN r.platform = 'facebook' THEN f.page_name ELSE NULL END) as fanpage_name"
                ) . "
             FROM $table_posts p
             LEFT JOIN $table_results r ON p.id = r.post_id
             LEFT JOIN $table_pages f ON r.platform = 'facebook' AND f.id = r.target_id
             WHERE $where_p
             GROUP BY p.id
";

$total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM ($unified_sql) as total");
$total_pages = ceil($total_rows / $per_page);

// --- Query Data for Charts (Only from actual results table for metrics) ---
$chart_where_extra = $page_filter > 0 ? $wpdb->prepare(" AND r.target_id = %d", $page_filter) : '';
$chart_sql = $wpdb->prepare("SELECT r.*, p.title 
               FROM $table_results r 
               LEFT JOIN $table_posts p ON r.post_id = p.id
               WHERE r.platform = 'facebook' AND DATE(r.created_at) BETWEEN %s AND %s $chart_where_extra
               ORDER BY r.created_at DESC LIMIT 1000", $from_date, $to_date);
$chart_results = $wpdb->get_results($chart_sql);

// --- Query Data for Table (Paginated) ---
$sql = "SELECT * FROM ($unified_sql) as combined
        ORDER BY $orderby $order
        LIMIT $per_page OFFSET $offset";
$results = $wpdb->get_results($sql);

// --- Fetch all FB links per post (for multi-page badge display) ---
$post_ids_on_page = array_map(function($r) { return intval($r->post_id); }, $results);
$fb_links_by_post = []; // [ post_id => [ ['page_name'=>..., 'link'=>...], ... ] ]
if (!empty($post_ids_on_page)) {
    $ids_in = implode(',', $post_ids_on_page);
    $page_filter_extra = $page_filter > 0 ? $wpdb->prepare(" AND r.target_id = %d", $page_filter) : '';
    $fb_links_rows = $wpdb->get_results(
        "SELECT r.post_id, r.link, r.target_id, f.page_name
         FROM $table_results r
         LEFT JOIN $table_pages f ON f.id = r.target_id
         WHERE r.platform = 'facebook' AND r.post_id IN ($ids_in) $page_filter_extra
         ORDER BY r.id ASC"
    );
    foreach ($fb_links_rows as $fl) {
        $fb_links_by_post[$fl->post_id][] = [
            'page_name' => $fl->page_name ?: 'Facebook',
            'link'      => $fl->link,
        ];
    }
}

// --- Calculate Summary ---
$summary_sql = "SELECT 
    COUNT(DISTINCT p.id) as total_posts,
    SUM(r.likes_count) as total_likes,
    SUM(r.shares_count) as total_shares,
    SUM(r.comments_count) as total_comments
FROM $table_posts p
LEFT JOIN $table_results r ON p.id = r.post_id
WHERE $where_p";
$summary = $wpdb->get_row($summary_sql);

$now = current_time('timestamp');

// --- PHP Data Pre-processing for Charts ---
$daily_stats = [];
foreach ($chart_results as $r) {
    $day = date('d/m', strtotime($r->created_at));
    if (!isset($daily_stats[$day])) {
        $daily_stats[$day] = ['likes' => 0, 'shares' => 0, 'comments' => 0];
    }
    $daily_stats[$day]['likes'] += $r->likes_count;
    $daily_stats[$day]['shares'] += $r->shares_count;
    $daily_stats[$day]['comments'] += $r->comments_count;
}
$chart_labels = array_reverse(array_keys($daily_stats));
$chart_likes = [];
$chart_shares = [];
$chart_comments = [];
foreach ($chart_labels as $label) {
    $chart_likes[] = $daily_stats[$label]['likes'];
    $chart_shares[] = $daily_stats[$label]['shares'];
    $chart_comments[] = $daily_stats[$label]['comments'];
}

// Top 5 Posts by Engagement (From chart/total results)
$top_posts = $chart_results;
usort($top_posts, function ($a, $b) {
    return ($b->likes_count + $b->shares_count + $b->comments_count) - ($a->likes_count + $a->shares_count + $a->comments_count);
});
$top_posts = array_slice($top_posts, 0, 5);
$top_labels = [];
$top_values = [];
foreach ($top_posts as $tp) {
    $top_labels[] = mb_strimwidth($tp->title ?: '#' . $tp->post_id, 0, 20, '...');
    $top_values[] = $tp->likes_count + $tp->shares_count + $tp->comments_count;
}
?>

<div class="wrap aif-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div>
            <h1 class="wp-heading-inline" style="margin:0;">AIF Analytics & Insights</h1>
            <p style="color: #64748b; margin: 5px 0 0;">Theo dõi hiệu quả nội dung trên các nền tảng mạng xã hội.</p>
        </div>
        <div class="aif-filter-container">
            <form method="GET" class="aif-date-filter-form" style="display: flex; gap: 8px; align-items: center; background: transparent; padding: 0;">
                <input type="hidden" name="page" value="ai-fanpage-analytics">
                <input type="hidden" name="platform" value="<?php echo esc_attr($platform_filter); ?>">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 800; color: #1a1c1e; text-transform: uppercase;">Từ</span>
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>" style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 8px; font-size: 12px; font-weight: 600;">
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 11px; font-weight: 800; color: #1a1c1e; text-transform: uppercase;">Đến</span>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>" style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 4px 8px; font-size: 12px; font-weight: 600;">
                </div>
                <button type="submit" style="background: var(--aif-primary); color: white; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer;">
                    <span class="dashicons dashicons-filter" style="font-size: 16px; width: 16px; height: 16px; margin-top: 0;"></span>
                </button>
            </form>
            <div class="aif-filter-group" style="margin-left: 15px; border-left: 1px solid var(--aif-border-light); padding-left: 15px;">
                <span class="aif-filter-label">Nền tảng:</span>
                <div class="aif-pill-group">
                    <a href="<?php echo add_query_arg(['platform' => 'all', 'fanpage_id' => $page_filter]); ?>"
                        class="aif-pill <?php echo $platform_filter == 'all' ? 'active' : ''; ?>">Tất cả</a>
                    <a href="<?php echo add_query_arg(['platform' => 'facebook', 'fanpage_id' => $page_filter]); ?>"
                        class="aif-pill <?php echo $platform_filter == 'facebook' ? 'active' : ''; ?>">Facebook</a>
                    <a href="<?php echo add_query_arg(['platform' => 'website', 'fanpage_id' => 0]); ?>"
                        class="aif-pill <?php echo $platform_filter == 'website' ? 'active' : ''; ?>">Website</a>
                </div>
            </div>

            <?php if (!empty($all_pages) && $platform_filter !== 'website'): ?>
            <div class="aif-filter-group" style="margin-left: 15px; border-left: 1px solid var(--aif-border-light); padding-left: 15px;">
                <span class="aif-filter-label">Fanpage:</span>
                <div class="aif-pill-group">
                    <a href="<?php echo add_query_arg(['fanpage_id' => 0, 'platform' => $platform_filter === 'website' ? 'all' : $platform_filter]); ?>"
                        class="aif-pill <?php echo $page_filter === 0 ? 'active' : ''; ?>">Tất cả</a>
                    <?php foreach ($all_pages as $fp): ?>
                        <a href="<?php echo add_query_arg(['fanpage_id' => $fp->id, 'platform' => $platform_filter === 'website' ? 'facebook' : $platform_filter]); ?>"
                            class="aif-pill <?php echo $page_filter === (int)$fp->id ? 'active' : ''; ?>">
                            <?php echo esc_html($fp->page_name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        :root {
            --aif-primary: #3b82f6;
            --aif-success: #10b981;
            --aif-warning: #f59e0b;
            --aif-danger: #ef4444;
            --aif-bg-subtle: #f8fafc;
            --aif-border-light: #e2e8f0;
            --aif-text-main: #1e293b;
            --aif-text-muted: #64748b;
        }

        .aif-container {
            color: var(--aif-text-main);
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        /* KPI Cards */
        .aif-analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .aif-stat-card {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--aif-border-light);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s ease;
        }

        .aif-stat-card:hover {
            transform: translateY(-2px);
        }

        .aif-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .aif-stat-icon span {
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .aif-stat-content .label {
            font-size: 13px;
            color: var(--aif-text-muted);
            font-weight: 500;
        }

        .aif-stat-content .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--aif-text-main);
            margin-top: 4px;
        }

        /* Filter Pills Modernized */
        .aif-filter-container {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #fff;
            padding: 8px 20px;
            border-radius: 14px;
            border: 1px solid var(--aif-border-light);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .aif-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aif-filter-group:not(:last-child) {
            border-right: 1px solid var(--aif-border-light);
            padding-right: 20px;
        }

        .aif-filter-label {
            font-size: 11px;
            font-weight: 800;
            color: #1a1c1e;
            /* text-transform: uppercase; */
            letter-spacing: 0.5px;
        }

        .aif-pill-group {
            display: flex;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 10px;
            gap: 2px;
        }

        .aif-pill {
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            color: var(--aif-text-muted);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .aif-pill:hover {
            color: var(--aif-text-main);
            background: rgba(255, 255, 255, 0.5);
        }

        .aif-pill.active {
            background: #fff;
            color: var(--aif-primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Table */
        .aif-premium-table {
            background: #fff;
            border-radius: 16px;
            border: 1px solid var(--aif-border-light);
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        }

        .aif-premium-table table {
            border: none !important;
            min-width: 900px;
        }

        /* Wrapper cuộn ngang cho bảng chi tiết */
        .aif-table-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Header filter responsive */
        @media (max-width: 1200px) {
            .aif-filter-container {
                flex-wrap: wrap;
                gap: 10px;
            }
        }

        /* Chart grid responsive */
        @media (max-width: 900px) {
            .aif-charts-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .aif-premium-table thead th {
            background: #f8fafc;
            padding: 15px 20px;
            font-weight: 600;
            border-bottom: 1px solid var(--aif-border-light);
            white-space: nowrap;
        }

        .aif-premium-table tbody td {
            vertical-align: middle;
        }

        @keyframes aif-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin {
            animation: aif-spin 1s linear infinite;
            display: inline-block;
        }
    </style>


    <!-- Summary Cards -->
    <div class="aif-analytics-grid">
        <div class="aif-stat-card">
            <div class="aif-stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--aif-primary);">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="aif-stat-content">
                <div class="label">Tổng bài đã đăng</div>
                <div class="value"><?php echo number_format($summary->total_posts ?: 0); ?></div>
            </div>
        </div>
        <div class="aif-stat-card">
            <div class="aif-stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--aif-warning);">
                <span class="dashicons dashicons-thumbs-up"></span>
            </div>
            <div class="aif-stat-content">
                <div class="label">Tổng Likes</div>
                <div class="value"><?php echo number_format($summary->total_likes ?: 0); ?></div>
            </div>
        </div>
        <div class="aif-stat-card">
            <div class="aif-stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--aif-danger);">
                <span class="dashicons dashicons-share"></span>
            </div>
            <div class="aif-stat-content">
                <div class="label">Tổng Shares</div>
                <div class="value"><?php echo number_format($summary->total_shares ?: 0); ?></div>
            </div>
        </div>
        <div class="aif-stat-card">
            <div class="aif-stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--aif-success);">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="aif-stat-content">
                <div class="label">Tổng Comments</div>
                <div class="value"><?php echo number_format($summary->total_comments ?: 0); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="aif-charts-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div class="aif-premium-table" style="padding: 20px;">
            <h3 style="margin-top:0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-chart-line" style="color: var(--aif-primary);"></span>
                Xu hướng tương tác
            </h3>
            <canvas id="engagementChart" style="max-height: 300px;"></canvas>
        </div>
        <div class="aif-premium-table" style="padding: 20px;">
            <h3 style="margin-top:0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-awards" style="color: var(--aif-warning);"></span>
                Top bài đăng
            </h3>
            <canvas id="topPostsChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Results Table Section -->
    <div class="aif-premium-table">
        <div
            style="padding: 20px; border-bottom: 1px solid var(--aif-border-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0; font-size: 16px;">Chi tiết bài viết</h3>
            <span style="font-size: 13px; color: var(--aif-text-muted);">
                Hiển thị <?php echo count($results); ?> / <?php echo $total_rows; ?> kết quả
            </span>
        </div>
        <div class="aif-table-scroll-wrap">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px; padding-left: 20px; text-align: center;">STT</th>
                    <th style="width: 250px;"><a
                            href="<?php echo add_query_arg(['orderby' => 'id', 'order' => ($orderby == 'id' && $order == 'DESC' ? 'ASC' : 'DESC')]); ?>">Bài
                            viết</a></th>
                    <th style="width: 70px; text-align: center;">Media</th>
                    <th style="width: 120px;">Tác giả</th>
                    <th style="width: 100px; text-align: center;"><a
                            href="<?php echo add_query_arg(['orderby' => 'likes_count', 'order' => ($orderby == 'likes_count' && $order == 'DESC' ? 'ASC' : 'DESC')]); ?>">Likes</a>
                    </th>
                    <th style="width: 100px; text-align: center;"><a
                            href="<?php echo add_query_arg(['orderby' => 'shares_count', 'order' => ($orderby == 'shares_count' && $order == 'DESC' ? 'ASC' : 'DESC')]); ?>">Shares</a>
                    </th>
                    <th style="width: 100px; text-align: center;"><a
                            href="<?php echo add_query_arg(['orderby' => 'comments_count', 'order' => ($orderby == 'comments_count' && $order == 'DESC' ? 'ASC' : 'DESC')]); ?>">Comments</a>
                    </th>
                    <th style="width: 150px; text-align: center;">Tương tác (%)</th>
                    <th style="width: 120px;"><a
                            href="<?php echo add_query_arg(['orderby' => 'created_at', 'order' => ($orderby == 'created_at' && $order == 'DESC' ? 'ASC' : 'DESC')]); ?>">Ngày
                            đăng</a></th>
                    <th style="width: 80px; text-align: right; padding-right: 20px;">Sync</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)): ?>
                    <?php $stt = 1; ?>
                    <?php foreach ($results as $row):
                        $edit_url = admin_url('admin.php?page=ai-fanpage-post-detail&id=' . $row->post_id);
                        $total_eng = $row->likes_count + $row->shares_count + $row->comments_count;
                        $total_all_eng = ($summary->total_likes + $summary->total_shares + $summary->total_comments) ?: 1;
                        $eng_rate = ($total_eng / $total_all_eng) * 100;

                        $last_sync = strtotime($row->metrics_updated_at);
                        $is_old = ($last_sync && ($now - $last_sync) > 86400);
                        $sync_dot = '<span class="dashicons dashicons-yes-alt" style="color:var(--aif-success); font-size: 18px;"></span>';

                        if (!$last_sync)
                            $sync_dot = '<span class="dashicons dashicons-warning" style="color:var(--aif-text-muted); font-size: 18px;"></span>';
                        elseif ($is_old)
                            $sync_dot = '<span class="dashicons dashicons-warning" style="color:var(--aif-danger); font-size: 18px;"></span>';
                        ?>
                        <tr class="row-result <?php echo empty($row->sync_result_id) ? 'missing-result' : ''; ?>"
                            data-result-id="<?php echo $row->sync_result_id; ?>">
                            <td style="padding-left: 20px; color: var(--aif-text-muted); text-align: center;">
                                <?php echo ($offset + $stt++); ?>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    <a href="<?php echo esc_url($edit_url); ?>"
                                        style="text-decoration:none; color: var(--aif-text-main);"><?php echo esc_html($row->title ?: '(Không tiêu đề)'); ?></a>
                                </div>
                                <div style="margin-top: 6px; display: flex; gap: 8px; flex-wrap: wrap;">
                                    <?php
                                    // Render một badge cho từng Fanpage đã đăng
                                    $fb_entries = $fb_links_by_post[$row->post_id] ?? [];
                                    foreach ($fb_entries as $fb_entry):
                                    ?>
                                        <a href="<?php echo esc_url($fb_entry['link']); ?>" target="_blank"
                                            class="aif-platform-badge aif-fb-badge">
                                            <span class="dashicons dashicons-facebook"
                                                style="font-size: 14px; width: 14px; height: 14px;"></span>
                                            <?php echo esc_html($fb_entry['page_name']); ?>
                                        </a>
                                    <?php endforeach; ?>

                                    <?php if (!empty($row->web_link)): ?>
                                        <a href="<?php echo esc_url($row->web_link); ?>" target="_blank"
                                            class="aif-platform-badge aif-web-badge">
                                            <span class="dashicons dashicons-admin-site"
                                                style="font-size: 14px; width: 14px; height: 14px;"></span> WEBSITE
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <?php
                                $img_url = '';
                                $img_list = json_decode($row->images ?? '[]', true);
                                if (!empty($img_list) && is_array($img_list)) {
                                    $first = $img_list[0];
                                    if (strpos($first, 'wp-att-') === 0) {
                                        $att_id  = intval(substr($first, 7));
                                        $img_url = wp_get_attachment_image_url($att_id, 'thumbnail') ?: wp_get_attachment_url($att_id);
                                    } else {
                                        $img_url = AIF_URL . 'upload/' . $first;
                                    }
                                } elseif (!empty($row->image_website)) {
                                    $iw = $row->image_website;
                                    if (strpos($iw, 'wp-att-') === 0) {
                                        $att_id  = intval(substr($iw, 7));
                                        $img_url = wp_get_attachment_image_url($att_id, 'thumbnail') ?: wp_get_attachment_url($att_id);
                                    } else {
                                        $img_url = AIF_URL . 'upload/' . $iw;
                                    }
                                }
                                if ($img_url):
                                ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">
                                        <img src="<?php echo esc_url($img_url); ?>"
                                             loading="lazy"
                                             style="width:50px; height:50px; border-radius:10px; object-fit:cover; border:1px solid var(--aif-border-light); display:block; margin:0 auto;">
                                    </a>
                                <?php else: ?>
                                    <div onclick="location.href='<?php echo esc_url($edit_url); ?>'"
                                         style="width:50px; height:50px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; margin:0 auto; cursor:pointer;">
                                        <span class="dashicons dashicons-images-alt2" style="font-size:20px; width:20px; height:20px;"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 13px;">
                                <?php
                                $author = $row->wp_author_id ? get_userdata($row->wp_author_id) : null;
                                echo esc_html($author ? $author->display_name : '---');
                                ?>
                            </td>
                            <td class="col-likes" style="text-align: center; font-weight: 600;">
                                <?php echo number_format($row->likes_count); ?>
                            </td>
                            <td class="col-shares" style="text-align: center; font-weight: 600;">
                                <?php echo number_format($row->shares_count); ?>
                            </td>
                            <td class="col-comments" style="text-align: center; font-weight: 600;">
                                <?php echo number_format($row->comments_count); ?>
                            </td>
                            <td style="padding: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="flex:1; background: #f1f5f9; height: 6px; border-radius: 3px; overflow: hidden;">
                                        <div
                                            style="width: <?php echo min(100, $eng_rate * 5); ?>%; background: var(--aif-primary); height: 100%;">
                                        </div>
                                    </div>
                                    <span
                                        style="font-size: 11px; font-weight: 700; width: 35px;"><?php echo round($eng_rate, 1); ?>%</span>
                                </div>
                            </td>
                            <td style="font-size: 12px; color: var(--aif-text-muted);">
                                <?php echo date('d/m/Y', strtotime($row->created_at)); ?><br>
                                <small><?php echo date('H:i', strtotime($row->created_at)); ?></small>
                            </td>
                            <td class="col-sync-status" style="text-align: right; padding-right: 20px;">
                                <?php
                                if (empty($row->sync_result_id)) {
                                    echo '<span class="dashicons dashicons-no" style="color:var(--aif-danger); font-size: 18px;" title="Thiếu dữ liệu (Chưa có ID bài đăng)"></span>';
                                } else {
                                    echo '<div class="sync-indicator">' . $sync_dot . '</div>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px; color: var(--aif-text-muted);">
                            <span class="dashicons dashicons-info"
                                style="font-size: 32px; width: 32px; height: 32px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;"></span>
                            Không có dữ liệu bài viết đã đăng trong khoảng thời gian này.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div><!-- end .aif-table-scroll-wrap -->

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom"
                style="padding: 15px 20px; border-top: 1px solid var(--aif-border-light); display: flex; justify-content: flex-end;">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Trước'),
                        'next_text' => __('Sau &raquo;'),
                        'total' => $total_pages,
                        'current' => $paged,
                        'type' => 'plain'
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    jQuery(document).ready(function ($) {
        // --- Charts Implementation ---
        const ctxEng = document.getElementById('engagementChart').getContext('2d');
        new Chart(ctxEng, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Likes',
                        data: <?php echo json_encode($chart_likes); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Comments',
                        data: <?php echo json_encode($chart_comments); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6 } } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { display: false },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });

        const ctxTop = document.getElementById('topPostsChart').getContext('2d');
        new Chart(ctxTop, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($top_labels); ?>,
                datasets: [{
                    label: 'Tổng tương tác',
                    data: <?php echo json_encode($top_values); ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { 
                        beginAtZero: true, 
                        grid: { display: false },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    y: { grid: { display: false } }
                }
            }
        });

        // Auto sync visible rows on load
        $('.row-result').each(function () {
            var $row = $(this);
            var resultId = $row.data('result-id');
            if (!resultId) return;

            var $syncCol = $row.find('.col-sync-status .sync-indicator');
            $syncCol.html('<span class="dashicons dashicons-update spin" style="color:var(--aif-primary); font-size: 18px;"></span>');

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aif_fetch_metrics',
                    nonce: aif_ajax.nonce,
                    id: resultId
                },
                success: function (response) {
                    if (response.success) {
                        var data = response.data;
                        $row.find('.col-likes').text(data.likes.toLocaleString());
                        $row.find('.col-shares').text(data.shares.toLocaleString());
                        $row.find('.col-comments').text(data.comments.toLocaleString());
                        $syncCol.html('<span class="dashicons dashicons-yes-alt" style="color:var(--aif-success); font-size: 18px;"></span>');
                    } else {
                        $syncCol.html('<span class="dashicons dashicons-warning" style="color:var(--aif-danger); font-size: 18px;" title="Lỗi: ' + response.data + '"></span>');
                    }
                },
                error: function() {
                    $syncCol.html('<span class="dashicons dashicons-warning" style="color:var(--aif-danger); font-size: 18px;"></span>');
                }
            });
        });
    });
</script>