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
    public function generate($raw_content, $platform)
    {
        $industry    = '';
        $description = '';

        if (is_array($raw_content)) {
            $industry    = isset($raw_content['industry'])    ? $raw_content['industry']    : '';
            $description = isset($raw_content['description']) ? $raw_content['description'] : '';
        } else {
            $description = $raw_content;
        }

        // Prompt Engineering
        $seed             = wp_hash(microtime() . $description);
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
        $prompt .= "3. PHONG CÁCH: Nghiên cứu chuyên sâu, Sáng tạo đột phá, Insight đắt giá.\n";

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
