import { router } from '@inertiajs/vue3';

function isNative(): boolean {
    return typeof window !== 'undefined' && 'Capacitor' in window;
}

function pathFromUrl(url: string): string | null {
    try {
        const parsed = new URL(url);

        if (parsed.protocol === 'mom:') {
            return `/${parsed.host}${parsed.pathname}`.replace(/\/+/g, '/');
        }

        if (['http:', 'https:'].includes(parsed.protocol)) {
            return `${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
    } catch {
        return null;
    }

    return null;
}

export async function initializeNativeShell(): Promise<void> {
    if (!isNative()) {
        return;
    }

    const [{ App }, { StatusBar, Style }] = await Promise.all([import('@capacitor/app'), import('@capacitor/status-bar')]);

    await App.addListener('appUrlOpen', ({ url }) => {
        const path = pathFromUrl(url);

        if (path) {
            router.visit(path);
        }
    });

    await App.addListener('backButton', ({ canGoBack }) => {
        if (window.location.pathname === '/dashboard' || !canGoBack) {
            void App.exitApp();

            return;
        }

        window.history.back();
    });

    const isDark = document.documentElement.classList.contains('dark');
    await StatusBar.setStyle({ style: isDark ? Style.Light : Style.Dark });
    await StatusBar.setBackgroundColor({ color: isDark ? '#12100e' : '#f7f2ea' });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const dark = document.documentElement.classList.contains('dark');
        void StatusBar.setStyle({ style: dark ? Style.Light : Style.Dark });
        void StatusBar.setBackgroundColor({ color: dark ? '#12100e' : '#f7f2ea' });
    });
}

export function isNativePlatform(): boolean {
    return isNative();
}

export async function shareDocument(title: string, url: string): Promise<void> {
    if (isNative()) {
        const { Share } = await import('@capacitor/share');
        await Share.share({ title, text: title, url, dialogTitle: title });

        return;
    }

    if (navigator.share) {
        await navigator.share({ title, text: title, url });

        return;
    }

    await navigator.clipboard.writeText(url);
}
