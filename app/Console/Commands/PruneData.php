<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneData extends Command
{
    protected $signature = 'mom:prune-data
        {--force : Benar-benar hapus; tanpa ini hanya laporan dry-run}
        {--audit-days=365}
        {--notification-days=90}
        {--version-days=90}';

    protected $description = 'Pangkas audit log, notifikasi terbaca, dan versi meeting lama';

    public function handle(): int
    {
        $auditCutoff = now()->subDays((int) $this->option('audit-days'));
        $notificationCutoff = now()->subDays((int) $this->option('notification-days'));
        $versionCutoff = now()->subDays((int) $this->option('version-days'));

        $latestVersions = DB::table('meeting_versions')
            ->selectRaw('MAX(id)')
            ->groupBy('meeting_id');

        $auditCount = DB::table('audit_logs')->where('created_at', '<', $auditCutoff)->count();
        $notificationCount = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $notificationCutoff)
            ->count();
        $versionCount = DB::table('meeting_versions')
            ->where('created_at', '<', $versionCutoff)
            ->whereNotIn('id', $latestVersions)
            ->count();

        $this->line("Audit log lama: {$auditCount}");
        $this->line("Notifikasi terbaca lama: {$notificationCount}");
        $this->line("Versi meeting lama (bukan versi terbaru): {$versionCount}");

        if (! $this->option('force')) {
            $this->warn('Dry-run. Tambahkan --force untuk menghapus.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($auditCutoff, $notificationCutoff, $versionCutoff) {
            DB::table('audit_logs')->where('created_at', '<', $auditCutoff)->delete();
            DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', $notificationCutoff)->delete();
            DB::table('meeting_versions')
                ->where('created_at', '<', $versionCutoff)
                ->whereNotIn('id', DB::table('meeting_versions')->selectRaw('MAX(id)')->groupBy('meeting_id'))
                ->delete();
        });

        $this->info('Pemangkasan selesai.');

        return self::SUCCESS;
    }
}
