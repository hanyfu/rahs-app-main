@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <div class="w-full max-w-sm">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-xl bg-primary text-xl font-black text-primary-foreground shadow-sm">
                R
            </div>
            <h1 class="text-xl font-bold tracking-tight">Choose a new password</h1>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email"
                       placeholder="Email" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5 {{ $errors->has('email') ? '!border-destructive' : '' }}">
                @error('email')
                    <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="label">New password</label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"
                       placeholder="New password (at least 8 characters)" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5 {{ $errors->has('password') ? '!border-destructive' : '' }}">
                @error('password')
                    <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       placeholder="Confirm new password" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5">
            </div>
            <button type="submit" class="btn h-12 w-full rounded-xl text-base font-semibold">Reset password</button>
        </form>
    </div>
@endsection
