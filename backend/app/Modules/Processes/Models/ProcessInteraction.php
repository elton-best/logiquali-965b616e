<?php

namespace App\Modules\Processes\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessInteraction extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'supplier_process_id',
        'self_process_id',
        'client_process_id',
        'description',
    ];

    public function supplierProcess()
    {
        return $this->belongsTo(Process::class, 'supplier_process_id');
    }

    public function selfProcess()
    {
        return $this->belongsTo(Process::class, 'self_process_id');
    }

    public function clientProcess()
    {
        return $this->belongsTo(Process::class, 'client_process_id');
    }
}

