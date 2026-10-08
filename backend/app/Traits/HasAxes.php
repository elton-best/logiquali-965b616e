<?php

namespace App\Traits;

use App\Models\Axe;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasAxes
{
    public function axes(): MorphToMany
    {
        return $this->morphToMany(Axe::class, 'axeable');
    }

    public function hasAxe(string $code): bool
    {
        return $this->axes()->where('code', $code)->exists();
    }

    public function attachAxe(string|int $axe): void
    {
        if (is_string($axe)) {
            $axe = Axe::where('code', $axe)->firstOrFail();
        }
        $this->axes()->syncWithoutDetaching($axe);
    }

    public function detachAxe(string|int $axe): void
    {
        if (is_string($axe)) {
            $axe = Axe::where('code', $axe)->firstOrFail();
        }
        $this->axes()->detach($axe);
    }

    public function syncAxes(array $axes): void
    {
        $axeIds = collect($axes)->map(function ($axe) {
            if (is_string($axe)) {
                return Axe::where('code', $axe)->firstOrFail()->id;
            }
            return $axe;
        })->toArray();

        $this->axes()->sync($axeIds);
    }

    public function getAxeCodes(): array
    {
        return $this->axes()->pluck('code')->toArray();
    }

    public function scopeWithAxe($query, string $code)
    {
        return $query->whereHas('axes', function ($q) use ($code) {
            $q->where('code', $code);
        });
    }

    public function scopeWithAnyAxe($query, array $codes)
    {
        return $query->whereHas('axes', function ($q) use ($codes) {
            $q->whereIn('code', $codes);
        });
    }
}
