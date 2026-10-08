<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Activitylog\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity as LogsActivityTrait;

class Axe extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'icon',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected static $logAttributes = ['code', 'name', 'is_active'];
    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['code', 'name', 'is_active'])
            ->logOnlyDirty();
    }

    public function nonConformities(): MorphToMany
    {
        return $this->morphedByMany(NonConformity::class, 'axeable');
    }

    public function audits(): MorphToMany
    {
        return $this->morphedByMany(Audit::class, 'axeable');
    }

    public function objectives(): MorphToMany
    {
        return $this->morphedByMany(Objective::class, 'axeable');
    }

    public function risks(): MorphToMany
    {
        return $this->morphedByMany(Risk::class, 'axeable');
    }

    public function reclamations(): MorphToMany
    {
        return $this->morphedByMany(Reclamation::class, 'axeable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}

