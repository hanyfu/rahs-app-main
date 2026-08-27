@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-xl font-black text-primary-foreground shadow-sm">
                R
            </div>
            <h1 class="text-xl font-bold tracking-tight">Forgot password?</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <label for="email" class="label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   placeholder="you@example.com" aria-describedby="email-error" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5 {{ $errors->has('email') ? '!border-destructive' : '' }}">
            @error('email')
                <p id="email-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>
            @enderror
            <button type="submit" class="btn h-12 w-full rounded-xl text-base font-semibold">Send reset link</button>
            <button type="button" onclick="history.back()" class="min-h-11 w-full text-sm text-muted-foreground transition-colors hover:text-foreground">
                Back to sign in
            </button>
        </form>
    </div>
@endsection
