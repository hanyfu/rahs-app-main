@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="w-full max-w-sm">
        <div class="mb-10 text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-xl bg-primary text-2xl font-black text-primary-foreground shadow-sm">
                R
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Sign in to RAHS</h1>
            <p class="mt-1 text-sm text-muted-foreground">Task Manager</p>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                       placeholder="name@example.com" aria-describedby="email-error" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5 {{ $errors->has('email') ? '!border-destructive' : '' }}">
                @error('email')
                    <p id="email-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       placeholder="Enter your password" aria-describedby="password-error" class="input h-12 rounded-xl border-white/20 bg-white/80 text-base dark:border-white/10 dark:bg-white/5 {{ $errors->has('password') ? '!border-destructive' : '' }}">
                @error('password')
                    <p id="password-error" role="alert" class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div class="-mt-2 text-right">
                <a href="{{ route('password.request') }}" class="inline-flex min-h-11 items-center text-sm text-primary hover:underline">Forgot password?</a>
            </div>
            <button type="submit" class="btn h-12 w-full rounded-xl text-base font-semibold">Sign in</button>
        </form>
    </div>
@endsection
