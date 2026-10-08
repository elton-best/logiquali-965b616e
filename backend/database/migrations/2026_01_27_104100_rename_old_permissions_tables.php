<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('permissions', 'old_permissions');
        Schema::rename('roles', 'old_roles');
        
        if (Schema::hasTable('role_user')) {
            Schema::rename('role_user', 'old_role_user');
        }
    }

    public function down(): void
    {
        Schema::rename('old_permissions', 'permissions');
        Schema::rename('old_roles', 'roles');
        
        if (Schema::hasTable('old_role_user')) {
            Schema::rename('old_role_user', 'role_user');
        }
    }
};
