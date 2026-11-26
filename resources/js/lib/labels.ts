import { currentLocale, i18n } from '@/i18n';

function translatedLabels(group: string, fallback: Record<string, string>): Record<string, string> {
    return new Proxy(fallback, {
        get(target, property: string | symbol) {
            if (typeof property !== 'string') {
                return Reflect.get(target, property);
            }

            const key = `labels.${group}.${property}`;

            return i18n.global.te(key) ? i18n.global.t(key) : target[property];
        },
    });
}

export const meetingTypeLabels = translatedLabels('meetingTypes', {
    daily: 'Rapat Harian',
    weekly: 'Rapat Mingguan',
    division: 'Rapat Divisi',
    cross_division: 'Rapat Lintas Divisi',
    management: 'Rapat Manajemen',
    other: 'Lainnya',
});

export const documentTypeLabels = translatedLabels('documentTypes', {
    meeting: 'Notulen',
    proposal: 'Proposal',
    brochure: 'Brosur',
    report: 'Laporan',
    other: 'Dokumen Lain',
});

export const documentTypeClasses: Record<string, string> = {
    meeting: 'bg-person-surface text-person',
    proposal: 'bg-topic-surface text-topic',
    brochure: 'bg-division-surface text-division',
    report: 'bg-success/10 text-success-strong',
    other: 'bg-muted text-muted-foreground',
};

export const statusLabels = translatedLabels('statuses', {
    draft: 'Draf',
    final: 'Final',
    locked: 'Terkunci',
});

export const confidentialityLabels = translatedLabels('confidentiality', {
    open: 'Terbuka',
    division: 'Divisi',
    restricted: 'Terbatas',
    secret: 'Rahasia',
});

export const confidentialityClasses: Record<string, string> = {
    open: 'bg-success/10 text-success-strong',
    division: 'bg-person-surface text-person',
    restricted: 'bg-division-surface text-division',
    secret: 'bg-destructive/10 text-destructive',
};

export const statusClasses: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    final: 'bg-success/10 text-success-strong',
    locked: 'bg-secondary text-secondary-foreground',
};

export const actionStatusLabels = translatedLabels('actionStatuses', {
    new: 'Baru',
    in_progress: 'Berjalan',
    done: 'Selesai',
    cancelled: 'Dibatalkan',
});

export const actionStatusClasses: Record<string, string> = {
    new: 'bg-muted text-muted-foreground',
    in_progress: 'bg-person-surface text-person',
    done: 'bg-success/10 text-success-strong',
    cancelled: 'bg-secondary text-secondary-foreground',
};

export const attendanceLabels = translatedLabels('attendance', {
    invited: 'Diundang',
    present: 'Hadir',
    absent: 'Tidak hadir',
    excused: 'Izin',
});

export const roleLabels = translatedLabels('roles', {
    admin: 'Admin',
    notulis: 'Notulis',
    member: 'Anggota',
    reader: 'Pembaca',
});

function intlLocale(): string {
    return currentLocale();
}

export function formatDate(value?: string | null): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat(intlLocale(), {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value?: string | null): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat(intlLocale(), {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

