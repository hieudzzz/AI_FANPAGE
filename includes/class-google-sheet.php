<?php
class AIF_Google_Sheet
{
    public function __construct()
    {
        // Init
    }

    /**
     * Fetch data from a Google Sheet (Published as CSV) and sync to ai_post
     *
     * @param string $csv_url The URL of the published CSV
     * @return array Result stats ['created' => int, 'skipped' => int]
     */
    public function sync_from_csv($csv_url)
    {
        $response = wp_remote_get($csv_url);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return ['error' => 'Empty response from Google Sheet'];
        }

        $lines = explode("\n", $body);
        $header = str_getcsv(array_shift($lines)); // Use header to map columns? For now assume strict order provided in requirements.

        // Expected Order: Description, Platform, Time, Media
        // Or Map by header names if robust. Let's assume index for MVP but Header mapping is safer.

        $db = new AIF_DB();
        $stats = ['created' => 0, 'skipped' => 0];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (empty($row) || count($row) < 1)
                continue;

            // Mapping (Simple assumption based on user context)
            // 0: Description, 1: Platform, 2: Time, 3: Media Url
            $desc = isset($row[0]) ? trim($row[0]) : '';
            $platform = isset($row[1]) ? strtolower(trim($row[1])) : 'facebook';
            $time = isset($row[2]) ? trim($row[2]) : '';
            $media = isset($row[3]) ? trim($row[3]) : '';

            if (empty($desc))
                continue;

            // Duplicate Check
            if ($this->post_exists($desc, $platform)) {
                $stats['skipped']++;
                continue;
            }

            // Create Post via DB
            $result = $db->insert([
                'content' => $desc,
                'title' => wp_trim_words($desc, 10), // Auto-title
                'platform' => $platform,
                'schedule_time' => $time ? $time : '0000-00-00 00:00:00', // Basic check, better validation needed later
                'media_url' => $media,
                'status' => 'draft',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]);

            if ($result) {
                $stats['created']++;
            }
        }

        return $stats;
    }

    private function post_exists($content, $platform)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'aif_posts';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE content = %s AND platform = %s LIMIT 1",
            $content,
            $platform
        ));

        return $exists;
    }
}
