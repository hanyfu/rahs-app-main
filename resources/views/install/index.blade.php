@extends('layouts.app')

@section('title', 'Install')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="gov-card overflow-hidden">
            <div class="p-6 sm:p-8">
                <h1 class="text-2xl font-bold">Install RAHS Task Manager</h1>
                <p class="mt-2 text-sm text-muted-foreground">Install this application on your device for offline access and quick launch.</p>

                <div class="mt-6 grid gap-3 sm:grid-cols-3" id="install-features">
                    <div class="rounded-lg bg-secondary p-4">
                        <p class="text-2xl font-bold text-primary">24/7</p>
                        <p class="text-sm text-muted-foreground">Access from home screen</p>
                    </div>
                    <div class="rounded-lg bg-secondary p-4">
                        <p class="text-2xl font-bold text-primary">Fast</p>
                        <p class="text-sm text-muted-foreground">Cached assets load instantly</p>
                    </div>
                    <div class="rounded-lg bg-secondary p-4">
                        <p class="text-2xl font-bold text-primary">Standalone</p>
                        <p class="text-sm text-muted-foreground">Runs in its own window</p>
                    </div>
                </div>

                <div class="mt-8 rounded-lg border border-border p-4" x-data="{ supported: 'serviceWorker' in navigator, installing: false, canInstall: false }" x-init="$nextTick(() => { canInstall = !!window.beforeinstallpromptEvent; })">
                    <p class="text-sm font-semibold">How to install</p>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                        <li>On your browser, open the address bar menu (or the share icon on mobile Safari).</li>
                        <li>Select <strong class="text-foreground">"Add to Home Screen"</strong> or <strong class="text-foreground">"Install App"</strong>.</li>
                        <li>The app will appear on your home screen like a native app.</li>
                    </ol>
                    <template x-if="supported">
                        <button type="button" class="gov-btn gov-btn-primary mt-4" x-show="canInstall"
                                @click="installing = true; window.beforeinstallpromptEvent.prompt();">
                            <template x-if="!installing">Install now</template>
                            <template x-if="installing">Installing…</template>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

