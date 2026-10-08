<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->foreignId('enterprise_id')->after('id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->after('enterprise_id')->constrained()->onDelete('cascade');
            $table->index(['enterprise_id', 'site_id']);
        });
    }

    public function down(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['enterprise_id']);
            $table->dropIndex(['enterprise_id', 'site_id']);
            $table->dropColumn(['site_id', 'enterprise_id']);
        });
    }
};
