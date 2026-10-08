<?php

namespace App\Models;


use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class AuditChecklistItem extends Model
{
    //
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'audit_id',
        'process_id',
        'requirement',
        'question',
        'evidence',
        'conformity_status',
        'notes'
    ] ;

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}
