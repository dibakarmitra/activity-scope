# Activity Scope: Laravel Audit Trail Package

Looking for a powerful activity logging system for your Laravel applications? Meet Activity Scope - a comprehensive audit trail package that combines performance, privacy, and developer experience.

## Why Activity Scope?

In modern Laravel development, tracking user actions and maintaining audit trails is essential. Most existing solutions either:

- Are overly complex with steep learning curves
- Sacrifice performance for features  
- Ignore privacy concerns in modern applications
- Force rigid logging patterns

Activity Scope changes this with a thoughtful approach that prioritizes what developers actually need.

## Key Features

### Fluent Builder API

```php
activity()
    ->on($post)
    ->did('published')
    ->with(['scheduled_at' => now()])
    ->success()
    ->log();
```

Clean, readable, and chainable API that makes sense.

### Smart Actor Resolution

```php
// Automatically uses auth()->user()
activity()->created($post)->log();

// Override when needed
activity()->by($admin)->on($user)->did('suspended')->log();
```

Works the way you expect, with sensible defaults.

### Privacy-First Design

```php
activity()
    ->sensitive()
    ->with(['api_key' => 'sk_live_123456']) // Auto-redacted
    ->log();
```

Built-in IP anonymization, sensitive data scrubbing, and privacy controls.

### Modular Traits

```php
// Automatic logging
class Post extends Model {
    use LogsActivity;
}

// Manual control
class User extends Model {
    use HasActivities;
    
    function login() {
        $this->newActivity()->did('logged_in')->log();
    }
}
```

Use what you need, ignore what you don't.

## Getting Started

### Installation

```bash
composer require dibakar/activity-scope
php artisan activityscope:install
php artisan migrate
```

### Your First Activity Log

```php
// That's it. You're ready.
activity()
    ->on($user)
    ->did('registered')
    ->log();
```

## Real-World Use Cases

### E-commerce Order Tracking

```php
class OrderService {
    public function processOrder(Order $order, User $user) {
        activity()
            ->by($user)
            ->created($order)
            ->with(['total' => $order->total, 'items' => $order->items->count()])
            ->tags(['order', 'ecommerce'])
            ->log();
            
        if ($payment->successful) {
            activity()
                ->by($user)
                ->on($order)
                ->did('payment_confirmed')
                ->success()
                ->log();
        }
    }
}
```

### Security Event Logging

```php
// Failed login attempts
activity()
    ->byGuest()
    ->on($user)
    ->failed('Invalid credentials')
    ->severity('warning')
    ->tags(['auth', 'security'])
    ->log();

// Admin actions
activity()
    ->by($admin)
    ->on($user)
    ->did('account_suspended')
    ->severity('critical')
    ->tags(['admin', 'security'])
    ->log();
```

### Analytics Integration

```php
// Track user engagement
activity()
    ->by($user)
    ->on($article)
    ->did('viewed')
    ->with(['duration' => $readingTime, 'source' => 'search'])
    ->tags(['analytics', 'engagement'])
    ->log();
```

## Advanced Features

### Human-Readable Messages

```php
$activity = Activity::first();
echo $activity->message(); 
// "John Doe published Post 'Getting Started with Laravel' 2 minutes ago"
```

### Powerful Query Scopes

```php
// Find all admin actions in last week
$adminActions = Activity::by($admin)
    ->where('created_at', '>=', now()->subDays(7))
    ->where('severity', 'critical')
    ->get();

// Security events
$securityEvents = Activity::withTag('security')
    ->failed()
    ->get();
```

### Performance Optimized

- Efficient Queries: Optimized database interactions
- Queue Support: Async logging when you need it
- Smart Pruning: Automatic cleanup of old logs
- Minimal Overhead: Won't slow down your application

## Security & Compliance

### GDPR Compliant

```php
// Automatic privacy controls
'privacy' => [
    'sanitize_data' => true,
    'anonymize_ip' => true,
    'sensitive_fields' => [
        'password', 'token', 'api_key', 'credit_card', 'ssn'
    ],
],
```

### Audit Trail Standards

- Immutable logs (once written, never changed)
- Complete context (who, what, when, where)
- Tamper-evident (cryptographic hashes available)
- Searchable and filterable

## Developer Experience

### Clean Configuration

```php
// Simple, sensible defaults
return [
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    'auto_log' => env('ACTIVITY_LOGGER_AUTO_LOG', false),
    'privacy' => [
        'sanitize_data' => true,
        'track_ip_address' => true,
        'anonymize_ip' => false,
    ],
];
```

### Comprehensive Testing

```php
// Built-in test helpers
$this->assertActivityLogged('created', $post);
$this->assertDatabaseHas('activities', [
    'action' => 'published',
    'subject_id' => $post->id
]);
```

## Why Choose Activity Scope?

1. **Purpose-Built**: Designed specifically for Laravel's ecosystem
2. **Privacy-First**: Security and compliance built-in
3. **Performance**: Won't slow down your application
4. **Flexible**: Use what you need, ignore what you don't
5. **Intuitive**: API that just makes sense
6. **Production-Ready**: Battle-tested in real applications

## Resources

- **Documentation**: [Complete API Reference](https://github.com/dibakarmitra/activity-scope/blob/main/API.md)
- **GitHub**: [Repository & Issues](https://github.com/dibakarmitra/activity-scope)
- **Packagist**: [Install via Composer](https://packagist.org/packages/dibakar/activity-scope)
- **Examples**: [Real-world code samples](https://github.com/dibakarmitra/activity-scope/tree/main/examples)

## Get Started Today

Stop fighting with complex logging solutions. Activity Scope gives you the power, flexibility, and privacy features you need.

```bash
composer require dibakar/activity-scope
```

Your future self will thank you for choosing a logging solution that actually respects your time and your users' privacy.

---

**Hot Tip**: Check out the [examples directory](https://github.com/dibakarmitra/activity-scope/tree/main/examples) for ready-to-use implementations for common scenarios like e-commerce, user management, and security monitoring.

---

#Laravel #PHP #AuditTrail #ActivityLogging #OpenSource #DeveloperTools
