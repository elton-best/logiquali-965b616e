<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraint first
        Schema::table('risks', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
        });
        
        // Make column nullable
        DB::statement('ALTER TABLE risks ALTER COLUMN responsible_id DROP NOT NULL');
        
        // Re-add foreign key
        Schema::table('risks', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Drop foreign key
        Schema::table('risks', function (Blueprint $table) {
            $table->dropForeign(['responsible_id']);
        });
        
        // Make column NOT NULL again
        DB::statement('ALTER TABLE risks ALTER COLUMN responsible_id SET NOT NULL');
        
        // Re-add foreign key
        Schema::table('risks', function (Blueprint $table) {
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
