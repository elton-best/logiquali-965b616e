<?php

namespace App\Modules\Support\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_session_id',
        'user_id',
        'attended',
        'satisfaction_score',
        'knowledge_acquired_score',
        'comments',
        'certificate_path',
        'evaluation_locked',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'satisfaction_score' => 'integer',
        'knowledge_acquired_score' => 'integer',
        'evaluation_locked' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
