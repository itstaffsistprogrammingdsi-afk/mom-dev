<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            $table->timestamp('first_responded_at')->nullable();
        });

        Schema::create('action_item_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_item_id')->constrained('action_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['action_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_item_feedback');

        Schema::table('action_items', function (Blueprint $table) {
            $table->dropColumn('first_responded_at');
        });
    }
};
