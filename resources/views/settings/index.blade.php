@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div x-data='settingsPage({ first_name: @json($profile->first_name), last_name: @json($profile->last_name), contact_no: @json($profile->contact_no), designation: @json($profile->designation), avatar_url: @json($profile->avatar_url) })' x-init="init()" class="max-w-3xl mx-auto space-y-6">
    <x-page-header title="Settings" description="Manage your profile, account details, and password." eyebrow="Account" icon="settings" />

    {{-- Profile --}}
    <div class="gov-card p-6">
        <h2 class="font-semibold mb-4">Profile</h2>
        <div class="mb-5 flex items-center gap-4">
            <div class="shrink-0">
                <template x-if="profileForm.avatar_url">
                    <img :src="profileForm.avatar_url" alt="Profile avatar preview" class="h-14 w-14 rounded-full bg-muted object-cover">
                </template>
                <template x-if="!profileForm.avatar_url">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/20 text-lg font-bold text-primary">{{ $profile->initials }}</div>
                </template>
            </div>
            <div class="flex-1">
                <p class="font-semibold">{{ $profile->full_name }}</p>
                <p class="text-sm text-muted-foreground">{{ $profile->email }}</p>
            </div>
        </div>

        <form @submit.prevent="saveProfile()" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium mb-1">First name *</label>
                <input type="text" x-model="profileForm.first_name" required class="gov-input">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Last name *</label>
                <input type="text" x-model="profileForm.last_name" required class="gov-input">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Contact number</label>
                <input type="tel" x-model="profileForm.contact_no" class="gov-input" placeholder="+960 7xx xxxx">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Designation</label>
                <input type="text" x-model="profileForm.designation" class="gov-input">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Avatar</label>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="text" x-model="profileForm.avatar_url" readonly class="gov-input min-w-0" placeholder="No avatar set">
                    <label class="gov-btn gov-btn-outline shrink-0 cursor-pointer text-sm" :class="avatarUploading && 'pointer-events-none opacity-60'">
                        <span x-text="avatarUploading ? 'Uploading…' : 'Upload'"></span>
                        <input type="file" class="hidden" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp" @change="uploadAvatar($event)" :disabled="avatarUploading">
                    </label>
                    <button type="button" x-show="profileForm.avatar_url" @click="profileForm.avatar_url = ''" :disabled="avatarUploading" class="gov-btn gov-btn-ghost shrink-0 text-sm">Remove</button>
                </div>
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="gov-btn gov-btn-primary">Save profile</button>
            </div>
        </form>
    </div>

    {{-- Password --}}
    <div class="gov-card p-6">
        <h2 class="font-semibold mb-4">Change password</h2>
        <form @submit.prevent="changePassword()" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Current password *</label>
                <input type="password" x-model="passwordForm.current_password" required class="gov-input">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">New password *</label>
                <input type="password" x-model="passwordForm.password" required minlength="8" class="gov-input">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Confirm new password *</label>
                <input type="password" x-model="passwordForm.password_confirmation" required class="gov-input">
            </div>
            <div class="sm:col-span-2 flex justify-end">
                <button type="submit" class="gov-btn gov-btn-primary">Update password</button>
            </div>
        </form>
    </div>

    {{-- Account info --}}
    <div class="gov-card p-6">
        <h2 class="font-semibold mb-4">Account</h2>
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-muted-foreground">Role</dt>
                <dd class="font-medium capitalize">{{ auth()->user()->role }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Email</dt>
                <dd class="font-medium">{{ $profile->email }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Member since</dt>
                <dd class="font-medium">{{ $profile->created_at?->format('M j, Y') ?? '—' }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-5">
            @csrf
            <button type="submit" class="gov-btn gov-btn-danger">Sign out</button>
        </form>
    </div>
</div>
@endsection
