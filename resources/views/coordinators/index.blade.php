@extends('layouts.app')

@section('title', 'Coordinators')

@section('content')
<div x-data='coordinatorsPage({ managers: @json($managers), atolls: @json($atolls) })' class="max-w-6xl mx-auto space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Atoll Managers</h1>
        <p class="text-sm text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                <span x-text="counts.coordinators + ' coordinator' + (counts.coordinators !== 1 ? 's' : '')"></span>
            </span>
            <span class="mx-2">·</span>
            <span class="inline-flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                <span x-text="counts.supervisors + ' supervisor' + (counts.supervisors !== 1 ? 's' : '')"></span>
            </span>
        </p>
        @if ($role === 'staff')
            <p class="mt-1 text-xs text-muted-foreground">Read-only view for staff.</p>
        @endif
    </div>

    {{-- Search & Filter --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" x-model="searchQuery" placeholder="Search by name, occupation, atoll, or role..." class="h-10 w-full rounded-xl border border-border bg-background pl-9 pr-9 text-sm">
            <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <select x-model="atollFilter" class="h-10 w-full sm:w-[200px] rounded-xl border border-border bg-background px-3 text-sm">
            <option value="all">All Atolls</option>
            <template x-for="a in atolls" :key="a.id">
                <option :value="a.id" x-text="a.name"></option>
            </template>
        </select>
    </div>

    {{-- Desktop table --}}
    <div class="hidden md:block overflow-hidden rounded-xl border border-border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Occupation</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Assigned Atoll(s)</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Contact</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="filteredManagers().length === 0">
                    <tr>
                        <td colspan="6" class="py-12 text-center text-muted-foreground">
                            <span x-text="searchQuery || atollFilter !== 'all' ? 'No managers match your filters.' : 'No managers found. Add one to get started.'"></span>
                        </td>
                    </tr>
                </template>
                <template x-for="m in filteredManagers()" :key="m.id + '-' + m.role">
                    <tr class="border-t border-border hover:bg-muted/30 transition-colors">
                        <td class="px-4 py-3 font-semibold" x-text="m.first_name + ' ' + m.last_name"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider" :class="m.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span x-text="m.role === 'coordinator' ? 'Coordinator' : 'Supervisor'"></span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground" x-text="m.designation || '—'"></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="a in m.assigned_atolls" :key="a.id">
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider" x-text="a.name"></span>
                                </template>
                                <template x-if="m.assigned_atolls.length === 0">
                                    <span class="text-sm italic text-muted-foreground">Not assigned</span>
                                </template>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-0.5 text-xs text-muted-foreground">
                                <template x-if="m.contact_no">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span x-text="m.contact_no"></span>
                                    </span>
                                </template>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span x-text="m.email"></span>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" @click="openView(m)" class="p-1.5 text-muted-foreground hover:text-foreground" title="View">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @if ($role !== 'staff')
                                    <button type="button" @click="openEdit(m)" class="p-1.5 text-muted-foreground hover:text-foreground" title="Edit">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" @click="confirmDeactivate(m)" class="p-1.5 text-muted-foreground hover:text-destructive" title="Deactivate">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        <template x-if="filteredManagers().length === 0">
            <div class="py-12 text-center text-muted-foreground">
                <span x-text="searchQuery || atollFilter !== 'all' ? 'No managers match your filters.' : 'No managers found. Add one to get started.'"></span>
            </div>
        </template>
        <template x-for="m in filteredManagers()" :key="m.id + '-' + m.role + '-card'">
            <div class="overflow-hidden rounded-xl border border-border/50 transition-colors hover:border-border">
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-center gap-2">
                                <h4 class="truncate font-bold" x-text="m.first_name + ' ' + m.last_name"></h4>
                                <template x-if="m.assigned_atolls.length === 0">
                                    <span class="rounded-full border border-amber-500/30 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-500">Unassigned</span>
                                </template>
                            </div>
                            <span class="mb-2 inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider" :class="m.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <span x-text="m.role === 'coordinator' ? 'Coordinator' : 'Supervisor'"></span>
                            </span>
                            <p class="mb-2 text-xs text-muted-foreground" x-text="m.designation || 'No designation'"></p>
                            <div class="mb-2 flex flex-wrap gap-1">
                                <template x-for="a in m.assigned_atolls" :key="a.id">
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider" x-text="a.name"></span>
                                </template>
                            </div>
                            <div class="space-y-1 text-xs text-muted-foreground">
                                <template x-if="m.contact_no">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span class="truncate" x-text="m.contact_no"></span>
                                    </div>
                                </template>
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="truncate" x-text="m.email"></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col gap-1">
                            <button type="button" @click="openView(m)" class="p-1.5 text-muted-foreground hover:text-foreground">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            @if ($role !== 'staff')
                                <button type="button" @click="openEdit(m)" class="p-1.5 text-muted-foreground hover:text-foreground">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button type="button" @click="confirmDeactivate(m)" class="p-1.5 text-muted-foreground hover:text-destructive">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Edit dialog --}}
    <div x-show="isEditDialogOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="isEditDialogOpen = false" @keydown.escape.window="isEditDialogOpen = false" role="dialog" aria-modal="true" aria-label="Edit coordinator">
        <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-md bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-1" x-text="'Edit ' + (editingManager ? roleLabel(editingManager.role) : 'Manager')"></h2>
            <p class="text-sm text-muted-foreground mb-4" x-text="editingManager ? 'Update atoll assignments for ' + editingManager.first_name + ' ' + editingManager.last_name : ''"></p>
            <div class="space-y-4">
                <template x-if="editingManager">
                    <div class="rounded-xl border border-border bg-muted/30 p-3">
                        <div class="text-sm font-semibold" x-text="editingManager.first_name + ' ' + editingManager.last_name"></div>
                        <div class="text-xs text-muted-foreground" x-text="editingManager.designation || 'No designation'"></div>
                        <div class="text-xs text-muted-foreground" x-text="editingManager.email"></div>
                    </div>
                </template>
                <div>
                    <p class="mb-2 text-sm font-medium">Assigned Atoll(s)</p>
                    <div class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-border p-3">
                        <template x-for="a in atolls" :key="a.id">
                            <button type="button" @click="toggleAtoll(a.id)" class="flex items-center gap-2 rounded-lg p-2 text-left text-xs font-medium transition-all" :class="editForm.atoll_ids.includes(a.id) ? 'border border-primary/30 bg-primary/10 text-primary' : 'border border-transparent bg-muted/30 hover:bg-muted/50'">
                                <svg class="h-3.5 w-3.5 shrink-0" :class="editForm.atoll_ids.includes(a.id) ? 'text-primary' : 'text-muted-foreground'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span x-text="a.name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="isEditDialogOpen = false" class="gov-btn gov-btn-outline">Cancel</button>
                <button type="button" @click="saveAssignments()" class="gov-btn gov-btn-primary" :disabled="saving">
                    <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- View dialog --}}
    <div x-show="isViewDialogOpen" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="isViewDialogOpen = false" @keydown.escape.window="isViewDialogOpen = false" role="dialog" aria-modal="true" aria-label="Coordinator details">
        <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain sm:max-w-lg bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-4">Manager Profile</h2>
            <template x-if="viewingManager">
                <div class="space-y-6">
                    <div class="flex items-center gap-4 rounded-xl border border-border bg-muted/30 p-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-lg font-black text-primary" x-text="(viewingManager.first_name[0] || '') + (viewingManager.last_name[0] || '')"></div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold" x-text="viewingManager.first_name + ' ' + viewingManager.last_name"></h3>
                                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider" :class="viewingManager.role === 'coordinator' ? 'border-amber-500/30 text-amber-600' : 'border-blue-500/30 text-blue-600'">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span x-text="roleLabel(viewingManager.role)"></span>
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground" x-text="viewingManager.designation || roleLabel(viewingManager.role)"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Email</p>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span x-text="viewingManager.email"></span>
                            </div>
                        </div>
                        <template x-if="viewingManager.contact_no">
                            <div>
                                <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Mobile</p>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <span x-text="viewingManager.contact_no"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div>
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Assigned Atoll(s)</p>
                        <template x-if="viewingManager.assigned_atolls.length > 0">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <template x-for="a in viewingManager.assigned_atolls" :key="a.id">
                                    <div class="flex items-center gap-2 rounded-lg border border-primary/10 bg-primary/5 p-3">
                                        <svg class="h-4 w-4 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="text-sm font-semibold" x-text="a.name"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="viewingManager.assigned_atolls.length === 0">
                            <p class="text-sm italic text-muted-foreground">No atolls assigned</p>
                        </template>
                    </div>
                </div>
            </template>
            <div class="mt-6 flex justify-end">
                <button type="button" @click="isViewDialogOpen = false" class="gov-btn gov-btn-outline">Close</button>
            </div>
        </div>
    </div>

    {{-- Deactivate confirmation --}}
    <div x-show="deleteConfirmId" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="deleteConfirmId = null" @keydown.escape.window="deleteConfirmId = null" role="alertdialog" aria-modal="true" aria-label="Delete coordinator">
        <div class="w-full sm:max-w-md bg-card rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-1">Deactivate Manager</h2>
            <p class="text-sm text-muted-foreground mb-5">This will remove their role and unassign all atolls. The user will retain their profile but lose access.</p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="deleteConfirmId = null" class="gov-btn gov-btn-outline">Cancel</button>
                <button type="button" @click="handleDeactivate()" class="gov-btn gov-btn-danger">Deactivate</button>
            </div>
        </div>
    </div>
</div>
@endsection
