function permissionsPage(props = {}) {
        return {
            permissions: props.permissions || [],
            showForm: false,
            editing: false,
            form: {},
            editingId: null,

            init() {},

            async toggleAccess(id, field, enabled) {
                const permission = this.permissions.find((item) => item.id === id);
                if (!permission || field === 'admin_access') return;
                const previous = !!permission[field];
                permission[field] = enabled;
                try {
                    const updated = await window.api.patch(`/api/role-permissions/${id}`, { [field]: enabled });
                    Object.assign(permission, updated);
                    Alpine.store('toast').success('Role access updated');
                } catch (e) {
                    permission[field] = previous;
                    Alpine.store('toast').error(e.message);
                }
            },

            openCreate() {
                this.editing = false;
                this.form = { permission_name: '', category: '', permission_description: '', supervisor_access: false, coordinator_access: false, staff_access: false };
                this.showForm = true;
            },

            openEdit(id) {
                const p = this.permissions.find((x) => x.id === id);
                if (!p) return;
                this.editing = true;
                this.editingId = id;
                this.form = {
                    permission_name: p.permission_name,
                    category: p.category,
                    permission_description: p.permission_description || '',
                    supervisor_access: !!p.supervisor_access,
                    coordinator_access: !!p.coordinator_access,
                    staff_access: !!p.staff_access,
                };
                this.showForm = true;
            },

            async save() {
                try {
                    if (this.editing) {
                        const updated = await window.api.patch(`/api/role-permissions/${this.editingId}`, this.form);
                        const idx = this.permissions.findIndex((x) => x.id === updated.id);
                        if (idx >= 0) this.permissions[idx] = updated;
                        Alpine.store('toast').success('Permission updated');
                    } else {
                        const created = await window.api.post('/api/role-permissions', this.form);
                        this.permissions.push(created);
                        Alpine.store('toast').success('Permission added');
                    }
                    this.showForm = false;
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async remove(id) {
                if (!await window.confirmAction('Delete this permission? This may affect what users can access.', { title: 'Delete permission', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/role-permissions/${id}`);
                    this.permissions = this.permissions.filter((x) => x.id !== id);
                    Alpine.store('toast').success('Permission deleted');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.permissionsPage = permissionsPage;
