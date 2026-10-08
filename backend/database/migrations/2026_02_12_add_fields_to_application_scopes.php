<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            if (!Schema::hasColumn('application_scopes', 'document_objective')) {
                $table->text('document_objective')->nullable()->after('is_current');
            }
            if (!Schema::hasColumn('application_scopes', 'scope_definition')) {
                $table->text('scope_definition')->nullable()->after('scope');
            }
            if (!Schema::hasColumn('application_scopes', 'referenced_documents')) {
                $table->json('referenced_documents')->nullable()->after('document_objective');
            }
            if (!Schema::hasColumn('application_scopes', 'processes')) {
                $table->json('processes')->nullable()->after('included_processes');
            }
            if (!Schema::hasColumn('application_scopes', 'scope_exclusions')) {
                $table->text('scope_exclusions')->nullable()->after('exclusions');
            }
            if (!Schema::hasColumn('application_scopes', 'iso_exclusions')) {
                $table->string('iso_exclusions')->nullable()->after('scope_exclusions');
            }
            if (!Schema::hasColumn('application_scopes', 'iso_exclusions_justification')) {
                $table->text('iso_exclusions_justification')->nullable()->after('iso_exclusions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            $table->dropColumn([
                'document_objective',
                'scope_definition',
                'referenced_documents',
                'processes',
                'scope_exclusions',
                'iso_exclusions',
                'iso_exclusions_justification'
            ]);
        });
    }
};
