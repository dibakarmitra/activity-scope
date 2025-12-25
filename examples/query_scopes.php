<?php

use Dibakar\ActivityScope\Models\Activity;
use App\Models\User;
use App\Models\Post;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| Activity Querying Examples
|--------------------------------------------------------------------------
|
| This file demonstrates the powerful fluent query scopes available on the
| Activity model. These scopes allow you to easily filter and retrieve
| audit logs based on actors, subjects, actions, and more.
|
*/

// 1. Filtering by Actor
// ========================================================================

// Get all activities performed by a specific user (User model instance)
$userActivities = Activity::by(User::find(1))->get();

// Get activities by a specific actor type and ID (useful if model is not loaded)
$adminActivities = Activity::by('App\Models\Admin', 5)->get();

// Alias method 'causedBy' is also available
$systemActivities = Activity::causedBy('system')->get();


// 2. Filtering by Subject
// ========================================================================

// Get the full history of a specific post
$postHistory = Activity::forSubject(Post::find(101))->latest()->get();

// Get history for a subject using type and ID
$orderHistory = Activity::forSubject('App\Models\Order', 500)->get();

// Note: Activity::onSubject() is also available.
// e.g. Activity::onSubject($post)->get();


// 3. Filtering by Action & Status
// ========================================================================

// Find all 'login' events
$logins = Activity::did('login')->get();

// Find multiple action types
$auditEvents = Activity::did(['created', 'updated', 'deleted'])->get();

// Combine with other scopes: Failed logins
$failedLogins = Activity::did('login')
    ->where('status', 'failed')
    ->get();


// 4. Time-Based Filtering
// ========================================================================

// Get activities from the last week
$recentActivities = Activity::between(now()->subWeek(), now())->get();

// Combine with subject: What happened to this Order yesterday?
$yesterdayOrderLogs = Activity::forSubject(Order::find(1))
    ->between(now()->subDay()->startOfDay(), now()->subDay()->endOfDay())
    ->get();


// 5. Advanced Filtering (Log Names & Metadata)
// ========================================================================

// Filter by specific log channel/name
$systemLogs = Activity::inLog('system', 'worker')->get();

// Query JSON metadata fields efficiently
// Example: Find all activities where the IP address in metadata was 127.0.0.1
$localActions = Activity::withProperty('ip', '127.0.0.1')->get();

// Example: Find all changes where the 'role' field was updated to 'admin'
$adminPromotions = Activity::withProperty('role', 'admin')->get();


// 6. Eager Loading & Performance
// ========================================================================

// The 'actor' and 'subject' relationships are EAGER LOADED by default.
// You do not need to call ->with() manually.

$activities = Activity::latest()->limit(20)->get();

foreach ($activities as $activity) {
    // These calls will NOT trigger N+1 queries
    echo $activity->actor?->name;
    echo $activity->subject?->title;
    echo $activity->message();
}
