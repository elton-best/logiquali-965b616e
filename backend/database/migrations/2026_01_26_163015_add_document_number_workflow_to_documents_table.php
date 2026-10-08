<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->unique()->after('code');
            $table->foreignId('workflow_id')->nullable()->after('category_id')
                ->constrained('document_workflows')->nullOnDelete();
            $table->json('tags')->nullable()->after('keywords');
            
            $table->index('document_number');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropIndex(['document_number']);
            $table->dropColumn(['document_number', 'workflow_id', 'tags']);
        });
    }
};
