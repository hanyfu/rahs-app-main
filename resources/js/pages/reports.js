function reportsPage(props = {}) {
        return {
            tasks: props.tasks || [],
            scheduled: props.scheduled || [],
            atolls: props.atolls || [],
            islands: props.islands || [],
            departments: props.departments || [],
            profiles: props.profiles || [],
            reportTypes: [
                { value: 'tasks', label: 'Detailed task report', description: 'Every matching task with assignment, location, priority and due date.' },
                { value: 'overdue', label: 'Overdue task report', description: 'Outstanding tasks whose due dates have passed.' },
                { value: 'completed', label: 'Completed task report', description: 'Completed work for auditing and performance review.' },
                { value: 'workload', label: 'Staff workload summary', description: 'Task volume, completion and overdue totals grouped by staff member.' },
                { value: 'atoll-performance', label: 'Atoll performance summary', description: 'Task outcomes and completion rate grouped by atoll.' },
                { value: 'department-performance', label: 'Department performance summary', description: 'Task outcomes and completion rate grouped by department.' },
            ],
            reportType: 'tasks',
            reportFilters: { date_from: '', date_to: '', atoll_id: '', island_id: '', department_id: '', assigned_to: '', status: '', priority: '' },
            stats: {
                total: 0, pending: 0, inProgress: 0, completed: 0, efficiency: 0,
            },
            showSchedule: false,
            scheduleForm: { name: '', report_type: 'tasks', frequency: 'weekly', day_of_week: 1, day_of_month: 1, time_of_day: '08:00' },
            recipientsText: '',

            init() {
                const tasks = this.tasks;
                this.stats.total = tasks.length;
                this.stats.pending = tasks.filter((t) => t.status === 'pending').length;
                this.stats.inProgress = tasks.filter((t) => t.status === 'in_progress').length;
                this.stats.completed = tasks.filter((t) => t.status === 'completed').length;
                this.stats.efficiency = this.stats.total ? Math.round((this.stats.completed / this.stats.total) * 100) : 0;
            },

            statusChart() {
                const colors = {
                    pending: 'hsl(38 92% 50%)',
                    in_progress: 'hsl(217 91% 60%)',
                    completed: 'hsl(160 84% 39%)',
                    cancelled: 'hsl(215 16% 47%)',
                };
                const max = Math.max(this.stats.total, 1);
                return [
                    { label: 'Pending', value: this.stats.pending, height: (this.stats.pending / max) * 180, color: colors.pending },
                    { label: 'In progress', value: this.stats.inProgress, height: (this.stats.inProgress / max) * 180, color: colors.in_progress },
                    { label: 'Completed', value: this.stats.completed, height: (this.stats.completed / max) * 180, color: colors.completed },
                ];
            },

            recentTasks() {
                return this.tasks.slice(0, 15);
            },

            statusClasses(status) {
                const map = {
                    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                    cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                };
                return map[status] || map.pending;
            },

            download(url) {
                window.location.href = url;
            },

            selectedReportDescription() {
                return this.reportTypes.find((option) => option.value === this.reportType)?.description || '';
            },

            filteredIslands() {
                return this.reportFilters.atoll_id ? this.islands.filter((island) => island.atoll_id === this.reportFilters.atoll_id) : this.islands;
            },

            activeFilterCount() {
                return Object.values(this.reportFilters).filter(Boolean).length;
            },

            clearReportFilters() {
                Object.keys(this.reportFilters).forEach((key) => { this.reportFilters[key] = ''; });
            },

            generateReport() {
                const params = new URLSearchParams(Object.entries(this.reportFilters).filter(([, value]) => value));
                window.location.href = `/api/reports/generate/${this.reportType}?${params.toString()}`;
            },

            scheduleCurrentReport() {
                const label = this.reportTypes.find((option) => option.value === this.reportType)?.label || 'Task report';
                this.scheduleForm.report_type = this.reportType;
                this.scheduleForm.name = label;
                this.showSchedule = true;
                this.recipientsText = '';
            },

            scheduleLabel(r) {
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                let freq = r.frequency;
                if (r.frequency === 'weekly') freq += ` (${days[r.day_of_week]})`;
                if (r.frequency === 'monthly') freq += ` (day ${r.day_of_month})`;
                const type = this.reportTypes.find((option) => option.value === (r.filters?.report_type || 'tasks'))?.label;
                return `${type || 'Task report'} · ${freq} at ${r.time_of_day}`;
            },

            onFrequencyChange() {
                this.scheduleForm.day_of_week = this.scheduleForm.day_of_week || 1;
                this.scheduleForm.day_of_month = this.scheduleForm.day_of_month || 1;
            },

            openSchedule() {
                this.showSchedule = true;
                this.recipientsText = '';
            },

            async createSchedule() {
                try {
                    const recipients = this.recipientsText.split(',').map((e) => e.trim()).filter(Boolean);
                    const report = await window.api.post('/api/scheduled-reports', {
                        ...this.scheduleForm,
                        recipients,
                        filters: { ...this.reportFilters, report_type: this.scheduleForm.report_type },
                        day_of_week: this.scheduleForm.frequency === 'weekly' ? Number(this.scheduleForm.day_of_week) : null,
                        day_of_month: this.scheduleForm.frequency === 'monthly' ? Number(this.scheduleForm.day_of_month) : null,
                    });
                    this.scheduled.unshift(report);
                    this.showSchedule = false;
                    Alpine.store('toast').success('Report scheduled');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async toggleSchedule(r) {
                try {
                    const updated = await window.api.patch(`/api/scheduled-reports/${r.id}`, { is_active: !r.is_active });
                    Object.assign(r, updated);
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },

            async removeSchedule(r) {
                if (!await window.confirmAction(`Delete scheduled report "${r.name}"?`, { title: 'Delete scheduled report', confirmLabel: 'Delete' })) return;
                try {
                    await window.api.del(`/api/scheduled-reports/${r.id}`);
                    this.scheduled = this.scheduled.filter((x) => x.id !== r.id);
                    Alpine.store('toast').success('Scheduled report deleted');
                } catch (e) {
                    Alpine.store('toast').error(e.message);
                }
            },
        };
    }

window.reportsPage = reportsPage;
