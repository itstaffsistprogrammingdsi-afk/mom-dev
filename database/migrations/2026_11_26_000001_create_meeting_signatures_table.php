<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('signature_path');
            $table->string('original_name')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['meeting_id', 'role']);
            $table->index('signed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_signatures');
    }
};
