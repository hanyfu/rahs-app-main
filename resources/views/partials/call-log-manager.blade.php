<div
    x-data="callLogManager(task.id, task.title, { assignedTo: task.assigned_to, assignedBy: task.assigned_by, userRole: userRole, currentUserId: currentUserId })"
    x-init="init()"
    @keydown.escape.window="!uploading && (showAdd=false, showEdit=false, showDelete=false)"
    class="space-y-4"
>
    {{-- Header with search and add --}}
    <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div class="flex w-full flex-1 items-center gap-2 sm:w-auto">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input type="search" x-model="searchQuery" placeholder="Search task logs..." class="input h-9 pl-9">
            </div>
            <select x-model="sortOrder" class="select-trigger h-9 w-[140px] rounded-md text-sm">
                <option value="desc">Newest first</option>
                <option value="asc">Oldest first</option>
            </select>
        </div>

        <template x-if="canAddLog">
            <button type="button" @click="openAdd()" class="btn h-9 rounded-md px-3 text-sm">
                <x-icon name="plus" class="mr-2 h-4 w-4" />
                Add Task Log
            </button>
        </template>
    </div>

    {{-- Task logs list --}}
    <template x-if="loading">
        <div class="py-4 text-center text-muted-foreground">Loading task logs...</div>
    </template>

    <template x-if="!loading && filteredLogs.length === 0">
        <div class="rounded-lg border border-border bg-card">
            <div class="py-8 text-center text-muted-foreground">
                <x-icon name="phone" class="mx-auto mb-2 h-8 w-8 opacity-50" />
                <span x-text="searchQuery ? 'No task logs match your search' : 'No task logs yet'"></span>
            </div>
        </div>
    </template>

    <template x-if="!loading && filteredLogs.length > 0">
        <div class="space-y-3">
            <template x-for="log in filteredLogs" :key="log.id">
                <div class="rounded-lg border border-border bg-card transition-shadow hover:shadow-sm">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 flex-1 gap-3">
                                <div class="h-8 w-8 shrink-0 overflow-hidden rounded-full bg-primary/10 text-xs font-bold text-primary">
                                    <template x-if="creatorOf(log.user_id)?.avatar_url"><img :src="creatorOf(log.user_id).avatar_url" alt="Task log author avatar" class="h-full w-full object-cover"></template>
                                    <template x-if="!creatorOf(log.user_id)?.avatar_url"><span class="flex h-full w-full items-center justify-center" x-text="initials(creatorOf(log.user_id))"></span></template>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <span class="font-medium" x-text="log.contact_name"></span>
                                        <template x-if="log.contact_phone">
                                            <span class="text-xs text-muted-foreground" x-text="'• ' + log.contact_phone"></span>
                                        </template>
                                        <span class="ml-auto text-xs text-muted-foreground" x-text="formatDateTime(log.call_date)"></span>
                                    </div>
                                    <template x-if="creatorOf(log.user_id)">
                                        <p class="mb-1 text-xs text-muted-foreground" x-text="'by ' + creatorOf(log.user_id).first_name + ' ' + creatorOf(log.user_id).last_name"></p>
                                    </template>
                                    <template x-if="log.notes">
                                        <p class="mt-1 text-sm text-muted-foreground" x-text="log.notes"></p>
                                    </template>
                                    <template x-if="log.attachment_url">
                                        <a :href="log.attachment_url" target="_blank" class="mt-2 inline-flex cursor-pointer items-center gap-1 text-sm text-primary hover:underline">
                                            <x-icon name="paperclip" class="h-3 w-3" />
                                            <span x-text="fileName(log.attachment_url)"></span>
                                            <x-icon name="download" class="h-3 w-3" />
                                        </a>
                                    </template>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="openEdit(log)" class="btn btn-ghost h-8 w-8 p-0" aria-label="Edit call log"><x-icon name="pencil" class="h-4 w-4" /></button>
                                <button type="button" @click="openDelete(log)" class="btn btn-ghost h-8 w-8 p-0 text-destructive hover:text-destructive" aria-label="Delete call log"><x-icon name="trash-2" class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Add dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="showAdd" x-cloak role="dialog" aria-modal="true" aria-label="Add call log">
        <button type="button" class="absolute inset-0 bg-black/60" @click="!uploading && closeAdd()" aria-label="Cancel adding task log"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-lg overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-lg font-black uppercase tracking-tight">Add Task Log</h3>
            <p class="mt-1 text-sm text-muted-foreground" x-text="'Record a log for ' + (taskTitle || 'this task')"></p>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="callDate">Date &amp; Time</label>
                    <input id="callDate" type="datetime-local" x-model="formData.call_date" required class="input">
                </div>
                <div>
                    <label class="label" for="contactName">Contact Name</label>
                    <input id="contactName" type="text" autocomplete="off" x-model="formData.contact_name" placeholder="Enter contact name" required class="input">
                </div>
                <div>
                    <label class="label" for="contactPhone">Phone Number (Optional)</label>
                    <input id="contactPhone" type="tel" autocomplete="off" x-model="formData.contact_phone" placeholder="Enter phone number" class="input">
                </div>
                <div>
                    <label class="label" for="logNotes">Notes</label>
                    <textarea id="logNotes" autocomplete="off" x-model="formData.notes" placeholder="Add notes about the call..." rows="4" class="textarea resize-none"></textarea>
                </div>
                <div>
                    <label class="label" for="logAttachment">Attachment</label>
                    <input id="logAttachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp" @change="onFileChange($event)" class="input h-10 file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-primary">
                    <p class="mt-1 text-xs text-muted-foreground">Supported: PDF, DOC, DOCX, JPG, PNG, GIF, WEBP (max 5MB)</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="closeAdd()" :disabled="uploading" class="btn btn-outline mr-2">Cancel</button>
                <button type="button" @click="submitAdd()" :disabled="uploading" class="btn">
                    <x-icon name="loader-2" x-show="uploading" class="mr-2 h-4 w-4 animate-spin" />
                    <span x-text="uploading ? 'Uploading...' : 'Add Task Log'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Edit dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="showEdit" x-cloak role="dialog" aria-modal="true" aria-label="Edit call log">
        <button type="button" class="absolute inset-0 bg-black/60" @click="!uploading && closeEdit()" aria-label="Cancel editing task log"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-lg overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-lg font-black uppercase tracking-tight">Edit Task Log</h3>
            <p class="mt-1 text-sm text-muted-foreground">Update task log details</p>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="editCallDate">Date &amp; Time</label>
                    <input id="editCallDate" type="datetime-local" x-model="formData.call_date" required class="input">
                </div>
                <div>
                    <label class="label" for="editContactName">Contact Name</label>
                    <input id="editContactName" type="text" autocomplete="off" x-model="formData.contact_name" placeholder="Enter contact name" required class="input">
                </div>
                <div>
                    <label class="label" for="editContactPhone">Phone Number (Optional)</label>
                    <input id="editContactPhone" type="tel" autocomplete="off" x-model="formData.contact_phone" placeholder="Enter phone number" class="input">
                </div>
                <div>
                    <label class="label" for="editLogNotes">Notes</label>
                    <textarea id="editLogNotes" autocomplete="off" x-model="formData.notes" placeholder="Add notes about the call..." rows="4" class="textarea resize-none"></textarea>
                </div>
                <div>
                    <label class="label" for="editLogAttachment">Attachment</label>
                    <template x-if="formData.attachment_url && !formData.attachment_file">
                        <div class="mb-2 flex items-center gap-2 rounded-md bg-muted p-2">
                            <x-icon name="paperclip" class="h-4 w-4 shrink-0" />
                            <span class="flex-1 truncate text-sm" x-text="fileName(formData.attachment_url)"></span>
                            <button type="button" @click="removeAttachment()" class="btn btn-ghost h-8 w-8 p-0" aria-label="Remove attachment"><x-icon name="x" class="h-4 w-4" /></button>
                        </div>
                    </template>
                    <input id="editLogAttachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp" @change="onFileChange($event)" class="input h-10 file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-primary">
                    <p class="mt-1 text-xs text-muted-foreground">Supported: PDF, DOC, DOCX, JPG, PNG, GIF, WEBP (max 5MB)</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="closeEdit()" :disabled="uploading" class="btn btn-outline mr-2">Cancel</button>
                <button type="button" @click="submitEdit()" :disabled="uploading" class="btn">
                    <x-icon name="loader-2" x-show="uploading" class="mr-2 h-4 w-4 animate-spin" />
                    <span x-text="uploading ? 'Uploading...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Delete confirm dialog --}}
    <div class="mobile-dialog fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" x-show="showDelete" x-cloak role="alertdialog" aria-modal="true" aria-label="Delete call log">
        <button type="button" class="absolute inset-0 bg-black/60" @click="showDelete = false" aria-label="Cancel deleting task log"></button>
        <div class="mobile-dialog-panel relative z-10 max-h-[calc(100dvh-0.5rem)] w-full max-w-md overflow-y-auto overscroll-contain rounded-t-2xl border border-border bg-card p-5 shadow-xl animate-zoom-in sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl sm:p-6">
            <h3 class="text-lg font-black uppercase tracking-tight">Delete Task Log</h3>
            <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-slate-400">Are you sure you want to delete this task log? This action cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="showDelete = false" class="btn btn-outline">Cancel</button>
                <button type="button" @click="confirmDelete()" class="btn bg-destructive text-destructive-foreground hover:bg-destructive/90">Delete</button>
            </div>
        </div>
    </div>
</div>
