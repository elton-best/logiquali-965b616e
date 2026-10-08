<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            if (!Schema::hasColumn('application_scopes', 'norm_exclusions')) {
                $table->json('norm_exclusions')->nullable()->after('iso_exclusions_justification');
            }
            if (!Schema::hasColumn('application_scopes', 'norm_exclusions_justifications')) {
                $table->json('norm_exclusions_justifications')->nullable()->after('norm_exclusions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('application_scopes', 'norm_exclusions')) {
                $dropColumns[] = 'norm_exclusions';
            }
            if (Schema::hasColumn('application_scopes', 'norm_exclusions_justifications')) {
                $dropColumns[] = 'norm_exclusions_justifications';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
