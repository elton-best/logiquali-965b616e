<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CodeSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'document_type_configuration_id',
        'process_id',
        'year',
        'month',
        'last_sequence_number',
        'available_numbers',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'last_sequence_number' => 'integer',
        'available_numbers' => 'array',
    ];

    /**
     * Relations
     */
    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(DocumentTypeConfiguration::class, 'document_type_configuration_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Récupère le prochain numéro disponible (avec recyclage)
     *
     * @return int
     */
    public function getNextNumber(): int
    {
        return DB::transaction(function () {
            // Verrouiller la ligne pour éviter les doublons
            $sequence = self::lockForUpdate()->find($this->id);

            // Vérifier s'il y a des numéros recyclés disponibles
            $availableNumbers = $sequence->available_numbers ?? [];
            
            if (!empty($availableNumbers)) {
                // Trier pour prendre le plus petit
                sort($availableNumbers);
                $nextNumber = array_shift($availableNumbers);
                
                // Mettre à jour la liste des numéros disponibles
                $sequence->available_numbers = $availableNumbers;
                $sequence->save();
                
                return $nextNumber;
            }

            // Sinon, incrémenter la séquence
            $sequence->last_sequence_number++;
            $sequence->save();

            return $sequence->last_sequence_number;
        });
    }

    /**
     * Libère un numéro pour le rendre disponible au recyclage
     *
     * @param int $number
     * @return void
     */
    public function releaseNumber(int $number): void
    {
        DB::transaction(function () use ($number) {
            $sequence = self::lockForUpdate()->find($this->id);

            $availableNumbers = $sequence->available_numbers ?? [];
            
            // Ajouter le numéro s'il n'est pas déjà dans la liste
            if (!in_array($number, $availableNumbers)) {
                $availableNumbers[] = $number;
                sort($availableNumbers);
                
                $sequence->available_numbers = $availableNumbers;
                $sequence->save();
            }
        });
    }

    /**
     * Trouve ou crée une séquence selon les paramètres de scope
     *
     * @param array $params
     * @return self
     */
    public static function findOrCreateForScope(array $params): self
    {
        return self::firstOrCreate(
            [
                'enterprise_id' => $params['enterprise_id'],
                'site_id' => $params['site_id'],
                'document_type_configuration_id' => $params['document_type_configuration_id'],
                'process_id' => $params['process_id'] ?? null,
                'year' => $params['year'] ?? null,
                'month' => $params['month'] ?? null,
            ],
            [
                'last_sequence_number' => 0,
                'available_numbers' => [],
            ]
        );
    }

    /**
     * Verrouille cette séquence pour génération
     *
     * @return self
     */
    public function lockForGeneration(): self
    {
        return self::lockForUpdate()->find($this->id);
    }

    /**
     * Récupère le nombre de numéros recyclés disponibles
     *
     * @return int
     */
    public function getAvailableNumbersCount(): int
    {
        return count($this->available_numbers ?? []);
    }

    /**
     * Récupère le prochain numéro qui serait généré (sans le consommer)
     *
     * @return int
     */
    public function peekNextNumber(): int
    {
        $availableNumbers = $this->available_numbers ?? [];
        
        if (!empty($availableNumbers)) {
            sort($availableNumbers);
            return $availableNumbers[0];
        }

        return $this->last_sequence_number + 1;
    }

    /**
     * Réinitialise la séquence (attention : opération dangereuse)
     *
     * @return void
     */
    public function reset(): void
    {
        $this->update([
            'last_sequence_number' => 0,
            'available_numbers' => [],
        ]);
    }
}
