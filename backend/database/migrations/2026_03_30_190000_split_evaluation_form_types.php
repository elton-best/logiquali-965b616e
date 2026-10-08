<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $criteriaAllowed = [
            'satisfaction_client',
            'satisfaction_personnel',
            'performance_personnel',
            'evaluation_personnel',
            'evaluation_auditeur',
            'satisfaction_fournisseur',
            'performance_fournisseur',
            'evaluation_fournisseur',
            'audit_interne',
            'custom',
        ];

        $requestAllowed = [
            'satisfaction_client',
            'satisfaction_personnel',
            'performance_personnel',
            'evaluation_personnel',
            'evaluation_auditeur',
            'satisfaction_fournisseur',
            'performance_fournisseur',
            'evaluation_fournisseur',
            'audit_interne',
            'custom',
        ];

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $this->replacePgsqlCheckConstraint('evaluation_criteria', 'form_type', $criteriaAllowed, 'custom');
            $this->replacePgsqlCheckConstraint('evaluation_requests', 'type', $requestAllowed, 'satisfaction_client');
        } elseif ($driver === 'mysql') {
            $criteriaEnum = implode("','", $criteriaAllowed);
            DB::statement("ALTER TABLE evaluation_criteria MODIFY form_type ENUM('{$criteriaEnum}') NOT NULL DEFAULT 'custom'");

            $requestEnum = implode("','", $requestAllowed);
            DB::statement("ALTER TABLE evaluation_requests MODIFY type ENUM('{$requestEnum}') NOT NULL DEFAULT 'satisfaction_client'");
        }

        // Dupliquer les critères legacy pour garder un jeu dédié en performance
        $this->duplicateCriteriaByType('evaluation_personnel', 'performance_personnel');
        $this->duplicateCriteriaByType('evaluation_fournisseur', 'performance_fournisseur');

        // Mapper les anciens critères vers les jeux satisfaction dédiés
        DB::table('evaluation_criteria')
            ->where('form_type', 'evaluation_personnel')
            ->update(['form_type' => 'satisfaction_personnel']);

        DB::table('evaluation_criteria')
            ->where('form_type', 'evaluation_fournisseur')
            ->update(['form_type' => 'satisfaction_fournisseur']);
    }

    public function down(): void
    {
        DB::table('evaluation_criteria')
            ->whereIn('form_type', ['satisfaction_personnel', 'performance_personnel'])
            ->update(['form_type' => 'evaluation_personnel']);

        DB::table('evaluation_criteria')
            ->whereIn('form_type', ['satisfaction_fournisseur', 'performance_fournisseur'])
            ->update(['form_type' => 'evaluation_fournisseur']);

        DB::table('evaluation_requests')
            ->whereIn('type', ['satisfaction_personnel', 'performance_personnel'])
            ->update(['type' => 'evaluation_personnel']);

        DB::table('evaluation_requests')
            ->whereIn('type', ['satisfaction_fournisseur', 'performance_fournisseur'])
            ->update(['type' => 'evaluation_fournisseur']);
    }

    private function duplicateCriteriaByType(string $sourceType, string $targetType): void
    {
        $rows = DB::table('evaluation_criteria')->where('form_type', $sourceType)->get();
        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $row) {
            DB::table('evaluation_criteria')->insert([
                'ref' => null,
                'enterprise_id' => $row->enterprise_id,
                'site_id' => $row->site_id,
                'name' => $row->name,
                'code' => $row->code,
                'description' => $row->description,
                'category' => $row->category,
                'scale_type' => $row->scale_type,
                'scale_min' => $row->scale_min,
                'scale_max' => $row->scale_max,
                'scale_labels' => $row->scale_labels,
                'weight' => $row->weight,
                'is_mandatory' => $row->is_mandatory,
                'is_active' => $row->is_active,
                'form_type' => $targetType,
                'display_order' => $row->display_order,
                'created_by' => $row->created_by,
                'updated_by' => $row->updated_by,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }
    }

    private function replacePgsqlCheckConstraint(string $table, string $column, array $allowedValues, string $defaultValue): void
    {
        $constraints = DB::select(
            "SELECT conname
             FROM pg_constraint c
             JOIN pg_class t ON c.conrelid = t.oid
             WHERE t.relname = ? AND c.contype = 'c' AND pg_get_constraintdef(c.oid) ILIKE ?",
            [$table, "%{$column}%"]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint->conname}");
        }

        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(64)");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '{$defaultValue}'");

        $quoted = implode(', ', array_map(static fn (string $value) => "'" . $value . "'", $allowedValues));
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$column}_check CHECK ({$column} IN ({$quoted}))");
    }
};
