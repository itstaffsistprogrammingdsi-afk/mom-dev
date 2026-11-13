<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('response_hours')->nullable()->after('due_date');
            $table->timestamp('response_due_at')->nullable()->after('response_hours');

            $table->index(['response_due_at', 'first_responded_at']);
        });

        Schema::table('action_item_feedback', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('body');
            $table->string('photo_original_name')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('action_item_feedback', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_original_name']);
        });

        Schema::table('action_items', function (Blueprint $table) {
            $table->dropIndex(['response_due_at', 'first_responded_at']);
            $table->dropColumn(['response_hours', 'response_due_at']);
        });
    }
};
