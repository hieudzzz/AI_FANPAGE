<?php
/**
 * AIF_Tones_DB — CRUD cho bảng wp_aif_tones
 */
if ( ! defined('ABSPATH') ) exit;

class AIF_Tones_DB {

    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'aif_tones';
    }

    // ── Lấy tất cả tones ──────────────────────────────────────
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC"
        );
    }

    // ── Lấy 1 tone theo id ────────────────────────────────────
    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d", $id
        ));
    }

    // ── Tạo mới ────────────────────────────────────────────────
    public function insert( $data ) {
        global $wpdb;
        $defaults = [
            'tone_key'    => '',
            'label'       => '',
            'description' => '',
            'style'       => '',
            'is_default' => 0,
            'sort_order' => $this->next_sort_order(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        $row = array_merge( $defaults, $data );

        if ( empty($row['tone_key']) ) {
            $row['tone_key'] = $this->generate_key( $row['label'] );
        }
        $row['tone_key'] = $this->ensure_unique_key( $row['tone_key'] );

        $result = $wpdb->insert( $this->table, $row );
        return $result ? $wpdb->insert_id : false;
    }

    // ── Cập nhật ───────────────────────────────────────────────
    public function update( $id, $data ) {
        global $wpdb;
        $data['updated_at'] = current_time('mysql');
        // Không cho sửa tone_key của tone mặc định
        $row = $this->get( $id );
        if ( $row && intval($row->is_default) === 1 ) {
            unset($data['tone_key'], $data['is_default']);
        }
        return $wpdb->update( $this->table, $data, ['id' => intval($id)] );
    }

    // ── Xóa (chỉ xóa được custom tone) ────────────────────────
    public function delete( $id ) {
        global $wpdb;
        $row = $this->get( $id );
        if ( ! $row || intval($row->is_default) === 1 ) return false;
        return $wpdb->delete( $this->table, ['id' => intval($id)] );
    }

    // ── Cập nhật thứ tự (drag & drop) ─────────────────────────
    public function reorder( array $ids ) {
        global $wpdb;
        foreach ( $ids as $order => $id ) {
            $wpdb->update( $this->table, ['sort_order' => intval($order) * 10], ['id' => intval($id)] );
        }
        return true;
    }

    // ── Helpers ────────────────────────────────────────────────
    private function next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table}");
        return intval($max) + 10;
    }

    private function generate_key( $label ) {
        // Bỏ emoji, lowercase, thay khoảng trắng bằng _
        $key = preg_replace('/[^\x20-\x7E]/', '', $label); // strip non-ASCII (emoji)
        $key = strtolower( trim($key) );
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_') ?: ('tone_' . time());
        return substr('custom_' . $key, 0, 80);
    }

    private function ensure_unique_key( $key ) {
        global $wpdb;
        $original = $key;
        $i = 2;
        while ( $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$this->table} WHERE tone_key = %s", $key) ) ) {
            $key = $original . '_' . $i++;
        }
        return $key;
    }

    // ── Tạo bảng ──────────────────────────────────────────────
    public static function create_table() {
        global $wpdb;
        $table           = $wpdb->prefix . 'aif_tones';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id          mediumint(9) NOT NULL AUTO_INCREMENT,
            tone_key    varchar(100) NOT NULL,
            label       varchar(100) NOT NULL,
            description varchar(255) DEFAULT '',
            style       text NOT NULL,
            is_default  tinyint(1) DEFAULT 0,
            sort_order  int DEFAULT 0,
            created_at  datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tone_key (tone_key)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ── Seed tones mặc định (gọi sau create_table) ────────────
    public static function seed_defaults() {
        global $wpdb;
        $table = $wpdb->prefix . 'aif_tones';
        if ( intval($wpdb->get_var("SELECT COUNT(*) FROM $table")) > 0 ) return;

        $defaults = [
            [ 'tone_key' => 'professional', 'label' => '💼 Chuyên nghiệp', 'description' => 'Lịch sự, rõ ràng, phù hợp doanh nghiệp.',       'style' => 'Viết với giọng văn chuyên nghiệp, lịch sự, rõ ràng và súc tích. Tránh dùng tiếng lóng hay cảm xúc thái quá.',                           'sort_order' => 10 ],
            [ 'tone_key' => 'friendly',     'label' => '😊 Thân thiện',    'description' => 'Gần gũi, ấm áp như nói chuyện với bạn bè.',      'style' => 'Viết theo phong cách thân thiện, gần gũi, dùng ngôn ngữ tự nhiên như đang nói chuyện với bạn bè. Thêm emoji vừa phải.',                    'sort_order' => 20 ],
            [ 'tone_key' => 'viral',        'label' => '🔥 Viral/Trendy',  'description' => 'Bắt trend, ngôn ngữ trẻ, dễ lan truyền.',        'style' => 'Dùng ngôn ngữ trẻ trung, bắt trend, meme, hashtag hot. Câu ngắn, mạnh, dễ share. Đánh vào cảm xúc mạnh.',                               'sort_order' => 30 ],
            [ 'tone_key' => 'storytelling', 'label' => '📖 Kể chuyện',    'description' => 'Kể câu chuyện hấp dẫn, lôi cuốn người đọc.',     'style' => 'Kể câu chuyện có mở đầu, diễn biến và kết. Dùng ngôi thứ nhất hoặc thứ ba, tạo hình ảnh sống động, khơi gợi cảm xúc.',               'sort_order' => 40 ],
            [ 'tone_key' => 'educational',  'label' => '🎓 Giáo dục',     'description' => 'Cung cấp kiến thức hữu ích, rõ ràng dễ hiểu.',   'style' => 'Truyền tải kiến thức rõ ràng, súc tích. Dùng gạch đầu dòng hoặc số thứ tự. Giải thích đơn giản, dễ hiểu cho người không chuyên.',    'sort_order' => 50 ],
            [ 'tone_key' => 'promotional',  'label' => '📣 Quảng cáo',    'description' => 'Thuyết phục, nhấn mạnh lợi ích, kêu gọi hành động.', 'style' => 'Tập trung vào lợi ích, giá trị cho khách hàng. Nhấn mạnh điểm khác biệt, tạo urgency nhẹ. Có CTA rõ ràng cuối bài.',              'sort_order' => 60 ],
            [ 'tone_key' => 'humorous',     'label' => '😂 Hài hước',     'description' => 'Vui vẻ, hóm hỉnh, tạo tiếng cười.',              'style' => 'Dùng hài hước, wordplay, tình huống bất ngờ để tạo tiếng cười. Nhẹ nhàng, không xúc phạm. Vẫn truyền tải được thông điệp chính.',    'sort_order' => 70 ],
            [ 'tone_key' => 'luxury',       'label' => '✨ Cao cấp',      'description' => 'Sang trọng, tinh tế, dành cho thương hiệu cao cấp.', 'style' => 'Ngôn ngữ tinh tế, sang trọng. Dùng từ ngữ cẩn thận, hình ảnh ẩn dụ đẹp. Tránh ngôn ngữ bình dân. Nhấn mạnh sự độc quyền.',       'sort_order' => 80 ],
        ];

        $db = new self();
        foreach ( $defaults as $t ) {
            $t['is_default'] = 1;
            $db->insert($t);
        }
    }
}
