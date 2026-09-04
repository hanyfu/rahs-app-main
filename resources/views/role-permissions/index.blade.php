@extends('layouts.app')
@section('title', 'Role Permissions')
@section('content')
@php($roles = [['admin_access','Admin','shield'],['supervisor_access','Supervisor','eye'],['coordinator_access','Coordinator','users'],['staff_access','Staff','user-round-check']])
<div x-data='permissionsPage({ permissions: @json($permissions) })' class="mx-auto max-w-6xl space-y-8 pb-16">
    <x-page-header title="Role Permissions" description="Control what each system role can view and manage." eyebrow="Access control" icon="shield">
        <x-slot:actions><button @click="openCreate()" class="gov-btn gov-btn-primary rounded-full"><x-icon name="plus" class="h-4 w-4" />Add permission</button></x-slot:actions>
    </x-page-header>

    <div class="rounded-[2rem] bg-primary/[.05] p-2 ring-1 ring-primary/10">
        <div class="grid gap-5 rounded-[1.5rem] bg-card p-6 shadow-[inset_0_1px_0_rgba(255,255,255,.65)] sm:grid-cols-3">
            <div><p class="text-[10px] font-bold uppercase tracking-[.18em] text-muted-foreground">Permissions</p><p class="mt-2 text-2xl font-black" x-text="permissions.length"></p></div>
            <div><p class="text-[10px] font-bold uppercase tracking-[.18em] text-muted-foreground">System roles</p><p class="mt-2 text-2xl font-black">4</p></div>
            <div><p class="text-[10px] font-bold uppercase tracking-[.18em] text-muted-foreground">Admin access</p><p class="mt-2 font-bold text-primary">Always protected</p></div>
        </div>
    </div>

    @forelse($permissions->groupBy('category') as $category => $items)
    <section class="rounded-[2rem] bg-black/[.025] p-2 ring-1 ring-black/5 dark:bg-white/[.035] dark:ring-white/10">
        <div class="overflow-hidden rounded-[1.5rem] bg-card shadow-[inset_0_1px_0_rgba(255,255,255,.65)]">
            <div class="flex items-center gap-3 px-6 py-5"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-icon name="shield" class="h-5 w-5" /></span><div><h2 class="text-lg font-black">{{ $category }}</h2><p class="text-xs text-muted-foreground">{{ $items->count() }} access rules</p></div></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
                <thead><tr class="border-y border-border/60 bg-muted/30"><th class="w-[38%] px-6 py-4 text-left text-xs text-muted-foreground">Permission</th>@foreach($roles as [$field,$label,$icon])<th class="px-3 py-4 text-center text-xs text-muted-foreground"><span class="inline-flex items-center gap-2"><x-icon name="{{ $icon }}" class="h-4 w-4" />{{ $label }}</span></th>@endforeach<th class="px-5 text-right text-xs text-muted-foreground">Actions</th></tr></thead>
                <tbody>@foreach($items as $permission)<tr class="border-b border-border/50 last:border-0 hover:bg-primary/[.025]">
                    <td class="px-6 py-4"><p class="font-bold">{{ $permission->permission_name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ $permission->permission_description ?: Str::headline($permission->permission_key) }}</p></td>
                    @foreach($roles as [$field,$label,$icon])<td class="px-3 py-4 text-center">@if($field === 'admin_access')
                        <span class="mx-auto flex h-7 w-12 items-center justify-end rounded-full bg-primary p-1 opacity-80"><span class="h-5 w-5 rounded-full bg-white shadow-sm"></span></span>
                    @else<label class="relative mx-auto block h-7 w-12 cursor-pointer"><input type="checkbox" class="peer sr-only" :checked="!!permissions.find(p => p.id === '{{ $permission->id }}')?.{{ $field }}" @change="toggleAccess('{{ $permission->id }}','{{ $field }}',$event.target.checked)"><span class="absolute inset-0 rounded-full bg-muted transition-colors duration-500 peer-checked:bg-primary"></span><span class="absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-500 peer-checked:translate-x-5"></span></label>@endif</td>@endforeach
                    <td class="px-5 py-4"><div class="flex justify-end gap-1"><button @click="openEdit('{{ $permission->id }}')" class="gov-btn gov-btn-ghost h-9 w-9 p-0" aria-label="Edit"><x-icon name="pencil" class="h-4 w-4" /></button><button @click="remove('{{ $permission->id }}')" class="gov-btn gov-btn-ghost h-9 w-9 p-0 text-destructive" aria-label="Delete"><x-icon name="trash-2" class="h-4 w-4" /></button></div></td>
                </tr>@endforeach</tbody>
            </table></div>
        </div>
    </section>
    @empty<div class="gov-card p-12 text-center font-bold">No permissions configured</div>@endforelse

    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/55 sm:items-center sm:p-4" @click.self="showForm=false" @keydown.escape.window="showForm=false" role="dialog" aria-modal="true">
        <div class="max-h-[calc(100dvh-0.5rem)] w-full overflow-y-auto overscroll-contain rounded-t-[2rem] bg-card p-6 sm:max-w-lg sm:rounded-[2rem]"><h2 class="text-xl font-black" x-text="editing?'Edit permission':'Add permission'"></h2><form @submit.prevent="save()" class="mt-6 space-y-4">
            <div><label class="label">Permission name</label><input x-model="form.permission_name" required class="gov-input"></div><div><label class="label">Category</label><input x-model="form.category" required class="gov-input"></div><div><label class="label">Description</label><input x-model="form.permission_description" class="gov-input"></div>
            <div class="grid grid-cols-3 gap-2"><label class="gov-card p-3 text-xs"><input type="checkbox" x-model="form.supervisor_access"> Supervisor</label><label class="gov-card p-3 text-xs"><input type="checkbox" x-model="form.coordinator_access"> Coordinator</label><label class="gov-card p-3 text-xs"><input type="checkbox" x-model="form.staff_access"> Staff</label></div>
            <div class="flex justify-end gap-2 pt-3"><button type="button" @click="showForm=false" class="gov-btn gov-btn-outline">Cancel</button><button class="gov-btn gov-btn-primary" x-text="editing?'Save changes':'Add permission'"></button></div>
        </form></div>
    </div>
</div>
@endsection
