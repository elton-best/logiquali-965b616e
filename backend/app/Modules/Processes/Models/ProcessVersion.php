<?php

namespace App\Modules\Processes\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'process_id',
        'version_number',
        'version_date',
        'author_user_id',
        'verifier_user_id',
        'approver_user_id',
        'status',
        'changes_description',
        'verified_at',
        'approved_at',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'version_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    // Relations

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    // Scopes

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Helpers

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isVerified()
    {
        return $this->status === 'verified';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function canBeVerified()
    {
        return $this->isDraft();
    }

    public function canBeApproved()
    {
        return $this->isVerified();
    }

    public function verify($userId = null)
    {
        $this->update([
            'status' => 'verified',
            'verifier_user_id' => $userId ?? auth()->id(),
            'verified_at' => now(),
        ]);
    }

    public function approve($userId = null)
    {
        $this->update([
            'status' => 'approved',
            'approver_user_id' => $userId ?? auth()->id(),
            'approved_at' => now(),
        ]);
        
        $this->makeCurrent();
    }

    public function makeCurrent()
    {
        // Set all other versions as not current
        $this->process->versions()->update(['is_current' => false]);

        // Set this version as current
        $this->update(['is_current' => true]);
    }
}
