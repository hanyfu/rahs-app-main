@extends('layouts.app')

@section('title', 'Critical Staff Leave')

@section('content')
<div x-data='leavesPage({ leaves: @json($leaves), profiles: @json($profiles), setup: @json($setup), hospitals: @json($hospitalProfiles), staffCategories: @json($staffCategories), staffCategoryFields: @json($staffCategoryFields), role: @json($role) })' x-init="init()" class="max-w-6xl mx-auto">
    <x-page-header class="mb-6" title="Critical staff leave" description="{{ count($leaves) }} leave record(s) currently available." eyebrow="Workforce continuity" icon="calendar">
        <x-slot:actions>
            <button type="button" @click="openCreate()" class="gov-btn gov-btn-primary"><x-icon name="plus" class="h-4 w-4" />New leave record</button>
            @if (in_array($role, ['admin', 'supervisor', 'coordinator'], true))
                <button type="button" @click="showSetup = true; loadSetup()" class="gov-btn gov-btn-outline"><x-icon name="settings" class="h-4 w-4" />Availability setup</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($role === 'staff' && $assignees)
        <div class="mb-6 gov-card p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-3">My approving officers</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg bg-secondary p-3 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/15 text-primary text-xs font-bold">
                        {{ $assignees['coordinator'] ? \Illuminate\Support\Str::substr($assignees['coordinator']->first_name, 0, 1) . \Illuminate\Support\Str::substr($assignees['coordinator']->last_name ?? '', 0, 1) : '?' }}
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Coordinator</p>
                        <p class="text-sm font-medium">{{ $assignees['coordinator']?->full_name ?? 'Not assigned' }}</p>
                    </div>
                </div>
                <div class="rounded-lg bg-secondary p-3 flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/15 text-primary text-xs font-bold">
                        {{ $assignees['supervisor'] ? \Illuminate\Support\Str::substr($assignees['supervisor']->first_name, 0, 1) . \Illuminate\Support\Str::substr($assignees['supervisor']->last_name ?? '', 0, 1) : '?' }}
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Supervisor</p>
                        <p class="text-sm font-medium">{{ $assignees['supervisor']?->full_name ?? 'Not assigned' }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Shortage risk alerts --}}
    <template x-for="alert in shortageAlerts()" :key="alert.key">
        <div class="mb-4 rounded-lg border-l-4 border-destructive bg-destructive/5 p-4">
            <p class="text-sm font-semibold text-destructive" x-text="alert.title"></p>
            <p class="text-sm mt-1" x-text="alert.message"></p>
        </div>
    </template>

    {{-- Leave records --}}
    <div class="space-y-3">
        <template x-for="l in filteredLeaves()" :key="l.id">
            <article class="gov-card overflow-hidden p-1.5 interactive-lift">
                <div class="rounded-[calc(1rem-0.25rem)] bg-card p-4 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge" :class="statusClasses(l.approval_status)" x-text="labelize(l.approval_status)"></span>
                            <span class="badge" :class="criticalClasses(l.critical_level)" x-text="labelize(l.critical_level) + ' priority'"></span>
                            <span class="badge" :class="urgencyClasses(l.urgency)" x-text="labelize(l.urgency)"></span>
                            <span class="badge bg-muted text-muted-foreground" x-text="labelize(l.leave_type) + ' leave'"></span>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                        <button type="button" @click="openEdit(l)" class="gov-btn gov-btn-outline text-xs">Edit</button>
                        @if (in_array($role, ['admin', 'supervisor', 'coordinator'], true))
                            <template x-if="l.approval_status === 'submitted' || l.approval_status === 'pending_review'">
                                <button type="button" @click="setStatus(l, 'approved')" class="gov-btn gov-btn-primary text-xs">Approve</button>
                            </template>
                        @endif
                        <button type="button" @click="removeLeave(l)" class="gov-btn gov-btn-danger text-xs">Delete</button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                        <div class="flex items-center gap-3 rounded-2xl bg-muted/25 p-4 ring-1 ring-border/50">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-sm font-bold uppercase text-primary" x-text="String(l.staff_name || '?').charAt(0)"></div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold" x-text="l.staff_name"></p>
                                <p class="mt-0.5 text-xs font-medium text-muted-foreground" x-text="labelize(l.staff_category)"></p>
                                <p class="mt-1 truncate text-[11px] font-semibold text-primary" x-text="hospitalName(l)"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-primary/[.045] p-4 ring-1 ring-primary/10">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"><x-icon name="calendar" class="h-5 w-5" /></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold uppercase tracking-[.14em] text-muted-foreground">Leave period</p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-foreground" x-text="leavePeriod(l)"></p>
                            </div>
                            <span class="shrink-0 rounded-full bg-card px-3 py-1.5 text-xs font-bold text-primary shadow-sm ring-1 ring-primary/10" x-text="leaveDaysLabel(l.number_of_leave_days)"></span>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-3">
                        <div x-show="l.assigned_coordinator" class="rounded-xl border border-border/50 px-3 py-2.5"><span class="block text-[9px] font-bold uppercase tracking-wider">Coordinator</span><span class="mt-0.5 block font-semibold text-foreground" x-text="personName(l.coordinator)"></span></div>
                        <div x-show="l.direct_supervisor" class="rounded-xl border border-border/50 px-3 py-2.5"><span class="block text-[9px] font-bold uppercase tracking-wider">Supervisor</span><span class="mt-0.5 block font-semibold text-foreground" x-text="personName(l.supervisor)"></span></div>
                        <div x-show="l.replacement_staff" class="rounded-xl border border-border/50 px-3 py-2.5"><span class="block text-[9px] font-bold uppercase tracking-wider">Replacement</span><span class="mt-0.5 block font-semibold text-foreground" x-text="l.replacement_staff"></span></div>
                    </div>
                </div>
            </article>
        </template>
        <template x-if="filteredLeaves().length === 0">
            <div class="gov-card p-12 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground" aria-hidden="true">
                    <x-icon name="calendar" class="h-6 w-6" />
                </div>
                <p class="font-semibold">No leave records found</p>
            </div>
        </template>
    </div>

    {{-- Leave form dialog --}}
    <template x-teleport="body">
    <div x-show="showForm" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/60 p-0 sm:items-center sm:px-5 sm:pb-5 sm:pt-20" @click.self="!saving && (showForm = false)" @keydown.escape.window="!saving && (showForm = false)" role="dialog" aria-modal="true" aria-labelledby="leave-form-title">
        <article x-show="showForm" x-transition:enter="transition duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-[.98]" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100" class="flex max-h-[calc(100dvh-0.75rem)] w-full flex-col overflow-hidden rounded-t-[1.75rem] border border-border/70 bg-card shadow-[0_32px_90px_-30px_rgba(2,12,27,0.55)] sm:h-auto sm:max-h-[calc(100dvh-7rem)] sm:max-w-3xl sm:rounded-[1.75rem] xl:max-h-[760px]">
            <header class="flex shrink-0 items-center justify-between gap-4 border-b border-border/70 bg-card px-5 py-4 sm:px-6 sm:py-5">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.16em] text-primary">Workforce continuity</p>
                    <h2 id="leave-form-title" class="mt-1 text-lg font-bold" x-text="editing ? 'Edit leave record' : 'New leave record'"></h2>
                </div>
                <button type="button" @click="showForm = false" class="touch-target inline-flex items-center justify-center rounded-xl text-muted-foreground transition-colors hover:bg-muted hover:text-foreground" aria-label="Close leave form">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </header>
            <form @submit.prevent="save()" class="flex min-h-0 flex-1 flex-col">
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 sm:px-6">
                <div x-show="formError" x-cloak role="alert" class="mb-4 rounded-xl border border-destructive/25 bg-destructive/10 px-4 py-3 text-sm font-semibold text-destructive" x-text="formError"></div>
                <div class="space-y-3.5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium mb-1">Hospital *</label>
                        <select x-model="form.hospital_profile_id" required class="gov-input">
                            <option value="">Select assigned hospital</option>
                            <template x-for="hospital in hospitals" :key="hospital.id">
                                <option :value="hospital.id" x-text="hospitalLabel(hospital)"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">Only hospitals within your assigned island or atoll are available.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Staff name *</label>
                        <input type="text" x-model="form.staff_name" required class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Staff category *</label>
                        <select x-model="form.staff_category" required class="gov-input">
                            <template x-for="category in staffCategories" :key="category">
                                <option :value="category" x-text="categoryOptionLabel(category, form.hospital_profile_id)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Leave type *</label>
                        <select x-model="form.leave_type" required class="gov-input">
                            <option value="annual">Annual</option>
                            <option value="sick">Sick</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="maternity">Maternity</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Shift affected</label>
                        <input type="text" x-model="form.shift_affected" class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Start date *</label>
                        <div @click.outside="calendarOpen === 'leave_start_date' && (calendarOpen = '')">
                            <button type="button" @click="openCalendar('leave_start_date')" class="premium-date-trigger" :aria-expanded="calendarOpen === 'leave_start_date'" aria-haspopup="dialog">
                                <span class="flex items-center gap-3"><span class="premium-date-icon"><x-icon name="calendar" class="h-4 w-4" /></span><span class="text-left"><span class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Leave begins</span><span class="block text-sm font-bold" :class="form.leave_start_date ? 'text-foreground' : 'text-muted-foreground'" x-text="calendarDateLabel('leave_start_date')"></span></span></span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform duration-200" x-bind:class="calendarOpen === 'leave_start_date' ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="calendarOpen === 'leave_start_date'" x-cloak x-transition.origin.top class="premium-calendar mt-2 p-4" role="dialog" aria-label="Choose leave start date">
                                <div class="flex items-center justify-between"><button type="button" @click="changeCalendarMonth(-1)" class="premium-calendar-nav" aria-label="Previous month"><x-icon name="chevron-left" class="h-4 w-4" /></button><div class="text-center"><p class="text-sm font-black" x-text="calendarMonthLabel()"></p><button type="button" @click="goCalendarToday()" class="mt-0.5 text-[11px] font-semibold text-primary">Today</button></div><button type="button" @click="changeCalendarMonth(1)" class="premium-calendar-nav" aria-label="Next month"><x-icon name="chevron-right" class="h-4 w-4" /></button></div>
                                <div class="mt-4 grid grid-cols-7 text-center"><template x-for="dayName in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="dayName"><span class="py-1 text-[10px] font-black uppercase tracking-wider text-muted-foreground" x-text="dayName"></span></template></div>
                                <div class="mt-1 grid grid-cols-7 gap-1"><template x-for="day in calendarDays('leave_start_date')" :key="day.value"><button type="button" @click="selectCalendarDate('leave_start_date', day.value)" class="premium-calendar-day" :class="{ 'is-outside': !day.currentMonth, 'is-today': day.today, 'is-selected': day.selected }" :aria-label="day.label" :aria-pressed="day.selected" x-text="day.day"></button></template></div>
                                <div class="mt-4 flex items-center justify-between border-t border-border/60 pt-3"><button type="button" @click="clearCalendarDate('leave_start_date')" class="text-xs font-semibold text-muted-foreground">Clear</button><button type="button" @click="calendarOpen = ''" class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-primary-foreground">Done</button></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">End date *</label>
                        <div @click.outside="calendarOpen === 'leave_end_date' && (calendarOpen = '')">
                            <button type="button" @click="openCalendar('leave_end_date')" class="premium-date-trigger" :aria-expanded="calendarOpen === 'leave_end_date'" aria-haspopup="dialog">
                                <span class="flex items-center gap-3"><span class="premium-date-icon"><x-icon name="calendar" class="h-4 w-4" /></span><span class="text-left"><span class="block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Return date</span><span class="block text-sm font-bold" :class="form.leave_end_date ? 'text-foreground' : 'text-muted-foreground'" x-text="calendarDateLabel('leave_end_date')"></span></span></span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground transition-transform duration-200" x-bind:class="calendarOpen === 'leave_end_date' ? 'rotate-180' : ''" />
                            </button>
                            <div x-show="calendarOpen === 'leave_end_date'" x-cloak x-transition.origin.top class="premium-calendar mt-2 p-4" role="dialog" aria-label="Choose leave end date">
                                <div class="flex items-center justify-between"><button type="button" @click="changeCalendarMonth(-1)" class="premium-calendar-nav" aria-label="Previous month"><x-icon name="chevron-left" class="h-4 w-4" /></button><div class="text-center"><p class="text-sm font-black" x-text="calendarMonthLabel()"></p><button type="button" @click="goCalendarToday()" class="mt-0.5 text-[11px] font-semibold text-primary">Today</button></div><button type="button" @click="changeCalendarMonth(1)" class="premium-calendar-nav" aria-label="Next month"><x-icon name="chevron-right" class="h-4 w-4" /></button></div>
                                <div class="mt-4 grid grid-cols-7 text-center"><template x-for="dayName in ['Su','Mo','Tu','We','Th','Fr','Sa']" :key="dayName"><span class="py-1 text-[10px] font-black uppercase tracking-wider text-muted-foreground" x-text="dayName"></span></template></div>
                                <div class="mt-1 grid grid-cols-7 gap-1"><template x-for="day in calendarDays('leave_end_date')" :key="day.value"><button type="button" @click="selectCalendarDate('leave_end_date', day.value)" class="premium-calendar-day" :class="{ 'is-outside': !day.currentMonth, 'is-today': day.today, 'is-selected': day.selected }" :aria-label="day.label" :aria-pressed="day.selected" x-text="day.day"></button></template></div>
                                <div class="mt-4 flex items-center justify-between border-t border-border/60 pt-3"><button type="button" @click="clearCalendarDate('leave_end_date')" class="text-xs font-semibold text-muted-foreground">Clear</button><button type="button" @click="calendarOpen = ''" class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-primary-foreground">Done</button></div>
                            </div>
                        </div>
                    </div>
                    @if ($role !== 'staff')
                        <div>
                            <label class="block text-sm font-medium mb-1">Assigned coordinator</label>
                            <select x-model="form.assigned_coordinator" class="gov-input">
                                <option value="">None</option>
                                <template x-for="p in coordinators" :key="p.id">
                                    <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Direct supervisor</label>
                            <select x-model="form.direct_supervisor" class="gov-input">
                                <option value="">None</option>
                                <template x-for="p in supervisors" :key="p.id">
                                    <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                                </template>
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium mb-1">Critical level</label>
                        <select x-model="form.critical_level" class="gov-input">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Urgency</label>
                        <select x-model="form.urgency" class="gov-input">
                            <option value="normal">Normal</option>
                            <option value="urgent">Urgent</option>
                            <option value="emergency">Emergency</option>
                        </select>
                    </div>
                    @if ($role !== 'staff')
                        <div>
                            <label class="block text-sm font-medium mb-1">Approval status</label>
                            <select x-model="form.approval_status" class="gov-input">
                                <option value="submitted">Submitted</option>
                                <option value="pending_review">Pending review</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Reason for leave</label>
                    <textarea x-model="form.reason_for_leave" rows="2" class="gov-input"></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Contact during leave</label>
                        <input type="text" x-model="form.contact_during_leave" class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Replacement staff</label>
                        <input type="text" x-model="form.replacement_staff" class="gov-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Handover notes</label>
                    <textarea x-model="form.handover_notes" rows="2" class="gov-input"></textarea>
                </div>
                </div>
                </div>
                <footer class="grid shrink-0 grid-cols-2 gap-2 border-t border-border/70 bg-card px-4 py-3.5 safe-bottom sm:flex sm:items-center sm:justify-end sm:px-6 sm:py-4">
                    <button type="button" @click="showForm = false" :disabled="saving" class="gov-btn gov-btn-outline w-full sm:w-auto">Cancel</button>
                    <button type="submit" :disabled="saving" class="gov-btn gov-btn-primary w-full sm:w-auto"><x-icon name="loader-2" x-show="saving" x-cloak class="h-4 w-4 animate-spin" /><span x-text="saving ? 'Saving…' : (editing ? 'Save changes' : 'Create record')"></span></button>
                </footer>
            </form>
        </article>
    </div>
    </template>

    {{-- Availability setup dialog --}}
    <div x-show="showSetup" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showSetup = false" @keydown.escape.window="showSetup = false" role="dialog" aria-modal="true" aria-label="Staff availability setup">
        <div class="w-full sm:max-w-2xl max-h-[90dvh] overscroll-contain overflow-y-auto bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">Availability Setup</h2>
                <button type="button" @click="showSetup = false" class="touch-target rounded-md p-1 text-muted-foreground hover:bg-secondary">✕</button>
            </div>

            <form @submit.prevent="saveSetup()" class="mb-6 grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Hospital *</label>
                    <select x-model="setupForm.hospital_profile_id" required class="gov-input">
                        <option value="">Select hospital</option>
                        <template x-for="hospital in hospitals" :key="hospital.id">
                            <option :value="hospital.id" x-text="hospitalLabel(hospital)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Staff category *</label>
                    <select x-model="setupForm.staff_category" required class="gov-input">
                        <template x-for="category in staffCategories" :key="category">
                            <option :value="category" x-text="categoryOptionLabel(category, setupForm.hospital_profile_id)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Shift *</label>
                    <input type="text" x-model="setupForm.shift" required class="gov-input" placeholder="All shifts / Morning / Evening / Night">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Coordinator responsible</label>
                    <select x-model="setupForm.coordinator_responsible" class="gov-input">
                        <option value="">None</option>
                        <template x-for="p in coordinators" :key="p.id">
                            <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                        </template>
                    </select>
                </div>
                <div class="rounded-xl bg-primary/5 p-3 text-sm text-muted-foreground">Active staff total is read automatically from the selected hospital profile.</div>
                <div>
                    <label class="block text-sm font-medium mb-1">Required minimum staff *</label>
                    <input type="number" x-model="setupForm.required_minimum_staff" required min="0" class="gov-input">
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <button type="submit" class="gov-btn gov-btn-primary">Add setup row</button>
                </div>
            </form>

            <div class="gov-card overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Unit</th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">Shift</th>
                            <th class="px-4 py-3 font-semibold">Active</th>
                            <th class="px-4 py-3 font-semibold">Min</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in setup" :key="s.id">
                            <tr class="border-t border-border">
                                <td class="px-4 py-3 font-medium" x-text="s.department_unit"></td>
                                <td class="px-4 py-3" x-text="s.staff_category"></td>
                                <td class="px-4 py-3" x-text="s.shift"></td>
                                <td class="px-4 py-3" x-text="s.total_active_staff"></td>
                                <td class="px-4 py-3" x-text="s.required_minimum_staff"></td>
                                <td class="px-4 py-3"><span class="badge" :class="s.status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'" x-text="s.status"></span></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="toggleSetup(s)" class="gov-btn gov-btn-outline text-xs" x-text="s.status === 'active' ? 'Deactivate' : 'Activate'"></button>
                                        <button type="button" @click="removeSetup(s)" class="gov-btn gov-btn-danger text-xs">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-if="setup.length === 0">
                            <tr><td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No setup rows</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
