function atollManagement(props = {}) {
        return {
            atolls: props.atolls || [],
            coordinators: props.coordinators || [],
            editingAtoll: null,
            formData: { name: '', status: 'active', coordinator_id: '' },
            dialogOpen: false,
            coordinatorName(id) {
                const c = this.coordinators.find((c) => c.id === id);
                return c ? c.first_name : 'Assigned';
            },
            openCreate() {
                this.editingAtoll = null;
                this.formData = { name: '', status: 'active', coordinator_id: '' };
                this.dialogOpen = true;
            },
            openEdit(a) {
                this.editingAtoll = a;
                this.formData = { name: a.name, status: a.status, coordinator_id: a.coordinator_id || '' };
                this.dialogOpen = true;
            },
            async submit() {
                try {
                    if (this.editingAtoll) {
                        await window.api.patch(`/api/atolls/${this.editingAtoll.id}`, { ...this.formData, coordinator_id: this.formData.coordinator_id || null });
                        Alpine.store('toast').success('Atoll updated');
                    } else {
                        await window.api.post('/api/atolls', { ...this.formData, coordinator_id: this.formData.coordinator_id || null });
                        Alpine.store('toast').success('Atoll created');
                    }
                    this.dialogOpen = false;
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async remove(id) {
                if (!await window.confirmAction('Delete this atoll? This cannot be undone.', { title: 'Delete atoll', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/atolls/${id}`);
                    Alpine.store('toast').success('Atoll deleted');
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async openBulk() {
                const existing = new Set(this.atolls.map((a) => a.name));
                const available = ['Alifu Alifu', 'Alifu Dhaalu', 'Baa', 'Dhaalu', 'Faafu', 'Gaafu Alifu', 'Gaafu Dhaalu', 'Gnaviyani', 'Haa Alifu', 'Haa Dhaalu', 'Kaafu', 'Laamu', 'Lhaviyani', 'Meemu', 'Noonu', 'Raa', 'Seenu', 'Shaviyani', 'Thaa', 'Vaavu'].filter((n) => !existing.has(n));
                const result = await window.promptAction({
                    title: 'Quick import atolls',
                    description: `${available.length} standard atoll names are not yet in the directory. Review or edit the comma-separated list before importing.`,
                    submitLabel: 'Import atolls',
                    fields: [{ name: 'names', label: 'Atoll names', type: 'textarea', rows: 6, required: true, value: available.join(', '), help: 'Separate names with commas.' }],
                });
                if (!result?.names) return;
                window.api.post('/api/atolls/import', { names: result.names.split(',').map((s) => s.trim()).filter(Boolean) })
                    .then(() => { Alpine.store('toast').success('Atolls imported'); window.location.reload(); })
                    .catch((e) => Alpine.store('toast').error(e.message));
            },
        };
    }

function coordinatorManagement(props = {}) {
        return {
            managers: props.managers || [],
            allAtolls: props.allAtolls || [],
            searchQuery: '',
            atollFilter: 'all',
            viewing: null,
            editing: null,
            editForm: { atoll_ids: [] },
            counts: {
                coordinators: props.coordinatorsCount,
                supervisors: props.supervisorsCount,
            },
            filteredManagers() {
                const q = this.searchQuery.toLowerCase();
                return this.managers.filter((m) => {
                    if (this.atollFilter !== 'all' && !m.assigned_atolls.some((a) => a.id === this.atollFilter)) return false;
                    if (q) {
                        const hay = (m.full_name + ' ' + (m.designation || '') + ' ' + m.role + ' ' + m.assigned_atolls.map((a) => a.name).join(' ')).toLowerCase();
                        if (!hay.includes(q)) return false;
                    }
                    return true;
                });
            },
            openView(m) { this.viewing = m; },
            openEdit(m) {
                this.editing = m;
                this.editForm = { atoll_ids: m.assigned_atolls.map((a) => a.id) };
            },
            async saveEdit() {
                try {
                    await window.api.post('/api/coordinators/assignments', { user_id: this.editing.id, atoll_ids: this.editForm.atoll_ids });
                    Alpine.store('toast').success('Assignments updated');
                    this.editing = null;
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async confirmDeactivate(m) {
                if (!await window.confirmAction(`Deactivate ${m.full_name}? This will remove their role and unassign all atolls.`, { title: 'Deactivate coordinator', confirmLabel: 'Deactivate' })) return;
                try {
                    await window.api.post('/api/coordinators/deactivate', { user_id: m.id });
                    Alpine.store('toast').success('Manager deactivated');
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

function islandManagement(props = {}) {
        return {
            islands: props.islands || [],
            atolls: props.atolls || [],
            staff: props.staff || [],
            staffDirectory: props.staffDirectory || [],
            filterAtoll: 'all',
            editingIsland: null,
            formData: { name: '', atoll_id: '', assigned_staff_id: '', status: 'active' },
            dialogOpen: false,
            atollName(id) { const a = this.atolls.find((a) => a.id === id); return a ? a.name : '—'; },
            staffName(id) { if (!id) return ''; const s = this.staffDirectory.find((s) => s.id === id); return s ? `${s.first_name} ${s.last_name || ''}` : ''; },
            filteredIslands() {
                return this.filterAtoll === 'all' ? this.islands : this.islands.filter((i) => i.atoll_id === this.filterAtoll);
            },
            openCreate() {
                this.editingIsland = null;
                this.formData = { name: '', atoll_id: '', assigned_staff_id: '', status: 'active' };
                this.dialogOpen = true;
            },
            openEdit(i) {
                this.editingIsland = i;
                this.formData = { name: i.name, atoll_id: i.atoll_id, assigned_staff_id: i.assigned_staff_id || '', status: i.status };
                this.dialogOpen = true;
            },
            async submit() {
                try {
                    const payload = { ...this.formData, assigned_staff_id: this.formData.assigned_staff_id || null };
                    if (this.editingIsland) {
                        await window.api.patch(`/api/islands/${this.editingIsland.id}`, payload);
                        Alpine.store('toast').success('Island updated');
                    } else {
                        await window.api.post('/api/islands', payload);
                        Alpine.store('toast').success('Island created');
                    }
                    this.dialogOpen = false;
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async remove(id) {
                if (!await window.confirmAction('Delete this island? This cannot be undone.', { title: 'Delete island', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/islands/${id}`);
                    Alpine.store('toast').success('Island deleted');
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async viewProfile(i) {
                const openHospitals = await window.confirmAction(`Hospital profiles for ${i.name} (${this.atollName(i.atoll_id)}) are managed from the Hospitals directory.`, { title: 'Open hospital directory?', confirmLabel: 'Open hospitals', tone: 'primary' });
                if (openHospitals) window.location.href = '/hospitals';
            },
            async openBulk() {
                const result = await window.promptAction({
                    title: 'Bulk add islands',
                    description: 'Add multiple island records to one atoll in a single operation.',
                    submitLabel: 'Add islands',
                    fields: [
                        { name: 'atoll_name', label: 'Atoll name', required: true, placeholder: 'Enter the exact atoll name' },
                        { name: 'names', label: 'Island names', type: 'textarea', rows: 7, required: true, placeholder: 'One island per line', help: 'Enter one island name on each line.' },
                    ],
                });
                if (!result) return;
                window.api.post('/api/islands/bulk', { atoll_name: result.atoll_name.trim(), names: result.names.split('\n').map((s) => s.trim()).filter(Boolean) })
                    .then(() => { Alpine.store('toast').success('Islands imported'); window.location.reload(); })
                    .catch((e) => Alpine.store('toast').error(e.message));
            },
        };
    }

function userManagement(props = {}) {
        return {
            profiles: props.profiles || [],
            userRoles: props.userRoles || [],
            userDepartments: props.userDepartments || [],
            currentUserId: props.currentUserId,
            editingProfile: null,
            formData: {},
            dialogOpen: false,
            getRole(id) { return this.userRoles[id] || 'staff'; },
            deptName(id) { const d = this.userDepartments.find((d) => d.id === id); return d ? d.name : '—'; },
            openCreate() {
                this.editingProfile = null;
                this.formData = { first_name: '', last_name: '', email: '', password: '', contact_no: '', user_department_id: '', status: 'active' };
                this.dialogOpen = true;
            },
            openEdit(p) {
                this.editingProfile = p;
                this.formData = { first_name: p.first_name, last_name: p.last_name || '', email: p.email, contact_no: p.contact_no || '', user_department_id: p.user_department_id || '', status: p.status };
                this.dialogOpen = true;
            },
            async submit() {
                try {
                    if (this.editingProfile) {
                        await window.api.patch(`/api/users/${this.editingProfile.id}`, this.formData);
                        Alpine.store('toast').success('User updated');
                    } else {
                        await window.api.post('/api/users', this.formData);
                        Alpine.store('toast').success('User created');
                    }
                    this.dialogOpen = false;
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async updateRole(p, role) {
                try {
                    await window.api.post(`/api/users/${p.id}/role`, { role });
                    this.userRoles[p.id] = role;
                    Alpine.store('toast').success('Role updated');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async remove(id) {
                if (!await window.confirmAction('Delete this user? This action cannot be undone.', { title: 'Delete user', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/users/${id}`);
                    Alpine.store('toast').success('User deleted');
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

function departmentManagement(endpoint, props = {}) {
        return {
            endpoint,
            itemLabel: endpoint === '/api/departments' ? 'Task Type' : 'Department',
            items: endpoint === '/api/departments' ? props.departments : props.userDepartments,
            editing: null,
            form: { name: '', description: '', color: '#3b82f6', status: 'active' },
            dialogOpen: false,
            colors: ['#3b82f6', '#ef4444', '#f97316', '#f59e0b', '#10b981', '#14b8a6', '#06b6d4', '#8b5cf6', '#ec4899', '#64748b'],
            openCreate() {
                this.editing = null;
                this.form = { name: '', description: '', color: '#3b82f6', status: 'active' };
                this.dialogOpen = true;
            },
            openEdit(d) {
                this.editing = d;
                this.form = { name: d.name, description: d.description || '', color: d.color || '#3b82f6', status: d.status };
                this.dialogOpen = true;
            },
            async submit() {
                try {
                    if (this.editing) {
                        await window.api.patch(`${endpoint}/${this.editing.id}`, this.form);
                        Alpine.store('toast').success('Updated');
                    } else {
                        await window.api.post(endpoint, this.form);
                        Alpine.store('toast').success('Created');
                    }
                    this.dialogOpen = false;
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
            async remove(id) {
                if (!await window.confirmAction('Delete this item? This action cannot be undone.', { title: 'Delete item', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`${endpoint}/${id}`);
                    Alpine.store('toast').success('Deleted');
                    window.location.reload();
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }
window.atollManagement = atollManagement;
window.coordinatorManagement = coordinatorManagement;
window.islandManagement = islandManagement;
window.userManagement = userManagement;
window.departmentManagement = departmentManagement;
