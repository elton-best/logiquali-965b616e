<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasAuditFields
{
    protected static function bootHasAuditFields()
    {
        static::creating(function ($model) {
            if (Auth::check() && static::modelHasColumn($model, 'created_by')) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && static::modelHasColumn($model, 'updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (
                Auth::check()
                && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model), true)
                && static::modelHasColumn($model, 'deleted_by')
            ) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }

    private static function modelHasColumn($model, string $column): bool
    {
        try {
            $connection = $model->getConnectionName();
            return Schema::connection($connection)->hasColumn($model->getTable(), $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
