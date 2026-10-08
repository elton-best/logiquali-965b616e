<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_contracts', function (Blueprint $table) {
            $table->string('generated_file_path')->nullable()->after('signed_file_name');
            $table->string('generated_file_name')->nullable()->after('generated_file_path');
            $table->timestamp('generated_at')->nullable()->after('generated_file_name');
        });
    }

    public function down(): void
    {
        Schema::table('provider_contracts', function (Blueprint $table) {
            $table->dropColumn(['generated_file_path', 'generated_file_name', 'generated_at']);
        });
    }
};
