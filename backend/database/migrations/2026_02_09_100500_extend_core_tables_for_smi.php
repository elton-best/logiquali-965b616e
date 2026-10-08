<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // processes extensions
        if (!Schema::hasColumn('processes', 'turtle_diagram')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->jsonb('turtle_diagram')->nullable()->after('interfaces');
                $table->foreignId('process_owner_id')->nullable()->after('pilot_id')->constrained('users')->nullOnDelete();
                $table->string('process_type', 100)->nullable()->after('type');
                $table->integer('sequence_order')->nullable()->after('order');
                $table->jsonb('interactions')->nullable()->after('interfaces');
            });
        }

        // documents extensions
        if (!Schema::hasColumn('documents', 'source_type')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->string('source_type', 50)->default('uploaded')->after('type');
                $table->unsignedBigInteger('template_id')->nullable()->after('source_type');
                $table->jsonb('metadata')->nullable()->after('template_id');
                $table->string('generation_context', 100)->nullable()->after('metadata');
            });
        }

        // audits extensions
        if (!Schema::hasColumn('audits', 'risk_based_priority')) {
            Schema::table('audits', function (Blueprint $table) {
                $table->integer('risk_based_priority')->nullable()->after('status');
            });
        }

        // audit_findings extensions
        if (!Schema::hasColumn('audit_findings', 'auto_create_nc')) {
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->boolean('auto_create_nc')->default(true)->after('status');
            });
        }

        // management_reviews extensions
        if (!Schema::hasColumn('management_reviews', 'input_data')) {
            Schema::table('management_reviews', function (Blueprint $table) {
                $table->jsonb('input_data')->nullable()->after('audit_data');
                $table->jsonb('output_decisions')->nullable()->after('input_data');
                $table->jsonb('action_ids')->nullable()->after('output_decisions');
                $table->string('status_workflow', 50)->nullable()->after('status');
                $table->timestamp('validated_by_ceo_at')->nullable()->after('status');
                $table->foreignId('validated_by_ceo_user_id')->nullable()->after('validated_by_ceo_at')->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable()->after('report_path');
            });
        }

        // non_conformities extensions
        if (!Schema::hasColumn('non_conformities', 'root_cause_analysis')) {
            Schema::table('non_conformities', function (Blueprint $table) {
                $table->jsonb('root_cause_analysis')->nullable()->after('detected_at');
            });
        }

        // actions extensions
        if (!Schema::hasColumn('actions', 'proof_path')) {
            Schema::table('actions', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
                $table->string('proof_path', 500)->nullable()->after('verification_date');
                $table->integer('delay_alert_threshold')->default(10)->after('proof_path');
                $table->integer('delay_days')->nullable()->after('delay_alert_threshold');
                $table->integer('progress_rate')->nullable()->after('progress');
            });
        }

        // users extensions for onboarding
        if (!Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('password');
                $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            });
        }

        // enterprises extensions for onboarding
        if (!Schema::hasColumn('enterprises', 'domaine_activite')) {
            Schema::table('enterprises', function (Blueprint $table) {
                $table->string('domaine_activite')->nullable()->after('industry');
                $table->boolean('domaine_activite_set')->default(false)->after('domaine_activite');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('processes', 'turtle_diagram')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->dropForeign(['process_owner_id']);
                $table->dropColumn(['turtle_diagram', 'process_owner_id', 'process_type', 'sequence_order', 'interactions']);
            });
        }

        if (Schema::hasColumn('documents', 'source_type')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn(['source_type', 'template_id', 'metadata', 'generation_context']);
            });
        }

        if (Schema::hasColumn('audits', 'risk_based_priority')) {
            Schema::table('audits', function (Blueprint $table) {
                $table->dropColumn('risk_based_priority');
            });
        }

        if (Schema::hasColumn('audit_findings', 'auto_create_nc')) {
            Schema::table('audit_findings', function (Blueprint $table) {
                $table->dropColumn('auto_create_nc');
            });
        }

        if (Schema::hasColumn('management_reviews', 'input_data')) {
            Schema::table('management_reviews', function (Blueprint $table) {
                $table->dropForeign(['validated_by_ceo_user_id']);
                $table->dropColumn([
                    'input_data', 'output_decisions', 'action_ids',
                    'status_workflow', 'validated_by_ceo_at',
                    'validated_by_ceo_user_id', 'generated_at'
                ]);
            });
        }

        if (Schema::hasColumn('actions', 'proof_path')) {
            Schema::table('actions', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn(['enterprise_id', 'proof_path', 'delay_alert_threshold', 'delay_days', 'progress_rate']);
            });
        }

        if (Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['must_change_password', 'password_changed_at']);
            });
        }

        if (Schema::hasColumn('enterprises', 'domaine_activite')) {
            Schema::table('enterprises', function (Blueprint $table) {
                $table->dropColumn(['domaine_activite', 'domaine_activite_set']);
            });
        }
    }
};
