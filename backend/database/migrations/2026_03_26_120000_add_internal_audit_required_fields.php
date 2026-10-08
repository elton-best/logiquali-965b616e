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
            $table->foreignId('assigned_to')
                ->nullable()
                ->after('lead_auditor_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reference_documents')
                ->nullable()
                ->after('objectives');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('reference_documents');
        });
    }
};
