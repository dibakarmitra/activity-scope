<?php

namespace Dibakar\ActivityScope\Support;

use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Contracts\ActivityActor;
use Dibakar\ActivityScope\Contracts\ActivitySubject;

class NameResolver
{
    public function resolveActor(Model $model): string
    {
        if ($model instanceof ActivityActor) {
            return $model->displayName();
        }

        $mapping = config('activityscope.mappings.' . $model::class);
        if ($mapping && $name = $model->getAttribute($mapping)) {
            return (string) $name;
        }

        return 'System';
    }

    public function resolveSubject(Model $model): string
    {
        if ($model instanceof ActivitySubject) {
            return $model->activityLabel();
        }

        $mapping = config('activityscope.mappings.' . $model::class);
        if ($mapping && $name = $model->getAttribute($mapping)) {
            return (string) $name;
        }

        return '';
    }
}
