<?php
if (!defined('ABSPATH')) exit;

// Xử lý save khi submit form
$saved = false;

if (isset($_POST['aif_settings_nonce']) && wp_verify_nonce($_POST['aif_settings_nonce'], 'aif_save_settings')) {

    $ai_provider     = sanitize_text_field($_POST['ai_provider'] ?? 'gemini');
    $gemini_key      = sanitize_text_field($_POST['ai_gemini_key'] ?? '');
    $openai_key      = sanitize_text_field($_POST['ai_openai_key'] ?? '');
    $anthropic_key   = sanitize_text_field($_POST['ai_anthropic_key'] ?? '');

    // Model: nếu chọn __custom__ thì lấy từ input tay
    $gemini_model_sel   = sanitize_text_field($_POST['ai_gemini_model'] ?? '');
    $gemini_model       = ($gemini_model_sel === '__custom__')
                            ? sanitize_text_field($_POST['ai_gemini_model_custom'] ?? 'gemini-2.0-flash')
                            : $gemini_model_sel;

    $openai_model_sel   = sanitize_text_field($_POST['ai_openai_model'] ?? '');
    $openai_model       = ($openai_model_sel === '__custom__')
                            ? sanitize_text_field($_POST['ai_openai_model_custom'] ?? 'gpt-4o')
                            : $openai_model_sel;

    $anthropic_model_sel = sanitize_text_field($_POST['ai_anthropic_model'] ?? '');
    $anthropic_model     = ($anthropic_model_sel === '__custom__')
                            ? sanitize_text_field($_POST['ai_anthropic_model_custom'] ?? 'claude-3-5-sonnet-20241022')
                            : $anthropic_model_sel;

    AIF_Settings::set('ai_provider', $ai_provider);
    if (!empty($gemini_model))   AIF_Settings::set('ai_gemini_model', $gemini_model);
    if (!empty($openai_model))   AIF_Settings::set('ai_openai_model', $openai_model);
    if (!empty($anthropic_model)) AIF_Settings::set('ai_anthropic_model', $anthropic_model);

    if (!empty($gemini_key))    AIF_Settings::set('ai_gemini_key', $gemini_key);
    if (!empty($openai_key))    AIF_Settings::set('ai_openai_key', $openai_key);
    if (!empty($anthropic_key)) AIF_Settings::set('ai_anthropic_key', $anthropic_key);

    $saved = true;
}

// Lấy giá trị hiện tại để hiển thị
$current_provider      = AIF_Settings::get_ai_provider();
$current_gemini_model  = AIF_Settings::get_gemini_model();
$current_openai_model  = AIF_Settings::get('ai_openai_model', 'gpt-4o');
$current_anthropic_model = AIF_Settings::get('ai_anthropic_model', 'claude-3-5-sonnet-20241022');
$has_gemini_key        = !empty(AIF_Settings::get_gemini_key());
$has_openai_key        = !empty(AIF_Settings::get_openai_key());
$has_anthropic_key     = !empty(AIF_Settings::get_anthropic_key());

$gemini_models = [
    'gemini-2.5-flash'  => 'Gemini 2.5 Flash (Nhanh, tiết kiệm)',
    'gemini-2.5-pro'    => 'Gemini 2.5 Pro (Mạnh nhất)',
    'gemini-2.0-flash'  => 'Gemini 2.0 Flash',
    'gemini-1.5-flash'  => 'Gemini 1.5 Flash',
    'gemini-1.5-pro'    => 'Gemini 1.5 Pro',
];

$openai_models = [
    'gpt-4o'            => 'GPT-4o (Khuyên dùng)',
    'gpt-4o-mini'       => 'GPT-4o Mini (Nhanh, rẻ)',
    'gpt-4-turbo'       => 'GPT-4 Turbo',
    'gpt-4'             => 'GPT-4',
    'gpt-3.5-turbo'     => 'GPT-3.5 Turbo (Rẻ nhất)',
];

$anthropic_models = [
    'claude-opus-4-5'              => 'Claude Opus 4.5 (Mạnh nhất)',
    'claude-sonnet-4-5'            => 'Claude Sonnet 4.5 (Khuyên dùng)',
    'claude-3-5-sonnet-20241022'   => 'Claude 3.5 Sonnet',
    'claude-3-5-haiku-20241022'    => 'Claude 3.5 Haiku (Nhanh, rẻ)',
    'claude-3-opus-20240229'       => 'Claude 3 Opus',
];
?>

