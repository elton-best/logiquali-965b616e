<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (!Schema::hasColumn('enterprises', 'owner_user_id')) {
                $table->foreignId('owner_user_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('enterprises')
            ->whereNull('owner_user_id')
            ->whereNotNull('created_by')
            ->update(['owner_user_id' => DB::raw('created_by')]);
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (Schema::hasColumn('enterprises', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }
        });
    }
};
