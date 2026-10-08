<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_procedures', function (Blueprint $table): void {
            if (!Schema::hasColumn('emergency_procedures', 'preparation_measures')) {
                $table->text('preparation_measures')->nullable()->after('title');
            }
            if (!Schema::hasColumn('emergency_procedures', 'responsible_id')) {
                $table->foreignId('responsible_id')->nullable()->after('site_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('emergency_procedures', 'deadline')) {
                $table->date('deadline')->nullable()->after('responsible_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('emergency_procedures', function (Blueprint $table): void {
            if (Schema::hasColumn('emergency_procedures', 'responsible_id')) {
                $table->dropForeign(['responsible_id']);
            }
            $columns = array_values(array_filter(['preparation_measures', 'responsible_id', 'deadline'], fn (string $column): bool => Schema::hasColumn('emergency_procedures', $column)));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
