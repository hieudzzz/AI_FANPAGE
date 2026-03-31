<?php
class AIF_Google_Sheet
{
    public function __construct()
    {
        // Init
    }

    /**
     * Fetch data from a Google Sheet (Published as CSV) and sync to aif_posts
     * Expected CSV columns: Description, Platform, Time, Industry
     *
     * @param string $csv_url The URL of the published CSV
     * @return array Result stats ['created' => int, 'skipped' => int, 'error' => string]
     */
    public function sync_from_csv($csv_url)
    {
        // Validate URL scheme — chặn SSRF
        $parsed = wp_parse_url($csv_url);
        if (empty($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'], true)) {
            return ['error' => 'URL không hợp lệ.'];
        }

        $response = wp_remote_get($csv_url, ['timeout' => 15]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return ['error' => 'Empty response from Google Sheet'];
        }

        $lines  = explode("\n", $body);
        $header = str_getcsv(array_shift($lines));
        // header row đọc để bỏ qua, không dùng

        $db    = new AIF_DB();
        $stats = ['created' => 0, 'skipped' => 0];

        // Platform hợp lệ theo schema aif_posts
        $allowed_platforms = ['Facebook', 'Instagram', 'Website', 'Tiktok'];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (empty($row) || count($row) < 1) continue;

            // Mapping: 0=Description, 1=Platform, 2=Time (YYYY-MM-DD HH:MM:SS), 3=Industry
            $desc     = isset($row[0]) ? sanitize_textarea_field(trim($row[0])) : '';
            $platform = isset($row[1]) ? trim($row[1]) : 'Facebook';
            $time     = isset($row[2]) ? trim($row[2]) : '';
            $industry = isset($row[3]) ? sanitize_text_field(trim($row[3])) : '';

            if (empty($desc)) continue;

            // Normalize & validate platform
            $platform_cap = ucfirst(strtolower($platform));
            if (!in_array($platform_cap, $allowed_platforms, true)) {
                $platform_cap = 'Facebook';
            }

            // Validate time format — phải là YYYY-MM-DD HH:MM:SS hoặc bỏ trống
            $time_posting = '0000-00-00 00:00:00';
            if (!empty($time)) {
                $ts = strtotime($time);
                if ($ts !== false) {
                    $time_posting = date('Y-m-d H:i:s', $ts);
                }
            }

            // Duplicate Check (dùng đúng column option_platform)
            if ($this->post_exists($desc, $platform_cap)) {
                $stats['skipped']++;
                continue;
            }

            // Insert với đúng schema của bảng aif_posts
            $result = $db->insert([
                'description'     => $desc,
                'content'         => '',
                'title'           => wp_trim_words($desc, 10),
                'industry'        => $industry,
                'option_platform' => $platform_cap,
                'time_posting'    => $time_posting,
                'status'          => 'To do',
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ]);

            if ($result) {
                $stats['created']++;
            }
        }

        return $stats;
    }

    private function post_exists($description, $platform)
    {
        global $wpdb;
        $table  = $wpdb->prefix . 'aif_posts';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE description = %s AND option_platform = %s LIMIT 1",
            $description,
            $platform
        ));
        return (bool) $exists;
    }
}
