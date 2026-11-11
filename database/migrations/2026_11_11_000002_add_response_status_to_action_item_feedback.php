<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_item_feedback', function (Blueprint $table) {
            $table->string('response_status')->default('acknowledged');
        });
    }

    public function down(): void
    {
        Schema::table('action_item_feedback', function (Blueprint $table) {
            $table->dropColumn('response_status');
        });
    }
};
