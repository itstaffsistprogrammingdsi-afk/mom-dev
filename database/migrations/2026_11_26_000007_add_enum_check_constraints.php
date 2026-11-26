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

        $constraints = [
            ['meetings_confidentiality_check', 'meetings', "confidentiality IN ('open', 'division', 'restricted', 'secret')"],
            ['meetings_status_check', 'meetings', "status IN ('draft', 'final', 'locked')"],
            ['meetings_document_type_check', 'meetings', "document_type IN ('meeting', 'proposal', 'report', 'brochure', 'other')"],
            ['action_items_status_check', 'action_items', "status IN ('new', 'in_progress', 'done', 'cancelled')"],
            ['action_items_source_check', 'action_items', "source IN ('manual', 'ai')"],
            ['action_items_response_hours_check', 'action_items', "response_hours IS NULL OR response_hours IN (1, 4, 24, 48)"],
            ['meeting_participants_attendance_check', 'meeting_participants', "attendance IN ('invited', 'present', 'absent', 'excused')"],
            ['tags_type_check', 'tags', "type IN ('person', 'division', 'topic')"],
            ['ai_summaries_status_check', 'ai_summaries', "status IN ('draft', 'approved', 'rejected')"],
            ['ai_summaries_generation_status_check', 'ai_summaries', "generation_status IN ('queued', 'processing', 'completed', 'failed')"],
            ['topics_status_check', 'topics', "status IN ('active', 'archived')"],
        ];

        foreach ($constraints as [$name, $table, $expression]) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression}) NOT VALID");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $constraints = [
            'meetings_confidentiality_check',
            'meetings_status_check',
            'meetings_document_type_check',
            'action_items_status_check',
            'action_items_source_check',
            'action_items_response_hours_check',
            'meeting_participants_attendance_check',
            'tags_type_check',
            'ai_summaries_status_check',
            'ai_summaries_generation_status_check',
            'topics_status_check',
        ];

        foreach ($constraints as $name) {
            DB::statement("ALTER TABLE {$this->tableFor($name)} DROP CONSTRAINT IF EXISTS {$name}");
        }
    }

    private function tableFor(string $constraint): string
    {
        return match (true) {
            str_starts_with($constraint, 'meetings_') => 'meetings',
            str_starts_with($constraint, 'action_items_') => 'action_items',
            str_starts_with($constraint, 'meeting_participants_') => 'meeting_participants',
            str_starts_with($constraint, 'ai_summaries_') => 'ai_summaries',
            str_starts_with($constraint, 'tags_') => 'tags',
            default => 'topics',
        };
    }
};
