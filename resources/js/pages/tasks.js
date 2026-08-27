function statusConfigOf(status) {
    const map = {
        pending: { label: 'Pending', bg: 'bg-amber-500/10', color: 'text-amber-600', border: 'border-amber-500/20' },
        in_progress: { label: 'In Progress', bg: 'bg-blue-500/10', color: 'text-blue-600', border: 'border-blue-500/20' },
        completed: { label: 'Completed', bg: 'bg-emerald-500/10', color: 'text-emerald-600', border: 'border-emerald-500/20' },
        cancelled: { label: 'Cancelled', bg: 'bg-rose-500/10', color: 'text-rose-600', border: 'border-rose-500/20' },
    };
    return map[status] || map.pending;
}

function priorityConfigOf(priority) {
    const map = {
        urgent: { bg: 'bg-rose-500/10', color: 'text-rose-600', border: 'border-rose-500/20' },
        high: { bg: 'bg-orange-500/10', color: 'text-orange-600', border: 'border-orange-500/20' },
        medium: { bg: 'bg-blue-500/10', color: 'text-blue-600', border: 'border-blue-500/20' },
        low: { bg: 'bg-slate-500/10', color: 'text-slate-600', border: 'border-slate-500/20' },
    };
    return map[priority] || map.low;
}

function parseDue(dueDate) {
    if (!dueDate) return null;
    if (/^\d{4}-\d{2}-\d{2}$/.test(dueDate)) {
        return new Date(dueDate + 'T00:00:00');
    }
    return new Date(dueDate);
}

function isOverdueStatus(dueDate, status) {
    if (!dueDate || status === 'completed' || status === 'cancelled') return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return parseDue(dueDate) < today;
}

function isDueSoonStatus(dueDate, status) {
    if (!dueDate || status === 'completed' || status === 'cancelled') return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const due = parseDue(dueDate);
    if (due < today) return false;
    const horizon = new Date(today);
    horizon.setDate(horizon.getDate() + 2);
    return due <= horizon;
}

