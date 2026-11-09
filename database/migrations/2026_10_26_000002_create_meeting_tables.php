<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('meeting_type')->default('other')->index();
            $table->text('description')->nullable();
            $table->jsonb('sections')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('meeting_type')->default('other')->index();
            $table->foreignId('meeting_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('agenda')->nullable();
            $table->jsonb('content')->nullable();
            $table->longText('content_text')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('confidentiality')->default('division')->index();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
            $table->index(['division_id', 'scheduled_at']);
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('attendance')->default('invited')->index();
            $table->boolean('is_external')->default(false);
            $table->timestamps();

            $table->index('meeting_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name')->nullable();
            $table->jsonb('position')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['meeting_id', 'type']);
            $table->index(['type', 'target_id']);
        });

        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->foreignId('topic_id')->nullable();
            $table->date('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pic_name')->nullable();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->string('source')->default('manual');
            $table->boolean('ai_confirmed')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['pic_id', 'status']);
            $table->index(['division_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind')->default('file');
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->text('url')->nullable();
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('action_items');
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
        Schema::dropIfExists('meeting_templates');
    }
};
