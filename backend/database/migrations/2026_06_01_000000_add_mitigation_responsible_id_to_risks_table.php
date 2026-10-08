<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne mitigation_responsible_id à la table risks.
     * Cette colonne désigne le responsable du plan de mitigation,
     * distinct du responsable principal (responsible_id).
     */
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->foreignId('mitigation_responsible_id')
                ->nullable()
                ->after('responsible_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropForeign(['mitigation_responsible_id']);
            $table->dropColumn('mitigation_responsible_id');
        });
    }
};
