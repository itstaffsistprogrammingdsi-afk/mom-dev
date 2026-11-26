<?php

namespace App\Console\Commands;

use App\Enums\Confidentiality;
use App\Enums\TagType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckIntegrity extends Command
{
    protected $signature = 'mom:check-integrity {--fix-participants : Hapus duplikat peserta setelah laporan}';

    protected $description = 'Periksa duplikat peserta, nomor notulen, enum, tag yatim, dan JSON notifikasi';

    public function handle(): int
    {
        $duplicateParticipants = DB::table('meeting_participants')
            ->select('meeting_id', 'user_id', DB::raw('COUNT(*) AS total'), DB::raw('MIN(id) AS keep_id'))
            ->whereNotNull('user_id')
            ->groupBy('meeting_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->line('Duplikat peserta: '.$duplicateParticipants->count());

        if ($duplicateParticipants->isNotEmpty() && $this->option('fix-participants')) {
            DB::transaction(function () use ($duplicateParticipants) {
                foreach ($duplicateParticipants as $duplicate) {
                    DB::table('meeting_participants')
                        ->where('meeting_id', $duplicate->meeting_id)
                        ->where('user_id', $duplicate->user_id)
                        ->where('id', '!=', $duplicate->keep_id)
                        ->delete();
                }
            });

            $this->info('Duplikat peserta dibersihkan (dry-run tidak dijalankan).');
        }

        $duplicateNumbers = DB::table('meetings')
            ->select('nomor_notulensi', DB::raw('COUNT(*) AS total'))
            ->whereNotNull('nomor_notulensi')
            ->where('nomor_notulensi', '!=', '')
            ->groupBy('nomor_notulensi')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->line('Nomor notulen duplikat: '.$duplicateNumbers->count());

        $duplicateSummaries = DB::table('ai_summaries')
            ->select('meeting_id', 'version', DB::raw('COUNT(*) AS total'))
            ->whereNotNull('meeting_id')
            ->where('scope', 'meeting')
            ->groupBy('meeting_id', 'version')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->line('Resume AI duplikat (meeting + versi): '.$duplicateSummaries->count());

        $this->reportEnum('meetings', 'confidentiality', array_column(Confidentiality::cases(), 'value'));
        $this->reportEnum('meetings', 'status', ['draft', 'final', 'locked']);
        $this->reportEnum('tags', 'type', array_column(TagType::cases(), 'value'));
        $this->reportEnum('action_items', 'status', ['new', 'in_progress', 'done', 'cancelled']);

        $orphanTags = DB::table('tags')
            ->whereNotNull('target_id')
            ->where(function ($query) {
                $query->where(function ($person) {
                    $person->where('type', 'person')
                        ->whereNotIn('target_id', DB::table('users')->select('id'));
                })->orWhere(function ($division) {
                    $division->where('type', 'division')
                        ->whereNotIn('target_id', DB::table('divisions')->select('id'));
                })->orWhere(function ($topic) {
                    $topic->where('type', 'topic')
                        ->whereNotIn('target_id', DB::table('topics')->select('id'));
                });
            })
            ->count();

        $this->line('Tag yatim: '.$orphanTags);

        $invalidNotifications = DB::table('notifications')
            ->get()
            ->filter(fn ($notification) => json_decode((string) $notification->data, true) === null)
            ->count();

        $this->line('Notifikasi dengan JSON tidak valid: '.$invalidNotifications);

        return self::SUCCESS;
    }

    private function reportEnum(string $table, string $column, array $allowed): void
    {
        $values = DB::table($table)->distinct()->pluck($column)->filter()->values();
        $invalid = $values->diff($allowed);

        $this->line("Nilai {$table}.{$column} di luar enum: ".$invalid->implode(', '));
    }
}
