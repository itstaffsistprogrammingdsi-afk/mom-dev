<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quick_notes', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->after('user_id')->constrained('divisions')->nullOnDelete();
            $table->string('photo_path')->nullable()->after('content_text');
            $table->string('photo_original_name')->nullable()->after('photo_path');
            $table->index('division_id');
        });

        DB::table('quick_notes')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(100, function ($notes) {
                foreach ($notes as $note) {
                    $divisionId = DB::table('users')->where('id', $note->user_id)->value('division_id');

                    DB::table('quick_notes')->where('id', $note->id)->update(['division_id' => $divisionId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quick_notes', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropIndex(['division_id']);
            $table->dropColumn(['division_id', 'photo_path', 'photo_original_name']);
        });
    }
};
