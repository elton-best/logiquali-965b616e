<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_settings', function (Blueprint $table) {
            $table->id();
            $table->json('general')->nullable();
            $table->json('email')->nullable();
            $table->json('security')->nullable();
            $table->json('system')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_settings');
    }
};
