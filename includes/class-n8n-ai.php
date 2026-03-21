<?php
/**
 * AI Logic Class for N8N Integration using Gemini
 */
class AIF_N8N_AI
{
    private $engine;
    private $gemini_key;
    private $gemini_model;
    private $openai_key;
    private $openai_model;

    public function __construct()
    {
        // Đọc key từ bảng aif_settings (không dùng wp_options)
        $this->engine       = AIF_Settings::get_ai_provider();
        $this->gemini_key   = AIF_Settings::get_gemini_key();
        $this->gemini_model = AIF_Settings::get_gemini_model();
        $this->openai_key   = AIF_Settings::get_openai_key();
        $this->openai_model = AIF_Settings::get('ai_openai_model', 'gpt-4-turbo-preview');
    }

    /**
     * Generate AI Response
     */
    public function generate_response($user_message, $session, $products, $history = [])
    {
        $product_context  = $this->format_products($products);
        $collected_data   = json_decode($session->collected_data, true);
        $current_field    = $session->current_field;
        $history_context  = $this->format_history($history);
        $custom_prompt    = get_option('aif_n8n_system_prompt', '');
        $cs_info          = get_option('aif_n8n_cs_info', '');
        $customer_name    = !empty($session->customer_name) ? $session->customer_name : 'Khách hàng';
        $policy_context   = AI_Fanpage::get_active_policies_text();

        $system_prompt = $this->get_system_prompt($product_context, $collected_data, $current_field, $history_context, $custom_prompt, $cs_info, $customer_name, $policy_context);

        // ── Semantic Cache ────────────────────────────────────────────────────
        // Chỉ cache khi session chưa có state (khách mới hỏi FAQ, chưa để lại info)
        $is_stateless = empty($collected_data['phone'])
                     && empty($collected_data['address'])
                     && (empty($current_field) || $current_field === 'none')
                     && empty($history);

        if ($is_stateless) {
            $cache_key = 'aif_ai_' . md5(
                mb_strtolower(trim($user_message)) .
                md5($product_context) .
                md5($policy_context) .
                md5($custom_prompt)
            );
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        if ($this->engine === 'openai') {
            $result = $this->generate_openai_response($user_message, $system_prompt);
        } else {
            $result = $this->generate_gemini_response($user_message, $system_prompt);
        }

        // Lưu cache nếu đủ điều kiện và AI trả về thành công
        if ($is_stateless && $result && !empty($result['reply'])) {
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
        }

        return $result;
    }

    private function generate_gemini_response($user_message, $system_prompt)
    {
        if (empty($this->gemini_key))
            return false;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->gemini_model}:generateContent?key={$this->gemini_key}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => "System Prompt:\n$system_prompt\n\nCustomer message: \"$user_message\""]]
                ]
            ],
            'generationConfig' => ['responseMimeType' => 'application/json']
        ];

        $args = [
            'body' => json_encode($payload),
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json']
        ];

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response))
            return false;

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return json_decode($data['candidates'][0]['content']['parts'][0]['text'], true);
        }
        return false;
    }

    private function generate_openai_response($user_message, $system_prompt)
    {
        if (empty($this->openai_key))
            return false;

        $url = "https://api.openai.com/v1/chat/completions";

        $payload = [
            'model' => $this->openai_model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $user_message]
            ],
            'response_format' => ['type' => 'json_object']
        ];

        $args = [
            'body' => json_encode($payload),
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->openai_key
            ]
        ];

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response))
            return false;

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return json_decode($data['choices'][0]['message']['content'], true);
        }
        return false;
    }

    private function format_history($history)
    {
        if (empty($history)) {
            return "No previous messages.";
        }
        // $history is DESC order from DB, reverse for AI context
        $history = array_reverse($history);
        $output = "Recent History:\n";
        foreach ($history as $h) {
            $sender = strtoupper($h->sender);
            $output .= "$sender: {$h->message} (Intent: {$h->intent})\n";
        }
        return $output;
    }

    private function format_products($products)
    {
        if (empty($products)) {
            return "No products available.";
        }

        $output = "Data Table (Products):\n";
        $output .= "id | product_name | category | description | price | sku\n";
        foreach ($products as $p) {
            $output .= "{$p->id} | {$p->product_name} | {$p->category} | {$p->description} | {$p->price} | {$p->sku}\n";
        }
        return $output;
    }

    private function get_system_prompt($product_context, $collected_data, $current_field, $history_context, $custom_prompt = '', $cs_info = '', $customer_name = 'Khách hàng', $policy_context = '')
    {
        $collected_json = json_encode($collected_data);

        $base_prompt = !empty($custom_prompt) ? $custom_prompt : "
You are a premium sales assistant for a Vietnamese shop. You manage conversation flow, recommend products, score leads, and automate order creation.
You are currently chatting with: $customer_name.
        ";

        $policy_section = '';
        if (!empty($policy_context)) {
            $policy_section = "
---

## 📋 CHÍNH SÁCH & QUY ĐỊNH CỬA HÀNG
Khi khách hỏi về đổi trả, bảo hành, vận chuyển, thanh toán hoặc bất kỳ quy định nào — hãy trả lời **chính xác theo nội dung dưới đây**, không được bịa thêm:

$policy_context

---
";
        }

        return "
# Sales Assistant Specialist - Pro Ecommerce Logic

$base_prompt

$policy_section

---

## 📂 CUSTOMER SERVICE & CONTACT INFO
If the user asks for contact info, support, or who is handling the case, use this information:
$cs_info

---

## 📂 DATA SOURCES
Use ONLY these data points:
- **Product DB**: Lists products you are allowed to sell/discuss.
- **History**: Use context to avoid repeating or to follow up logically.
- **Collected Info**: Current known customer details.
- **CS Info**: Use this if the user asks for contact/support details.

---

## 🎯 INTENT CATEGORIES
Detect the most appropriate intent from this list:
- `ask_price`: Customer asks for price of specific item.
- `ask_product`: General questions about product features or availability.
- `product_consultation`: Customer asks for advice, best choice, or cheap options.
- `buy_product`: Clear intent to purchase (e.g., 'Tôi lấy cái này', 'Cho 1kg táo').
- `order_request`: Asking about shipping, delivery time, or payment methods.
- `general_question`: Greeting, general talk, or non-sales chat.
- `unknown`: Intent is unclear.

---

## 📈 LEAD SCORING & LEVEL
Calculate 0-100 score and map to Level:
- **High (80-100)**: Stage 3 (Providing info) or Stage 2 (Explicit buying decision).
- **Medium (40-79)**: Stage 1 (Active interest, asking details, comparing).
- **Low (0-39)**: Stage 1 (Greeting, non-product browsing, general questions).

---

## 🛒 ORDER & COLLECTION LOGIC

### STAGE 1: Discovery (Default)
Be helpful, natural, and friendly. Answer using the Product DB.

### STAGE 2: Purchase Detection
When intent is `buy_product`:
1. Parse `order_items` (product_id, quantity, price).
2. Confirm the selection to customer.
3. Start collecting info one-by-one: `name` -> `phone` -> `address`.

### STAGE 3: Info Collection
Move sequentialy. Skip fields already in `collected_info`.
Transition: `none` -> `name` -> `phone` -> `address` -> `complete`.

---

## 🚨 HUMAN HANDOFF (need_human)
Set `need_human: true` IMMEDIATELY IF:
- The customer asks for a product, category, or service NOT found in the **Product DB**. (Even if you can politely say \"no\", still set this to true so the owner can follow up).
- The question is outside the scope of your provided context.
- Technical issues, complaints, or explicit request for a human/at-person.
- AI cannot answer with 100% confidence.

---

## 📦 MANDATORY JSON OUTPUT FORMAT
Return ONLY this JSON structure. Strict!

```json
{
  \"reply\": \"Text response to user in Vietnamese\",
  \"intent\": \"intent_name\",
  \"lead_level\": \"High|Medium|Low\",
  \"lead_score\": 0,
  \"suggest_products\": [\"Tên sản phẩm khách hỏi\", \"ID_123\"], -- ALWAYS include any products or categories the customer asks about here, even if they are not buying yet.
  \"order_items\": [
    {\"product_id\": \"id_from_db\", \"product_name\": \"Tên sản phẩm\", \"quantity\": 1, \"price\": 10.50}
  ],
  \"customer_info\": {
    \"name\": \"...\",
    \"phone\": \"...\",
    \"address\": \"...\"
  },
  \"current_field\": \"name|phone|address|complete|none\",
  \"need_human\": false
}
```

---

## PRODUCT DATABASE:
$product_context

## CONVERSATION HISTORY:
$history_context

## CURRENT CUSTOMER STATE:
Info: $collected_json
Waiting for: $current_field
";
    }
}
