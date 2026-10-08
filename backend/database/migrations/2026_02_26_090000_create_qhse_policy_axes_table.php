<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qhse_policy_axes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qhse_policy_id')->constrained('qhse_policies')->cascadeOnDelete();
            $table->string('axis_name', 255);
            $table->unsignedInteger('axis_order')->default(1);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['qhse_policy_id', 'axis_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qhse_policy_axes');
    }
};
