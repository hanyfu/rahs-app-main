@extends('layouts.app')

@section('title', 'Page not found')

@section('content')
    <div class="mx-auto flex min-h-[65vh] max-w-xl flex-col items-center justify-center text-center">
        <div class="rounded-[2rem] border border-border/60 bg-muted/50 p-2 shadow-[0_24px_70px_-38px_rgba(0,69,75,.45)]">
            <div class="flex h-24 w-24 items-center justify-center rounded-[1.55rem] border border-border/60 bg-card text-primary shadow-sm">
                <x-icon name="search" class="h-10 w-10" />
            </div>
        </div>
        <p class="mt-8 text-xs font-bold uppercase tracking-[.2em] text-primary">Error 404</p>
        <h1 class="mt-3 text-3xl font-black sm:text-4xl">This page is out of range</h1>
        <p class="mt-3 max-w-md text-base leading-7 text-muted-foreground">The address may be outdated, or the page may have moved. Return to the dashboard to continue working.</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <button type="button" onclick="history.back()" class="gov-btn gov-btn-outline">Go back</button>
            <a href="{{ route('dashboard') }}" class="gov-btn gov-btn-primary">Back to dashboard</a>
        </div>
    </div>
@endsection
