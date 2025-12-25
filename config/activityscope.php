<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Configuration
    |--------------------------------------------------------------------------
    */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'table' => 'activities',
        'connection' => env('DB_CONNECTION', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Actor Resolution
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic actor detection and resolution.
    |
    */
    'actor' => [
        'auto_resolve' => env('ACTIVITY_AUTO_RESOLVE_ACTOR', true),
        'system_actor_id' => env('ACTIVITY_SYSTEM_ACTOR_ID'),
        'default_guard' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, models using the LogsActivity trait will automatically
    | log 'created', 'updated', and 'deleted' events.
    |
    */
    'auto_log' => env('ACTIVITY_LOGGER_AUTO_LOG', false),
    'auto_log_events' => ['created', 'updated', 'deleted'],
    'auto_actor' => true,
    'require_actor' => false,
    'auto_log_models' => [
        // App\\Models\\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy & Security
    |--------------------------------------------------------------------------
    |
    | Configuration for IP anonymization and sensitive data handling.
    |
    */
    'privacy' => [
        'sanitize_data' => env('ACTIVITY_SANITIZE_DATA', true),
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'secret',
            'token',
            'key',
            'card',
            'cvv',
            'cvc',
            'pin',
            'api_key',
            'private_key',
            'credit_card',
            'ssn',
        ],
        'track_ip_address' => env('ACTIVITY_TRACK_IP', true),
        'anonymize_ip' => env('ACTIVITY_ANONYMIZE_IP', false),
        'ipv4_mask' => 24,
        'ipv6_mask' => 48,
        'track_user_agent' => env('ACTIVITY_TRACK_USER_AGENT', true),
        'hide_sensitive' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for activity logging.
    |
    */
    'performance' => [
        'max_payload_size' => 65535, // MySQL text limit
        'batch_insert' => false,
        'queue_logging' => false, // Log activities asynchronously
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors are handled during activity logging.
    |
    */
    'error_handling' => [
        'throw_on_error' => env('ACTIVITY_THROW_ON_ERROR', false),
        'log_failures' => env('ACTIVITY_LOG_FAILURES', true),
        'dispatch_events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning Configuration
    |--------------------------------------------------------------------------
    |
    | Define how many days activity logs should be kept before being automatically
    | pruned. Set to null to disable pruning.
    |
    */
    'prune_after_days' => env('ACTIVITY_LOGGER_PRUNE_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Model Attribute Mappings
    |--------------------------------------------------------------------------
    |
    | Define which attribute should be used for specific models.
    | This takes precedence over the defaults list above.
    |
    */
    'mappings' => [
        // \App\Models\User::class => 'name',
        // \App\Models\Post::class => 'title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Log Name
    |--------------------------------------------------------------------------
    |
    | Set the default log name for activities.
    |
    */
    'default_log_name' => env('ACTIVITY_DEFAULT_LOG_NAME', 'default'),

];
