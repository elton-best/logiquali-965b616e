<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actionables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->morphs('actionable'); // actionable_type, actionable_id
            $table->timestamps();

            $table->unique(['action_id', 'actionable_id', 'actionable_type'], 'actionables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actionables');
    }
};
