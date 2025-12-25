<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Traits\HasActivities;
use Dibakar\ActivityScope\Traits\LogsActivity;

/**
 * Example: Post Model
 * 
 * Demonstrates proper usage of:
 * - LogsActivity: For automatic event logging (created, updated, deleted)
 * - HasActivities: For relationship access (model->actions, model->activities)
 */
class Post extends Model
{
    // Auto-logs 'created', 'updated', 'deleted' events
    use LogsActivity;

    // Provides relationships: actions(), activities()
    use HasActivities;

    protected $fillable = ['title', 'content', 'status'];
}

/**
 * Example: User Model
 * 
 * Typically users perform actions (actors) but can also be subjects.
 */
class User extends Model
{
    use HasActivities;

    // ... user logic
}

// usage_example.php

$user = User::find(1);
$post = new Post(['title' => 'Hello World']);

// 1. Auto-logging in action
// This will automatically create a "created" activity
// Actor: Auth::user() (if logged in) or System
// Subject: The new Post instance
$post->save();

// 2. Accessing Logs via Relationships
// Get all activities performed ON this post
$history = $post->activities()->latest()->get();

foreach ($history as $activity) {
    echo "{$activity->created_at}: {$activity->action} by {$activity->actor?->name}";
}

// 3. User History
// Get all activities performed BY this user
$userActions = $user->actions()->get();

// 4. Manual Activity using Model Context
// Using newActivity() from HasActivities trait automatically sets:
// - The model as the Subject (if it implements ActivitySubject)
// - The model as the Actor (if it implements ActivityActor AND calls it)
// But usually, you want independent logging:

$post->newActivity()
    ->did('published')
    ->with(['scheduled_for' => '2023-12-25'])
    ->log();
