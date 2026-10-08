<?php

namespace App\Modules\Improvement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollaboratorActionConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_id',
        'site_id',
        'user_id',
        'notified_at',
        'notification_sent',
        'confirmed_at',
        'confirmation_notes',
        'proof_path',
        'proof_uploaded_at',
        'status',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'notification_sent' => 'boolean',
        'confirmed_at' => 'datetime',
        'proof_uploaded_at' => 'datetime',
    ];

    public function action()
    {
        return $this->belongsTo(Action::class);
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
