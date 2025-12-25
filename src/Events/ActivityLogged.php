<?php

namespace Dibakar\ActivityScope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Dibakar\ActivityScope\Models\Activity;

class ActivityLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Activity $activity
    ) {
    }
}
