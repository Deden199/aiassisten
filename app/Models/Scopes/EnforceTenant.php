<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EnforceTenant implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->hasUser()) {
            $tenantId = auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
            }
        }
    }
}
