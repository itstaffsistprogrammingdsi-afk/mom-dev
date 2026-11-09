<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->timestamp('content_updated_at')->nullable()->after('content_text');
        });

        Schema::create('meeting_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->jsonb('content')->nullable();
            $table->longText('content_text')->nullable();
            $table->string('reason')->default('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['meeting_id', 'version']);
        });

        Schema::table('ai_summaries', function (Blueprint $table) {
            $table->string('generation_status')->default('completed')->after('status')->index();
            $table->text('error_message')->nullable()->after('generation_status');
            $table->jsonb('sources')->nullable()->after('content');
            $table->jsonb('usage')->nullable()->after('sources');
            $table->timestamp('started_at')->nullable()->after('approved_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });

        Schema::table('action_items', function (Blueprint $table) {
            $table->timestamp('reminder_h3_sent_at')->nullable()->after('completed_at');
            $table->timestamp('reminder_h1_sent_at')->nullable()->after('reminder_h3_sent_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('reminder_h1_sent_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('notification_preferences')->nullable()->after('is_active');
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->string('source')->default('mention')->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });

        Schema::table('action_items', function (Blueprint $table) {
            $table->dropColumn(['reminder_h3_sent_at', 'reminder_h1_sent_at', 'overdue_notified_at']);
        });

        Schema::table('ai_summaries', function (Blueprint $table) {
            $table->dropColumn(['generation_status', 'error_message', 'sources', 'usage', 'started_at', 'completed_at']);
        });

        Schema::dropIfExists('meeting_versions');

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['version', 'content_updated_at']);
        });
    }
};
