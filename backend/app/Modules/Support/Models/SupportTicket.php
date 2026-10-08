<?php

namespace App\Modules\Support\Models;

use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes, HasReference;

    protected $fillable = [
        'ref',
        'user_id',
        'type',
        'subject',
        'message',
        'status',
        'priority',
        'assigned_to',
        'response',
        'responded_at',
        'closed_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ticket) {
            if (empty($ticket->ref)) {
                $ticket->ref = 'SUP-' . strtoupper(uniqid());
            }
        });
    }
}
