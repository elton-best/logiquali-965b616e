<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime la table user_permissions custom.
 *
 * Cette table était un doublon des tables Spatie Permission (model_has_permissions).
 * Le système est désormais en mode RBAC pur : les permissions viennent uniquement
 * des rôles assignés via les tables Spatie (model_has_roles, role_has_permissions).
 *
 * Les permissions directes sur les utilisateurs sont interdites.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_permissions');
    }

    public function down(): void
    {
        // Restauration possible si rollback nécessaire
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });
    }
};
