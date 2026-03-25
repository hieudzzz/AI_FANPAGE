jQuery(document).ready(function ($) {
    console.log('AIF: admin-script.js is loaded and ready.');

    // --- Dashboard KPI Modal ---
    $(document).on('click', '.clickable-kpi', function (e) {
        e.preventDefault();
        const type = $(this).data('type');
        console.log('AIF Dashboard: KPI Clicked', type);
        // alert('KPI Clicked: ' + type); // Temporary debug alert

        const modal = $('#aif-kpi-modal');
        const resultsBody = $('#aif-kpi-modal-results');
        const loading = $('#aif-kpi-modal-loading');
        const table = $('#aif-kpi-table');

        if (modal.length === 0) {
            console.error('AIF Dashboard: Modal element #aif-kpi-modal not found!');
            alert('Lỗi: Không tìm thấy phần tử modal #aif-kpi-modal trên trang này.');
            return;
        }

        $('#aif-kpi-modal-title').text('Đang tải...');
        resultsBody.empty();
        table.hide();
        loading.show();
        modal.css('display', 'flex').show();

        $.post(aif_ajax.ajax_url, {
            action: 'aif_get_dashboard_list',
            nonce: aif_ajax.nonce,
            type: type
        }, function (res) {
            loading.hide();
            if (res.success) {
                $('#aif-kpi-modal-title').text(res.data.title);
                let html = '';
                if (res.data.posts && res.data.posts.length > 0) {
                    res.data.posts.forEach(function (post, index) {
                        const errorRow = (post.error_summary)
                            ? `<div style="font-size:11px;color:#b91c1c;margin-top:3px;font-weight:500;">${post.error_summary}</div>`
                            : '';
                        html += `
                            <tr>
                                <td style="padding-left: 15px;">${index + 1}</td>
                                <td>
                                    <a href="${post.edit_url}" style="text-decoration: none; font-weight: 600; color: #2271b1;">${post.title}</a>
                                    ${errorRow}
                                </td>
                                <td><span class="aif-status-badge ${post.status_class}">${post.status}</span></td>
                                <td style="text-align: right; padding-right: 15px; color: #666; font-size: 12px;">${post.date}</td>
                            </tr>
                        `;
                    });
                } else {
                    const emptyMsg = (type === 'failed')
                        ? '✅ Không có bài viết nào bị lỗi đăng.'
                        : 'Không có dữ liệu.';
                    html = `<tr><td colspan="4" style="padding: 20px; text-align: center; color: #64748b;">${emptyMsg}</td></tr>`;
                }
                resultsBody.html(html);
                table.show();
            } else {
                $('#aif-kpi-modal-title').text('Thông báo');
                resultsBody.html(`<tr><td colspan="4" style="padding: 40px; text-align: center; color: #666;">${res.data}</td></tr>`);
                table.show();
            }
        }).fail(function (xhr, status, error) {
            loading.hide();
            console.error('AIF Dashboard: AJAX Failed', status, error);
            $('#aif-kpi-modal-title').text('Lỗi');
            resultsBody.html(`<tr><td colspan="4" style="padding: 40px; text-align: center; color: #dc3232;">Kết nối thất bại. Vui lòng thử lại sau.</td></tr>`);
            table.show();
        });
    });

    function getStatusClass(status) {
        if (!status) return 'status-pending';
        status = status.toLowerCase();
        if (status.includes('to do')) return 'status-pending';
        if (status.includes('updated')) return 'status-processing'; // Blue
        if (status.includes('done')) return 'status-future';      // Amber
        if (status.includes('success') || status.includes('posted') || status.includes('connected')) return 'status-publish'; // Green
        if (status.includes('failed')) return 'status-error';    // Red
        return 'status-pending';
    }

    // --- Dashboard Chart ---
    try {
        const ctx = document.getElementById('aifActivityChart');
        if (ctx && typeof Chart !== 'undefined') {
            // Check if chart instance already exists to avoid canvas reuse error
            const existingChart = Chart.getChart(ctx);
            if (!existingChart) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Bài đăng (Posts)',
                            data: [1, 2, 0, 3, 1, 4, 1], // Placeholder data
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34, 113, 177, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'AI Requests',
                            data: [5, 8, 3, 12, 6, 15, 4], // Placeholder data
                            borderColor: '#d63638',
                            borderDash: [5, 5],
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        }
    } catch (e) {
        console.warn('AIF: Chart initialization in admin-script.js skipped or failed:', e);
    }

    // --- Import Google Sheet ---
    // Hidden standard import button which the custom UI triggers will click
    if ($('#btn-import-gsheet').length === 0) {
        // Create a hidden button basically to hold the logic/event, or just append it hidden
        $('body').append('<button id="btn-import-gsheet" style="display:none;">Sync Logic</button>');
    }

    $('#btn-import-gsheet').on('click', function (e) {
        e.preventDefault();
        const url = prompt("Nhập Public CSV URL của Google Sheet:", "https://docs.google.com/spreadsheets/d/e/2PACX-1vT..../pub?output=csv");
        if (url) {
            const btn = $(this);
            btn.text('Syncing...').prop('disabled', true);

            $.post(aif_ajax.ajax_url, {
                action: 'aif_import_gsheet',
                nonce: aif_ajax.nonce,
                csv_url: url
            }, function (res) {
                if (res.success) {
                    alert('Đã sync thành công: ' + res.data.created + ' bài viết mới.');
                    location.reload();
                } else {
                    alert('Lỗi: ' + res.data);
                }
                btn.text('Sync Google Sheet').prop('disabled', false);
            });
        }
    });

    // --- Generate Content (Post Detail) ---
    $('#btn-generate').on('click', function (e) {
        e.preventDefault();
        const btn = $(this);
        const originalText = btn.html();
        const prompt = $('#aif-prompt').val();
        const content = $('#aif-caption').val();
        const platform = $('#aif-platform').val();

        // Get Post ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const postId = urlParams.get('id');

        btn.html('<span class="dashicons dashicons-update spin"></span> Processing...').prop('disabled', true);

        $.post(aif_ajax.ajax_url, {
            action: 'aif_generate_content',
            nonce: aif_ajax.nonce,
            post_id: postId,
            prompt: prompt,
            current_content: content,
            platform: platform
        }, function (res) {
            if (res.success) {
                // Typewriter effect or just replace? Replace for MVP.
                $('#aif-caption').val(res.data.caption);
                $('#preview-text').text(res.data.caption);
            } else {
                alert('AI Error: ' + res.data);
            }
            btn.html(originalText).prop('disabled', false);
        });
    });

    // Copy URL Media
    $('.copy-url').on('click', function () {
        const url = $(this).data('url');
        navigator.clipboard.writeText(url);
        $(this).text('Copied!');
        setTimeout(() => $(this).text('Copy URL'), 2000);
    });
    // AI Suggestion Chips
    $('.ai-suggestion-chip').on('click', function () {
        const text = $(this).text();
        const promptInput = $('#aif-prompt');
        let currentVal = promptInput.val();

        if (currentVal.trim() === '') {
            promptInput.val(text);
        } else {
            promptInput.val(currentVal + ', ' + text);
        }

        // Visual feedback
        $(this).css('transform', 'scale(0.95)');
        setTimeout(() => {
            $(this).css('transform', '');
        }, 100);
    });

    // --- View Content Logic ---
    $(document).on('click', '.btn-view-content', function (e) {
        e.preventDefault();
        const btn = $(this);
        const postId = btn.data('id');

        // Simple Modal or Alert for MVP
        // Let's create a dynamic modal
        let modal = $('#aif-view-modal');
        if (modal.length === 0) {
            $('body').append(`
                <div id="aif-view-modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
                    <div style="background-color:#fefefe; margin:5% auto; padding:20px; border:1px solid #888; width:50%; max-width:600px; max-height:80vh; overflow-y:auto; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <span class="close-modal" style="color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                        <h2 id="modal-title" style="margin-top:0; padding-right: 30px;">loading...</h2>
                        <div id="modal-content" style="white-space: pre-wrap; margin-top: 15px;"></div>
                    </div>
                </div>
            `);

            modal = $('#aif-view-modal');

            // Close logic
            $(document).on('click', '.close-modal', function () {
                modal.hide();
            });
            $(window).on('click', function (event) {
                if (event.target.id == 'aif-view-modal') {
                    modal.hide();
                }
            });
        }

        $('#modal-title').text('Đang tải...');
        $('#modal-content').text('');
        modal.show();

        $.post(aif_ajax.ajax_url, {
            action: 'aif_get_post_details',
            nonce: aif_ajax.nonce,
            post_id: postId
        }, function (res) {
            if (res.success) {
                $('#modal-title').text(res.data.title || 'Bài viết #' + postId);
                $('#modal-content').text(res.data.content || '(Chưa có nội dung)');
            } else {
                $('#modal-title').text('Lỗi');
                $('#modal-content').text(res.data);
            }
        });
    });



    // --- Improved Inline Edit Logic ---
    let activeEditingRow = null;

    function updateRowLockState(row) {
        const status = row.attr('data-status');
        const isQueued = row.attr('data-queued') === '1';

        if (status === 'Posted successfully' || isQueued) {
            row.addClass('locked');
        } else {
            row.removeClass('locked');
        }
    }

    // Initialize locks on load
    $('.wp-list-table tbody tr').each(function () {
        updateRowLockState($(this));
    });

    // Enter edit mode for a cell
    $(document).on('click', '.editable-cell', function (e) {
        if ($(e.target).closest('.edit-mode').length) return;

        const cell = $(this);
        const row = cell.closest('tr');

        // --- Multi-cell Same-Row logic ---
        // If clicking a new row, save the old one first
        if (activeEditingRow && activeEditingRow[0] !== row[0]) {
            saveRow(activeEditingRow);
        }

        activeEditingRow = row;
        row.addClass('row-editing');

        if (row.hasClass('locked')) {
            const status = row.attr('data-status');
            const isQueued = row.attr('data-queued') === '1';
            if (status === 'Posted successfully') { if (window.AIF_Toast) AIF_Toast.show('Bài viết đã đăng, không thể sửa.', 'error'); }
            else if (isQueued) { if (window.AIF_Toast) AIF_Toast.show('Bài viết trong hàng chờ, không thể sửa.', 'error'); }
            return;
        }

        // Show edit mode for this specific cell
        // We do NOT hide other cells' edit-mode in the same row here
        const input = cell.find('.inline-edit-input');
        cell.data('original-value', input.val()); // Store original value to detect changes

        cell.find('.view-mode').hide();
        cell.find('.edit-mode').show().find('.inline-edit-input').focus();
    });

    // Auto-save on click outside
    $(document).on('click', function (e) {
        if (activeEditingRow && !$(e.target).closest(activeEditingRow).length && !$(e.target).closest('#aif-content-modal').length) {
            saveRow(activeEditingRow);
        }
    });

    function saveRow(row) {
        if (!row.hasClass('row-editing')) return;

        const editingCells = row.find('.editable-cell:has(.edit-mode:visible)');
        editingCells.each(function () {
            saveCell($(this));
        });

        row.removeClass('row-editing');
        activeEditingRow = null;
    }

    function saveCell(cell) {
        const field = cell.data('field');
        const row = cell.closest('tr');
        const postId = row.attr('id').replace('post-', '');
        const input = cell.find('.inline-edit-input');
        const newValue = input.val();
        const originalValue = cell.data('original-value');
        const updatedAt = row.attr('data-updated-at');
        const viewMode = cell.find('.view-mode');

        // Check if value actually changed
        if (newValue === originalValue) {
            cell.find('.edit-mode').hide();
            viewMode.show();
            return;
        }

        // Show spinner for status field
        let statusBadgeOriginal = '';
        if (field === 'status') {
            const badge = viewMode.find('.aif-status-badge');
            statusBadgeOriginal = badge.text();
            badge.html('<span class="inline-spinner"></span> Đang lưu...');
        }

        cell.addClass('saving');

        $.post(aif_ajax.ajax_url, {
            action: 'aif_inline_edit_post',
            nonce: aif_ajax.nonce,
            post_id: postId,
            field: field,
            value: newValue,
            last_updated_at: updatedAt
        }, function (res) {
            cell.removeClass('saving');
            if (res.success) {
                if (res.data.updated_at) row.attr('data-updated-at', res.data.updated_at);

                // Update queued state from server response
                if (res.data.is_queued !== undefined) {
                    row.attr('data-queued', res.data.is_queued);
                }

                // Remove red border if content is now valid
                if (newValue && newValue.trim() !== '' && newValue !== 'Trống') {
                    cell.removeClass('aif-invalid-field');
                }

                if (res.data.message && window.AIF_Toast) AIF_Toast.show(res.data.message, 'success');

                if (field === 'status') {
                    const actualValue = res.data.value || newValue;
                    const badge = viewMode.find('.aif-status-pill, .aif-status-badge');
                    // Use localized label for display, keep internal value for data attr
                    const labels = aif_ajax.status_labels || {};
                    const classes = aif_ajax.status_classes || {};

                    badge.text(labels[actualValue] || actualValue);
                    row.attr('data-status', actualValue);

                    // Remove all possible status classes
                    badge.removeClass('status-pending status-processing status-future status-publish status-draft');

                    // Add the new class if it exists in metadata
                    if (classes[actualValue]) {
                        badge.addClass(classes[actualValue]);
                    }
                    updateRowLockState(row);
                }
                else if (field === 'time_posting') {
                    if (res.data.formatted_value) {
                        viewMode.find('.time-value').text(res.data.formatted_value);
                    } else {
                        viewMode.html('<div style="font-size: 11px; color: #aaa; font-style: italic;">Chưa hẹn lịch</div>');
                    }
                }
                else if (field === 'title') {
                    viewMode.text(newValue || 'Trống');
                }
                else if (field === 'description') {
                    viewMode.find('div').text(newValue.length > 50 ? newValue.substring(0, 50) + '...' : newValue);
                }
                else {
                    viewMode.text(newValue || 'Trống');
                }

                cell.find('.edit-mode').hide();
                viewMode.show();

                // Flash row on success
                row.addClass('row-updated-flash');
                setTimeout(() => row.removeClass('row-updated-flash'), 2000);
            } else {
                // Restore original badge text on error
                if (field === 'status' && statusBadgeOriginal) {
                    viewMode.find('.aif-status-badge').text(statusBadgeOriginal);
                }
                // Reset the input back to original value so user can retry
                input.val(originalValue);
                cell.data('original-value', originalValue);
                if (window.AIF_Toast) AIF_Toast.show('Lỗi: ' + res.data, 'error');
                cell.find('.edit-mode').hide();
                viewMode.show();
            }
        }).fail(function () {
            cell.removeClass('saving');
            if (field === 'status' && statusBadgeOriginal) {
                viewMode.find('.aif-status-badge').text(statusBadgeOriginal);
            }
            // Reset the input back to original value so user can retry
            input.val(originalValue);
            cell.data('original-value', originalValue);
            if (window.AIF_Toast) AIF_Toast.show('Kết nối thất bại.', 'error');
            cell.find('.edit-mode').hide();
            viewMode.show();
        });
    }

    // --- Content Modal Logic ---
    let currentModalRow = null;

    $(document).on('click', '.btn-edit-content-modal', function () {
        const row = $(this).closest('tr');
        if (row.hasClass('locked')) return;

        currentModalRow = row;
        const currentContent = row.find('.full-content-store').val();
        row.data('original-content', currentContent); // Store original for comparison

        $('#aif-modal-textarea').val(currentContent);
        $('#aif-content-modal').css('display', 'flex');

        // Mark row as editing
        if (activeEditingRow && activeEditingRow[0] !== row[0]) saveRow(activeEditingRow);
        activeEditingRow = row;
        row.addClass('row-editing');
    });

    $('.close-aif-modal').on('click', function () {
        $('#aif-content-modal').hide();
    });

    $('#btn-save-modal-content').on('click', function () {
        if (!currentModalRow) return;

        const postId = currentModalRow.attr('id').replace('post-', '');
        const newContent = $('#aif-modal-textarea').val();
        const originalContent = currentModalRow.data('original-content');
        const updatedAt = currentModalRow.attr('data-updated-at');
        const btn = $(this);

        // Only save if changed
        if (newContent === originalContent) {
            $('#aif-content-modal').hide();
            return;
        }

        btn.prop('disabled', true).text('Đang lưu...');

        $.post(aif_ajax.ajax_url, {
            action: 'aif_inline_edit_post',
            nonce: aif_ajax.nonce,
            post_id: postId,
            field: 'content',
            value: newContent,
            last_updated_at: updatedAt
        }, function (res) {
            btn.prop('disabled', false).text('Lưu nội dung');
            if (res.success) {
                if (res.data.updated_at) currentModalRow.attr('data-updated-at', res.data.updated_at);
                currentModalRow.find('.full-content-store').val(newContent);
                currentModalRow.find('.content-preview').text(newContent.length > 60 ? newContent.substring(0, 60) + '...' : newContent);

                $('#aif-content-modal').hide();
                if (window.AIF_Toast) AIF_Toast.show('Nội dung đã được cập nhật!', 'success');

                // Flash row
                currentModalRow.addClass('row-updated-flash');
                setTimeout(() => currentModalRow.removeClass('row-updated-flash'), 2000);
            } else {
                if (window.AIF_Toast) AIF_Toast.show('Lỗi: ' + res.data, 'error');
            }
        });
    });

    // --- AI Feedback Logic ---
    $(document).on('click', '.btn-apply-feedback', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        if (row.hasClass('locked')) return;

        const postId = row.attr('id').replace('post-', '');
        const feedback = row.find('.feedback-input').val().trim();

        if (!feedback) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng nhập góp ý!', 'error');
            return;
        }

        btn.prop('disabled', true).html('<span class="inline-spinner"></span>');

        $.post(aif_ajax.ajax_url, {
            action: 'aif_apply_feedback',
            nonce: aif_ajax.nonce,
            post_id: postId,
            feedback: feedback
        }, function (res) {
            btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero"></span> Apply AI');

            if (res.success) {
                // Update UI
                if (res.data.updated_at) row.attr('data-updated-at', res.data.updated_at);

                // Update Title
                row.find('[data-field="title"] .view-mode div').first().text(res.data.title);
                row.find('[data-field="title"] .inline-edit-input').val(res.data.title);

                // Update Content
                row.find('.full-content-store').val(res.data.content);
                row.find('[data-field="content"] .view-mode div').first().text(
                    res.data.content.length > 60 ? res.data.content.substring(0, 60) + '...' : res.data.content
                );

                // Clear feedback
                row.find('.feedback-input').val('');

                if (window.AIF_Toast) AIF_Toast.show('AI đã cập nhật nội dung!', 'success');

                // Flash Row
                row.addClass('row-updated-flash');
                setTimeout(() => row.removeClass('row-updated-flash'), 2000);
            } else {
                if (window.AIF_Toast) AIF_Toast.show('AI Error: ' + res.data, 'error');
            }
        });
    });

    // --- Bulk Action Handler ---
    $('#aif-doaction').on('click', function (e) {
        e.preventDefault();

        const bulkAction = $('#bulk-action-selector-top').val();
        if (bulkAction === '-1') {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng chọn hành động.', 'error');
            return;
        }

        const checkedBoxes = $('input[name="post[]"]:checked');
        if (checkedBoxes.length === 0) {
            if (window.AIF_Toast) AIF_Toast.show('Vui lòng chọn ít nhất 1 bài viết.', 'error');
            return;
        }


        // Clear previous highlights
        $('.aif-invalid-field').removeClass('aif-invalid-field');

        let validPostIds = [];
        let invalidCount = 0;

        // PRE-VALIDATION: Check all selected posts before showing progress UI
        if (bulkAction === 'generate') {
            checkedBoxes.each(function () {
                const postId = $(this).val();
                const row = $('#post-' + postId);
                const industryCell = row.find('[data-field="industry"]');
                const descCell = row.find('[data-field="description"]');
                const industryViewMode = industryCell.find('.view-mode');
                const checkIndustry = (industryViewMode.find('div').first().text().trim() || industryViewMode.text().trim());
                const checkDesc = descCell.find('.view-mode').text().trim();

                let isInvalid = false;
                if (!checkIndustry || checkIndustry === 'Trống') {
                    industryCell.addClass('aif-invalid-field');
                    isInvalid = true;
                }
                if (!checkDesc || checkDesc === 'Trống') {
                    descCell.addClass('aif-invalid-field');
                    isInvalid = true;
                }

                if (isInvalid) {
                    invalidCount++;
                } else {
                    validPostIds.push(postId);
                }
            });

            if (validPostIds.length === 0) {
                if (window.AIF_Toast) AIF_Toast.show('Tất cả bài viết đã chọn đều thiếu Đề tài hoặc Yêu cầu.', 'error');
                return;
            }
        } else {
            // For other actions, just copy all IDs
            checkedBoxes.each(function () { validPostIds.push($(this).val()); });
        }

        const postIds = validPostIds; // Use only valid IDs for processing

        // Show progress
        const $progress = $('#bulk-action-progress');
        const $progressCount = $('#bulk-progress-count');
        const $progressBar = $('#bulk-progress-bar');
        const $bulkLog = $('#bulk-log');
        const $progressHeader = $('#bulk-progress-header');
        const $dismissBtn = $('#bulk-progress-dismiss');

        $progress.slideDown(200);
        $bulkLog.empty();
        $progressBar.css('width', '0%').removeClass('complete');
        $progressCount.text('0/' + postIds.length);
        if ($progressHeader) $progressHeader.text('Đang xử lý...');
        if ($dismissBtn) $dismissBtn.hide();

        // Log pre-skipped items if any
        if (invalidCount > 0) {
            $bulkLog.append('<div class="bulk-log-item error"><span class="dashicons dashicons-warning"></span> Đã bỏ qua <strong>' + invalidCount + '</strong> bài viết do thiếu thông tin.</div>');
        }

        let processed = 0;
        let successCount = 0;
        let errorCount = 0;

        function processNext(index) {
            if (index >= postIds.length) {
                // Done - show summary
                $progressBar.addClass('complete');
                if ($progressHeader) $progressHeader.text('Hoàn tất xử lý');
                const summaryHtml = '<div class="bulk-log-summary">' +
                    '<span class="dashicons dashicons-yes-alt"></span> ' +
                    '<strong>' + successCount + '</strong> thành công, ' +
                    '<strong>' + errorCount + '</strong> lỗi / ' +
                    '<strong>' + postIds.length + '</strong> bài viết' +
                    '</div>';
                $bulkLog.append(summaryHtml);
                $bulkLog.scrollTop($bulkLog[0].scrollHeight);
                if ($dismissBtn) $dismissBtn.show();
                return;
            }

            const postId = postIds[index];
            const row = $('#post-' + postId);
            const stt = row.data('stt') || postId;

            // Individual post validation (Already filtered, but keep for robustness or other actions)
            if (bulkAction === 'generate') {
                const industryCell = row.find('[data-field="industry"]');
                const descCell = row.find('[data-field="description"]');
                const industryViewMode2 = industryCell.find('.view-mode');
                const checkIndustry = (industryViewMode2.find('div').first().text().trim() || industryViewMode2.text().trim());
                const checkDesc = descCell.find('.view-mode').text().trim();

                if (!checkIndustry || checkIndustry === 'Trống' || !checkDesc || checkDesc === 'Trống') {
                    processed++;
                    const pct = Math.round((processed / postIds.length) * 100);
                    $progressBar.css('width', pct + '%');
                    $progressCount.text(processed + '/' + postIds.length);
                    errorCount++;
                    $bulkLog.append('<div class="bulk-log-item error"><span class="dashicons dashicons-no"></span> Bài STT ' + stt + ': Thiếu Đề tài hoặc Yêu cầu</div>');
                    $bulkLog.scrollTop($bulkLog[0].scrollHeight);
                    setTimeout(function () { processNext(index + 1); }, 100);
                    return;
                }
            }

            $.post(aif_ajax.ajax_url, {
                action: 'aif_bulk_process_item',
                nonce: aif_ajax.nonce,
                post_id: postId,
                bulk_action: bulkAction,
                stt: stt
            }, function (res) {
                processed++;
                const pct = Math.round((processed / postIds.length) * 100);
                $progressBar.css('width', pct + '%');
                $progressCount.text(processed + '/' + postIds.length);

                if (res.success) {
                    successCount++;
                    $bulkLog.append('<div class="bulk-log-item success"><span class="dashicons dashicons-yes"></span> Bài STT ' + stt + ': ' + res.data.message.replace(/^STT\s*\d+\s*:\s*/i, '') + '</div>');

                    // Update row status in table
                    if (res.data.new_status) {
                        const badge = row.find('[data-field="status"] .aif-status-pill');
                        const labels = aif_ajax.status_labels || {};
                        const classes = aif_ajax.status_classes || {};
                        badge.text(labels[res.data.new_status] || res.data.new_status);
                        row.attr('data-status', res.data.new_status);
                        badge.removeClass('status-draft status-pending status-processing status-future status-publish status-error');
                        if (classes[res.data.new_status]) badge.addClass(classes[res.data.new_status]);
                        updateRowLockState(row);
                    }

                    // Update title if generated
                    if (res.data.generated_title) {
                        const titleViewDiv = row.find('[data-field="title"] .view-mode div').first();
                        if (titleViewDiv.length) {
                            titleViewDiv.text(res.data.generated_title);
                        } else {
                            row.find('[data-field="title"] .view-mode').text(res.data.generated_title);
                        }
                    }

                    // Update content preview in real-time
                    if (res.data.content_preview) {
                        row.find('[data-field="content"] .view-mode div').first().text(res.data.content_preview);
                    }
                    if (res.data.content) {
                        row.find('[data-field="content"] .full-content-store').val(res.data.content);
                    }

                    // Flash row
                    row.addClass('row-updated-flash');
                    setTimeout(() => row.removeClass('row-updated-flash'), 2000);
                } else {
                    errorCount++;
                    $bulkLog.append('<div class="bulk-log-item error"><span class="dashicons dashicons-no"></span> Bài STT ' + stt + ': ' + res.data + '</div>');
                }

                // Auto-scroll log
                $bulkLog.scrollTop($bulkLog[0].scrollHeight);

                // Process next with small delay
                setTimeout(function () { processNext(index + 1); }, 300);
            }).fail(function () {
                processed++;
                errorCount++;
                const pct = Math.round((processed / postIds.length) * 100);
                $progressBar.css('width', pct + '%');
                $progressCount.text(processed + '/' + postIds.length);
                $bulkLog.append('<div class="bulk-log-item error"><span class="dashicons dashicons-no"></span> Bài STT ' + postId + ': Kết nối thất bại</div>');
                $bulkLog.scrollTop($bulkLog[0].scrollHeight);
                setTimeout(function () { processNext(index + 1); }, 300);
            });
        }

        processNext(0);
    });


    // --- Shortcuts ---
    $(document).on('keydown', '.inline-edit-input', function (e) {
        if (e.key === "Escape") {
            const cell = $(this).closest('.editable-cell');
            cell.find('.edit-mode').hide();
            cell.find('.view-mode').show();
            // Re-load original value? (Simplified: just close, next edit will have last saved)
        }
        if (e.key === "Enter" && (e.ctrlKey || !$(this).is('textarea'))) {
            saveRow($(this).closest('tr'));
        }
    });

    // === POST TYPE → Dynamic Category Loading (Post Detail Page) ===
    // Multi post type checkboxes: load categories grouped by post type
    var _aifCatRequestId = 0; // Track latest request batch to ignore stale responses
    $(document).on('change', '.aif-post-type-checkbox', function () {
        var currentRequestId = ++_aifCatRequestId; // Increment on every change

        const checkedTypes = [];
        $('.aif-post-type-checkbox:checked').each(function () {
            checkedTypes.push($(this).val());
        });
        const container = $('#aif-category-list');
        if (checkedTypes.length === 0) {
            container.html('<em style="color:#999;">Vui lòng chọn ít nhất một Post Type.</em>');
            return;
        }
        container.html('<em style="color:#999;">Đang tải danh mục...</em>');

        // Load categories from all selected post types, grouped by post type
        let groupResults = []; // [{postType, label, terms}]
        let loaded = 0;
        checkedTypes.forEach(function (postType) {
            $.post(aif_ajax.ajax_url, {
                action: 'aif_get_taxonomies',
                nonce: aif_ajax.nonce,
                post_type: postType
            }, function (res) {
                // Ignore if a newer request batch has started
                if (currentRequestId !== _aifCatRequestId) return;

                loaded++;
                const label = (res.success && res.data.post_type_label) ? res.data.post_type_label : postType;
                const terms = (res.success && res.data.terms) ? res.data.terms : [];
                groupResults.push({ postType: postType, label: label, terms: terms });

                // When all requests done, render grouped
                if (loaded === checkedTypes.length) {
                    // Sort groups by original checkedTypes order
                    groupResults.sort(function (a, b) {
                        return checkedTypes.indexOf(a.postType) - checkedTypes.indexOf(b.postType);
                    });

                    let hasAnyCat = false;
                    let html = '';
                    groupResults.forEach(function (group) {
                        if (group.terms.length > 0) {
                            hasAnyCat = true;
                            html += '<div class="aif-cat-group" style="margin-bottom:10px;">';
                            html += '<div style="font-size:11px; font-weight:700; color:#6366f1; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; padding-bottom:4px; border-bottom:1px dashed #e2e8f0;">';
                            html += group.label + ' <code style="font-size:10px;color:#94a3b8;">(' + group.postType + ')</code>';
                            html += '</div>';
                            group.terms.forEach(function (t) {
                                html += '<label style="display:flex; align-items:center; gap:8px; margin-bottom:6px; cursor:pointer; font-size:13px; padding-left:4px;">';
                                html += '<input type="checkbox" name="aif_wp_category[]" value="' + t.id + '"> ';
                                html += t.name;
                                html += '</label>';
                            });
                            html += '</div>';
                        }
                    });

                    if (!hasAnyCat) {
                        container.html('<em style="color:#999;">Các Post Type đã chọn không có danh mục phân cấp.</em>');
                    } else {
                        container.html(html);
                    }
                }
            });
        });
    });

    // === UNSAVED CHANGES WARNING (Post Detail Page) ===
    (function () {
        const $form = $('form[action*="aif_save_post"], form#aif-post-form, .aif-detail-form form, .wrap.aif-container form');
        if ($form.length === 0) return;

        let formDirty = false;

        $form.on('change input', 'input, textarea, select', function () {
            formDirty = true;
        });

        // Also track checkbox clicks in category list
        $(document).on('change', '#aif-category-list input[type="checkbox"]', function () {
            formDirty = true;
        });

        // Reset on form submit
        $form.on('submit', function () {
            formDirty = false;
        });

        $(window).on('beforeunload', function (e) {
            if (formDirty) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    })();

    // === AUTO REFRESH METRICS (Post Detail Page) ===
    (function () {
        const refreshStatus = $('#aif-metrics-refresh-status');
        if (!refreshStatus.length) return;

        const postId = refreshStatus.data('post-id');
        if (!postId) return;

        // Show status
        refreshStatus.show();

        $.post(aif_ajax.ajax_url, {
            action: 'aif_fetch_all_metrics',
            nonce: aif_ajax.nonce,
            post_id: postId
        }, function (res) {
            refreshStatus.hide();
            if (res.success && res.data.updated_data) {
                const data = res.data.updated_data;
                // Update rows
                $('.aif-result-row').each(function () {
                    const row = $(this);
                    const resultId = row.data('result-id');
                    if (data[resultId]) {
                        const metrics = data[resultId];
                        row.find('.col-likes').text(metrics.likes);
                        row.find('.col-comments').text(metrics.comments);
                        row.find('.col-shares').text(metrics.shares);
                        row.find('.col-updated').text(metrics.updated_at);

                        // Visual feedback (optional highlight)
                        row.css('background-color', '#f0f9ff');
                        setTimeout(() => row.css('background-color', ''), 2000);
                    }
                });

                if (window.AIF_Toast) {
                    AIF_Toast.show('Đã tự động cập nhật số liệu mới nhất.', 'success');
                }
            } else {
                console.error('AIF: Auto-refresh failed', res);
            }
        }).fail(function () {
            refreshStatus.hide();
            console.error('AIF: Auto-refresh connection failed');
        });
    })();

    // Close modal on outside click
    $(window).on('click', function (event) {
        if (event.target.id == 'aif-kpi-modal') {
            $('#aif-kpi-modal').hide();
        }
    });

    // Close on Escape key
    $(document).on('keydown', function (e) {
        if (e.key === "Escape") {
            $('#aif-kpi-modal').hide();
        }
    });

});
