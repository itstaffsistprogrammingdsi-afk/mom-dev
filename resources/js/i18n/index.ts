import { createI18n } from 'vue-i18n';
import id from './locales/id.json';
import en from './locales/en.json';

export type Locale = 'id' | 'en';

export const supportedLocales: Locale[] = ['id', 'en'];

function detectLocale(): Locale {
    const documentLocale = document.documentElement.lang.slice(0, 2);

    if (documentLocale === 'en') {
        return 'en';
    }

    const stored = window.localStorage.getItem('locale');

    if (stored === 'en' || stored === 'id') {
        return stored;
    }

    return navigator.language.toLowerCase().startsWith('en') ? 'en' : 'id';
}

export const i18n = createI18n({
    legacy: false,
    locale: detectLocale(),
    fallbackLocale: 'id',
    messages: { id, en },
});

export function currentLocale(): Locale {
    return i18n.global.locale.value as Locale;
}

export function setLocale(locale: Locale): void {
    i18n.global.locale.value = locale;
    document.documentElement.lang = locale;
    window.localStorage.setItem('locale', locale);
    document.cookie = `locale=${locale}; path=/; max-age=31536000; SameSite=Lax`;
}

export function translate(key: string, params?: Record<string, unknown>): string {
    return params ? i18n.global.t(key, params) : i18n.global.t(key);
}
