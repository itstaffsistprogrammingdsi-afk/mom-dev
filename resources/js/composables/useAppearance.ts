import { router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import type { SharedData } from '@/types';

type Appearance = 'light' | 'dark' | 'system';

const themeColors: Record<'light' | 'dark', string> = {
    light: '#f7f2ea',
    dark: '#12100e',
};

let activeAppearance: Appearance = 'system';

export function updateTheme(value: Appearance) {
    activeAppearance = value;

    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const resolved = value === 'system' ? systemTheme : value;

    document.documentElement.classList.toggle('dark', resolved === 'dark');
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themeColors[resolved]);
}

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
let listenerRegistered = false;

const handleSystemThemeChange = () => {
    if (activeAppearance === 'system') {
        updateTheme('system');
    }
};

export function initializeTheme() {
    const serverAppearance = (window as Window & { __MOM_SERVER_APPEARANCE__?: Appearance }).__MOM_SERVER_APPEARANCE__;
    const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(serverAppearance ?? savedAppearance ?? 'system');

    if (!listenerRegistered) {
        mediaQuery.addEventListener('change', handleSystemThemeChange);
        listenerRegistered = true;
    }
}

export function useAppearance() {
    const page = usePage<SharedData>();
    const appearance = ref<Appearance>('system');

    onMounted(() => {
        const serverAppearance = page.props.preferences?.appearance;
        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

        appearance.value = serverAppearance ?? savedAppearance ?? 'system';
        updateTheme(appearance.value);
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        updateTheme(value);

        if (page.props.auth?.user) {
            router.put(
                route('interface-preferences.update'),
                { appearance: value },
                { preserveScroll: true, preserveState: true },
            );
        }
    }

    return {
        appearance,
        updateAppearance,
    };
}
