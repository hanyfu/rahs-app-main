function leavesPage(props = {}) {
        return {
            leaves: props.leaves || [],
            profiles: props.profiles || [],
            setup: props.setup || null,
            role: props.role || "",

            showForm: false,
            editing: false,
            form: {},
            editingLeave: null,
            showSetup: false,
            setupForm: {},

            init() {},

            get coordinators() {
                return this.profiles.filter((p) => p.role === 'coordinator');
            },
            get supervisors() {
                return this.profiles.filter((p) => p.role === 'supervisor');
            },

            filteredLeaves() {
                return this.leaves;
            },

            statusClasses(status) {
                const map = {
                    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                    pending_review: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
                    cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                };
                return map[status] || map.submitted;
            },

            criticalClasses(level) {
                const map = {
                    low: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                    medium: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200',
                    high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
                    critical: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
                };
                return map[level] || map.low;
            },

            urgencyClasses(urgency) {
                const map = {
                    normal: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                    urgent: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
                    emergency: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
                };
                return map[urgency] || map.normal;
            },

            shortageAlerts() {
                const alerts = [];
                for (const s of this.setup) {
                    if (s.status !== 'active') continue;
                    const overlapping = this.leaves.filter((l) =>
                        l.approval_status === 'approved' &&
                        l.department_unit === s.department_unit &&
                        l.staff_category === s.staff_category
                    );
                    if (overlapping.length > 0) {
                        const effective = s.total_active_staff - overlapping.length;
                        if (effective < s.required_minimum_staff) {
                            alerts.push({
                                key: s.id,
                                title: `Shortage risk: ${s.department_unit} (${s.staff_category}, ${s.shift})`,
                                message: `${effective} available but ${s.required_minimum_staff} required. ${overlapping.length} staff on approved leave.`,
                            });
                        }
                    }
                }
                return alerts;
            },

            emptyForm() {
                return {
                    staff_name: '',
                    staff_id: '',
                    staff_category: 'nurse',
                    department_unit: '',
                    assigned_coordinator: '',
                    direct_supervisor: '',
                    leave_type: 'annual',
                    leave_start_date: '',
                    leave_end_date: '',
                    shift_affected: '',
                    reason_for_leave: '',
                    contact_during_leave: '',
                    replacement_staff: '',
                    handover_notes: '',
                    critical_level: 'low',
                    urgency: 'normal',
                    approval_status: 'submitted',
                    remarks: '',
                };
            },

            openCreate() {
                this.editing = false;
                this.form = this.emptyForm();
                this.showForm = true;
            },

            openEdit(l) {
                this.editing = true;
                this.editingLeave = l;
                this.form = {
                    staff_name: l.staff_name,
                    staff_id: l.staff_id,
                    staff_category: l.staff_category,
                    department_unit: l.department_unit,
                    assigned_coordinator: l.assigned_coordinator || '',
                    direct_supervisor: l.direct_supervisor || '',
                    leave_type: l.leave_type,
                    leave_start_date: l.leave_start_date,
                    leave_end_date: l.leave_end_date,
                    shift_affected: l.shift_affected || '',
                    reason_for_leave: l.reason_for_leave || '',
                    contact_during_leave: l.contact_during_leave || '',
                    replacement_staff: l.replacement_staff || '',
                    handover_notes: l.handover_notes || '',
                    critical_level: l.critical_level,
                    urgency: l.urgency,
                    approval_status: l.approval_status,
                    remarks: l.remarks || '',
                };
                this.showForm = true;
            },

            async save() {
                try {
                    if (this.editing) {
                        const updated = await window.api.patch(`/api/leaves/${this.editingLeave.id}`, this.form);
                        const idx = this.leaves.findIndex((l) => l.id === updated.id);
                        if (idx >= 0) this.leaves[idx] = updated;
                        Alpine.store('toast').success('Leave record updated');
                    } else {
                        const created = await window.api.post('/api/leaves', this.form);
                        this.leaves.unshift(created);
                        Alpine.store('toast').success('Leave record created');
                    }
                    this.showForm = false;
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async setStatus(l, status) {
                try {
                    const updated = await window.api.patch(`/api/leaves/${l.id}`, { approval_status: status });
                    const idx = this.leaves.findIndex((x) => x.id === updated.id);
                    if (idx >= 0) this.leaves[idx] = updated;
                    Alpine.store('toast').success(`Leave ${status}`);
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async removeLeave(l) {
                if (!await window.confirmAction(`Delete leave record for ${l.staff_name}?`, { title: 'Delete leave record', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/leaves/${l.id}`);
                    this.leaves = this.leaves.filter((x) => x.id !== l.id);
                    Alpine.store('toast').success('Leave record deleted');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async loadSetup() {
                try {
                    this.setup = await window.api.get('/api/availability-setup');
                } catch (e) {
                    /* silent */
                }
            },

            async saveSetup() {
                try {
                    const created = await window.api.post('/api/availability-setup', this.setupForm);
                    this.setup.push(created);
                    this.setupForm = {};
                    Alpine.store('toast').success('Setup row added');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async toggleSetup(s) {
                try {
                    const updated = await window.api.patch(`/api/availability-setup/${s.id}`, {
                        status: s.status === 'active' ? 'inactive' : 'active',
                    });
                    Object.assign(s, updated);
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async removeSetup(s) {
                if (!await window.confirmAction('Delete this availability setup row?', { title: 'Delete setup row', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/availability-setup/${s.id}`);
                    this.setup = this.setup.filter((x) => x.id !== s.id);
                    Alpine.store('toast').success('Setup row deleted');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.leavesPage = leavesPage;
