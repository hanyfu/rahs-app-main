@extends('layouts.app')

@section('title', 'Critical Staff Leave')

@section('content')
<div x-data='leavesPage({ leaves: @json($leaves), profiles: @json($profiles), setup: @json($setup), role: @json($role) })' x-init="init()" class="max-w-6xl mx-auto">
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
            <div class="gov-card p-4 sm:p-5 interactive-lift">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge" :class="statusClasses(l.approval_status)" x-text="l.approval_status.replace('_', ' ')"></span>
                            <span class="badge" :class="criticalClasses(l.critical_level)" x-text="l.critical_level"></span>
                            <span class="badge" :class="urgencyClasses(l.urgency)" x-text="l.urgency"></span>
                            <span class="badge bg-muted text-muted-foreground" x-text="l.number_of_leave_days + ' day(s)'"></span>
                        </div>
                        <h3 class="mt-2 font-semibold" x-text="l.staff_name"></h3>
                        <p class="text-sm text-muted-foreground" x-text="l.department_unit + ' · ' + l.staff_category + ' · ' + l.staff_id"></p>
                        <p class="mt-1 text-sm font-medium" x-text="l.leave_start_date + ' → ' + l.leave_end_date"></p>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                            <span x-show="l.assigned_coordinator" x-text="'Coordinator: ' + (l.coordinator?.first_name || '')"></span>
                            <span x-show="l.direct_supervisor" x-text="'Supervisor: ' + (l.supervisor?.first_name || '')"></span>
                            <span x-show="l.replacement_staff" x-text="'Replacement: ' + l.replacement_staff"></span>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2 flex-wrap">
                        <button type="button" @click="openEdit(l)" class="gov-btn gov-btn-outline text-xs">Edit</button>
                        @if (in_array($role, ['admin', 'supervisor', 'coordinator'], true))
                            <template x-if="l.approval_status === 'submitted' || l.approval_status === 'pending_review'">
                                <button type="button" @click="setStatus(l, 'approved')" class="gov-btn gov-btn-primary text-xs">Approve</button>
                            </template>
                        @endif
                        <button type="button" @click="removeLeave(l)" class="gov-btn gov-btn-danger text-xs">Delete</button>
                    </div>
                </div>
            </div>
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
    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showForm = false" role="dialog" aria-modal="true" aria-label="Leave record">
        <div class="w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-4" x-text="editing ? 'Edit leave record' : 'New leave record'"></h2>
            <form @submit.prevent="save()" class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Staff name *</label>
                        <input type="text" x-model="form.staff_name" required class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Staff ID *</label>
                        <input type="text" x-model="form.staff_id" required class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Staff category *</label>
                        <select x-model="form.staff_category" required class="gov-input">
                            <option value="doctor">Doctor</option>
                            <option value="nurse">Nurse</option>
                            <option value="lab_technician">Lab Technician</option>
                            <option value="pharmacist">Pharmacist</option>
                            <option value="admin">Administrative</option>
                            <option value="driver">Driver</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Department / unit *</label>
                        <input type="text" x-model="form.department_unit" required class="gov-input">
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
                        <input type="date" x-model="form.leave_start_date" required class="gov-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">End date *</label>
                        <input type="date" x-model="form.leave_end_date" required class="gov-input">
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
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showForm = false" class="gov-btn gov-btn-outline">Cancel</button>
                    <button type="submit" class="gov-btn gov-btn-primary" x-text="editing ? 'Save changes' : 'Create record'"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Availability setup dialog --}}
    <div x-show="showSetup" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showSetup = false" role="dialog" aria-modal="true" aria-label="Staff availability setup">
        <div class="w-full sm:max-w-2xl max-h-[90vh] overflow-y-auto bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">Availability Setup</h2>
                <button type="button" @click="showSetup = false" class="touch-target rounded-md p-1 text-muted-foreground hover:bg-secondary">✕</button>
            </div>

            <form @submit.prevent="saveSetup()" class="mb-6 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Department / unit *</label>
                    <input type="text" x-model="setupForm.department_unit" required class="gov-input">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Staff category *</label>
                    <select x-model="setupForm.staff_category" required class="gov-input">
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="lab_technician">Lab Technician</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="admin">Administrative</option>
                        <option value="driver">Driver</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Shift *</label>
                    <input type="text" x-model="setupForm.shift" required class="gov-input" placeholder="Morning / Evening / Night">
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
                <div>
                    <label class="block text-sm font-medium mb-1">Total active staff *</label>
                    <input type="number" x-model="setupForm.total_active_staff" required min="0" class="gov-input">
                </div>
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
