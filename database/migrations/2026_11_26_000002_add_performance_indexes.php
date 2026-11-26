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

        DB::statement('CREATE INDEX IF NOT EXISTS action_items_meeting_id_index ON action_items (meeting_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS decisions_meeting_id_index ON decisions (meeting_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS decisions_topic_id_decided_at_index ON decisions (topic_id, decided_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS attachments_meeting_id_index ON attachments (meeting_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS attachments_uploaded_by_index ON attachments (uploaded_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS meeting_participants_user_id_index ON meeting_participants (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_created_by_index ON meetings (created_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_meeting_template_id_index ON meetings (meeting_template_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_approved_by_index ON meetings (approved_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS ai_summaries_division_id_index ON ai_summaries (division_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ai_summaries_generated_by_index ON ai_summaries (generated_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS ai_summaries_approved_by_index ON ai_summaries (approved_by)');
        DB::statement('CREATE INDEX IF NOT EXISTS audit_logs_user_id_index ON audit_logs (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_content_text_trgm_index ON meetings USING gin (content_text gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS quick_notes_content_text_trgm_index ON quick_notes USING gin (content_text gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS action_items_meeting_id_index');
        DB::statement('DROP INDEX IF EXISTS decisions_meeting_id_index');
        DB::statement('DROP INDEX IF EXISTS decisions_topic_id_decided_at_index');
        DB::statement('DROP INDEX IF EXISTS attachments_meeting_id_index');
        DB::statement('DROP INDEX IF EXISTS attachments_uploaded_by_index');
        DB::statement('DROP INDEX IF EXISTS meeting_participants_user_id_index');
        DB::statement('DROP INDEX IF EXISTS meetings_created_by_index');
        DB::statement('DROP INDEX IF EXISTS meetings_meeting_template_id_index');
        DB::statement('DROP INDEX IF EXISTS meetings_approved_by_index');
        DB::statement('DROP INDEX IF EXISTS ai_summaries_division_id_index');
        DB::statement('DROP INDEX IF EXISTS ai_summaries_generated_by_index');
        DB::statement('DROP INDEX IF EXISTS ai_summaries_approved_by_index');
        DB::statement('DROP INDEX IF EXISTS audit_logs_user_id_index');
        DB::statement('DROP INDEX IF EXISTS meetings_content_text_trgm_index');
        DB::statement('DROP INDEX IF EXISTS quick_notes_content_text_trgm_index');
    }
};
