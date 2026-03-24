<?php

/**
 * Admin page: Quản lý Phong cách viết
 */
if (!defined('ABSPATH')) exit;

$db    = new AIF_Tones_DB();
$tones = $db->get_all();
$count = count($tones);
?>

<div class="wrap aif-container">

    <!-- ===== HEADER ===== -->
    <div class="aif-n8n-header" style="margin-bottom:24px;">
        <div class="aif-n8n-header-left">
            <div class="aif-n8n-header-icon" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);">
                <span class="dashicons dashicons-edit-page"></span>
            </div>
            <div>
                <h1 class="aif-n8n-title">Phong cách viết</h1>
                <p class="aif-n8n-subtitle">Quản lý phong cách viết tùy chỉnh dùng cho AI tạo nội dung</p>
            </div>
        </div>
        <div class="aif-n8n-header-stats">
            <span class="aif-badge aif-badge-indigo" style="font-size:13px;padding:6px 14px;"><?php echo $count; ?> phong cách</span>
        </div>
    </div>

    <!-- ===== TOOLBAR ===== -->
    <div class="aif-toolbar" style="margin-bottom:16px;">
        <div class="aif-toolbar-left">
            <button id="btn-add-tone" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> Thêm phong cách mới
            </button>
        </div>
        <div class="aif-toolbar-right">
            <span class="aif-toolbar-hint">
                <span class="dashicons dashicons-info-outline" style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:4px;"></span>
                Kéo <span class="dashicons dashicons-menu" style="font-size:12px;width:12px;height:12px;vertical-align:middle;"></span> để sắp xếp thứ tự hiển thị.
            </span>
        </div>
    </div>

    <!-- ===== TABLE ===== -->
    <div class="aif-card">
        <table class="aif-table" id="tones-table">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th style="width:60px;text-align:center;">ID</th>
                    <th style="width:200px;">Tên phong cách</th>
                    <th style="width:220px;">Mô tả ngắn</th>
                    <th>Hướng dẫn AI</th>
                    <th style="width:110px;text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody id="tones-body">
                <?php if (empty($tones)): ?>
                    <tr id="tones-empty-row">
                        <td colspan="6" class="aif-loading-cell" style="color:#94a3b8;font-style:italic;">
                            Chưa có phong cách nào. Nhấn <b>Thêm phong cách mới</b> để bắt đầu.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tones as $t): ?>
                        <tr data-id="<?php echo $t->id; ?>">
                            <td style="text-align:center;cursor:grab;color:#cbd5e1;" class="tone-drag-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </td>
                            <td style="text-align:center;color:#6366f1;font-weight:700;">#<?php echo $t->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($t->label); ?></strong>
                                <br><code style="font-size:10px;color:#94a3b8;"><?php echo esc_html($t->tone_key); ?></code>
                            </td>
                            <td style="font-size:13px;color:#64748b;"><?php echo esc_html($t->description ?: '—'); ?></td>
                            <td style="font-size:12px;color:#475569;">
                                <div style="white-space:pre-wrap;overflow:hidden;max-height:60px;"><?php echo esc_html($t->style); ?></div>
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <button class="button button-small btn-edit-tone"
                                    data-id="<?php echo $t->id; ?>"
                                    data-label="<?php echo esc_attr($t->label); ?>"
                                    data-desc="<?php echo esc_attr($t->description); ?>"
                                    data-style="<?php echo esc_attr($t->style); ?>"
                                    style="border-radius:6px;color:var(--aif-primary);border-color:var(--aif-border-light);padding:4px 10px;">
                                    <span class="dashicons dashicons-edit" style="font-size:14px;margin-top:3px;"></span>
                                </button>
                                <button class="button button-small btn-del-tone"
                                    data-id="<?php echo $t->id; ?>"
                                    data-label="<?php echo esc_attr($t->label); ?>"
                                    style="border-radius:6px;color:#ef4444;border-color:#fee2e2;padding:4px 10px;">
                                    <span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- .wrap -->


