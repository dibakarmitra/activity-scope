<?php

namespace Dibakar\ActivityScope\Services;

use Illuminate\Support\Str;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Support\NameResolver;

class MessageBuilder
{
    public function __construct(
        protected readonly NameResolver $names
    ) {
    }

    public function build(Activity $activity): string
    {
        $actor = $this->actor($activity);
        $action = $this->action($activity);
        $subject = $this->subject($activity);
        $meta = $activity->meta ?? [];

        if (str_starts_with($action, 'security:')) {
            $event = substr($action, 9);
            return trim("{$actor} {$event}");
        }

        if (isset($meta['changes']) && is_array($meta['changes']) && !empty($meta['changes'])) {
            $fields = implode(', ', array_keys($meta['changes']));
            $subjectText = !empty($subject) ? " for {$subject}" : '';
            return "{$actor} {$action} {$fields}{$subjectText}";
        }

        if (isset($meta['old'], $meta['new']) && empty($meta['sensitive'])) {
            $subjectText = !empty($subject) ? " {$subject}" : '';
            return "{$actor} {$action}{$subjectText} from {$meta['old']} to {$meta['new']}";
        }

        $parts = array_filter([$actor, $action, $subject], fn($part) => !empty($part));
        $message = implode(' ', $parts);

        if ($activity->status !== 'success' && !empty($activity->status)) {
            $message .= " ({$activity->status})";
        }

        if (isset($meta['reason']) && !empty($meta['reason'])) {
            $message .= ": {$meta['reason']}";
        }

        return $message;
    }

    protected function actor(Activity $activity): string
    {
        $meta = $activity->meta ?? [];

        if (($meta['system'] ?? false) === true) {
            return 'System';
        }

        if (isset($meta['job']) && !empty($meta['job'])) {
            return "Job: {$meta['job']}";
        }

        if (($meta['guest'] ?? false) === true) {
            return 'Guest';
        }

        if ($activity->actor !== null) {
            $resolved = $this->names->resolveActor($activity->actor);
            return !empty($resolved) ? $resolved : 'Unknown User';
        }

        return 'System';
    }

    protected function subject(Activity $activity): string
    {
        $meta = $activity->meta ?? [];

        if (isset($meta['subject_ids']) && is_array($meta['subject_ids'])) {
            $count = count($meta['subject_ids']);

            if ($count === 0) {
                return '';
            }

            $type = $meta['subject_type'] ?? 'items';
            $type = class_basename($type);

            $type = $count === 1 ? Str::singular($type) : Str::plural($type);

            return "{$count} {$type}";
        }

        if (isset($meta['subject_label']) && !empty($meta['subject_label'])) {
            return $meta['subject_label'];
        }

        if (isset($meta['subject_id']) && !empty($meta['subject_id'])) {
            return "#{$meta['subject_id']}";
        }

        if ($activity->subject !== null) {
            $resolved = $this->names->resolveSubject($activity->subject);
            return !empty($resolved) ? $resolved : '';
        }

        return '';
    }

    protected function action(Activity $activity): string
    {
        $action = $activity->action ?? '';

        $action = str_replace(['_', '-'], ' ', $action);

        return !empty($action) ? $action : 'performed action';
    }

    public function buildDetailed(Activity $activity): string
    {
        $message = $this->build($activity);
        // $meta = $activity->meta ?? [];
        // $details = [];

        // if (!empty($activity->ip_address)) {
        //     $details[] = "IP: {$activity->ip_address}";
        // }

        // if (!empty($activity->user_agent)) {
        //     $agent = $this->truncateUserAgent($activity->user_agent);
        //     $details[] = "Agent: {$agent}";
        // }

        // if (!empty($activity->method) || !empty($activity->path)) {
        //     $method = strtoupper($activity->method ?? 'GET');
        //     $path = $activity->path ?? '/';
        //     $details[] = "{$method} {$path}";
        // }

        // if (isset($meta['tags']) && is_array($meta['tags']) && !empty($meta['tags'])) {
        //     $details[] = 'Tags: ' . implode(', ', $meta['tags']);
        // }

        // if (!empty($details)) {
        //     $message .= ' [' . implode(' | ', $details) . ']';
        // }

        return $message;
    }

    protected function truncateUserAgent(string $userAgent, int $maxLength = 50): string
    {
        if (strlen($userAgent) <= $maxLength) {
            return $userAgent;
        }

        return substr($userAgent, 0, $maxLength - 3) . '...';
    }
}