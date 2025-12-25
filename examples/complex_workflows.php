<?php

use Illuminate\Support\Facades\DB;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Events\ActivityLogged;

/**
 * Example 1: E-Commerce Order Workflow
 * 
 * Demonstrates how to track a multi-step process with correlation IDs.
 */
function processOrder($order, $user)
{
    $correlationId = (string) Str::uuid();

    DB::transaction(function () use ($order, $user, $correlationId) {

        // Step 1: Payment
        // ... payment logic ...
        activity()
            ->by($user)
            ->on($order)
            ->did('payment_processed')
            ->correlationId($correlationId) // Grouping all steps together
            ->success()
            ->log();

        // Step 2: Inventory
        // ... inventory logic ...
        activity()
            ->bySystem() // System actor for automated step
            ->on($order)
            ->did('inventory_reserved')
            ->correlationId($correlationId)
            ->with(['items' => $order->items->count()])
            ->log();

        // Step 3: Notification
        // ... email logic ...
        activity()
            ->did('confirmation_sent')
            ->correlationId($correlationId)
            ->log();
    });
}

/**
 * Example 2: Administrative User Ban
 * 
 * Demonstrates privacy features and detailed reasoning.
 */
function banUser($admin, $targetUser, $reason)
{
    // ... ban logic ...
    $targetUser->update(['banned_at' => now()]);

    activity()
        ->by($admin)
        ->on($targetUser)
        ->did('user_banned')
        ->severity('critical')
        ->private() // Only admins can see this log
        ->with([
            'reason' => $reason,
            'ban_duration' => 'permanent',
            'ticket_id' => 'T-5592'
        ])
        ->log();
}

/**
 * Example 3: Listening to Events
 * 
 * In your EventServiceProvider:
 * ActivityLogged::class => [SendSlackNotification::class],
 */
class SendSlackNotification
{
    public function handle(ActivityLogged $event)
    {
        $activity = $event->activity;

        if ($activity->meta['severity'] === 'critical') {
            Slack::send("CRITICAL ALERT: {$activity->message()}");
        }
    }
}
