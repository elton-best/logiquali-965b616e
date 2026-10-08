<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('norms', function (Blueprint $table) {
            if (!Schema::hasColumn('norms', 'pdf_file_path')) {
                $table->string('pdf_file_path')->nullable()->after('current_version_id');
            }

            if (!Schema::hasColumn('norms', 'pdf_original_name')) {
                $table->string('pdf_original_name')->nullable()->after('pdf_file_path');
            }

            if (!Schema::hasColumn('norms', 'pdf_uploaded_at')) {
                $table->timestamp('pdf_uploaded_at')->nullable()->after('pdf_original_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('norms', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('norms', 'pdf_uploaded_at') ? 'pdf_uploaded_at' : null,
                Schema::hasColumn('norms', 'pdf_original_name') ? 'pdf_original_name' : null,
                Schema::hasColumn('norms', 'pdf_file_path') ? 'pdf_file_path' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
