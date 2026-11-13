<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_note_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quick_note_id')->constrained('quick_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('response_hours')->default(24);
            $table->timestamp('due_at')->nullable();
            $table->string('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['quick_note_id', 'user_id']);
            $table->index(['user_id', 'responded_at']);
            $table->index(['quick_note_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_note_mentions');
    }
};
