function contactsAdmin(props = {}) {
        return {
            contacts: props.contacts || [],
            selected: new Set(),
            filters: { search: '' },
            showForm: false,
            editing: false,
            editingContact: null,
            saving: false,
            form: {},

            get getFiltered() {
                const q = this.filters.search.toLowerCase();
                return this.contacts.filter((c) => {
                    const hay = (c.name + ' ' + (c.title || '') + ' ' + (c.organization || '') + ' ' + (c.phone_primary || '')).toLowerCase();
                    return hay.includes(q);
                });
            },

            get organizationCount() {
                return new Set(this.contacts.map((c) => c.organization).filter(Boolean)).size;
            },

            get highPriorityCount() {
                return this.contacts.filter((c) => Number(c.priority) < 10).length;
            },

            initials(name) {
                return String(name || '')
                    .trim()
                    .split(/\s+/)
                    .slice(0, 2)
                    .map((part) => part.charAt(0))
                    .join('')
                    .toUpperCase();
            },

            get selected() {
                return this._selected || (this._selected = new Set());
            },
            set selected(val) {
                this._selected = val;
            },

            toggleSelect(id, checked) {
                if (checked) this.selected.add(id);
                else this.selected.delete(id);
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.getFiltered.forEach((c) => this.selected.add(c.id));
                } else {
                    this.selected.clear();
                }
            },

            openCreate() {
                this.editing = false;
                this.form = { name: '', title: '', organization: '', phone_primary: '', phone_secondary: '', email: '', notes: '', priority: 100 };
                this.showForm = true;
            },

            openEdit(c) {
                this.editing = true;
                this.editingContact = c;
                this.form = {
                    name: c.name,
                    title: c.title,
                    organization: c.organization || '',
                    phone_primary: c.phone_primary,
                    phone_secondary: c.phone_secondary || '',
                    email: c.email || '',
                    notes: c.notes || '',
                    priority: c.priority,
                };
                this.showForm = true;
            },

            async save() {
                this.saving = true;
                try {
                    if (this.editing) {
                        const updated = await window.api.patch(`/api/important-contacts/${this.editingContact.id}`, this.form);
                        const idx = this.contacts.findIndex((c) => c.id === updated.id);
                        if (idx >= 0) this.contacts[idx] = updated;
                        Alpine.store('toast').success('Contact updated');
                    } else {
                        const created = await window.api.post('/api/important-contacts', this.form);
                        this.contacts.unshift(created);
                        Alpine.store('toast').success('Contact added');
                    }
                    this.showForm = false;
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                } finally {
                    this.saving = false;
                }
            },

            async deactivate(c) {
                if (!await window.confirmAction(`Deactivate contact "${c.name}"?`, { title: 'Deactivate contact', confirmLabel: 'Deactivate' })) return;
                try {
                    await window.api.post(`/api/important-contacts/${c.id}/deactivate`);
                    this.contacts = this.contacts.filter((x) => x.id !== c.id);
                    this.selected.delete(c.id);
                    Alpine.store('toast').success('Contact deactivated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async deactivateSelected() {
                if (this.selected.size === 0) return;
                if (!await window.confirmAction(`Deactivate ${this.selected.size} selected contact${this.selected.size !== 1 ? 's' : ''}?`, { title: 'Deactivate selected contacts', confirmLabel: 'Deactivate' })) return;
                try {
                    await window.api.post('/api/important-contacts/deactivate', { ids: [...this.selected] });
                    this.contacts = this.contacts.filter((c) => !this.selected.has(c.id));
                    this.selected.clear();
                    Alpine.store('toast').success('Contacts deactivated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.contactsAdmin = contactsAdmin;
