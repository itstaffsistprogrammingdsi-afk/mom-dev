export const meetingTypeLabels: Record<string, string> = {
    daily: 'Rapat Harian',
    weekly: 'Rapat Mingguan',
    division: 'Rapat Divisi',
    cross_division: 'Rapat Lintas Divisi',
    management: 'Rapat Manajemen',
    other: 'Lainnya',
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
    open: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    division: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    restricted: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-300',
    secret: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
};

export const statusClasses: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    final: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    locked: 'bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200',
};

export const actionStatusLabels: Record<string, string> = {
    new: 'Baru',
    in_progress: 'Berjalan',
    done: 'Selesai',
    cancelled: 'Dibatalkan',
};

export const actionStatusClasses: Record<string, string> = {
    new: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    in_progress: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
    done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    cancelled: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
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
    if (!value) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value?: string | null): string {
    if (!value) return '—';

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
