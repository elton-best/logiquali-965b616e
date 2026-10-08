<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('improvement_suggestions', function (Blueprint $table) {
            $table->json('normes')->nullable()->after('impact');
            $table->foreignId('assigned_to')->nullable()->after('proposer_id')->constrained('users')->nullOnDelete();
            $table->text('validation_comment')->nullable()->after('follow_up');
        });
    }

    public function down(): void
    {
        Schema::table('improvement_suggestions', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['normes', 'assigned_to', 'validation_comment']);
        });
    }
};
