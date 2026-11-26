<?php

namespace App\Console\Commands;

use App\Models\Meeting;
use App\Services\MeetingAccessProjector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildMeetingAccess extends Command
{
    protected $signature = 'mom:rebuild-meeting-access {--chunk=500}';

    protected $description = 'Bangun ulang proyeksi akses meeting dari tag divisi dan orang';

    public function handle(MeetingAccessProjector $projector): int
    {
        DB::table('meeting_access')->delete();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                INSERT INTO meeting_access (meeting_id, user_id, source)
                SELECT DISTINCT m.id, u.id, 'division'
                FROM meetings m
                JOIN tags t ON t.meeting_id = m.id AND t.type = 'division'
                JOIN users u ON u.division_id = t.target_id AND u.is_active = true
                WHERE m.confidentiality = 'division'
                ON CONFLICT (meeting_id, user_id) DO NOTHING
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO meeting_access (meeting_id, user_id, source)
                SELECT DISTINCT m.id, u.id, 'person'
                FROM meetings m
                JOIN tags t ON t.meeting_id = m.id AND t.type = 'person'
                JOIN users u ON u.id = t.target_id AND u.is_active = true
                WHERE m.confidentiality = 'restricted'
                ON CONFLICT (meeting_id, user_id) DO NOTHING
            SQL);
        } else {
            Meeting::query()
                ->whereIn('confidentiality', ['division', 'restricted'])
                ->chunkById((int) $this->option('chunk'), fn ($meetings) => $meetings->each(
                    fn (Meeting $meeting) => $projector->sync($meeting)
                ));
        }

        $this->info('Proyeksi akses meeting dibangun ulang: '.DB::table('meeting_access')->count().' baris.');

        return self::SUCCESS;
    }
}
