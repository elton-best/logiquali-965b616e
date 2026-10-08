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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ref')->unique()->after('id');
            $table->string('username')->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('photo_path')->nullable()->after('phone');
            $table->enum('user_type', ['admin', 'entreprise', 'user', 'collaborator'])->default('user')->after('photo_path');
            $table->foreignId('enterprise_id')->nullable()->constrained()->cascadeOnDelete()->after('user_type');
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete()->after('enterprise_id');
            $table->string('role')->nullable()->after('site_id');
            $table->boolean('is_active')->default(true)->after('role');
            
            $table->foreignId('created_by')->nullable()->after('updated_at');
            $table->foreignId('updated_by')->nullable()->after('created_by');
            $table->foreignId('deleted_by')->nullable()->after('updated_by');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ref', 'username', 'phone', 'photo_path', 'user_type',
                'enterprise_id', 'site_id', 'role', 'is_active',
                'created_by', 'updated_by', 'deleted_by', 'deleted_at'
            ]);
        });
    }
};
