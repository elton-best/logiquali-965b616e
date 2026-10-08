<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent: ne pas échouer si la permission existe déjà.
        Permission::firstOrCreate(
            [
                'name' => 'import_documents',
                'guard_name' => 'web',
            ],
            [
                'description' => 'Importer des documents existants dans le système de nomenclature',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'import_documents')->delete();
    }
};
