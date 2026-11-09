<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX IF NOT EXISTS meetings_title_trgm_index ON meetings USING gin (title gin_trgm_ops)');

        $hasVector = DB::selectOne("SELECT 1 AS ok FROM pg_available_extensions WHERE name = 'vector'");

        if (! $hasVector) {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        if (Schema::hasTable('document_embeddings')) {
            return;
        }

        DB::statement('
            CREATE TABLE document_embeddings (
                id BIGSERIAL PRIMARY KEY,
                meeting_id BIGINT NULL REFERENCES meetings(id) ON DELETE CASCADE,
                source_type VARCHAR(255) NOT NULL,
                source_id BIGINT NULL,
                content TEXT NOT NULL,
                embedding VECTOR(1536) NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )
        ');

        DB::statement('CREATE INDEX document_embeddings_meeting_id_index ON document_embeddings (meeting_id)');
        DB::statement('CREATE INDEX document_embeddings_embedding_hnsw_index ON document_embeddings USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS document_embeddings');
        DB::statement('DROP INDEX IF EXISTS meetings_title_trgm_index');
    }
};
