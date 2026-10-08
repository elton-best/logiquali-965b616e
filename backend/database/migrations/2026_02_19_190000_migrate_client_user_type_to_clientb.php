<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'client')
            ->update(['user_type' => 'clientb']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('user_type', 'clientb')
            ->update(['user_type' => 'client']);
    }
};
