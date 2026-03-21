<?php
/**
 * AIF_Settings - Quản lý cài đặt plugin, lưu vào bảng DB riêng (aif_settings)
 * Không dùng wp_options để tránh lẫn với settings hệ thống WordPress.
 */
class AIF_Settings
{
    private static $table = null;
    private static $cache = [];

    /**
     * Lấy tên bảng đầy đủ (có prefix)
     */
    private static function table()
    {
        if (self::$table === null) {
            global $wpdb;
            self::$table = $wpdb->prefix . 'aif_settings';
        }
        return self::$table;
    }

    /**
     * Tạo bảng aif_settings (gọi khi activate plugin)
     */
    public static function create_table()
    {
        global $wpdb;
        $table = self::table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_key varchar(191) NOT NULL,
            setting_value longtext DEFAULT NULL,
            autoload tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Lấy giá trị setting
     *
     * @param string $key
     * @param mixed  $default Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public static function get($key, $default = '')
    {
        // Trả về từ cache nếu có
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        global $wpdb;
        $table = self::table();

        $value = $wpdb->get_var(
            $wpdb->prepare("SELECT setting_value FROM $table WHERE setting_key = %s LIMIT 1", $key)
        );

        if ($value === null) {
            return $default;
        }

        // Thử unserialize nếu là dữ liệu phức tạp
        $unserialized = maybe_unserialize($value);
        self::$cache[$key] = $unserialized;

        return $unserialized;
    }

    /**
     * Lưu giá trị setting (INSERT hoặc UPDATE)
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $autoload 1 = load khi init, 0 = không
     * @return bool
     */
    public static function set($key, $value, $autoload = 1)
    {
        global $wpdb;
        $table = self::table();

        // Serialize nếu là array/object
        $serialized = maybe_serialize($value);

        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE setting_key = %s LIMIT 1", $key)
        );

        if ($existing) {
            $result = $wpdb->update(
                $table,
                ['setting_value' => $serialized, 'updated_at' => current_time('mysql')],
                ['setting_key' => $key],
                ['%s', '%s'],
                ['%s']
            );
        } else {
            $result = $wpdb->insert(
                $table,
                [
                    'setting_key'   => $key,
                    'setting_value' => $serialized,
                    'autoload'      => (int) $autoload,
                    'created_at'    => current_time('mysql'),
                    'updated_at'    => current_time('mysql'),
                ],
                ['%s', '%s', '%d', '%s', '%s']
            );
        }

        // Xóa cache để lần sau lấy fresh
        unset(self::$cache[$key]);

        return $result !== false;
    }

    /**
     * Xóa một setting
     *
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        global $wpdb;
        $table = self::table();

        unset(self::$cache[$key]);

        return $wpdb->delete($table, ['setting_key' => $key], ['%s']) !== false;
    }

    /**
     * Lấy tất cả settings (dạng key => value)
     *
     * @return array
     */
    public static function all()
    {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results("SELECT setting_key, setting_value FROM $table");

        $result = [];
        foreach ($rows as $row) {
            $result[$row->setting_key] = maybe_unserialize($row->setting_value);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Helpers tiện lợi cho các key thông dụng
    // -------------------------------------------------------------------------

    /** Lấy Gemini API Key */
    public static function get_gemini_key()
    {
        return self::get('ai_gemini_key', '');
    }

    /** Lấy model Gemini đang dùng */
    public static function get_gemini_model()
    {
        return self::get('ai_gemini_model', 'gemini-2.0-flash');
    }

    /** Lấy AI provider đang chọn */
    public static function get_ai_provider()
    {
        return self::get('ai_provider', 'gemini');
    }

    /** Lấy OpenAI API Key (nếu sau này cần) */
    public static function get_openai_key()
    {
        return self::get('ai_openai_key', '');
    }

    /** Lấy Anthropic API Key (nếu sau này cần) */
    public static function get_anthropic_key()
    {
        return self::get('ai_anthropic_key', '');
    }

    /** Lấy model Anthropic đang dùng */
    public static function get_anthropic_model()
    {
        return self::get('ai_anthropic_model', 'claude-3-5-sonnet-20241022');
    }

    /** Lấy model OpenAI đang dùng */
    public static function get_openai_model()
    {
        return self::get('ai_openai_model', 'gpt-4o');
    }
}
