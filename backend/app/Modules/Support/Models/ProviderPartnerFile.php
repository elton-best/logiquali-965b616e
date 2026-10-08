<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPartnerFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'provider_partner_id',
        'title',
        'category',
        'note',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'uploaded_by',
        'updated_by',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function providerPartner()
    {
        return $this->belongsTo(ProviderPartner::class);
    }
}

