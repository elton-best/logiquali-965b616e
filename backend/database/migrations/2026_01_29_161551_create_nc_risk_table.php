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
        Schema::create('nc_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('non_conformity_id')->constrained('non_conformities')->onDelete('cascade');
            $table->foreignId('risk_id')->constrained('risks')->onDelete('cascade');
            $table->string('relation_type')->default('realization'); // realization, cause, etc.
            $table->timestamps();
            
            $table->unique(['non_conformity_id', 'risk_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nc_risk');
    }
};
