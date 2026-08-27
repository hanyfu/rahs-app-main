function coordinatorsPage(props = {}) {
        return {
            managers: props.managers || [],
            atolls: props.atolls || [],
            searchQuery: '',
            atollFilter: 'all',
            isEditDialogOpen: false,
            isViewDialogOpen: false,
            deleteConfirmId: null,
            editingManager: null,
            viewingManager: null,
            editForm: { atoll_ids: [] },
            saving: false,

            get counts() {
                return {
                    coordinators: this.managers.filter((m) => m.role === 'coordinator').length,
                    supervisors: this.managers.filter((m) => m.role === 'supervisor').length,
                };
            },

            roleLabel(role) {
                return role === 'coordinator' ? 'Coordinator' : 'Supervisor';
            },

            filteredManagers() {
                const q = this.searchQuery.toLowerCase();
                return this.managers.filter((m) => {
                    if (this.atollFilter !== 'all' && !m.assigned_atolls.some((a) => a.id === this.atollFilter)) return false;
                    if (!q) return true;
                    const hay = (m.first_name + ' ' + m.last_name + ' ' + (m.designation || '') + ' ' + m.role + ' ' + m.assigned_atolls.map((a) => a.name).join(' ')).toLowerCase();
                    return hay.includes(q);
                });
            },

            openView(m) {
                this.viewingManager = m;
                this.isViewDialogOpen = true;
            },

            openEdit(m) {
                this.editingManager = m;
                this.editForm = { atoll_ids: m.assigned_atolls.map((a) => a.id) };
                this.isEditDialogOpen = true;
            },

            toggleAtoll(id) {
                const idx = this.editForm.atoll_ids.indexOf(id);
                if (idx >= 0) this.editForm.atoll_ids.splice(idx, 1);
                else this.editForm.atoll_ids.push(id);
            },

            async saveAssignments() {
                if (!this.editingManager) return;
                this.saving = true;
                try {
                    await window.api.post('/api/coordinators/assignments', {
                        profileId: this.editingManager.id,
                        role: this.editingManager.role,
                        atollIds: this.editForm.atoll_ids,
                    });
                    const m = this.managers.find((x) => x.id === this.editingManager.id);
                    if (m) m.assigned_atolls = this.atolls.filter((a) => this.editForm.atoll_ids.includes(a.id));
                    this.isEditDialogOpen = false;
                    Alpine.store('toast').success('Assignments updated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                } finally {
                    this.saving = false;
                }
            },

            confirmDeactivate(m) {
                this.deleteConfirmId = m.id;
            },

            async handleDeactivate() {
                try {
                    await window.api.post('/api/coordinators/deactivate', { profileId: this.deleteConfirmId });
                    this.managers = this.managers.filter((m) => m.id !== this.deleteConfirmId);
                    this.deleteConfirmId = null;
                    Alpine.store('toast').success('Manager deactivated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.coordinatorsPage = coordinatorsPage;
