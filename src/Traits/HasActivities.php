<?php

namespace Dibakar\ActivityScope\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Dibakar\ActivityScope\Contracts\ActivityActor;
use Dibakar\ActivityScope\Contracts\ActivitySubject;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Services\ActivityBuilder;
use Dibakar\ActivityScope\Support\ActorResolver;

trait HasActivities
{
    public function actions(): MorphMany
    {
        return $this->morphMany(Activity::class, 'actor');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function newActivity(): ActivityBuilder
    {
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

    public function scopeWithinDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}