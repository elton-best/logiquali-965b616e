<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'enterprise_id')) {
                $table->foreignId('enterprise_id')->nullable()->after('site_id')->constrained('enterprises')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'enterprise_id')) {
                $table->dropConstrainedForeignId('enterprise_id');
            }
        });
    }
};
