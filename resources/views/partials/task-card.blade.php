<div
    x-data='taskCard(task, { profiles: @json($profiles), assignableProfiles: @json($assignableProfiles), islands: @json($islands), atolls: @json($atolls), departments: @json($departments), userRole: @json($userRole), currentUserId: @json($currentUserId) })'
    class="group relative flex flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-300 hover:border-primary/30 hover:shadow-md"
    @keydown.escape.window="closeDialogs()"
    :class="isOverdue ? 'border-rose-300 bg-rose-50/50 dark:border-rose-900 dark:bg-rose-950/20' : (isDueSoon ? 'border-orange-300 bg-orange-50/50 dark:border-orange-900 dark:bg-orange-950/20' : '')"
>
    {{-- Official Accent --}}
    <div class="absolute left-0 top-0 z-10 h-full w-1.5 transition-all duration-300 group-hover:w-2" :style="taskType ? 'background-color: ' + taskType.color : ''" :class="!taskType ? statusConfig.bg : ''"></div>

    {{-- Main row --}}
    <div class="flex flex-col items-stretch md:flex-row">
        {{-- Core info --}}
        <div class="flex min-w-0 flex-1 flex-col justify-center p-5 pl-6 md:py-6">
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="rounded-md border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest" :class="statusConfig.bg + ' ' + statusConfig.color + ' ' + statusConfig.border" x-text="statusConfig.label"></span>
                <template x-if="task.priority">
                    <span class="flex items-center gap-1 rounded-md border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest" :class="priorityConfig.bg + ' ' + priorityConfig.color + ' ' + priorityConfig.border">
                        <x-icon name="alert-triangle" x-show="task.priority === 'urgent' || task.priority === 'high'" class="h-3 w-3" />
                        <x-icon name="flag" x-show="task.priority === 'medium' || task.priority === 'low'" class="h-3 w-3" />
                        <span x-text="task.priority"></span>
                    </span>
                </template>
                <template x-if="task.task_types && task.task_types.length > 0">
                    <template x-for="(t, i) in task.task_types" :key="i">
                        <span class="rounded-md border border-border px-2 py-0.5 text-[10px] font-semibold text-muted-foreground" x-text="t"></span>
                    </template>
                </template>
                <template x-if="(!task.task_types || task.task_types.length === 0) && taskType">
                    <span class="rounded-md border border-border px-2 py-0.5 text-[10px] font-semibold text-muted-foreground" x-text="taskType.name"></span>
                </template>
            </div>
            <h3 class="mb-1.5 truncate text-lg font-black leading-tight tracking-tight text-slate-900 transition-colors group-hover:text-primary dark:text-white" x-text="task.title"></h3>
            <p class="whitespace-pre-wrap text-xs font-semibold leading-relaxed text-slate-500 dark:text-slate-400" x-text="task.creator_description"></p>
        </div>

        {{-- Meta info --}}
        <div class="flex flex-col items-stretch border-t border-slate-100 dark:border-slate-800 sm:flex-row md:border-l md:border-t-0">
            {{-- Location & Date --}}
            <div class="flex min-w-[180px] shrink-0 flex-col justify-center gap-4 p-5">
                <div class="flex items-center gap-2">
                    <div class="shrink-0 rounded-lg bg-slate-50 p-2 text-primary dark:bg-slate-800">
                        <x-icon name="map-pin" class="h-4 w-4" />
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Region</span>
                        <span class="truncate text-xs font-bold text-slate-700 dark:text-slate-200">
                            <template x-if="locationInfo">
                                <span x-text="(locationInfo.atoll?.name || 'Unknown Atoll') + '.' + locationInfo.island.name"></span>
                            </template>
                            <template x-if="!locationInfo">
                                <span>Global Portal</span>
                            </template>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="shrink-0 rounded-lg p-2" :class="isOverdue ? 'bg-rose-50 text-rose-600 dark:bg-rose-900/20' : (isDueSoon ? 'bg-orange-50 text-orange-600 dark:bg-orange-900/20' : 'bg-slate-50 text-primary dark:bg-slate-800')">
                        <x-icon name="calendar" class="h-4 w-4" />
                    </div>
                    <div class="flex flex-col overflow-hidden">
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Deadline</span>
                        <span class="truncate text-xs font-bold" :class="isOverdue ? 'text-rose-600' : (isDueSoon ? 'text-orange-600' : 'text-slate-700 dark:text-slate-200')">
                            <template x-if="task.due_date">
                                <span x-text="formatDate(task.due_date)"></span>
                            </template>
                            <template x-if="!task.due_date">
                                <span>Indefinite</span>
                            </template>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Creator & Assignee & Primary action --}}
            <div class="flex min-w-[160px] shrink-0 flex-col justify-center gap-3 border-t border-slate-100 bg-slate-50/50 p-5 dark:border-slate-800 dark:bg-slate-800/30 sm:border-l sm:border-t-0">
                <template x-if="createdByProfile">
                    <div class="flex w-full items-center gap-3">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800">
                            <template x-if="createdByProfile.avatar_url"><img :src="createdByProfile.avatar_url" :alt="createdByProfile.first_name + ' avatar'" class="h-full w-full object-cover"></template>
                            <template x-if="!createdByProfile.avatar_url"><span class="flex h-full w-full items-center justify-center text-xs font-bold text-muted-foreground" x-text="initials(createdByProfile)"></span></template>
                        </div>
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Created by</span>
                            <span class="truncate text-[11px] font-bold text-slate-700 dark:text-slate-200" x-text="createdByProfile.first_name + ' ' + createdByProfile.last_name"></span>
                        </div>
                    </div>
                </template>
                <template x-if="assignedToProfile">
                    <div class="flex w-full items-center gap-3">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100 font-bold text-primary shadow-sm transition-transform duration-300 group-hover:scale-105 dark:border-slate-700 dark:bg-slate-800">
                            <template x-if="assignedToProfile.avatar_url"><img :src="assignedToProfile.avatar_url" :alt="assignedToProfile.first_name + ' avatar'" class="h-full w-full object-cover"></template>
                            <template x-if="!assignedToProfile.avatar_url"><span class="flex h-full w-full items-center justify-center" x-text="initials(assignedToProfile)"></span></template>
                        </div>
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Assignee</span>
                            <span class="truncate text-[11px] font-bold text-slate-700 dark:text-slate-200" x-text="assignedToProfile.first_name + ' ' + assignedToProfile.last_name"></span>
                        </div>
                    </div>
                </template>
                <template x-if="!assignedToProfile">
                    <div class="flex w-full items-center gap-3 opacity-50">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-dashed border-slate-300 dark:border-slate-600">
                            <x-icon name="user" class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black uppercase tracking-widest">Assignee</span>
                            <span class="text-[11px] font-bold">Unassigned</span>
                        </div>
                    </div>
                </template>

                <button type="button" @click="openStatusDialog()" class="h-9 rounded-md bg-primary text-[10px] font-black uppercase tracking-wider text-white shadow-sm transition-colors hover:bg-primary/90 w-full md:w-auto">
                    Update Status
                </button>
            </div>
        </div>
    </div>

    {{-- Bottom bar: logs, comments, actions --}}
    <div class="flex flex-col divide-y divide-slate-100 border-t border-slate-100 bg-slate-50/30 dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900/50 md:flex-row md:divide-x md:divide-y-0">
        {{-- Logs --}}
        <div class="flex flex-1 flex-col justify-start">
            <button type="button" @click="toggleCallLogs()" class="flex h-12 w-full shrink-0 items-center justify-between rounded-none px-5 hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-slate-600 dark:text-slate-400">
                    <x-icon name="clock" class="h-3.5 w-3.5 text-primary" />
                    Official Logs
                    <span class="ml-1 h-4 rounded-sm bg-secondary px-1.5 py-0 text-[9px] font-black text-secondary-foreground" x-text="callLogsCount"></span>
                </span>
                <x-icon name="chevron-down" x-show="callLogsExpanded" class="h-3.5 w-3.5 opacity-50" />
                <x-icon name="chevron-right" x-show="!callLogsExpanded" class="h-3.5 w-3.5 opacity-50" />
            </button>
            <template x-if="callLogsExpanded">
                <div class="animate-fade-in border-t border-border bg-card p-3">
                    @include('partials.call-log-manager')
                </div>
            </template>
        </div>

        {{-- Comments --}}
        <div class="flex flex-1 flex-col justify-start">
            <button type="button" @click="commentsExpanded = !commentsExpanded" class="flex h-12 w-full shrink-0 items-center justify-between rounded-none px-5 hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-slate-600 dark:text-slate-400">
                    <x-icon name="message-square" class="h-3.5 w-3.5 text-accent" />
                    Communication
                </span>
                <x-icon name="chevron-down" x-show="commentsExpanded" class="h-3.5 w-3.5 opacity-50" />
                <x-icon name="chevron-right" x-show="!commentsExpanded" class="h-3.5 w-3.5 opacity-50" />
            </button>
            <template x-if="commentsExpanded">
                <div class="animate-fade-in border-t border-border bg-card p-3">
                    @include('partials.task-comments')
                </div>
            </template>
        </div>

        {{-- Extra actions --}}
        <div class="flex min-w-[160px] shrink-0 items-center justify-end gap-2 bg-card px-4 py-2">
            <button type="button" @click="openReassign()" class="h-9 w-9 rounded-md border border-slate-200 shadow-sm transition-all hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-800" title="Reassign">
                <x-icon name="user-plus" class="mx-auto h-4 w-4 text-slate-600 dark:text-slate-400" />
            </button>
            <template x-if="canEdit">
                <button type="button" @click="openEditDialog()" class="h-9 w-9 rounded-md border border-slate-200 shadow-sm transition-all hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-800" title="Edit task">
                    <x-icon name="pencil" class="mx-auto h-4 w-4 text-slate-600 dark:text-slate-400" />
                </button>
            </template>
            <template x-if="canDelete">
                <button type="button" @click="deleteDialogOpen = true" class="h-9 w-9 rounded-md border border-slate-200 text-slate-400 shadow-sm transition-colors hover:bg-rose-50 hover:text-rose-600 dark:border-slate-800 dark:hover:bg-rose-950/20" title="Delete task">
                    <x-icon name="trash-2" class="mx-auto h-4 w-4" />
                </button>
            </template>
            <template x-if="task.status === 'completed'">
                <button type="button" @click="archive()" class="h-9 w-9 rounded-md border border-slate-200 text-primary shadow-sm transition-all hover:bg-slate-100 dark:border-slate-800 dark:hover:bg-slate-800" title="Archive task">
                    <x-icon name="archive" class="mx-auto h-4 w-4" />
                </button>
            </template>
        </div>
    </div>

    {{-- Status update dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="statusDialogOpen" x-cloak @keydown.escape.window="statusDialogOpen && closeDialogs()" role="dialog" aria-modal="true" aria-label="Update task status">
        <button type="button" class="absolute inset-0 bg-black/60" @click="closeDialogs()" aria-label="Cancel status update"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Status Update</h3>
            <p class="text-sm font-semibold text-slate-500">Log the current progress of this official task.</p>
            <div class="space-y-5 py-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Operational Phase</label>
                    <select x-model="newStatus" class="select-trigger h-11 rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                        <option value="pending">Pending Review</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Finalized</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <template x-if="newStatus === 'completed'">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Final Summary</label>
                        <textarea x-model="completionDescription" placeholder="Provide a formal summary of the outcome..." rows="4" class="textarea rounded-md border-slate-200 bg-slate-50 font-medium dark:border-slate-700 dark:bg-slate-800"></textarea>
                    </div>
                </template>
            </div>
            <div class="grid grid-cols-2 gap-3"><button type="button" @click="closeDialogs()" :disabled="statusSaving" class="h-11 rounded-md border border-border bg-background text-xs font-black uppercase tracking-widest text-slate-600 hover:bg-muted dark:text-slate-300">Cancel</button><button type="button" @click="confirmStatusUpdate()" :disabled="statusSaving" class="h-11 rounded-md bg-primary text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60" x-text="statusSaving ? 'Updating…' : 'Update status'"></button></div>
        </div>
    </div>

    {{-- Reassign dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="reassignDialogOpen" x-cloak role="dialog" aria-modal="true" aria-label="Reassign task">
        <button type="button" class="absolute inset-0 bg-black/60" @click="closeReassign()" aria-label="Cancel task reassignment"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Hand Over Responsibility</h3>
            <p class="text-sm font-semibold text-slate-500">Transfer this official task to another authorized member.</p>
            <div class="space-y-6 py-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Select Assignee</label>
                    <select x-model="newAssignee" class="select-trigger h-12 rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                        <option value="">Assign to personnel...</option>
                        <template x-for="p in reassignOptions" :key="p.id">
                            <option :value="p.id" x-text="p.first_name + ' ' + p.last_name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <button type="button" @click="closeReassign()" class="h-11 rounded-md border border-border bg-background text-xs font-black uppercase tracking-widest text-slate-600 transition-colors hover:bg-muted dark:text-slate-300">
                    Cancel
                </button>
                <button type="button" @click="confirmReassign()" :disabled="!newAssignee || newAssignee === task.assigned_to" class="h-11 rounded-md bg-primary text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                    Confirm Reassignment
                </button>
            </div>
        </div>
    </div>

    {{-- Edit dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="editDialogOpen" x-cloak role="dialog" aria-modal="true" aria-label="Edit task">
        <button type="button" class="absolute inset-0 bg-black/60" @click="closeDialogs()" aria-label="Cancel task editing"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-lg overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 dark:text-white">Modify Task Records</h3>
            <p class="text-sm font-semibold text-slate-500">Update the formal details of this official record.</p>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Formal Title</label>
                    <input type="text" x-model="editForm.title" class="input h-11 rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Detailed Description</label>
                    <textarea x-model="editForm.creator_description" rows="4" class="textarea rounded-md border-slate-200 bg-slate-50 font-medium dark:border-slate-700 dark:bg-slate-800"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Priority Level</label>
                        <select x-model="editForm.priority" class="select-trigger h-11 rounded-md border-slate-200 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Task Classification</label>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <template x-for="t in departments" :key="t.id">
                                <button type="button" @click="toggleTaskType(t.name)" class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                    :style="editForm.task_types.includes(t.name) ? 'background-color: ' + (t.color || '#3b82f6') + '20; color: ' + (t.color || '#3b82f6') + '; border-color: ' + (t.color || '#3b82f6') : ''"
                                    :class="!editForm.task_types.includes(t.name) ? 'border-border bg-background text-muted-foreground hover:bg-muted' : ''">
                                    <x-icon name="check" x-show="editForm.task_types.includes(t.name)" class="h-3 w-3" />
                                    <span x-text="t.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3"><button type="button" @click="closeDialogs()" class="h-11 rounded-md border border-border bg-background text-xs font-black uppercase tracking-widest text-slate-600 hover:bg-muted dark:text-slate-300">Cancel</button><button type="button" @click="confirmEdit()" class="h-11 rounded-md bg-primary text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-primary/90">Save changes</button></div>
        </div>
    </div>

    {{-- Delete confirm dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="deleteDialogOpen" x-cloak role="alertdialog" aria-modal="true" aria-label="Delete task">
        <button type="button" class="absolute inset-0 bg-black/60" @click="closeDialogs()" aria-label="Cancel task deletion"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-t-2xl border border-destructive/25 bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-xl font-black uppercase tracking-tight text-rose-600">Authorize Deletion?</h3>
            <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-400">
                This action will permanently purge this record and all associated data from the portal database.
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="deleteDialogOpen = false" class="h-11 rounded-md px-4 text-xs font-bold uppercase tracking-widest text-slate-600 hover:bg-muted dark:text-slate-300">Cancel</button>
                <button type="button" @click="confirmDelete()" class="h-11 rounded-md bg-rose-600 px-4 text-xs font-black uppercase tracking-widest text-white transition-colors hover:bg-rose-700">Confirm Purge</button>
            </div>
        </div>
    </div>
</div>
