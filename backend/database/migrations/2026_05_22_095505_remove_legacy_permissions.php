<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Database\Seeders\PermissionCatalogSeeder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On récupère le catalogue valide via Reflection car getCatalog est private
        $seeder = new PermissionCatalogSeeder();
        $reflection = new \ReflectionClass($seeder);
        $method = $reflection->getMethod('getCatalog');
        $method->setAccessible(true);
        $validPermissions = $method->invoke($seeder);

        // On supprime toutes les permissions qui ne sont pas dans le catalogue
        DB::table('permissions')
            ->whereNotIn('name', $validPermissions)
            ->delete();

        // Le cache des permissions Spatie doit être vidé
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // La restauration des permissions legacy n'est pas supportée de manière automatisée
    }
};

