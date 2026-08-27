@extends('layouts.app')

@section('title', 'Important Contacts')

@section('content')
@php
    $searchableContacts = $contacts->map(
        fn ($contact) => implode(' ', array_filter([
            $contact->name,
            $contact->title,
            $contact->organization,
            $contact->phone_primary,
            $contact->phone_secondary,
            $contact->email,
        ]))
    )->values();
@endphp
<div x-data='contactsPage({ searchableContacts: @json($searchableContacts) })' class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Important Contacts</h1>
            <p class="text-sm text-muted-foreground">Emergency and key administrative contacts</p>
        </div>
        <div class="flex flex-wrap gap-2">
@if (in_array($role, ['admin', 'supervisor'], true))
            <a href="{{ route('important-contacts.admin') }}" class="gov-btn gov-btn-primary text-sm">
                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Manage contacts
            </a>
        @endif
        </div>
    </div>

    {{-- Search --}}
    <div class="gov-card p-3 mb-6">
        <div class="relative flex items-center gap-2">
            <svg class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" x-model="filters.search" placeholder="Search by name, title, organization, phone..." class="gov-input flex-1 pl-10 pr-12">
            <button type="button" x-show="filters.search" @click="filters.search = ''" class="gov-btn gov-btn-outline text-sm absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($contacts as $contact)
            <div class="gov-card p-5 interactive-lift"
                 x-show="matches(@js(implode(' ', array_filter([$contact->name, $contact->title, $contact->organization, $contact->phone_primary, $contact->phone_secondary, $contact->email]))))"
                 x-transition.opacity.duration.150ms>
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/15 text-primary font-bold text-sm">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($contact->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $contact->name }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $contact->title }}</p>
                        </div>
                    </div>
                    @if ($contact->priority < 10)
                        <span class="badge shrink-0 bg-destructive/10 text-destructive">Priority {{ $contact->priority }}</span>
                    @endif
                </div>

                @if ($contact->organization)
                    <p class="mt-3 text-sm text-muted-foreground">{{ $contact->organization }}</p>
                @endif

                <div class="mt-4 space-y-2 text-sm">
                    <a href="tel:{{ $contact->phone_primary }}" class="flex items-center gap-2 font-medium text-primary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $contact->phone_primary }}
                    </a>
                    @if ($contact->phone_secondary)
                        <a href="tel:{{ $contact->phone_secondary }}" class="flex items-center gap-2 text-muted-foreground">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $contact->phone_secondary }}
                        </a>
                    @endif
                    @if ($contact->email)
                        <a href="mailto:{{ $contact->email }}" class="flex items-center gap-2 text-muted-foreground break-all">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $contact->email }}
                        </a>
                    @endif
                </div>

                @if ($contact->notes)
                    <p class="mt-4 rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground">{{ $contact->notes }}</p>
                @endif
            </div>
        @empty
            <div class="gov-card p-12 text-center col-span-full">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground" aria-hidden="true">
                    <x-icon name="phone" class="h-6 w-6" />
                </div>
                <p class="font-semibold">No important contacts found</p>
            </div>
        @endforelse
    </div>

    @if ($contacts->isNotEmpty())
        <div x-show="filters.search && !hasMatches()" x-cloak class="gov-card mt-4 p-10 text-center" role="status">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground" aria-hidden="true">
                <x-icon name="search" class="h-6 w-6" />
            </div>
            <p class="font-semibold">No contacts match your search</p>
            <p class="mt-1 text-sm text-muted-foreground">Try a name, designation, organization, or phone number.</p>
            <button type="button" @click="filters.search = ''" class="gov-btn gov-btn-outline mt-4">Clear search</button>
        </div>
    @endif
</div>
@endsection
