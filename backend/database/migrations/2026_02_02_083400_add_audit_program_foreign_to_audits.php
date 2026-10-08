<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Ajout de la foreign key vers audit_programs
            // Cette migration doit s'exécuter APRÈS create_audit_programs_table
            if (!Schema::hasColumn('audits', 'audit_program_id')) {
                $table->foreignId('audit_program_id')->nullable()->after('ref')->constrained('audit_programs')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['audit_program_id']);
            $table->dropColumn('audit_program_id');
        });
    }
};
