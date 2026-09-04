function leavesPage(props = {}) {
        return {
            leaves: props.leaves || [],
            profiles: props.profiles || [],
            setup: props.setup || null,
            hospitals: props.hospitals || [],
            staffCategories: props.staffCategories || [],
            staffCategoryFields: props.staffCategoryFields || {},
            role: props.role || "",

            showForm: false,
            editing: false,
            form: {},
            editingLeave: null,
            showSetup: false,
            setupForm: {},
            calendarOpen: '',
            calendarCursor: '',
            saving: false,
            formError: '',

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

            labelize(value) {
                const text = String(value || '').replaceAll('_', ' ');
                return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
            },

            formatDate(value) {
                if (!value) return 'Date not set';
                const datePart = String(value).slice(0, 10);
                const [year, month, day] = datePart.split('-').map(Number);
                if (!year || !month || !day) return 'Date not set';
                return new Date(year, month - 1, day).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            leavePeriod(leave) {
                const start = this.formatDate(leave.leave_start_date);
                const end = this.formatDate(leave.leave_end_date);
                return start === end ? start : `${start} – ${end}`;
            },

            personName(person) {
                return [person?.first_name, person?.last_name].filter(Boolean).join(' ') || 'Not assigned';
            },

            hospitalLabel(hospital) {
                return hospital?.hospital_contact?.hospital_name || hospital?.island?.name || 'Hospital';
            },

            hospitalName(leave) {
                return leave?.hospital_profile?.hospital_contact?.hospital_name || leave?.department_unit || 'Hospital not linked';
            },

            categoryOptionLabel(category, hospitalId) {
                const hospital = this.hospitals.find((item) => item.id === hospitalId);
                const field = this.staffCategoryFields[category];
                if (!hospital || !field) return category;
                const count = Number(hospital[field] || 0);
                return `${category} — ${count} staff`;
            },

            leaveDaysLabel(days) {
                const count = Number(days) || 0;
                return `${count} ${count === 1 ? 'day' : 'days'}`;
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
                        ['submitted', 'pending_review', 'approved'].includes(l.approval_status) &&
                        (!s.hospital_profile_id || l.hospital_profile_id === s.hospital_profile_id) &&
                        l.department_unit === s.department_unit &&
                        l.staff_category === s.staff_category &&
                        (s.shift === 'All shifts' || l.shift_affected === s.shift)
                    );
                    if (overlapping.length > 0) {
                        const effective = s.total_active_staff - overlapping.length;
                        if (effective < s.required_minimum_staff) {
                            alerts.push({
                                key: s.id,
                                title: `Shortage risk: ${s.department_unit} (${s.staff_category}, ${s.shift})`,
                                message: `${effective} available but ${s.required_minimum_staff} required. ${overlapping.length} active leave record(s).`,
                            });
                        }
                    }
                }
                return alerts;
            },

            emptyForm() {
                return {
                    staff_name: '',
                    hospital_profile_id: this.hospitals[0]?.id || '',
                    staff_category: this.staffCategories[0] || '',
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

            openCalendar(field) {
                if (this.calendarOpen === field) {
                    this.calendarOpen = '';
                    return;
                }
                const selected = this.form[field];
                const base = selected ? new Date(`${selected}T00:00:00`) : new Date();
                this.calendarCursor = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;
                this.calendarOpen = field;
            },

            calendarBaseDate() {
                const [year, month] = (this.calendarCursor || '').split('-').map(Number);
                return year && month ? new Date(year, month - 1, 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            },

            calendarMonthLabel() {
                return this.calendarBaseDate().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            },

            calendarDateLabel(field) {
                if (!this.form[field]) return 'Choose a date';
                return new Date(`${this.form[field]}T00:00:00`).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
            },

            calendarDays(field) {
                const base = this.calendarBaseDate();
                const gridStart = new Date(base.getFullYear(), base.getMonth(), 1 - base.getDay());
                const today = new Date();
                const formatValue = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
                return Array.from({ length: 42 }, (_, index) => {
                    const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + index);
                    const value = formatValue(date);
                    return {
                        value,
                        day: date.getDate(),
                        currentMonth: date.getMonth() === base.getMonth(),
                        today: date.toDateString() === today.toDateString(),
                        selected: value === this.form[field],
                        label: date.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
                    };
                });
            },

            changeCalendarMonth(offset) {
                const base = this.calendarBaseDate();
                const next = new Date(base.getFullYear(), base.getMonth() + offset, 1);
                this.calendarCursor = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`;
            },

            goCalendarToday() {
                const today = new Date();
                this.calendarCursor = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
            },

            selectCalendarDate(field, value) {
                this.form[field] = value;
                this.calendarOpen = '';
            },

            clearCalendarDate(field) {
                this.form[field] = '';
                this.calendarOpen = '';
            },

            openCreate() {
                this.editing = false;
                this.form = this.emptyForm();
                this.calendarOpen = '';
                this.formError = '';
                this.showForm = true;
            },

            openEdit(l) {
                this.editing = true;
                this.editingLeave = l;
                this.form = {
                    staff_name: l.staff_name,
                    hospital_profile_id: l.hospital_profile_id || '',
                    staff_category: l.staff_category,
                    assigned_coordinator: l.assigned_coordinator || '',
                    direct_supervisor: l.direct_supervisor || '',
                    leave_type: l.leave_type,
                    leave_start_date: String(l.leave_start_date || '').slice(0, 10),
                    leave_end_date: String(l.leave_end_date || '').slice(0, 10),
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
                this.calendarOpen = '';
                this.formError = '';
                this.showForm = true;
            },

            async save() {
                if (this.saving) return;
                this.formError = '';
                if (!this.form.hospital_profile_id || !this.form.staff_name?.trim() || !this.form.staff_category || !this.form.leave_start_date || !this.form.leave_end_date) {
                    this.formError = 'Complete the hospital, staff name, staff category, start date and end date fields.';
                    return;
                }
                if (this.form.leave_end_date < this.form.leave_start_date) {
                    this.formError = 'The end date must be the same as or later than the start date.';
                    return;
                }
                this.saving = true;
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
                    this.formError = e.message || 'The leave record could not be saved. Check the fields and try again.';
                    Alpine.store('toast').error(this.formError);
                } finally {
                    this.saving = false;
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
