<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codification_elements', function (Blueprint $table) {
            if (!Schema::hasColumn('codification_elements', 'enterprise_id')) {
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('enterprises')
                    ->nullOnDelete();
            }
        });

        // Best-effort migration legacy data to enterprise scope.
        $elements = DB::table('codification_elements')->select('id')->get();
        foreach ($elements as $element) {
            $enterpriseIds = DB::table('equipements')
                ->where(function ($query) use ($element) {
                    $query->where('categorie_id', $element->id)
                        ->orWhere('localisation_id', $element->id);
                })
                ->whereNotNull('enterprise_id')
                ->distinct()
                ->pluck('enterprise_id')
                ->filter()
                ->values();

            if ($enterpriseIds->count() === 1) {
                DB::table('codification_elements')
                    ->where('id', $element->id)
                    ->update(['enterprise_id' => $enterpriseIds->first()]);
                continue;
            }

            if ($enterpriseIds->count() <= 1) {
                continue;
            }

            $baseElement = DB::table('codification_elements')->where('id', $element->id)->first();
            if (!$baseElement) {
                continue;
            }

            $firstEnterpriseId = (int) $enterpriseIds->first();
            DB::table('codification_elements')
                ->where('id', $element->id)
                ->update(['enterprise_id' => $firstEnterpriseId]);

            foreach ($enterpriseIds->slice(1) as $enterpriseId) {
                $newId = DB::table('codification_elements')->insertGetId([
                    'enterprise_id' => $enterpriseId,
                    'type' => $baseElement->type,
                    'code' => $baseElement->code,
                    'libelle' => $baseElement->libelle,
                    'description' => $baseElement->description,
                    'actif' => $baseElement->actif,
                    'created_at' => $baseElement->created_at,
                    'updated_at' => $baseElement->updated_at,
                ]);

                DB::table('equipements')
                    ->where('enterprise_id', $enterpriseId)
                    ->where('categorie_id', $element->id)
                    ->update(['categorie_id' => $newId]);

                DB::table('equipements')
                    ->where('enterprise_id', $enterpriseId)
                    ->where('localisation_id', $element->id)
                    ->update(['localisation_id' => $newId]);
            }
        }

        Schema::table('codification_elements', function (Blueprint $table) {
            $table->dropUnique('codification_elements_type_code_unique');
            $table->unique(['enterprise_id', 'type', 'code'], 'codification_elements_enterprise_type_code_unique');
            $table->index(['enterprise_id', 'type'], 'codification_elements_enterprise_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('codification_elements', function (Blueprint $table) {
            $table->dropIndex('codification_elements_enterprise_type_index');
            $table->dropUnique('codification_elements_enterprise_type_code_unique');
            $table->unique(['type', 'code']);
            $table->dropConstrainedForeignId('enterprise_id');
        });
    }
};
