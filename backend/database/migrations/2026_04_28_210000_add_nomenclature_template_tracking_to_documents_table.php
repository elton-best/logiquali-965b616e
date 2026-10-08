<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'nomenclature_template_id')) {
                $table->foreignId('nomenclature_template_id')
                    ->nullable()
                    ->after('template_id')
                    ->constrained('nomenclature_templates')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('documents', 'nomenclature_template_version')) {
                $table->unsignedInteger('nomenclature_template_version')
                    ->nullable()
                    ->after('nomenclature_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'nomenclature_template_id')) {
                $table->dropConstrainedForeignId('nomenclature_template_id');
            }
            if (Schema::hasColumn('documents', 'nomenclature_template_version')) {
                $table->dropColumn('nomenclature_template_version');
            }
        });
    }
};
