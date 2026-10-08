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
        Schema::table('risks', function (Blueprint $table) {
            $table->integer('residual_probability')->nullable();
            $table->integer('residual_gravity')->nullable();
            $table->integer('risk_reduction_percentage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn('residual_probability');
            $table->dropColumn('residual_gravity');
            $table->dropColumn('risk_reduction_percentage');
        });
    }
};
