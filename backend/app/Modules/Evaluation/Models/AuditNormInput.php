<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditNormInput extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'norm_reference',
        'clause',
        'requirement',
        'evidence_required',
        'is_mandatory',
        'verification_status',
        'notes',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    /**
     * Verification statuses
     */
    const STATUS_NOT_CHECKED = 'not_checked';
    const STATUS_CONFORM = 'conform';
    const STATUS_NON_CONFORM = 'non_conform';
    const STATUS_PARTIAL = 'partial';
    const STATUS_NA = 'na';

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Scopes
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeNotChecked($query)
    {
        return $query->where('verification_status', self::STATUS_NOT_CHECKED);
    }

    public function scopeNonConform($query)
    {
        return $query->where('verification_status', self::STATUS_NON_CONFORM);
    }

    /**
     * Get common ISO norms with clauses
     */
    public static function getIso9001Clauses(): array
    {
        return [
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '4.1', 'requirement' => 'Compréhension de l\'organisme et de son contexte', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '4.2', 'requirement' => 'Compréhension des besoins et attentes des parties intéressées', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '4.3', 'requirement' => 'Détermination du domaine d\'application du SMQ', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '4.4', 'requirement' => 'Système de management de la qualité et ses processus', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '5.1', 'requirement' => 'Leadership et engagement', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '5.2', 'requirement' => 'Politique qualité', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '5.3', 'requirement' => 'Rôles, responsabilités et autorités', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '6.1', 'requirement' => 'Actions à mettre en œuvre face aux risques et opportunités', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '6.2', 'requirement' => 'Objectifs qualité et planification', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '6.3', 'requirement' => 'Planification des modifications', 'is_mandatory' => false],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '7.1', 'requirement' => 'Ressources', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '7.2', 'requirement' => 'Compétences', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '7.3', 'requirement' => 'Sensibilisation', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '7.4', 'requirement' => 'Communication', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '7.5', 'requirement' => 'Informations documentées', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.1', 'requirement' => 'Planification et maîtrise opérationnelles', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.2', 'requirement' => 'Exigences relatives aux produits et services', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.3', 'requirement' => 'Conception et développement', 'is_mandatory' => false],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.4', 'requirement' => 'Maîtrise des processus externalisés', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.5', 'requirement' => 'Production et prestation de service', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.6', 'requirement' => 'Libération des produits et services', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '8.7', 'requirement' => 'Maîtrise des éléments de sortie non conformes', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '9.1', 'requirement' => 'Surveillance, mesure, analyse et évaluation', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '9.2', 'requirement' => 'Audit interne', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '9.3', 'requirement' => 'Revue de direction', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '10.1', 'requirement' => 'Généralités - Amélioration', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '10.2', 'requirement' => 'Non-conformité et action corrective', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 9001:2015', 'clause' => '10.3', 'requirement' => 'Amélioration continue', 'is_mandatory' => true],
        ];
    }

    public static function getIso14001Clauses(): array
    {
        return [
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '4.1', 'requirement' => 'Compréhension de l\'organisme et de son contexte', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '4.2', 'requirement' => 'Compréhension des besoins et attentes des parties intéressées', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '4.3', 'requirement' => 'Détermination du domaine d\'application du SME', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '6.1.2', 'requirement' => 'Aspects environnementaux', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '6.1.3', 'requirement' => 'Obligations de conformité', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '8.1', 'requirement' => 'Planification et maîtrise opérationnelles', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '8.2', 'requirement' => 'Préparation et réponse aux situations d\'urgence', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 14001:2015', 'clause' => '9.1.2', 'requirement' => 'Évaluation de la conformité', 'is_mandatory' => true],
        ];
    }

    public static function getIso45001Clauses(): array
    {
        return [
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '4.1', 'requirement' => 'Compréhension de l\'organisme et de son contexte', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '5.4', 'requirement' => 'Consultation et participation des travailleurs', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '6.1.2', 'requirement' => 'Identification des dangers et évaluation des risques', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '6.1.3', 'requirement' => 'Détermination des exigences légales', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '8.1.2', 'requirement' => 'Élimination des dangers et réduction des risques', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '8.2', 'requirement' => 'Préparation et réponse aux situations d\'urgence', 'is_mandatory' => true],
            ['norm_reference' => 'ISO 45001:2018', 'clause' => '10.2', 'requirement' => 'Événement indésirable, non-conformité et action corrective', 'is_mandatory' => true],
        ];
    }
}
