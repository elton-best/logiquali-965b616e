<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('application_scopes', 'metadata')) {
            Schema::table('application_scopes', function (Blueprint $table) {
                $table->jsonb('metadata')->nullable()->after('document_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('application_scopes', 'metadata')) {
            Schema::table('application_scopes', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
