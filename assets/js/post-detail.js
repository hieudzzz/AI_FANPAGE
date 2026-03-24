/**
 * AI Fanpage — Post Detail Page Scripts
 * Depends on: jquery, aif-toast-script
 * Data object: aif_post_detail (localized via wp_localize_script)
 *   - ajax_url   : admin-ajax URL
 *   - nonce      : aif_nonce value
 *   - post_id    : current post ID (int, 0 if new)
 *   - upload_url : plugin upload folder URL (trailing slash)
 *   - status_labels  : object { status_key: label }
 *   - status_classes : object { status_key: css_class }
 */

/* global aif_post_detail, AIF_Toast, wp */

jQuery(document).ready(function ($) {

    const ajaxUrl      = aif_post_detail.ajax_url;
    const nonce        = aif_post_detail.nonce;
    const postId       = aif_post_detail.post_id;
    const uploadUrl    = aif_post_detail.upload_url;
    const statusLabels  = aif_post_detail.status_labels;
    const statusClasses = aif_post_detail.status_classes;

    // =========================================================
    // 1. Media Preview Logic
    // =========================================================
    function updateMediaPreview() {
        // -- A. Facebook Gallery --
        const $containerFb = $('#aif-preview-container-fb');
        $containerFb.empty();

        // Lấy URL: ưu tiên từ modalFilesCache, fallback DOM, fallback uploadUrl+val
        function resolveUrl(val) {
            // WP attachment — từ wpUrlMap hoặc DOM
            if (val.startsWith('wp-att-')) {
                if (wpUrlMap[val]) return wpUrlMap[val];
                const $inp = $('.aif-media-option input[value="' + val + '"]');
                if ($inp.length) { wpUrlMap[val] = $inp.data('url'); return wpUrlMap[val]; }
                return '';
            }
            // Từ cache AJAX
            if (modalFilesCache) {
                const f = modalFilesCache.find(function(fx) {
                    const fkey = fx.folder ? fx.folder + '/' + fx.name : fx.name;
                    return fkey === val;
                });
                if (f) return f.url;
            }
            // Fallback: uploadUrl + filename (strip folder prefix since files are flat on disk)
            const basename = val.includes('/') ? val.substring(val.lastIndexOf('/') + 1) : val;
            return uploadUrl + basename;
        }

        modalSelected.forEach(function(val) {
            const url     = resolveUrl(val);
            if (!url) return;
            const isVideo = val.toLowerCase().endsWith('.mp4');

            const mediaHtml = isVideo
                ? `<video src="${url}" style="width:100%; height:100%; object-fit:cover;"></video>`
                : `<img src="${url}" style="width:100%; height:100%; object-fit:cover;">`;

            const $thumb = $(`
                <div class="aif-media-thumb" data-val="${val}" style="aspect-ratio:1; border-radius:8px; overflow:hidden; position:relative; border:1px solid #e2e8f0;">
                    ${mediaHtml}
                    <div class="aif-media-order-btns">
                        <button type="button" class="aif-order-btn move-left" title="Move Left"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
                        <button type="button" class="aif-order-btn move-right" title="Move Right"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                    </div>
                    <span class="dashicons dashicons-no-alt remove-media" data-val="${val}"
                          style="position:absolute; top:4px; right:4px; background:#fff; border-radius:50%; cursor:pointer; font-size:16px; color:#ef4444; box-shadow:0 2px 4px rgba(0,0,0,0.1);"></span>
                </div>
            `);
            $containerFb.append($thumb);
        });

        // -- B. Website Thumbnail --
        const $containerWeb = $('#aif-preview-container-web');
        const webVal = $('#aif-image-website-input').val();
        $containerWeb.empty();

        if (webVal) {
            const webUrl = resolveUrl(webVal);

            const $thumb = $(`
                <div class="aif-media-thumb" style="aspect-ratio:1; border-radius:8px; overflow:hidden; position:relative; border:2px solid var(--aif-primary);">
                    <img src="${webUrl}" style="width:100%; height:100%; object-fit:cover;">
                    <span class="dashicons dashicons-no-alt remove-web-media"
                          style="position:absolute; top:4px; right:4px; background:#fff; border-radius:50%; cursor:pointer; font-size:16px; color:#ef4444; box-shadow:0 2px 4px rgba(0,0,0,0.1);"></span>
                </div>
            `);
            $containerWeb.append($thumb);
        } else {
            $containerWeb.append(`<div style="width:100px; height:100px; border:1px dashed #cbd5e1; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:10px; text-align:center; padding:10px;">Chưa chọn</div>`);
        }

        // Sync visual in Modal grid
        $('.aif-media-option').each(function () {
            const $input   = $(this).find('input');
            const $wrapper = $(this).find('.img-wrapper');
            const val      = $input.val();

            if ($input.is(':checked') || val === webVal) {
                const borderColor = (val === webVal) ? 'var(--aif-primary)' : '#3b82f6';
                $wrapper.css({ 'border-color': borderColor, 'transform': 'scale(0.96)', 'opacity': '1' });
                $(this).css('background', val === webVal ? '#f0f9ff' : '#eff6ff');
            } else {
                $wrapper.css({ 'border-color': 'transparent', 'transform': 'scale(1)', 'opacity': '1' });
                $(this).css('background', 'transparent');
            }
        });

        syncInputOrder();
    }

    // Reordering (Move Left / Right)
    $(document).on('click', '.aif-order-btn', function (e) {
        e.preventDefault();
        const $thumb = $(this).closest('.aif-media-thumb');
        if ($(this).hasClass('move-left')) {
            $thumb.prev().before($thumb);
        } else {
            $thumb.next().after($thumb);
        }
        syncInputOrder();
    });

    function syncInputOrder() {
        const order = [];
        $('#aif-preview-container-fb .aif-media-thumb').each(function () {
            order.push($(this).data('val'));
        });
        // Sync lại modalSelected theo thứ tự mới
        modalSelected.clear();
        order.forEach(v => modalSelected.add(v));
        $('#aif-images-order-input').val(JSON.stringify(order));
    }

    // Set Website Media
    $(document).on('click', '.aif-btn-set-web', function (e) {
        e.preventDefault();
        const filename = $(this).data('filename');
        $('#aif-image-website-input').val(filename);
        updateMediaPreview();
        if (window.AIF_Toast) AIF_Toast.show('Đã chọn làm ảnh đại diện Website', 'success');
    });

    $(document).on('click', '.remove-web-media', function () {
        $('#aif-image-website-input').val('');
        updateMediaPreview();
    });

    $(document).on('click', '.remove-media', function (e) {
        e.stopPropagation();
        const val = $(this).data('val');
        modalSelected.delete(val);
        $('.aif-media-option input').filter(function () { return $(this).val() === val; }).prop('checked', false);
        updateMediaPreview();
    });

    $(document).on('change', '.aif-media-option input', updateMediaPreview);

    // =========================================================
    // 2. Modal Controls
    // =========================================================
    let modalFilesCache  = null;   // cache toàn bộ files từ server
    let modalFolderCache = null;   // cache folder list
    let modalCurFolder   = '';     // folder đang active trong modal
    let modalSelected    = new Set(); // set các value đã chọn — persistent qua các folder
    let wpUrlMap         = aif_post_detail.wp_att_urls || {}; // map wp-att-ID -> URL

    // Khởi tạo từ hidden input hiện tại
    function initModalSelected() {
        modalSelected.clear();
        try {
            const existing = JSON.parse($('#aif-images-order-input').val() || '[]');
            existing.forEach(v => modalSelected.add(v));
        } catch(e) {}
    }

    $('#aif-open-media-modal').on('click', function () {
        initModalSelected();
        modalFilesCache  = null;  // reset để luôn fetch mới khi mở modal
        modalFolderCache = null;
        $('#aif-media-modal').css('display', 'flex').hide().fadeIn(200);
        loadModalMedia('');
    });

    $('#aif-close-media-modal, #aif-close-modal-x, #aif-media-modal').on('click', function (e) {
        if (e.target !== this && e.target.id !== 'aif-close-media-modal' && e.target.id !== 'aif-close-modal-x') return;
        // Ghi toàn bộ selection vào hidden input rồi sync preview
        const order = Array.from(modalSelected);
        $('#aif-images-order-input').val(JSON.stringify(order));
        updateMediaPreview();
        $('#aif-media-modal').fadeOut(200);
    });

    // Load media từ AJAX theo folder
    function loadModalMedia(folder) {
        modalCurFolder = folder;
        const $grid = $('#aif-media-modal .aif-image-grid');
        $grid.html('<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;"><div class="spinner is-active" style="float:none;margin:0 auto 10px;"></div>Đang tải...</div>');

        $.post(ajaxUrl, { action: 'aif_media_get_folders', nonce: nonce, folder: folder })
            .done(function(res) {
                if (!res || !res.success) {
                    $grid.html('<div style="grid-column:1/-1;text-align:center;padding:40px;color:#ef4444;">Lỗi tải ảnh: ' + (res && res.data ? res.data : 'Unknown') + '</div>');
                    return;
                }
                modalFolderCache = res.data.folders;
                modalFilesCache  = res.data.files;
                renderModalSidebar();
                renderModalGrid();
            })
            .fail(function(xhr) {
                $grid.html('<div style="grid-column:1/-1;text-align:center;padding:40px;color:#ef4444;">Lỗi kết nối AJAX (' + xhr.status + ')</div>');
            });
    }

    function renderModalSidebar() {
        if (!modalFolderCache) return;
        let html = '';
        modalFolderCache.forEach(function(f) {
            const active = modalCurFolder === f.name ? 'background:#eff6ff;color:#3b82f6;font-weight:700;' : '';
            const icon   = f.name === '' ? 'dashicons-category' : 'dashicons-portfolio';
            html += `<div class="aif-modal-folder-btn" data-folder="${f.name}"
                style="display:flex;align-items:center;gap:7px;padding:8px 10px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:500;color:#475569;transition:all 0.15s;margin-bottom:2px;${active}">
                <span class="dashicons ${icon}" style="font-size:15px;width:15px;height:15px;flex-shrink:0;"></span>
                <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.label || 'Tất cả'}</span>
                <span style="font-size:10px;font-weight:700;background:#f1f5f9;color:#94a3b8;padding:1px 6px;border-radius:8px;flex-shrink:0;">${f.count}</span>
            </div>`;
        });
        $('#aif-modal-sidebar').html(
            '<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:#94a3b8;padding:4px 8px 8px;">Chuyên mục</div>' + html
        );
    }

    function renderModalGrid() {
        if (!modalFilesCache) return;
        const q     = ($('#aif-modal-search').val() || '').toLowerCase().trim();
        const files = q ? modalFilesCache.filter(f => f.name.toLowerCase().includes(q)) : modalFilesCache;

        let html = '';

        // ── Section: Ảnh WP đã chọn (chỉ hiện khi có) ──────────────────────
        const wpSelected = Array.from(modalSelected).filter(v => v.startsWith('wp-att-'));
        if (wpSelected.length > 0 && !q) {
            html += `<div style="grid-column:1/-1; font-size:11px; font-weight:800; text-transform:uppercase;
                letter-spacing:0.7px; color:#7c3aed; margin-bottom:4px; padding:4px 2px;
                border-bottom:2px solid #ede9fe; display:flex; align-items:center; gap:6px;">
                <span class="dashicons dashicons-images-alt2" style="font-size:14px;width:14px;height:14px;"></span>
                Ảnh WP đã chọn
            </div>`;
            wpSelected.forEach(function(id) {
                const url = wpUrlMap[id] || '';
                if (!url) return;
                html += buildMediaCard(id, url, url, true);
            });
            html += `<div style="grid-column:1/-1; border-top:1px solid #f1f5f9; margin:6px 0 10px;"></div>`;
        }

        // ── Section: Plugin upload files ─────────────────────────────────────
        if (files.length === 0 && wpSelected.length === 0) {
            html += '<div style="grid-column:1/-1;text-align:center;padding:60px;color:#94a3b8;">' +
                '<span class="dashicons dashicons-format-gallery" style="font-size:40px;width:40px;height:40px;display:block;margin:0 auto 12px;opacity:0.3;"></span>' +
                (q ? 'Không tìm thấy file nào.' : 'Chuyên mục này chưa có file.') + '</div>';
        } else if (files.length === 0 && q) {
            html += '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">Không tìm thấy file nào.</div>';
        } else {
            files.forEach(function(f) {
                const fileVal = f.folder ? f.folder + '/' + f.name : f.name;
                html += buildMediaCard(fileVal, f.url, f.url, false, f);
            });
        }

        $('#aif-media-modal .aif-image-grid').html(html);
        updateModalCount();
    }

    function buildMediaCard(val, url, thumbUrl, isWp, f) {
        const checked  = modalSelected.has(val);
        const border   = checked ? '#3b82f6' : 'transparent';
        const isVideo  = val.toLowerCase().endsWith('.mp4');
        const label    = isWp
            ? `<div style="font-size:10px;border-radius:3px;background:#ede9fe;padding:1px 5px;color:#7c3aed;font-weight:700;">WP</div>`
            : `<div style="font-size:10px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:70px;">${f ? f.name : val}</div>`;

        return `<label class="aif-media-option" style="cursor:pointer;position:relative;display:block;border-radius:12px;overflow:hidden;">
            <input type="checkbox" name="aif_images[]" value="${val}" ${checked ? 'checked' : ''}
                data-url="${url}" form="aif-post-form"
                style="position:absolute;top:10px;left:10px;z-index:10;width:22px;height:22px;border-radius:6px;">
            <span class="aif-lightbox-trigger dashicons ${isVideo ? 'dashicons-controls-play' : 'dashicons-search'}"
                data-src="${url}"
                style="position:absolute;top:10px;right:10px;z-index:10;background:rgba(15,23,42,0.6);color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:zoom-in;"></span>
            <div class="img-wrapper" style="border:3px solid ${border};border-radius:12px;overflow:hidden;background:#f8fafc;aspect-ratio:1/1;">
                ${isVideo
                    ? `<video src="${url}" style="width:100%;height:100%;object-fit:cover;pointer-events:none;"></video>`
                    : `<img src="${thumbUrl}" loading="lazy" style="width:100%;height:100%;object-fit:cover;">`}
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:5px;padding:0 2px;">
                ${label}
                <button type="button" class="aif-btn-set-web"
                    data-filename="${val}" data-url="${url}"
                    style="font-size:9px;padding:2px 5px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;cursor:pointer;white-space:nowrap;">
                    Set Web
                </button>
            </div>
        </label>`;
    }

    function updateModalCount() {
        const cnt = modalSelected.size;
        $('#aif-modal-selected-count').text(cnt > 0 ? cnt + ' file đã chọn' : '');
    }

    // Click folder trong sidebar modal
    $(document).on('click', '.aif-modal-folder-btn', function() {
        const folder = $(this).data('folder');
        // Reset cache để load lại theo folder mới
        modalFilesCache = null;
        loadModalMedia(folder);
    });

    // Search trong modal
    $('#aif-modal-search').on('input', function() { renderModalGrid(); });

    // Checkbox change → cập nhật modalSelected + border + count
    $(document).on('change', '#aif-media-modal .aif-image-grid input[type=checkbox]', function() {
        const val     = $(this).val();
        const checked = $(this).is(':checked');
        const $wrap   = $(this).siblings('.img-wrapper');
        if (checked) {
            modalSelected.add(val);
            $wrap.css('border-color', '#3b82f6');
        } else {
            modalSelected.delete(val);
            $wrap.css('border-color', 'transparent');
        }
        updateModalCount();
    });

    // =========================================================
    // 3. Inline Upload
    // =========================================================
    $('#aif-btn-inline-upload').on('click', function () {
        $('#aif-inline-upload-input').click();
    });

    $('#aif-inline-upload-input').on('change', function () {
        const files = this.files;
        if (!files || files.length === 0) return;

        const spinner = $('#aif-inline-upload-spinner');
        spinner.addClass('is-active');

        // Upload từng file vào folder đang active trong modal
        let done = 0;
        Array.from(files).forEach(function(file) {
            const formData = new FormData();
            formData.append('action', 'aif_media_upload');
            formData.append('nonce',  nonce);
            formData.append('folder', modalCurFolder || '');
            formData.append('file',   file);

            $.ajax({
                url: ajaxUrl, type: 'POST', data: formData,
                processData: false, contentType: false,
                success: function(res) {
                    done++;
                    if (res.success && modalFilesCache) {
                        modalFilesCache.unshift(res.data);
                    }
                    if (done === files.length) {
                        spinner.removeClass('is-active');
                        renderModalGrid();
                        if (window.AIF_Toast) AIF_Toast.show(done + ' file đã tải lên!', 'success');
                    }
                },
                error: function() {
                    done++;
                    if (done === files.length) { spinner.removeClass('is-active'); renderModalGrid(); }
                }
            });
        });

        this.value = '';
    });

    // =========================================================
    // 3b. WP Media Library Picker
    // =========================================================
    let wpMediaFrame = null;
    $('#aif-btn-wp-media').on('click', function () {
        if (wpMediaFrame) {
            wpMediaFrame.open();
            return;
        }
        wpMediaFrame = wp.media({
            title: 'Chọn ảnh / video từ Thư viện WordPress',
            button: { text: 'Chọn' },
            multiple: true,
            library: { type: ['image', 'video'] }
        });

        wpMediaFrame.on('select', function () {
            const selection = wpMediaFrame.state().get('selection');
            selection.each(function (attachment) {
                const att = attachment.toJSON();
                const url = att.url;
                const id  = 'wp-att-' + att.id;

                // Thêm vào modalSelected + wpUrlMap
                modalSelected.add(id);
                wpUrlMap[id] = url;

                // Nếu đã có trong grid rồi thì bỏ qua
                if ($('.aif-media-option input[value="' + id + '"]').length) return;

                const isVideo  = att.mime && att.mime.indexOf('video') === 0;
                const thumbUrl = (!isVideo && att.sizes && att.sizes.thumbnail)
                    ? att.sizes.thumbnail.url : url;

                const $item = $(`
                    <label class="aif-media-option"
                        style="cursor:pointer; position:relative; display:block; border-radius:12px; overflow:hidden;">
                        <input type="checkbox" name="aif_images[]" value="${id}"
                            data-url="${url}" form="aif-post-form"
                            style="position:absolute; top:10px; left:10px; z-index:10; width:22px; height:22px; border-radius:6px;" checked>
                        <span class="aif-lightbox-trigger dashicons ${isVideo ? 'dashicons-controls-play' : 'dashicons-search'}"
                            data-src="${url}"
                            style="position:absolute; top:10px; right:10px; z-index:10; background:rgba(15,23,42,0.6); color:#fff; border-radius:50%; width:26px; height:26px; display:flex; align-items:center; justify-content:center; font-size:14px; cursor:zoom-in;"></span>
                        <div class="img-wrapper"
                            style="border:3px solid #3b82f6; border-radius:12px; overflow:hidden; background:#f8fafc; aspect-ratio:1/1;">
                            ${isVideo
                                ? `<video src="${url}" style="width:100%; height:100%; object-fit:cover;"></video>`
                                : `<img src="${thumbUrl}" loading="lazy" style="width:100%; height:100%; object-fit:cover;">`}
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; padding:0 4px;">
                            <div style="font-size:10px; border-radius:3px; background:#ede9fe; padding:1px 5px; color:#7c3aed; font-weight:700;">WP</div>
                            <button type="button" class="aif-btn-set-web"
                                data-filename="${id}" data-url="${url}"
                                style="font-size:9px; padding:2px 5px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; cursor:pointer;">
                                Set Web
                            </button>
                        </div>
                    </label>
                `);

                $('#aif-media-modal .aif-image-grid').append($item);
            });

            updateModalCount();
            updateMediaPreview();
            if (window.AIF_Toast) AIF_Toast.show('Đã thêm ảnh từ WordPress Media Library!', 'success');
        });

        wpMediaFrame.open();
    });

    // =========================================================
    // 4. Lightbox
    // =========================================================
    $(document).on('click', '.aif-lightbox-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const src     = $(this).data('src');
        const isVideo = src.toLowerCase().endsWith('.mp4');
        const $container = $('#aif-lightbox-media-container');

        if (isVideo) {
            $container.html(`<video src="${src}" controls autoplay style="max-width:100%; max-height:85vh; border-radius:12px; box-shadow:0 0 40px rgba(0,0,0,0.5);"></video>`);
        } else {
            $container.html(`<img src="${src}" style="max-width:100%; max-height:85vh; border-radius:12px; box-shadow:0 0 40px rgba(0,0,0,0.5); object-fit:contain;">`);
        }
        $('#aif-lightbox').css('display', 'flex').hide().fadeIn(200);
    });

    $('#aif-lightbox-close, #aif-lightbox').on('click', function (e) {
        if (e.target !== this && e.target.id !== 'aif-lightbox-close') return;
        $('#aif-lightbox').fadeOut(200, function () {
            $('#aif-lightbox-media-container').empty();
        });
    });

    // =========================================================
    // 5. AI Generation
    // =========================================================

    // ── Tone selector ─────────────────────────────────────────
    $(document).on('click', '.aif-tone-btn', function () {
        $('.aif-tone-btn').removeClass('active');
        $(this).addClass('active');
        $('#aif-tone-input').val($(this).data('tone'));
    });

    // Helper lấy tone đang chọn
    function getSelectedTone() {
        return $('#aif-tone-input').val() || '';
    }

    // ── Suggestion chips ───────────────────────────────────────
    $('.ai-suggestion-chip').on('click', function () {
        const chipText = $(this).text().trim().replace(/\s+/g, ' ');
        const $desc    = $('#aif-description');
        const current  = $desc.val().trim();
        $desc.val(current ? current + ', ' + chipText : chipText).focus();
    });

    // ── Hiện smart check bar khi có nội dung ─────────────────
    $('#aif-caption').on('input', function () {
        const hasContent = $(this).val().trim().length > 20;
        $('#aif-smart-check-bar').toggle(hasContent);
    });
    // Trigger on page load nếu đã có nội dung
    if ($('#aif-caption').val().trim().length > 20) {
        $('#aif-smart-check-bar').show();
    }

    // ── Smart Check ───────────────────────────────────────────
    $('#btn-smart-check').on('click', function () {
        const content = $('#aif-caption').val();
        const title   = $('#aif-title').val();
        if (!content.trim()) { AIF_Toast && AIF_Toast.show('Nội dung đang trống!', 'error'); return; }

        const $btn = $(this).prop('disabled', true).text('Đang kiểm tra...');

        $.post(ajaxUrl, {
            action: 'aif_smart_check',
            nonce: nonce,
            content: content,
            title: title,
        }, function (res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="font-size:13px;width:13px;height:13px;vertical-align:middle;margin-right:3px;"></span> Kiểm tra ngay');
            if (!res.success) return;

            const d = res.data;

            // Grade badge
            $('#aif-check-grade-badge').text(d.grade).css({
                'background': d.grade_color + '22',
                'color': d.grade_color,
                'border': '2px solid ' + d.grade_color,
            });
            $('#aif-check-label').text('Điểm: ' + d.score + '/100 — ' + d.grade_label)
                .css('color', d.grade_color);
            $('#aif-check-score-fill').css({ 'width': d.score + '%', 'background': d.grade_color });

            // Issues
            const $issues = $('#aif-check-issues');
            if (d.issues && d.issues.length > 0) {
                const iconMap = { error: '❌', warning: '⚠️', info: 'ℹ️' };
                const colorMap = { error: '#fef2f2', warning: '#fffbeb', info: '#eff6ff' };
                const borderMap = { error: '#fca5a5', warning: '#fde68a', info: '#bfdbfe' };
                let html = '';
                d.issues.forEach(function (iss) {
                    html += `<div style="display:flex;align-items:flex-start;gap:6px;padding:6px 9px;border-radius:6px;font-size:12px;margin-bottom:4px;background:${colorMap[iss.type]};border:1px solid ${borderMap[iss.type]};">
                        <span>${iconMap[iss.type]}</span>
                        <span style="color:#374151;">${iss.msg}</span>
                    </div>`;
                });
                $issues.html(html).show();
            } else {
                $issues.html('<div style="font-size:12px;color:#059669;font-weight:600;">✅ Nội dung đạt chất lượng tốt! Không có vấn đề nào.</div>').show();
            }
        });
    });

    // ── Generate (1 version) ──────────────────────────────────
    $('#btn-generate-v2').on('click', function (e) {
        e.preventDefault();
        const description = $('#aif-description').val().trim();
        if (!description) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng nhập mô tả yêu cầu!', 'error');
            else alert('Vui lòng nhập mô tả yêu cầu!');
            return;
        }

        const $btn    = $(this);
        const origHtml = $btn.html();
        const $loader = $('#aif-ai-loader');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:16px;width:16px;height:16px;display:inline-block;animation:aif-rotate .7s linear infinite;"></span> Đang tạo...');
        $loader.addClass('is-active');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aif_generate_content',
                nonce: nonce,
                post_id: postId,
                prompt: description,
                current_content: $('#aif-caption').val(),
                tone: getSelectedTone(),
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    if (data.generated_title) $('#aif-title').val(data.generated_title);
                    if (data.caption) {
                        let fullContent = data.caption;
                        if (data.hashtags) fullContent += '\n\n' + data.hashtags;
                        $('#aif-caption').val(fullContent).trigger('input');
                    }
                    _syncStatusUI('Content updated');
                    if (window.AIF_Toast) AIF_Toast.show('Đã tạo nội dung AI thành công!', 'success');
                } else {
                    if (window.AIF_Toast) AIF_Toast.show('Lỗi AI: ' + (response.data || 'Không thể tạo nội dung.'), 'error');
                    else alert('Lỗi AI: ' + (response.data || ''));
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html(origHtml);
                $loader.removeClass('is-active');
            }
        });
    });

    // ── Generate 3 Variations ─────────────────────────────────
    $('#btn-generate-variations').on('click', function () {
        const description = $('#aif-description').val().trim();
        if (!description) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng nhập mô tả yêu cầu!', 'error');
            return;
        }

        const $btn     = $(this);
        const origHtml = $btn.html();
        const $loader  = $('#aif-ai-loader');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;display:inline-block;animation:aif-rotate .7s linear infinite;"></span> Đang tạo 3 bản...');
        $loader.addClass('is-active');
        $('#aif-variations-panel').hide();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'aif_generate_variations',
                nonce: nonce,
                post_id: postId,
                prompt: description,
                current_content: $('#aif-caption').val(),
                tone: getSelectedTone(),
            },
            success: function (res) {
                if (!res.success) {
                    AIF_Toast && AIF_Toast.show('Lỗi: ' + res.data, 'error');
                    return;
                }
                const vars = res.data.variations;
                if (!vars || vars.length === 0) {
                    AIF_Toast && AIF_Toast.show('Không tạo được phiên bản nào. Thử lại!', 'error');
                    return;
                }

                let html = '';
                vars.forEach(function (v, i) {
                    const fullText   = v.caption || '';
                    const hasMore    = fullText.length > 180;
                    const previewTxt = hasMore
                        ? fullText.substring(0, 180).replace(/\n/g, ' ') + '…'
                        : fullText.replace(/\n/g, ' ');
                    html += `
                    <div class="aif-variation-card" data-index="${i}" style="border:2px solid #e2e8f0;border-radius:10px;padding:14px;transition:border-color .15s,box-shadow .15s;background:#fff;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px;">
                            <div style="font-size:13px;font-weight:700;color:#1e293b;flex:1;">${i + 1}. ${v.generated_title || 'Phiên bản ' + (i + 1)}</div>
                            <button type="button" class="btn-pick-variation aif-btn aif-btn-primary" data-index="${i}"
                                style="font-size:11px;padding:5px 11px;white-space:nowrap;flex-shrink:0;">
                                Chọn
                            </button>
                        </div>

                        <!-- Preview ngắn -->
                        <div class="var-preview" style="font-size:12px;color:#64748b;line-height:1.6;">${previewTxt}</div>

                        <!-- Nội dung đầy đủ (ẩn mặc định) -->
                        <div class="var-full" style="display:none;font-size:12px;color:#374151;line-height:1.8;white-space:pre-wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;margin-top:6px;max-height:260px;overflow-y:auto;">${fullText}</div>

                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;flex-wrap:wrap;gap:6px;">
                            ${v.hashtags ? `<div style="font-size:11px;color:#6366f1;">${v.hashtags}</div>` : '<div></div>'}
                            ${hasMore ? `
                            <button type="button" class="btn-var-expand"
                                style="font-size:11px;color:#6366f1;background:none;border:none;cursor:pointer;padding:0;display:inline-flex;align-items:center;gap:3px;font-weight:600;white-space:nowrap;flex-shrink:0;">
                                <span class="expand-icon dashicons dashicons-arrow-down-alt2" style="font-size:12px;width:12px;height:12px;transition:transform .2s;"></span>
                                <span class="expand-label">Xem đầy đủ</span>
                            </button>` : ''}
                        </div>
                    </div>`;
                });
                $('#aif-variations-list').html(html);
                $('#aif-variations-panel').show();

                // Lưu tạm variations để dùng khi user chọn
                window._aifVariations = vars;
            },
            complete: function () {
                $btn.prop('disabled', false).html(origHtml);
                $loader.removeClass('is-active');
            }
        });
    });

    // Chọn 1 variation → đổ vào editor
    $(document).on('click', '.btn-pick-variation', function (e) {
        e.stopPropagation();
        const idx = parseInt($(this).data('index'));
        const v   = window._aifVariations && window._aifVariations[idx];
        if (!v) return;

        if (v.generated_title) $('#aif-title').val(v.generated_title);
        if (v.caption) {
            let full = v.caption;
            if (v.hashtags) full += '\n\n' + v.hashtags;
            $('#aif-caption').val(full).trigger('input');
        }

        _syncStatusUI('Content updated');
        $('#aif-variations-panel').hide();
        AIF_Toast && AIF_Toast.show('Đã chọn phiên bản ' + (idx + 1) + '!', 'success');
    });

    // Toggle expand / collapse nội dung đầy đủ
    $(document).on('click', '.btn-var-expand', function (e) {
        e.stopPropagation();
        const $card    = $(this).closest('.aif-variation-card');
        const $preview = $card.find('.var-preview');
        const $full    = $card.find('.var-full');
        const $icon    = $(this).find('.expand-icon');
        const $label   = $(this).find('.expand-label');
        const isOpen   = $full.is(':visible');

        $preview.toggle(isOpen);   // ẩn preview khi mở full, hiện lại khi đóng
        $full.slideToggle(180);
        $icon.css('transform', isOpen ? '' : 'rotate(180deg)');
        $label.text(isOpen ? 'Xem đầy đủ' : 'Thu gọn');
    });

    // Hover effect cho variation card
    $(document).on('mouseenter', '.aif-variation-card', function () {
        $(this).css({ 'border-color': '#6366f1', 'box-shadow': '0 0 0 3px rgba(99,102,241,0.1)' });
    }).on('mouseleave', '.aif-variation-card', function () {
        $(this).css({ 'border-color': '#e2e8f0', 'box-shadow': 'none' });
    });

    // Đóng variations panel
    $('#btn-close-variations').on('click', function () {
        $('#aif-variations-panel').hide();
    });

    // =========================================================
    // 6. Content Revision
    // =========================================================
    $('#btn-revise-content').on('click', function () {
        const feedback       = $('#aif-feedback').val().trim();
        const currentCaption = $('#aif-caption').val();

        if (!feedback) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng nhập góp ý sửa đổi!', 'error');
            return;
        }

        const $btn    = $(this);
        const $loader = $('#aif-ai-loader');
        $btn.prop('disabled', true);
        $loader.addClass('is-active');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aif_revise_content',
                nonce: nonce,
                title: $('#aif-title').val(),
                content: currentCaption,
                feedback: feedback
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    if (data.generated_title) $('#aif-title').val(data.generated_title);
                    if (data.caption) $('#aif-caption').val(data.caption);
                    $('#aif-feedback').val('');
                    _syncStatusUI('Content updated');
                    if (window.AIF_Toast) AIF_Toast.show('Đã tối ưu lời văn theo ý bạn!', 'success');
                } else {
                    alert('Lỗi: ' + (response.data || 'Không thể sửa nội dung.'));
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
                $loader.removeClass('is-active');
            }
        });
    });

    function _syncStatusUI(statusKey) {
        $('#aif_manual_status').val(statusKey);
        const $pill = $('.aif-status-pill');
        $pill.removeClass(function (i, cls) {
            return (cls.match(/(^|\s)status-\S+/g) || []).join(' ');
        }).addClass(statusClasses[statusKey]).text(statusLabels[statusKey]);
    }

    // =========================================================
    // 7. Queue Management
    // =========================================================
    $(document).on('click', '.btn-retry-queue', function () {
        const $btn = $(this);
        const id   = $btn.data('id');
        const orig = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;animation:aif-rotate 0.7s linear infinite;display:inline-block;"></span> Đang thử...');

        $.post(ajaxUrl, { action: 'aif_retry_queue_item', nonce: nonce, id: id }, function (res) {
            if (res.success) {
                if (window.AIF_Toast) AIF_Toast.show('Đã đặt lại — bài sẽ được đăng trong vòng 1 phút!', 'success');
                $btn.closest('.aif-failed-item').fadeOut(400, function () {
                    $(this).remove();
                    if ($('.aif-failed-list .aif-failed-item').length === 0) {
                        $('.aif-status-banner.banner-error').fadeOut(300);
                    }
                });
            } else {
                if (window.AIF_Toast) AIF_Toast.show('Lỗi: ' + res.data, 'error');
                $btn.prop('disabled', false).html(orig);
            }
        }).fail(function () {
            if (window.AIF_Toast) AIF_Toast.show('Lỗi kết nối', 'error');
            $btn.prop('disabled', false).html(orig);
        });
    });

    $('#btn-remove-from-queue').on('click', function () {
        if (!confirm('Xóa bài viết này khỏi hàng chờ Facebook?')) return;
        const $btn = $(this);
        $btn.prop('disabled', true).text('Đang gỡ...');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aif_remove_post_from_queue',
                nonce: nonce,
                post_id: postId
            },
            success: function (res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert('Lỗi: ' + res.data);
                    $btn.prop('disabled', false).text('Hủy hàng chờ');
                }
            }
        });
    });

    // =========================================================
    // 8. Validate before Done → custom confirm modal
    // =========================================================
    $('button[name="aif_status_action"]').on('click', function (e) {
        if ($(this).val() !== 'done') return;
        e.preventDefault();

        const title   = $('#aif-title').val().trim();
        const content = $('#aif-caption').val().trim();

        if (!title) {
            if (window.AIF_Toast) AIF_Toast.show('Tiêu đề không được để trống khi hoàn tất!', 'error');
            $('#aif-title').focus();
            return;
        }
        if (!content) {
            if (window.AIF_Toast) AIF_Toast.show('Nội dung không được để trống khi hoàn tất!', 'error');
            $('#aif-caption').focus();
            return;
        }

        const checkedPages   = $('input[name="aif_target_pages[]"]:checked').length;
        const checkedWebsite = $('input[name="aif_target_website"]:checked').length;
        if (checkedPages === 0 && checkedWebsite === 0) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng chọn ít nhất một nơi đăng bài (Website hoặc Fanpage)!', 'error');
            return;
        }

        // ── Tất cả validate OK → hiện custom confirm modal ──────────────────
        const schedule = $('input[name="aif_schedule"]').val();
        const targets  = [];
        $('input[name="aif_target_website"]:checked').each(function () { targets.push('🌐 Website'); });
        $('input[name="aif_target_pages[]"]:checked').each(function () {
            targets.push('📘 ' + $(this).closest('label').text().trim());
        });

        let summaryHtml = '<div style="margin-bottom:6px;"><b>📝 Tiêu đề:</b> ' + $('<div>').text(title.length > 60 ? title.substring(0, 60) + '…' : title).html() + '</div>';
        summaryHtml += '<div style="margin-bottom:6px;"><b>📍 Đăng lên:</b> ' + targets.join(', ') + '</div>';
        if (schedule) {
            const dt = new Date(schedule);
            const formatted = dt.toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric' })
                             + ' ' + dt.toLocaleTimeString('vi-VN', { hour:'2-digit', minute:'2-digit' });
            summaryHtml += '<div><b>🕐 Lịch đăng:</b> ' + formatted + '</div>';
        } else {
            summaryHtml += '<div><b>🕐 Lịch đăng:</b> <span style="color:#059669;font-weight:600;">Đăng ngay</span></div>';
        }

        $('#aif-confirm-done-summary').html(summaryHtml);
        $('#aif-confirm-done-modal').css('display', 'flex');
    });

    // ── Đóng modal xác nhận ──────────────────────────────────────────────────
    $('#aif-confirm-done-close, #aif-confirm-done-cancel').on('click', function () {
        $('#aif-confirm-done-modal').css('display', 'none');
    });
    $('#aif-confirm-done-modal').on('click', function (e) {
        if (e.target === this) $(this).css('display', 'none');
    });

    // ── Bấm "Xác nhận đăng" → thêm hidden input rồi submit form ────────────
    $('#aif-confirm-done-submit').on('click', function () {
        $('#aif-confirm-done-modal').css('display', 'none');
        const $form = $('#aif-post-form');
        $form.find('#_aif_done_trigger').remove();
        $form.append('<input type="hidden" id="_aif_done_trigger" name="aif_status_action" value="done">');
        $form.submit();
    });

    // =========================================================
    // 9. Metrics Auto Sync
    // =========================================================
    function syncAllMetrics() {
        const $btn  = $('#btn-refresh-metrics');
        const $icon = $btn.find('.dashicons');
        $icon.addClass('rotating');
        $btn.prop('disabled', true);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'aif_fetch_all_metrics',
                nonce: nonce,
                post_id: postId
            },
            success: function (res) {
                if (res.success && res.data.updated_data) {
                    const updated = res.data.updated_data;
                    $('.aif-metric-row').each(function () {
                        const resId = $(this).data('result-id');
                        if (updated[resId]) {
                            const d = updated[resId];
                            $(this).find('.count-likes').text(d.likes.toLocaleString());
                            $(this).find('.count-comments').text(d.comments.toLocaleString());
                            $(this).find('.count-shares').text(d.shares.toLocaleString());
                        }
                    });
                    if (window.AIF_Toast) AIF_Toast.show('Đã cập nhật chỉ số tương tác mới nhất!', 'success');
                }
            },
            complete: function () {
                $icon.removeClass('rotating');
                $btn.prop('disabled', false);
            }
        });
    }

    $('#btn-refresh-metrics').on('click', syncAllMetrics);
    syncAllMetrics();

    // Initial Preview — khởi tạo selection từ hidden input rồi render luôn
    // wpUrlMap đã được PHP localize sẵn, uploadUrl dùng làm fallback cho plugin files
    initModalSelected();
    updateMediaPreview();
});