<!-- ===== MODAL: Thêm / Sửa ===== -->
<div id="aif-tone-modal" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:560px;margin:20px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.2);">

        <!-- Header -->
        <div style="padding:20px 24px;background:linear-gradient(135deg,#8b5cf6,#6366f1);position:relative;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <span class="dashicons dashicons-edit-page" style="color:#fff;font-size:18px;width:18px;height:18px;"></span>
                </div>
                <div>
                    <h3 id="tone-modal-title" style="margin:0;font-size:16px;font-weight:800;color:#fff;">Thêm phong cách mới</h3>
                    <p style="margin:2px 0 0;font-size:12px;color:rgba(255,255,255,0.75);">Phong cách sẽ xuất hiện trong trang soạn bài viết AI</p>
                </div>
            </div>
            <button type="button" id="tone-modal-close" style="position:absolute;top:14px;right:16px;background:rgba(255,255,255,0.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;cursor:pointer;font-size:18px;line-height:28px;text-align:center;">&times;</button>
        </div>

        <!-- Body -->
        <div style="padding:24px;">
            <input type="hidden" id="tone-edit-id">

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">
                    Tên phong cách <span style="color:#ef4444;">*</span>
                    <span style="font-size:11px;font-weight:400;color:#94a3b8;">(có thể dùng emoji)</span>
                </label>
                <input type="text" id="tone-modal-label"
                    placeholder="VD: ✍️ Văn học, 😂 Hài hước, 💼 Chuyên nghiệp..."
                    style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border-color .2s;">
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">
                    Mô tả ngắn
                    <span style="font-size:11px;font-weight:400;color:#94a3b8;">(hiện khi hover vào nút chọn)</span>
                </label>
                <input type="text" id="tone-modal-desc"
                    placeholder="VD: Giọng hài hước, dùng meme và từ ngữ trẻ trung..."
                    style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box;outline:none;transition:border-color .2s;">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">
                    Hướng dẫn cho AI <span style="color:#ef4444;">*</span>
                    <span style="font-size:11px;font-weight:400;color:#94a3b8;">(AI đọc và áp dụng khi viết)</span>
                </label>
                <textarea id="tone-modal-style" rows="5"
                    placeholder="VD: Viết theo phong cách hài hước, dùng ngôn ngữ Gen Z, thêm meme reference. Câu ngắn, dùng dấu ... nhiều để tạo kịch tính..."
                    style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;box-sizing:border-box;outline:none;resize:vertical;line-height:1.6;transition:border-color .2s;"></textarea>
                <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                    <span id="tone-style-count" style="font-size:11px;color:#94a3b8;">0 ký tự</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;gap:10px;background:#fafafa;">
            <button type="button" id="tone-modal-cancel" class="aif-btn aif-btn-outline">Hủy</button>
            <button type="button" id="tone-modal-save" class="aif-btn aif-btn-primary">
                <span class="dashicons dashicons-saved" style="font-size:15px;width:15px;height:15px;"></span>
                Lưu phong cách
            </button>
        </div>
    </div>
</div>


