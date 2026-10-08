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
        Schema::table('reclamations', function (Blueprint $table) {
            // Alias pour customer_* fields (en complément des client_* existants)
            $table->string('customer_name')->nullable()->after('client_name');
            $table->text('customer_address')->nullable()->after('customer_name');
            $table->string('customer_phone')->nullable()->after('customer_address');
            $table->string('customer_email')->nullable()->after('customer_phone');
            
            // Préférences de réponse Client B
            $table->boolean('wants_email_response')->default(true)->after('wants_mail');
            
            // Solutions attendues
            $table->text('expected_solution')->nullable()->after('immediate_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_address',
                'customer_phone',
                'customer_email',
                'wants_email_response',
                'expected_solution'
            ]);
        });
    }
};
