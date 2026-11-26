<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("CREATE UNIQUE INDEX CONCURRENTLY IF NOT EXISTS ai_summaries_meeting_scope_version_unique ON ai_summaries (meeting_id, scope, version) WHERE meeting_id IS NOT NULL AND scope = 'meeting'");
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS ai_summaries_generated_by_created_at_index ON ai_summaries (generated_by, created_at)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ai_summaries_meeting_scope_version_unique');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS ai_summaries_generated_by_created_at_index');
    }
};
