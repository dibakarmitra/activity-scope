<?php

namespace Dibakar\ActivityScope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Dibakar\ActivityScope\Services\ActivityBuilder;
use Throwable;

class ActivityLogFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Throwable $throwable,
        public ActivityBuilder $activity
    ) {
    }
}
