<?php

use Dibakar\ActivityScope\Services\ActivityBuilder;

if (!function_exists('activity')) {
    function activity(): ActivityBuilder
    {
        return app('activityscope.activity');
    }
}
