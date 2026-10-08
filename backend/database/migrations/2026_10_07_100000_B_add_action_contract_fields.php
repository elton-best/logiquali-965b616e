<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table): void {
            if (!Schema::hasColumn('actions', 'normes')) {
                $table->jsonb('normes')->nullable()->after('source_id');
            }

            if (!Schema::hasColumn('actions', 'replanifiee_de')) {
                $table->foreignId('replanifiee_de')
                    ->nullable()
                    ->after('deadline')
                    ->constrained('actions')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('actions', 'replanifiee_reason')) {
                $table->text('replanifiee_reason')->nullable()->after('replanifiee_de');
            }

            if (!Schema::hasColumn('actions', 'replanifiee_at')) {
                $table->timestamp('replanifiee_at')->nullable()->after('replanifiee_reason');
            }

            if (!Schema::hasColumn('actions', 'replanifiee_by')) {
                $table->foreignId('replanifiee_by')
                    ->nullable()
                    ->after('replanifiee_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            $table->index(['enterprise_id', 'responsible_id', 'deadline'], 'actions_contract_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table): void {
            $table->dropIndex('actions_contract_scope_idx');

            foreach (['replanifiee_by', 'replanifiee_de'] as $foreignColumn) {
                if (Schema::hasColumn('actions', $foreignColumn)) {
                    $table->dropForeign([$foreignColumn]);
                }
            }

            $columns = ['normes', 'replanifiee_de', 'replanifiee_reason', 'replanifiee_at', 'replanifiee_by'];
            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('actions', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
