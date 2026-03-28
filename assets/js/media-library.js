(function ($) {
    const NONCE   = (typeof aif_media !== 'undefined') ? aif_media.nonce   : '';
    const AJAXURL = (typeof aif_media !== 'undefined') ? aif_media.ajax_url : ajaxurl;

    let currentFolder = '';      // '' = all
    let allFiles      = [];      // raw file list from server
    let allFolders    = [];      // folder list from server
    let selected      = new Set(); // selected filenames (folder::name)
    let viewMode      = 'grid';

    // ── Helpers ──────────────────────────────────────────────────────────────
    function fileKey(f) { return (f.folder || '') + '::' + f.name; }

    function isVideo(ext) { return ext === 'mp4'; }

    function toast(msg, type) {
        type = type || 'success';
        if (window.AIF_Toast) AIF_Toast.show(msg, type);
    }

    function esc(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Load data ─────────────────────────────────────────────────────────────
    function loadData(folder) {
        currentFolder = (folder === undefined) ? currentFolder : folder;
        $('#ml-grid').html('<div class="ml-empty"><div class="aif-spinner"></div></div>');

        $.get(AJAXURL, {
            action: 'aif_media_get_folders',
            nonce:  NONCE,
            folder: currentFolder
        }, function (res) {
            if (!res.success) return;
            allFolders = res.data.folders;
            allFiles   = res.data.files;
            renderSidebar();
            renderGrid();
        });
    }

    // ── Sidebar ───────────────────────────────────────────────────────────────
    function renderSidebar() {
        var html = '';
        allFolders.forEach(function (f) {
            var isRoot  = f.name === '';
            var icon    = isRoot ? 'dashicons-category' : 'dashicons-portfolio';
            var active  = currentFolder === f.name ? 'active' : '';
            var label   = isRoot ? 'Tất cả' : f.label;
            html += '<div class="ml-folder-item ' + active + '" data-folder="' + esc(f.name) + '">';
            html +=   '<span class="dashicons ' + icon + '"></span>';
            html +=   '<span class="ml-folder-label">' + esc(label) + '</span>';
            html +=   '<span class="ml-folder-count">' + f.count + '</span>';
            if (!isRoot) {
                html += '<button class="ml-folder-rename dashicons dashicons-edit" data-id="' + f.id + '" data-folder="' + esc(f.name) + '" title="Đổi tên"></button>';
                html += '<button class="ml-folder-del dashicons dashicons-trash" data-id="' + f.id + '" data-folder="' + esc(f.name) + '" title="Xóa chuyên mục"></button>';
            }
            html += '</div>';
        });
        $('#ml-folder-list').html(html);

        var folderLabel = currentFolder === ''
            ? 'Tất cả'
            : (allFolders.find(function (f) { return f.name === currentFolder; }) || {}).label || currentFolder;
        $('#ml-folder-breadcrumb').text(folderLabel);
        $('#ml-folder-icon').attr('class', currentFolder === '' ? 'dashicons dashicons-category' : 'dashicons dashicons-portfolio');
    }

    // ── Grid ──────────────────────────────────────────────────────────────────
    function renderGrid() {
        var q     = $('#ml-search').val().toLowerCase().trim();
        var files = allFiles.filter(function (f) { return !q || f.name.toLowerCase().includes(q); });

        $('#ml-file-count').text(files.length + ' file');

        if (files.length === 0) {
            $('#ml-grid').html(
                '<div class="ml-empty" style="grid-column:1/-1;">' +
                '<span class="dashicons dashicons-format-gallery"></span>' +
                '<p>' + (q ? 'Không tìm thấy file nào.' : 'Chuyên mục này chưa có file nào.') + '</p>' +
                '</div>'
            );
            return;
        }

        var html = '';
        files.forEach(function (f) {
            var key       = fileKey(f);
            var chk       = selected.has(key) ? 'checked' : '';
            var vid       = isVideo(f.ext);
            var typeLabel = vid ? 'VIDEO' : f.ext.toUpperCase();
            var typeClass = vid ? 'is-video' : '';
            var thumb     = vid
                ? '<video src="' + f.url + '" style="width:100%;height:100%;object-fit:cover;pointer-events:none;"></video>'
                : '<img src="' + f.url + '" loading="lazy" alt="' + esc(f.name) + '">';

            var tagHtml = (f.folders && f.folders.length)
                ? f.folders.map(function (fn) { return '<span class="ml-folder-tag">' + esc(fn) + '</span>'; }).join('')
                : '<span class="ml-folder-tag ml-folder-tag--none">Chưa phân loại</span>';

            html += '<div class="ml-card" data-key="' + key + '" data-name="' + esc(f.name) + '" data-folder="' + esc(f.folder) + '" data-folder-ids="' + esc(JSON.stringify(f.folder_ids || [])) + '" data-url="' + esc(f.url) + '">';
            html +=   '<div class="ml-card-thumb" data-lightbox="' + esc(f.url) + '" data-type="' + (vid ? 'video' : 'image') + '" data-name="' + esc(f.name) + '">';
            html +=     thumb;
            html +=     '<span class="ml-card-type ' + typeClass + '">' + typeLabel + '</span>';
            html +=     '<div class="ml-card-check ' + chk + '" data-key="' + key + '"></div>';
            html +=   '</div>';
            html +=   '<div class="ml-card-info">';
            html +=     '<div class="ml-card-name" title="' + esc(f.name) + '">' + esc(f.name) + '</div>';
            html +=     '<div class="ml-card-tags">' + tagHtml + '</div>';
            html +=     '<div class="ml-card-actions">';
            html +=       '<button class="ml-card-btn copy-btn" data-url="' + esc(f.url) + '" title="Copy URL"><span class="dashicons dashicons-admin-links"></span> URL</button>';
            html +=       '<button class="ml-card-btn assign-folder-btn" data-key="' + key + '" data-name="' + esc(f.name) + '" data-folder-ids="' + esc(JSON.stringify(f.folder_ids || [])) + '" title="Chọn chuyên mục"><span class="dashicons dashicons-category"></span></button>';
            html +=       '<button class="ml-card-btn del del-btn" data-key="' + key + '" data-name="' + esc(f.name) + '" data-folder="' + esc(f.folder) + '" title="Xóa"><span class="dashicons dashicons-trash"></span></button>';
            html +=     '</div>';
            html +=   '</div>';
            html += '</div>';
        });
        $('#ml-grid').html(html);
    }

    // ── Selection ─────────────────────────────────────────────────────────────
    function updateBulkBar() {
        if (selected.size > 0) {
            $('#ml-bulk-count').text(selected.size + ' file được chọn');
            $('#ml-bulk-bar').addClass('visible');
        } else {
            $('#ml-bulk-bar').removeClass('visible');
        }
    }

    $(document).on('click', '.ml-card-check', function (e) {
        e.stopPropagation();
        var key = $(this).data('key');
        if (selected.has(key)) { selected.delete(key); $(this).removeClass('checked'); }
        else                   { selected.add(key);    $(this).addClass('checked'); }
        updateBulkBar();
    });

    // ── Folder navigation ─────────────────────────────────────────────────────
    $(document).on('click', '.ml-folder-item', function (e) {
        if ($(e.target).hasClass('ml-folder-del') || $(e.target).closest('.ml-folder-del').length) return;
        var folder = $(this).data('folder');
        selected.clear();
        updateBulkBar();
        loadData(folder);
    });

    // ── Lightbox ──────────────────────────────────────────────────────────────
    $(document).on('click', '.ml-card-thumb', function (e) {
        if ($(e.target).hasClass('ml-card-check') || $(e.target).closest('.ml-card-check').length) return;
        var src  = $(this).data('lightbox');
        var type = $(this).data('type');
        var name = $(this).data('name');
        if (!src) return;
        var media = type === 'video'
            ? '<video src="' + src + '" controls autoplay></video>'
            : '<img src="' + src + '" alt="' + esc(name) + '">';
        $('#ml-lb-media').html(media);
        $('#ml-lb-name').text(name);
        $('#ml-lightbox').addClass('open');
    });
    $('#ml-lb-close, #ml-lightbox').on('click', function (e) {
        if (e.target === this || $(e.target).is('#ml-lb-close')) {
            $('#ml-lightbox').removeClass('open');
            $('#ml-lb-media').html('');
        }
    });

    // ── Search ────────────────────────────────────────────────────────────────
    $('#ml-search').on('input', function () { renderGrid(); });

    // ── View toggle ───────────────────────────────────────────────────────────
    $('#ml-view-grid').on('click', function () {
        viewMode = 'grid';
        $('#ml-grid').removeClass('view-list');
        $('#ml-view-grid').addClass('active');
        $('#ml-view-list').removeClass('active');
    });
    $('#ml-view-list').on('click', function () {
        viewMode = 'list';
        $('#ml-grid').addClass('view-list');
        $('#ml-view-list').addClass('active');
        $('#ml-view-grid').removeClass('active');
    });

    // ── Upload ────────────────────────────────────────────────────────────────
    $('#ml-btn-upload').on('click', function () { $('#ml-file-input').click(); });
    $('#ml-dropzone').on('click', function () { $('#ml-file-input').click(); });

    $('#ml-dropzone').on('dragover dragenter', function (e) {
        e.preventDefault(); $(this).addClass('drag-over');
    }).on('dragleave drop', function (e) {
        e.preventDefault(); $(this).removeClass('drag-over');
        if (e.type === 'drop') {
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) uploadFiles(files);
        }
    });

    $('#ml-file-input').on('change', function () {
        if (this.files.length) uploadFiles(this.files);
        this.value = '';
    });

    function uploadFiles(fileList) {
        var files = Array.from(fileList);
        var done  = 0;
        var successCount = 0;
        var $bar  = $('#ml-progress').addClass('visible');
        var $fill = $('#ml-progress-bar').css('width', '0%');

        function uploadOne(file) {
            var fd = new FormData();
            fd.append('action', 'aif_media_upload');
            fd.append('nonce',  NONCE);
            fd.append('folder', currentFolder === '' ? '' : currentFolder);
            fd.append('file',   file);

            $.ajax({
                url: AJAXURL, type: 'POST', data: fd,
                processData: false, contentType: false,
                success: function (res) {
                    done++;
                    $fill.css('width', (done / files.length * 100) + '%');
                    if (res.success) {
                        successCount++;
                        allFiles.unshift(res.data);
                        allFolders.forEach(function (f) {
                            if (f.name === '' || f.name === res.data.folder) f.count++;
                        });
                    } else {
                        toast('Lỗi upload ' + file.name + ': ' + (res.data || 'Unknown error'), 'error');
                    }
                    if (done === files.length) {
                        finishUploads();
                    }
                },
                error: function () {
                    done++;
                    toast('Lỗi kết nối khi upload ' + file.name, 'error');
                    if (done === files.length) {
                        finishUploads();
                    }
                }
            });
        }

        function finishUploads() {
            setTimeout(function () { $bar.removeClass('visible'); $fill.css('width', '0%'); }, 600);
            renderSidebar();
            renderGrid();
            if (successCount > 0) {
                toast(successCount + ' file đã được tải lên!');
            }
        }

        files.forEach(uploadOne);
    }

    // ── Copy URL ──────────────────────────────────────────────────────────────
    $(document).on('click', '.copy-btn', function (e) {
        e.stopPropagation();
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function () { toast('Đã sao chép URL!'); });
    });

    // ── New folder modal ──────────────────────────────────────────────────────
    $('#ml-btn-new-folder').on('click', function () {
        $('#ml-folder-name-input').val('');
        $('#ml-modal-folder').addClass('open');
        setTimeout(function () { $('#ml-folder-name-input').focus(); }, 100);
    });
    $('#ml-folder-modal-cancel').on('click', function () { $('#ml-modal-folder').removeClass('open'); });
    $('#ml-folder-name-input').on('keydown', function (e) { if (e.key === 'Enter') $('#ml-folder-modal-confirm').click(); });

    $('#ml-folder-modal-confirm').on('click', function () {
        var name = $('#ml-folder-name-input').val().trim();
        if (!name) return;
        $.post(AJAXURL, { action: 'aif_media_create_folder', nonce: NONCE, name: name }, function (res) {
            $('#ml-modal-folder').removeClass('open');
            if (res.success) {
                toast('Đã tạo chuyên mục "' + res.data.name + '"!');
                loadData(res.data.name);
            } else {
                toast(res.data || 'Lỗi tạo chuyên mục', 'error');
            }
        });
    });

    // ── Rename folder ─────────────────────────────────────────────────────────
    var pendingRename = null;

    $(document).on('click', '.ml-folder-rename', function (e) {
        e.stopPropagation();
        pendingRename = { id: $(this).data('id'), name: $(this).data('folder') };
        $('#ml-rename-old-name').text(pendingRename.name);
        $('#ml-rename-input').val(pendingRename.name);
        $('#ml-modal-rename').addClass('open');
        setTimeout(function () { $('#ml-rename-input').focus().select(); }, 100);
    });

    $('#ml-rename-modal-cancel').on('click', function () { $('#ml-modal-rename').removeClass('open'); pendingRename = null; });
    $('#ml-rename-input').on('keydown', function (e) { if (e.key === 'Enter') $('#ml-rename-modal-confirm').click(); });

    $('#ml-rename-modal-confirm').on('click', function () {
        if (!pendingRename) return;
        var newName = $('#ml-rename-input').val().trim();
        if (!newName || newName === pendingRename.name) {
            $('#ml-modal-rename').removeClass('open');
            pendingRename = null;
            return;
        }
        $.post(AJAXURL, { action: 'aif_media_rename_folder', nonce: NONCE, id: pendingRename.id, name: newName }, function (res) {
            $('#ml-modal-rename').removeClass('open');
            pendingRename = null;
            if (res.success) {
                toast('Đã đổi tên thành "' + res.data.name + '"', 'success');
                loadData(res.data.name);
            } else {
                toast(res.data || 'Lỗi đổi tên', 'error');
            }
        });
    });

    // ── Delete folder ─────────────────────────────────────────────────────────
    $(document).on('click', '.ml-folder-del', function (e) {
        e.stopPropagation();
        var id     = $(this).data('id');
        var folder = $(this).data('folder');
        doDeleteFolder(id, folder, false);
    });

    function doDeleteFolder(id, folder, force) {
        $.post(AJAXURL, { action: 'aif_media_delete_folder', nonce: NONCE, id: id, force: force ? 1 : 0 }, function (res) {
            if (res.success) {
                toast('Đã xóa chuyên mục!');
                if (currentFolder === folder) currentFolder = '';
                loadData();
            } else if (res.data && res.data.code === 'not_empty') {
                if (confirm(res.data.msg + '\nCác file sẽ được chuyển về "Chưa phân loại".')) {
                    doDeleteFolder(id, folder, true);
                }
            } else {
                toast((res.data && res.data.msg) || res.data || 'Lỗi', 'error');
            }
        });
    }

    // ── Delete single file ────────────────────────────────────────────────────
    var pendingDelete = null;

    $(document).on('click', '.del-btn', function (e) {
        e.stopPropagation();
        pendingDelete = [{ name: $(this).data('name'), folder: $(this).data('folder') }];
        $('#ml-delete-msg').text('Xóa vĩnh viễn file "' + pendingDelete[0].name + '"?');
        $('#ml-modal-delete').addClass('open');
    });

    $('#ml-delete-modal-cancel').on('click', function () { $('#ml-modal-delete').removeClass('open'); pendingDelete = null; });

    $('#ml-delete-modal-confirm').on('click', function () {
        if (!pendingDelete) return;
        var items = pendingDelete;
        $('#ml-modal-delete').removeClass('open');
        pendingDelete = null;
        var done = 0;
        items.forEach(function (item) {
            $.post(AJAXURL, { action: 'aif_media_delete_file', nonce: NONCE, filename: item.name }, function (res) {
                done++;
                if (res.success) {
                    var key = (item.folder || '') + '::' + item.name;
                    allFiles = allFiles.filter(function (f) { return fileKey(f) !== key; });
                    selected.delete(key);
                    allFolders.forEach(function (fd) {
                        if (fd.name === '' || fd.name === item.folder) fd.count = Math.max(0, fd.count - 1);
                    });
                }
                if (done === items.length) {
                    renderSidebar(); renderGrid(); updateBulkBar();
                    toast(items.length + ' file đã xóa!');
                }
            });
        });
    });

    // ── Assign folder (multi-select) ──────────────────────────────────────────
    var pendingAssign  = null;
    var bulkAssignMode = false;

    $(document).on('click', '.assign-folder-btn', function (e) {
        e.stopPropagation();
        var name      = $(this).data('name');
        var folderIds = $(this).data('folder-ids') || [];
        bulkAssignMode = false;
        pendingAssign  = {
            name: name,
            folder_ids: Array.isArray(folderIds) ? folderIds : JSON.parse(folderIds || '[]')
        };
        openAssignModal();
    });

    $('#ml-bulk-assign').on('click', function () {
        if (!selected.size) return;
        bulkAssignMode = true;
        var selectedFiles = allFiles.filter(function (f) { return selected.has(fileKey(f)); });
        var allIds   = selectedFiles.map(function (f) { return f.folder_ids || []; });
        var commonIds = allIds.reduce(function (acc, ids) { return acc.filter(function (id) { return ids.includes(id); }); }, allIds[0] || []);
        pendingAssign = {
            name: selectedFiles.length + ' file đã chọn',
            folder_ids: commonIds,
            files: selectedFiles
        };
        openAssignModal();
    });

    function openAssignModal() {
        if (!pendingAssign) return;
        var currentIds = pendingAssign.folder_ids;
        var html = '<div style="display:flex;flex-direction:column;gap:8px;max-height:300px;overflow-y:auto;">';
        allFolders.filter(function (f) { return f.name !== ''; }).forEach(function (f) {
            var chk = currentIds.includes(f.id) ? 'checked' : '';
            html += '<label style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;">';
            html += '<input type="checkbox" value="' + f.id + '" ' + chk + ' style="width:16px;height:16px;min-width:16px;border-radius:4px;margin:0;flex-shrink:0;">';
            html += '<span style="font-size:13px;font-weight:600;line-height:1;">' + esc(f.name) + '</span>';
            html += '<span style="margin-left:auto;font-size:11px;color:#94a3b8;line-height:1;white-space:nowrap;">' + f.count + ' file</span>';
            html += '</label>';
        });
        if (!allFolders.filter(function (f) { return f.name !== ''; }).length) {
            html += '<p style="color:#94a3b8;font-size:13px;text-align:center;padding:20px 0;">Chưa có chuyên mục nào. Tạo chuyên mục trước.</p>';
        }
        html += '</div>';
        $('#ml-assign-folder-body').html(html);
        $('#ml-assign-filename').text(pendingAssign.name);
        $('#ml-modal-assign').addClass('open');
    }

    $('#ml-assign-modal-cancel').on('click', function () { $('#ml-modal-assign').removeClass('open'); pendingAssign = null; bulkAssignMode = false; });

    $('#ml-assign-modal-confirm').on('click', function () {
        if (!pendingAssign) return;
        var folder_ids = [];
        $('#ml-assign-folder-body input[type="checkbox"]:checked').each(function () {
            folder_ids.push(parseInt($(this).val()));
        });
        $('#ml-modal-assign').removeClass('open');

        if (bulkAssignMode && pendingAssign.files) {
            var filesToAssign = pendingAssign.files;
            pendingAssign  = null;
            bulkAssignMode = false;
            var done = 0;
            filesToAssign.forEach(function (file) {
                $.post(AJAXURL, { action: 'aif_media_set_folders', nonce: NONCE, filename: file.name, 'folder_ids[]': folder_ids }, function (res) {
                    done++;
                    if (res.success) {
                        var newFolderIds = res.data.folder_ids || [];
                        var oldFolderIds = file.folder_ids   || [];
                        var idx = allFiles.findIndex(function (f) { return f.name === file.name; });
                        if (idx > -1) {
                            allFiles[idx].folders    = res.data.folders;
                            allFiles[idx].folder_ids = newFolderIds;
                            allFiles[idx].folder     = res.data.folder;
                        }
                        var removed = oldFolderIds.filter(function (id) { return !newFolderIds.includes(id); });
                        var added   = newFolderIds.filter(function (id) { return !oldFolderIds.includes(id); });
                        allFolders.forEach(function (f) {
                            if (removed.includes(f.id)) f.count = Math.max(0, f.count - 1);
                            if (added.includes(f.id))   f.count++;
                        });
                    }
                    if (done === filesToAssign.length) {
                        selected.clear();
                        updateBulkBar();
                        renderSidebar();
                        renderGrid();
                        toast('Đã phân loại ' + filesToAssign.length + ' file!', 'success');
                    }
                });
            });
        } else {
            var filename      = pendingAssign.name;
            var oldFolderIds  = pendingAssign.folder_ids || [];
            pendingAssign  = null;
            bulkAssignMode = false;

            $.post(AJAXURL, { action: 'aif_media_set_folders', nonce: NONCE, filename: filename, 'folder_ids[]': folder_ids }, function (res) {
                if (res.success) {
                    var newFolderIds = res.data.folder_ids || [];
                    var idx = allFiles.findIndex(function (f) { return f.name === filename; });
                    if (idx > -1) {
                        allFiles[idx].folders    = res.data.folders;
                        allFiles[idx].folder_ids = newFolderIds;
                        allFiles[idx].folder     = res.data.folder;
                    }
                    var removed = oldFolderIds.filter(function (id) { return !newFolderIds.includes(id); });
                    var added   = newFolderIds.filter(function (id) { return !oldFolderIds.includes(id); });
                    allFolders.forEach(function (f) {
                        if (removed.includes(f.id)) f.count = Math.max(0, f.count - 1);
                        if (added.includes(f.id))   f.count++;
                    });
                    renderSidebar();
                    renderGrid();
                    var label = res.data.folders.length ? res.data.folders.join(', ') : 'Chưa phân loại';
                    toast('Đã gán vào: ' + label, 'success');
                } else {
                    toast(res.data || 'Lỗi', 'error');
                }
            });
        }
    });

    // ── Bulk cancel / delete ──────────────────────────────────────────────────
    $('#ml-bulk-cancel').on('click', function () {
        selected.clear();
        $('.ml-card-check').removeClass('checked');
        updateBulkBar();
    });

    $('#ml-bulk-delete').on('click', function () {
        if (!selected.size) return;
        pendingDelete = allFiles.filter(function (f) { return selected.has(fileKey(f)); });
        $('#ml-delete-msg').text('Xóa vĩnh viễn ' + pendingDelete.length + ' file đã chọn?');
        $('#ml-modal-delete').addClass('open');
    });

    // ── Escape key ────────────────────────────────────────────────────────────
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('#ml-lightbox').removeClass('open'); $('#ml-lb-media').html('');
            $('.ml-modal-backdrop').removeClass('open');
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    loadData('');

})(jQuery);
