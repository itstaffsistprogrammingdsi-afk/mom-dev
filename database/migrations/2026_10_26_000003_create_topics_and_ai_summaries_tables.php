<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('decisions', function (Blueprint $table) {
            $table->foreign('topic_id')->references('id')->on('topics')->nullOnDelete();
        });

        Schema::create('ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('scope')->default('meeting')->index();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('audience')->nullable();
            $table->string('language')->default('id');
            $table->jsonb('content');
            $table->string('model')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('draft')->index();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['meeting_id', 'scope']);
            $table->index(['scope', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::table('decisions', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
        });

        Schema::dropIfExists('ai_summaries');
        Schema::dropIfExists('topics');
    }
};
