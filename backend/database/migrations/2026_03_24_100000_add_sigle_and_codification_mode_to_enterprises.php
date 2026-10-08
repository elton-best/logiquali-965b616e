<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->string('sigle', 10)->nullable()->after('name');
            $table->string('codification_mode', 20)->default('standard')->after('sigle');
            $table->index(['codification_mode'], 'enterprises_codification_mode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->dropIndex('enterprises_codification_mode_idx');
            $table->dropColumn(['sigle', 'codification_mode']);
        });
    }
};
