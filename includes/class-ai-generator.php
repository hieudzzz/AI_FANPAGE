<?php
class AIF_AI_Generator
{

    public function __construct()
    {
        // Configuration is managed centrally via AIF_Settings
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Generate content based on context and platform.
     * Automatically uses the AI provider configured in Settings.
     */
    /**
     * Tone definitions: key => [label, desc, style]
     */
    private static $tone_map = [
        'ban_hang'      => ['label' => '🛒 Bán hàng',      'desc' => 'Tập trung vào lợi ích sản phẩm, tạo cảm giác cấp bách. Dùng nhiều dấu chấm than, bullet point và CTA mạnh mẽ.',         'style' => 'Giọng văn bán hàng mạnh mẽ, tập trung vào lợi ích sản phẩm, tạo cảm giác cấp bách, CTA rõ ràng. Dùng nhiều dấu chấm than và bullet point.'],
        'viral'         => ['label' => '🔥 Viral',          'desc' => 'Gây sốc, kích thích tò mò bằng sự thật ít người biết. Ngắn gọn, mạnh mẽ — người đọc muốn chia sẻ ngay.',               'style' => 'Giọng văn gây sốc, kích thích tò mò, dùng sự thật ít người biết, đặt câu hỏi khiêu khích. Ngắn gọn, mạnh mẽ, chia sẻ nhiều.'],
        'kien_thuc'     => ['label' => '📚 Kiến thức',      'desc' => 'Giọng chuyên gia, có số liệu & dẫn chứng cụ thể. Phân tích sâu, cấu trúc rõ ràng, cung cấp giá trị thực sự.',           'style' => 'Giọng văn chuyên gia, uy tín, có số liệu và dẫn chứng cụ thể. Phân tích sâu, cấu trúc rõ ràng, cung cấp giá trị thực.'],
        'storytelling'  => ['label' => '📖 Storytelling',   'desc' => 'Kể câu chuyện có nhân vật, diễn biến và bài học. Giọng ấm áp, chân thực — kéo người đọc vào câu chuyện.',               'style' => 'Kể câu chuyện cảm xúc, có nhân vật, có diễn biến và bài học. Giọng văn ấm áp, chân thực, kéo người đọc vào câu chuyện.'],
        'hai_huoc'      => ['label' => '😄 Hài hước',       'desc' => 'Vui vẻ, hài hước với so sánh bất ngờ. Nhẹ nhàng nhưng có điểm nhấn — khiến người đọc mỉm cười và nhớ lâu.',            'style' => 'Giọng văn vui vẻ, hài hước, dùng sự so sánh bất ngờ và meme. Nhẹ nhàng nhưng có điểm nhấn, khiến người đọc mỉm cười.'],
        'chuyen_nghiep' => ['label' => '💼 Chuyên nghiệp', 'desc' => 'Lịch sự, trau chuốt — phù hợp môi trường doanh nghiệp. Thuyết phục, đáng tin cậy, không dùng ngôn ngữ thông tục.',    'style' => 'Giọng văn chuyên nghiệp, lịch sự, phù hợp môi trường doanh nghiệp. Ngôn ngữ trau chuốt, thuyết phục, đáng tin cậy.'],
    ];

    /** Lấy tones mặc định (không có custom) — giữ lại để backward compat */
    public static function get_tones()
    {
        return self::$tone_map;
    }

    /**
     * Lấy tất cả tones từ DB (bảng aif_tones).
     * Fallback về $tone_map cũ nếu DB chưa sẵn sàng.
     * Trả về array: [ tone_key => ['label', 'description', 'style', 'custom'] ]
     */
    public static function get_all_tones()
    {
        if ( class_exists('AIF_Tones_DB') ) {
            $db    = new AIF_Tones_DB();
            $rows  = $db->get_all();
            if ( ! empty($rows) ) {
                $result = [];
                foreach ( $rows as $t ) {
                    $result[ $t->tone_key ] = [
                        'id'          => $t->id,
                        'label'       => $t->label,
                        'desc'        => $t->description,   // alias cho backward compat
                        'description' => $t->description,
                        'style'       => $t->style,
                        'custom'      => intval($t->is_default) === 0 ? true : false,
                    ];
                }
                return $result;
            }
        }
        // Fallback nếu DB chưa migrate
        $custom = get_option('aif_custom_tones', []);
        return array_merge(self::$tone_map, is_array($custom) ? $custom : []);
    }

    public function generate($raw_content, $platform, $tone = '')
    {
        $industry    = '';
        $description = '';

        if (is_array($raw_content)) {
            $industry    = isset($raw_content['industry'])    ? $raw_content['industry']    : '';
            $description = isset($raw_content['description']) ? $raw_content['description'] : '';
        } else {
            $description = $raw_content;
        }

        // Tone instruction
        $all_tones = self::get_all_tones();
        $tone_instruction = '';
        if ($tone && isset($all_tones[$tone])) {
            $tone_instruction = $all_tones[$tone]['style'];
        }

        // Prompt Engineering
        $seed              = wp_hash(microtime() . $description);
        $uniqueness_factor = substr($seed, 0, 8);

        $prompt  = "Bạn là một Chuyên gia Nghiên cứu Thị trường & Content Strategist hàng đầu.\n";
        $prompt .= "NHIỆM VỤ: Sáng tạo Tiêu đề (Title) và Nội dung (Content) cho $platform chuyên sâu và khác biệt.\n\n";
        $prompt .= "--- THÔNG TIN ĐẦU VÀO ---\n";
        if ($industry) {
            $prompt .= "- Ngành hàng (Industry): $industry\n";
        }
        $prompt .= "- Chủ đề/Yêu cầu: $description\n";
        $prompt .= "- Uniqueness Seed: $uniqueness_factor\n\n";
        $prompt .= "--- YÊU CẦU CẤU TRÚC ---\n";
        $prompt .= "1. TIÊU ĐỀ (TITLE): Hook cực mạnh.\n";
        $prompt .= "2. NỘI DUNG (CONTENT): PHẦN MỞ (Hook) -> PHẦN THÂN (Value/Insight) -> PHẦN KẾT (Solution) -> CTA (Mạnh mẽ).\n";
        if ($tone_instruction) {
            $prompt .= "3. PHONG CÁCH VIẾT: $tone_instruction\n";
        } else {
            $prompt .= "3. PHONG CÁCH: Nghiên cứu chuyên sâu, Sáng tạo đột phá, Insight đắt giá.\n";
        }

        $system = 'You are a professional content creator. Respond in JSON format with keys: "title", "content", "hashtags". Language: Vietnamese. Return ONLY the JSON object.';

        $ai_response = $this->call_ai($prompt, $system, true);

        if (is_array($ai_response) && isset($ai_response['error'])) {
            return [
                'caption' => 'LỖI AI: ' . $ai_response['error'],
                'success' => false,
            ];
        }

        if ($ai_response) {
            return $this->parse_ai_response($ai_response, $platform, $description, $industry);
        }

        return [
            'caption' => 'Gặp lỗi khi kết nối với AI API.',
            'success' => false,
        ];
    }

    /**
     * Generate 3 variations for the user to choose from.
     * Calls generate() 3 times with slight uniqueness variation.
     */
    public function generate_variations($raw_content, $platform, $tone = '')
    {
        $industry    = '';
        $description = '';
        if (is_array($raw_content)) {
            $industry    = isset($raw_content['industry'])    ? $raw_content['industry']    : '';
            $description = isset($raw_content['description']) ? $raw_content['description'] : '';
        } else {
            $description = $raw_content;
        }

        $all_tones = self::get_all_tones();
        $tone_instruction = '';
        if ($tone && isset($all_tones[$tone])) {
            $tone_instruction = $all_tones[$tone]['style'];
        }

        // ── 1 API call duy nhất, yêu cầu AI trả 3 variations rõ ràng khác nhau ──
        $prompt  = "Bạn là Chuyên gia Content Marketing cho $platform.\n";
        $prompt .= "NHIỆM VỤ: Viết ĐÚNG 3 phiên bản bài viết HOÀN TOÀN KHÁC NHAU về cách tiếp cận, góc nhìn và phong cách.\n\n";
        $prompt .= "--- THÔNG TIN ---\n";
        if ($industry) $prompt .= "- Ngành: $industry\n";
        $prompt .= "- Yêu cầu: $description\n";
        if ($tone_instruction) $prompt .= "- Phong cách chung: $tone_instruction\n";
        $prompt .= "\n--- YÊU CẦU 3 PHIÊN BẢN ---\n";
        $prompt .= "Phiên bản 1 (STORYTELLING): Dùng kể chuyện cá nhân, cảm xúc để kéo người đọc.\n";
        $prompt .= "Phiên bản 2 (DATA & INSIGHT): Dùng số liệu, sự thật thú vị, góc nhìn chuyên gia.\n";
        $prompt .= "Phiên bản 3 (THỰC CHIẾN): Hướng dẫn cụ thể, tips hành động ngay, CTA mạnh mẽ.\n\n";
        $prompt .= "QUAN TRỌNG: Mỗi phiên bản phải có hook mở đầu hoàn toàn khác nhau. Không được copy paste giữa các phiên bản.\n";

        $system = 'You are a professional Vietnamese content creator. Return ONLY a valid JSON array with exactly 3 objects. Each object must have keys: "title" (string), "content" (string), "hashtags" (string). No markdown, no explanation outside JSON.';

        $ai_response = $this->call_ai($prompt, $system, true);

        if (!$ai_response || (is_array($ai_response) && isset($ai_response['error']))) {
            return [];
        }

        // Parse JSON array từ AI response
        $raw = $ai_response;
        if (is_string($raw)) {
            // Loại bỏ markdown fences nếu có
            $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw = preg_replace('/\s*```$/', '', $raw);
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            // Một số provider trả về array trực tiếp hoặc object có key 'variations'
            $decoded = isset($raw['variations']) ? $raw['variations'] : $raw;
        } else {
            $decoded = null;
        }

        $variations = [];
        if (is_array($decoded)) {
            // Nếu AI trả về object đơn {title,content,hashtags} thay vì array → bọc lại
            if (isset($decoded['title'])) {
                $decoded = [$decoded];
            }
            foreach ($decoded as $item) {
                if (!is_array($item) || empty($item['content'])) continue;
                $variations[] = [
                    'success'         => true,
                    'generated_title' => isset($item['title'])    ? $item['title']    : '',
                    'caption'         => isset($item['content'])  ? $item['content']  : '',
                    'hashtags'        => isset($item['hashtags']) ? $item['hashtags'] : '',
                    'platform'        => $platform,
                ];
            }
        }

        // Fallback: nếu AI vẫn không trả về array, thử parse 3 lần riêng với tone khác nhau
        if (empty($variations)) {
            $fallback_tones = ['storytelling', 'data-driven', 'actionable'];
            foreach ($fallback_tones as $ft) {
                $result = $this->generate($raw_content, $platform, $tone ?: $ft);
                if ($result && !empty($result['success'])) {
                    $variations[] = $result;
                }
                if (count($variations) >= 3) break;
                usleep(300000); // 300ms delay để tránh cache
            }
        }

        return $variations;
    }

    /**
     * Revise content based on user feedback.
     * Automatically uses the AI provider configured in Settings.
     */
    public function revise($title, $content, $feedback)
    {
        $prompt  = "Dưới đây là bài viết hiện tại:\n";
        $prompt .= "---\nTITLE: $title\nCONTENT:\n$content\n---\n\n";
        $prompt .= "Góp ý chỉnh sửa từ người dùng: $feedback\n\n";
        $prompt .= "Hãy chỉnh sửa bài viết chuyên nghiệp hơn. Trả về đúng định dạng:\nTITLE: <tiêu đề>\nCONTENT:\n<nội dung>";

        $system = 'You are a professional content editor. Respond ONLY in format: TITLE: <title>\nCONTENT:\n<content>. No JSON. Vietnamese.';

        $ai_response = $this->call_ai($prompt, $system, false);

        if (!$ai_response || (is_array($ai_response) && isset($ai_response['error']))) {
            $msg = (is_array($ai_response) && isset($ai_response['error'])) ? $ai_response['error'] : 'AI API call failed';
            return ['success' => false, 'message' => $msg];
        }

        return $this->parse_revise_response($ai_response);
    }

    /**
     * Suggest 3 optimal posting times based on content and industry.
     */
    public function suggest_time($title, $content, $industry, $platform, $current_time = '')
    {
        if (empty($current_time)) {
            $current_time = current_time('mysql');
        }

        $prompt  = "Bạn là Chuyên gia Tối ưu hóa Tương tác mạng xã hội.\n";
        $prompt .= "Thời gian hiện tại của hệ thống: $current_time\n\n";
        $prompt .= "NHIỆM VỤ: Gợi ý 3 mốc thời gian đăng bài (YYYY-MM-DD HH:mm) tốt nhất để đạt tương tác cao nhất cho bài viết sau.\n";
        $prompt .= "QUY TẮC BẮT BUỘC: Các mốc thời gian phải ở trong TƯƠNG LAI (Sau thời điểm $current_time).\n\n";
        $prompt .= "--- THÔNG TIN BÀI VIẾT ---\n";
        $prompt .= "- Ngành hàng: $industry\n";
        $prompt .= "- Tiêu đề: $title\n";
        $prompt .= "- Nội dung: " . wp_trim_words($content, 50, '...') . "\n";
        $prompt .= "- Nền tảng: $platform\n\n";
        $prompt .= "--- YÊU CẦU ---\n";
        $prompt .= "1. Gợi ý 3 mốc thời gian cụ thể.\n";
        $prompt .= "2. Thời gian phải ở định dạng: YYYY-MM-DD HH:MM.\n";
        $prompt .= "3. Giải thích ngắn gọn lý do chọn mốc giờ đó (insight khách hàng).\n\n";
        $prompt .= "QUAN TRỌNG: Trả về duy nhất 1 JSON array gồm 3 objects. \n";
        $prompt .= "Ví dụ định dạng trả về:\n";
        $prompt .= "[\n  {\"time\": \"2024-03-25 09:00\", \"reason\": \"Mô tả lý do chọn mốc này...\"},\n  {\"time\": \"2024-03-25 15:30\", \"reason\": \"...\"},\n  {\"time\": \"2024-03-25 20:15\", \"reason\": \"...\"}\n]\n";
        $prompt .= "Mỗi object BẮT BUỘC có đúng 2 key: \"time\" (định dạng YYYY-MM-DD HH:mm) và \"reason\".";

        $system = 'You are a social media optimization expert. Return ONLY a valid JSON array of 3 objects with keys "time" and "reason". Language: Vietnamese.';

        $ai_response = $this->call_ai($prompt, $system, true);

        if (!$ai_response || (is_array($ai_response) && isset($ai_response['error']))) {
            return [];
        }

        // Parse JSON array
        $raw = $ai_response;
        if (is_string($raw)) {
            $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
            $raw = preg_replace('/\s*```$/', '', $raw);
            $decoded = json_decode($raw, true);
        } else {
            $decoded = $ai_response;
        }

        // If returned as { "suggestions": [...] } instead of [...]
        if (is_array($decoded) && count($decoded) === 1 && is_array(current($decoded))) {
            $decoded = current($decoded);
        }

        return is_array($decoded) ? array_values($decoded) : [];
    }

    // =========================================================================
    // ROUTING — chọn provider theo cài đặt
    // =========================================================================

    /**
     * Gọi AI provider đang được cấu hình.
     *
     * @param string $prompt      User prompt
     * @param string $system      System instruction
     * @param bool   $json_mode   true = yêu cầu JSON output (generate), false = plain text (revise)
     * @return string|array  string = raw AI text, array với key 'error' nếu lỗi
     */
    private function call_ai($prompt, $system, $json_mode)
    {
        $provider = AIF_Settings::get_ai_provider(); // 'gemini' | 'openai' | 'anthropic'

        switch ($provider) {
            case 'openai':
                return $this->call_openai($prompt, $system, $json_mode);
            case 'anthropic':
                return $this->call_anthropic($prompt, $system, $json_mode);
            case 'gemini':
            default:
                return $json_mode
                    ? $this->call_gemini($prompt)
                    : $this->call_gemini_revise($prompt);
        }
    }

    // =========================================================================
    // PROVIDERS
    // =========================================================================

    /** Gemini — JSON mode (generate) */
    private function call_gemini($prompt)
    {
        $api_key = AIF_Settings::get_gemini_key();
        if (empty($api_key)) {
            $api_key = defined('CRM_GEMINI_KEY') ? CRM_GEMINI_KEY : '';
        }
        if (empty($api_key)) {
            return ['error' => 'Chưa cấu hình Google Gemini API Key. Vui lòng vào Cài Đặt > AI Settings để nhập key.'];
        }

        $model = AIF_Settings::get_gemini_model();
        if (empty($model)) {
            $model = defined('CRM_AI_MODEL_GEMINI') ? CRM_AI_MODEL_GEMINI : 'gemini-2.0-flash';
        }

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        $body = [
            'system_instruction' => [
                'parts' => [['text' => 'You are a professional content creator. Respond in JSON format with keys: "title", "content", "hashtags". Language: Vietnamese. Return ONLY the JSON object.']],
            ],
            'contents'           => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig'   => ['temperature' => 0.8, 'responseMimeType' => 'application/json'],
        ];

        return $this->do_post($url, $body, [], 'gemini');
    }

    /** Gemini — plain text mode (revise) */
    private function call_gemini_revise($prompt)
    {
        $api_key = AIF_Settings::get_gemini_key();
        if (empty($api_key)) {
            $api_key = defined('CRM_GEMINI_KEY') ? CRM_GEMINI_KEY : '';
        }
        if (empty($api_key)) {
            return ['error' => 'Chưa cấu hình Google Gemini API Key.'];
        }

        $model = AIF_Settings::get_gemini_model();
        if (empty($model)) {
            $model = defined('CRM_AI_MODEL_GEMINI') ? CRM_AI_MODEL_GEMINI : 'gemini-2.0-flash';
        }

        $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        $body = [
            'system_instruction' => [
                'parts' => [['text' => 'You are a professional content editor. Respond ONLY in format: TITLE: <title>\nCONTENT:\n<content>. No JSON. Vietnamese.']],
            ],
            'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7],
        ];

        return $this->do_post($url, $body, [], 'gemini');
    }

    /** OpenAI — Chat Completions */
    private function call_openai($prompt, $system, $json_mode)
    {
        $api_key = AIF_Settings::get_openai_key();
        if (empty($api_key)) {
            return ['error' => 'Chưa cấu hình OpenAI API Key. Vui lòng vào Cài Đặt > AI Settings để nhập key.'];
        }

        $model = AIF_Settings::get_openai_model();
        if (empty($model)) {
            $model = 'gpt-4o';
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $body = [
            'model'       => $model,
            'temperature' => $json_mode ? 0.8 : 0.7,
            'messages'    => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $prompt],
            ],
        ];

        if ($json_mode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ];

        return $this->do_post($url, $body, $headers, 'openai');
    }

