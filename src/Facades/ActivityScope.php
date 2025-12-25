<?php

namespace Dibakar\ActivityScope\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @package Dibakar\ActivityScope\Facades
 */
class ActivityScope extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'activityscope';
    }
}