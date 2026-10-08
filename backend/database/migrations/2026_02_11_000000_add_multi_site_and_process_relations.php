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
        // 1. Ajouter site_id à la table users (si pas déjà fait)
        if (!Schema::hasColumn('users', 'site_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->onDelete('set null');
            });
        }

        // 2. Créer la table user_site_access pour accès multi-sites
        Schema::create('user_site_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'site_id']);
        });

        // 3. Créer la table process_activities
        Schema::create('process_activities', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 50)->unique();
            $table->foreignId('process_id')->constrained('processes')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sequence_order')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('duration_estimated')->nullable()->comment('Duration in minutes');
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Créer la table activity_supplier_processes (processus fournisseurs)
        Schema::create('activity_supplier_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('process_activities')->onDelete('cascade');
            $table->foreignId('supplier_process_id')->constrained('processes')->onDelete('cascade');
            $table->text('input_description')->nullable()->comment('Description de l\'entrée fournie');
            $table->string('input_type', 100)->nullable()->comment('document, matière, information, etc.');
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
            
            $table->unique(['activity_id', 'supplier_process_id'], 'activity_supplier_unique');
        });

        // 5. Créer la table activity_client_processes (processus clients)
        Schema::create('activity_client_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('process_activities')->onDelete('cascade');
            $table->foreignId('client_process_id')->constrained('processes')->onDelete('cascade');
            $table->text('output_description')->nullable()->comment('Description de la sortie fournie');
            $table->string('output_type', 100)->nullable()->comment('document, produit, service, information, etc.');
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
            
            $table->unique(['activity_id', 'client_process_id'], 'activity_client_unique');
        });

        // 6. Ajouter manager_id à la table sites (si pas déjà fait)
        if (!Schema::hasColumn('sites', 'manager_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->foreignId('manager_id')->nullable()->after('email')->constrained('users')->onDelete('set null');
            });
        }

        // 7. Ajouter description aux rôles (si pas déjà fait)
        if (!Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->text('description')->nullable()->after('guard_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_client_processes');
        Schema::dropIfExists('activity_supplier_processes');
        Schema::dropIfExists('process_activities');
        Schema::dropIfExists('user_site_access');
        
        if (Schema::hasColumn('users', 'site_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['site_id']);
                $table->dropColumn('site_id');
            });
        }

        if (Schema::hasColumn('sites', 'manager_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropForeign(['manager_id']);
                $table->dropColumn('manager_id');
            });
        }

        if (Schema::hasColumn('roles', 'description')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
