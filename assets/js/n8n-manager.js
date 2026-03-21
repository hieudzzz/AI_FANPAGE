    (function ($) {
        const NONCE = aif_n8n.nonce;
        const AJAXURL = aif_n8n.ajax_url;

        // ── Helpers ──────────────────────────────────────────────────────────────
        function esc(s) { return $('<div>').text(s || '').html(); }

        function toast(msg, type) {
            if (window.AIF_Toast) AIF_Toast.show(msg, type || 'success');
            else alert(msg);
        }

        // ── Load policies ─────────────────────────────────────────────────────────
        function loadPolicies() {
            $.post(AJAXURL, { action: 'aif_n8n_get_policies', nonce: NONCE }, function (res) {
                if (!res.success) return;
                const policies = res.data;
                renderPolicies(policies);

                // Badge
                $('#kpi-badge-policies').text(policies.length);

                // Stats bar
                const active = policies.filter(p => parseInt(p.is_active)).length;
                const inactive = policies.length - active;
                $('#policy-stats-bar').html(`
                <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#dcfce7;border-radius:20px;font-size:12px;font-weight:700;color:#16a34a;">
                    <span class="dashicons dashicons-yes-alt" style="font-size:14px;width:14px;height:14px;"></span>
                    ${active} bật
                </div>
                <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#f1f5f9;border-radius:20px;font-size:12px;font-weight:700;color:#64748b;">
                    <span class="dashicons dashicons-hidden" style="font-size:14px;width:14px;height:14px;"></span>
                    ${inactive} tắt
                </div>
                <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#dbeafe;border-radius:20px;font-size:12px;font-weight:700;color:#2563eb;">
                    <span class="dashicons dashicons-info-outline" style="font-size:14px;width:14px;height:14px;"></span>
                    Chatbot dùng ${active} chính sách
                </div>
            `);
            });
        }

        function renderPolicies(policies) {
            if (policies.length === 0) {
                $('#policy-list').html(`
                <div style="text-align:center;padding:80px 20px;color:#94a3b8;background:#fff;border-radius:12px;border:1px solid #f1f5f9;">
                    <span class="dashicons dashicons-media-document" style="font-size:48px;width:48px;height:48px;display:block;margin:0 auto 16px;opacity:0.25;"></span>
                    <p style="font-size:15px;font-weight:600;margin:0 0 8px;">Chưa có chính sách nào</p>
                    <p style="font-size:13px;margin:0;">Nhấn <b>"Thêm chính sách"</b> để bắt đầu xây dựng knowledge base cho chatbot.</p>
                </div>
            `);
                return;
            }

            let html = '';
            policies.forEach(function (p, i) {
                const active = parseInt(p.is_active);
                const preview = p.content.length > 160 ? p.content.substring(0, 160) + '...' : p.content;
                html += `
            <div class="policy-item" data-id="${p.id}"
                style="background:${active ? '#fff' : '#fafafa'};border-bottom:1px solid #f1f5f9;padding:16px 20px;
                display:flex;align-items:flex-start;gap:14px;transition:background 0.2s;opacity:${active ? 1 : 0.55};">

                <!-- Drag handle -->
                <div class="policy-drag-handle" title="Kéo để sắp xếp"
                    style="cursor:grab;color:#cbd5e1;padding-top:2px;flex-shrink:0;">
                    <span class="dashicons dashicons-menu" style="font-size:18px;width:18px;height:18px;"></span>
                </div>

                <!-- Toggle -->
                <div style="flex-shrink:0;padding-top:1px;">
                    <label class="aif-policy-toggle">
                        <input type="checkbox" class="policy-toggle-input" data-id="${p.id}" ${active ? 'checked' : ''}>
                        <span class="aif-policy-slider"></span>
                    </label>
                </div>

                <!-- Content -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-size:14px;font-weight:700;color:#1e293b;">${esc(p.title)}</span>
                        ${active
                        ? `<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:#dcfce7;color:#16a34a;">● BẬT</span>`
                        : `<span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:#f1f5f9;color:#94a3b8;">○ TẮT</span>`}
                    </div>
                    <p style="font-size:12px;color:#64748b;margin:0;line-height:1.6;white-space:pre-wrap;">${esc(preview)}</p>
                    <div style="font-size:11px;color:#94a3b8;margin-top:8px;">
                        ${p.content.length} ký tự &bull; Cập nhật: ${p.updated_at ? p.updated_at.substring(0, 16) : '—'}
                    </div>
                </div>

                <!-- Actions -->
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <button class="button button-small btn-edit-policy" data-id="${p.id}"
                        style="border-radius:6px;border-color:var(--aif-border-light);color:var(--aif-primary);padding:4px 10px;display:inline-flex;align-items:center;gap:4px;" title="Sửa">
                        <span class="dashicons dashicons-edit" style="font-size:14px;margin-top:3px;"></span>
                    </button>
                    <button class="button button-small btn-delete-policy" data-id="${p.id}" data-title="${esc(p.title)}"
                        style="border-radius:6px;border-color:#fee2e2;color:#ef4444;padding:4px 8px;" title="Xóa">
                        <span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span>
                    </button>
                </div>
            </div>`;
            });
            $('#policy-list').html(html);
            initSortable();
        }

        // ── Sortable (drag & drop) ────────────────────────────────────────────────
        function initSortable() {
            if (!window.Sortable) return;
            const el = document.getElementById('policy-list');
            if (el._sortable) el._sortable.destroy();
            el._sortable = Sortable.create(el, {
                handle: '.policy-drag-handle',
                animation: 150,
                onEnd: function () {
                    const ids = $('#policy-list .policy-item').map(function () { return $(this).data('id'); }).get();
                    $.post(AJAXURL, { action: 'aif_n8n_reorder_policies', nonce: NONCE, ids: ids });
                }
            });
        }

        // ── Modal open/close ──────────────────────────────────────────────────────
        function openPolicyModal(policy) {
            if (policy) {
                $('#policy-modal-title').text('Sửa chính sách');
                $('#policy-edit-id').val(policy.id);
                $('#policy-title-input').val(policy.title);
                $('#policy-content-input').val(policy.content);
                $('#policy-char-count').text(policy.content.length + ' ký tự');
            } else {
                $('#policy-modal-title').text('Thêm chính sách mới');
                $('#policy-edit-id').val('');
                $('#policy-title-input').val('');
                $('#policy-content-input').val('');
                $('#policy-char-count').text('0 ký tự');
            }
            $('#aif-policy-modal').addClass('active');
            setTimeout(function () { $('#policy-title-input').focus(); }, 200);
        }

        function closePolicyModal() { $('#aif-policy-modal').removeClass('active'); }

        // ── Events ────────────────────────────────────────────────────────────────
        $('#btn-add-policy').on('click', function () { openPolicyModal(null); });
        $('#policy-modal-close, #policy-modal-cancel').on('click', closePolicyModal);
        $('#aif-policy-modal').on('click', function (e) { if (e.target === this) closePolicyModal(); });

        $('#policy-content-input').on('input', function () {
            $('#policy-char-count').text($(this).val().length + ' ký tự');
        });
        $('#policy-title-input, #policy-content-input').on('focus', function () {
            $(this).css('border-color', '#3b82f6');
        }).on('blur', function () {
            $(this).css('border-color', '#e2e8f0');
        });

        // Save
        $('#policy-modal-save').on('click', function () {
            const id = $('#policy-edit-id').val();
            const title = $('#policy-title-input').val().trim();
            const content = $('#policy-content-input').val().trim();

            if (!title) { toast('Vui lòng nhập tiêu đề.', 'error'); $('#policy-title-input').focus(); return; }
            if (!content) { toast('Vui lòng nhập nội dung.', 'error'); $('#policy-content-input').focus(); return; }

            const $btn = $(this).prop('disabled', true).text('Đang lưu...');
            $.post(AJAXURL, { action: 'aif_n8n_save_policy', nonce: NONCE, id: id, title: title, content: content }, function (res) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved" style="font-size:15px;width:15px;height:15px;"></span> Lưu chính sách');
                if (res.success) {
                    closePolicyModal();
                    loadPolicies();
                    toast(id ? 'Đã cập nhật chính sách!' : 'Đã thêm chính sách mới!');
                } else {
                    toast(res.data || 'Lỗi lưu', 'error');
                }
            });
        });

        // Toggle active
        $(document).on('change', '.policy-toggle-input', function () {
            const $input = $(this);
            const $label = $input.closest('.aif-policy-toggle');
            const id = $input.data('id');
            const isChecked = $input.prop('checked');

            $label.addClass('is-saving');

            $.post(AJAXURL, { action: 'aif_n8n_toggle_policy', nonce: NONCE, id: id }, function (res) {
                $label.removeClass('is-saving');
                if (res.success) {
                    toast(isChecked ? 'Đã bật chính sách' : 'Đã tắt chính sách');
                    // Không re-render cả list để tránh giật, chỉ load lại stats bar / badges
                    $.post(AJAXURL, { action: 'aif_n8n_get_policies', nonce: NONCE }, function (res2) {
                        if (!res2.success) return;
                        const policies = res2.data;
                        const active = policies.filter(p => parseInt(p.is_active)).length;
                        $('#kpi-badge-policies').text(policies.length);
                        // Cập nhật stats bar (như trong loadPolicies)
                        const inactive = policies.length - active;
                        $('#policy-stats-bar').html(`
                            <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#dcfce7;border-radius:20px;font-size:12px;font-weight:700;color:#16a34a;">
                                <span class="dashicons dashicons-yes-alt" style="font-size:14px;width:14px;height:14px;"></span>
                                ${active} bật
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#f1f5f9;border-radius:20px;font-size:12px;font-weight:700;color:#64748b;">
                                <span class="dashicons dashicons-hidden" style="font-size:14px;width:14px;height:14px;"></span>
                                ${inactive} tắt
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;padding:5px 10px;background:#dbeafe;border-radius:20px;font-size:12px;font-weight:700;color:#2563eb;">
                                <span class="dashicons dashicons-info-outline" style="font-size:14px;width:14px;height:14px;"></span>
                                Chatbot dùng ${active} chính sách
                            </div>
                        `);
                        // Cập nhật opacity của item hiện tại (optimistic)
                        $label.closest('.policy-item').css('opacity', isChecked ? '1' : '0.55');
                        $label.closest('.policy-item').css('background', isChecked ? '#fff' : '#fafafa');
                        // Cập nhật cái badge BẬT/TẮT bên cạnh title
                        const $stateBadge = $label.closest('.policy-item').find('span[style*="font-size:10px"]');
                        if (isChecked) {
                            $stateBadge.css({ 'background': '#dcfce7', 'color': '#16a34a' }).html('● BẬT');
                        } else {
                            $stateBadge.css({ 'background': '#f1f5f9', 'color': '#94a3b8' }).html('○ TẮT');
                        }
                    });
                } else {
                    $input.prop('checked', !isChecked); // Revert
                    toast(res.data || 'Lỗi khi cập nhật', 'error');
                }
            });
        });

        // Edit
        $(document).on('click', '.btn-edit-policy', function () {
            const id = $(this).data('id');
            $.post(AJAXURL, { action: 'aif_n8n_get_policies', nonce: NONCE }, function (res) {
                if (!res.success) return;
                const p = res.data.find(function (x) { return parseInt(x.id) === parseInt(id); });
                if (p) openPolicyModal(p);
            });
        });

        // Delete
        $(document).on('click', '.btn-delete-policy', function () {
            const id = $(this).data('id');
            const title = $(this).data('title');
            if (!confirm('Xóa chính sách "' + title + '"?\nHành động này không thể hoàn tác.')) return;
            $.post(AJAXURL, { action: 'aif_n8n_delete_policy', nonce: NONCE, id: id }, function (res) {
                if (res.success) { loadPolicies(); toast('Đã xóa chính sách.'); }
                else toast(res.data || 'Lỗi xóa', 'error');
            });
        });

        // ── Load badge ngay khi trang load ───────────────────────────────────────
        $.post(AJAXURL, { action: 'aif_n8n_get_policies', nonce: NONCE }, function (res) {
            if (res.success) $('#kpi-badge-policies').text(res.data.length);
        });

        // ── Expose ra window để chat-bot.js gọi được ─────────────────────────
        window.loadPolicies = loadPolicies;

        // ── Flush AI cache ────────────────────────────────────────────────────
        $(document).on('click', '#btn-flush-ai-cache', function () {
            const $btn = $(this).prop('disabled', true);
            const orig = $btn.html();
            $btn.html('<span class="dashicons dashicons-update" style="font-size:15px;width:15px;height:15px;animation:aif-rotate 0.7s linear infinite;display:inline-block;"></span> Đang xóa...');
            $.post(AJAXURL, { action: 'aif_flush_ai_cache', nonce: NONCE }, function (res) {
                $btn.prop('disabled', false).html(orig);
                if (res.success) {
                    toast('✅ Đã xóa ' + (res.data.deleted / 2 | 0) + ' cache entry', 'success');
                } else {
                    toast('Lỗi: ' + res.data, 'error');
                }
            });
        });

        // ── Bind tab click (fallback nếu chat-bot.js không xử lý) ────────────
        $(document).on('click', '.aif-n8n-tab[data-tab="policies"]', function () {
            loadPolicies();
        });

        // ── Nếu tab policies đang active khi load trang → load ngay ──────────
        if ($('.aif-n8n-tab[data-tab="policies"]').hasClass('active')) {
            loadPolicies();
        }

        // Load Sortable.js nếu chưa có
        if (!window.Sortable) {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
            document.head.appendChild(s);
        }

    })(jQuery);
    document.addEventListener('DOMContentLoaded', function () {
        const tooltip = document.createElement('div');
        tooltip.className = 'aif-lead-score-tooltip';
        tooltip.innerHTML = `
        <div class="aif-tt-title">📊 Tiêu chí điểm Lead</div>
        <div class="aif-tt-divider"></div>
        <div class="aif-tt-row">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#ef4444,#f97316);color:#fff;">🔥 90–100</span>
            <div class="aif-tt-desc"><strong>Quá tiềm năng</strong><br>Đã hỏi giá, muốn đặt hàng</div>
        </div>
        <div class="aif-tt-row">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;">💎 70–89</span>
            <div class="aif-tt-desc"><strong>Cao – Hứa hẹn</strong><br>Rõ nhu cầu, đang tìm hiểu</div>
        </div>
        <div class="aif-tt-row">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;">⭐ 40–69</span>
            <div class="aif-tt-desc"><strong>Trung bình</strong><br>Ghé xem, chưa rõ ý định</div>
        </div>
        <div class="aif-tt-row" style="margin-bottom:0;">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#64748b,#94a3b8);color:#fff;">❄️ 0–39</span>
            <div class="aif-tt-desc"><strong>Thấp – Lạnh</strong><br>Chỉ lướt, chưa quan tâm</div>
        </div>
        <div class="aif-tt-arrow"></div>
    `;
        document.body.appendChild(tooltip);

        const scoreInfo = document.querySelector('.aif-lead-score-info');
        if (scoreInfo) {
            scoreInfo.addEventListener('mouseenter', function () {
                const rect = this.getBoundingClientRect();
                const tw = tooltip.offsetWidth || 260;
                const th = tooltip.offsetHeight || 220;
                const gap = 10;

                let left = rect.left + rect.width / 2 - tw / 2;
                let top = rect.top - th - gap;

                // Clamp ngang
                if (left < 10) left = 10;
                if (left + tw > window.innerWidth - 10) left = window.innerWidth - tw - 10;

                // Nếu vượt lên trên viewport thì xuống dưới icon
                if (top < 10) top = rect.bottom + gap;

                tooltip.style.left = left + 'px';
                tooltip.style.top = top + 'px';
                tooltip.classList.add('visible');
            });

            scoreInfo.addEventListener('mouseleave', function () {
                tooltip.classList.remove('visible');
            });
        }

        // ── Tooltip tiêu chí Khách hàng tiềm năng ──
        const criteriaTooltip = document.createElement('div');
        criteriaTooltip.className = 'aif-lead-criteria-tooltip';
        criteriaTooltip.innerHTML = `
        <div class="aif-tt-title">👥 Tiêu chí Khách hàng tiềm năng</div>
        <div class="aif-tt-divider"></div>
        <div class="aif-tt-row">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#059669,#34d399);color:#fff;">📞</span>
            <div class="aif-tt-desc"><strong>Có số điện thoại</strong><br>Khách đã cung cấp SĐT trong cuộc trò chuyện</div>
        </div>
        <div class="aif-tt-row" style="margin-bottom:0;">
            <span class="aif-tt-badge" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;">📍</span>
            <div class="aif-tt-desc"><strong>Có địa chỉ giao hàng</strong><br>Khách đã cung cấp địa chỉ nhận hàng</div>
        </div>
    `;
        document.body.appendChild(criteriaTooltip);

        const criteriaInfo = document.querySelector('.aif-lead-criteria-info');
        if (criteriaInfo) {
            criteriaInfo.addEventListener('mouseenter', function (e) {
                e.stopPropagation();
                const rect = this.getBoundingClientRect();
                const tw = criteriaTooltip.offsetWidth || 270;
                const th = criteriaTooltip.offsetHeight || 160;
                const gap = 10;

                let left = rect.left + rect.width / 2 - tw / 2;
                let top = rect.bottom + gap;

                if (left < 10) left = 10;
                if (left + tw > window.innerWidth - 10) left = window.innerWidth - tw - 10;
                if (top + th > window.innerHeight - 10) top = rect.top - th - gap;

                criteriaTooltip.style.left = left + 'px';
                criteriaTooltip.style.top = top + 'px';
                criteriaTooltip.classList.add('visible');
            });

            criteriaInfo.addEventListener('mouseleave', function () {
                criteriaTooltip.classList.remove('visible');
            });
        }
    });
