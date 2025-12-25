<?php

namespace Dibakar\ActivityScope\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\MassPrunable;

/**
 * @property int $id
 * @property string|null $log_name
 * @property string|null $actor_type
 * @property int|string|null $actor_id
 * @property string|null $subject_type
 * @property int|string|null $subject_id
 * @property string $action
 * @property string $status
 * @property string|null $category
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array $meta
 * @property string|null $method
 * @property string|null $path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $actor
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 */
class Activity extends Model
{
    use MassPrunable;

    protected $connection;

    public function __construct(array $attributes = [])
    {
        $this->connection = config('activityscope.database.connection');
        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('activityscope.database.table', parent::getTable());
    }

    protected $fillable = [
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'action',
        'log_name',
        'status',
        'category',
        'ip_address',
        'user_agent',
        'meta',
        'method',
        'path',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected $with = ['actor', 'subject'];

    public function getActorNameAttribute(): ?string
    {
        if (!$this->actor) {
            return null;
        }

        $mapping = config('activityscope.mappings.' . $this->actor_type, 'name');
        return $this->actor->{$mapping} ?? $this->actor->name ?? null;
    }

    public function getSubjectNameAttribute(): ?string
    {
        if (!$this->subject) {
            return null;
        }

        $mapping = config('activityscope.mappings.' . $this->subject_type, 'name');
        return $this->subject->{$mapping} ?? $this->subject->name ?? null;
    }

    public function prunable()
    {
        if ($days = config('activityscope.prune_after_days')) {
            return static::where('created_at', '<=', now()->subDays($days));
        }

        return static::where('id', '<', 0);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('with_relations', function (Builder $builder) {
            $builder->with(['actor', 'subject']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeBy(Builder $query, $actor, $id = null): Builder
    {
        if ($actor instanceof Model) {
            return $query->where('actor_type', $actor->getMorphClass())
                ->where('actor_id', $actor->getKey());
        }

        if ($id !== null) {
            return $query->where('actor_type', $actor)
                ->where('actor_id', $id);
        }

        return $query->where('actor_type', $actor);
    }

    public function scopeCausedBy(Builder $query, $actor, $id = null): Builder
    {
        return $this->scopeBy($query, $actor, $id);
    }

    public function scopeOnSubject(Builder $query, $subject, $id = null): Builder
    {
        if ($subject instanceof Model) {
            return $query->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey());
        }

        if ($id !== null) {
            return $query->where('subject_type', $subject)
                ->where('subject_id', $id);
        }

        return $query->where('subject_type', $subject);
    }

    public function scopeForSubject(Builder $query, $subject, $id = null): Builder
    {
        return $this->scopeOnSubject($query, $subject, $id);
    }

    public function scopeDid(Builder $query, string|array $action): Builder
    {
        if (is_array($action)) {
            return $query->whereIn('action', $action);
        }
        return $query->where('action', $action);
    }

    public function scopeBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeInLog(Builder $query, string|array ...$names): Builder
    {
        if (is_array($names[0] ?? null)) {
            $names = $names[0];
        }
        return $query->whereIn('log_name', $names);
    }

    public function scopeWithProperty(Builder $query, string $key, mixed $value): Builder
    {
        return $query->where("meta->{$key}", $value);
    }

    public function scopeByActor(Builder $q, Model $actor): Builder
    {
        return $this->scopeBy($q, $actor);
    }

    public function scopeWhereAction(Builder $q, string $action): Builder
    {
        return $this->scopeDid($q, $action);
    }

    public function scopeWithRelations(Builder $query): Builder
    {
        return $query->with(['actor', 'subject']);
    }

    /*
    |--------------------------------------------------------------------------
    | Other Methods
    |--------------------------------------------------------------------------
    */

    public function message(): string
    {
        return app('activityscope.message')->build($this);
    }

    public function ip(): ?string
    {
        return $this->getAttribute('ip_address') ?? $this->meta['ip'] ?? null;
    }

    public function userAgent(): ?string
    {
        return $this->getAttribute('user_agent') ?? $this->meta['agent'] ?? null;
    }

    public function isSystem(): bool
    {
        return ($this->meta['system'] ?? false) === true;
    }

    public function isGuest(): bool
    {
        return ($this->meta['guest'] ?? false) === true;
    }

    public function jobName(): ?string
    {
        return $this->meta['job'] ?? null;
    }
}
