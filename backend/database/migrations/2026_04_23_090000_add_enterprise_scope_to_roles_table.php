<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'enterprise_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('guard_name')
                    ->constrained('enterprises')
                    ->nullOnDelete();
                $table->index('enterprise_id');
            });
        }

        DB::table('roles')
            ->select(['id', 'name'])
            ->whereNull('enterprise_id')
            ->where('name', 'like', 'custom_enterprise_%')
            ->orderBy('id')
            ->chunkById(200, function ($roles): void {
                foreach ($roles as $role) {
                    if (!preg_match('/^custom_enterprise_(\d+)_/i', (string) $role->name, $matches)) {
                        continue;
                    }

                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update([
                            'enterprise_id' => (int) $matches[1],
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('roles', 'enterprise_id')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['enterprise_id']);
            $table->dropIndex(['enterprise_id']);
            $table->dropColumn('enterprise_id');
        });
    }
};
