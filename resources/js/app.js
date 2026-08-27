import Alpine from 'alpinejs';

import './pages/coordinators';
import './pages/dashboard';
import './pages/hospitals';
import './pages/important-contacts-admin';
import './pages/important-contacts';
import './pages/install';
import './pages/leaves';
import './pages/reports';
import './pages/role-permissions';
import './pages/settings';
import './pages/tasks';

window.Alpine = Alpine;

const safeStorage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage can be unavailable in Safari privacy modes. UI state is
            // optional and must never prevent Alpine from initializing.
        }
    },
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const isAuthenticatedPage = () => document.querySelector('meta[name="app-authenticated"]')?.getAttribute('content') === 'true';

window.api = {
    async request(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const headers = options.headers || {};
        let body = options.body;

        if (body !== undefined && !(body instanceof FormData) && typeof body !== 'string') {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(body);
        }

        if (method !== 'GET') {
            headers['X-CSRF-TOKEN'] = csrfToken();
        }

        const network = window.Alpine?.store?.('network');
        network?.begin();

        try {
            const response = await fetch(url, { method, headers, body });

            if (response.status === 401) {
                if (isAuthenticatedPage() && !['/auth', '/login'].includes(window.location.pathname)) {
                    window.location.replace('/auth');
                }
                throw new Error('Unauthorized');
            }

            if (response.status === 419) {
                // Session expired / CSRF mismatch. Refresh the page so Laravel
                // issues a fresh token, but never allow this to loop — reload
                // at most once per minute regardless of how many requests fail.
                const lastReload = Number(sessionStorage.getItem('rahs-419-reloaded') || 0);
                if (Date.now() - lastReload > 60000) {
                    sessionStorage.setItem('rahs-419-reloaded', String(Date.now()));
                    window.location.reload();
                }
                throw new Error('Session expired');
            }

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : await response.text();

            if (!response.ok) {
                const message = data?.message
                    || (data?.errors ? Object.values(data.errors).flat().join(', ') : null)
                    || (typeof data === 'string' && data.trim() ? data : 'Request failed');
                throw new Error(message);
            }

            return data;
        } finally {
            network?.end();
        }
    },

    get(url) {
        return this.request(url);
    },

    post(url, body) {
        return this.request(url, { method: 'POST', body });
    },

    patch(url, body) {
        return this.request(url, { method: 'PATCH', body });
    },

    del(url) {
        return this.request(url, { method: 'DELETE' });
    },
};

