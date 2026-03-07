const Admin = (function () {

    let token = null;
    let user = null;
    let currentTab = 'domains';
    let domainsCache = [];
    let searchTimeout = null;

    async function api(path, opts = {}) {
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const res = await fetch(`/api${path}`, { ...opts, headers });
        const data = await res.json();
        if (res.status === 401) { token = null; user = null; showAuth(); throw new Error('Unauthorized'); }
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
            ? '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-500/15 text-emerald-400">Активен</span>'
            : '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-gray-700 text-gray-400">Неактивен</span>';
    }

    function verifiedBadge(v) {
        return v
            ? '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-blue-500/15 text-blue-400">Да</span>'
            : '<span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-500/15 text-yellow-400">Нет</span>';
    }

    function truncate(str, len = 40) {
        if (!str) return '<span class="text-gray-600">—</span>';
        return str.length > len ? str.slice(0, len) + '…' : str;
    }

    function toast(msg, type = 'success') {
        const el = document.createElement('div');
        el.className = `fixed bottom-6 right-6 z-[100] px-5 py-3 rounded-xl shadow-lg text-sm font-medium text-white transition-all
            ${type === 'success' ? 'bg-emerald-600' : 'bg-red-600'}`;
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

    function inputHtml(id, label, type, value, placeholder) {
        return `
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-400">${label}</label>
                <input id="${id}" type="${type}" value="${value}" placeholder="${placeholder}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-200 placeholder-gray-500
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>`;
    }

    function selectHtml(id, label, options) {
        return `
            <div>
                <label class="block text-sm font-medium mb-1 text-gray-400">${label}</label>
                <select id="${id}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-200
                           focus:ring-2 focus:ring-indigo-500 outline-none">
                    ${options}
                </select>
            </div>`;
    }

    function checkboxHtml(id, label, checked) {
        return `
            <label class="flex items-center gap-2 text-sm text-gray-300">
                <input id="${id}" type="checkbox" ${checked ? 'checked' : ''}
                    class="rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
                ${label}
            </label>`;
    }

    const REDIRECT_TYPES = [
        { value: '301', label: '301 Permanent' },
        { value: '302', label: '302 Temporary' },
        { value: '307', label: '307 Temporary (preserve method)' },
        { value: '308', label: '308 Permanent (preserve method)' },
    ];

    function redirectOptions(current) {
        return REDIRECT_TYPES.map(t =>
            `<option value="${t.value}" ${current == t.value ? 'selected' : ''}>${t.label}</option>`
        ).join('');
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
            <span class="text-sm text-gray-400">${user.name}</span>
            <button onclick="Admin.logout()"
                class="text-sm text-red-400 hover:text-red-300 font-medium transition">Выйти</button>
        `;
        switchTab('domains');
    }

    async function login(e) {
        e.preventDefault();
        const form = e.target;
        const errEl = $('#auth-error');
        hide(errEl);
        try {
            const data = await api('/login', {
                method: 'POST',
                body: JSON.stringify({ email: form.email.value, password: form.password.value }),
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
        token = null; user = null; showAuth();
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
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Нет доменов</td></tr>';
            $('#domains-pagination').innerHTML = '';
            return;
        }
        tbody.innerHTML = items.map(d => `
            <tr class="hover:bg-gray-800/50 transition">
                <td class="px-6 py-4 font-medium text-gray-100">${d.name}</td>
                <td class="px-6 py-4 text-gray-400">${truncate(d.target_url)}</td>
                <td class="px-6 py-4 text-center text-gray-300">${d.links_count ?? '—'}</td>
                <td class="px-6 py-4 text-center">${badge(d.is_active)}</td>
                <td class="px-6 py-4 text-center">${verifiedBadge(d.is_verified)}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick="Admin.openDomainModal(${d.id})"
                        class="text-indigo-400 hover:text-indigo-300 text-sm font-medium transition">Изменить</button>
                    <button onclick="Admin.deleteDomain(${d.id})"
                        class="text-red-400 hover:text-red-300 text-sm font-medium transition">Удалить</button>
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
                <h3 class="text-lg font-bold mb-4 text-gray-100">${title}</h3>
                <div id="domain-form-error" class="hidden mb-3 p-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    ${inputHtml('df-name', 'Имя домена', 'text', d?.name || '', 'go.example.com')}
                    ${inputHtml('df-target', 'URL назначения', 'url', d?.target_url || '', 'https://example.com')}
                    <div class="grid grid-cols-2 gap-4">
                        ${selectHtml('df-redirect', 'Redirect', redirectOptions(d?.redirect_type))}
                        <div class="flex items-end gap-4">
                            ${checkboxHtml('df-forward', 'Forward query', d?.forward_query)}
                            ${checkboxHtml('df-active', 'Активен', d ? d.is_active : true)}
                        </div>
                    </div>
                    ${inputHtml('df-extra-query', 'Extra query', 'text', d?.extra_query || '', 'utm_source=short')}
                    ${inputHtml('df-extra-path', 'Extra path', 'text', d?.extra_path || '', '/landing')}
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700 transition">Отмена</button>
                    <button onclick="Admin.saveDomain(${d?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition">Сохранить</button>
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
            closeModal(); loadDomains();
        } catch (e) { errEl.textContent = e.message; show(errEl); }
    }

    async function deleteDomain(id) {
        if (!confirm('Удалить домен?')) return;
        try {
            await api(`/domains/${id}`, { method: 'DELETE' });
            toast('Домен удалён'); loadDomains();
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
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Нет ссылок</td></tr>';
            $('#links-pagination').innerHTML = '';
            return;
        }
        tbody.innerHTML = items.map(l => `
            <tr class="hover:bg-gray-800/50 transition">
                <td class="px-6 py-4 font-mono text-indigo-400 font-medium">${l.code}</td>
                <td class="px-6 py-4 text-gray-400 text-xs">${truncate(l.short_url, 35) || '—'}</td>
                <td class="px-6 py-4 text-gray-400">${truncate(l.target_url)}</td>
                <td class="px-6 py-4 text-center text-gray-200 font-medium">${l.hit_count}</td>
                <td class="px-6 py-4 text-center">${badge(l.is_active)}</td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick="Admin.openLinkModal(${l.id})"
                        class="text-indigo-400 hover:text-indigo-300 text-sm font-medium transition">Изменить</button>
                    <button onclick="Admin.deleteLink(${l.id})"
                        class="text-red-400 hover:text-red-300 text-sm font-medium transition">Удалить</button>
                </td>
            </tr>
        `).join('');
        renderPagination('#links-pagination', meta, loadLinks);
    }

    async function openLinkModal(id = null) {
        if (!domainsCache.length) {
            try { const d = await api('/domains?per_page=100'); domainsCache = d.data; } catch (_) {}
        }

        let l = null;
        if (id) {
            try { const data = await api(`/links/${id}`); l = data.data; }
            catch (e) { toast(e.message, 'error'); return; }
        }

        const title = l ? 'Редактировать ссылку' : 'Новая ссылка';
        const domainOpts = domainsCache.map(d =>
            `<option value="${d.id}" ${l?.domain_id == d.id ? 'selected' : ''}>${d.name}</option>`
        ).join('');

        let passwordsHtml = '';
        if (l?.passwords?.length) {
            passwordsHtml = `
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-400">Пароли</label>
                    <div class="space-y-1.5">
                        ${l.passwords.map(p => `
                            <div class="flex items-center justify-between bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-xs">
                                <span class="font-mono text-gray-200">${p.password}</span>
                                <span class="text-gray-400">Кликов: ${p.hit_count}${p.max_uses ? ' / ' + p.max_uses : ''} ${badge(p.is_active)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        }

        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold mb-4 text-gray-100">${title}</h3>
                <div id="link-form-error" class="hidden mb-3 p-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        ${selectHtml('lf-domain', 'Домен', domainOpts)}
                        ${inputHtml('lf-code', 'Код', 'text', l?.code || '', 'promo1')}
                    </div>
                    ${inputHtml('lf-target', 'URL назначения', 'url', l?.target_url || '', 'https://example.com/page')}
                    <div class="grid grid-cols-2 gap-4">
                        ${selectHtml('lf-redirect', 'Redirect', redirectOptions(l?.redirect_type))}
                        <div class="flex items-end gap-4">
                            ${checkboxHtml('lf-forward', 'Forward query', l?.forward_query)}
                            ${checkboxHtml('lf-active', 'Активна', l ? l.is_active : true)}
                        </div>
                    </div>
                    ${inputHtml('lf-extra-query', 'Extra query', 'text', l?.extra_query || '', 'utm_source=short')}
                    ${inputHtml('lf-extra-path', 'Extra path', 'text', l?.extra_path || '', '/landing')}
                    ${passwordsHtml}
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700 transition">Отмена</button>
                    <button onclick="Admin.saveLink(${l?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition">Сохранить</button>
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
            closeModal(); loadLinks(linksPage);
        } catch (e) { errEl.textContent = e.message; show(errEl); }
    }

    async function deleteLink(id) {
        if (!confirm('Удалить ссылку?')) return;
        try {
            await api(`/links/${id}`, { method: 'DELETE' });
            toast('Ссылка удалена'); loadLinks(linksPage);
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
                active
                    ? 'bg-indigo-600 text-white'
                    : 'bg-gray-800 border border-gray-700 text-gray-300 hover:bg-gray-700'
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
        $$('.nav-btn').forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));
        $('#links-search')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { linksSearch = e.target.value.trim(); loadLinks(1); }, 400);
        });
        $('#modal-backdrop')?.addEventListener('click', (e) => {
            if (e.target === $('#modal-backdrop')) closeModal();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        openDomainModal, saveDomain, deleteDomain,
        openLinkModal, saveLink, deleteLink,
        closeModal, logout, _paginate,
    };
})();

window.Admin = Admin;
