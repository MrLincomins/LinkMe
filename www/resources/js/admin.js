const Admin = (function () {

    let token = null;
    let user = null;
    let currentTab = 'domains';
    let domainsCache = [];
    let searchTimeout = null;
    let modalPasswords = [];

    async function api(path, opts = {}) {
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;
        const res = await fetch(`/api${path}`, { ...opts, headers });
        const data = await res.json();
        if (res.status === 401) { token = null; user = null; clearSession(); showAuth(); throw new Error('Unauthorized'); }
        if (!res.ok) {
            const msg = data.message || Object.values(data.errors || {}).flat().join(', ') || 'Ошибка';
            throw new Error(msg);
        }
        return data;
    }

    const $ = (s) => document.querySelector(s);
    const qsa = (s) => document.querySelectorAll(s);
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
        modalPasswords = [];
    }

    function inputHtml(id, label, type, value, placeholder, disabled = false) {
        const disabledAttr = disabled ? 'disabled' : '';
        const disabledClass = disabled ? 'opacity-40 cursor-not-allowed' : '';
        return `
            <div id="${id}-wrap" class="${disabledClass} transition-opacity">
                <label for="${id}" class="block text-sm font-medium mb-1 text-gray-400">${label}</label>
                <input id="${id}" type="${type}" value="${value}" placeholder="${placeholder}" ${disabledAttr}
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-200 placeholder-gray-500
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none
                           disabled:opacity-50 disabled:cursor-not-allowed">
            </div>`;
    }

    function selectHtml(id, label, options) {
        return `
            <div>
                <label for="${id}" class="block text-sm font-medium mb-1 text-gray-400">${label}</label>
                <select id="${id}"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-200
                           focus:ring-2 focus:ring-indigo-500 outline-none">
                    ${options}
                </select>
            </div>`;
    }

    function checkboxHtml(id, label, checked) {
        return `
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-300">
                    <input id="${id}" type="checkbox" ${checked ? 'checked' : ''}
                        class="rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
                    ${label}
                </label>
            </div>`;
    }

    function sectionTitle(text) {
        return `
            <div class="pt-2 pb-1 border-t border-gray-800 mt-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">${text}</p>
            </div>`;
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

    function bindForwardQueryToggle(forwardId, extraQueryId) {
        const cb = $(`#${forwardId}`);
        if (!cb) return;

        const update = () => {
            const wrap = $(`#${extraQueryId}-wrap`);
            const input = $(`#${extraQueryId}`);
            if (!wrap || !input) return;

            if (cb.checked) {
                wrap.classList.add('opacity-40', 'cursor-not-allowed');
                input.disabled = true;
                input.value = '';
                input.placeholder = 'Недоступно при forward query';
            } else {
                wrap.classList.remove('opacity-40', 'cursor-not-allowed');
                input.disabled = false;
                input.placeholder = 'utm_source=short';
            }
        };

        cb.addEventListener('change', update);
        update();
    }

    function renderPasswordRows() {
        const container = $('#passwords-list');
        if (!container) return;

        if (!modalPasswords.length) {
            container.innerHTML = '<p class="text-xs text-gray-500 py-2">Нет паролей. Добавьте хотя бы один.</p>';
            return;
        }

        container.innerHTML = modalPasswords.map((p, i) => {
            const isExisting = !!p.id;
            const usageInfo = isExisting ? `<span class="text-gray-500 text-xs">${p.hit_count || 0} переходов</span>` : '';

            return `
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 space-y-3" data-pw-index="${i}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-400">${isExisting ? 'Пароль #' + p.id : 'Новый пароль'}</span>
                        <div class="flex items-center gap-3">
                            ${usageInfo}
                            <button onclick="Admin.removePassword(${i})"
                                class="text-red-400 hover:text-red-300 text-xs font-medium transition">Удалить</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Пароль</label>
                            <input type="text" value="${p.password || ''}" placeholder="Оставьте пустым для доступа без пароля"
                                onchange="Admin.updatePassword(${i}, 'password', this.value)"
                                class="w-full bg-gray-900 border border-gray-600 rounded-lg px-2.5 py-1.5 text-xs text-gray-200 placeholder-gray-600
                                       focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Макс. использований</label>
                            <input type="number" value="${p.max_uses ?? ''}" placeholder="Без лимита" min="1"
                                onchange="Admin.updatePassword(${i}, 'max_uses', this.value)"
                                class="w-full bg-gray-900 border border-gray-600 rounded-lg px-2.5 py-1.5 text-xs text-gray-200 placeholder-gray-600
                                       focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">URL назначения (свой для этого пароля)</label>
                        <input type="url" value="${p.target_url || ''}" placeholder="По умолчанию — основной URL ссылки"
                            onchange="Admin.updatePassword(${i}, 'target_url', this.value)"
                            class="w-full bg-gray-900 border border-gray-600 rounded-lg px-2.5 py-1.5 text-xs text-gray-200 placeholder-gray-600
                                   focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Extra query</label>
                            <input type="text" value="${p.extra_query || ''}" placeholder="utm_source=pw"
                                onchange="Admin.updatePassword(${i}, 'extra_query', this.value)"
                                class="w-full bg-gray-900 border border-gray-600 rounded-lg px-2.5 py-1.5 text-xs text-gray-200 placeholder-gray-600
                                       focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Extra path</label>
                            <input type="text" value="${p.extra_path || ''}" placeholder="/page"
                                onchange="Admin.updatePassword(${i}, 'extra_path', this.value)"
                                class="w-full bg-gray-900 border border-gray-600 rounded-lg px-2.5 py-1.5 text-xs text-gray-200 placeholder-gray-600
                                       focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-300">
                        <input type="checkbox" ${p.is_active !== false ? 'checked' : ''}
                            onchange="Admin.updatePassword(${i}, 'is_active', this.checked)"
                            class="rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500">
                        Активен
                    </label>
                </div>`;
        }).join('');
    }

    function addPassword() {
        modalPasswords.push({
            password: '',
            target_url: '',
            extra_query: '',
            extra_path: '',
            max_uses: null,
            is_active: true,
            _new: true,
        });
        renderPasswordRows();
    }

    function removePassword(index) {
        const p = modalPasswords[index];
        if (p.id && !confirm('Удалить этот пароль?')) return;
        if (p.id) {
            p._deleted = true;
        } else {
            modalPasswords.splice(index, 1);
        }
        renderPasswordRows();
    }

    function updatePassword(index, field, value) {
        if (field === 'max_uses') {
            modalPasswords[index][field] = value === '' ? null : parseInt(value);
        } else if (field === 'is_active') {
            modalPasswords[index][field] = value;
        } else {
            modalPasswords[index][field] = value;
        }
        if (modalPasswords[index].id) {
            modalPasswords[index]._dirty = true;
        }
    }

    function bindPasswordToggle() {
        const cb = $('#lf-has-passwords');
        const section = $('#passwords-section');
        if (!cb || !section) return;

        const update = () => {
            if (cb.checked) {
                section.classList.remove('hidden');
                if (!modalPasswords.length) addPassword();
            } else {
                section.classList.add('hidden');
            }
        };

        cb.addEventListener('change', update);
        update();
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

    function saveSession() {
        try {
            localStorage.setItem('linkme_token', token);
            localStorage.setItem('linkme_user', JSON.stringify(user));
        } catch (_) {}
    }

    function clearSession() {
        try {
            localStorage.removeItem('linkme_token');
            localStorage.removeItem('linkme_user');
        } catch (_) {}
    }

    function restoreSession() {
        try {
            const t = localStorage.getItem('linkme_token');
            const u = localStorage.getItem('linkme_user');
            if (t && u) {
                token = t;
                user = JSON.parse(u);
                return true;
            }
        } catch (_) {}
        return false;
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
            saveSession();
            showDashboard();
        } catch (err) {
            errEl.textContent = err.message;
            show(errEl);
        }
    }

    async function logout() {
        try { await api('/logout', { method: 'POST' }); } catch (_) {}
        token = null; user = null;
        clearSession();
        showAuth();
    }

    function switchTab(tab) {
        currentTab = tab;
        qsa('.nav-btn').forEach(b => {
            if (b.dataset.tab === tab) b.setAttribute('data-active', '');
            else b.removeAttribute('data-active');
        });
        qsa('[id^="tab-"]').forEach(s => hide(s));
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
        const isForward = d ? d.forward_query : false;

        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold mb-1 text-gray-100">${title}</h3>
                <p class="text-xs text-gray-500 mb-4">Домен используется как базовый адрес для коротких ссылок. Если задан URL назначения — корень домена будет перенаправлять туда.</p>

                <div id="domain-form-error" class="hidden mb-3 p-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    ${inputHtml('df-name', 'Имя домена', 'text', d?.name || '', 'go.example.com')}
                    ${inputHtml('df-target', 'URL назначения (корень домена)', 'url', d?.target_url || '', 'https://example.com')}

                    ${sectionTitle('Настройки редиректа')}

                    ${selectHtml('df-redirect', 'Тип редиректа', redirectOptions(d?.redirect_type))}

                    <div class="space-y-3">
                        ${checkboxHtml('df-forward', 'Пробрасывать query-параметры', isForward)}
                        ${checkboxHtml('df-active', 'Домен активен', d ? d.is_active : true)}
                    </div>

                    ${sectionTitle('Дополнительные параметры')}

                    ${inputHtml('df-extra-query', 'Дополнительные query-параметры', 'text', d?.extra_query || '', 'utm_source=short', isForward)}
                    ${inputHtml('df-extra-path', 'Дополнительный путь', 'text', d?.extra_path || '', '/landing')}
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700 transition">Отмена</button>
                    <button onclick="Admin.saveDomain(${d?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition">Сохранить</button>
                </div>
            </div>
        `);

        bindForwardQueryToggle('df-forward', 'df-extra-query');
    }

    async function saveDomain(id) {
        const body = {
            name: $('#df-name').value,
            target_url: $('#df-target').value || null,
            redirect_type: $('#df-redirect').value,
            forward_query: $('#df-forward').checked,
            extra_query: $('#df-forward').checked ? null : ($('#df-extra-query').value || null),
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
        if (!confirm('Удалить домен? Домен с привязанными ссылками удалить нельзя.')) return;
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
            `<option value="${d.id}" ${l?.domain_id == d.id ? 'selected' : ''}>${d.name}${d.is_active ? '' : ' (неактивен)'}</option>`
        ).join('');

        const isForward = l ? l.forward_query : false;
        const hasPasswords = l?.passwords?.length > 0;

        modalPasswords = hasPasswords
            ? l.passwords.map(p => ({ ...p }))
            : [];

        openModal(`
            <div class="p-6">
                <h3 class="text-lg font-bold mb-1 text-gray-100">${title}</h3>
                <p class="text-xs text-gray-500 mb-4">Короткая ссылка перенаправляет посетителей с <code class="text-gray-400">домен/код</code> на URL назначения.</p>

                <div id="link-form-error" class="hidden mb-3 p-3 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg text-sm"></div>
                <div class="space-y-4">
                    ${sectionTitle('Основное')}

                    <div class="grid grid-cols-2 gap-4">
                        ${selectHtml('lf-domain', 'Домен', domainOpts)}
                        ${inputHtml('lf-code', 'Код (slug)', 'text', l?.code || '', 'promo1')}
                    </div>

                    ${inputHtml('lf-target', 'URL назначения', 'url', l?.target_url || '', 'https://example.com/page')}

                    ${sectionTitle('Настройки редиректа')}

                    ${selectHtml('lf-redirect', 'Тип редиректа', redirectOptions(l?.redirect_type))}

                    <div class="space-y-3">
                        ${checkboxHtml('lf-forward', 'Пробрасывать query-параметры', isForward)}
                        ${checkboxHtml('lf-active', 'Ссылка активна', l ? l.is_active : true)}
                    </div>

                    ${sectionTitle('Дополнительные параметры')}

                    ${inputHtml('lf-extra-query', 'Дополнительные query-параметры', 'text', l?.extra_query || '', 'utm_source=short&utm_medium=link', isForward)}
                    ${inputHtml('lf-extra-path', 'Дополнительный путь', 'text', l?.extra_path || '', '/landing')}

                    ${sectionTitle('Защита паролем')}

                    ${checkboxHtml('lf-has-passwords', 'Включить коды доступа', hasPasswords)}

                    <div id="passwords-section" class="${hasPasswords ? '' : 'hidden'} space-y-3">
                        <div id="passwords-list"></div>
                        <button onclick="Admin.addPassword()"
                            class="w-full py-2 border border-dashed border-gray-600 rounded-lg text-xs text-gray-400
                                   hover:border-indigo-500 hover:text-indigo-400 transition">
                            + Добавить пароль
                        </button>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button onclick="Admin.closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700 transition">Отмена</button>
                    <button onclick="Admin.saveLink(${l?.id || 'null'})"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition">Сохранить</button>
                </div>
            </div>
        `);

        bindForwardQueryToggle('lf-forward', 'lf-extra-query');
        bindPasswordToggle();
        renderPasswordRows();
    }

    async function saveLink(id) {
        const body = {
            domain_id: parseInt($('#lf-domain').value),
            code: $('#lf-code').value,
            target_url: $('#lf-target').value,
            redirect_type: $('#lf-redirect').value,
            forward_query: $('#lf-forward').checked,
            extra_query: $('#lf-forward').checked ? null : ($('#lf-extra-query').value || null),
            extra_path: $('#lf-extra-path').value || null,
            is_active: $('#lf-active').checked,
        };
        const errEl = $('#link-form-error');
        hide(errEl);
        try {
            let linkId = id;

            if (id) {
                await api(`/links/${id}`, { method: 'PUT', body: JSON.stringify(body) });
            } else {
                const res = await api('/links', { method: 'POST', body: JSON.stringify(body) });
                linkId = res.data.id;
            }

            const hasPasswordsEnabled = $('#lf-has-passwords')?.checked;

            if (hasPasswordsEnabled && linkId) {
                await syncPasswords(linkId);
            } else if (!hasPasswordsEnabled && linkId && modalPasswords.some(p => p.id)) {
                for (const p of modalPasswords.filter(p => p.id)) {
                    await api(`/links/${linkId}/passwords/${p.id}`, { method: 'DELETE' });
                }
            }

            toast(id ? 'Ссылка обновлена' : 'Ссылка создана');
            closeModal();
            loadLinks(linksPage);
        } catch (e) { errEl.textContent = e.message; show(errEl); }
    }

    async function syncPasswords(linkId) {
        for (const p of modalPasswords) {
            if (p._deleted && p.id) {
                await api(`/links/${linkId}/passwords/${p.id}`, { method: 'DELETE' });
                continue;
            }

            if (p._deleted) continue;

            const pwBody = {
                password: p.password || '',
                target_url: p.target_url || null,
                extra_query: p.extra_query || null,
                extra_path: p.extra_path || null,
                max_uses: p.max_uses || null,
                is_active: p.is_active !== false,
            };

            if (p._new) {
                await api(`/links/${linkId}/passwords`, { method: 'POST', body: JSON.stringify(pwBody) });
            } else if (p._dirty && p.id) {
                await api(`/links/${linkId}/passwords/${p.id}`, { method: 'PUT', body: JSON.stringify(pwBody) });
            }
        }
    }

    async function deleteLink(id) {
        if (!confirm('Удалить ссылку? Это действие необратимо.')) return;
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
        qsa('.nav-btn').forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));
        $('#links-search')?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { linksSearch = e.target.value.trim(); loadLinks(1); }, 400);
        });
        $('#modal-backdrop')?.addEventListener('click', (e) => {
            if (e.target === $('#modal-backdrop')) closeModal();
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

        if (restoreSession()) {
            api('/user').then(data => {
                user = data.data;
                saveSession();
                showDashboard();
            }).catch(() => {
                token = null; user = null;
                clearSession();
                showAuth();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        openDomainModal, saveDomain, deleteDomain,
        openLinkModal, saveLink, deleteLink,
        closeModal, logout, _paginate,
        addPassword, removePassword, updatePassword,
    };
})();

window.Admin = Admin;
