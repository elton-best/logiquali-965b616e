<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationWorkflowHistory extends Model
{
    protected $table = 'validation_workflow_histories';

    protected $fillable = [
        'validation_workflow_id',
        'from_status',
        'to_status',
        'actor_id',
        'commentaire',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ValidationWorkflow::class, 'validation_workflow_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
