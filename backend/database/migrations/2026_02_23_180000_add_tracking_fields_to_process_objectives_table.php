<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('process_objectives', 'measurement_frequency')) {
                $table->enum('measurement_frequency', ['monthly', 'quarterly', 'semiannual', 'annual'])
                    ->default('monthly')
                    ->after('target_date');
            }

            if (!Schema::hasColumn('process_objectives', 'period_realizations')) {
                $table->json('period_realizations')->nullable()->after('achievement_percentage');
            }

            if (!Schema::hasColumn('process_objectives', 'notes')) {
                $table->text('notes')->nullable()->after('period_realizations');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('process_objectives', 'measurement_frequency')) {
                $dropColumns[] = 'measurement_frequency';
            }
            if (Schema::hasColumn('process_objectives', 'period_realizations')) {
                $dropColumns[] = 'period_realizations';
            }
            if (Schema::hasColumn('process_objectives', 'notes')) {
                $dropColumns[] = 'notes';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
