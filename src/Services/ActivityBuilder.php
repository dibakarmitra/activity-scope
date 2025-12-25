<?php

namespace Dibakar\ActivityScope\Services;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Dibakar\ActivityScope\Events\ActivityLogFailed;
use Dibakar\ActivityScope\Events\ActivityLogged;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Exceptions\ActivityException;
use Dibakar\ActivityScope\Support\ActorResolver;
use Dibakar\ActivityScope\Support\DataSanitizer;
use Dibakar\ActivityScope\Support\IpAnonymizer;
use Throwable;

class ActivityBuilder
{
    private const MAX_PAYLOAD_SIZE = 65535;

    protected ?string $log_name = null;
    protected ?Model $actor = null;
    protected ?Model $subject = null;
    protected string $action = '';
    protected string $status = 'success';
    protected ?string $category = null;
    protected ?string $ip = null;
    protected ?string $userAgent = null;
    protected ?string $path = null;
    protected ?string $method = null;
    protected array $meta = [];
    protected ?Carbon $at = null;
    protected bool $silent = false;
    protected ?string $connection = null;

    public function __construct(
        private readonly DataSanitizer $dataSanitizer
    ) {
    }

    // ============================================
    // Configuration Methods
    // ============================================

    public function connection(?string $connection = null): self
    {
        $this->connection = $connection ?? config('activityscope.database.connection', config('database.default'));
        return $this;
    }

    public function name(?string $name = null): self
    {
        $this->log_name = $name ?? $this->log_name;
        return $this;
    }

    // ============================================
    // Control Flow Methods
    // ============================================

    public function silent(): self
    {
        $this->silent = true;
        return $this;
    }

    public function when(bool $condition, Closure $callback): self
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    public function tap(Closure $callback): self
    {
        $callback($this);
        return $this;
    }

    // ============================================
    // Actor Methods
    // ============================================

    public function by(?Model $actor): self
    {
        $this->actor = $actor;
        return $this;
    }

    public function bySystem(): self
    {
        $this->actor = null;
        $this->meta['system'] = true;
        return $this;
    }

    public function byJob(string $job): self
    {
        $this->meta['job'] = $job;
        return $this;
    }

    public function byGuest(): self
    {
        $this->meta['guest'] = true;
        return $this;
    }

    // ============================================
    // Subject Methods
    // ============================================

