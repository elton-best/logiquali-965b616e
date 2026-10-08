<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 64);
            $table->string('designation');
            $table->string('provider_type', 64)->default('autre');
            $table->string('legal_form')->nullable();
            $table->text('service_offers')->nullable();
            $table->string('phone_primary', 64)->nullable();
            $table->string('phone_secondary', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('ifu', 128)->nullable();
            $table->unsignedInteger('experience_years')->nullable();
            $table->text('evaluation_observation')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enterprise_id', 'reference'], 'provider_partners_enterprise_reference_unique');
            $table->index(['enterprise_id', 'designation'], 'provider_partners_enterprise_designation_index');
        });

        Schema::create('provider_contract_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Canevas contrat prestataire');
            $table->longText('content');
            $table->json('placeholders')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique('enterprise_id');
        });

        Schema::create('provider_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_partner_id')->constrained()->cascadeOnDelete();
            $table->string('contract_reference', 128)->nullable();
            $table->string('template_title')->nullable();
            $table->longText('template_content')->nullable();
            $table->longText('filled_content')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('XOF');
            $table->text('payment_terms')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->string('signed_file_name')->nullable();
            $table->string('status', 32)->default('draft');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->unique('provider_partner_id');
            $table->index(['enterprise_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_contracts');
        Schema::dropIfExists('provider_contract_templates');
        Schema::dropIfExists('provider_partners');
    }
};