    /** Anthropic Claude — Messages API */
    private function call_anthropic($prompt, $system, $json_mode)
    {
        $api_key = AIF_Settings::get_anthropic_key();
        if (empty($api_key)) {
            return ['error' => 'Chưa cấu hình Anthropic API Key. Vui lòng vào Cài Đặt > AI Settings để nhập key.'];
        }

        $model = AIF_Settings::get_anthropic_model();
        if (empty($model)) {
            $model = 'claude-3-5-sonnet-20241022';
        }

        // Nếu json_mode, thêm hướng dẫn vào prompt
        $user_content = $json_mode
            ? $prompt . "\n\nRemember: respond ONLY with a valid JSON object with keys: title, content, hashtags."
            : $prompt;

        $url  = 'https://api.anthropic.com/v1/messages';
        $body = [
            'model'      => $model,
            'max_tokens' => 2048,
            'system'     => $system,
            'messages'   => [
                ['role' => 'user', 'content' => $user_content],
            ],
        ];

        $headers = [
            'Content-Type'      => 'application/json',
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
        ];

        return $this->do_post($url, $body, $headers, 'anthropic');
    }

    // =========================================================================
    // HTTP HELPER
    // =========================================================================

    /**
     * Gửi POST request và trả về raw text từ AI.
     *
     * @param string $url
     * @param array  $body
     * @param array  $extra_headers  Headers bổ sung (ngoài Content-Type)
     * @param string $provider       'gemini' | 'openai' | 'anthropic'
     * @return string|array
     */
    private function do_post($url, $body, $extra_headers, $provider)
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $extra_headers);

        $response = wp_remote_post($url, [
            'body'      => json_encode($body),
            'headers'   => $headers,
            'timeout'   => 60,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        // Parse text từ response theo từng provider
        switch ($provider) {
            case 'gemini':
                if (isset($data['error'])) {
                    return ['error' => isset($data['error']['message']) ? $data['error']['message'] : 'Gemini Error'];
                }
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
                return ['error' => 'Gemini trả về kết quả rỗng.'];

            case 'openai':
                if (isset($data['error'])) {
                    return ['error' => isset($data['error']['message']) ? $data['error']['message'] : 'OpenAI Error'];
                }
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
                return ['error' => 'OpenAI trả về kết quả rỗng.'];

            case 'anthropic':
                if (isset($data['error'])) {
                    return ['error' => isset($data['error']['message']) ? $data['error']['message'] : 'Anthropic Error'];
                }
                if (isset($data['content'][0]['text'])) {
                    return $data['content'][0]['text'];
                }
                return ['error' => 'Anthropic trả về kết quả rỗng.'];
        }

        return ['error' => 'Unknown provider error.'];
    }

    // =========================================================================
    // RESPONSE PARSERS
    // =========================================================================

    private function parse_ai_response($raw_response, $platform, $description, $industry)
    {
        $json_str = $raw_response;

        if (preg_match('/```json(.*?)```/s', $raw_response, $matches)) {
            $json_str = trim($matches[1]);
        } elseif (preg_match('/```(.*?)```/s', $raw_response, $matches)) {
            $json_str = trim($matches[1]);
        }

        $start = strpos($json_str, '{');
        $end   = strrpos($json_str, '}');
        if ($start !== false && $end !== false) {
            $json_str = substr($json_str, $start, $end - $start + 1);
        }

        $data = json_decode($json_str, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['content'])) {
            return [
                'caption'         => $data['content'],
                'generated_title' => isset($data['title'])    ? $data['title']    : '',
                'hashtags'        => isset($data['hashtags']) ? $data['hashtags'] : "#$industry #AI",
                'success'         => true,
            ];
        }

        // Fallback: raw text as content
        return [
            'caption'         => $raw_response,
            'generated_title' => "Content for $industry",
            'hashtags'        => "#$industry #AI",
            'success'         => true,
        ];
    }

    private function parse_revise_response($raw)
    {
        $title   = '';
        $content = '';

        if (preg_match('/TITLE:\s*(.+)/i', $raw, $m)) {
            $title = trim($m[1]);
        }
        if (preg_match('/CONTENT:\s*\n([\s\S]+)/i', $raw, $m)) {
            $content = trim($m[1]);
        }
        if (empty($content)) {
            $content = $raw;
        }

        return [
            'generated_title' => $title,
            'caption'         => $content,
            'success'         => true,
        ];
    }
}
