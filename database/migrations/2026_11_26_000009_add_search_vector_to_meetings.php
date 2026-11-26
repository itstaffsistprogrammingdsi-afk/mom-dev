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

        DB::statement(<<<'SQL'
            ALTER TABLE meetings
            ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('simple', coalesce(agenda, '')), 'B') ||
                setweight(to_tsvector('simple', coalesce(content_text, '')), 'C')
            ) STORED
        SQL);

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS meetings_search_vector_gin ON meetings USING gin (search_vector)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS meetings_search_vector_gin');
        DB::statement('ALTER TABLE meetings DROP COLUMN IF EXISTS search_vector');
    }
};
