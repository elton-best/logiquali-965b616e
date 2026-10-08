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
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enterprise_id');
            $table->string('country_code', 2);
            $table->date('date');
            $table->string('name');
            $table->boolean('recurring')->default(false);
            $table->timestamps();

            $table->foreign('enterprise_id')->references('id')->on('enterprises')->onDelete('cascade');
            $table->index(['enterprise_id', 'country_code']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
