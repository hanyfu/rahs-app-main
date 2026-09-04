@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="gov-card p-6 sm:p-8">
            <div class="flex items-center gap-4">
                <x-profile-avatar :profile="$profile" size="lg" />
                <div>
                    <p class="text-sm text-muted-foreground">Welcome back,</p>
                    <h1 class="text-xl sm:text-2xl font-bold">{{ $profile?->full_name ?? 'Staff' }}</h1>
                    <p class="text-sm text-muted-foreground capitalize">{{ auth()->user()?->role ?? '' }}</p>
                </div>
            </div>

            @if ($assignedIsland)
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-secondary p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Assigned Island</p>
                        <p class="mt-1 text-lg font-bold">{{ $assignedIsland->name }}</p>
                        <p class="text-sm text-muted-foreground">{{ $atollName }}</p>
                    </div>
                    <div class="rounded-lg bg-secondary p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Health Facility</p>
                        <p class="mt-1 text-lg font-bold">{{ $hospitalName }}</p>
                    </div>
                </div>
            @else
                <div class="mt-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                    <p class="text-sm text-amber-800 dark:text-amber-200">No island is assigned to your account yet. Contact your administrator.</p>
                </div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('tasks.index') }}" class="gov-btn gov-btn-primary">View my tasks</a>
                <a href="{{ route('hospitals.index') }}" class="gov-btn gov-btn-outline">Hospital contacts</a>
                <a href="{{ route('leaves.index') }}" class="gov-btn gov-btn-outline">Staff leave</a>
                <a href="{{ route('settings.index') }}" class="gov-btn gov-btn-outline">Settings</a>
            </div>
        </div>
    </div>
@endsection