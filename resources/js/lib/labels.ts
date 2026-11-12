export const meetingTypeLabels: Record<string, string> = {
    daily: 'Rapat Harian',
    weekly: 'Rapat Mingguan',
    division: 'Rapat Divisi',
    cross_division: 'Rapat Lintas Divisi',
    management: 'Rapat Manajemen',
    other: 'Lainnya',
};

export const documentTypeLabels: Record<string, string> = {
    meeting: 'Notulen',
    proposal: 'Proposal',
    brochure: 'Brosur',
    report: 'Laporan',
    other: 'Dokumen Lain',
};

export const documentTypeClasses: Record<string, string> = {
    meeting: 'bg-person-surface text-person',
    proposal: 'bg-topic-surface text-topic',
    brochure: 'bg-division-surface text-division',
    report: 'bg-success/10 text-success',
    other: 'bg-muted text-muted-foreground',
};

export const statusLabels: Record<string, string> = {
    draft: 'Draf',
    final: 'Final',
    locked: 'Terkunci',
};

export const confidentialityLabels: Record<string, string> = {
    open: 'Terbuka',
    division: 'Divisi',
    restricted: 'Terbatas',
    secret: 'Rahasia',
};

export const confidentialityClasses: Record<string, string> = {
    open: 'bg-success/10 text-success',
    division: 'bg-person-surface text-person',
    restricted: 'bg-division-surface text-division',
    secret: 'bg-destructive/10 text-destructive',
};

export const statusClasses: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    final: 'bg-success/10 text-success',
    locked: 'bg-secondary text-secondary-foreground',
};

export const actionStatusLabels: Record<string, string> = {
    new: 'Baru',
    in_progress: 'Berjalan',
    done: 'Selesai',
    cancelled: 'Dibatalkan',
};

export const actionStatusClasses: Record<string, string> = {
    new: 'bg-muted text-muted-foreground',
    in_progress: 'bg-person-surface text-person',
    done: 'bg-success/10 text-success',
    cancelled: 'bg-secondary text-secondary-foreground',
};

export const attendanceLabels: Record<string, string> = {
    invited: 'Diundang',
    present: 'Hadir',
    absent: 'Tidak hadir',
    excused: 'Izin',
};

export const roleLabels: Record<string, string> = {
    admin: 'Admin',
    notulis: 'Notulis',
    member: 'Anggota',
    reader: 'Pembaca',
};

export function formatDate(value?: string | null): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value?: string | null): string {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
