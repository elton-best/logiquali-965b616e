<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->string('generates_habilitation_type')->nullable()->after('status');
            $table->integer('habilitation_validity_months')->nullable()->after('generates_habilitation_type');
            $table->string('issuing_authority')->nullable()->after('habilitation_validity_months');
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropColumn(['generates_habilitation_type', 'habilitation_validity_months', 'issuing_authority']);
        });
    }
};