<script>
    jQuery(document).ready(function($) {

        var NONCE = <?php echo json_encode(wp_create_nonce('aif_nonce')); ?>;
        var AJAXURL = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;

        // ── Modal ─────────────────────────────────────────────────────────────
        function openModal(tone) {
            if (tone) {
                $('#tone-modal-title').text('Sửa phong cách');
                $('#tone-edit-id').val(tone.id);
                $('#tone-modal-label').val(tone.label);
                $('#tone-modal-desc').val(tone.desc);
                $('#tone-modal-style').val(tone.style);
                $('#tone-style-count').text(tone.style.length + ' ký tự');
            } else {
                $('#tone-modal-title').text('Thêm phong cách mới');
                $('#tone-edit-id').val('');
                $('#tone-modal-label, #tone-modal-desc, #tone-modal-style').val('');
                $('#tone-style-count').text('0 ký tự');
            }
            $('#aif-tone-modal').css('display', 'flex');
            setTimeout(function() {
                $('#tone-modal-label').focus();
            }, 100);
        }

        function closeModal() {
            $('#aif-tone-modal').css('display', 'none');
        }

        $('#btn-add-tone').on('click', function() {
            openModal(null);
        });
        $('#tone-modal-close, #tone-modal-cancel').on('click', closeModal);
        $('#aif-tone-modal').on('click', function(e) {
            if (e.target === this) closeModal();
        });

        $(document).on('click', '.btn-edit-tone', function() {
            openModal({
                id: $(this).data('id'),
                label: $(this).data('label'),
                desc: $(this).data('desc'),
                style: $(this).data('style'),
            });
        });

        $('#tone-modal-style').on('input', function() {
            $('#tone-style-count').text($(this).val().length + ' ký tự');
        });

        $('#tone-modal-label, #tone-modal-desc, #tone-modal-style').on('focus', function() {
            $(this).css('border-color', '#8b5cf6');
        }).on('blur', function() {
            $(this).css('border-color', '#e2e8f0');
        });

        // ── Lưu ──────────────────────────────────────────────────────────────
        $('#tone-modal-save').on('click', function() {
            var id = $('#tone-edit-id').val();
            var label = $('#tone-modal-label').val().trim();
            var desc = $('#tone-modal-desc').val().trim();
            var style = $('#tone-modal-style').val().trim();

            if (!label) {
                window.AIF_Toast && AIF_Toast.show('Vui lòng nhập tên phong cách.', 'error');
                $('#tone-modal-label').focus().css('border-color', '#ef4444');
                return;
            }
            if (!style) {
                window.AIF_Toast && AIF_Toast.show('Vui lòng nhập hướng dẫn cho AI.', 'error');
                $('#tone-modal-style').focus().css('border-color', '#ef4444');
                return;
            }

            var $btn = $(this).prop('disabled', true);
            var orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;display:inline-block;animation:aif-rotate .7s linear infinite;"></span> Đang lưu...');

            $.post(AJAXURL, {
                action: 'aif_tone_save',
                nonce: NONCE,
                id: id,
                label: label,
                description: desc,
                style: style
            }, function(res) {
                $btn.prop('disabled', false).html(orig);
                if (!res.success) {
                    window.AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error');
                    return;
                }

                closeModal();
                window.AIF_Toast && AIF_Toast.show(id ? 'Đã cập nhật phong cách!' : 'Đã thêm phong cách mới!', 'success');

                var t = res.data;
                $('#tones-empty-row').remove();

                var row = buildRow(t);
                if (id) {
                    $('#tones-body tr[data-id="' + id + '"]').replaceWith(row);
                } else {
                    $('#tones-body').append(row);
                }
                updateCount();
            });
        });

        // ── Xóa ──────────────────────────────────────────────────────────────
        $(document).on('click', '.btn-del-tone', function() {
            var id = $(this).data('id');
            var label = $(this).data('label');
            if (!confirm('Xóa phong cách "' + label + '"?\nHành động này không thể hoàn tác.')) return;

            var $row = $(this).closest('tr');
            $.post(AJAXURL, {
                action: 'aif_tone_delete',
                nonce: NONCE,
                id: id
            }, function(res) {
                if (res.success) {
                    $row.fadeOut(200, function() {
                        $(this).remove();
                        if (!$('#tones-body tr[data-id]').length) {
                            $('#tones-body').html('<tr id="tones-empty-row"><td colspan="6" class="aif-loading-cell" style="color:#94a3b8;font-style:italic;">Chưa có phong cách nào.</td></tr>');
                        }
                        updateCount();
                    });
                    window.AIF_Toast && AIF_Toast.show('Đã xóa phong cách.', 'success');
                } else {
                    window.AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error');
                }
            });
        });

        // ── Drag & drop ───────────────────────────────────────────────────────
        function initSortable() {
            Sortable.create(document.getElementById('tones-body'), {
                handle: '.tone-drag-handle',
                animation: 150,
                onEnd: function() {
                    var ids = [];
                    $('#tones-body tr[data-id]').each(function() {
                        ids.push($(this).data('id'));
                    });
                    $.post(AJAXURL, {
                        action: 'aif_tone_reorder',
                        nonce: NONCE,
                        ids: ids
                    });
                }
            });
        }

        if (window.Sortable) {
            initSortable();
        } else {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
            s.onload = initSortable;
            document.head.appendChild(s);
        }

        // ── Helpers ───────────────────────────────────────────────────────────
        function buildRow(t) {
            return '<tr data-id="' + t.id + '">' +
                '<td style="text-align:center;cursor:grab;color:#cbd5e1;" class="tone-drag-handle"><span class="dashicons dashicons-menu"></span></td>' +
                '<td style="text-align:center;color:#6366f1;font-weight:700;">#' + t.id + '</td>' +
                '<td><strong>' + esc(t.label) + '</strong><br><code style="font-size:10px;color:#94a3b8;">' + esc(t.tone_key) + '</code></td>' +
                '<td style="font-size:13px;color:#64748b;">' + esc(t.description || '—') + '</td>' +
                '<td style="font-size:12px;color:#475569;"><div style="white-space:pre-wrap;overflow:hidden;max-height:60px;">' + esc(t.style) + '</div></td>' +
                '<td style="text-align:right;white-space:nowrap;">' +
                '<button class="button button-small btn-edit-tone" data-id="' + t.id + '" data-label="' + attr(t.label) + '" data-desc="' + attr(t.description) + '" data-style="' + attr(t.style) + '" style="border-radius:6px;color:var(--aif-primary);border-color:var(--aif-border-light);padding:4px 10px;"><span class="dashicons dashicons-edit" style="font-size:14px;margin-top:3px;"></span></button> ' +
                '<button class="button button-small btn-del-tone" data-id="' + t.id + '" data-label="' + attr(t.label) + '" style="border-radius:6px;color:#ef4444;border-color:#fee2e2;padding:4px 10px;"><span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span></button>' +
                '</td></tr>';
        }

        function updateCount() {
            var n = $('#tones-body tr[data-id]').length;
            $('.aif-badge-indigo').text(n + ' phong cách');
        }

        function esc(str) {
            return $('<div>').text(str || '').html();
        }

        function attr(str) {
            return (str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

    });
</script>