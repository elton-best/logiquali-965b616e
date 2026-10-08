<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            // Drop old foreign key that references old_permissions
            $table->dropForeign('user_permissions_permission_id_foreign');
            
            // Add new foreign key that references permissions
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_permissions', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['permission_id']);
            
            // Restore old foreign key to old_permissions
            $table->foreign('permission_id')
                ->references('id')
                ->on('old_permissions')
                ->onDelete('cascade');
        });
    }
};
