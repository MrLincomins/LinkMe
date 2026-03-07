const Admin = (function () {

    let token = null;
    let user = null;
    let currentTab = 'domains';
    let domainsCache = [];
    let searchTimeout = null;

    async function api(path, opts = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const res = await fetch(`/api${path}`, { ...opts, headers });
        const data = await res.json();

        if (res.status === 401) {
            token = null;
            user = null;
            showAuth();
            throw new Error('Unauthorized');
        }
        if (!res.ok) {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Ошибка';
            throw new Error(msg);
        }
        return data;
    }

    const $ = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    function show(el) { el.classList.remove('hidden'); }
    function hide(el) { el.classList.add('hidden'); }

    function badge(active) {
        return active
            ? '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Активен</span>'
            : '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-500">Неактивен</span>';
    }

    function verifiedBadge(v) {
        return v
            ? '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Да</span>'
            : '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Нет</span>';
    }

    function truncate(str, len = 40) {
        if (!str) return '—';
        return str.length > len ? str.slice(0, len) + '…' : str;
    }

    function toast(msg, type = 'success') {
        const el = document.createElement('div');
        el.className = `fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-xl shadow-lg text-sm font-medium text-white transition-all
            ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 2500);
    }

    function openModal(html) {
        $('#modal-content').innerHTML = html;
        show($('#modal-backdrop'));
    }

    function closeModal() {
        hide($('#modal-backdrop'));
        $('#modal-content').innerHTML = '';
    }

    function showAuth() {
        show($('#auth-screen'));
        hide($('#dashboard'));
        hide($('#main-nav'));
        $('#header-right').innerHTML = '';
    }

    function showDashboard() {
        hide($('#auth-screen'));
        show($('#dashboard'));
        show($('#main-nav'));
        $('#header-right').innerHTML = `
            <span class="text-sm text-gray-600">${user.name}</span>
            <button onclick="Admin.logout()"
                class="text-sm text-red-600 hover:text-red-700 font-medium">Выйти</button>
        `;
        switchTab('domains');
    }

    async function login(e) {
        e.preventDefault();
        const form = e.target;
        const email = form.email.value;
        const password = form.password.value;
        const errEl = $('#auth-error');
        hide(errEl);

        try {
            const data = await api('/login', {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });
            token = data.data.token;
            user = data.data.user;
            showDashboard();
        } catch (err) {
            errEl.textContent = err.message;
            show(errEl);
        }
    }

    async function logout() {
        try { await api('/logout', { method: 'POST' }); } catch (_) {}
        token = null;
        user = null;
        showAuth();
    }

    function switchTab(tab) {
        currentTab = tab;
        $$('.nav-btn').forEach(b => {
            if (b.dataset.tab === tab) b.setAttribute('data-active', '');
            else b.removeAttribute('data-active');
        });
        $$('[id^="tab-"]').forEach(s => hide(s));
        show($(`#tab-${tab}`));

        if (tab === 'domains') loadDomains();
        if (tab === 'links') loadLinks();
    }

    async function loadDomains(page = 1) {
        try {
            const data = await api(`/domains?per_page=15&page=${page}`);
            domainsCache = data.data;
            renderDomains(data.data, data.meta || data);
        } catch (e) { toast(e.message, 'error'); }
    }

    function renderDomains(items, meta) {
        const tbody = $('#domains-tbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">Нет доменов</td></tr>';
            $('#domains-pagination').innerHTML = '';
            return;
        }
        tbody.innerHTML = items.map(d => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-medium">${d.name}</td>
                <td class="px-6 py-4 text-gray-500">${truncate(d.target_url)}</td>
                <td class="px-6 py-4 text-center">${d.links_count ?? '—'}</td>
                <td class="px-6 py-4 text-center">${badge(d.is_active)}</td>
                <td class="px-6 py-4 text-center">${verifiedBadge(d.is_verified)}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick="Admin.openDomainModal(${d.id})"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Изменить</button>
                    <button onclick="Admin.deleteDomain(${d.id})"
                        class="text-red-500 hover:text-red-700 text-sm font-medium">Удалить</button>
                </td>
            </tr>
        `).join('');

        renderPagination('#domains-pagination', meta, loadDomains);
    }

    function openDomainModal(id = null) {
        const d = id ? domainsCache.find(x => x.id === id) : null;
        const title = d ? 'Редактировать домен' : 'Новый домен';

        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold mb-4">${title}</h3>
                <div id="domain-form-error" class="hidden mb-3 p-3 bg-red-50 text-red-700 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Имя домена</label>
                        <input id="df-name" type="text" value="${d?.name || ''}" placeholder="go.example.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">URL назначения</label>
                        <input id="df-target" type="url" value="${d?.target_url || ''}" placeholder="https://example.com"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Redirect</label>
                            <select id="df-redirect"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="301" ${d?.redirect_type == 301 ? 'selected' : ''}>301</option>
                                <option value="302" ${d?.redirect_type == 302 ? 'selected' : ''}>302</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input id="df-forward" type="checkbox" ${d?.forward_query ? 'checked' : ''}
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Forward query
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input id="df-active" type="checkbox" ${d ? (d.is_active ? 'checked' : '') : 'checked'}
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Активен
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Extra query</label>
                        <input id="df-extra-query" type="text" value="${d?.extra_query || ''}" placeholder="utm_source=short"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Extra path</label>
                        <input id="df-extra-path" type="text" value="${d?.extra_path || ''}" placeholder="/landing"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Отмена</button>
                    <button onclick="Admin.saveDomain(${d?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Сохранить</button>
                </div>
            </div>
        `);
    }

    async function saveDomain(id) {
        const body = {
            name: $('#df-name').value,
            target_url: $('#df-target').value || null,
            redirect_type: $('#df-redirect').value,
            forward_query: $('#df-forward').checked,
            extra_query: $('#df-extra-query').value || null,
            extra_path: $('#df-extra-path').value || null,
            is_active: $('#df-active').checked,
        };
        const errEl = $('#domain-form-error');
        hide(errEl);

        try {
            if (id) {
                await api(`/domains/${id}`, { method: 'PUT', body: JSON.stringify(body) });
                toast('Домен обновлён');
            } else {
                await api('/domains', { method: 'POST', body: JSON.stringify(body) });
                toast('Домен создан');
            }
            closeModal();
            loadDomains();
        } catch (e) {
            errEl.textContent = e.message;
            show(errEl);
        }
    }

    async function deleteDomain(id) {
        if (!confirm('Удалить домен?')) return;
        try {
            await api(`/domains/${id}`, { method: 'DELETE' });
            toast('Домен удалён');
            loadDomains();
        } catch (e) { toast(e.message, 'error'); }
    }

    let linksPage = 1;
    let linksSearch = '';

    async function loadLinks(page = 1) {
        linksPage = page;
        try {
            let url = `/links?per_page=15&page=${page}`;
            if (linksSearch) url += `&search=${encodeURIComponent(linksSearch)}`;
            const data = await api(url);
            renderLinks(data.data, data.meta || data);
        } catch (e) { toast(e.message, 'error'); }
    }

    function renderLinks(items, meta) {
        const tbody = $('#links-tbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">Нет ссылок</td></tr>';
            $('#links-pagination').innerHTML = '';
            return;
        }
        tbody.innerHTML = items.map(l => `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-mono text-indigo-600 font-medium">${l.code}</td>
                <td class="px-6 py-4 text-gray-500 text-xs">${truncate(l.short_url, 35) || '—'}</td>
                <td class="px-6 py-4 text-gray-500">${truncate(l.target_url)}</td>
                <td class="px-6 py-4 text-center font-medium">${l.hit_count}</td>
                <td class="px-6 py-4 text-center">${badge(l.is_active)}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick="Admin.openLinkModal(${l.id})"
                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Изменить</button>
                    <button onclick="Admin.deleteLink(${l.id})"
                        class="text-red-500 hover:text-red-700 text-sm font-medium">Удалить</button>
                </td>
            </tr>
        `).join('');

        renderPagination('#links-pagination', meta, loadLinks);
    }

    async function openLinkModal(id = null) {
        if (!domainsCache.length) {
            try {
                const d = await api('/domains?per_page=100');
                domainsCache = d.data;
            } catch (_) {}
        }

        let l = null;
        if (id) {
            try {
                const data = await api(`/links/${id}`);
                l = data.data;
            } catch (e) { toast(e.message, 'error'); return; }
        }

        const title = l ? 'Редактировать ссылку' : 'Новая ссылка';
        const domainOptions = domainsCache.map(d =>
            `<option value="${d.id}" ${l?.domain_id == d.id ? 'selected' : ''}>${d.name}</option>`
        ).join('');

        let passwordsHtml = '';
        if (l && l.passwords && l.passwords.length) {
            passwordsHtml = `
                <div>
                    <label class="block text-sm font-medium mb-1">Пароли</label>
                    <div class="space-y-1 text-xs text-gray-600">
                        ${l.passwords.map(p => `
                            <div class="flex items-center justify-between bg-gray-50 rounded px-3 py-1.5">
                                <span class="font-mono">${p.password}</span>
                                <span>Кликов: ${p.hit_count}${p.max_uses ? ' / ' + p.max_uses : ''} ${badge(p.is_active)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold mb-4">${title}</h3>
                <div id="link-form-error" class="hidden mb-3 p-3 bg-red-50 text-red-700 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Домен</label>
                            <select id="lf-domain"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                ${domainOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Код</label>
                            <input id="lf-code" type="text" value="${l?.code || ''}" placeholder="promo1"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">URL назначения</label>
                        <input id="lf-target" type="url" value="${l?.target_url || ''}" placeholder="https://example.com/page"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Redirect</label>
                            <select id="lf-redirect"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="301" ${l?.redirect_type == 301 ? 'selected' : ''}>301</option>
                                <option value="302" ${l?.redirect_type == 302 ? 'selected' : ''}>302</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input id="lf-forward" type="checkbox" ${l?.forward_query ? 'checked' : ''}
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Forward query
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input id="lf-active" type="checkbox" ${l ? (l.is_active ? 'checked' : '') : 'checked'}
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Активна
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Extra query</label>
                        <input id="lf-extra-query" type="text" value="${l?.extra_query || ''}" placeholder="utm_source=short"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Extra path</label>
                        <input id="lf-extra-path" type="text" value="${l?.extra_path || ''}" placeholder="/landing"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    ${passwordsHtml}
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">Отмена</button>
                    <button onclick="Admin.saveLink(${l?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Сохранить</button>
                </div>
            </div>
        `);
    }

    async function saveLink(id) {
        const body = {
            domain_id: parseInt($('#lf-domain').value),
            code: $('#lf-code').value,
            target_url: $('#lf-target').value,
            redirect_type: $('#lf-redirect').value,
            forward_query: $('#lf-forward').checked,
            extra_query: $('#lf-extra-query').value || null,
            extra_path: $('#lf-extra-path').value || null,
            is_active: $('#lf-active').checked,
        };
        const errEl = $('#link-form-error');
        hide(errEl);

        try {
            if (id) {
                await api(`/links/${id}`, { method: 'PUT', body: JSON.stringify(body) });
                toast('Ссылка обновлена');
            } else {
                await api('/links', { method: 'POST', body: JSON.stringify(body) });
                toast('Ссылка создана');
            }
            closeModal();
            loadLinks(linksPage);
        } catch (e) {
            errEl.textContent = e.message;
            show(errEl);
        }
    }

    async function deleteLink(id) {
        if (!confirm('Удалить ссылку?')) return;
        try {
            await api(`/links/${id}`, { method: 'DELETE' });
            toast('Ссылка удалена');
            loadLinks(linksPage);
        } catch (e) { toast(e.message, 'error'); }
    }

    function renderPagination(selector, meta, loadFn) {
        const container = $(selector);
        if (!meta || !meta.last_page || meta.last_page <= 1) { container.innerHTML = ''; return; }

        let html = '';
        for (let i = 1; i <= meta.last_page; i++) {
            const active = i === meta.current_page;
            html += `<button onclick="Admin._paginate('${selector}', ${i})"
                class="px-3 py-1.5 text-sm rounded-lg transition ${
                active ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50'
            }">${i}</button>`;
        }
        container.innerHTML = html;

        container._loadFn = loadFn;
    }

    function _paginate(selector, page) {
        const container = $(selector);
        if (container._loadFn) container._loadFn(page);
    }

    function init() {
        $('#login-form')?.addEventListener('submit', login);

        $$('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        $('#links-search')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                linksSearch = e.target.value.trim();
                loadLinks(1);
            }, 400);
        });

        $('#modal-backdrop')?.addEventListener('click', (e) => {
            if (e.target === $('#modal-backdrop')) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal();
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        openDomainModal,
        saveDomain,
        deleteDomain,
        openLinkModal,
        saveLink,
        deleteLink,
        closeModal,
        logout,
        _paginate,
    };
})();
window.Admin = Admin;

