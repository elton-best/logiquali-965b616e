<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessRiskOpportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'process_risks_opportunities';

    protected $fillable = [
        'process_id',
        'type',
        'code',
        'title',
        'description',
        'normes_iso',
        'cause',
        'consequence',
        'aspect_qualite',
        'aspect_environnement',
        'aspect_sante_securite',
        'probabilite',
        'gravite',
        'criticite',
        'niveau',
        'strategie',
        'actions_prevues',
        'planned_actions',
        'action_types',
        'responsible_user_id',
        'target_date',
        'status',
        'probabilite_residuelle',
        'gravite_residuelle',
        'criticite_residuelle',
        'created_by',
    ];

    protected $casts = [
        'aspect_qualite' => 'boolean',
        'aspect_environnement' => 'boolean',
        'aspect_sante_securite' => 'boolean',
        'normes_iso' => 'array',
        'planned_actions' => 'array',
        'action_types' => 'array',
        'target_date' => 'date',
    ];

    /**
     * Compatibilité legacy:
     * expose actions_prevues même si seules planned_actions sont renseignées.
     */
    public function getActionsPrevuesAttribute($value): string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        $planned = $this->planned_actions;
        if (is_array($planned) && !empty($planned)) {
            return implode("\n", array_filter(array_map(
                static function ($item) {
                    if (is_string($item)) {
                        return trim($item);
                    }

                    if (is_array($item)) {
                        $title = trim((string) ($item['title'] ?? $item['action'] ?? ''));
                        if ($title === '') {
                            return '';
                        }

                        $typeLabel = match ($item['action_type'] ?? null) {
                            'preventive' => 'Préventive',
                            'corrective' => 'Corrective',
                            'control' => 'Maîtrise',
                            default => '',
                        };

                        return $typeLabel !== '' ? "[{$typeLabel}] {$title}" : $title;
                    }

                    return '';
                },
                $planned
            )));
        }

        return '';
    }

    // Relations
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeRisks($query)
    {
        return $query->where('type', 'risque');
    }

    public function scopeOpportunities($query)
    {
        return $query->where('type', 'opportunite');
    }

    public function scopeCritical($query)
    {
        return $query->where('niveau', 'critique');
    }

    public function scopeByAspect($query, $aspect)
    {
        return $query->where("aspect_{$aspect}", true);
    }

    // Helpers
    public function calculateCriticite()
    {
        $this->criticite = $this->probabilite * $this->gravite;
        
        if ($this->criticite >= 12) {
            $this->niveau = 'critique';
        } elseif ($this->criticite >= 8) {
            $this->niveau = 'eleve';
        } elseif ($this->criticite >= 4) {
            $this->niveau = 'moyen';
        } else {
            $this->niveau = 'faible';
        }
        
        $this->save();
    }

    public function calculateCriticiteResiduelle()
    {
        if ($this->probabilite_residuelle && $this->gravite_residuelle) {
            $this->criticite_residuelle = $this->probabilite_residuelle * $this->gravite_residuelle;
            $this->save();
        }
    }

    // Events
    protected static function booted()
    {
        static::saving(function ($riskOpp) {
            $riskOpp->criticite = $riskOpp->probabilite * $riskOpp->gravite;
            
            if ($riskOpp->criticite >= 12) {
                $riskOpp->niveau = 'critique';
            } elseif ($riskOpp->criticite >= 8) {
                $riskOpp->niveau = 'eleve';
            } elseif ($riskOpp->criticite >= 4) {
                $riskOpp->niveau = 'moyen';
            } else {
                $riskOpp->niveau = 'faible';
            }
            
            if ($riskOpp->probabilite_residuelle && $riskOpp->gravite_residuelle) {
                $riskOpp->criticite_residuelle = $riskOpp->probabilite_residuelle * $riskOpp->gravite_residuelle;
            }
        });
    }
}