// Register components and stores synchronously before Alpine starts. Relying
// on the alpine:init DOM event can leave Safari processing the document before
// global stores exist, exposing every cloaked overlay and blank dialog.
(() => {
    Alpine.data('connectivityStatus', () => ({
        online: true,
        timer: null,
        async check(showRestoredToast = false) {
            const wasOffline = !this.online;

            try {
                const response = await fetch(`/up?connectivity=${Date.now()}`, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });
                this.online = response.ok;
            } catch {
                this.online = false;
            }

            if (showRestoredToast && wasOffline && this.online) {
                Alpine.store('toast').success('Connection restored');
            }
        },
        init() {
            this.check();
            this.timer = window.setInterval(() => this.check(true), 15000);
        },
    }));

    Alpine.store('network', {
        pending: 0,
        get active() { return this.pending > 0; },
        begin() { this.pending += 1; },
        end() { this.pending = Math.max(0, this.pending - 1); },
    });

    Alpine.store('sidebar', {
        open: false,
        collapsed: safeStorage.get('rahs-sidebar-collapsed') === 'true',
        toggle() {
            this.open = !this.open;
        },
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            safeStorage.set('rahs-sidebar-collapsed', String(this.collapsed));
        },
    });

    Alpine.store('theme', {
        dark: safeStorage.get('rahs-theme') === 'dark',
        init() {
            if (this.dark) {
                document.documentElement.classList.add('dark');
            }
        },
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            safeStorage.set('rahs-theme', this.dark ? 'dark' : 'light');
        },
    });

    Alpine.store('toast', {
        items: [],
        id: 0,
        show(message, type = 'success', title = null, action = null) {
            const id = ++this.id;
            this.items.push({ id, message, type, title, action });
            setTimeout(() => this.dismiss(id), action ? 7000 : 4500);
        },
        success(message) {
            this.show(message, 'success');
        },
        error(message) {
            this.show(message, 'error');
        },
        info(message) {
            this.show(message, 'info');
        },
        withAction(message, label, callback, type = 'info', title = null) {
            this.show(message, type, title, { label, callback });
        },
        dismiss(id) {
            this.items = this.items.filter((i) => i.id !== id);
        },
    });

    Alpine.store('confirm', {
        open: false,
        title: 'Confirm action',
        message: '',
        confirmLabel: 'Confirm',
        tone: 'danger',
        resolver: null,
        returnFocus: null,
        ask(message, options = {}) {
            this.title = options.title || 'Confirm action';
            this.message = message;
            this.confirmLabel = options.confirmLabel || 'Confirm';
            this.tone = options.tone || 'danger';
            this.returnFocus = document.activeElement;
            this.open = true;
            return new Promise((resolve) => { this.resolver = resolve; });
        },
        finish(value) {
            this.open = false;
            const resolve = this.resolver;
            this.resolver = null;
            resolve?.(value);
            requestAnimationFrame(() => this.returnFocus?.focus?.());
        },
    });

    window.confirmAction = (message, options = {}) => Alpine.store('confirm').ask(message, options);

    Alpine.store('prompt', {
        open: false,
        title: '',
        description: '',
        submitLabel: 'Continue',
        fields: [],
        values: {},
        resolver: null,
        returnFocus: null,
        ask(options = {}) {
            this.title = options.title || 'Enter details';
            this.description = options.description || '';
            this.submitLabel = options.submitLabel || 'Continue';
            this.fields = options.fields || [];
            this.values = Object.fromEntries(this.fields.map((field) => [field.name, field.value || '']));
            this.returnFocus = document.activeElement;
            this.open = true;
            return new Promise((resolve) => { this.resolver = resolve; });
        },
        finish(value) {
            this.open = false;
            const resolve = this.resolver;
            this.resolver = null;
            resolve?.(value);
            requestAnimationFrame(() => this.returnFocus?.focus?.());
        },
        submit() {
            const missing = this.fields.find((field) => field.required && !String(this.values[field.name] || '').trim());
            if (missing) {
                document.getElementById(`global-prompt-${missing.name}`)?.focus();
                return;
            }
            this.finish({ ...this.values });
        },
    });

    window.promptAction = (options = {}) => Alpine.store('prompt').ask(options);

    Alpine.store('notifications', {
        items: [],
        unread: 0,
        _timer: null,
        init() {
            if (!isAuthenticatedPage()) {
                return;
            }
            this.refresh();
            this._timer = window.setInterval(() => this.refresh(), 20000);
            window.addEventListener('focus', () => this.refresh());
        },
        async refresh() {
            try {
                const [data, count] = await Promise.all([
                    window.api.get('/api/notifications'),
                    window.api.get('/api/notifications/unread-count'),
                ]);
                this.items = data;
                this.unread = count.count || 0;
            } catch (e) {
                /* silent */
            }
        },
        async markAllRead() {
            await window.api.post('/api/notifications/mark-all-read');
            this.items = this.items.map((n) => ({ ...n, is_read: true }));
            this.unread = 0;
        },
        async markRead(item) {
            if (item.is_read) {
                return;
            }
            await window.api.post(`/api/notifications/${item.id}/read`);
            item.is_read = true;
            this.unread = Math.max(0, this.unread - 1);
        },
        async clear() {
            await window.api.del('/api/notifications');
            this.items = [];
            this.unread = 0;
        },
    });

    Alpine.data('globalSearch', () => ({
        query: '',
        open: false,
        results: { tasks: [], hospitals: [], users: [] },
        searching: false,
        timeout: null,
        init() {
            this.$watch('query', () => {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.run(), 250);
            });
        },
        async run() {
            if (!this.query || this.query.length < 2) {
                this.results = { tasks: [], hospitals: [], users: [] };
                return;
            }
            this.searching = true;
            try {
                this.results = await window.api.get(`/api/search?q=${encodeURIComponent(this.query)}`);
                this.open = true;
            } catch (e) {
                /* silent */
            } finally {
                this.searching = false;
            }
        },
        close() {
            this.open = false;
        },
    }));

    Alpine.data('fileField', (model) => ({
        model,
        dragging: false,
        async onSelect(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }
            await this.upload(file);
        },
        onDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer.files?.[0];
            if (file) {
                this.upload(file);
            }
        },
        async upload(file) {
            const reader = new FileReader();
            reader.onload = async () => {
                const base64 = String(reader.result).split(',')[1];
                try {
                    const data = await window.api.post('/api/upload', {
                        file: base64,
                        filename: file.name,
                    });
                    this.model = data.url;
                    Alpine.store('toast').success('File uploaded');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            };
            reader.readAsDataURL(file);
        },
    }));

    Alpine.data('thaanaInput', (enabled = false) => ({
        enabled,
        autoTag(event) {
            const el = event.target;
            const value = el.value || '';
            const hasThaana = /[\u0780-\u07BF]/.test(value);
            el.dataset.thaana = hasThaana ? 'true' : 'false';
            if (hasThaana) {
                el.classList.add('thaana');
            } else {
                el.classList.remove('thaana');
            }
        },
    }));

    const createHospitalProfileState = (profile, options = {}) => ({
        profile: profile || {},
        islandName: options.islandName || '',
        atollName: options.atollName || '',
        hospitalName: options.hospitalName || '',
        hospitalContactId: options.hospitalContactId || profile?.hospital_contact_id || null,
        canEdit: !!options.canEdit,
        compact: !!options.compact,
        dashboardStyle: !!options.dashboardStyle,
        isEditing: false,
        saving: false,
        activeSection: 'overview',
        statusOptions: ['Adequate', 'Limited', 'Critical', 'Not Available'],
        get totalMedicalStaff() {
            const p = this.profile;
            return ['staff_physiotherapy', 'staff_dermatology', 'staff_ortho', 'staff_medicine', 'staff_surgeon', 'staff_gynaecology', 'staff_paediatrician', 'staff_ent', 'staff_dental', 'staff_ophthalmology', 'staff_psychology', 'staff_radiology', 'staff_anesthesiologist', 'staff_medical_officer', 'staff_psychiatrist'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
        },
        get totalNursingStaff() {
            const p = this.profile;
            return ['nurses_clinical', 'nurses_senior_registered', 'nurses_registered', 'nurses_enrolled'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
        },
        get totalAdminStaff() {
            const p = this.profile;
            return ['admin_officers_senior', 'admin_officers', 'customer_service', 'drivers', 'lab_tech', 'other_staffs'].reduce((s, f) => s + (Number(p[f]) || 0), 0);
        },
        get totalStaff() {
            return this.totalMedicalStaff + this.totalNursingStaff + this.totalAdminStaff;
        },
        get activeServices() {
            const p = this.profile;
            return ['operation_theatre_service', 'emergency_room_service', 'radiology_service', 'public_health_unit_service', 'sterilization_service', 'lab_service_available', 'poct_available', 'launch_boat_service'].filter((f) => p[f]).length;
        },
        get hasProfile() {
            const p = this.profile || {};
            return !!(p.created_at || p.updated_at);
        },
        lastUpdatedLabel() {
            const p = this.profile || {};
            if (!p.updated_at) return null;
            const then = new Date(p.updated_at);
            if (Number.isNaN(then.getTime())) return 'Recently updated';
            const days = Math.max(0, Math.floor((Date.now() - then.getTime()) / 86400000));
            if (days === 0) return 'Updated today';
            if (days === 1) return 'Updated 1 day ago';
            if (days < 30) return `Updated ${days} days ago`;
            const months = Math.floor(days / 30);
            return months === 1 ? 'Updated 1 month ago' : `Updated ${months} months ago`;
        },
        isStale() {
            const p = this.profile || {};
            if (!p.updated_at) return true;
            const then = new Date(p.updated_at);
            if (Number.isNaN(then.getTime())) return false;
            return Date.now() - then.getTime() > 60 * 86400000;
        },
        updateField(field, value) {
            this.profile[field] = value;
        },
        statusMeta(value) {
            const v = String(value || '').toLowerCase();
            if (['adequate', 'good'].includes(v)) return { color: 'text-emerald-500', bg: 'bg-emerald-500/10', border: 'border-emerald-500/20', icon: 'check-circle-2' };
            if (['limited', 'low'].includes(v)) return { color: 'text-amber-500', bg: 'bg-amber-500/10', border: 'border-amber-500/20', icon: 'alert-circle' };
            if (['critical', 'poor'].includes(v)) return { color: 'text-red-500', bg: 'bg-red-500/10', border: 'border-red-500/20', icon: 'x-circle' };
            return { color: 'text-muted-foreground', bg: 'bg-muted/30', border: 'border-border/50', icon: 'circle-dot' };
        },
        enterEdit() {
            this.isEditing = true;
        },
        cancelEdit() {
            this.isEditing = false;
        },
        toggleCompact() {
            this.compact = !this.compact;
        },
        async save() {
            this.saving = true;
            try {
                const payload = { ...this.profile, hospital_contact_id: this.hospitalContactId || null };
                const saved = await window.api.post('/api/hospital-profiles', payload);
                this.profile = saved;
                Alpine.store('toast').success('Hospital profile saved');
                this.isEditing = false;
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
            this.saving = false;
        },
    });

    Alpine.data('hospitalProfile', createHospitalProfileState);
    Alpine.data('hospitalProfilePayload', (encodedPayload) => {
        const payload = JSON.parse(atob(encodedPayload));
        return createHospitalProfileState(payload.profile || {}, payload.options || {});
    });

    Alpine.data('taskStatusBadge', (status) => ({
        status,
        classes() {
            const map = {
                pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
            };
            return map[this.status] || map.pending;
        },
        label() {
            return String(this.status || '').replace('_', ' ');
        },
    }));

    Alpine.data('priorityBadge', (priority) => ({
        priority,
        classes() {
            const map = {
                low: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                medium: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200',
                high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
                urgent: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
            };
            return map[this.priority] || map.low;
        },
        label() {
            return String(this.priority || '').replace('_', ' ');
        },
    }));
})();

Alpine.start();

// Premium select enhancement. The original select remains the source of truth
// for Alpine x-model, validation, form submission, and existing change handlers.
let activePremiumSelect = null;
let premiumSelectSequence = 0;

function closePremiumSelect(restoreFocus = false) {
    if (!activePremiumSelect) return;
    const { button, menu } = activePremiumSelect;
    menu.hidden = true;
    button.setAttribute('aria-expanded', 'false');
    if (restoreFocus) button.focus();
    activePremiumSelect = null;
}

function enhancePremiumSelect(select) {
    if (!(select instanceof HTMLSelectElement)
        || select.dataset.premiumEnhanced
        || select.multiple
        || select.hasAttribute('data-native-select')) return;

    select.dataset.premiumEnhanced = 'true';
    const id = select.id || `premium-select-${++premiumSelectSequence}`;
    if (!select.id) select.id = id;

    const wrapper = document.createElement('div');
    wrapper.className = 'premium-system-select';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('premium-system-select-source');
    select.tabIndex = -1;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'premium-system-select-trigger';
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-controls', `${id}-menu`);
    const fieldLabel = [...select.labels].map((label) => label.textContent.trim()).filter(Boolean).join(' ');
    if (fieldLabel) button.setAttribute('aria-label', fieldLabel);
    wrapper.appendChild(button);

    const menu = document.createElement('div');
    menu.id = `${id}-menu`;
    menu.className = 'premium-system-select-menu';
    menu.setAttribute('role', 'listbox');
    menu.hidden = true;
    document.body.appendChild(menu);

    const selectedText = () => select.selectedOptions[0]?.textContent?.trim() || 'Select an option';

    function syncButton() {
        button.disabled = select.disabled;
        button.classList.toggle('is-invalid', !select.checkValidity());
        button.setAttribute('aria-disabled', String(select.disabled));
        button.setAttribute('aria-required', String(select.required));
        const option = select.selectedOptions[0];
        const placeholder = !option || option.value === '';
        button.innerHTML = `<span class="premium-system-select-value${placeholder ? ' is-placeholder' : ''}">${selectedText()}</span><span class="premium-system-select-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 10 5 5 5-5"/></svg></span>`;
    }

    function positionMenu() {
        if (menu.hidden) return;
        const rect = button.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const openAbove = spaceBelow < Math.min(320, menu.scrollHeight + 16) && rect.top > spaceBelow;
        menu.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - rect.width - 8))}px`;
        menu.style.width = `${rect.width}px`;
        menu.style.maxHeight = `${Math.max(180, Math.min(340, openAbove ? rect.top - 12 : spaceBelow - 12))}px`;
        menu.style.top = openAbove ? 'auto' : `${rect.bottom + 7}px`;
        menu.style.bottom = openAbove ? `${window.innerHeight - rect.top + 7}px` : 'auto';
        menu.dataset.side = openAbove ? 'top' : 'bottom';
    }

    function chooseOption(option) {
        if (option.disabled) return;
        select.value = option.value;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncButton();
        closePremiumSelect(true);
        requestAnimationFrame(() => document.querySelectorAll('select[data-premium-enhanced]').forEach((item) => item._premiumRefresh?.()));
    }

    function renderOptions(query = '') {
        const options = [...select.options];
        const searchTerm = query.trim().toLowerCase();
        const searchable = options.length > 8;
        menu.replaceChildren();

        if (searchable) {
            const searchWrap = document.createElement('label');
            searchWrap.className = 'premium-system-select-search-wrap';
            searchWrap.innerHTML = '<span class="sr-only">Search options</span><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
            const search = document.createElement('input');
            search.type = 'search';
            search.className = 'premium-system-select-search';
            search.placeholder = 'Search options…';
            search.value = query;
            search.addEventListener('input', () => renderOptions(search.value));
            searchWrap.appendChild(search);
            menu.appendChild(searchWrap);
        }

        const list = document.createElement('div');
        list.className = 'premium-system-select-options';
        const visible = options.filter((option) => !searchTerm || option.textContent.toLowerCase().includes(searchTerm));
        visible.forEach((option) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'premium-system-select-option';
            if (option.selected) item.classList.add('is-selected');
            if (option.disabled) item.classList.add('is-disabled');
            item.disabled = option.disabled;
            item.setAttribute('role', 'option');
            item.setAttribute('aria-selected', String(option.selected));
            item.innerHTML = `<span>${option.textContent.trim()}</span>${option.selected ? '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>' : ''}`;
            item.addEventListener('click', () => chooseOption(option));
            list.appendChild(item);
        });
        if (!visible.length) {
            const empty = document.createElement('p');
            empty.className = 'premium-system-select-empty';
            empty.textContent = 'No matching options';
            list.appendChild(empty);
        }
        menu.appendChild(list);
        requestAnimationFrame(() => {
            positionMenu();
            if (searchable && query === '') menu.querySelector('input')?.focus();
        });
    }

    function openMenu() {
        if (select.disabled) return;
        if (activePremiumSelect?.select === select) {
            closePremiumSelect();
            return;
        }
        closePremiumSelect();
        renderOptions();
        menu.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        activePremiumSelect = { select, button, menu };
        requestAnimationFrame(positionMenu);
    }

    button.addEventListener('click', openMenu);
    button.addEventListener('keydown', (event) => {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
            event.preventDefault();
            if (!activePremiumSelect || activePremiumSelect.select !== select) openMenu();
            requestAnimationFrame(() => menu.querySelector('[role="option"]:not(:disabled)')?.focus());
        }
        if (event.key === 'Escape') closePremiumSelect(true);
    });
    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { event.preventDefault(); closePremiumSelect(true); }
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        const items = [...menu.querySelectorAll('[role="option"]:not(:disabled)')];
        const current = items.indexOf(document.activeElement);
        const next = event.key === 'ArrowDown' ? Math.min(items.length - 1, current + 1) : Math.max(0, current - 1);
        items[next]?.focus();
    });
    select.addEventListener('change', () => { syncButton(); if (!menu.hidden) renderOptions(); });
    select.addEventListener('input', syncButton);
    select.addEventListener('focus', () => button.focus());
    select._premiumRefresh = () => { syncButton(); if (!menu.hidden) renderOptions(); };

    const optionObserver = new MutationObserver(() => select._premiumRefresh());
    optionObserver.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected', 'label', 'value'] });
    syncButton();
}

function enhancePremiumSelects(root = document) {
    root.querySelectorAll?.('select:not([multiple]):not([data-native-select])').forEach(enhancePremiumSelect);
}

requestAnimationFrame(() => enhancePremiumSelects());
new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) return;
        if (node.matches('select')) enhancePremiumSelect(node);
        enhancePremiumSelects(node);
    }));
}).observe(document.body, { childList: true, subtree: true });

document.addEventListener('pointerdown', (event) => {
    if (!activePremiumSelect) return;
    if (activePremiumSelect.menu.contains(event.target) || activePremiumSelect.button.contains(event.target)) return;
    closePremiumSelect();
});
window.addEventListener('resize', () => closePremiumSelect());
document.addEventListener('scroll', () => closePremiumSelect(), true);

const dirtyForms = new Set();
document.addEventListener('input', (event) => {
    const form = event.target.closest?.('.app-ui form');
    if (form && String(form.method || '').toLowerCase() !== 'get' && event.target.type !== 'search') dirtyForms.add(form);
});
document.addEventListener('submit', (event) => dirtyForms.delete(event.target));
window.addEventListener('beforeunload', (event) => {
    if (!dirtyForms.size) return;
    event.preventDefault();
    event.returnValue = '';
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab') return;
    const dialogs = [...document.querySelectorAll('[role="dialog"], [role="alertdialog"]')].filter((dialog) => dialog.getClientRects().length > 0);
    const dialog = dialogs.at(-1);
    if (!dialog) return;
    const focusable = [...dialog.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')].filter((element) => element.getClientRects().length > 0);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
});

// Give every native temporal field a consistent, explicit calendar/clock action.
// The platform picker remains in use for excellent keyboard and mobile support.
function enhanceTemporalInput(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.temporalEnhanced) return;
    if (!['date', 'datetime-local', 'time', 'month', 'week'].includes(input.type)) return;
    input.dataset.temporalEnhanced = 'true';
    const wrapper = document.createElement('div');
    wrapper.className = 'premium-temporal-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'premium-temporal-trigger';
    trigger.setAttribute('aria-label', input.type === 'time' ? 'Open time picker' : 'Open calendar');
    trigger.innerHTML = input.type === 'time'
        ? '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>'
        : '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>';
    trigger.addEventListener('click', () => {
        input.focus();
        if (typeof input.showPicker === 'function') input.showPicker();
        else input.click();
    });
    wrapper.appendChild(trigger);
}

function enhanceTemporalInputs(root = document) {
    root.querySelectorAll?.('input[type="date"], input[type="datetime-local"], input[type="time"], input[type="month"], input[type="week"]').forEach(enhanceTemporalInput);
}

requestAnimationFrame(() => enhanceTemporalInputs());
new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
    if (!(node instanceof Element)) return;
    if (node.matches('input[type="date"], input[type="datetime-local"], input[type="time"], input[type="month"], input[type="week"]')) enhanceTemporalInput(node);
    enhanceTemporalInputs(node);
}))).observe(document.body, { childList: true, subtree: true });
