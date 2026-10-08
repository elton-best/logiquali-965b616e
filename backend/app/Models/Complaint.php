<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasActions;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, HasActions;

    protected $table = 'reclamations';

    protected $fillable = [
        'ref',
        'user_id',
        'site_id',
        'title',
        'description',
        'wants_mail',
        'recommandations',
        'status',
        'assigned_to',
        // Client B specific fields
        'customer_name',
        'customer_address',
        'customer_phone',
        'customer_email',
        'wants_email_response',
        'expected_solution',
        // Extended fields from reclamations
        'category',
        'priority',
        'client_name',
        'client_email',
        'client_phone',
        'client_company',
        'stakeholder_type',
        'severity',
        'analysis',
        'immediate_response',
        'response_date',
        'responded_by',
        'satisfaction_rating',
        'satisfaction_comment',
        'received_date',
        'due_date',
        'closed_date',
    ];

    protected function casts(): array
    {
        return [
            'wants_mail' => 'boolean',
            'wants_email_response' => 'boolean',
            'warranty_claim' => 'boolean',
            'response_date' => 'date',
            'received_date' => 'date',
            'due_date' => 'date',
            'closed_date' => 'date',
            'satisfaction_date' => 'date',
            'satisfaction_rating' => 'integer',
            'cost_impact' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
