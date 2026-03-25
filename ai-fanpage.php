<?php

/**
 * Plugin Name: AI Fanpage
 * Description: Automate social media content creation and publishing with AI.
 * Version: 1.0.0
 * Author: AI Fanpage Team
 * Text Domain: ai-fanpage
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Constants
define('AIF_PATH', plugin_dir_path(__FILE__));
define('AIF_URL', plugin_dir_url(__FILE__));

// Load Settings class sớm nhất (trước activation hook và main class)
// để AIF_Activator::activate() có thể gọi AIF_Settings::create_table()
require_once AIF_PATH . 'includes/class-settings.php';

// Activation Hook
require_once AIF_PATH . 'includes/class-activator.php';
register_activation_hook(__FILE__, ['AIF_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['AIF_Activator', 'deactivate']);


/**
 * Main Plugin Class
 */
class AI_Fanpage
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_hooks();
        $this->includes();
    }

    private function init_hooks()
    {
        add_action('admin_menu',             [$this, 'register_admin_menu']);
        add_action('admin_head',             [$this, 'highlight_parent_menu']);
        add_action('admin_head',             [$this, 'render_menu_badge_styles']);
        add_action('admin_footer',           [$this, 'render_menu_badge_script']);
        add_action('admin_enqueue_scripts',  [$this, 'enqueue_admin_assets']);

        // Start session for admin messages
        add_action('init', function () {
            if (!session_id() && !headers_sent()) {
                session_start();
            }
        });

        // Ensure messages table exists
        add_action('init', array($this, 'maybe_create_messages_table'));
        // Removed CPT init as we are moving to DB table
        // add_action( 'init', [ $this, 'register_post_type' ] ); 

        // AJAX Handlers
        add_action('wp_ajax_aif_import_gsheet', [$this, 'handle_import_gsheet']);
        add_action('wp_ajax_aif_generate_content', [$this, 'handle_generate_content']);
        add_action('wp_ajax_aif_generate_variations', [$this, 'handle_generate_variations']);
        add_action('wp_ajax_aif_save_custom_tone', [$this, 'handle_save_custom_tone']);
        add_action('wp_ajax_aif_delete_custom_tone', [$this, 'handle_delete_custom_tone']);
        add_action('wp_ajax_aif_suggest_time', [$this, 'handle_suggest_time']);
        add_action('wp_ajax_aif_smart_check', [$this, 'handle_smart_check']);
        add_action('wp_ajax_aif_bulk_process_item', [$this, 'handle_bulk_process_item']);
        add_action('wp_ajax_aif_get_post_details', [$this, 'handle_get_post_details']);
        add_action('wp_ajax_aif_save_fanpage', [$this, 'handle_save_fanpage']);
        add_action('wp_ajax_aif_edit_fanpage', [$this, 'handle_edit_fanpage']);
        add_action('wp_ajax_aif_delete_fanpage', [$this, 'handle_delete_fanpage']);
        add_action('wp_ajax_aif_add_to_queue', [$this, 'handle_add_to_queue']);
        add_action('wp_ajax_aif_force_run_cron', [$this, 'handle_force_run_cron']);
        add_action('wp_ajax_aif_delete_queue_item', [$this, 'handle_delete_queue_item']);
        add_action('wp_ajax_aif_retry_queue_item', [$this, 'handle_retry_queue_item']);
        add_action('wp_ajax_aif_remove_post_from_queue', [$this, 'handle_remove_post_from_queue']);
        add_action('wp_ajax_aif_revise_content', [$this, 'handle_revise_content']);
        add_action('wp_ajax_aif_inline_edit_post', [$this, 'handle_inline_edit_post']);
        add_action('wp_ajax_aif_apply_feedback', [$this, 'handle_apply_feedback']);
        add_action('wp_ajax_aif_get_taxonomies', [$this, 'handle_get_taxonomies']);
        add_action('wp_ajax_aif_fetch_metrics', [$this, 'handle_fetch_metrics']);
        add_action('wp_ajax_aif_fetch_all_metrics', [$this, 'handle_fetch_all_metrics']);
        add_action('wp_ajax_aif_upload_media_inline', [$this, 'handle_upload_media_inline']);
        add_action('wp_ajax_aif_media_get_folders',    [$this, 'handle_media_get_folders']);
        add_action('wp_ajax_aif_media_create_folder',  [$this, 'handle_media_create_folder']);
        add_action('wp_ajax_aif_media_rename_folder',  [$this, 'handle_media_rename_folder']);
        add_action('wp_ajax_aif_media_delete_folder',  [$this, 'handle_media_delete_folder']);
        add_action('wp_ajax_aif_media_upload',         [$this, 'handle_media_upload']);
        add_action('wp_ajax_aif_media_delete_file',    [$this, 'handle_media_delete_file']);
        add_action('wp_ajax_aif_media_set_folders',    [$this, 'handle_media_set_folders']);
        add_action('wp_ajax_aif_get_dashboard_list', [$this, 'handle_get_dashboard_list']);
        add_action('wp_ajax_aif_update_fanpage_token', [$this, 'handle_update_fanpage_token']);

        // N8N Management AJAX
        add_action('wp_ajax_aif_get_activity_feed', [$this, 'handle_get_activity_feed']);
        add_action('wp_ajax_aif_n8n_get_chats', [$this, 'handle_n8n_get_chats']);
        add_action('wp_ajax_aif_n8n_delete_chat', [$this, 'handle_n8n_delete_chat']);
        add_action('wp_ajax_aif_n8n_get_products',    [$this, 'handle_n8n_get_products']);
        add_action('wp_ajax_aif_n8n_toggle_product',  [$this, 'handle_n8n_toggle_product']);
        add_action('wp_ajax_aif_n8n_save_product', [$this, 'handle_n8n_save_product']);
        add_action('wp_ajax_aif_n8n_delete_product', [$this, 'handle_n8n_delete_product']);
        add_action('wp_ajax_aif_n8n_get_leads', [$this, 'handle_n8n_get_leads']);
        add_action('wp_ajax_aif_n8n_export_leads', [$this, 'handle_n8n_export_leads']);
        add_action('wp_ajax_aif_n8n_get_settings', [$this, 'handle_n8n_get_settings']);
        add_action('wp_ajax_aif_n8n_save_settings',    [$this, 'handle_n8n_save_settings']);
        add_action('wp_ajax_aif_n8n_get_policies',     [$this, 'handle_n8n_get_policies']);
        add_action('wp_ajax_aif_n8n_save_policy',      [$this, 'handle_n8n_save_policy']);
        add_action('wp_ajax_aif_n8n_delete_policy',    [$this, 'handle_n8n_delete_policy']);
        add_action('wp_ajax_aif_n8n_toggle_policy',    [$this, 'handle_n8n_toggle_policy']);
        add_action('wp_ajax_aif_n8n_reorder_policies', [$this, 'handle_n8n_reorder_policies']);
        add_action('wp_ajax_aif_flush_ai_cache',       [$this, 'handle_flush_ai_cache']);
        add_action('wp_ajax_aif_n8n_export_products', [$this, 'handle_n8n_export_products']);
        add_action('wp_ajax_aif_n8n_import_products', [$this, 'handle_n8n_import_products']);
        add_action('wp_ajax_aif_n8n_check_updates', [$this, 'handle_n8n_check_updates']);
        add_action('wp_ajax_aif_n8n_mark_chat_viewed', [$this, 'handle_n8n_mark_chat_viewed']);
        add_action('wp_ajax_aif_n8n_mark_leads_viewed', [$this, 'handle_n8n_mark_leads_viewed']);

        // Tones (phong cách viết)
        add_action('wp_ajax_aif_get_tones',    [$this, 'handle_tone_get_all']);
        add_action('wp_ajax_aif_tone_save',    [$this, 'handle_tone_save']);
        add_action('wp_ajax_aif_tone_delete',  [$this, 'handle_tone_delete']);
        add_action('wp_ajax_aif_tone_reorder', [$this, 'handle_tone_reorder']);
    }

    private function includes()
    {
        // Load Core Services
        require_once AIF_PATH . 'includes/class-activator.php';
        require_once AIF_PATH . 'includes/class-settings.php'; // Settings từ DB riêng
        require_once AIF_PATH . 'includes/class-db.php';

        // Đảm bảo bảng aif_settings tồn tại (cho trường hợp plugin update không chạy lại activate)
        AIF_Settings::create_table();

        // Auto-upgrade: tạo bảng tones nếu chưa tồn tại
        require_once AIF_PATH . 'includes/class-tones-db.php';
        AIF_Tones_DB::create_table();

        // Auto-upgrade: tạo bảng media mới nếu chưa tồn tại
        global $wpdb;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}aif_media_file_folders'") === null) {
            AIF_Activator::activate();
        }

        // Auto-upgrade: thêm cột is_viewed cho chats & leads (nếu chưa có)
        if ($wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}aif_n8n_chats LIKE 'is_viewed'") === []) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}aif_n8n_chats ADD COLUMN is_viewed tinyint(1) DEFAULT 0 AFTER updated_at");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}aif_n8n_chats ADD COLUMN viewed_at datetime DEFAULT NULL AFTER is_viewed");
        }
        if ($wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}aif_n8n_leads LIKE 'is_viewed'") === []) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}aif_n8n_leads ADD COLUMN is_viewed tinyint(1) DEFAULT 0 AFTER created_at");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}aif_n8n_leads ADD COLUMN viewed_at datetime DEFAULT NULL AFTER is_viewed");
        }

        require_once AIF_PATH . 'includes/class-google-sheet.php';
        require_once AIF_PATH . 'includes/class-ai-generator.php';
        require_once AIF_PATH . 'includes/class-media-manager.php';
        require_once AIF_PATH . 'includes/class-scheduler.php';
        new AIF_Scheduler(); // Instantiate to register hooks
        require_once AIF_PATH . 'includes/class-publisher.php';
        require_once AIF_PATH . 'includes/class-facebook-manager.php';
        require_once AIF_PATH . 'includes/class-status.php';
        require_once AIF_PATH . 'includes/class-webhook-handler.php';
        require_once AIF_PATH . 'includes/class-n8n-db.php';
        require_once AIF_PATH . 'includes/class-n8n-ai.php';
        require_once AIF_PATH . 'includes/class-n8n-handler.php';
        require_once AIF_PATH . 'includes/class-tones-db.php';
    }

    // --- AJAX Callback Implementations ---

    public function handle_import_gsheet()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $csv_url = isset($_POST['csv_url']) ? sanitize_text_field($_POST['csv_url']) : '';
        if (empty($csv_url)) {
            wp_send_json_error('Missing CSV URL');
        }

        $service = new AIF_Google_Sheet();
        $result = $service->sync_from_csv($csv_url);

        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        } else {
            wp_send_json_success($result);
        }
    }

    public function handle_upload_media_inline()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Unauthorized');
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['file'];

        // Basic error check
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Upload error code: ' . $file['error']);
        }

        // Validate type (basic)
        $file_type = wp_check_filetype(basename($file['name']));
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4'];

        // Let's rely on standard extension check or MIME type
        // The previous media-library logic just used move_uploaded_file with whatever was accepted by html input
        // For security, enforcing some check is good

        $filename = sanitize_file_name($file['name']);

        // Ensure unique filename to prevent overwrites (Optional but recommended, though old code didn't do it)
        $upload_dir = AIF_PATH . 'upload/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Handle uniqueness
        $target_file = $upload_dir . $filename;
        $file_parts = pathinfo($filename);
        $name = $file_parts['filename'];
        $ext = isset($file_parts['extension']) ? '.' . $file_parts['extension'] : '';
        $i = 1;
        while (file_exists($target_file)) {
            $filename = $name . '-' . $i . $ext;
            $target_file = $upload_dir . $filename;
            $i++;
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            wp_send_json_success([
                'filename' => $filename,
                'url' => AIF_URL . 'upload/' . $filename
            ]);
        } else {
            wp_send_json_error('Failed to move uploaded file');
        }
    }

    // ─── Media Library: helpers ───────────────────────────────────────────────

    // ─── Media helpers ────────────────────────────────────────────────────────

    private function media_upload_base()
    {
        return AIF_PATH . 'upload/';
    }
    private function media_upload_url()
    {
        return AIF_URL  . 'upload/';
    }

    private function media_tbl_folders()
    {
        global $wpdb;
        return $wpdb->prefix . 'aif_media_folders';
    }
    private function media_tbl_meta()
    {
        global $wpdb;
        return $wpdb->prefix . 'aif_media_meta';
    }
    private function media_tbl_file_folders()
    {
        global $wpdb;
        return $wpdb->prefix . 'aif_media_file_folders';
    }

    /** Lấy tất cả folder_id của 1 file */
    private function media_get_file_folder_ids($file_id)
    {
        global $wpdb;
        return array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT folder_id FROM {$this->media_tbl_file_folders()} WHERE file_id = %d",
            $file_id
        )));
    }

    /** Đảm bảo file có record trong meta, trả về row */
    private function media_ensure_meta($filename)
    {
        global $wpdb;
        $t = $this->media_tbl_meta();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE filename = %s", $filename));
        if (!$row) {
            $wpdb->insert($t, ['filename' => $filename]);
            $row = (object)['id' => $wpdb->insert_id, 'filename' => $filename];
        }
        return $row;
    }

    // ─── Media Library: AJAX handlers ────────────────────────────────────────

    public function handle_media_get_folders()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        global $wpdb;

        $tf  = $this->media_tbl_folders();
        $tm  = $this->media_tbl_meta();
        $tff = $this->media_tbl_file_folders();
        $base     = $this->media_upload_base();
        $base_url = $this->media_upload_url();

        // ── Sync filesystem → meta ────────────────────────────────────────────
        $exts  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'];
        $known = array_fill_keys($wpdb->get_col("SELECT filename FROM $tm") ?: [], true);
        $raw   = glob($base . '*.{' . implode(',', $exts) . '}', GLOB_BRACE) ?: [];
        foreach ($raw as $path) {
            $fname = basename($path);
            if (!isset($known[$fname])) {
                $wpdb->insert($tm, ['filename' => $fname]);
            }
        }
        // Xóa meta của file đã bị xóa khỏi disk
        foreach ($wpdb->get_results("SELECT id, filename FROM $tm") ?: [] as $row) {
            if (!file_exists($base . $row->filename)) {
                $wpdb->delete($tff, ['file_id' => $row->id]);
                $wpdb->delete($tm, ['id' => $row->id]);
            }
        }

        // ── Folder list + count ───────────────────────────────────────────────
        $db_folders  = $wpdb->get_results("SELECT * FROM $tf ORDER BY name ASC") ?: [];
        $count_rows  = $wpdb->get_results("SELECT folder_id, COUNT(DISTINCT file_id) as cnt FROM $tff GROUP BY folder_id") ?: [];
        $count_map   = [];
        foreach ($count_rows as $r) $count_map[$r->folder_id] = (int)$r->cnt;

        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM $tm");

        $folders = [['id' => 0, 'name' => '', 'label' => 'Tất cả', 'count' => $total]];
        foreach ($db_folders as $f) {
            $folders[] = ['id' => (int)$f->id, 'name' => $f->name, 'label' => $f->name, 'count' => $count_map[$f->id] ?? 0];
        }

        // ── Files theo folder filter ──────────────────────────────────────────
        $folder_param = sanitize_text_field($_REQUEST['folder'] ?? '');
        if ($folder_param === '') {
            $rows = $wpdb->get_results(
                "SELECT m.id, m.filename, m.uploaded_at,
                    GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ',') as folder_names,
                    GROUP_CONCAT(f.id   ORDER BY f.name SEPARATOR ',') as folder_ids
                 FROM $tm m
                 LEFT JOIN $tff ff ON ff.file_id = m.id
                 LEFT JOIN $tf  f  ON f.id = ff.folder_id
                 GROUP BY m.id ORDER BY m.uploaded_at DESC"
            ) ?: [];
        } else {
            $fid  = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $tf WHERE name = %s", $folder_param));
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT m.id, m.filename, m.uploaded_at,
                    GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ',') as folder_names,
                    GROUP_CONCAT(f.id   ORDER BY f.name SEPARATOR ',') as folder_ids
                 FROM $tm m
                 INNER JOIN $tff ff2 ON ff2.file_id = m.id AND ff2.folder_id = %d
                 LEFT JOIN  $tff ff  ON ff.file_id = m.id
                 LEFT JOIN  $tf  f   ON f.id = ff.folder_id
                 GROUP BY m.id ORDER BY m.uploaded_at DESC",
                $fid
            )) ?: [];
        }

        $files = [];
        foreach ($rows as $row) {
            $path = $base . $row->filename;
            if (!file_exists($path)) continue;
            $files[] = [
                'id'         => (int)$row->id,
                'name'       => $row->filename,
                'url'        => $base_url . $row->filename,
                'size'       => filesize($path),
                'mtime'      => filemtime($path),
                'ext'        => strtolower(pathinfo($row->filename, PATHINFO_EXTENSION)),
                'folder'     => $row->folder_names ? explode(',', $row->folder_names)[0] : '', // compat
                'folders'    => $row->folder_names ? explode(',', $row->folder_names) : [],
                'folder_ids' => $row->folder_ids   ? array_map('intval', explode(',', $row->folder_ids)) : [],
            ];
        }

        wp_send_json_success(['folders' => $folders, 'files' => $files]);
    }

    public function handle_media_create_folder()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $name = trim(sanitize_text_field($_POST['name'] ?? ''));
        if (!$name) wp_send_json_error('Tên chuyên mục không hợp lệ.');
        $tf = $this->media_tbl_folders();
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tf WHERE name = %s", $name)))
            wp_send_json_error('Chuyên mục đã tồn tại.');
        $wpdb->insert($tf, ['name' => $name]);
        wp_send_json_success(['id' => $wpdb->insert_id, 'name' => $name]);
    }

    public function handle_media_rename_folder()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id      = intval($_POST['id'] ?? 0);
        $newname = trim(sanitize_text_field($_POST['name'] ?? ''));
        if (!$id || !$newname) wp_send_json_error('Thiếu thông tin.');
        $tf = $this->media_tbl_folders();
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM $tf WHERE name = %s AND id != %d", $newname, $id)))
            wp_send_json_error('Tên chuyên mục đã tồn tại.');
        $wpdb->update($tf, ['name' => $newname], ['id' => $id], ['%s'], ['%d']);
        wp_send_json_success(['id' => $id, 'name' => $newname]);
    }

    public function handle_media_delete_folder()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id    = intval($_POST['id'] ?? 0);
        $force = !empty($_POST['force']);
        if (!$id) wp_send_json_error('Thiếu ID.');
        $tff = $this->media_tbl_file_folders();
        $tf  = $this->media_tbl_folders();
        $count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tff WHERE folder_id = %d", $id));
        if ($count > 0 && !$force)
            wp_send_json_error([
                'code' => 'not_empty',
                'count' => $count,
                'msg' => "Chuyên mục còn $count file. Gỡ chuyên mục khỏi các file đó?"
            ]);
        $wpdb->delete($tff, ['folder_id' => $id]);
        $wpdb->delete($tf,  ['id' => $id]);
        wp_send_json_success('Deleted');
    }

    public function handle_media_upload()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        if (empty($_FILES['file'])) wp_send_json_error('No file');
        global $wpdb;
        $folder_ids = isset($_POST['folder_ids']) ? array_map('intval', (array)$_POST['folder_ids']) : [];
        $base       = $this->media_upload_base();
        $file       = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) wp_send_json_error('Upload error: ' . $file['error']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) wp_send_json_error('Định dạng không được phép.');
        $filename = sanitize_file_name($file['name']);
        $parts    = pathinfo($filename);
        $dotExt   = '.' . $ext;
        $target   = $base . $filename;
        $i = 1;
        while (file_exists($target)) {
            $filename = $parts['filename'] . '-' . $i . $dotExt;
            $target   = $base . $filename;
            $i++;
        }
        if (!move_uploaded_file($file['tmp_name'], $target)) wp_send_json_error('Không thể lưu file.');
        $tm = $this->media_tbl_meta();
        $wpdb->insert($tm, ['filename' => $filename]);
        $file_id = $wpdb->insert_id;
        $tff = $this->media_tbl_file_folders();
        foreach ($folder_ids as $fid) {
            if ($fid > 0) $wpdb->insert($tff, ['file_id' => $file_id, 'folder_id' => $fid]);
        }
        $folder_names = [];
        if ($folder_ids) {
            $in = implode(',', array_filter($folder_ids, 'intval'));
            $folder_names = $in ? $wpdb->get_col("SELECT name FROM {$this->media_tbl_folders()} WHERE id IN ($in)") : [];
        }
        wp_send_json_success([
            'id'         => $file_id,
            'name'       => $filename,
            'url'        => $this->media_upload_url() . $filename,
            'ext'        => $ext,
            'folder'     => $folder_names[0] ?? '',
            'folders'    => $folder_names,
            'folder_ids' => $folder_ids,
        ]);
    }

    public function handle_media_delete_file()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $filename = sanitize_file_name($_POST['filename'] ?? '');
        if (!$filename) wp_send_json_error('Invalid filename');
        $path = $this->media_upload_base() . $filename;
        if (!file_exists($path) || !is_file($path)) wp_send_json_error('File không tồn tại.');
        if (!unlink($path)) wp_send_json_error('Không thể xóa file.');
        $tm  = $this->media_tbl_meta();
        $tff = $this->media_tbl_file_folders();
        $id  = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $tm WHERE filename = %s", $filename));
        if ($id) {
            $wpdb->delete($tff, ['file_id' => $id]);
            $wpdb->delete($tm,  ['id' => $id]);
        }
        wp_send_json_success('Deleted');
    }

    /** Gán nhiều chuyên mục cho 1 file (thay thế hoàn toàn) */
    public function handle_media_set_folders()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $filename   = sanitize_file_name($_POST['filename'] ?? '');
        $folder_ids = isset($_POST['folder_ids']) ? array_map('intval', (array)$_POST['folder_ids']) : [];
        if (!$filename) wp_send_json_error('Invalid filename');
        $row = $this->media_ensure_meta($filename);
        $tff = $this->media_tbl_file_folders();
        // Xóa hết rồi insert lại
        $wpdb->delete($tff, ['file_id' => $row->id]);
        foreach ($folder_ids as $fid) {
            if ($fid > 0) $wpdb->insert($tff, ['file_id' => $row->id, 'folder_id' => $fid]);
        }
        $folder_names = [];
        if ($folder_ids) {
            $in = implode(',', array_filter($folder_ids, 'intval'));
            $folder_names = $in ? $wpdb->get_col("SELECT name FROM {$this->media_tbl_folders()} WHERE id IN ($in)") : [];
        }
        wp_send_json_success([
            'folder'     => $folder_names[0] ?? '',
            'folders'    => $folder_names,
            'folder_ids' => $folder_ids,
        ]);
    }

    public function handle_update_fanpage_token()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $token = isset($_POST['access_token']) ? sanitize_text_field(wp_unslash($_POST['access_token'])) : '';
        $app_id = isset($_POST['app_id']) ? sanitize_text_field(wp_unslash($_POST['app_id'])) : '';
        $app_secret = isset($_POST['app_secret']) ? sanitize_text_field(wp_unslash($_POST['app_secret'])) : '';

        if (!$id || empty($token)) {
            wp_send_json_error('Thiếu thông tin ID hoặc Token.');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->update_token($id, $token, $app_id, $app_secret);

        if ($result['success']) {
            wp_send_json_success('Cập nhật Token thành công!');
        } else {
            wp_send_json_error(isset($result['message']) ? $result['message'] : 'Lỗi khi cập nhật Token vào database.');
        }
    }

    public function handle_generate_content()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_id         = isset($_POST['post_id'])         ? intval($_POST['post_id'])                         : 0;
        $prompt_input    = isset($_POST['prompt'])          ? sanitize_textarea_field($_POST['prompt'])         : '';
        $platform        = isset($_POST['platform'])        ? sanitize_text_field($_POST['platform'])           : 'facebook';
        $current_content = isset($_POST['current_content']) ? sanitize_textarea_field($_POST['current_content']) : '';
        $tone            = isset($_POST['tone'])            ? sanitize_text_field($_POST['tone'])               : '';

        $industry = '';
        if ($post_id) {
            $db   = new AIF_DB();
            $post = $db->get($post_id);
            if ($post) $industry = $post->industry;
        }

        $user_prompt = $prompt_input ? $prompt_input : $current_content;

        $input = [
            'industry'    => $industry,
            'description' => $user_prompt . ($current_content ? " (Context: $current_content)" : ''),
        ];

        $service  = new AIF_AI_Generator();
        $response = $service->generate($input, $platform, $tone);

        if ($response && isset($response['success']) && $response['success']) {
            wp_send_json_success($response);
        } else {
            $error_msg = isset($response['caption']) ? $response['caption'] : 'AI Generation Failed - Lỗi không xác định';
            if (isset($response['error'])) $error_msg = $response['error'];
            wp_send_json_error($error_msg);
        }
    }

    // ── Generate 3 variations ────────────────────────────────────────────────
    public function handle_generate_variations()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_id         = isset($_POST['post_id'])         ? intval($_POST['post_id'])                         : 0;
        $prompt_input    = isset($_POST['prompt'])          ? sanitize_textarea_field($_POST['prompt'])         : '';
        $platform        = isset($_POST['platform'])        ? sanitize_text_field($_POST['platform'])           : 'facebook';
        $current_content = isset($_POST['current_content']) ? sanitize_textarea_field($_POST['current_content']) : '';
        $tone            = isset($_POST['tone'])            ? sanitize_text_field($_POST['tone'])               : '';

        if (empty($prompt_input) && empty($current_content)) {
            wp_send_json_error('Vui lòng nhập mô tả yêu cầu.');
        }

        $industry = '';
        if ($post_id) {
            $db   = new AIF_DB();
            $post = $db->get($post_id);
            if ($post) $industry = $post->industry;
        }

        $user_prompt = $prompt_input ? $prompt_input : $current_content;
        $input = [
            'industry'    => $industry,
            'description' => $user_prompt . ($current_content ? " (Context: $current_content)" : ''),
        ];

        $service    = new AIF_AI_Generator();
        $variations = $service->generate_variations($input, $platform, $tone);

        if (empty($variations)) {
            wp_send_json_error('Không thể tạo nội dung. Vui lòng thử lại.');
        }

        wp_send_json_success(['variations' => $variations]);
    }

    // ── Lưu custom tone ──────────────────────────────────────────────────────
    public function handle_save_custom_tone()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Không có quyền.');

        $label = sanitize_text_field(wp_unslash($_POST['label'] ?? ''));
        $desc  = sanitize_textarea_field(wp_unslash($_POST['desc']  ?? ''));
        $style = sanitize_textarea_field(wp_unslash($_POST['style'] ?? ''));
        $key   = isset($_POST['key']) ? sanitize_key($_POST['key']) : '';

        if (empty($label) || empty($style)) wp_send_json_error('Vui lòng điền đầy đủ tên và hướng dẫn phong cách.');

        // Tạo key từ label nếu chưa có (khi thêm mới)
        if (!$key) {
            $key = 'custom_' . sanitize_key(preg_replace('/[^a-z0-9]/ui', '_', mb_strtolower($label))) . '_' . substr(md5(microtime()), 0, 4);
        }

        $customs = get_option('aif_custom_tones', []);
        if (!is_array($customs)) $customs = [];
        $customs[$key] = ['label' => $label, 'desc' => $desc, 'style' => $style, 'custom' => true];
        update_option('aif_custom_tones', $customs);

        wp_send_json_success(['key' => $key, 'label' => $label, 'desc' => $desc]);
    }

    // ── Xóa custom tone ──────────────────────────────────────────────────────
    public function handle_delete_custom_tone()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Không có quyền.');

        $key = sanitize_key($_POST['key'] ?? '');
        if (!$key) wp_send_json_error('Key không hợp lệ.');

        $customs = get_option('aif_custom_tones', []);
        if (!is_array($customs) || !isset($customs[$key])) wp_send_json_error('Không tìm thấy tone.');

        unset($customs[$key]);
        update_option('aif_custom_tones', $customs);
        wp_send_json_success();
    }

    // ── Smart Check caption ──────────────────────────────────────────────────
    public function handle_smart_check()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        $title   = isset($_POST['title'])   ? sanitize_text_field(wp_unslash($_POST['title'])) : '';

        $issues  = [];
        $score   = 100;

        // 1. Độ dài caption
        $len = mb_strlen(strip_tags($content));
        if ($len < 50) {
            $issues[] = ['type' => 'error',   'msg' => "Nội dung quá ngắn ({$len} ký tự). Nên ít nhất 50 ký tự."];
            $score -= 30;
        } elseif ($len < 150) {
            $issues[] = ['type' => 'warning', 'msg' => "Nội dung hơi ngắn ({$len} ký tự). Nên từ 150+ ký tự để đạt reach tốt."];
            $score -= 10;
        } elseif ($len > 5000) {
            $issues[] = ['type' => 'warning', 'msg' => "Nội dung quá dài ({$len} ký tự). Facebook cắt bài sau ~400 ký tự hiển thị."];
            $score -= 10;
        }

        // 2. Thiếu tiêu đề
        if (empty(trim($title))) {
            $issues[] = ['type' => 'warning', 'msg' => 'Chưa có tiêu đề. Tiêu đề giúp tăng CTR đáng kể.'];
            $score -= 15;
        }

        // 3. Spam words
        $spam_words = ['click ngay', 'mua ngay', 'siêu rẻ', 'free ship', 'giảm giá sốc', 'cực hot', 'inbox ngay', 'dm ngay'];
        $content_lower = mb_strtolower($content);
        $found_spam = [];
        foreach ($spam_words as $w) {
            if (mb_strpos($content_lower, $w) !== false) $found_spam[] = $w;
        }
        if (count($found_spam) > 2) {
            $issues[] = ['type' => 'warning', 'msg' => 'Có ' . count($found_spam) . ' cụm từ spam: "' . implode('", "', $found_spam) . '". Facebook có thể hạn chế reach.'];
            $score -= 15;
        }

        // 4. Không có CTA
        $cta_signals = ['comment', 'bình luận', 'chia sẻ', 'share', 'like', 'tag', 'liên hệ', 'nhắn tin', 'inbox', 'dm', 'đặt hàng', 'mua', 'xem thêm'];
        $has_cta = false;
        foreach ($cta_signals as $cta) {
            if (mb_strpos($content_lower, $cta) !== false) {
                $has_cta = true;
                break;
            }
        }
        if (!$has_cta) {
            $issues[] = ['type' => 'info', 'msg' => 'Bài viết chưa có CTA (Call to Action). Thêm lời kêu gọi hành động để tăng tương tác.'];
            $score -= 10;
        }

        // 5. Không có hashtag
        if (mb_strpos($content, '#') === false) {
            $issues[] = ['type' => 'info', 'msg' => 'Chưa có hashtag. Thêm 3–5 hashtag liên quan để tăng khả năng tìm kiếm.'];
            $score -= 5;
        }

        // 6. Quá nhiều emoji liên tiếp
        if (preg_match('/(\p{So}|\p{Sm}){5,}/u', $content)) {
            $issues[] = ['type' => 'info', 'msg' => 'Có quá nhiều emoji liên tiếp. Dùng tiết chế để trông chuyên nghiệp hơn.'];
            $score -= 5;
        }

        $score = max(0, $score);

        // Xếp loại
        if ($score >= 85) {
            $grade = 'A';
            $grade_label = 'Tốt';
            $grade_color = '#059669';
        } elseif ($score >= 65) {
            $grade = 'B';
            $grade_label = 'Khá';
            $grade_color = '#0284c7';
        } elseif ($score >= 45) {
            $grade = 'C';
            $grade_label = 'Trung bình';
            $grade_color = '#d97706';
        } else {
            $grade = 'D';
            $grade_label = 'Yếu';
            $grade_color = '#ef4444';
        }

        wp_send_json_success([
            'score'       => $score,
            'grade'       => $grade,
            'grade_label' => $grade_label,
            'grade_color' => $grade_color,
            'issues'      => $issues,
            'length'      => $len,
        ]);
    }

    public function handle_get_post_details()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id)
            wp_send_json_error('Invalid ID');

        $db = new AIF_DB();
        $post = $db->get($post_id);

        if ($post) {
            wp_send_json_success([
                'title' => $post->title,
                'content' => $post->content
            ]);
        } else {
            wp_send_json_error('Not found');
        }
    }

    public function handle_bulk_process_item()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';

        if (!$post_id || !$action) {
            wp_send_json_error('Invalid parameters');
        }

        $db = new AIF_DB();
        $post = $db->get($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        // --- Restriction Logic ---
        // 1. Status Check
        if ($post->status === 'Posted successfully') {
            wp_send_json_error('Bài viết đã đăng thành công, không thể sửa.');
        }

        // 2. Queue Check
        $fb_manager = new AIF_Facebook_Manager();
        if ($fb_manager->is_post_queued($post_id)) {
            wp_send_json_error('Bài viết đang trong hàng chờ, không thể sửa.');
        }

        // --- ACTION 1: Generate Content (To do -> Content updated) ---
        if ($action === 'generate') {
            if ($post->status !== 'To do') {
                wp_send_json_error('Skip: Status must be "To do"');
            }

            // Call AI
            // Call AI
            $service = new AIF_AI_Generator();

            // Match logic from handle_generate_content to ensure consistency
            $description_prompt = $post->description;
            // Fallback description
            if (empty($description_prompt)) {
                $description_prompt = "Viết nội dung quảng cáo về " . $post->industry;
            }

            // Include current content as context if available, just like in Post Detail
            $context = !empty($post->content) ? " (Context: " . $post->content . ")" : "";

            $input = [
                'industry' => $post->industry,
                'description' => $description_prompt . $context
            ];

            $response = $service->generate($input, $post->option_platform);

            if ($response && $response['success']) {
                // Clean content just in case
                $clean_content = wp_kses_post($response['caption']);
                // Basic cleanup of excessive backslashes if AI hallucinates them
                $clean_content = str_replace('\\\\', '', $clean_content);

                $update_data = [
                    'content' => $clean_content,
                    'status' => 'Content updated',
                    'updated_at' => current_time('mysql')
                ];

                // Optional: Extract a pseudo-title from content if missing
                if (!empty($response['generated_title'])) {
                    $generated_title = wp_kses_post($response['generated_title']);
                    // Update title ALWAYS if it's from AI? Or only if empty?
                    // User likely wants the AI title. If status is 'To do', we assume we are filling content.
                    $update_data['title'] = $generated_title;
                } elseif (empty($post->title)) {
                    $generated_title = wp_trim_words($response['caption'], 10, '...');
                    $update_data['title'] = $generated_title;
                } else {
                    $generated_title = $post->title;
                }

                $db->update($post_id, $update_data);

                wp_send_json_success([
                    'message' => 'Generated content successfully',
                    'new_status' => 'Content updated',
                    'generated_title' => $generated_title,
                    'content' => $clean_content,
                    'content_preview' => wp_trim_words($clean_content, 12, '...')
                ]);
            } else {
                $error_msg = isset($response['caption']) ? $response['caption'] : 'Failed to generate content via AI.';
                if (isset($response['error'])) {
                    $error_msg = $response['error'];
                }
                wp_send_json_error($error_msg);
            }
        }

        // --- ACTION 2: Publish Post (Queue for publishing) ---
        elseif ($action === 'publish') {
            $stt_display = isset($_POST['stt']) ? sanitize_text_field($_POST['stt']) : $post->id;

            // Must be Content updated or Done to publish
            if ($post->status === 'To do') {
                wp_send_json_error("Bài viết STT $stt_display vẫn đang To do, không được đăng.");
            }

            if (empty($post->content)) {
                wp_send_json_error("Bài viết STT $stt_display chưa có nội dung (content trống).");
            }

            // Check targets
            $targets = !empty($post->targets) ? json_decode($post->targets, true) : [];
            if (empty($targets)) {
                wp_send_json_error("Bài viết STT $stt_display chưa chọn Fanpage để đăng. Vui lòng vào Chi tiết bài viết để chọn Fanpage.");
            }

            // Set status to Done (triggers queueing)
            $is_scheduled = false;
            $now = current_time('mysql');
            if (!empty($post->time_posting) && $post->time_posting !== '0000-00-00 00:00:00' && $post->time_posting > $now) {
                $is_scheduled = true;
            }

            $queue_status = $is_scheduled ? 'scheduled' : 'pending';
            $added_count = 0;
            global $wpdb;
            $table_queue = $wpdb->prefix . 'aif_posting_queue';

            foreach ($targets as $target) {
                $pid = isset($target['id']) ? intval($target['id']) : 0;
                if (!$pid)
                    continue;

                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_queue WHERE post_id = %d AND page_id = %d AND status IN ('pending', 'scheduled', 'processing')",
                    $post_id,
                    $pid
                ));

                if (!$exists) {
                    $fb_manager->add_to_queue($post_id, $pid, $queue_status);
                    $added_count++;
                }
            }

            // Update status to Done
            $db->update($post_id, [
                'status' => 'Done',
                'updated_at' => current_time('mysql')
            ]);

            if ($added_count > 0) {
                if (!$is_scheduled) {
                    $fb_manager->process_queue();
                    $status_msg = "STT $stt_display: Đã thêm vào hàng chờ & đang đăng bài...";
                } else {
                    $status_msg = "STT $stt_display: Đã lên lịch đăng bài (" . $post->time_posting . ")";
                }
            } else {
                $status_msg = "STT $stt_display: Bài viết đã có trong hàng chờ.";
            }

            // Re-fetch to get actual status (process_queue may have changed it)
            $updated_post = $db->get($post_id);

            wp_send_json_success([
                'message' => $status_msg,
                'new_status' => $updated_post->status,
                'stt' => $stt_display
            ]);
        } else {
            wp_send_json_error('Unknown action');
        }
    }

    public function handle_save_fanpage()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $params = [];
        parse_str($_POST['data'], $params);

        if (empty($params['page_name']) || empty($params['page_id']) || empty($params['access_token'])) {
            wp_send_json_error('Missing required fields');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->save_page($params);

        if ($result['success']) {
            wp_send_json_success('Saved successfully');
        } else {
            wp_send_json_error(isset($result['message']) ? $result['message'] : 'Failed to save to database');
        }
    }

    public function handle_edit_fanpage()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->update_page_info($id, [
            'page_name' => isset($_POST['page_name']) ? wp_unslash($_POST['page_name']) : '',
            'app_id'    => isset($_POST['app_id'])    ? wp_unslash($_POST['app_id'])    : '',
        ]);

        if ($result['success']) {
            wp_send_json_success('Cập nhật thành công');
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function handle_delete_fanpage()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->delete_page($id);

        if ($result['success']) {
            wp_send_json_success('Deleted successfully');
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function handle_add_to_queue()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;

        if (!$post_id || !$page_id) {
            wp_send_json_error('Invalid parameters');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->add_to_queue($post_id, $page_id);

        if ($result) {
            // Update Post Status to show it's queued
            $db = new AIF_DB();
            $db->update($post_id, ['status' => 'Queued']);

            wp_send_json_success('Added to queue');
        } else {
            wp_send_json_error('Failed to add to queue');
        }
    }

    public function handle_force_run_cron()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        error_log('[AIF] handle_force_run_cron: START');

        $manager = new AIF_Facebook_Manager();

        error_log('[AIF] handle_force_run_cron: calling check_scheduled_posts');
        $manager->check_scheduled_posts();

        error_log('[AIF] handle_force_run_cron: calling process_queue');
        $manager->process_queue();

        error_log('[AIF] handle_force_run_cron: DONE');

        wp_send_json_success('Cron logic executed.');
    }

    public function register_post_type()
    {
        register_post_type('ai_post', [
            'labels' => [
                'name' => 'AI Posts',
                'singular_name' => 'AI Post',
            ],
            'public' => false,
            'show_ui' => false, // We use custom admin pages
            'supports' => ['title', 'editor', 'custom-fields'],
            'capability_type' => 'post',
        ]);
    }

    private function get_queue_failed_count()
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aif_posting_queue WHERE status LIKE 'failed%'"
        );
    }

    private function get_unread_chat_count()
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}aif_n8n_chats WHERE is_viewed = 0"
        );
    }

    /** CSS badge — inject vào <head> trên toàn bộ admin */
    public function render_menu_badge_styles()
    {
        echo '<style>
            #adminmenu a[href*="page=ai-fanpage-post-detail"] { display: none !important; }
            #adminmenu .aif-menu-badge {
                display: inline-block;
                background: #ef4444;
                color: #fff;
                font-size: 10px;
                font-weight: 700;
                line-height: 1;
                padding: 2px 6px;
                border-radius: 10px;
                margin-left: 5px;
                vertical-align: middle;
            }
            #adminmenu .aif-menu-badge-chat {
                background: #f97316;
                animation: aif-badge-pulse 2s infinite;
            }
            @keyframes aif-badge-pulse {
                0%, 100% { opacity: 1; }
                50%       { opacity: 0.6; }
            }
        </style>';
    }

    /** JS badge — inject vào <footer> trên toàn bộ admin, sau khi DOM sẵn sàng */
    public function render_menu_badge_script()
    {
        $unread = $this->get_unread_chat_count();
?>
        <script>
            (function($) {
                var unread = <?php echo $unread; ?>;
                var $link = $('#adminmenu a[href*="page=ai-fanpage-chatbot"]').first();
                if (!$link.length) return;
                $link.find('.aif-menu-badge-chat').remove();
                if (unread > 0) {
                    $link.append('<span class="aif-menu-badge aif-menu-badge-chat">' + unread + '</span>');
                }
            })(jQuery);
        </script>
<?php
    }

    public function register_admin_menu()
    {
        // Main Menu (Dashboard)
        add_menu_page(
            'AI Fanpage',
            'AI Fanpage',
            'manage_options',
            'ai-fanpage',
            [$this, 'render_dashboard'],
            'dashicons-share', // Social icon
            6
        );

        // Submenu: Management (Same as main for now, or separate if needed)
        add_submenu_page(
            'ai-fanpage',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'ai-fanpage',
            [$this, 'render_dashboard']
        );

        // Submenu: All Posts
        add_submenu_page(
            'ai-fanpage',
            'Tất cả bài viết',
            'Bài viết AI',
            'manage_options',
            'ai-fanpage-posts',
            [$this, 'render_posts_list']
        );

        // Submenu: Media Library
        add_submenu_page(
            'ai-fanpage',
            'Kho Media',
            'Kho Media',
            'manage_options',
            'ai-fanpage-media',
            [$this, 'render_media_library']
        );

        // Submenu: Fanpage Connection
        add_submenu_page(
            'ai-fanpage',
            'Kết nối Fanpage',
            'Kết nối Fanpage',
            'manage_options',
            'ai-fanpage-settings',
            [$this, 'render_fanpage_settings']
        );

        // Submenu: Posting Queue
        $failed_count = $this->get_queue_failed_count();
        $queue_label  = 'Hàng chờ đăng';
        if ($failed_count > 0) {
            $queue_label .= ' <span class="aif-menu-badge">' . $failed_count . '</span>';
        }

        add_submenu_page(
            'ai-fanpage',
            'Hàng chờ đăng',
            $queue_label,
            'manage_options',
            'ai-fanpage-queue',
            [$this, 'render_queue_list']
        );

        // Submenu: Analytics
        add_submenu_page(
            'ai-fanpage',
            'AIF Analytics',
            'AIF Analytics',
            'manage_options',
            'ai-fanpage-analytics',
            [$this, 'render_analytics']
        );

        // Hidden Page: Edit Post — đăng ký dưới menu cha để WP giữ menu active,
        // ẩn khỏi sidebar bằng CSS (thêm ở enqueue_admin_assets).
        add_submenu_page(
            'ai-fanpage',
            'Chi tiết bài viết',
            'Chi tiết bài viết',
            'manage_options',
            'ai-fanpage-post-detail',
            [$this, 'render_post_detail']
        );

        // Submenu: N8N Management
        $unread_chats  = $this->get_unread_chat_count();
        $chatbot_label = 'Chatbot';
        if ($unread_chats > 0) {
            $chatbot_label .= ' <span class="aif-menu-badge aif-menu-badge-chat">' . $unread_chats . '</span>';
        }

        add_submenu_page(
            'ai-fanpage',
            'N8N Chat & Products',
            $chatbot_label,
            'manage_options',
            'ai-fanpage-chatbot',
            [$this, 'render_n8n_manager']
        );

        // Submenu: AI Settings (API Keys lưu trong DB riêng)
        add_submenu_page(
            'ai-fanpage',
            'AI Settings',
            '⚙ AI Settings',
            'manage_options',
            'ai-fanpage-ai-settings',
            [$this, 'render_ai_settings']
        );
    }

    /**
     * Giữ menu AI Fanpage active khi đang ở trang chi tiết bài viết.
     */
    public function highlight_parent_menu()
    {
        global $parent_file, $submenu_file;

        $page = isset($_GET['page']) ? $_GET['page'] : '';
        if ($page === 'ai-fanpage-post-detail') {
            $parent_file  = 'ai-fanpage';
            $submenu_file = 'ai-fanpage-posts';
        }
    }

    public function enqueue_admin_assets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, 'ai-fanpage') === false) {
            return;
        }

        wp_enqueue_style('aif-admin-style', AIF_URL . 'assets/css/admin-style.css', [], filemtime(AIF_PATH . 'assets/css/admin-style.css'));

        // Load WP Media Library + page-specific assets on post-detail page
        if (strpos($hook, 'post-detail') !== false || (isset($_GET['page']) && $_GET['page'] === 'ai-fanpage-post-detail')) {
            wp_enqueue_media();
            wp_enqueue_style('aif-post-detail-style', AIF_URL . 'assets/css/post-detail.css', [], filemtime(AIF_PATH . 'assets/css/post-detail.css'));
            wp_enqueue_script('aif-post-detail-script', AIF_URL . 'assets/js/post-detail.js', ['jquery', 'aif-toast-script'], filemtime(AIF_PATH . 'assets/js/post-detail.js'), true);
            // Build wp_att_urls map cho các ảnh WP đã lưu trong bài
            $wp_att_urls = [];
            $post_id_for_media = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($post_id_for_media) {
                global $wpdb;
                $saved_images_raw = $wpdb->get_var($wpdb->prepare(
                    "SELECT images FROM {$wpdb->prefix}aif_posts WHERE id = %d",
                    $post_id_for_media
                ));
                if ($saved_images_raw) {
                    $saved = json_decode($saved_images_raw, true) ?: [];
                    foreach ($saved as $v) {
                        if (strpos($v, 'wp-att-') === 0) {
                            $att_id = intval(substr($v, 7));
                            $url    = wp_get_attachment_url($att_id);
                            if ($url) $wp_att_urls[$v] = $url;
                        }
                    }
                }
            }

            wp_localize_script('aif-post-detail-script', 'aif_post_detail', [
                'ajax_url'       => admin_url('admin-ajax.php'),
                'nonce'          => wp_create_nonce('aif_nonce'),
                'post_id'        => isset($_GET['id']) ? intval($_GET['id']) : 0,
                'upload_url'     => AIF_URL . 'upload/',
                'status_labels'  => AIF_Status::js_labels(),
                'status_classes' => AIF_Status::js_badge_classes(),
                'wp_att_urls'    => $wp_att_urls,
            ]);
        }

        // Then enqueue our script which depends on jQuery and Toast
        wp_enqueue_script('aif-toast-script',  AIF_URL . 'assets/js/aif-toast.js',    [], filemtime(AIF_PATH . 'assets/js/aif-toast.js'),    true);
        wp_enqueue_script('aif-admin-script',  AIF_URL . 'assets/js/admin-script.js', ['jquery', 'aif-toast-script'], filemtime(AIF_PATH . 'assets/js/admin-script.js'), true);
        wp_enqueue_script('aif-chat-bot',      AIF_URL . 'assets/js/chat-bot.js',     ['jquery', 'aif-toast-script'], filemtime(AIF_PATH . 'assets/js/chat-bot.js'),     true);

        // Posts list page only
        if (isset($_GET['page']) && $_GET['page'] === 'ai-fanpage-posts') {
            wp_enqueue_style('aif-posts-list-style', AIF_URL . 'assets/css/posts-list.css', [], filemtime(AIF_PATH . 'assets/css/posts-list.css'));
            wp_enqueue_script('aif-posts-list-script', AIF_URL . 'assets/js/posts-list.js', ['jquery', 'aif-toast-script'], filemtime(AIF_PATH . 'assets/js/posts-list.js'), true);
            wp_add_inline_script(
                'aif-posts-list-script',
                'if (typeof aif_ajax !== "undefined") {' .
                    '    aif_ajax.status_labels  = ' . wp_json_encode(AIF_Status::js_labels()) . ';' .
                    '    aif_ajax.status_classes = ' . wp_json_encode(AIF_Status::js_badge_classes()) . ';' .
                    '}',
                'before'
            );
        }

        // Media Library page only
        if (isset($_GET['page']) && $_GET['page'] === 'ai-fanpage-media') {
            wp_enqueue_style('aif-media-library-style', AIF_URL . 'assets/css/media-library.css', [], filemtime(AIF_PATH . 'assets/css/media-library.css'));
            wp_enqueue_script('aif-media-library-script', AIF_URL . 'assets/js/media-library.js', ['jquery', 'aif-toast-script'], filemtime(AIF_PATH . 'assets/js/media-library.js'), true);
            wp_localize_script('aif-media-library-script', 'aif_media', [
                'nonce'    => wp_create_nonce('aif_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);
        }

        // n8n-manager page only
        if (isset($_GET['page']) && $_GET['page'] === 'ai-fanpage-chatbot') {
            wp_enqueue_style('aif-n8n-manager-style', AIF_URL . 'assets/css/n8n-manager.css', [], filemtime(AIF_PATH . 'assets/css/n8n-manager.css'));
            wp_enqueue_script('aif-n8n-manager-script', AIF_URL . 'assets/js/n8n-manager.js', ['jquery', 'aif-toast-script', 'aif-chat-bot'], filemtime(AIF_PATH . 'assets/js/n8n-manager.js'), true);
            wp_localize_script('aif-n8n-manager-script', 'aif_n8n', [
                'nonce'    => wp_create_nonce('aif_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
            ]);
        }

        $localize_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aif_nonce'),
            'status_labels' => AIF_Status::js_labels(),
            'status_classes' => AIF_Status::js_badge_classes()
        ];

        wp_localize_script('aif-admin-script', 'aif_ajax', $localize_data);
        wp_localize_script('aif-chat-bot', 'aif_ajax', $localize_data);
    }

    // --- Render Callbacks ---

    public function render_dashboard()
    {
        include AIF_PATH . 'admin/dashboard.php';
    }

    public function render_posts_list()
    {
        include AIF_PATH . 'admin/posts-list.php';
    }

    public function render_post_detail()
    {
        include AIF_PATH . 'admin/post-detail.php';
    }

    public function render_media_library()
    {
        include AIF_PATH . 'admin/media-library.php';
    }

    public function render_fanpage_settings()
    {
        include AIF_PATH . 'admin/settings-fanpage.php';
    }

    public function render_queue_list()
    {
        include AIF_PATH . 'admin/queue-list.php';
    }

    public function render_analytics()
    {
        include AIF_PATH . 'admin/analytics.php';
    }

    public function render_n8n_manager()
    {
        include AIF_PATH . 'admin/n8n-manager.php';
    }

    public function render_ai_settings()
    {
        include AIF_PATH . 'admin/settings-ai.php';
    }

    public function render_tones()
    {
        include AIF_PATH . 'admin/tones-manager.php';
    }

    // ── Tones AJAX ────────────────────────────────────────────────────────────
    public function handle_tone_get_all()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success((new AIF_Tones_DB())->get_all());
    }

    public function handle_tone_save()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $id    = intval($_POST['id'] ?? 0);
        $label = sanitize_text_field($_POST['label'] ?? '');
        $desc  = sanitize_text_field($_POST['description'] ?? '');
        $style = sanitize_textarea_field($_POST['style'] ?? '');

        if (!$label || !$style) wp_send_json_error('Thiếu dữ liệu');

        $db = new AIF_Tones_DB();
        if ($id) {
            $db->update($id, ['label' => $label, 'description' => $desc, 'style' => $style]);
            $tone = $db->get($id);
        } else {
            $new_id = $db->insert(['label' => $label, 'description' => $desc, 'style' => $style]);
            $tone   = $db->get($new_id);
        }

        wp_send_json_success($tone);
    }

    public function handle_tone_delete()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error('ID không hợp lệ');

        (new AIF_Tones_DB())->delete($id);
        wp_send_json_success();
    }

    public function handle_tone_reorder()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        if (empty($ids)) wp_send_json_error('Không có dữ liệu');

        (new AIF_Tones_DB())->reorder($ids);
        wp_send_json_success();
    }

    // --- N8N AJAX Implementations ---

    public function handle_n8n_get_chats()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $db = new AIF_N8N_DB();
        wp_send_json_success($db->get_chat_sessions());
    }

    public function handle_n8n_delete_chat()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id)
            wp_send_json_error('Invalid ID');
        $db = new AIF_N8N_DB();
        $db->delete_chat_session($id);
        wp_send_json_success('Deleted');
    }

    public function handle_n8n_get_products()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $db = new AIF_N8N_DB();
        wp_send_json_success($db->get_products(true)); // all = true để admin thấy cả ngừng bán
    }

    public function handle_n8n_toggle_product()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id = intval($_POST['id'] ?? 0);
        if (!$id) wp_send_json_error('Invalid ID');
        $table = $wpdb->prefix . 'aif_n8n_products';
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table WHERE id = %d", $id));
        $new_status = ($current === 'active') ? 'inactive' : 'active';
        $wpdb->update($table, ['status' => $new_status], ['id' => $id], ['%s'], ['%d']);
        wp_send_json_success(['new_status' => $new_status]);
    }

    public function handle_n8n_save_product()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $data = isset($_POST['product']) ? $_POST['product'] : [];
        if (empty($data['product_name'])) {
            wp_send_json_error('Missing Product Name');
        }
        $db = new AIF_N8N_DB();
        $db->upsert_product($data);
        wp_send_json_success('Saved');
    }

    public function handle_n8n_delete_product()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id)
            wp_send_json_error('Invalid ID');
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'aif_n8n_products', ['id' => $id]);
        wp_send_json_success('Deleted');
    }

    public function handle_n8n_get_leads()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $db = new AIF_N8N_DB();
        $leads = $db->get_leads();
        wp_send_json_success($leads);
    }

    public function handle_n8n_export_leads()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $db    = new AIF_N8N_DB();
        $leads = $db->get_leads();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=leads_export_' . date('Y-m-d') . '.csv');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8 (tránh lỗi tiếng Việt)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        fputcsv($output, ['#', 'Tên khách hàng', 'Số điện thoại', 'Sản phẩm quan tâm', 'Địa chỉ giao hàng', 'Ghi chú', 'Nguồn', 'Thời gian']);

        $i = 1;
        foreach ($leads as $lead) {
            $name    = !empty($lead->fb_name) ? $lead->fb_name : (isset($lead->customer_name) ? $lead->customer_name : '');
            $phone   = isset($lead->phone)   ? $lead->phone   : '';
            $address = isset($lead->address) ? $lead->address : '';
            $notes   = isset($lead->notes)   ? $lead->notes   : '';
            $source  = isset($lead->source)  ? $lead->source  : 'chat';
            $time    = isset($lead->created_at) ? $lead->created_at : '';

            // Parse products
            $ai_state = !empty($lead->ai_state) ? json_decode($lead->ai_state, true) : [];
            $prods = [];
            if (!empty($ai_state['order_items'])) {
                foreach ($ai_state['order_items'] as $it) {
                    $prods[] = $it['product_name'];
                }
            } elseif (!empty($ai_state['suggest_products'])) {
                $prods = $ai_state['suggest_products'];
            }
            $product_str = implode(', ', $prods);

            fputcsv($output, [$i++, $name, $phone, $product_str, $address, $notes, $source, $time]);
        }
        fclose($output);
        exit;
    }

    public function handle_n8n_get_settings()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $settings = [
            'system_prompt' => get_option('aif_n8n_system_prompt', ''),
            'cs_info'       => get_option('aif_n8n_cs_info', ''),
            'context_limit' => get_option('aif_n8n_context_limit', 5),
        ];
        wp_send_json_success($settings);
    }

    public function handle_n8n_save_settings()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $system_prompt = isset($_POST['system_prompt']) ? wp_kses_post(wp_unslash($_POST['system_prompt'])) : '';
        $cs_info       = isset($_POST['cs_info']) ? wp_kses_post(wp_unslash($_POST['cs_info'])) : '';
        $context_limit = isset($_POST['context_limit']) ? intval($_POST['context_limit']) : 5;

        update_option('aif_n8n_system_prompt', $system_prompt);
        update_option('aif_n8n_cs_info', $cs_info);
        update_option('aif_n8n_context_limit', $context_limit);

        wp_send_json_success('Settings saved');
    }

    // ── Policy handlers ───────────────────────────────────────────────────────

    private function policy_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'aif_policies';
    }

    public function handle_n8n_get_policies()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT * FROM {$this->policy_table()} ORDER BY sort_order ASC, id ASC"
        );
        wp_send_json_success($rows);
    }

    public function handle_n8n_save_policy()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $id      = isset($_POST['id'])      ? intval($_POST['id'])                              : 0;
        $title   = isset($_POST['title'])   ? sanitize_text_field(wp_unslash($_POST['title']))  : '';
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content']))       : '';

        if (!$title || !$content) wp_send_json_error('Tiêu đề và nội dung không được trống.');

        $table = $this->policy_table();

        // Đảm bảo bảng tồn tại (plugin chưa deactivate/activate lại)
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) " . $wpdb->get_charset_collate());

        if ($id > 0) {
            $wpdb->update(
                $table,
                ['title' => $title, 'content' => $content, 'updated_at' => current_time('mysql')],
                ['id' => $id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            wp_send_json_success(['id' => $id, 'msg' => 'Đã cập nhật']);
        } else {
            $max = $wpdb->get_var("SELECT MAX(sort_order) FROM $table") ?: 0;
            $wpdb->insert(
                $table,
                ['title' => $title, 'content' => $content, 'is_active' => 1, 'sort_order' => $max + 1],
                ['%s', '%s', '%d', '%d']
            );
            wp_send_json_success(['id' => $wpdb->insert_id, 'msg' => 'Đã tạo']);
        }
    }

    public function handle_n8n_delete_policy()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) wp_send_json_error('Invalid ID');
        $wpdb->delete($this->policy_table(), ['id' => $id], ['%d']);
        wp_send_json_success('Deleted');
    }

    public function handle_n8n_toggle_policy()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) wp_send_json_error('Invalid ID');
        $cur = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM {$this->policy_table()} WHERE id=%d", $id));
        $new = $cur ? 0 : 1;
        $wpdb->update($this->policy_table(), ['is_active' => $new], ['id' => $id], ['%d'], ['%d']);
        wp_send_json_success(['is_active' => $new]);
    }

    public function handle_n8n_reorder_policies()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : [];
        foreach ($ids as $i => $id) {
            $wpdb->update($this->policy_table(), ['sort_order' => $i], ['id' => $id], ['%d'], ['%d']);
        }
        wp_send_json_success('Reordered');
    }

    public function handle_flush_ai_cache()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aif_ai_%' OR option_name LIKE '_transient_timeout_aif_ai_%'"
        );
        wp_send_json_success(['deleted' => $deleted, 'msg' => 'Đã xóa cache AI']);
    }

    // Dùng cho chatbot — lấy tất cả chính sách đang bật dạng text
    public static function get_active_policies_text()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aif_policies';
        $rows  = $wpdb->get_results("SELECT title, content FROM $table WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
        if (empty($rows)) return '';
        $out = "=== CHÍNH SÁCH & QUY ĐỊNH ===\n";
        foreach ($rows as $r) {
            $out .= "\n## {$r->title}\n{$r->content}\n";
        }
        return $out;
    }

    public function handle_n8n_export_products()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $db = new AIF_N8N_DB();
        $products = $db->get_products();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=products_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header
        fputcsv($output, ['ID', 'Tên sản phẩm', 'Danh mục', 'SKU', 'Mô tả', 'Giá', 'Trạng thái']);

        foreach ($products as $p) {
            fputcsv($output, [
                $p->id,
                $p->product_name,
                $p->category,
                $p->sku,
                $p->description,
                $p->price,
                $p->status
            ]);
        }
        fclose($output);
        exit;
    }

    public function handle_n8n_import_products()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error('No file uploaded');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_send_json_error('Failed to open file');
        }

        // Skip BOM if exists
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
            rewind($handle);
        }

        $header = fgetcsv($handle); // Read header
        $db = new AIF_N8N_DB();
        $count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 3)
                continue;

            $product = [
                'product_name' => sanitize_text_field($data[1] ?? ''),
                'category' => sanitize_text_field($data[2] ?? ''),
                'sku' => sanitize_text_field($data[3] ?? ''),
                'description' => wp_kses_post($data[4] ?? ''),
                'price' => sanitize_text_field($data[5] ?? '0'),
                'status' => sanitize_text_field($data[6] ?? 'active'),
            ];

            // If ID matches, we update, else we insert
            $id = intval($data[0] ?? 0);
            if ($id > 0) {
                // Check if ID truly exists in DB or if it should be treated as new
                // upsert_product handles the check by product_id normally, but if we have numeric ID, we use it for update.
                $product['id'] = $id;
            }

            $db->upsert_product($product);
            $count++;
        }

        fclose($handle);
        wp_send_json_success("Đã nhập thành công $count sản phẩm");
    }
    public function handle_delete_queue_item()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error('Missing ID');
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';
        $item = $wpdb->get_row($wpdb->prepare("SELECT post_id FROM $table_queue WHERE id = %d", $id));

        $manager = new AIF_Facebook_Manager();
        $result = $manager->delete_queue_item($id);

        if ($result !== false) {
            // Revert status if no longer queued
            if ($item && !$manager->is_post_queued($item->post_id)) {
                $db = new AIF_DB();
                $post = $db->get($item->post_id);
                if ($post && ($post->status === 'Done' || $post->status === 'Queued')) {
                    $db->update($item->post_id, ['status' => 'Content updated']);
                }
            }
            wp_send_json_success('Đã xóa thành công!');
        } else {
            wp_send_json_error('Lỗi khi xóa khỏi hàng chờ');
        }
    }

    public function handle_retry_queue_item()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) {
            wp_send_json_error('Missing ID');
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'aif_posting_queue';

        // Chỉ retry nếu item đang ở trạng thái failed
        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_queue WHERE id = %d AND status LIKE 'failed%'",
            $id
        ));

        if (!$item) {
            wp_send_json_error('Item không tồn tại hoặc không ở trạng thái thất bại');
        }

        $result = $wpdb->update(
            $table_queue,
            ['status' => 'pending'],
            ['id' => $id]
        );

        if ($result !== false) {
            wp_send_json_success('Đã đặt lại trạng thái để thử đăng lại');
        } else {
            wp_send_json_error('Lỗi khi cập nhật trạng thái');
        }
    }

    public function handle_remove_post_from_queue()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error('Missing Post ID');
        }

        $manager = new AIF_Facebook_Manager();
        $result = $manager->delete_post_from_queue($post_id);

        if ($result !== false) {
            // Revert status as it was removed from all queues
            $db = new AIF_DB();
            $post = $db->get($post_id);
            if ($post && ($post->status === 'Done' || $post->status === 'Queued')) {
                $db->update($post_id, ['status' => 'Content updated']);
            }
            wp_send_json_success('Đã xóa khỏi hàng chờ thành công!');
        } else {
            wp_send_json_error('Lỗi khi xóa bài viết khỏi hàng chờ');
        }
    }

    public function handle_revise_content()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';

        if (empty($feedback)) {
            wp_send_json_error('Vui lòng nhập góp ý chỉnh sửa.');
        }

        if (empty($content)) {
            wp_send_json_error('Nội dung hiện tại đang trống, không có gì để sửa.');
        }

        $service = new AIF_AI_Generator();
        $response = $service->revise($title, $content, $feedback);

        if ($response && !empty($response['success'])) {
            wp_send_json_success($response);
        } else {
            $msg = isset($response['message']) ? $response['message'] : 'AI Revision Failed';
            wp_send_json_error($msg);
        }
    }

    public function handle_inline_edit_post()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $field = isset($_POST['field']) ? sanitize_key($_POST['field']) : '';
        $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';
        $last_updated_at = isset($_POST['last_updated_at']) ? sanitize_text_field($_POST['last_updated_at']) : '';

        if (!$post_id || !$field) {
            wp_send_json_error('Invalid parameters');
        }

        $db = new AIF_DB();
        $post = $db->get($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        // --- Concurrency Check ---
        if (!empty($last_updated_at) && $post->updated_at !== $last_updated_at) {
            wp_send_json_error('Dữ liệu đã bị thay đổi bởi người khác. Vui lòng tải lại trang.');
        }

        // --- Restriction Logic ---
        if ($post->status === 'Posted successfully' && $field !== 'status') {
            wp_send_json_error('Bài viết đã đăng thành công, không thể sửa.');
        }

        $fb_manager = new AIF_Facebook_Manager();
        if ($fb_manager->is_post_queued($post_id)) {
            wp_send_json_error('Bài viết đang trong hàng chờ, không thể sửa.');
        }

        // --- Sanitization & Field Mapping ---
        $allowed_fields = ['title', 'content', 'industry', 'description', 'status', 'note', 'owner', 'time_posting'];
        if (!in_array($field, $allowed_fields)) {
            wp_send_json_error('Field not allowed for inline edit');
        }

        $update_data = [];
        $status_msg = 'Updated successfully';
        switch ($field) {
            case 'content':
            case 'description':
                $update_data[$field] = wp_kses_post($value);
                break;
            case 'time_posting':
                if ($value) {
                    $update_data['time_posting'] = str_replace('T', ' ', $value) . ':00';
                } else {
                    $update_data['time_posting'] = '0000-00-00 00:00:00';
                }
                break;
            case 'status':
                $allowed_statuses = ['To do', 'Content updated', 'Done', 'Posted successfully'];
                $value = sanitize_text_field($value);
                if (!in_array($value, $allowed_statuses)) {
                    wp_send_json_error('Invalid status');
                }

                // 1. "To do" -> "Posted" restriction
                if ($value === 'Posted successfully' && $post->status === 'To do') {
                    $stt_display = !empty($post->stt) ? $post->stt : $post->id;
                    wp_send_json_error("Bài viết STT $stt_display vẫn đang To do, không được đăng.");
                }

                // 2. Automatic Queueing for "Done"
                if ($value === 'Done' && $post->status !== 'Done') {
                    $targets = !empty($post->targets) ? json_decode($post->targets, true) : [];
                    if (!empty($targets)) {
                        $is_scheduled = false;
                        $now = current_time('mysql');
                        if (!empty($post->time_posting) && $post->time_posting !== '0000-00-00 00:00:00' && $post->time_posting > $now) {
                            $is_scheduled = true;
                        }

                        $queue_status = $is_scheduled ? 'scheduled' : 'pending';
                        $added_count = 0;
                        global $wpdb;
                        $table_queue = $wpdb->prefix . 'aif_posting_queue';

                        foreach ($targets as $target) {
                            $pid = isset($target['id']) ? intval($target['id']) : 0;
                            $platform = isset($target['platform']) ? $target['platform'] : 'facebook';

                            $exists = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_queue WHERE post_id = %d AND page_id = %d AND platform = %s AND status IN ('pending', 'scheduled', 'processing')",
                                $post_id,
                                $pid,
                                $platform
                            ));

                            if (!$exists) {
                                $fb_manager->add_to_queue($post_id, $pid, $queue_status, $platform);
                                $added_count++;
                            }
                        }

                        if ($added_count > 0) {
                            if (!$is_scheduled) {
                                $fb_manager->process_queue();
                                
                                // Fetch fresh state to see if it's already posted
                                $check_post = $db->get($post_id);
                                if ($check_post && $check_post->status === 'Posted successfully') {
                                    $has_fb = false;
                                    $has_web = false;
                                    foreach ($targets as $t) {
                                        if (($t['platform'] ?? 'facebook') === 'website') $has_web = true;
                                        else $has_fb = true;
                                    }
                                    if ($has_fb && $has_web) $status_msg = 'Đã đăng bài thành công lên Fanpage và Website!';
                                    elseif ($has_web) $status_msg = 'Đã đăng bài thành công lên Website!';
                                    else $status_msg = 'Đã đăng bài thành công lên Fanpage!';
                                } else {
                                    $status_msg = 'Đã thêm vào hàng chờ & đang đăng bài...';
                                }
                            } else {
                                $status_msg = 'Đã lên lịch đăng bài (' . $post->time_posting . ')';
                            }
                        } else {
                            $status_msg = 'Bài viết đã có trong hàng chờ.';
                        }
                    } else {
                        wp_send_json_error('Chưa chọn Fanpage! Vui lòng vào chi tiết bài viết và chọn ít nhất 1 Fanpage trước khi chuyển sang Done.');
                    }
                }
                $update_data['status'] = $value;
                break;
            default:
                $update_data[$field] = sanitize_text_field($value);
                break;
        }

        if (empty($update_data)) {
            wp_send_json_error('No data to update');
        }

        // Perform main update
        $result = $db->update($post_id, $update_data);

        // Fetch FRESH post data because process_queue or other hooks might have changed it
        $updated_post = $db->get($post_id);

        if ($result !== false) {
            $response_data = [
                'message' => $status_msg,
                'field' => $field,
                'value' => ($field === 'status') ? $updated_post->status : $value,
                'updated_at' => $updated_post->updated_at,
                'is_queued' => $fb_manager->is_post_queued($post_id) ? '1' : '0'
            ];

            if ($field === 'time_posting' && $updated_post->time_posting != '0000-00-00 00:00:00') {
                $response_data['formatted_value'] = date('d/m/Y H:i', strtotime($updated_post->time_posting));
            }

            wp_send_json_success($response_data);
        } else {
            wp_send_json_error('Database update failed');
        }
    }

    /**
     * Handle AI Feedback from posts-list.php
     */
    public function handle_apply_feedback()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_textarea_field(wp_unslash($_POST['feedback'])) : '';

        if (!$post_id || empty($feedback)) {
            wp_send_json_error('Missing parameters');
        }

        $db = new AIF_DB();
        $post = $db->get($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        // Logic check: Locked?
        $fb_manager = new AIF_Facebook_Manager();
        if ($post->status === 'Posted successfully' || $fb_manager->is_post_queued($post_id)) {
            wp_send_json_error('Bài viết đã bị khóa, không thể apply AI.');
        }

        // Call AI
        $service = new AIF_AI_Generator();
        // Construct detailed prompt data
        $ai_res = $service->revise($post->title, $post->content, $feedback);

        if ($ai_res && !empty($ai_res['success'])) {
            $new_title = $ai_res['generated_title'];
            $new_content = $ai_res['caption'];

            // Update DB
            $update = $db->update($post_id, [
                'title' => sanitize_text_field($new_title),
                'content' => wp_kses_post($new_content),
                'status' => 'Content updated' // Move to processing
            ]);

            if ($update !== false) {
                $updated_post = $db->get($post_id);
                wp_send_json_success([
                    'title' => $new_title,
                    'content' => $new_content,
                    'updated_at' => $updated_post->updated_at
                ]);
            } else {
                wp_send_json_error('Failed to update database with AI content.');
            }
        } else {
            $error = isset($ai_res['message']) ? $ai_res['message'] : 'AI Service Error';
            wp_send_json_error($error);
        }
    }

    /**
     * AJAX: Get taxonomies for a post type (for dynamic category loading)
     */
    public function handle_get_taxonomies()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : 'post';

        // Get post type label
        $pt_obj = get_post_type_object($post_type);
        $pt_label = $pt_obj ? $pt_obj->labels->singular_name : $post_type;

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $tax_name = '';
        foreach ($taxonomies as $tax) {
            if ($tax->hierarchical) {
                $tax_name = $tax->name;
                break;
            }
        }

        if (!$tax_name) {
            wp_send_json_success(['terms' => [], 'taxonomy' => '', 'post_type' => $post_type, 'post_type_label' => $pt_label, 'message' => 'No hierarchical taxonomy']);
            return;
        }

        $terms = get_terms(['taxonomy' => $tax_name, 'hide_empty' => false]);
        $result = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $result[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                ];
            }
        }

        wp_send_json_success(['terms' => $result, 'taxonomy' => $tax_name, 'post_type' => $post_type, 'post_type_label' => $pt_label]);
    }

    public function handle_fetch_metrics()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id)
            wp_send_json_error('Invalid ID');

        require_once AIF_PATH . 'includes/class-facebook-manager.php';
        $fb = new AIF_Facebook_Manager();
        $result = $fb->fetch_post_metrics($id);

        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function handle_fetch_all_metrics()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id)
            wp_send_json_error('Invalid Post ID');

        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}aif_post_results WHERE post_id = %d AND platform = 'facebook'",
            $post_id
        ));

        if (empty($results)) {
            wp_send_json_error('No Facebook results found for this post');
        }

        require_once AIF_PATH . 'includes/class-facebook-manager.php';
        $fb = new AIF_Facebook_Manager();

        $updated_data = [];
        foreach ($results as $row) {
            $res = $fb->fetch_post_metrics($row->id);
            if ($res['success']) {
                // Fetch the updated row from DB to get the most accurate formatted or raw data
                $updated_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT likes_count, comments_count, shares_count, metrics_updated_at FROM {$wpdb->prefix}aif_post_results WHERE id = %d",
                    $row->id
                ));
                if ($updated_row) {
                    $updated_data[$row->id] = [
                        'likes' => intval($updated_row->likes_count),
                        'comments' => intval($updated_row->comments_count),
                        'shares' => intval($updated_row->shares_count),
                        'updated_at' => $updated_row->metrics_updated_at ? date('H:i d/m', strtotime($updated_row->metrics_updated_at)) : 'Chưa có'
                    ];
                }
            }
        }

        wp_send_json_success([
            'message' => 'Updated ' . count($updated_data) . ' platform(s)',
            'updated_data' => $updated_data
        ]);
    }

    /**
     * AJAX: Get list of posts for dashboard KPI modal
     */
    public function handle_get_dashboard_list()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'all';
        $db = new AIF_DB();
        global $wpdb;

        $results = [];
        $title = "Danh sách bài viết";

        switch ($type) {
            case 'total':
                $results = $wpdb->get_results("SELECT id, title, status, created_at FROM {$wpdb->prefix}aif_posts ORDER BY created_at DESC LIMIT 100");
                $title = "Tất cả bài viết (100 bài gần nhất)";
                break;
            case 'todo':
                $results = $wpdb->get_results("SELECT id, title, status, created_at FROM {$wpdb->prefix}aif_posts WHERE status = 'To do' ORDER BY created_at DESC LIMIT 100");
                $title = AIF_Status::label('To do');
                break;
            case 'updated':
                $results = $wpdb->get_results("SELECT id, title, status, created_at FROM {$wpdb->prefix}aif_posts WHERE status = 'Content updated' ORDER BY created_at DESC LIMIT 100");
                $title = AIF_Status::label('Content updated');
                break;
            case 'done':
                $results = $wpdb->get_results("SELECT id, title, status, created_at FROM {$wpdb->prefix}aif_posts WHERE status = 'Done' ORDER BY created_at DESC LIMIT 100");
                $title = AIF_Status::label('Done');
                break;
            case 'posted':
                $results = $wpdb->get_results("
                    SELECT p.id, p.title, p.status, p.created_at 
                    FROM {$wpdb->prefix}aif_posts p
                    INNER JOIN {$wpdb->prefix}aif_post_results r ON r.post_id = p.id
                    GROUP BY p.id
                    ORDER BY p.created_at DESC 
                    LIMIT 100
                ");
                $title = "Bài viết Đã đăng thành công";
                break;
            case 'queue':
                $results = $wpdb->get_results("
                SELECT p.id, p.title, p.status, p.created_at 
                FROM {$wpdb->prefix}aif_posts p
                INNER JOIN {$wpdb->prefix}aif_posting_queue q ON q.post_id = p.id
                WHERE q.status IN ('pending', 'scheduled', 'processing')
                GROUP BY p.id
                ORDER BY p.created_at DESC 
                LIMIT 100
            ");
                $title = "Bài viết đang trong Hàng đợi";
                break;
            case 'failed':
                $results = $wpdb->get_results("
                    SELECT p.id, p.title, p.status, p.created_at,
                           GROUP_CONCAT(DISTINCT q.status ORDER BY q.id SEPARATOR '|||') as queue_errors,
                           GROUP_CONCAT(DISTINCT COALESCE(f.page_name, q.platform) ORDER BY q.id SEPARATOR ', ') as targets
                    FROM {$wpdb->prefix}aif_posts p
                    INNER JOIN {$wpdb->prefix}aif_posting_queue q ON q.post_id = p.id
                    LEFT JOIN {$wpdb->prefix}aif_facebook_pages f ON f.id = q.page_id
                    WHERE q.status LIKE 'failed%'
                    GROUP BY p.id
                    ORDER BY p.created_at DESC
                    LIMIT 100
                ");
                $title = "Bài viết đăng lỗi (Failed)";
                break;
            case 'pages':
                $results = $wpdb->get_results("SELECT id, page_name as title, '" . AIF_Status::CONNECTED . "' as status, created_at FROM {$wpdb->prefix}aif_facebook_pages ORDER BY created_at DESC");
                $title = "Danh sách Fanpage đã kết nối";
                break;
        }

        if (empty($results)) {
            wp_send_json_success([
                'title' => $title,
                'posts' => [],
                'type'  => $type
            ]);
        }

        // Format data for JS
        $data = [];
        foreach ($results as $row) {
            // Build error summary for failed type
            $error_summary = '';
            if ($type === 'failed' && !empty($row->queue_errors)) {
                $error_parts = [];
                foreach (explode('|||', $row->queue_errors) as $qs) {
                    if (strpos($qs, 'failed: ') === 0) {
                        $error_parts[] = trim(substr($qs, 8));
                    } elseif ($qs === 'failed_no_token') {
                        $error_parts[] = 'Token hết hạn';
                    } elseif ($qs === 'failed_no_post') {
                        $error_parts[] = 'Không tìm thấy bài';
                    } else {
                        $error_parts[] = $qs;
                    }
                }
                $error_summary = implode(' • ', array_unique($error_parts));
            }

            $data[] = [
                'id'           => $row->id,
                'title'        => $row->title ?: '(Không có tiêu đề)',
                'status'       => $type === 'failed'
                    ? ('⚠️ ' . ($row->targets ?: 'Lỗi đăng'))
                    : AIF_Status::label($row->status),
                'status_raw'   => $row->status,
                'status_class' => $type === 'failed' ? 'status-error' : AIF_Status::badge_class($row->status),
                'date'         => date('d/m H:i', strtotime($row->created_at)),
                'edit_url'     => admin_url('admin.php?page=ai-fanpage-post-detail&id=' . $row->id),
                'error_summary' => $error_summary,
            ];
        }

        wp_send_json_success([
            'title' => $title,
            'posts' => $data,
            'type' => $type
        ]);
    }

    public function handle_n8n_check_updates()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        global $wpdb;
        $table_messages = $wpdb->prefix . 'aif_n8n_messages';
        $table_chats    = $wpdb->prefix . 'aif_n8n_chats';

        // Get the ID of the latest message
        $latest = $wpdb->get_row("SELECT id, created_at FROM $table_messages ORDER BY id DESC LIMIT 1");
        $latest_id = $latest ? intval($latest->id) : 0;

        $result = [
            'latest_id'     => $latest_id,
            'latest_time'   => $latest ? $latest->created_at : '',
            'changed_chats' => [],
            'ready'         => false, // true only when AI has finished replying
            'unread_counts' => (new AIF_N8N_DB())->get_unread_counts(),
        ];

        // If client sends since_id, check for completed conversation cycles
        $since_id = isset($_POST['since_id']) ? intval($_POST['since_id']) : 0;
        if ($since_id > 0 && $latest_id > $since_id) {
            // Find session IDs that have new messages since since_id
            $changed_session_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT session_id FROM $table_messages WHERE id > %d",
                $since_id
            ));

            if (!empty($changed_session_ids)) {
                // Only include sessions where the LATEST message is from AI (cycle complete)
                $ready_session_ids = [];
                foreach ($changed_session_ids as $sid) {
                    $last_sender = $wpdb->get_var($wpdb->prepare(
                        "SELECT sender FROM $table_messages WHERE session_id = %d ORDER BY id DESC LIMIT 1",
                        $sid
                    ));
                    if ($last_sender === 'ai') {
                        $ready_session_ids[] = intval($sid);
                    }
                }

                if (!empty($ready_session_ids)) {
                    $placeholders = implode(',', array_fill(0, count($ready_session_ids), '%d'));
                    $result['changed_chats'] = $wpdb->get_results($wpdb->prepare(
                        "SELECT c.*, COALESCE(p.page_name, c.page_id) as page_name
                         FROM $table_chats c
                         LEFT JOIN {$wpdb->prefix}aif_facebook_pages p ON p.page_id = c.page_id
                         WHERE c.id IN ($placeholders)
                         ORDER BY c.updated_at DESC",
                        ...$ready_session_ids
                    ));
                    $result['ready'] = true;
                }
                // If no sessions are ready yet (AI still processing), don't update latest_id
                // so the next poll will check again
                if (!$result['ready']) {
                    $result['latest_id'] = $since_id; // keep old ID, will re-check next poll
                }
            }
        }

        wp_send_json_success($result);
    }

    public function handle_n8n_mark_chat_viewed()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if (!$id) wp_send_json_error('Invalid ID');
        $db = new AIF_N8N_DB();
        $db->mark_chat_viewed($id);
        wp_send_json_success(['marked' => $id]);
    }

    public function handle_n8n_mark_leads_viewed()
    {
        check_ajax_referer('aif_nonce', 'nonce');
        $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
        if (empty($ids)) wp_send_json_error('No IDs');
        $db = new AIF_N8N_DB();
        foreach ($ids as $id) {
            $db->mark_lead_viewed($id);
        }
        wp_send_json_success(['marked' => count($ids)]);
    }

    public function maybe_create_messages_table()
    {
        if (!is_admin())
            return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'aif_messages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';
            AIF_Activator::activate();
        }
    }

    public function handle_get_activity_feed()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        global $wpdb;

        // Lấy 15 hoạt động gần nhất từ queue + results
        $rows = $wpdb->get_results("
            SELECT 
                q.id,
                q.post_id,
                q.status,
                q.platform,
                q.created_at,
                p.title as post_title,
                fp.page_name,
                r.link,
                r.created_at as posted_at
            FROM {$wpdb->prefix}aif_posting_queue q
            LEFT JOIN {$wpdb->prefix}aif_posts p ON p.id = q.post_id
            LEFT JOIN {$wpdb->prefix}aif_facebook_pages fp ON fp.id = q.page_id
            LEFT JOIN {$wpdb->prefix}aif_post_results r ON r.post_id = q.post_id AND r.target_id = q.page_id
            ORDER BY q.id DESC
            LIMIT 15
        ");

        $activities = [];
        foreach ($rows as $row) {
            $activities[] = [
                'id'         => $row->id,
                'post_id'    => $row->post_id,
                'title'      => $row->post_title ?: '(Không có tiêu đề)',
                'status'     => $row->status,
                'platform'   => $row->platform,
                'page_name'  => $row->page_name ?: 'Fanpage',
                'link'       => $row->link,
                'time'       => $row->posted_at ?: $row->created_at,
            ];
        }

        wp_send_json_success($activities);
    }

    public function handle_suggest_time()
    {
        check_ajax_referer('aif_nonce', 'nonce');

        $title    = isset($_POST['title'])    ? sanitize_text_field(wp_unslash($_POST['title']))    : '';
        $content  = isset($_POST['content'])  ? wp_kses_post(wp_unslash($_POST['content']))         : '';
        $industry = isset($_POST['industry']) ? sanitize_text_field(wp_unslash($_POST['industry'])) : '';
        $platform = isset($_POST['platform']) ? sanitize_text_field(wp_unslash($_POST['platform'])) : 'facebook';

        if (empty($title) && empty($content)) {
            wp_send_json_error('Cần tiêu đề hoặc nội dung để gợi ý thời gian.');
        }

        $service = new AIF_AI_Generator();
        $suggestions = $service->suggest_time($title, $content, $industry, $platform);

        if (!empty($suggestions)) {
            wp_send_json_success(['suggestions' => $suggestions]);
        } else {
            wp_send_json_error('AI không thể đưa ra gợi ý lúc này. Vui lòng thử lại.');
        }
    }
}

// Initialize Plugin
AI_Fanpage::get_instance();

/**
 * Register REST API hooks
 */
add_action('rest_api_init', function () {
    $handler = new AIF_Webhook_Handler();

    register_rest_route('ai-fanpage/v1', '/webhook', [
        [
            'methods' => 'GET',
            'callback' => [$handler, 'handle_verification'],
            'permission_callback' => '__return_true',
        ],
        [
            'methods' => 'POST',
            'callback' => [$handler, 'handle_message'],
            'permission_callback' => '__return_true',
        ],
    ]);

    register_rest_route('ai-fanpage/v1', '/n8n-chat', [
        [
            'methods' => 'POST',
            'callback' => [new AIF_N8N_Handler(), 'handle_chat'],
            'permission_callback' => '__return_true',
        ],
    ]);
});
