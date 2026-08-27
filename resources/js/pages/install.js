window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.beforeinstallpromptEvent = e;
        });

// install page side effects run on import.
