# API Reference

## Global Helper

### `activity()`

Resolves the `ActivityBuilder` from the service container.

```php
activity()->did('viewed')->log();
```

---

## ActivityBuilder

The `ActivityBuilder` provides a fluent interface for creating activity logs.

### Logic Helpers

- **`when(bool $condition, Closure $callback)`**: Conditionally apply builder methods.
- **`tap(Closure $callback)`**: Access the builder instance without breaking the chain.

### Actor Methods

- **`by(?Model $actor)`**: Set the actor for the activity.
- **`bySystem()`**: Mark the activity as performed by the system (sets `meta['system'] = true`).
- **`byJob(string $job)`**: Set a specific job name as the actor context (sets `meta['job']`).
- **`byGuest()`**: Mark as a guest activity (sets `meta['guest'] = true`).

### Subject Methods

- **`on(?Model $subject)`** / **`for(?Model $subject)`**: Set the subject for the activity.
- **`onMany(iterable $models)`** / **`forMany(iterable $models)`**: Log activity on multiple models of the same type.
- **`subject(string $label)`**: Set a manual subject label (e.g., "Settings").
- **`subjectId(string|int $id)`**: Set a manual subject ID.

### Action Methods

- **`did(string $action)`** / **`action(string $action)`**: Set the action name.
- **`created(Model $model)`**: Helper for `on($model)->did('created')`.
- **`updated(Model $model)`**: Helper for `on($model)->did('updated')`.
- **`deleted(Model $model)`**: Helper for `on($model)->did('deleted')`.
- **`restored(Model $model)`**: Helper for `on($model)->did('restored')`.
- **`approved(Model $model)`**: Helper for `on($model)->did('approved')`.
- **`rejected(Model $model)`**: Helper for `on($model)->did('rejected')`.

### Metadata Methods

- **`with(array $meta)`** / **`context(array $meta)`**: Add extra metadata.
- **`oldNew($old, $new)`**: Capture before/after state.
- **`changes(array $changes)`**: Capture updated changes.
- **`tags(array|string $tags)`**: Categorize with tags.
- **`severity(string|int $severity)`**: Set log severity.
- **`correlationId(string $id)`**: Set a correlation ID for cross-service tracking.
- **`requestId(string $id)`**: Explicitly set the request ID (defaults to app context if available).
- **`externalRef(string $ref)`**: Attach an external reference (e.g., Stripe ID).

### Security Methods

- **`security(string $event)`**: Alias for `did("security: {$event}")`.
- **`ip(?string $ip = null)`**: Set IP address (auto-resolved from request if null).
- **`agent(?string $agent = null)`**: Set User Agent (auto-resolved from request if null).

### Status Methods

- **`status(string $status, ?string $reason = null)`**: Set status (default: `success`).
- **`success()`**: Set status to `success`.
- **`failed(?string $reason = null)`**: Set status to `failed`.
- **`warning(string $reason)`**: Set status to `warning`.
- **`info(string $reason)`**: Set status to `info`.

### Execution Methods

- **`log()`**: Finalize and save the activity record. Returns the `Activity` model instance.
- **`silent()`**: Prevent the activity from being logged.
- **`at(Carbon $at)`**: Set a custom timestamp.

---

## Activity Model

### Properties

- `actor`: Morph relationship to the performer.
- `subject`: Morph relationship to the target.
- `action`: String identifier of what happened.
- `status`: success, failed, warning, etc.
- `meta`: JSON casted array of extra data.

### Methods

- **`message()`**: Returns a human-readable message using `MessageBuilder`.
- **`ip()`**: Returns the IP address (checks `ip_address` column or `meta['ip']`).
- **`userAgent()`**: Returns the User Agent (checks `user_agent` column or `meta['agent']`).
- **`isSystem()`**: Returns true if logged via `bySystem()`.
- **`isGuest()`**: Returns true if logged via `byGuest()`.
- **`jobName()`**: Returns the job name if logged via `byJob()`.

### Scopes

- **`byActor(Model $actor)`**: Filter logs by a specific actor.
- **`forSubject(Model $subject)`**: Filter logs by a specific subject.
- **`whereAction(string $action)`**: Filter by action name.
