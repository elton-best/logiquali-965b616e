<?php

namespace App\Modules\Enterprise\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_changed',
        'password_changed_at',
        'signature_uploaded',
        'signature_data',
        'signature_uploaded_at',
        'activity_domain_selected',
        'activity_domain',
        'activity_domain_selected_at',
        'onboarding_completed',
        'completed_at',
    ];

    protected $casts = [
        'password_changed' => 'boolean',
        'password_changed_at' => 'datetime',
        'signature_uploaded' => 'boolean',
        'signature_uploaded_at' => 'datetime',
        'activity_domain_selected' => 'boolean',
        'activity_domain_selected_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
