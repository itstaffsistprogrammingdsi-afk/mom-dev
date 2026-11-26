<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckFailedJobs extends Command
{
    protected $signature = 'mom:check-failed-jobs';

    protected $description = 'Beri tahu admin jika ada job gagal yang belum ditangani';

    public function handle(): int
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            Cache::forget('failed-jobs-alerted-at');
            $this->info('Tidak ada failed job.');

            return self::SUCCESS;
        }

        if (Cache::get('failed-jobs-alerted-at')) {
            $this->warn("Ada {$count} failed job; alert sudah dikirim dalam satu jam terakhir.");

            return self::SUCCESS;
        }

        User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->get()
            ->each(function (User $admin) use ($count) {
                $admin->notify(new SystemAlertNotification(
                    'Job gagal',
                    "Ada {$count} job gagal. Periksa failed_jobs dan jalankan retry setelah penyebabnya diperbaiki.",
                    '/admin/audit',
                ));
            });

        Cache::put('failed-jobs-alerted-at', now(), 3600);
        $this->warn("Alert failed job dikirim ke admin: {$count}");

        return self::SUCCESS;
    }
}
