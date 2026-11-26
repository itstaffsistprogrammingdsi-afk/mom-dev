<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('notification_preferences');
            $table->string('appearance', 10)->nullable()->after('locale');
            $table->string('timezone', 64)->nullable()->after('appearance');
        });

        DB::table('users')->update([
            'locale' => 'id',
            'appearance' => 'system',
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'appearance', 'timezone']);
        });
    }
};
