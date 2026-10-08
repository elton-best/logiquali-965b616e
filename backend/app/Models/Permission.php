<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'model_has_permissions', 'permission_id', 'model_id')
            ->wherePivot('model_type', User::class);
    }

    public function permissionNormMappings()
    {
        return $this->hasMany(PermissionNormMapping::class);
    }
}
