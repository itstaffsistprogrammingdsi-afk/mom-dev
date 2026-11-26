<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS meetings_scheduled_at_partial_index ON meetings (scheduled_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_created_at_partial_index ON meetings (created_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_agenda_trgm_index ON meetings USING gin (agenda gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS tags_type_target_meeting_index ON tags (type, target_id, meeting_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS meeting_participants_user_meeting_index ON meeting_participants (user_id, meeting_id)');
        DB::statement("CREATE INDEX IF NOT EXISTS action_items_open_due_date_index ON action_items (due_date) WHERE status NOT IN ('done', 'cancelled')");
        DB::statement('CREATE INDEX IF NOT EXISTS action_items_status_due_date_index ON action_items (status, due_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS action_items_title_trgm_index ON action_items USING gin (title gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_unread_partial_index ON notifications (notifiable_type, notifiable_id) WHERE read_at IS NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS notifications_unread_partial_index');
        DB::statement('DROP INDEX IF EXISTS action_items_title_trgm_index');
        DB::statement('DROP INDEX IF EXISTS action_items_status_due_date_index');
        DB::statement('DROP INDEX IF EXISTS action_items_open_due_date_index');
        DB::statement('DROP INDEX IF EXISTS meeting_participants_user_meeting_index');
        DB::statement('DROP INDEX IF EXISTS tags_type_target_meeting_index');
        DB::statement('DROP INDEX IF EXISTS meetings_agenda_trgm_index');
        DB::statement('DROP INDEX IF EXISTS meetings_created_at_partial_index');
        DB::statement('DROP INDEX IF EXISTS meetings_scheduled_at_partial_index');
    }
};
