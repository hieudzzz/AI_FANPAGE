/**
 * Posts List Page - Writing Tones & Utilities
 * AI Fanpage Plugin
 */

jQuery(function ($) {

    // ── Helpers ──────────────────────────────────────────────────────────────
    function escTone(s) {
        return $('<div>').text(s || '').html();
    }

    // ── Cache tones trong bộ nhớ — tránh AJAX thừa ──────────────────────────
    var tonesCache = null;

    // ── Open / Close modal ───────────────────────────────────────────────────
    $('#btn-open-tones').on('click', function () {
        $('#aif-tones-modal').css('display', 'flex');
        if (tonesCache === null) {
            loadTones();
        } else {
            renderTones(tonesCache);
        }
    });

    $('#btn-close-tones-modal').on('click', closeToneModal);

    $('#aif-tones-modal').on('click', function (e) {
        if (e.target === this) closeToneModal();
    });

    function closeToneModal() {
        $('#aif-tones-modal').css('display', 'none');
        resetForm();
    }

    // ── Load từ server → cập nhật cache → render ─────────────────────────────
    function loadTones() {
        $('#tones-list').html('<div style="text-align:center;padding:50px;color:#94a3b8;"><div class="spinner is-active" style="float:none;margin:0 auto 12px;"></div>Đang tải...</div>');
        $.post(aif_ajax.ajax_url, {
            action: 'aif_get_tones',
            nonce: aif_ajax.nonce
        }, function (res) {
            if (!res.success) return;
            tonesCache = res.data;
            renderTones(tonesCache);
        });
    }

    // ── Render từ data (dùng cache hoặc data server) ──────────────────────────
    function renderTones(tones) {
        $('#tones-count-label').text(tones.length + ' phong cách');
        if (!tones.length) {
            $('#tones-list').html('<div style="text-align:center;padding:60px;color:#94a3b8;font-size:14px;">Chưa có phong cách nào. Nhấn <b>Thêm</b> để tạo mới.</div>');
            return;
        }
        var html = '';
        tones.forEach(function (t) {
            var isDefault = parseInt(t.is_default);
            html += '<div class="tone-item" data-id="' + t.id + '" style="display:flex;align-items:flex-start;gap:14px;padding:14px 20px;border-bottom:1px solid #f1f5f9;background:#fff;transition:background .15s;">' +
                '<div style="flex:1;min-width:0;">' +
                '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">' +
                '<span style="font-size:14px;font-weight:700;color:#1e293b;">' + escTone(t.label) + '</span>' +
                (isDefault ? '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;background:#ede9fe;color:#7c3aed;">Mặc định</span>' : '') +
                '</div>' +
                (t.description ? '<div style="font-size:12px;color:#64748b;margin-bottom:5px;">' + escTone(t.description) + '</div>' : '') +
                '<div style="font-size:12px;color:#94a3b8;line-height:1.5;white-space:pre-wrap;">' + escTone(t.style) + '</div>' +
                '</div>' +
                '<div style="display:flex;gap:6px;flex-shrink:0;padding-top:2px;">' +
                '<button class="button button-small btn-tone-edit" data-id="' + t.id + '" style="border-radius:6px;border-color:#e2e8f0;color:#4f46e5;padding:4px 10px;" title="Sửa"><span class="dashicons dashicons-edit" style="font-size:13px;margin-top:3px;"></span></button>' +
                (isDefault ? '' : '<button class="button button-small btn-tone-delete" data-id="' + t.id + '" data-label="' + escTone(t.label) + '" style="border-radius:6px;border-color:#fee2e2;color:#ef4444;padding:4px 8px;" title="Xóa"><span class="dashicons dashicons-trash" style="font-size:13px;margin-top:3px;"></span></button>') +
                '</div>' +
                '</div>';
        });
        $('#tones-list').html(html);
    }

    // ── Form show/hide ────────────────────────────────────────────────────────
    function showForm(tone) {
        if (tone) {
            $('#tone-edit-id').val(tone.id);
            $('#tone-label').val(tone.label);
            $('#tone-description').val(tone.description);
            $('#tone-style').val(tone.style);
        } else {
            resetForm();
        }
        $('#tones-form-area').slideDown(150);
        setTimeout(function () { $('#tone-label').focus(); }, 160);
    }

    function resetForm() {
        $('#tone-edit-id').val('');
        $('#tone-label').val('');
        $('#tone-description').val('');
        $('#tone-style').val('');
        $('#tones-form-area').slideUp(150);
    }

    $('#btn-tone-add').on('click', function () { showForm(null); });
    $('#btn-tone-cancel').on('click', resetForm);

    // ── Edit — dùng cache, không gọi AJAX ────────────────────────────────────
    $(document).on('click', '.btn-tone-edit', function () {
        var id = $(this).data('id');
        var t = (tonesCache || []).find(function (x) {
            return String(x.id) === String(id);
        });
        if (t) {
            showForm(t);
        } else {
            $.post(aif_ajax.ajax_url, { action: 'aif_get_tones', nonce: aif_ajax.nonce }, function (res) {
                if (!res.success) return;
                tonesCache = res.data;
                var found = tonesCache.find(function (x) { return String(x.id) === String(id); });
                if (found) showForm(found);
            });
        }
    });

    // ── Save ──────────────────────────────────────────────────────────────────
    $('#btn-tone-save').on('click', function () {
        var id    = $('#tone-edit-id').val();
        var label = $('#tone-label').val().trim();
        var desc  = $('#tone-description').val().trim();
        var style = $('#tone-style').val().trim();

        if (!label) { alert('Vui lòng nhập tên phong cách.'); $('#tone-label').focus(); return; }
        if (!style) { alert('Vui lòng nhập hướng dẫn cho AI.'); $('#tone-style').focus(); return; }

        var $btn = $(this).prop('disabled', true);
        $.post(aif_ajax.ajax_url, {
            action: 'aif_tone_save',
            nonce: aif_ajax.nonce,
            id: id, label: label, description: desc, style: style
        }, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                resetForm();
                tonesCache = null;
                loadTones();
                if (window.AIF_Toast) AIF_Toast.show(id ? 'Đã cập nhật phong cách!' : 'Đã thêm phong cách mới!', 'success');
            } else {
                alert(res.data || 'Lỗi lưu');
            }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-tone-delete', function () {
        var id    = $(this).data('id');
        var label = $(this).data('label');
        if (!confirm('Xóa phong cách "' + label + '"?\nHành động này không thể hoàn tác.')) return;
        $.post(aif_ajax.ajax_url, {
            action: 'aif_tone_delete',
            nonce: aif_ajax.nonce,
            id: id
        }, function (res) {
            if (res.success) {
                tonesCache = null;
                loadTones();
                if (window.AIF_Toast) AIF_Toast.show('Đã xóa phong cách.', 'success');
            } else {
                alert(res.data || 'Lỗi xóa');
            }
        });
    });

    // ── Import Google Sheet trigger ───────────────────────────────────────────
    $('#btn-import-gsheet-trigger').on('click', function (e) {
        e.preventDefault();
        $('#btn-import-gsheet').click();
    });

    // ── Auto-expand textarea ──────────────────────────────────────────────────
    $(document).on('input', 'textarea.auto-expand', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
