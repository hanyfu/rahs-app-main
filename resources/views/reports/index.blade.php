@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div x-data='reportsPage({ tasks: @json($tasks), scheduled: @json($scheduledReports), atolls: @json($atolls), islands: @json($islands), departments: @json($departments), profiles: @json($profiles) })' class="max-w-6xl mx-auto">
    <x-page-header class="mb-6" title="Reports & analytics" description="Monitor task performance across atolls and departments." eyebrow="Insights" icon="bar-chart-3">
        <x-slot:actions>
            <a href="{{ route('reports.index') }}?export=tasks" class="gov-btn gov-btn-outline text-sm" x-show="false">Export CSV</a>
            <button type="button" @click="download('{{ url('/api/reports/export/tasks') }}')" class="gov-btn gov-btn-outline"><x-icon name="download" class="h-4 w-4" />Tasks CSV</button>
            <button type="button" @click="download('{{ url('/api/reports/export/hospital-contacts') }}')" class="gov-btn gov-btn-outline"><x-icon name="download" class="h-4 w-4" />Hospital contacts</button>
            <button type="button" @click="download('{{ url('/api/reports/export/hospital-profiles') }}')" class="gov-btn gov-btn-outline"><x-icon name="download" class="h-4 w-4" />Hospital profiles</button>
        </x-slot:actions>
    </x-page-header>

    {{-- Report builder --}}
    <section class="gov-card mb-6 overflow-hidden" aria-labelledby="report-builder-title">
        <div class="border-b border-border bg-muted/30 px-5 py-4 sm:flex sm:items-center sm:justify-between">
            <div>
                <h2 id="report-builder-title" class="font-semibold">Generate a report</h2>
                <p class="mt-1 text-sm text-muted-foreground">Choose a report, narrow the data, then download a current CSV.</p>
            </div>
            <span class="mt-2 inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary sm:mt-0" x-text="activeFilterCount() ? activeFilterCount() + ' filters applied' : 'All records'"></span>
        </div>
        <div class="space-y-5 p-5">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label for="report-type" class="mb-1.5 block text-sm font-medium">Report type</label>
                    <select id="report-type" x-model="reportType" class="gov-input">
                        <template x-for="option in reportTypes" :key="option.value">
                            <option :value="option.value" x-text="option.label"></option>
                        </template>
                    </select>
                    <p class="mt-1.5 text-xs text-muted-foreground" x-text="selectedReportDescription()"></p>
                </div>
                <div>
                    <label for="report-date-from" class="mb-1.5 block text-sm font-medium">From</label>
                    <input id="report-date-from" type="date" x-model="reportFilters.date_from" class="gov-input">
                </div>
                <div>
                    <label for="report-date-to" class="mb-1.5 block text-sm font-medium">To</label>
                    <input id="report-date-to" type="date" x-model="reportFilters.date_to" :min="reportFilters.date_from" class="gov-input">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="report-atoll" class="mb-1.5 block text-sm font-medium">Atoll</label>
                    <select id="report-atoll" x-model="reportFilters.atoll_id" @change="reportFilters.island_id = ''" class="gov-input">
                        <option value="">All atolls</option>
                        <template x-for="atoll in atolls" :key="atoll.id"><option :value="atoll.id" x-text="atoll.name"></option></template>
                    </select>
                </div>
                <div>
                    <label for="report-island" class="mb-1.5 block text-sm font-medium">Island</label>
                    <select id="report-island" x-model="reportFilters.island_id" class="gov-input">
                        <option value="">All islands</option>
                        <template x-for="island in filteredIslands()" :key="island.id"><option :value="island.id" x-text="island.name"></option></template>
                    </select>
                </div>
                <div>
                    <label for="report-department" class="mb-1.5 block text-sm font-medium">Department</label>
                    <select id="report-department" x-model="reportFilters.department_id" class="gov-input">
                        <option value="">All departments</option>
                        <template x-for="department in departments" :key="department.id"><option :value="department.id" x-text="department.name"></option></template>
                    </select>
                </div>
                <div>
                    <label for="report-assignee" class="mb-1.5 block text-sm font-medium">Assigned staff</label>
                    <select id="report-assignee" x-model="reportFilters.assigned_to" class="gov-input">
                        <option value="">All staff</option>
                        <template x-for="profile in profiles" :key="profile.id"><option :value="profile.id" x-text="profile.full_name || [profile.first_name, profile.last_name].filter(Boolean).join(' ')"></option></template>
                    </select>
                </div>
                <div>
                    <label for="report-status" class="mb-1.5 block text-sm font-medium">Status</label>
                    <select id="report-status" x-model="reportFilters.status" class="gov-input"><option value="">All statuses</option><option value="pending">Pending</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                </div>
                <div>
                    <label for="report-priority" class="mb-1.5 block text-sm font-medium">Priority</label>
                    <select id="report-priority" x-model="reportFilters.priority" class="gov-input"><option value="">All priorities</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" @click="clearReportFilters()" class="gov-btn gov-btn-ghost" :disabled="!activeFilterCount()">Clear filters</button>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="button" @click="scheduleCurrentReport()" class="gov-btn gov-btn-outline"><x-icon name="calendar" class="h-4 w-4" />Schedule this report</button>
                    <button type="button" @click="generateReport()" class="gov-btn gov-btn-primary"><x-icon name="download" class="h-4 w-4" />Generate CSV</button>
                </div>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
        <div class="gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
            <p class="mt-1 text-3xl font-bold" x-text="stats.total"></p>
        </div>
        <div class="gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Pending</p>
            <p class="mt-1 text-3xl font-bold text-amber-500" x-text="stats.pending"></p>
        </div>
        <div class="gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">In progress</p>
            <p class="mt-1 text-3xl font-bold text-blue-500" x-text="stats.inProgress"></p>
        </div>
        <div class="gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Completed</p>
            <p class="mt-1 text-3xl font-bold text-emerald-500" x-text="stats.completed"></p>
        </div>
        <div class="gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Efficiency</p>
            <p class="mt-1 text-3xl font-bold" x-text="stats.efficiency + '%'"></p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        <div class="gov-card p-5">
            <h3 class="font-semibold mb-4">Tasks by status</h3>
            <div class="flex h-48 items-end gap-3">
                <template x-for="d in statusChart()" :key="d.label">
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <div class="w-full rounded-t-md transition-all duration-500" :style="'height:' + d.height + 'px; background-color:' + d.color"></div>
                        <p class="text-xs font-medium" x-text="d.label"></p>
                        <p class="text-xs text-muted-foreground" x-text="d.value"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="gov-card p-5">
            <h3 class="font-semibold mb-4">Completion rate</h3>
            <div class="flex items-center justify-center py-6">
                <div class="relative h-40 w-40">
                    <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="hsl(var(--muted))" stroke-width="10" />
                        <circle cx="50" cy="50" r="42" fill="none" stroke="hsl(var(--primary))" stroke-width="10"
                                stroke-linecap="round" :stroke-dasharray="stats.efficiency + ' 100'" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <p class="text-2xl font-bold" x-text="stats.efficiency + '%'"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent tasks table --}}
    <div class="mt-8 gov-card overflow-hidden">
        <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="font-semibold">Recent tasks</h3>
            <a href="{{ route('tasks.index') }}" class="text-sm text-primary hover:underline">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Title</th>
                        <th class="px-4 py-3 font-semibold">Island</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Priority</th>
                        <th class="px-4 py-3 font-semibold">Due</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="t in recentTasks()" :key="t.id">
                        <tr class="border-t border-border">
                            <td class="px-4 py-3 font-medium max-w-[280px] truncate" x-text="t.title"></td>
                            <td class="px-4 py-3" x-text="t.island?.name || '—'"></td>
                            <td class="px-4 py-3"><span class="badge" :class="statusClasses(t.status)" x-text="t.status.replace('_', ' ')"></span></td>
                            <td class="px-4 py-3 capitalize" x-text="t.priority"></td>
                            <td class="px-4 py-3" x-text="t.due_date || '—'"></td>
                        </tr>
                    </template>
                    <template x-if="recentTasks().length === 0">
                        <tr><td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No tasks found</td></tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Scheduled reports --}}
    <div class="mt-8 gov-card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">Scheduled reports</h3>
            <button type="button" @click="openSchedule()" class="gov-btn gov-btn-outline text-sm">New schedule</button>
        </div>

        <div class="space-y-3">
            <template x-for="r in scheduled" :key="r.id">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 rounded-lg border border-border p-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm truncate" x-text="r.name"></p>
                        <p class="text-xs text-muted-foreground" x-text="scheduleLabel(r)"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleSchedule(r)" class="gov-btn gov-btn-outline text-xs" x-text="r.is_active ? 'Pause' : 'Resume'"></button>
                        <button type="button" @click="removeSchedule(r)" class="gov-btn gov-btn-danger text-xs">Delete</button>
                    </div>
                </div>
            </template>
            <template x-if="scheduled.length === 0">
                <p class="text-sm text-muted-foreground">No scheduled reports. Create one to receive automated email summaries.</p>
            </template>
        </div>
    </div>

    {{-- Schedule dialog --}}
    <div x-show="showSchedule" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showSchedule = false" @keydown.escape.window="showSchedule = false" role="dialog" aria-modal="true" aria-label="Schedule a report">
        <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-lg bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-4">Schedule a report</h2>
            <form @submit.prevent="createSchedule()" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Report name *</label>
                    <input type="text" x-model="scheduleForm.name" required class="gov-input" placeholder="Weekly task summary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Report type</label>
                    <select x-model="scheduleForm.report_type" class="gov-input">
                        <template x-for="option in reportTypes" :key="option.value"><option :value="option.value" x-text="option.label"></option></template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Recipients (comma separated emails)</label>
                    <input type="text" x-model="recipientsText" required class="gov-input" placeholder="a@rahs.mv, b@rahs.mv">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Frequency</label>
                        <select x-model="scheduleForm.frequency" class="gov-input" @change="onFrequencyChange()">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Time (HH:MM)</label>
                        <input type="time" x-model="scheduleForm.time_of_day" required class="gov-input">
                    </div>
                </div>
                <div x-show="scheduleForm.frequency === 'weekly'">
                    <label class="block text-sm font-medium mb-1">Day of week</label>
                    <select x-model="scheduleForm.day_of_week" class="gov-input">
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                </div>
                <div x-show="scheduleForm.frequency === 'monthly'">
                    <label class="block text-sm font-medium mb-1">Day of month</label>
                    <input type="number" x-model="scheduleForm.day_of_month" min="1" max="31" class="gov-input">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showSchedule = false" class="gov-btn gov-btn-outline">Cancel</button>
                    <button type="submit" class="gov-btn gov-btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
