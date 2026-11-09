<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_templates', function (Blueprint $table) {
            $table->string('document_type')->default('meeting')->after('name')->index();
            $table->jsonb('content')->nullable()->after('sections');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('document_type')->default('meeting')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });

        Schema::table('meeting_templates', function (Blueprint $table) {
            $table->dropColumn(['document_type', 'content']);
        });
    }
};
