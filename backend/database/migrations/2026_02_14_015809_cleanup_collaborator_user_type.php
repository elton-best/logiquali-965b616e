<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'collaborator')
            ->update(['user_type' => 'company']);
    }

    public function down(): void
    {
        // Pas de rollback
    }
};
