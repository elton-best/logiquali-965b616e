<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-7.2-03 : Double signature sur les fiches de poste (CEO + titulaire/employé).
     */
    public function up(): void
    {
        Schema::table('job_descriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('job_descriptions', 'ceo_signature_data')) {
                $table->text('ceo_signature_data')->nullable()->after('manager_signed_at');
            }
            if (!Schema::hasColumn('job_descriptions', 'ceo_signed_at')) {
                $table->timestamp('ceo_signed_at')->nullable()->after('ceo_signature_data');
            }
            if (!Schema::hasColumn('job_descriptions', 'ceo_user_id')) {
                $table->foreignId('ceo_user_id')->nullable()->after('ceo_signed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_descriptions', function (Blueprint $table) {
            if (Schema::hasColumn('job_descriptions', 'ceo_signature_data')) {
                $table->dropColumn('ceo_signature_data');
            }
            if (Schema::hasColumn('job_descriptions', 'ceo_signed_at')) {
                $table->dropColumn('ceo_signed_at');
            }
            if (Schema::hasColumn('job_descriptions', 'ceo_user_id')) {
                $table->dropForeign(['ceo_user_id']);
                $table->dropColumn('ceo_user_id');
            }
        });
    }
};
