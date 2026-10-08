<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_sequences', function (Blueprint $table) {
            if (!Schema::hasColumn('process_sequences', 'supplier_processes')) {
                $table->json('supplier_processes')->nullable()->after('output_description');
            }

            if (!Schema::hasColumn('process_sequences', 'client_processes')) {
                $table->json('client_processes')->nullable()->after('supplier_processes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_sequences', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('process_sequences', 'supplier_processes')) {
                $columnsToDrop[] = 'supplier_processes';
            }

            if (Schema::hasColumn('process_sequences', 'client_processes')) {
                $columnsToDrop[] = 'client_processes';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
