<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'key',
        'value',
        'description',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    // Relations
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // Helpers
    public static function get($key, $siteId = null, $default = null)
    {
        $setting = self::where('key', $key)
            ->where(function ($query) use ($siteId) {
                $query->where('site_id', $siteId)
                      ->orWhereNull('site_id');
            })
            ->orderByDesc('site_id')
            ->first();

        return $setting ? $setting->getValue() : $default;
    }

    public function getValue()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public static function set($key, $value, $siteId = null, $type = 'string')
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        return self::updateOrCreate(
            ['key' => $key, 'site_id' => $siteId],
            ['value' => $stringValue, 'type' => $type]
        );
    }
}
