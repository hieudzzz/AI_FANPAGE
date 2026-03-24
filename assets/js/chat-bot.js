jQuery(document).ready(function ($) {
    // Polling state
    let lastMessageId = 0;
    let isPolling = false;
    let currentTab = 'chats';
    let productsList = [];
    let allChatsCache = null; // cache để filter không cần gọi lại AJAX

    // ========================
    // UTILITY FUNCTIONS
    // ========================
    function timeAgo(dateStr) {
        if (!dateStr || dateStr === '0000-00-00 00:00:00') return '---';
        const now = new Date();
        const date = new Date(dateStr.replace(' ', 'T'));
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) return 'vừa xong';
        if (diff < 3600) return Math.floor(diff / 60) + 'p trước';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h trước';
        if (diff < 604800) return Math.floor(diff / 86400) + 'ng trước';
        return dateStr;
    }

    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function truncate(str, len) {
        if (!str) return '';
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    function intentBadge(intent) {
        const map = {
            'buy_product': { t: 'Mua hàng', c: '#059669', bg: '#ecfdf5', ic: '🛒' },
            'ask_price': { t: 'Hỏi giá', c: '#0284c7', bg: '#f0f9ff', ic: '💰' },
            'ask_product': { t: 'Hỏi SP', c: '#0284c7', bg: '#f0f9ff', ic: '📦' },
            'product_consultation': { t: 'Tư vấn', c: '#7c3aed', bg: '#f5f3ff', ic: '💡' },
            'order_request': { t: 'Đơn hàng', c: '#d97706', bg: '#fffbeb', ic: '🚚' },
            'general_question': { t: 'Hỏi thăm', c: '#64748b', bg: '#f1f5f9', ic: '💬' },
            'unknown': { t: 'Chưa rõ', c: '#64748b', bg: '#f1f5f9', ic: '❓' }
        };
        const m = map[intent] || map['unknown'];
        return `<span class="aif-badge" style="background:${m.bg};color:${m.c};">${m.ic} ${m.t}</span>`;
    }

    function levelBadge(level) {
        let t, cl;
        if (level === 'High' || level >= 70) { t = 'Rất cao'; cl = 'aif-badge-danger'; }
        else if (level === 'Medium' || level >= 40) { t = 'Cao'; cl = 'aif-badge-warning'; }
        else if (level === 'Low' || level >= 20) { t = 'Trung bình'; cl = 'aif-badge-info'; }
        else { t = 'Thấp'; cl = 'aif-badge-gray'; }
        return `<span class="aif-badge ${cl}">${t}</span>`;
    }

    function scoreBar(score, align = 'center') {
        const pct = Math.min(Math.max(score || 0, 0), 100);
        let color = '#94a3b8';
        if (pct >= 70) color = '#ef4444';
        else if (pct >= 40) color = '#f59e0b';
        else if (pct >= 15) color = '#3b82f6';
        return `<div style="display:flex;align-items:center;justify-content:${align};gap:8px;">
            <div style="height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;width:50px;flex-shrink:0;">
                <div style="width:${pct}%;height:100%;background:${color};border-radius:3px;transition:width .5s;"></div>
            </div>
            <span style="font-weight:800;font-size:12px;color:${color};min-width:24px;text-align:left;">${score || 0}</span>
        </div>`;
    }

    function kpiCard(icon, bg, title, value) {
        return `<div class="aif-kpi-card">
            <div class="aif-kpi-icon" style="background:${bg};">${icon}</div>
            <div class="aif-kpi-info">
                <h4>${title}</h4>
                <div class="aif-kpi-value">${value}</div>
            </div>
        </div>`;
    }

    function emptyState(icon, title, desc) {
        return `<tr><td colspan="7" style="text-align:center;padding:60px 30px;">
            <div style="color:#94a3b8;">
                <span class="dashicons dashicons-${icon}" style="font-size:44px;width:44px;height:44px;display:block;margin:0 auto 12px;opacity:.5;"></span>
                <p style="font-size:15px;font-weight:700;margin:0 0 4px;color:#64748b;">${title}</p>
                <p style="font-size:13px;margin:0;">${desc}</p>
            </div>
        </td></tr>`;
    }

    // ========================
    // TABS
    // ========================
    $('.aif-n8n-tab').on('click', function () {
        const tab = $(this).data('tab');
        currentTab = tab;
        $('.aif-n8n-tab').removeClass('active');
        $(this).addClass('active');
        $('.aif-tab-content').hide();
        $('#tab-' + tab).show();
        
        if (tab === 'chats') loadChats();
        else if (tab === 'products') loadProducts();
        else if (tab === 'leads') loadLeads();
        else if (tab === 'settings') loadSettings();
        else if (tab === 'policies' && typeof loadPolicies === 'function') loadPolicies();
    });

    // ========================
    // CHATS
    // ========================
    function loadChats() {
        $('#aif-n8n-chats-body').html('<tr><td colspan="7" class="aif-loading-cell"><div class="aif-spinner"></div> Đang tải dữ liệu...</td></tr>');
        $.post(ajaxurl, { action: 'aif_n8n_get_chats', nonce: aif_ajax.nonce }, function (res) {
            if (!res.success) {
                $('#aif-n8n-chats-body').html('<tr><td colspan="7" style="text-align:center;padding:30px;color:#ef4444;">Lỗi tải dữ liệu.</td></tr>');
                return;
            }
            allChatsCache = res.data;

            // Populate button group (1 lần duy nhất)
            const $group = $('#aif-filter-page-group');
            if ($group.find('button').length <= 1) {
                const pages = {};
                allChatsCache.forEach(c => {
                    if (c.page_id) pages[String(c.page_id)] = c.page_name || c.page_id;
                });
                Object.entries(pages).forEach(([id, name]) => {
                    $group.append(`<button class="aif-filter-btn" data-page="${id}" data-name="${name}">${name}</button>`);
                });
            }

            renderChats();
        });
    }

    // Build HTML cho 1 row — dùng chung cho renderChats() và patchChatRow()
    function buildChatRowHtml(chat) {
        let ai = {};
        try { ai = JSON.parse(chat.ai_state || '{}'); } catch (e) {}
        const intent   = ai.intent || 'unknown';
        const avatar   = chat.customer_pic || 'https://www.gravatar.com/avatar/?d=mp&f=y';
        const name     = chat.customer_name || 'Khách Facebook';
        const isUnread = parseInt(chat.is_viewed) === 0;

        return `<tr class="${isUnread ? 'aif-row-unread' : ''}" data-chat-id="${chat.id}">
            <td>
                <div style="display:flex;align-items:center;gap:11px;">
                    <img src="${avatar}" style="width:38px;height:38px;border-radius:50%;border:2px solid #f1f5f9;object-fit:cover;flex-shrink:0;" onerror="this.src='https://www.gravatar.com/avatar/?d=mp&f=y'">
                    <div style="min-width:0;flex:1;">
                        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                            ${isUnread ? '<span class="aif-unread-dot"></span>' : ''}
                            <div style="font-weight:700;color:#1e293b;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(name)}</div>
                            ${parseInt(chat.needs_support) === 1 ? '<span title="Cần hỗ trợ gấp" style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#ef4444;vertical-align:middle;cursor:help;box-shadow:0 0 0 2px rgba(239,68,68,0.3);flex-shrink:0;"></span>' : ''}
                        </div>
                        <div style="font-size:11px;color:#94a3b8;">${esc(chat.page_name || chat.page_id)}</div>
                    </div>
                </div>
            </td>
            <td style="text-align:center;font-size:13px;color:#475569;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(truncate(chat.last_message, 50)) || '<span style="color:#cbd5e1;font-style:italic;">—</span>'}</td>
            <td style="text-align:center;">${intentBadge(intent)}</td>
            <td style="text-align:center;min-width:110px;">${scoreBar(chat.lead_score)}</td>
            <td style="text-align:center;">${levelBadge(chat.lead_level)}</td>
            <td style="text-align:center;font-size:12px;color:#94a3b8;" title="${chat.updated_at}">${timeAgo(chat.updated_at)}</td>
            <td style="text-align:right;white-space:nowrap;">
                <button class="button button-small btn-view-state" data-state='${chat.ai_state}' data-name="${esc(name)}" data-avatar="${avatar}" style="border-radius:6px;border-color:var(--aif-border-light);color:var(--aif-primary);padding:4px 8px;" title="Xem chi tiết">
                    <span class="dashicons dashicons-visibility" style="font-size:14px;margin-top:3px;"></span>
                </button>
                <button class="button button-small btn-del-chat" data-id="${chat.id}" style="border-radius:6px;border-color:#fee2e2;color:#ef4444;padding:4px 8px;" title="Xóa">
                    <span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span>
                </button>
            </td>
        </tr>`;
    }

    // Patch đúng từng <tr> bị thay đổi, không rebuild toàn bảng
    // changedChats: mảng các chat object vừa được polling trả về
    function patchChatRows(changedChats) {
        const $tbody       = $('#aif-n8n-chats-body');
        const selectedPage = String($('#aif-filter-page-group').find('.active').data('page') || '');

        changedChats.forEach(function(chat) {
            const $existing = $tbody.find(`tr[data-chat-id="${chat.id}"]`);
            const inFilter  = !selectedPage || String(chat.page_id) === selectedPage;

            if ($existing.length) {
                // Row đã có → replace đúng row đó
                $existing.replaceWith(buildChatRowHtml(chat));
                // Nếu row này cần nổi lên đầu (tin mới nhất) → di chuyển
                $tbody.find(`tr[data-chat-id="${chat.id}"]`).prependTo($tbody);
            } else if (inFilter) {
                // Người dùng mới chưa có row → chèn lên đầu
                $tbody.prepend(buildChatRowHtml(chat));
            }
        });
    }

    function renderChats() {
        if (!allChatsCache) return;
        const $group = $('#aif-filter-page-group');
        const selectedPage = String($group.find('.active').data('page') || '');
        const chats = selectedPage
            ? allChatsCache.filter(c => String(c.page_id) === selectedPage)
            : allChatsCache;

        // Cập nhật count trên tất cả buttons
        $group.find('.aif-filter-btn').each(function() {
            const pg   = String($(this).data('page') || '');
            const cnt  = pg ? allChatsCache.filter(c => String(c.page_id) === pg).length : allChatsCache.length;
            const name = pg ? ($(this).data('name') || $(this).data('page')) : 'Tất cả';
            $(this).html(`${name} <span class="aif-filter-count">${cnt}</span>`);
        });

        // Unread badge
        const unreadChats = allChatsCache.filter(c => parseInt(c.is_viewed) === 0).length;
        if (unreadChats > 0) {
            $('#kpi-badge-chats').addClass('aif-badge-unread').text(unreadChats);
        } else {
            $('#kpi-badge-chats').removeClass('aif-badge-unread').text(allChatsCache.length);
        }

        if (currentTab === 'chats') {
            const hotLeads   = chats.filter(c => c.lead_level === 'High').length;
            const needSupport = chats.filter(c => parseInt(c.needs_support) === 1).length;
            $('#aif-kpi-row').html(
                kpiCard('💬', '#eef2ff', 'Tổng phiên chat', chats.length) +
                kpiCard('🔥', '#fef2f2', 'Lead nóng', hotLeads) +
                kpiCard('🆘', '#fffbeb', 'Cần hỗ trợ', needSupport)
            );
        }

        let html = '';
        if (chats.length === 0) {
            html = emptyState('format-chat', 'Chưa có phiên chat nào', selectedPage ? 'Fanpage này chưa có phiên chat nào.' : 'Khi khách nhắn tin qua Messenger, phiên chat sẽ hiện tại đây.');
        } else {
            chats.forEach(chat => { html += buildChatRowHtml(chat); });
        }
        $('#aif-n8n-chats-body').html(html);
    }

    // Click filter chats — filter client-side từ cache, không gọi lại AJAX
    $(document).on('click', '#aif-filter-page-group .aif-filter-btn', function() {
        $('#aif-filter-page-group .aif-filter-btn').removeClass('active');
        $(this).addClass('active');
        renderChats();
    });

    // ========================
    // LEADS
    // ========================
    function loadLeads() {
        $.post(ajaxurl, { action: 'aif_n8n_get_leads', nonce: aif_ajax.nonce }, function (res) {
            if (!res.success) return;
            const allLeads = res.data;

            // Populate button group
            const $group = $('#aif-filter-leads-page-group');
            const selectedPage = String($group.find('.active').data('page') || '');

            const pages = {};
            allLeads.forEach(l => { if (l.page_id) pages[l.page_id] = l.page_name || l.page_id; });
            
            let buttonsHtml = `<button class="aif-filter-btn ${selectedPage === '' ? 'active' : ''}" data-page="">Tất cả <span class="aif-filter-count">${allLeads.length}</span></button>`;
            Object.entries(pages).forEach(([id, name]) => {
                const count = allLeads.filter(l => String(l.page_id) === String(id)).length;
                buttonsHtml += `<button class="aif-filter-btn ${selectedPage === String(id) ? 'active' : ''}" data-page="${id}">${esc(name)} <span class="aif-filter-count">${count}</span></button>`;
            });
            $group.html(buttonsHtml);

            const leads = selectedPage ? allLeads.filter(l => String(l.page_id) === selectedPage) : allLeads;

            $('#kpi-badge-leads').text(allLeads.length);
            // Unread badge
            const unreadLeads = allLeads.filter(l => parseInt(l.is_viewed) === 0).length;
            if (unreadLeads > 0) {
                $('#kpi-badge-leads').addClass('aif-badge-unread').text(unreadLeads);
            } else {
                $('#kpi-badge-leads').removeClass('aif-badge-unread').text(allLeads.length);
            }

            if (currentTab === 'leads') {
                const hasPhone = leads.filter(l => l.phone && l.phone !== '---').length;
                const hasAddr = leads.filter(l => l.address && l.address !== '---').length;
                $('#aif-kpi-row').html(
                    kpiCard('👥', '#eef2ff', 'Tổng khách tiềm năng', leads.length) +
                    kpiCard('📞', '#ecfdf5', 'Có số điện thoại', hasPhone) +
                    kpiCard('📍', '#fffbeb', 'Có địa chỉ', hasAddr)
                );
            }

            let html = '';
            if (leads.length === 0) {
                html = emptyState('groups', 'Chưa có khách hàng tiềm năng', selectedPage ? 'Fanpage này chưa có khách hàng tiềm năng.' : 'Khi AI thu thập được SĐT hoặc Địa chỉ, dữ liệu sẽ hiện tại đây.');
            } else {
                leads.forEach((lead, idx) => {
                    const avatar = lead.fb_pic || 'https://www.gravatar.com/avatar/?d=mp&f=y';
                    const name = lead.fb_name || lead.customer_name || 'Khách Facebook';
                    const isLeadUnread = parseInt(lead.is_viewed) === 0;

                    let ai = {};
                    try { ai = JSON.parse(lead.ai_state || '{}'); } catch (e) { }

                    let prodHtml = '';
                    if (ai.order_items && ai.order_items.length > 0) {
                        ai.order_items.forEach(it => {
                            prodHtml += `<div style="font-size:11px;background:#ecfdf5;color:#059669;padding:2px 6px;border-radius:4px;margin-bottom:2px;display:inline-block;margin-right:4px;border:1px solid #d1fae5;">📦 ${esc(it.product_name)}</div>`;
                        });
                    } else if (ai.suggest_products && ai.suggest_products.length > 0) {
                        ai.suggest_products.forEach(p => {
                            prodHtml += `<div style="font-size:11px;background:#f0f9ff;color:#0284c7;padding:2px 6px;border-radius:4px;margin-bottom:2px;display:inline-block;margin-right:4px;border:1px solid #e0f2fe;">🔍 ${esc(p)}</div>`;
                        });
                    } else {
                        prodHtml = '<span style="color:#cbd5e1;">---</span>';
                    }

                    html += `<tr class="${isLeadUnread ? 'aif-row-unread' : ''}" data-lead-id="${lead.id}">
                        <td style="text-align:center;font-weight:800;color:var(--aif-primary);font-size:13px;">${idx + 1}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:11px;">
                                <img src="${avatar}" style="width:34px;height:34px;border-radius:50%;border:2px solid #f1f5f9;object-fit:cover;flex-shrink:0;" onerror="this.src='https://www.gravatar.com/avatar/?d=mp&f=y'">
                                <div style="min-width:0;flex:1;">
                                    <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                        ${isLeadUnread ? '<span class="aif-unread-dot"></span>' : ''}
                                        <div style="font-weight:700;color:#1e293b;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(name)}</div>
                                    </div>
                                    <div style="font-size:11px;color:#94a3b8;">${esc(lead.page_name || lead.page_id || '')}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            ${lead.phone && lead.phone !== '---' ? `<a href="tel:${lead.phone}" style="display:inline-flex;align-items:center;gap:5px;font-weight:600;color:#059669;text-decoration:none;font-size:12px;background:#ecfdf5;padding:4px 10px;border-radius:8px;">📞 ${esc(lead.phone)}</a>` : '<span style="color:#cbd5e1;">---</span>'}
                        </td>
                        <td>${prodHtml}</td>
                        <td style="font-size:12px;color:#475569;">${lead.address && lead.address !== '---' ? `📍 ${esc(truncate(lead.address, 40))}` : '<span style="color:#cbd5e1;">---</span>'}</td>
                        <td style="font-size:12px;color:#64748b;">${lead.notes ? truncate(lead.notes, 50).replace(/\n/g, '<br>') : '<span style="color:#cbd5e1;">—</span>'}</td>
                        <td style="text-align:center;font-size:12px;color:#94a3b8;">${timeAgo(lead.created_at)}</td>
                        <td style="text-align:center;">
                            ${lead.page_name
                                ? `<span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;background:#e7f0fd;color:#1877F2;padding:3px 9px;border-radius:20px;white-space:nowrap;"><span class="dashicons dashicons-facebook" style="font-size:12px;width:12px;height:12px;"></span>${esc(lead.page_name)}</span>`
                                : `<span style="color:#cbd5e1;">---</span>`}
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button class="button button-small btn-del-lead" data-id="${lead.id}" style="border-radius:6px;border-color:#fee2e2;color:#ef4444;padding:4px 8px;" title="Xóa">
                                <span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#aif-n8n-leads-body').html(html);

            // Auto-mark unread leads as viewed after 2 seconds when tab is open
            if (currentTab === 'leads') {
                const unreadIds = allLeads.filter(l => parseInt(l.is_viewed) === 0).map(l => l.id);
                if (unreadIds.length > 0) {
                    clearTimeout(window._leadViewTimer);
                    window._leadViewTimer = setTimeout(function() {
                        // Remove unread styling
                        $('#aif-n8n-leads-body tr.aif-row-unread').removeClass('aif-row-unread');
                        $('#aif-n8n-leads-body .aif-unread-dot').remove();
                        // Update badge
                        $('#kpi-badge-leads').removeClass('aif-badge-unread').text(allLeads.length);
                        // Persist to server
                        $.post(ajaxurl, {
                            action: 'aif_n8n_mark_leads_viewed',
                            nonce: aif_ajax.nonce,
                            ids: unreadIds
                        });
                    }, 2000);
                }
            }
        });
    }

    // Click filter leads
    $(document).on('click', '#aif-filter-leads-page-group .aif-filter-btn', function() {
        $('#aif-filter-leads-page-group .aif-filter-btn').removeClass('active');
        $(this).addClass('active');
        loadLeads();
    });

    // ========================
    // PRODUCTS
    // ========================
    function loadProducts() {
        $.post(ajaxurl, { action: 'aif_n8n_get_products', nonce: aif_ajax.nonce }, function (res) {
            if (!res.success) return;
            productsList = res.data;
            $('#kpi-badge-products').text(productsList.length);

            // Populate category filter (dynamic)
            const $catGroup = $('#aif-filter-prod-category');
            const activeCat = $catGroup.find('.active').data('cat') || '';
            const cats = [...new Set(productsList.map(p => p.category).filter(Boolean))].sort();
            let catHtml = `<button class="aif-filter-btn ${activeCat === '' ? 'active' : ''}" data-cat="">Mọi danh mục</button>`;
            cats.forEach(cat => {
                catHtml += `<button class="aif-filter-btn ${activeCat === cat ? 'active' : ''}" data-cat="${esc(cat)}" data-name="${esc(cat)}">${esc(cat)}</button>`;
            });
            $catGroup.html(catHtml);

            renderProducts();
        });
    }

    // Render bảng sản phẩm theo filter hiện tại (client-side)
    function renderProducts() {
        if (!productsList) return;

        const search  = ($('#aif-product-search').val() || '').toLowerCase().trim();
        const status  = $('#aif-filter-prod-status .active').data('status') || '';
        const cat     = $('#aif-filter-prod-category .active').data('cat') || '';

        let filtered = productsList;
        if (status)  filtered = filtered.filter(p => p.status === status);
        if (cat)     filtered = filtered.filter(p => (p.category || '') === cat);
        if (search)  filtered = filtered.filter(p =>
            (p.product_name || '').toLowerCase().includes(search) ||
            (p.sku || '').toLowerCase().includes(search)
        );

        // Cập nhật count trên status buttons
        $('#aif-filter-prod-status .aif-filter-btn').each(function() {
            const s   = $(this).data('status') || '';
            const cnt = s ? productsList.filter(p => p.status === s).length : productsList.length;
            $(this).text((s === '' ? 'Tất cả' : s === 'active' ? 'Đang bán' : 'Ngừng bán') + ' ' + cnt);
        });

        if (currentTab === 'products') {
            const active = productsList.filter(p => p.status === 'active').length;
            const inactive = productsList.length - active;
            $('#aif-kpi-row').html(
                kpiCard('📦', '#eef2ff', 'Tổng sản phẩm', productsList.length) +
                kpiCard('✅', '#ecfdf5', 'Đang kinh doanh', active) +
                kpiCard('⏸️', '#fef2f2', 'Ngừng bán', inactive)
            );
        }

        let html = '';
        if (filtered.length === 0) {
            html = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-style:italic;">
                <span class="dashicons dashicons-search" style="font-size:24px;display:block;margin:0 auto 8px;opacity:0.3;"></span>
                Không tìm thấy sản phẩm phù hợp.
            </td></tr>`;
        } else {
            filtered.forEach(p => {
                const isActive = p.status === 'active';
                html += `<tr>
                    <td style="font-weight:700;color:#6366f1;">#${p.id}</td>
                    <td style="text-align:center;">
                        <div style="font-weight:700;color:#1e293b;font-size:14px;">${esc(p.product_name)}</div>
                        ${p.description ? `<div style="font-size:12px;color:#94a3b8;margin-top:2px;">${truncate(p.description, 45)}</div>` : ''}
                    </td>
                    <td><span class="aif-badge aif-badge-indigo">${esc(p.category) || '---'}</span></td>
                    <td><code>${esc(p.sku) || '---'}</code></td>
                    <td style="font-weight:700;color:#059669;">${p.price || '0'}</td>
                    <td style="text-align:center;">
                        <span class="aif-badge ${isActive ? 'aif-badge-success' : 'aif-badge-danger'}">
                            ${isActive ? '● Đang bán' : '○ Ngừng'}
                        </span>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <button class="button button-small btn-edit-prod" data-id="${p.id}" style="border-radius:6px;border-color:var(--aif-border-light);color:var(--aif-primary);padding:4px 10px;" title="Sửa">
                            <span class="dashicons dashicons-edit" style="font-size:14px;margin-top:3px;"></span>
                        </button>
                        <button class="button button-small btn-toggle-prod" data-id="${p.id}" data-status="${p.status}"
                            style="border-radius:6px;border-color:${isActive ? '#dcfce7' : '#fef3c7'};color:${isActive ? '#16a34a' : '#d97706'};padding:4px 10px;"
                            title="${isActive ? 'Đang bán — nhấn để ngừng' : 'Đang ngừng — nhấn để kích hoạt'}">
                            <span class="dashicons ${isActive ? 'dashicons-visibility' : 'dashicons-hidden'}" style="font-size:14px;margin-top:3px;"></span>
                        </button>
                        <button class="button button-small btn-del-prod" data-id="${p.id}" style="border-radius:6px;border-color:#fee2e2;color:#ef4444;padding:4px 10px;" title="Xóa hẳn">
                            <span class="dashicons dashicons-trash" style="font-size:14px;margin-top:3px;"></span>
                        </button>
                    </td>
                </tr>`;
            });
        }
        $('#aif-n8n-products-body').html(html);
    }

    // ── Filter events ────────────────────────────────────────────────────────
    // Search — lọc ngay khi gõ (debounce 200ms)
    let _prodSearchTimer;
    $(document).on('input', '#aif-product-search', function() {
        const val = $(this).val();
        $('#aif-product-search-clear').toggle(val.length > 0);
        clearTimeout(_prodSearchTimer);
        _prodSearchTimer = setTimeout(renderProducts, 200);
    });
    $(document).on('click', '#aif-product-search-clear', function() {
        $('#aif-product-search').val('');
        $(this).hide();
        renderProducts();
    });

    // Status filter
    $(document).on('click', '#aif-filter-prod-status .aif-filter-btn', function() {
        $('#aif-filter-prod-status .aif-filter-btn').removeClass('active');
        $(this).addClass('active');
        renderProducts();
    });

    // Category filter
    $(document).on('click', '#aif-filter-prod-category .aif-filter-btn', function() {
        $('#aif-filter-prod-category .aif-filter-btn').removeClass('active');
        $(this).addClass('active');
        renderProducts();
    });

    // ========================
    // SETTINGS
    // ========================
    function loadSettings() {
        $('#aif-kpi-row').html('');
        $.post(ajaxurl, { action: 'aif_n8n_get_settings', nonce: aif_ajax.nonce }, function (res) {
            if (res.success) {
                const d = res.data;
                $('#set-system-prompt').val(d.system_prompt);
                $('#set-cs-info').val(d.cs_info);
                $('#set-context-limit').val(d.context_limit);
            }
        });
    }

    $('.aif-settings-nav-item').on('click', function () {
        const section = $(this).data('section');
        $('.aif-settings-nav-item').removeClass('active');
        $(this).addClass('active');
        $('.aif-settings-section').removeClass('active');
        $('#section-' + section).addClass('active');
    });

    $('#aif-n8n-settings-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const $msg = $('#settings-save-msg');
        $btn.prop('disabled', true).text('Đang lưu...');

        $.post(ajaxurl, {
            action: 'aif_n8n_save_settings', 
            nonce: aif_ajax.nonce,
            system_prompt: $('#set-system-prompt').val(),
            cs_info: $('#set-cs-info').val(),
            context_limit: $('#set-context-limit').val()
        }, function (res) {
            $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Lưu cấu hình');
            if (res.success) {
                if (window.AIF_Toast) AIF_Toast.show('Đã lưu cấu hình thành công!');
                else alert('Đã lưu thành công!');
            } else {
                alert('Lỗi: ' + res.data);
            }
        });
    });

    // ========================
    // ACTIONS
    // ========================

    // Delete Chat
    $(document).on('click', '.btn-del-chat', function () {
        if (!confirm('Xóa phiên chat này và toàn bộ tin nhắn liên quan?')) return;
        const id = $(this).data('id');
        $.post(ajaxurl, { action: 'aif_n8n_delete_chat', id: id, nonce: aif_ajax.nonce }, function (res) {
            if (res.success) loadChats();
        });
    });

    // Delete Lead
    $(document).on('click', '.btn-del-lead', function () {
        if (!confirm('Xóa khách hàng tiềm năng này?')) return;
        const id = $(this).data('id');
        $.post(ajaxurl, { action: 'aif_n8n_delete_lead', id: id, nonce: aif_ajax.nonce }, function (res) {
            if (res.success) {
                if (window.AIF_Toast) AIF_Toast.show('Đã xóa khách hàng tiềm năng.');
                loadLeads();
            }
        });
    });

    // Helper: set trạng thái cho toggle button theo value
    function setStatusToggle(val) {
        $('#prod-status').val(val);
        $('.aif-status-btn').removeClass('active');
        $(`.aif-status-btn[data-value="${val}"]`).addClass('active');
    }

    // Nhấn nút toggle
    $(document).on('click', '.aif-status-btn', function () {
        setStatusToggle($(this).data('value'));
    });

    // Add Product
    $('#btn-add-product').on('click', function () {
        $('#aif-product-form')[0].reset();
        $('#prod-id').val('');
        $('#modal-title').text('Thêm sản phẩm mới');
        $('#aif-product-modal').addClass('active');
        setStatusToggle('active');
    });

    // Edit Product
    $(document).on('click', '.btn-edit-prod', function () {
        const id = $(this).data('id');
        const p = productsList.find(x => x.id == id);
        if (!p) return;
        $('#prod-id').val(p.id);
        $('#prod-name').val(p.product_name);
        $('#prod-cat').val(p.category);
        $('#prod-sku').val(p.sku);
        $('#prod-price').val(p.price);
        $('#prod-desc').val(p.description);
        $('#modal-title').text('Sửa: ' + p.product_name);
        $('#aif-product-modal').addClass('active');
        setStatusToggle(p.status || 'active');
    });

    // Đổi màu live khi user chọn trạng thái khác
    $(document).on('change', '#prod-status', function() {
        setStatusToggle($(this).val());
    });

    $('#aif-product-form').on('submit', function (e) {
        e.preventDefault();
        const data = {};
        $(this).serializeArray().forEach(x => data[x.name] = x.value);
        $.post(ajaxurl, { action: 'aif_n8n_save_product', product: data, nonce: aif_ajax.nonce }, function (res) {
            if (res.success) {
                $('#aif-product-modal').removeClass('active');
                if (window.AIF_Toast) AIF_Toast.show('Đã lưu sản phẩm!');
                loadProducts();
            } else alert(res.data);
        });
    });

    $(document).on('click', '.btn-toggle-prod', function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const isActive = $btn.data('status') === 'active';
        const newActive = !isActive;

        // ── Optimistic update: đổi UI ngay lập tức ──────────────────────────
        const $row = $btn.closest('tr');
        const $icon = $btn.find('.dashicons');
        const $badge = $row.find('.aif-badge').filter(function () {
            return $(this).text().trim().startsWith('●') || $(this).text().trim().startsWith('○');
        });

        $btn.data('status', newActive ? 'active' : 'inactive')
            .css({
                'border-color': newActive ? '#dcfce7' : '#fef3c7',
                'color':        newActive ? '#16a34a' : '#d97706'
            })
            .attr('title', newActive ? 'Đang bán — nhấn để ngừng' : 'Đang ngừng — nhấn để kích hoạt');
        $icon.removeClass('dashicons-visibility dashicons-hidden')
             .addClass(newActive ? 'dashicons-visibility' : 'dashicons-hidden');
        $badge.removeClass('aif-badge-success aif-badge-danger')
              .addClass(newActive ? 'aif-badge-success' : 'aif-badge-danger')
              .text(newActive ? '● Đang bán' : '○ Ngừng');
        $btn.prop('disabled', true);

        // ── Gửi AJAX → nếu lỗi thì revert ───────────────────────────────────
        $.post(ajaxurl, { action: 'aif_n8n_toggle_product', id: id, nonce: aif_ajax.nonce }, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                const msg = res.data.new_status === 'active' ? ' Đã kích hoạt sản phẩm' : ' Đã ngừng kinh doanh sản phẩm';
                if (window.AIF_Toast) AIF_Toast.show(msg, res.data.new_status === 'active' ? 'success' : 'error');
            } else {
                // Revert lại trạng thái cũ nếu server báo lỗi
                $btn.data('status', isActive ? 'active' : 'inactive')
                    .css({
                        'border-color': isActive ? '#dcfce7' : '#fef3c7',
                        'color':        isActive ? '#16a34a' : '#d97706'
                    })
                    .attr('title', isActive ? 'Đang bán — nhấn để ngừng' : 'Đang ngừng — nhấn để kích hoạt');
                $icon.removeClass('dashicons-visibility dashicons-hidden')
                     .addClass(isActive ? 'dashicons-visibility' : 'dashicons-hidden');
                $badge.removeClass('aif-badge-success aif-badge-danger')
                      .addClass(isActive ? 'aif-badge-success' : 'aif-badge-danger')
                      .text(isActive ? '● Đang bán' : '○ Ngừng');
                if (window.AIF_Toast) AIF_Toast.show('Lỗi cập nhật trạng thái!', 'error');
            }
        });
    });

    $(document).on('click', '.btn-del-prod', function () {
        if (!confirm('Xóa hẳn sản phẩm này? Hành động không thể hoàn tác.')) return;
        $.post(ajaxurl, { action: 'aif_n8n_delete_product', id: $(this).data('id'), nonce: aif_ajax.nonce }, function (res) {
            if (res.success) loadProducts();
        });
    });

    // CSV/Excell Exports
    $('#btn-export-csv').on('click', function () {
        window.location.href = ajaxurl + '?action=aif_n8n_export_products&nonce=' + aif_ajax.nonce;
    });

    $('#btn-export-leads-excel').on('click', function () {
        window.location.href = ajaxurl + '?action=aif_n8n_export_leads&nonce=' + aif_ajax.nonce;
    });

    // Import CSV
    $('#btn-import-csv').on('click', function () { $('#aif-import-file').click(); });
    $('#aif-import-file').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('action', 'aif_n8n_import_products');
        fd.append('nonce', aif_ajax.nonce);
        fd.append('csv_file', file);
        $.ajax({
            url: ajaxurl, type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                if (res.success) {
                    if (window.AIF_Toast) AIF_Toast.show(res.data);
                    loadProducts();
                } else alert(res.data);
                $('#aif-import-file').val('');
            }
        });
    });

    // Chat Details Modal
    $(document).on('click', '.btn-view-state', function () {
        let state = $(this).data('state');
        if (!state) return;
        if (typeof state === 'string') { try { state = JSON.parse(state); } catch (e) { return; } }

        const chatName = $(this).data('name') || 'Khách hàng';
        const chatAvatar = $(this).data('avatar') || '';

        let itemsHtml = '';
        if (state.order_items && state.order_items.length > 0) {
            state.order_items.forEach(it => {
                // Fallback: nếu AI cũ không trả product_name, tra cứu từ productsList theo product_id
                const name = it.product_name
                    || (productsList && productsList.find(p => String(p.id) === String(it.product_id)) || {}).product_name
                    || ('SP #' + (it.product_id || '?'));
                itemsHtml += `<div style="background:#f8fafc;padding:10px 14px;border-radius:10px;margin-bottom:5px;font-size:13px;display:flex;justify-content:space-between;align-items:center;border:1px solid #f1f5f9;">
                    <span>🛒 <b>${esc(name)}</b> <span style="color:#94a3b8;">×${it.quantity || 1}</span></span>
                    <span style="color:#059669;font-weight:700;">${it.price || '---'}</span>
                </div>`;
            });
        } else {
            itemsHtml = '<p style="color:#94a3b8;font-style:italic;font-size:13px;text-align:center;">Giỏ hàng trống.</p>';
        }

        const info = state.customer_info || {};
        const html = `
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid #f1f5f9;">
                <img src="${chatAvatar}" style="width:46px;height:46px;border-radius:50%;border:2px solid #eef2ff;object-fit:cover;">
                <div>
                    <div style="font-weight:800;font-size:17px;color:#1e293b;">${esc(chatName)}</div>
                    <div style="font-size:12px;color:#94a3b8;">Facebook Chat Customer</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
                <div style="background:#f8fafc;padding:15px;border-radius:14px;border:1px solid #f1f5f9;">
                    <h4 style="margin:0 0 12px;color:#64748b;font-size:11px;text-transform:uppercase;">🤖 Phân tích AI</h4>
                    <div style="margin-bottom:8px;">
                        <span style="color:#94a3b8;font-size:11px;text-transform:uppercase;">Ý định</span>
                        <div style="margin-top:2px;">${intentBadge(state.intent)}</div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <span style="color:#94a3b8;font-size:11px;text-transform:uppercase;">Điểm tiềm năng</span>
                        <div style="margin-top:2px;">${scoreBar(state.lead_score, 'flex-start')}</div>
                    </div>
                    <div>
                        <span style="color:#94a3b8;font-size:11px;text-transform:uppercase;">Cần hỗ trợ</span>
                        <div style="margin-top:2px;font-weight:700;display:flex;align-items:center;gap:6px;color:${state.need_human ? '#ef4444' : '#059669'};">
                            <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:${state.need_human ? '#ef4444' : '#059669'};box-shadow:0 0 0 2px ${state.need_human ? 'rgba(239,68,68,0.3)' : 'rgba(5,150,105,0.3)'};flex-shrink:0;"></span>
                            ${state.need_human ? 'CÓ' : 'Không'}
                        </div>
                    </div>
                </div>
                <div style="background:#fffbeb;padding:15px;border-radius:14px;border:1px solid #fef3c7;">
                    <h4 style="margin:0 0 12px;color:#92400e;font-size:11px;text-transform:uppercase;">👤 Thông tin khách</h4>
                    <div style="margin-bottom:8px;">
                        <span style="color:#b45309;font-size:11px;text-transform:uppercase;">Họ tên</span>
                        <div style="font-weight:600;color:#78350f;">${esc(info.name) || '---'}</div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <span style="color:#b45309;font-size:11px;text-transform:uppercase;">SĐT</span>
                        <div style="font-weight:600;color:#78350f;">${info.phone || '---'}</div>
                    </div>
                    <div>
                        <span style="color:#b45309;font-size:11px;text-transform:uppercase;">Địa chỉ</span>
                        <div style="font-weight:600;color:#78350f;font-size:12px;">${esc(info.address) || '---'}</div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <h4 style="margin:0 0 10px;font-size:13px;">🛍️ Giỏ hàng</h4>
                ${itemsHtml}
            </div>
        `;
        $('#chat-details-content').html(html);
        $('#aif-chat-details-modal').addClass('active');

        // Mark chat as viewed (optimistic UI)
        const $row = $(this).closest('tr');
        const chatId = $row.data('chat-id');
        if (chatId) {
            $row.removeClass('aif-row-unread');
            $row.find('.aif-unread-dot').remove();
            // Update cache
            if (allChatsCache) {
                const cached = allChatsCache.find(c => String(c.id) === String(chatId));
                if (cached) cached.is_viewed = '1';
                // Update badge
                const unread = allChatsCache.filter(c => parseInt(c.is_viewed) === 0).length;
                if (unread > 0) {
                    $('#kpi-badge-chats').addClass('aif-badge-unread').text(unread);
                } else {
                    $('#kpi-badge-chats').removeClass('aif-badge-unread').text(allChatsCache.length);
                }
            }
            // Persist to server
            $.post(ajaxurl, { action: 'aif_n8n_mark_chat_viewed', nonce: aif_ajax.nonce, id: chatId });
        }
    });

    $(document).on('click', '#btn-close-chat-modal, #btn-close-chat-modal-footer', function () {
        $('#aif-chat-details-modal').removeClass('active');
    });

    // ========================
    // REAL-TIME POLLING
    // ========================
    let pollingInFlight = false;

    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        console.log('AIF Real-time Polling Started...');

        setInterval(function() {
            if (pollingInFlight) return; // prevent overlapping requests
            pollingInFlight = true;

            $.ajax({
                url: aif_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aif_n8n_check_updates',
                    nonce: aif_ajax.nonce,
                    since_id: lastMessageId
                },
                success: function(response) {
                    pollingInFlight = false;
                    if (!response.success) return;

                    const data = response.data;

                    // First poll — just record the baseline ID
                    if (lastMessageId === 0) {
                        lastMessageId = data.latest_id;
                        return;
                    }

                    // Always sync lastMessageId with server (server may hold it back if AI not ready)
                    lastMessageId = data.latest_id;

                    // Update unread badges from polling response
                    if (data.unread_counts) {
                        // Badge Chats (tab)
                        if (data.unread_counts.chats > 0) {
                            $('#kpi-badge-chats').addClass('aif-badge-unread').text(data.unread_counts.chats);
                        } else if (allChatsCache) {
                            $('#kpi-badge-chats').removeClass('aif-badge-unread').text(allChatsCache.length);
                        }

                        // Badge Leads (tab)
                        if (data.unread_counts.leads > 0) {
                            $('#kpi-badge-leads').addClass('aif-badge-unread').text(data.unread_counts.leads);
                        } else {
                            $('#kpi-badge-leads').removeClass('aif-badge-unread');
                        }

                        // Badge menu sidebar "Chatbot"
                        const $menuLink = $('#adminmenu a[href*="page=ai-fanpage-chatbot"]').first();
                        if ($menuLink.length) {
                            let $badge = $menuLink.find('.aif-menu-badge-chat');
                            if (data.unread_counts.chats > 0) {
                                if ($badge.length) {
                                    $badge.text(data.unread_counts.chats);
                                } else {
                                    $menuLink.append('<span class="aif-menu-badge aif-menu-badge-chat">' + data.unread_counts.chats + '</span>');
                                }
                            } else {
                                $badge.remove();
                            }
                        }
                    }

                    // Only process when AI has finished replying (ready = true)
                    if (!data.ready) return;

                    // Show toast once
                    if (window.AIF_Toast) {
                        AIF_Toast.show('Có tin nhắn mới từ khách hàng!', 'info');
                    }

                    // Merge changed chats into cache (no full reload)
                    if (data.changed_chats && data.changed_chats.length > 0 && allChatsCache) {
                        data.changed_chats.forEach(function(updated) {
                            const idx = allChatsCache.findIndex(function(c) {
                                return String(c.id) === String(updated.id);
                            });
                            if (idx > -1) {
                                allChatsCache[idx] = updated;
                            } else {
                                allChatsCache.unshift(updated);
                            }
                        });

                        allChatsCache.sort(function(a, b) {
                            return (b.updated_at || '').localeCompare(a.updated_at || '');
                        });

                        if (currentTab === 'chats') {
                            // Chỉ patch đúng các row thay đổi, không rebuild toàn bảng
                            patchChatRows(data.changed_chats);
                            // Cập nhật badge + KPI mà không cần render lại bảng
                            const unread = allChatsCache.filter(c => parseInt(c.is_viewed) === 0).length;
                            if (unread > 0) {
                                $('#kpi-badge-chats').addClass('aif-badge-unread').text(unread);
                            } else {
                                $('#kpi-badge-chats').removeClass('aif-badge-unread').text(allChatsCache.length);
                            }
                        }
                    } else if (!allChatsCache) {
                        if (currentTab === 'chats') loadChats();
                    }

                    if (currentTab === 'leads') loadLeads();
                },
                error: function() {
                    pollingInFlight = false;
                }
            });
        }, 5000); // 5 seconds
    }

    // Initial Load
    loadChats();
    loadProducts();
    loadLeads();
    startPolling();

    // Chỉ xóa badge khi đang đứng đúng trang Chatbot
    if (window.location.search.indexOf('page=ai-fanpage-chatbot') !== -1) {
        $('#adminmenu a[href*="page=ai-fanpage-chatbot"]').find('.aif-menu-badge-chat').remove();
    }
});
