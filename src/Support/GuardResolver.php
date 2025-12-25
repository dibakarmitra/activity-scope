<?php

namespace Dibakar\ActivityScope\Support;

use Illuminate\Support\Facades\Auth;

class GuardResolver
{
    protected ?string $guard = null;
    
    public function using(string $guard): self
    {
        $this->guard = $guard;
        return $this;
    }
    
    public function resolve(): string
    {
        if ($this->guard) {
            return $this->guard;
        }
        
        $used = Auth::getDefaultDriver();
        if ($used) {
            return $used;
        }
        
        return config('activityscope.actor.default_guard', 'web');
    }

    public function user()
    {
        return Auth::guard($this->resolve())->user();
    }
}