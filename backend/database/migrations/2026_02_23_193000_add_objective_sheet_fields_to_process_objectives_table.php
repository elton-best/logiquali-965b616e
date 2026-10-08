<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('process_objectives', 'strategic_axis')) {
                $table->string('strategic_axis', 20)->nullable()->after('title');
            }

            if (!Schema::hasColumn('process_objectives', 'calculation_mode')) {
                $table->text('calculation_mode')->nullable()->after('description');
            }

            if (!Schema::hasColumn('process_objectives', 'action_plan')) {
                $table->text('action_plan')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('process_objectives', 'responsibles')) {
                $table->text('responsibles')->nullable()->after('action_plan');
            }

            if (!Schema::hasColumn('process_objectives', 'special_resources')) {
                $table->text('special_resources')->nullable()->after('responsibles');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('process_objectives', 'strategic_axis')) {
                $dropColumns[] = 'strategic_axis';
            }
            if (Schema::hasColumn('process_objectives', 'calculation_mode')) {
                $dropColumns[] = 'calculation_mode';
            }
            if (Schema::hasColumn('process_objectives', 'action_plan')) {
                $dropColumns[] = 'action_plan';
            }
            if (Schema::hasColumn('process_objectives', 'responsibles')) {
                $dropColumns[] = 'responsibles';
            }
            if (Schema::hasColumn('process_objectives', 'special_resources')) {
                $dropColumns[] = 'special_resources';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