function formatDueDate(date) {
    if (!date) return '—';
    const d = parseDue(date);
    if (isNaN(d.getTime())) return String(date);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function timeAgo(value) {
    if (!value) return '';
    const then = new Date(value).getTime();
    if (isNaN(then)) return '';
    const seconds = Math.floor((Date.now() - then) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return minutes + ' minute' + (minutes === 1 ? '' : 's') + ' ago';
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return hours + ' hour' + (hours === 1 ? '' : 's') + ' ago';
    const days = Math.floor(hours / 24);
    if (days < 7) return days + ' day' + (days === 1 ? '' : 's') + ' ago';
    return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function initialsOf(profile) {
    if (!profile) return '?';
    const first = (profile.first_name || '').charAt(0);
    const last = (profile.last_name || '').charAt(0);
    return (first + last).toUpperCase() || '?';
}

function fileName(url) {
    if (!url) return '';
    const parts = String(url).split('/');
    return decodeURIComponent(parts[parts.length - 1] || '');
}

function fileNameOf(url) {
    return fileName(url);
}

function formatDateTimeRaw(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value);
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function toDatetimeLocal(value) {
    if (!value) return '';
    const d = new Date(value);
    if (isNaN(d.getTime())) return String(value).slice(0, 16);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function timeAgoOf(value) {
    return timeAgo(value);
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function taskCard(task, opts = {}) {
    return {
        task,
        profiles: opts.profiles || [],
        assignableProfiles: opts.assignableProfiles || [],
        islands: opts.islands || [],
        atolls: opts.atolls || [],
        departments: opts.departments || [],
        userRole: opts.userRole || '',
        currentUserId: opts.currentUserId || '',

        callLogsExpanded: false,
        callLogsCount: 0,
        commentsExpanded: false,
        statusDialogOpen: false,
        newStatus: task.status || 'pending',
        completionDescription: task.completion_description || '',
        reassignDialogOpen: false,
        newAssignee: task.assigned_to || '',
        editDialogOpen: false,
        editForm: { title: '', task_types: [] },
        deleteDialogOpen: false,

        get isOverdue() {
            return isOverdueStatus(this.task.due_date, this.task.status);
        },
        get isDueSoon() {
            return isDueSoonStatus(this.task.due_date, this.task.status);
        },
        get statusConfig() {
            return statusConfigOf(this.task.status);
        },
        get priorityConfig() {
            return priorityConfigOf(this.task.priority);
        },
        get taskType() {
            if (this.task.task_types && this.task.task_types.length > 0) return null;
            return this.departments.find((d) => d.id === this.task.department_id) || null;
        },
        get locationInfo() {
            const island = this.task.island || this.islands.find((i) => i.id === this.task.island_id) || null;
            if (!island) return null;
            const atoll = island.atoll || this.atolls.find((a) => a.id === island.atoll_id) || null;
            return { island, atoll };
        },
        get createdByProfile() {
            return this.profiles.find((p) => p.id === this.task.assigned_by) || null;
        },
        get assignedToProfile() {
            return this.profiles.find((p) => p.id === this.task.assigned_to) || null;
        },
        get canEdit() {
            return this.userRole !== 'staff';
        },
        get canDelete() {
            return this.userRole === 'admin';
        },
        get reassignOptions() {
            return this.assignableProfiles.length ? this.assignableProfiles : this.profiles;
        },

        initials(profile) {
            return initialsOf(profile);
        },
        formatDate(date) {
            return formatDueDate(date);
        },

        async toggleCallLogs() {
            this.callLogsExpanded = !this.callLogsExpanded;
            if (this.callLogsExpanded) {
                try {
                    const logs = await window.api.get(`/api/tasks/${this.task.id}/call-logs`);
                    this.callLogsCount = Array.isArray(logs) ? logs.length : (logs.meta?.total || 0);
                } catch (e) {
                    /* silent */
                }
            }
        },

        openStatusDialog() {
            this.newStatus = this.task.status || 'pending';
            this.completionDescription = this.task.completion_description || '';
            this.statusDialogOpen = true;
        },
        async confirmStatusUpdate() {
            const payload = { status: this.newStatus };
            if (this.newStatus === 'completed') {
                payload.completion_description = this.completionDescription;
            }
            try {
                await window.api.patch(`/api/tasks/${this.task.id}`, payload);
                Alpine.store('toast').success('Status updated');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        openReassign() {
            this.newAssignee = this.task.assigned_to || '';
            this.reassignDialogOpen = true;
        },
        closeReassign() {
            this.reassignDialogOpen = false;
        },
        async confirmReassign() {
            if (!this.newAssignee || this.newAssignee === this.task.assigned_to) return;
            try {
                await window.api.patch(`/api/tasks/${this.task.id}`, { assigned_to: this.newAssignee });
                Alpine.store('toast').success('Task reassigned');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        openEditDialog() {
            this.editForm = {
                title: this.task.title || '',
                creator_description: this.task.creator_description || '',
                priority: this.task.priority || 'medium',
                task_types: Array.isArray(this.task.task_types) ? this.task.task_types.slice() : [],
            };
            this.editDialogOpen = true;
        },
        toggleTaskType(name) {
            if (!Array.isArray(this.editForm.task_types)) {
                this.editForm.task_types = [];
            }
            const idx = this.editForm.task_types.indexOf(name);
            if (idx >= 0) {
                this.editForm.task_types.splice(idx, 1);
            } else {
                this.editForm.task_types.push(name);
            }
        },
        async confirmEdit() {
            if (!this.editForm.title.trim()) return;
            try {
                await window.api.patch(`/api/tasks/${this.task.id}`, {
                    title: this.editForm.title,
                    creator_description: this.editForm.creator_description,
                    priority: this.editForm.priority,
                    task_types: this.editForm.task_types,
                });
                Alpine.store('toast').success('Task updated');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        async confirmDelete() {
            try {
                await window.api.del(`/api/tasks/${this.task.id}`);
                Alpine.store('toast').success('Task deleted');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        async archive() {
            try {
                await window.api.patch(`/api/tasks/${this.task.id}`, { archived: true });
                Alpine.store('toast').success('Task archived');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },
    };
}
function callLogManager(taskId, taskTitle, opts = {}) {
    return {
        taskId,
        taskTitle,
        assignedTo: opts.assignedTo || null,
        assignedBy: opts.assignedBy || null,
        userRole: opts.userRole || '',
        currentUserId: opts.currentUserId || '',

        logs: [],
        loading: true,
        searchQuery: '',
        sortOrder: 'desc',
        showAdd: false,
        showEdit: false,
        showDelete: false,
        uploading: false,
        formData: {},
        editingLog: null,
        deletingLog: null,

        async init() {
            await this.fetchLogs();
        },
        async fetchLogs() {
            this.loading = true;
            try {
                const data = await window.api.get(`/api/tasks/${this.taskId}/call-logs`);
                this.logs = Array.isArray(data) ? data : (data.data || []);
            } catch (e) {
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },
        get filteredLogs() {
            let list = this.logs.slice();
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter((l) => ((l.contact_name || '') + ' ' + (l.contact_phone || '') + ' ' + (l.notes || '')).toLowerCase().includes(q));
            }
            list.sort((a, b) => {
                const ta = new Date(a.call_date || a.created_at || 0).getTime();
                const tb = new Date(b.call_date || b.created_at || 0).getTime();
                return this.sortOrder === 'desc' ? tb - ta : ta - tb;
            });
            return list;
        },
        get canAddLog() {
            if (this.userRole === 'admin' || this.userRole === 'supervisor') return true;
            if (this.userRole !== 'staff') return true;
            if (!this.assignedTo) return true;
            return this.assignedBy === this.currentUserId || this.assignedTo === this.currentUserId;
        },

        openAdd() {
            this.formData = { call_date: '', contact_name: '', contact_phone: '', notes: '', attachment_url: '' };
            this.showAdd = true;
        },
        closeAdd() {
            if (this.uploading) return;
            this.showAdd = false;
        },
        async submitAdd() {
            if (!this.formData.call_date || !this.formData.contact_name.trim()) return;
            this.uploading = true;
            try {
                await window.api.post(`/api/tasks/${this.taskId}/call-logs`, {
                    call_date: this.formData.call_date,
                    contact_name: this.formData.contact_name,
                    contact_phone: this.formData.contact_phone || '',
                    notes: this.formData.notes || '',
                    attachment_url: this.formData.attachment_url || '',
                });
                Alpine.store('toast').success('Task log added');
                this.showAdd = false;
                await this.fetchLogs();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.uploading = false;
            }
        },

        openEdit(log) {
            this.editingLog = log;
            this.formData = {
                call_date: toDatetimeLocal(log.call_date),
                contact_name: log.contact_name || '',
                contact_phone: log.contact_phone || '',
                notes: log.notes || '',
                attachment_url: log.attachment_url || '',
            };
            this.showEdit = true;
        },
        closeEdit() {
            if (this.uploading) return;
            this.showEdit = false;
        },
        async submitEdit() {
            if (!this.formData.call_date || !this.formData.contact_name.trim()) return;
            this.uploading = true;
            try {
                await window.api.patch(`/api/tasks/${this.taskId}/call-logs/${this.editingLog.id}`, {
                    call_date: this.formData.call_date,
                    contact_name: this.formData.contact_name,
                    contact_phone: this.formData.contact_phone || '',
                    notes: this.formData.notes || '',
                    attachment_url: this.formData.attachment_url || '',
                });
                Alpine.store('toast').success('Task log updated');
                this.showEdit = false;
                await this.fetchLogs();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.uploading = false;
            }
        },
        removeAttachment() {
            this.formData.attachment_url = '';
        },

        openDelete(log) {
            this.deletingLog = log;
            this.showDelete = true;
        },
        async confirmDelete() {
            if (!this.deletingLog) return;
            try {
                await window.api.del(`/api/tasks/${this.taskId}/call-logs/${this.deletingLog.id}`);
                Alpine.store('toast').success('Task log deleted');
                this.showDelete = false;
                this.deletingLog = null;
                await this.fetchLogs();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        async onFileChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.uploading = true;
            try {
                const base64 = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(String(reader.result).split(',')[1]);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
                const data = await window.api.post('/api/upload', { file: base64, filename: file.name });
                this.formData.attachment_url = data.url;
                Alpine.store('toast').success('File uploaded');
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },

        creatorOf(userId) {
            if (!userId) return null;
            for (const log of this.logs) {
                if (log.user && log.user.id === userId) return log.user;
            }
            return null;
        },
        initials(profile) {
            return initialsOf(profile);
        },
        formatDateTime(value) {
            return formatDateTimeRaw(value);
        },
        fileName(url) {
            return fileNameOf(url);
        },
    };
}

function commentsActivity(taskId, opts = {}) {
    return {
        taskId,
        profiles: opts.profiles || [],
        assignedTo: opts.assignedTo || null,
        assignedBy: opts.assignedBy || null,
        userRole: opts.userRole || '',
        currentUserId: opts.currentUserId || '',

        loading: true,
        tab: 'comments',
        comments: [],
        activities: [],
        newComment: '',
        submitting: false,

        async init() {
            try {
                const [comments, activities] = await Promise.all([
                    window.api.get(`/api/tasks/${this.taskId}/comments`),
                    window.api.get(`/api/tasks/${this.taskId}/activities`),
                ]);
                this.comments = Array.isArray(comments) ? comments : (comments.data || []);
                this.activities = Array.isArray(activities) ? activities : (activities.data || []);
            } catch (e) {
                this.comments = [];
                this.activities = [];
            } finally {
                this.loading = false;
            }
        },
        get canComment() {
            if (this.userRole === 'admin' || this.userRole === 'supervisor') return true;
            return this.assignedTo === this.currentUserId || this.assignedBy === this.currentUserId;
        },
        async submitComment() {
            const content = this.newComment.trim();
            if (!content || this.submitting) return;
            this.submitting = true;
            try {
                const comment = await window.api.post(`/api/tasks/${this.taskId}/comments`, { content });
                this.comments.push(comment);
                this.newComment = '';
                Alpine.store('toast').success('Comment posted');
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.submitting = false;
            }
        },
        async deleteComment(id) {
            try {
                await window.api.del(`/api/tasks/${this.taskId}/comments/${id}`);
                this.comments = this.comments.filter((c) => c.id !== id);
                Alpine.store('toast').success('Comment deleted');
            } catch (e) {
                Alpine.store('toast').error(e.message);
            }
        },

        profileOf(userId) {
            if (!userId) return null;
            if (typeof userId === 'object') return userId;
            return this.profiles.find((p) => p.id === userId) || null;
        },
        profileName(profile) {
            if (!profile) return 'Unknown';
            return (profile.first_name || '') + ' ' + (profile.last_name || '');
        },
        initials(profile) {
            return initialsOf(profile);
        },
        timeAgo(value) {
            return timeAgoOf(value);
        },
        activityMessage(activity) {
            const who = this.profileOf(activity.user_id);
            const name = escapeHtml(who ? this.profileName(who) : (activity.user ? this.profileName(activity.user) : 'Someone'));
            if (activity.action === 'created') {
                return `<b>${name}</b> created this task`;
            }
            if (activity.action === 'updated') {
                if (activity.field_name) {
                    const label = escapeHtml(String(activity.field_name).replace(/_/g, ' '));
                    const oldV = escapeHtml(String(activity.old_value ?? '—'));
                    const newV = escapeHtml(String(activity.new_value ?? '—'));
                    return `<b>${name}</b> updated ${label}: <span class="text-muted-foreground line-through">${oldV}</span> &rarr; <b>${newV}</b>`;
                }
                return `<b>${name}</b> updated this task`;
            }
            if (activity.action === 'commented') {
                return `<b>${name}</b> commented on this task`;
            }
            if (activity.action === 'status_changed') {
                const oldV = escapeHtml(String(activity.old_value ?? '—')).replace(/_/g, ' ');
                const newV = escapeHtml(String(activity.new_value ?? '—')).replace(/_/g, ' ');
                return `<b>${name}</b> changed status from <span class="text-muted-foreground line-through">${oldV}</span> to <b>${newV}</b>`;
            }
            return `<b>${name}</b> ${escapeHtml(String(activity.action || '')).replace(/_/g, ' ')} this task`;
        },
    };
}
function taskManager(props = {}) {
    return {
        tasks: props.tasks || [],
        profiles: props.profiles || [],
        assignableProfiles: props.assignableProfiles || [],
        islands: props.islands || [],
        atolls: props.atolls || [],
        departments: props.departments || [],
        archivedCounts: props.archivedCounts || { completed: 0, cancelled: 0 },
        nextCursor: props.nextCursor || null,
        loadingMore: false,
        userRole: props.userRole || '',
        currentUserId: props.currentUserId || '',

        filters: { search: '', atoll: '', island: '', status: '', user: '' },
        viewMode: 'list',
        createDialogOpen: false,
        createForm: { title: '', task_types: [] },
        priorityOpen: false,
        calendarOpen: false,
        calendarCursor: '',
        priorityOptions: [
            { value: 'low', label: 'Low', help: 'Flexible timeline', dot: 'bg-slate-400' },
            { value: 'medium', label: 'Medium', help: 'Standard attention', dot: 'bg-blue-500' },
            { value: 'high', label: 'High', help: 'Prompt action required', dot: 'bg-amber-500' },
            { value: 'urgent', label: 'Urgent', help: 'Immediate response', dot: 'bg-red-500' },
        ],
        uploading: false,
        archiveDialogOpen: false,
        expandedAtolls: {},
        expandedIslands: {},

        init() {
            this.viewMode = localStorage.getItem('taskViewMode') === 'grouped' ? 'grouped' : 'list';
            const params = new URLSearchParams(window.location.search);
            if (params.get('q')) this.filters.search = params.get('q');
            if (params.get('status')) this.filters.status = params.get('status');
            if (params.get('atoll')) this.filters.atoll = params.get('atoll');
            if (params.get('island')) this.filters.island = params.get('island');
            if (params.get('user')) this.filters.user = params.get('user');
            if (params.get('view') === 'grouped') this.viewMode = 'grouped';
            if (params.get('create') === 'true') this.openCreate();
        },

        syncUrl() {
            const params = new URLSearchParams();
            if (this.filters.search) params.set('q', this.filters.search);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.atoll) params.set('atoll', this.filters.atoll);
            if (this.filters.island) params.set('island', this.filters.island);
            if (this.filters.user) params.set('user', this.filters.user);
            if (this.viewMode === 'grouped') params.set('view', 'grouped');
            if (this.createDialogOpen) params.set('create', 'true');
            const qs = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (qs ? '?' + qs : ''));
        },

        get statCards() {
            return [
                { key: 'total', label: 'Total Tasks', value: this.totalTasks, gradient: 'bg-gradient-to-br from-blue-500 to-indigo-600' },
                { key: 'pending', label: 'Pending', value: this.pendingTasks, gradient: 'bg-gradient-to-br from-amber-400 to-orange-500' },
                { key: 'in_progress', label: 'In Progress', value: this.inProgressTasks, gradient: 'bg-gradient-to-br from-cyan-400 to-blue-600' },
                { key: 'completed', label: 'Completed', value: this.completedTasks, gradient: 'bg-gradient-to-br from-emerald-400 to-teal-600' },
                { key: 'overdue', label: 'Overdue', value: this.overdueTasks, gradient: 'bg-gradient-to-br from-rose-500 to-orange-500' },
                { key: 'efficiency', label: 'Efficiency', value: this.efficiency + '%', gradient: 'bg-gradient-to-br from-violet-500 to-purple-700' },
            ];
        },
        get totalTasks() {
            return this.tasks.length;
        },
        get pendingTasks() {
            return this.tasks.filter((t) => t.status === 'pending').length;
        },
        get inProgressTasks() {
            return this.tasks.filter((t) => t.status === 'in_progress').length;
        },
        get completedTasks() {
            return this.tasks.filter((t) => t.status === 'completed').length;
        },
        get overdueTasks() {
            return this.tasks.filter((t) => isOverdueStatus(t.due_date, t.status)).length;
        },
        get efficiency() {
            return this.totalTasks > 0 ? Math.round((this.completedTasks / this.totalTasks) * 100) : 0;
        },
        handleStatClick(key) {
            if (key === 'total' || key === 'efficiency') {
                this.filters.status = '';
                return;
            }
            this.filters.status = this.filters.status === key ? '' : key;
        },

        get hasActiveFilters() {
            return !!(this.filters.search || this.filters.atoll || this.filters.island || this.filters.status || this.filters.user);
        },
        resetFilters() {
            this.filters = { search: '', atoll: '', island: '', status: '', user: '' };
        },
        loadMore() {
            if (!this.nextCursor || this.loadingMore) return;
            this.loadingMore = true;
            window.api.request('/api/tasks?limit=100&cursor=' + encodeURIComponent(this.nextCursor))
                .then((res) => {
                    if (!res.ok) throw new Error('Failed to load more tasks');
                    return res.json();
                })
                .then((data) => {
                    const known = new Set(this.tasks.map((t) => t.id));
                    data.tasks.forEach((t) => {
                        if (!known.has(t.id)) this.tasks.push(t);
                    });
                    this.nextCursor = data.next_cursor || null;
                })
                .catch(() => {
                    this.nextCursor = null;
                })
                .finally(() => {
                    this.loadingMore = false;
                });
        },
        atollName(id) {
            return this.atolls.find((a) => a.id === id)?.name || '';
        },
        islandName(id) {
            return this.islands.find((i) => i.id === id)?.name || '';
        },
        statusLabel(status) {
            if (status === 'overdue') return 'Overdue';
            return statusConfigOf(status).label;
        },
        profileName(id) {
            const p = this.profiles.find((p) => p.id === id);
            return p ? p.first_name + ' ' + p.last_name : '';
        },
        formIslands(atollId) {
            if (!atollId) return [];
            return this.islands.filter((i) => i.atoll_id === atollId);
        },
        filteredTasks() {
            const q = this.filters.search.toLowerCase();
            return this.tasks.filter((t) => {
                if (t.archived) return false;
                if (this.filters.status === 'overdue') {
                    if (!isOverdueStatus(t.due_date, t.status)) return false;
                } else if (this.filters.status && t.status !== this.filters.status) {
                    return false;
                }
                if (this.filters.atoll && t.island?.atoll_id !== this.filters.atoll) return false;
                if (this.filters.island && t.island_id !== this.filters.island) return false;
                if (this.filters.user && t.assigned_to !== this.filters.user) return false;
                if (q) {
                    const assignor = t.assignor ? (t.assignor.first_name + ' ' + (t.assignor.last_name || '')) : '';
                    const assignee = t.assignee ? (t.assignee.first_name + ' ' + (t.assignee.last_name || '')) : '';
                    const haystack = ((t.title || '') + ' ' + (t.creator_description || '') + ' ' + assignor + ' ' + assignee).toLowerCase();
                    if (!haystack.includes(q)) return false;
                }
                return true;
            });
        },

        groupedTasks() {
            const groups = [];
            const index = {};
            this.filteredTasks().forEach((task) => {
                const atoll = task.island?.atoll || null;
                const atollId = atoll ? atoll.id : 'unassigned';
                if (!index[atollId]) {
                    index[atollId] = { atoll, islands: [], taskCount: 0 };
                    groups.push(index[atollId]);
                }
                const group = index[atollId];
                group.taskCount++;
                const island = task.island || null;
                const islandId = island ? island.id : 'unassigned';
                let sub = group.islands.find((s) => s.islandId === islandId);
                if (!sub) {
                    sub = { island, islandId, tasks: [], taskCount: 0 };
                    group.islands.push(sub);
                }
                sub.taskCount++;
                sub.tasks.push(task);
            });
            groups.sort((a, b) => (a.atoll?.name || 'zzz').localeCompare(b.atoll?.name || 'zzz'));
            groups.forEach((g) => g.islands.sort((a, b) => (a.island?.name || 'zzz').localeCompare(b.island?.name || 'zzz')));
            return groups;
        },
        isAtollExpanded(group) {
            const id = group.atoll ? group.atoll.id : 'unassigned';
            return this.expandedAtolls[id] !== false;
        },
        toggleAtoll(group) {
            const id = group.atoll ? group.atoll.id : 'unassigned';
            this.expandedAtolls[id] = !this.isAtollExpanded(group);
        },
        isIslandExpanded(sub) {
            return this.expandedIslands[sub.islandId] !== false;
        },
        toggleIsland(sub) {
            this.expandedIslands[sub.islandId] = !this.isIslandExpanded(sub);
        },
        expandAll() {
            this.groupedTasks().forEach((g) => {
                this.expandedAtolls[g.atoll ? g.atoll.id : 'unassigned'] = true;
                g.islands.forEach((s) => { this.expandedIslands[s.islandId] = true; });
            });
        },
        collapseAll() {
            this.groupedTasks().forEach((g) => {
                this.expandedAtolls[g.atoll ? g.atoll.id : 'unassigned'] = false;
                g.islands.forEach((s) => { this.expandedIslands[s.islandId] = false; });
            });
        },

        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('taskViewMode', mode);
        },

        openCreate() {
            this.createForm = {
                title: '',
                creator_description: '',
                atoll_id: '',
                island_id: '',
                assigned_to: this.currentUserId,
                priority: 'medium',
                due_date: '',
                task_types: [],
                attachment_url: '',
            };
            this.priorityOpen = false;
            this.calendarOpen = false;
            this.calendarCursor = '';
            this.createDialogOpen = true;
        },
        priorityOption(value) {
            return this.priorityOptions.find((option) => option.value === value) || this.priorityOptions[1];
        },
        openCalendar() {
            const base = this.createForm.due_date ? new Date(`${this.createForm.due_date}T00:00:00`) : new Date();
            this.calendarCursor = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;
            this.calendarOpen = !this.calendarOpen;
        },
        calendarBaseDate() {
            const [year, month] = (this.calendarCursor || '').split('-').map(Number);
            return year && month ? new Date(year, month - 1, 1) : new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        },
        calendarMonthLabel() {
            return this.calendarBaseDate().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },
        calendarDateLabel() {
            if (!this.createForm.due_date) return 'Choose a date';
            return new Date(`${this.createForm.due_date}T00:00:00`).toLocaleDateString('en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        },
        calendarDays() {
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
                    selected: value === this.createForm.due_date,
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
        selectCalendarDate(value) {
            this.createForm.due_date = value;
            this.calendarOpen = false;
        },
        toggleTaskType(name) {
            if (!Array.isArray(this.createForm.task_types)) {
                this.createForm.task_types = [];
            }
            const idx = this.createForm.task_types.indexOf(name);
            if (idx >= 0) {
                this.createForm.task_types.splice(idx, 1);
            } else {
                this.createForm.task_types.push(name);
            }
        },
        async onFileChange(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.uploading = true;
            try {
                const base64 = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(String(reader.result).split(',')[1]);
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                });
                const data = await window.api.post('/api/upload', { file: base64, filename: file.name });
                this.createForm.attachment_url = data.url;
                Alpine.store('toast').success('File uploaded');
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
        async submitCreate() {
            if (!this.createForm.title.trim() || this.uploading) return;
            this.uploading = true;
            try {
                await window.api.post('/api/tasks', {
                    title: this.createForm.title,
                    creator_description: this.createForm.creator_description || '',
                    priority: this.createForm.priority,
                    assigned_to: this.createForm.assigned_to || '',
                    island_id: this.createForm.island_id || '',
                    due_date: this.createForm.due_date || '',
                    task_types: this.createForm.task_types,
                    attachment_url: this.createForm.attachment_url || '',
                });
                Alpine.store('toast').success('Task created');
                window.location.reload();
            } catch (e) {
                Alpine.store('toast').error(e.message);
            } finally {
                this.uploading = false;
            }
        },

        get archivedTasks() {
            return this.tasks.filter((t) => t.archived && (t.status === 'completed' || t.status === 'cancelled'));
        },
        formatDate(date) {
            return formatDueDate(date);
        },
    };
}
window.taskManager = taskManager;
window.taskCard = taskCard;
window.callLogManager = callLogManager;
window.commentsActivity = commentsActivity;