<style>
    .aif-settings-page {
        max-width: 800px;
        padding: 20px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .aif-settings-page .aif-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,.05);
        margin-bottom: 24px;
        overflow: hidden;
    }

    .aif-settings-page .aif-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .aif-settings-page .aif-card-header h2 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .aif-settings-page .aif-card-body {
        padding: 24px;
    }

    .aif-settings-page .aif-form-group {
        margin-bottom: 20px;
    }

    .aif-settings-page .aif-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .aif-settings-page .aif-input,
    .aif-settings-page .aif-select {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 14px;
        color: #1e293b;
        background: #fff;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }

    .aif-settings-page .aif-input:focus,
    .aif-settings-page .aif-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        outline: none;
    }

    .aif-settings-page .aif-help-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
    }

    .aif-settings-page .aif-key-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-left: 8px;
    }

    .aif-settings-page .key-set {
        background: #dcfce7;
        color: #166534;
    }

    .aif-settings-page .key-missing {
        background: #fee2e2;
        color: #991b1b;
    }

    .aif-settings-page .aif-btn-save {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: #3b82f6;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
    }

    .aif-settings-page .aif-btn-save:hover {
        background: #2563eb;
    }

    .aif-settings-page .aif-notice {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .aif-settings-page .aif-notice-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .aif-settings-page .aif-notice-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .aif-settings-page .aif-section-desc {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 16px 0;
    }

    .aif-settings-page .provider-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .aif-settings-page .provider-tab {
        flex: 1;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        transition: all .2s;
        background: #fff;
    }

    .aif-settings-page .provider-tab:hover {
        border-color: #93c5fd;
        color: #3b82f6;
    }

    .aif-settings-page .provider-tab.active {
        border-color: #3b82f6;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .aif-settings-page .provider-tab .tab-icon {
        font-size: 20px;
        display: block;
        margin-bottom: 4px;
    }

    .aif-settings-page .provider-section {
        display: none;
    }

    .aif-settings-page .provider-section.active {
        display: block;
    }

    .aif-settings-page .aif-custom-model-wrap {
        margin-top: 8px;
        display: none;
    }

    .aif-settings-page .aif-custom-model-wrap.visible {
        display: block;
    }
</style>

<div class="aif-settings-page">

    <div style="margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <span class="dashicons dashicons-admin-generic" style="font-size: 28px; width: 28px; height: 28px; color: #3b82f6;"></span>
        <div>
            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #1e293b;">Cài Đặt Plugin</h1>
            <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">API Keys được lưu trong database riêng, không dùng wp_options.</p>
        </div>
    </div>

    <?php if ($saved): ?>
    <div class="aif-notice aif-notice-success">
        <span class="dashicons dashicons-yes-alt"></span>
        Đã lưu cài đặt thành công!
    </div>
    <?php endif; ?>

    <form method="POST">
        <?php wp_nonce_field('aif_save_settings', 'aif_settings_nonce'); ?>

        <!-- AI Provider -->
        <div class="aif-card">
            <div class="aif-card-header">
                <h2>
                    <span class="dashicons dashicons-superhero-alt"></span>
                    Cấu Hình AI
                </h2>
            </div>
            <div class="aif-card-body">
                <p class="aif-section-desc">Chọn nhà cung cấp AI và nhập API Key. Key được mã hóa và lưu trong bảng <code>aif_settings</code> riêng.</p>

                <!-- Provider selector tabs -->
                <div class="aif-form-group">
                    <label>AI Provider</label>
                    <div class="provider-tabs">
                        <div class="provider-tab <?php echo $current_provider === 'gemini' ? 'active' : ''; ?>" data-provider="gemini" onclick="switchProvider('gemini')">
                            <span class="tab-icon">✨</span>
                            Google Gemini
                            <?php if ($has_gemini_key): ?>
                                <span class="aif-key-status key-set">● Đã có key</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">● Chưa có key</span>
                            <?php endif; ?>
                        </div>
                        <div class="provider-tab <?php echo $current_provider === 'openai' ? 'active' : ''; ?>" data-provider="openai" onclick="switchProvider('openai')">
                            <span class="tab-icon">🤖</span>
                            OpenAI
                            <?php if ($has_openai_key): ?>
                                <span class="aif-key-status key-set">● Đã có key</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">● Chưa có key</span>
                            <?php endif; ?>
                        </div>
                        <div class="provider-tab <?php echo $current_provider === 'anthropic' ? 'active' : ''; ?>" data-provider="anthropic" onclick="switchProvider('anthropic')">
                            <span class="tab-icon">🧠</span>
                            Anthropic Claude
                            <?php if ($has_anthropic_key): ?>
                                <span class="aif-key-status key-set">● Đã có key</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">● Chưa có key</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <input type="hidden" name="ai_provider" id="ai_provider_input" value="<?php echo esc_attr($current_provider); ?>">
                </div>

                <!-- Gemini settings -->
                <div class="provider-section <?php echo $current_provider === 'gemini' ? 'active' : ''; ?>" id="section-gemini">
                    <div class="aif-form-group">
                        <label>
                            Google Gemini API Key
                            <?php if ($has_gemini_key): ?>
                                <span class="aif-key-status key-set">✓ Đã lưu</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">✗ Chưa có</span>
                            <?php endif; ?>
                        </label>
                        <input type="password"
                               name="ai_gemini_key"
                               class="aif-input"
                               placeholder="<?php echo $has_gemini_key ? '••••••••••••••••••• (để trống để giữ key cũ)' : 'AIzaSy...'; ?>"
                               autocomplete="new-password">
                        <p class="aif-help-text">
                            Lấy API key tại <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>.
                            Để trống nếu không muốn thay đổi key hiện tại.
                        </p>
                    </div>

                    <div class="aif-form-group">
                        <label>Model Gemini</label>
                        <?php
                        $gemini_in_list = array_key_exists($current_gemini_model, $gemini_models);
                        $gemini_select_val = $gemini_in_list ? $current_gemini_model : '__custom__';
                        ?>
                        <select name="ai_gemini_model" class="aif-select" onchange="toggleCustomModel(this, 'gemini-custom')">
                            <?php foreach ($gemini_models as $val => $label): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($gemini_select_val, $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?php selected($gemini_select_val, '__custom__'); ?>>✏️ Tự điền model...</option>
                        </select>
                        <div class="aif-custom-model-wrap <?php echo $gemini_select_val === '__custom__' ? 'visible' : ''; ?>" id="gemini-custom">
                            <input type="text" name="ai_gemini_model_custom" class="aif-input"
                                placeholder="VD: gemini-2.5-flash-preview-05-20"
                                value="<?php echo !$gemini_in_list ? esc_attr($current_gemini_model) : ''; ?>">
                        </div>
                        <p class="aif-help-text">Gemini 2.5 Flash là lựa chọn tốt nhất. Chọn "Tự điền" để nhập model mới nhất từ Google.</p>
                    </div>
                </div>

                <!-- OpenAI settings -->
                <div class="provider-section <?php echo $current_provider === 'openai' ? 'active' : ''; ?>" id="section-openai">
                    <div class="aif-form-group">
                        <label>
                            OpenAI API Key
                            <?php if ($has_openai_key): ?>
                                <span class="aif-key-status key-set">✓ Đã lưu</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">✗ Chưa có</span>
                            <?php endif; ?>
                        </label>
                        <input type="password"
                               name="ai_openai_key"
                               class="aif-input"
                               placeholder="<?php echo $has_openai_key ? '••••••••••••••••••• (để trống để giữ key cũ)' : 'sk-...'; ?>"
                               autocomplete="new-password">
                        <p class="aif-help-text">
                            Lấy API key tại <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>.
                        </p>
                    </div>

                    <div class="aif-form-group">
                        <label>Model OpenAI</label>
                        <?php
                        $openai_in_list = array_key_exists($current_openai_model, $openai_models);
                        $openai_select_val = $openai_in_list ? $current_openai_model : '__custom__';
                        ?>
                        <select name="ai_openai_model" class="aif-select" onchange="toggleCustomModel(this, 'openai-custom')">
                            <?php foreach ($openai_models as $val => $label): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($openai_select_val, $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?php selected($openai_select_val, '__custom__'); ?>>✏️ Tự điền model...</option>
                        </select>
                        <div class="aif-custom-model-wrap <?php echo $openai_select_val === '__custom__' ? 'visible' : ''; ?>" id="openai-custom">
                            <input type="text" name="ai_openai_model_custom" class="aif-input"
                                placeholder="VD: o3, o4-mini, gpt-4.5-preview"
                                value="<?php echo !$openai_in_list ? esc_attr($current_openai_model) : ''; ?>">
                        </div>
                        <p class="aif-help-text">GPT-4o là lựa chọn cân bằng nhất. Chọn "Tự điền" để nhập model mới nhất từ OpenAI.</p>
                    </div>
                </div>

                <!-- Anthropic settings -->
                <div class="provider-section <?php echo $current_provider === 'anthropic' ? 'active' : ''; ?>" id="section-anthropic">
                    <div class="aif-form-group">
                        <label>
                            Anthropic Claude API Key
                            <?php if ($has_anthropic_key): ?>
                                <span class="aif-key-status key-set">✓ Đã lưu</span>
                            <?php else: ?>
                                <span class="aif-key-status key-missing">✗ Chưa có</span>
                            <?php endif; ?>
                        </label>
                        <input type="password"
                               name="ai_anthropic_key"
                               class="aif-input"
                               placeholder="<?php echo $has_anthropic_key ? '••••••••••••••••••• (để trống để giữ key cũ)' : 'sk-ant-...'; ?>"
                               autocomplete="new-password">
                        <p class="aif-help-text">
                            Lấy API key tại <a href="https://console.anthropic.com/settings/keys" target="_blank">Anthropic Console</a>.
                        </p>
                    </div>

                    <div class="aif-form-group">
                        <label>Model Claude</label>
                        <?php
                        $anthropic_in_list = array_key_exists($current_anthropic_model, $anthropic_models);
                        $anthropic_select_val = $anthropic_in_list ? $current_anthropic_model : '__custom__';
                        ?>
                        <select name="ai_anthropic_model" class="aif-select" onchange="toggleCustomModel(this, 'anthropic-custom')">
                            <?php foreach ($anthropic_models as $val => $label): ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($anthropic_select_val, $val); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?php selected($anthropic_select_val, '__custom__'); ?>>✏️ Tự điền model...</option>
                        </select>
                        <div class="aif-custom-model-wrap <?php echo $anthropic_select_val === '__custom__' ? 'visible' : ''; ?>" id="anthropic-custom">
                            <input type="text" name="ai_anthropic_model_custom" class="aif-input"
                                placeholder="VD: claude-3-7-sonnet-20250219"
                                value="<?php echo !$anthropic_in_list ? esc_attr($current_anthropic_model) : ''; ?>">
                        </div>
                        <p class="aif-help-text">Claude Sonnet 4.5 là lựa chọn tốt nhất. Chọn "Tự điền" để nhập model mới nhất từ Anthropic.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Info box -->
        <div class="aif-card" style="background: #eff6ff; border-color: #bfdbfe;">
            <div class="aif-card-body" style="padding: 16px 20px;">
                <p style="margin: 0; font-size: 13px; color: #1e40af;">
                    <span class="dashicons dashicons-lock" style="font-size: 16px; vertical-align: middle;"></span>
                    <strong>Bảo mật:</strong> API Keys được lưu trong bảng <code><?php global $wpdb; echo $wpdb->prefix; ?>aif_settings</code> trong database,
                    không phải trong <code>wp_options</code>. Bảng này chỉ được truy cập qua plugin này.
                </p>
            </div>
        </div>

        <button type="submit" class="aif-btn-save">
            <span class="dashicons dashicons-saved"></span>
            Lưu Cài Đặt
        </button>

    </form>
</div>

<script>
function switchProvider(provider) {
    document.getElementById('ai_provider_input').value = provider;
    document.querySelectorAll('.provider-tab').forEach(function(tab) {
        tab.classList.toggle('active', tab.dataset.provider === provider);
    });
    document.querySelectorAll('.provider-section').forEach(function(section) {
        section.classList.toggle('active', section.id === 'section-' + provider);
    });
}

function toggleCustomModel(selectEl, customWrapId) {
    var wrap = document.getElementById(customWrapId);
    if (!wrap) return;
    if (selectEl.value === '__custom__') {
        wrap.classList.add('visible');
        wrap.querySelector('input').focus();
    } else {
        wrap.classList.remove('visible');
        wrap.querySelector('input').value = '';
    }
}
</script>