    public function on(?Model $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function for(?Model $subject): self
    {
        return $this->on($subject);
    }

    public function onMany(iterable $models): self
    {
        $ids = [];
        $type = null;

        foreach ($models as $model) {
            if (!$model instanceof Model) {
                continue;
            }
            $type ??= $model::class;
            $ids[] = $model->getKey();
        }

        if ($type !== null) {
            $this->meta['subject_ids'] = $ids;
            $this->meta['subject_type'] = $type;
        }

        return $this;
    }

    public function forMany(iterable $models): self
    {
        return $this->onMany($models);
    }

    public function subject(string $label): self
    {
        $this->meta['subject_label'] = $label;
        return $this;
    }

    public function subjectId(string|int $id): self
    {
        $this->meta['subject_id'] = $id;
        return $this;
    }

    // ============================================
    // Action Methods
    // ============================================

    public function did(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function action(string $action): self
    {
        return $this->did($action);
    }

    public function created(?Model $model = null): self
    {
        return $this->on($model)->did('created');
    }

    public function updated(?Model $model = null): self
    {
        return $this->on($model)->did('updated');
    }

    public function deleted(?Model $model = null): self
    {
        return $this->on($model)->did('deleted');
    }

    public function restored(?Model $model = null): self
    {
        return $this->on($model)->did('restored');
    }

    public function approved(?Model $model = null): self
    {
        return $this->on($model)->did('approved');
    }

    public function rejected(?Model $model = null): self
    {
        return $this->on($model)->did('rejected');
    }

    // ============================================
    // Status Methods
    // ============================================

    public function status(string $status, ?string $reason = null): self
    {
        $this->status = $status;
        if ($reason !== null) {
            $this->meta['reason'] = $reason;
        }
        return $this;
    }

    public function success(): self
    {
        return $this->status('success');
    }

    public function failed(?string $reason = null): self
    {
        return $this->status('failed', $reason);
    }

    public function warning(string $reason): self
    {
        return $this->status('warning', $reason);
    }

    public function info(string $reason): self
    {
        return $this->status('info', $reason);
    }

    // ============================================
    // Meta & Context Methods
    // ============================================

    public function with(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    public function context(array $meta): self
    {
        return $this->with($meta);
    }

    public function oldNew(mixed $old, mixed $new): self
    {
        return $this->with(['old' => $old, 'new' => $new]);
    }

    public function changes(array $changes): self
    {
        return $this->with(['changes' => $changes]);
    }

    // ============================================
    // Request & Security Methods
    // ============================================

    public function security(string $event): self
    {
        return $this->did("security:{$event}");
    }

    public function ip(?string $ip = null): self
    {
        if (!$this->dataSanitizer->shouldTrackIp()) {
            return $this;
        }

        if (!app()->bound('request')) {
            return $this;
        }

        $ipToUse = $ip ?? request()->ip();

        if ($ipToUse === null) {
            return $this;
        }

        if ($this->dataSanitizer->shouldAnonymizeIp()) {
            $ipToUse = app(IpAnonymizer::class)->anonymize($ipToUse);
        }

        $this->ip = $ipToUse;

        return $this;
    }

    public function userAgent(?string $userAgent = null): self
    {
        if ($this->dataSanitizer->shouldTrackUserAgent()) {
            $this->userAgent = $userAgent ?? (app()->bound('request') ? request()->userAgent() : null);
        }
        return $this;
    }

    public function path(?string $path = null): self
    {
        $this->path = $path ?? $this->path ?? (app()->bound('request') ? request()->path() : null);
        return $this;
    }

    public function method(?string $method = null): self
    {
        $this->method = $method ?? $this->method ?? (app()->bound('request') ? request()->method() : null);
        return $this;
    }

    // ============================================
    // Privacy & Classification Methods
    // ============================================

    public function private(): self
    {
        $this->meta['private'] = true;
        return $this;
    }

    public function public(): self
    {
        unset($this->meta['private']);
        return $this;
    }

    public function sensitive(): self
    {
        $this->meta['sensitive'] = true;
        return $this;
    }

    public function tags(array|string $tags): self
    {
        $this->meta['tags'] = is_array($tags) ? $tags : [$tags];
        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function severity(string|int $severity): self
    {
        $this->meta['severity'] = $severity;
        return $this;
    }

    // ============================================
    // Timestamp Methods
    // ============================================

    public function at(Carbon $at): self
    {
        $this->at = $at;
        return $this;
    }

    // ============================================
    // Correlation Methods
    // ============================================

    public function correlationId(string $id): self
    {
        $this->meta['correlation_id'] = $id;
        return $this;
    }

    public function requestId(string $id): self
    {
        $this->meta['request_id'] = $id;
        return $this;
    }

    public function externalRef(string $ref): self
    {
        $this->meta['external_ref'] = $ref;
        return $this;
    }

    // ============================================
    // Logging Methods
    // ============================================

    public function log(): ?Activity
    {
        try {
            $this->setDefaults();

            if ($this->shouldSkipLogging()) {
                return null;
            }

            $this->validateActivity();

            $activity = new Activity();
            $activity->setConnection($this->connection);
            $activity->fill($this->toArray())->save();

            if (!$this->silent) {
                event(new ActivityLogged($activity));
            }

            return $activity;
        } catch (Throwable $th) {
            $this->handleError($th);
            return null;
        }
    }

    public function query(?string $connection = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = new Activity();

        if ($connection !== null) {
            $query->setConnection($connection);
        }

        return $query->newQuery()->withRelations();
    }

    // ============================================
    // Protected Helper Methods
    // ============================================

    protected function setDefaults(): void
    {
        $this->connection ??= config('activityscope.database.connection', config('database.default'));
        $this->log_name ??= config('activityscope.default_log_name', 'default');

        if (app()->bound('request')) {
            $request = request();

            if ($this->ip === null && $this->dataSanitizer->shouldTrackIp()) {
                $ip = $request->ip();
                if ($ip !== null) {
                    $this->ip = $this->dataSanitizer->shouldAnonymizeIp()
                        ? app(IpAnonymizer::class)->anonymize($ip)
                        : $ip;
                }
            }

            if ($this->userAgent === null && $this->dataSanitizer->shouldTrackUserAgent()) {
                $this->userAgent = $request->userAgent();
            }

            $this->method ??= $request->method();
            $this->path ??= $request->path();
        }

        if ($this->shouldResolveActor()) {
            $this->resolveActor();
        }
    }

    protected function shouldResolveActor(): bool
    {
        return $this->actor === null
            && !($this->meta['system'] ?? false)
            && !($this->meta['job'] ?? false)
            && !($this->meta['guest'] ?? false)
            && config('activityscope.auto_actor', true);
    }

    protected function resolveActor(): void
    {
        $actorResolver = app(ActorResolver::class);

        $actor = $actorResolver->resolve();

        if ($actor !== null) {
            $this->actor = $actor;
        } elseif (config('activityscope.require_actor', false)) {
            throw new ActivityException('Activity actor is required.');
        }
    }

    protected function shouldSkipLogging(): bool
    {
        return $this->silent
            || config('activityscope.enabled') === false;
    }

    protected function validateActivity(): void
    {
        if (empty($this->action)) {
            throw new ActivityException('Activity action is required.');
        }

        $payloadSize = strlen(json_encode($this->meta));

        if ($payloadSize > self::MAX_PAYLOAD_SIZE) {
            throw new ActivityException(
                sprintf(
                    'Payload size (%d bytes) exceeds maximum allowed size (%d bytes).',
                    $payloadSize,
                    self::MAX_PAYLOAD_SIZE
                )
            );
        }
    }

    protected function toArray(): array
    {
        return [
            'actor_type' => $this->actor?->getMorphClass(),
            'actor_id' => $this->actor?->getKey(),
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'action' => $this->action,
            'log_name' => $this->log_name,
            'status' => $this->status,
            'category' => $this->category,
            'ip_address' => $this->ip,
            'user_agent' => $this->userAgent,
            'method' => $this->method,
            'path' => $this->path,
            'meta' => $this->prepareMeta(),
            'created_at' => $this->at ?? now(),
        ];
    }

    protected function prepareMeta(): array
    {
        return $this->dataSanitizer->shouldHideSensitive()
            ? $this->dataSanitizer->sanitize($this->meta)
            : $this->meta;
    }

    protected function handleError(Throwable $th): void
    {
        $config = config('activityscope.error_handling', [
            'throw_on_error' => false,
            'log_failures' => true,
            'dispatch_events' => true,
        ]);

        if ($config['log_failures'] ?? true) {
            Log::error('ActivityScope logging failed: ' . $th->getMessage(), [
                'exception' => $th,
                'activity' => $this->toArray(),
            ]);
        }

        if ($config['dispatch_events'] ?? true) {
            event(new ActivityLogFailed($th, $this));
        }

        if ($config['throw_on_error'] ?? false) {
            throw $th;
        }
    }
}