@extends('layouts.app')

@section('title', 'Role Permissions')

@section('content')
<div x-data='permissionsPage({ permissions: @json($permissions) })' x-init="init()" class="max-w-5xl mx-auto">
    <x-page-header class="mb-6" title="Role permissions" description="Review and manage the access-control matrix for every system role." eyebrow="Administration" icon="shield">
        @if (auth()->user()->role === 'admin')
            <x-slot:actions><button type="button" @click="openCreate()" class="gov-btn gov-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add permission</button></x-slot:actions>
        @endif
    </x-page-header>

    <div class="gov-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[680px]">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Permission</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                        <th class="px-4 py-3 font-semibold text-center">Admin</th>
                        <th class="px-4 py-3 font-semibold text-center">Supervisor</th>
                        <th class="px-4 py-3 font-semibold text-center">Coordinator</th>
                        <th class="px-4 py-3 font-semibold text-center">Staff</th>
                        @if (auth()->user()->role === 'admin')
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $permission->permission_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ $permission->permission_key }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary/10 text-primary">{{ $permission->category }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($permission->admin_access)
                                    <span class="text-emerald-500 font-bold">✓</span>
                                @else
                                    <span class="text-muted-foreground/40">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($permission->supervisor_access)
                                    <span class="text-emerald-500 font-bold">✓</span>
                                @else
                                    <span class="text-muted-foreground/40">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($permission->coordinator_access)
                                    <span class="text-emerald-500 font-bold">✓</span>
                                @else
                                    <span class="text-muted-foreground/40">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($permission->staff_access)
                                    <span class="text-emerald-500 font-bold">✓</span>
                                @else
                                    <span class="text-muted-foreground/40">—</span>
                                @endif
                            </td>
                            @if (auth()->user()->role === 'admin')
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openEdit('{{ $permission->id }}')" class="gov-btn gov-btn-outline text-xs">Edit</button>
                                        <button type="button" @click="remove('{{ $permission->id }}')" class="gov-btn gov-btn-danger text-xs">Delete</button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (auth()->user()->role === 'admin')
        {{-- Form dialog --}}
        <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4" @click.self="showForm = false" role="dialog" aria-modal="true" aria-label="Role permission">
            <div class="w-full sm:max-w-lg bg-card rounded-t-2xl sm:rounded-xl p-5 sm:p-6">
                <h2 class="text-lg font-bold mb-4" x-text="editing ? 'Edit permission' : 'Add permission'"></h2>
                <form @submit.prevent="save()" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Name *</label>
                            <input type="text" x-model="form.permission_name" required class="gov-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Category *</label>
                            <input type="text" x-model="form.category" required class="gov-input">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <input type="text" x-model="form.permission_description" class="gov-input">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 pt-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="form.supervisor_access" class="h-4 w-4 rounded border-border text-primary"> Supervisor
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="form.coordinator_access" class="h-4 w-4 rounded border-border text-primary"> Coordinator
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" x-model="form.staff_access" class="h-4 w-4 rounded border-border text-primary"> Staff
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showForm = false" class="gov-btn gov-btn-outline">Cancel</button>
                        <button type="submit" class="gov-btn gov-btn-primary" x-text="editing ? 'Save changes' : 'Add permission'"></button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

