<?php

namespace Dibakar\ActivityScope\Traits;

use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Contracts\ActivityActor;
use Dibakar\ActivityScope\Contracts\ActivitySubject;
use Dibakar\ActivityScope\Services\ActivityBuilder;
use Dibakar\ActivityScope\Support\ActorResolver;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        if (!config('activityscope.auto_log', false)) {
            return;
        }

        $events = config('activityscope.auto_log_events', ['created', 'updated', 'deleted']);

        foreach ($events as $event) {
            static::$event(function (Model $model) use ($event) {
                if (method_exists($model, 'logTraitActivity')) {
                    $model->logTraitActivity($event);
                }
            });
        }
    }

    protected function logTraitActivity(string $event): void
    {
        $builder = $this->resolveActivity()->did($event);

        if ($event === 'updated') {
            $builder->changes($this->getDirty());
        }

        $builder->log();
    }

    protected function resolveActivity(): ActivityBuilder
    {
        if (method_exists($this, 'newActivity')) {
            return $this->newActivity();
        }

        $builder = activity()->on($this instanceof ActivitySubject ? $this : null);

        if ($this instanceof ActivityActor) {
            $builder->by($this);
        } elseif (config('activityscope.auto_actor', true)) {
            $actor = app(ActorResolver::class)->resolve();
            if ($actor) {
                $builder->by($actor);
            }
        }

        return $builder;
    }
}
