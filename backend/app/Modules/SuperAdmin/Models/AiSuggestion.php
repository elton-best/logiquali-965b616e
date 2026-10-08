<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'suggestion_context',
        'related_entity_type',
        'related_entity_id',
        'user_prompt',
        'ai_response',
        'confidence_score',
        'accepted',
        'modified_by_user',
        'api_model',
        'api_tokens_used',
        'api_cost',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'accepted' => 'boolean',
        'modified_by_user' => 'boolean',
        'api_tokens_used' => 'integer',
        'api_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
