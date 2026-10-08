<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->integer('plan_year')->nullable()->after('date_fin');
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE formations MODIFY date_debut DATE NULL');
            DB::statement('ALTER TABLE formations MODIFY date_fin DATE NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE formations ALTER COLUMN date_debut DROP NOT NULL');
            DB::statement('ALTER TABLE formations ALTER COLUMN date_fin DROP NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropColumn('plan_year');
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE formations MODIFY date_debut DATE NOT NULL');
            DB::statement('ALTER TABLE formations MODIFY date_fin DATE NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE formations ALTER COLUMN date_debut SET NOT NULL');
            DB::statement('ALTER TABLE formations ALTER COLUMN date_fin SET NOT NULL');
        }
    }
};
