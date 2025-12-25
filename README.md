# Activity Scope — Audit Trail & Activity Logging for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dibakar/activity-scope.svg?style=flat-square)](https://packagist.org/packages/dibakar/activity-scope)
[![Total Downloads](https://img.shields.io/packagist/dt/dibakar/activity-scope.svg?style=flat-square)](https://packagist.org/packages/dibakar/activity-scope)
[![License](https://img.shields.io/github/license/dibakar/activity-scope?style=flat-square)](https://github.com/dibakarmitra/activity-scope)
[![PHP Version](https://img.shields.io/packagist/php-v/dibakar/activity-scope?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x+-orange?style=flat-square)](https://laravel.com/)

## 📋 Table of Contents

- [About](#about)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Model Integration](#model-integration)
  - [Query Scopes](#query-scopes)
  - [Advanced Features](#advanced-features)
  - [Security & Privacy](#security--privacy)
  - [Events & Hooks](#events--hooks)
- [Examples](#examples)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)
- [Support](#support)

## 📖 About {#about}

Activity Scope is a high-performance **audit trail** and **activity logging** system for Laravel. It allows you to effortlessly track user actions, monitor Eloquent model changes, and maintain a historical record of your application's state—all while prioritizing privacy and high performance.

> **📚 Check out the [API.md](API.md)** for a full list of available builder methods and scopes.

## ✨ Features {#features}

- **🔧 Fluent Activity Builder**: Expressive, chainable API for logging any event with ease
- **🤖 Smarter Auto-Actor**: Automatically resolves the current authenticated user as the actor
- **🔒 Privacy First**: Built-in IP anonymization and recursive sensitive metadata scrubbing
- **🧩 Independent Modular Traits**:
  - `HasActivities`: Core relationships and log retrieval
  - `LogsActivity`: Zero-config auto-logging for Eloquent events
- **🔗 Relationship Support**: Log activity across model relationships or on multiple models at once
- **📝 Human-Readable Logs**: Integrated `MessageBuilder` to transform raw logs into meaningful alerts
- **🛡️ Security Ready**: Dedicated security event logging for audits
- **🎯 Type Safety**: Built with PHP 8.1+ features and strict typing

## 📋 Requirements {#requirements}

- **PHP**: >= 8.1
- **Laravel**: ^10.0|^11.0|^12.0
- **Illuminate/Database**: ^10.0|^11.0|^12.0
- **Illuminate/Support**: ^10.0|^11.0|^12.0

## 🚀 Installation {#installation}

You can install the package via composer:

```bash
composer require dibakar/activity-scope
```

### Quick Setup

Publish the config and migrations with our handy install command:

```bash
php artisan activityscope:install
php artisan migrate
```

### Manual Setup

Alternatively, you can publish them manually via `vendor:publish`:

```bash
# Publish config file
php artisan vendor:publish --tag=activityscope-config

# Publish migration files
php artisan vendor:publish --tag=activityscope-migrations

# Run migrations
php artisan migrate
```

## ⚙️ Configuration {#configuration}

After publishing the config file, you can customize the behavior in `config/activityscope.php`:

```php
return [
    // Enable/disable activity logging
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
    
    // Enable automatic model event logging
    'auto_log' => env('ACTIVITY_LOGGER_AUTO_LOG', false),
    'auto_log_events' => ['created', 'updated', 'deleted'],
    
    // Privacy & Security settings
    'privacy' => [
        'sanitize_data' => env('ACTIVITY_SANITIZE_DATA', true),
        'sensitive_fields' => [
            'password', 'password_confirmation', 'secret', 'token', 
            'key', 'card', 'cvv', 'api_key', 'credit_card', 'ssn'
        ],
        'track_ip_address' => env('ACTIVITY_TRACK_IP', true),
        'anonymize_ip' => env('ACTIVITY_ANONYMIZE_IP', false),
    ],
];
```

## 🛠 Usage {#usage}

### Basic Usage

#### Using the Global Helper

The most common way to log activity is using the `activity()` helper:

```php
use App\Models\Post;
use App\Models\User;

$user = auth()->user();
$post = Post::find(1);

// Simple activity logging
activity()
    ->on($post)
    ->did('published')
    ->log();

// With additional context
activity()
    ->on($post)
    ->did('updated')
    ->with([
        'title' => $post->title,
        'scheduled_at' => now()->addDays(7),
        'editor' => 'tinymce'
    ])
    ->log();

// Using helper methods for common actions
activity()
    ->created($post) // Equivalent to ->on($post)->did('created')
    ->by($user)
    ->success()
    ->log();
```

#### Automatic Actor Resolution

By default, the package automatically attaches `auth()->user()` to any activity. You can override this manually:

```php
// Manual actor specification
activity()
    ->by($admin)
    ->on($user)
    ->did('suspended')
    ->with(['reason' => 'Policy violation'])
    ->log();

// System-performed activities
activity()
    ->bySystem()
    ->did('backup_completed')
    ->with(['size' => '2.5GB', 'duration' => '45s'])
    ->log();

// Guest user activities
activity()
    ->byGuest()
    ->on($product)
    ->did('viewed')
    ->log();

// Job/Queue context
activity()
    ->byJob('ProcessVideoUpload')
    ->on($video)
    ->did('processed')
    ->log();
```

### Model Integration

#### Automatic Model Logging

Add the `LogsActivity` trait to your model for automatic auditing of `created`, `updated`, and `deleted` events:

```php
<?php

namespace App\Models;

use Dibakar\ActivityScope\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use LogsActivity;
    
    protected $fillable = ['title', 'content', 'status'];
}
```

#### Manual Model Integration

Use the `HasActivities` trait to gain access to relationships and a model-contextual activity builder:

```php
<?php

namespace App\Models;

use Dibakar\ActivityScope\Traits\HasActivities;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasActivities;
    
    protected $fillable = ['name', 'email', 'role'];
}

// Log an activity performed BY this user
$user->newActivity()
    ->on($post)
    ->did('reviewed')
    ->with(['rating' => 5, 'comments' => 'Excellent post'])
    ->log();

// Access relationships
$user->actions;    // Activities performed BY this user
$user->activities; // Activities performed ON this user
```

#### Advanced Model Configuration

Customize what gets logged and when:

```php
<?php

namespace App\Models;

use Dibakar\ActivityScope\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use LogsActivity;
    
    protected $fillable = ['customer_id', 'total', 'status'];
    
    /**
     * Customize which events to log
     */
    protected function getActivityEvents(): array
    {
        return ['created', 'updated'];
    }
    
    /**
     * Customize what data gets logged
     */
    protected function getActivityData(): array
    {
        return [
            'total' => $this->total,
            'status' => $this->status,
            'customer_name' => $this->customer->name,
        ];
    }
    
    /**
     * Conditionally skip logging
     */
    protected function shouldLogActivity(string $event): bool
    {
        return $this->status !== 'draft';
    }
}
```

### Query Scopes

#### Filtering Activities

Use the powerful query scopes to retrieve specific activities:

```php
use Dibakar\ActivityScope\Models\Activity;

// Filter by Actor (User, System, etc.)
$activities = Activity::by($user)->get();
$systemActivities = Activity::bySystem()->get();
$guestActivities = Activity::byGuest()->get();

// Filter by Subject (Post, Order, etc.)
$postActivities = Activity::onSubject($post)->get();
// or using the alias
$orderActivities = Activity::forSubject($order)->get();

// Filter by Action
$updateActivities = Activity::did('updated')->get();
$multipleActions = Activity::did(['created', 'updated', 'deleted'])->get();

// Filter by Status
$failedActivities = Activity::failed()->get();
$warningActivities = Activity::warning()->get();

// Filter by Date Range
$recentActivities = Activity::today()->get();
$lastWeekActivities = Activity::lastWeek()->get();
$customRange = Activity::between($startDate, $endDate)->get();

// Combine multiple filters
$userPostUpdates = Activity::by($user)
    ->onSubject($post)
    ->did('updated')
    ->where('status', 'success')
    ->orderBy('created_at', 'desc')
    ->get();
```

#### Advanced Querying

```php
// Filter by tags
$securityEvents = Activity::withTag('security')->get();
$authEvents = Activity::withTags(['auth', 'login'])->get();

// Filter by category
$billingActivities = Activity::category('billing')->get();

// Filter by metadata
$highValueOrders = Activity::whereMeta('total', '>', 1000)->get();
$specificUser = Activity::whereMeta('user_agent', 'like', '%Chrome%')->get();

// Complex relationships
$userActionsOnPosts = Activity::by($user)
    ->whereHas('subject', function ($query) {
        $query->where('subject_type', 'App\\Models\\Post');
    })
    ->get();
```

### Advanced Features

#### Control Flow Logic

Utilize fluent methods to control when and how activities are logged:

```php
activity()
    ->when($user->isAdmin(), function ($builder) {
        $builder->warning('Admin override detected');
    })
    ->unless($post->isPublished(), function ($builder) {
        $builder->info('Draft post accessed');
    })
    ->tap(function ($builder) use ($request) {
        // Access builder for additional setup
        $builder->with(['request_id' => $request->id]);
    })
    ->silent() // Prevent saving to DB, useful for conditional dry-runs
    ->log();

// Conditional logging
$shouldLog = $user->hasPermission('audit-access');
activity()
    ->when($shouldLog, fn($b) => $b->did('accessed_sensitive_data'))
    ->when(!$shouldLog, fn($b) => $b->silent())
    ->on($report)
    ->log();
```

### Security & Privacy

Built-in tools to handle sensitive data and privacy requirements:

```php
activity()
    ->private() // Marks activity as private
    ->sensitive() // Flags as containing sensitive info
    ->ip('1.2.3.4') // Manually set IP (auto-handled usually)
    ->with(['api_key' => 'sk_live_123456']) // Automatically redacted to [REDACTED]
    ->log();

// Privacy-first logging with automatic data scrubbing
activity()
    ->by($user)
    ->on($payment)
    ->did('processed')
    ->with([
        'card_number' => '4242-4242-4242-4242', // Auto-redacted
        'cvv' => '123', // Auto-redacted
        'amount' => 99.99, // Safe to log
        'currency' => 'USD', // Safe to log
    ])
    ->sensitive()
    ->log();
```

#### Status & Severity

Categorize activities by outcome and importance:

```php
// Success activities (default)
activity()
    ->success()
    ->on($order)
    ->did('shipped')
    ->log();

// Failed activities with reasons
activity()
    ->failed('Invalid OTP') // Status: failed, Reason: Invalid OTP
    ->on($user)
    ->did('login_attempt')
    ->log();

// Warning levels
activity()
    ->warning('Rate limited')
    ->by($ip)
    ->did('api_request')
    ->log();

// Custom severity levels
activity()
    ->severity('critical') // Custom severity level
    ->bySystem()
    ->did('database_backup_failed')
    ->log();

// Info levels
activity()
    ->info('User session extended')
    ->by($user)
    ->log();
```

#### Metadata & Context

Rich context management for detailed audit trails:

```php
activity()
    ->with(['browser' => 'Chrome'])     // Merge metadata
    ->context(['os' => 'Linux'])        // Alias for with()
    ->changes(['role' => 'editor'])     // Track changes
    ->tags(['auth', 'security'])        // Tagging support
    ->category('access_control')
    ->correlationId('req_123456')       // Cross-service tracking
    ->requestId('req_789012')           // Request identification
    ->externalRef('stripe_ch_123')      // External reference
    ->log();

// Tracking before/after states
activity()
    ->on($user)
    ->did('profile_updated')
    ->oldNew($oldAttributes, $newAttributes)
    ->log();

// Using changes helper
activity()
    ->on($order)
    ->did('status_changed')
    ->changes([
        'old_status' => 'pending',
        'new_status' => 'confirmed',
        'changed_by' => auth()->user()->id,
    ])
    ->log();
```

### Events & Hooks

#### Human-Readable Messages

Transform any activity record into a readable string using the integrated `MessageBuilder`:

```php
$activity = Activity::first();
echo $activity->message(); // "System published Post 'Laravel Rocks'"

// Custom message formatting
echo $activity->getMessageBuilder()
    ->actorFirst()
    ->includeTime()
    ->build();
// "John Doe published Post 'Laravel Rocks' 2 minutes ago"
```

#### Event System

Listen to activity events to trigger additional actions:

```php
use Dibakar\ActivityScope\Events\ActivityLogged;

// In your EventServiceProvider
protected $listen = [
    ActivityLogged::class => [
        SendActivityNotification::class,
        LogToExternalService::class,
        UpdateAnalytics::class,
    ],
];

// Custom listener example
class SendActivityNotification
{
    public function handle(ActivityLogged $event)
    {
        $activity = $event->activity;
        
        if ($activity->isCritical()) {
            // Send email notification
            Mail::to('admin@example.com')
                ->send(new CriticalActivityAlert($activity));
        }
        
        if ($activity->hasTag('security')) {
            // Send to security team
            $this->securityService->alert($activity);
        }
    }
}
```

#### Custom Event Handlers

```php
// In your service provider
Event::listen(ActivityLogged::class, function ($event) {
    $activity = $event->activity;
    
    // Log to external monitoring
    if ($activity->status === 'failed') {
        Sentry::captureMessage($activity->message());
    }
    
    // Update user metrics
    if ($activity->subject_type === 'App\Models\User') {
        Cache::increment("user_activity_{$activity->subject_id}");
    }
});
```

## 📚 Examples {#examples}

Check out the `examples/` directory for comprehensive usage examples:

- **[basic_usage.php](examples/basic_usage.php)** - Simple logging examples
- **[model_integration.php](examples/model_integration.php)** - Model trait usage
- **[query_scopes.php](examples/query_scopes.php)** - Advanced querying
- **[complex_workflows.php](examples/complex_workflows.php)** - Real-world scenarios

### Quick Example

```php
<?php

// Complete e-commerce order workflow
class OrderService
{
    public function processOrder(Order $order, User $user)
    {
        // Log order creation
        activity()
            ->by($user)
            ->created($order)
            ->with(['total' => $order->total, 'items' => $order->items->count()])
            ->tags(['order', 'ecommerce'])
            ->log();
        
        try {
            // Process payment
            $payment = $this->paymentService->charge($order);
            
            activity()
                ->by($user)
                ->on($payment)
                ->did('processed')
                ->success()
                ->with(['amount' => $payment->amount, 'gateway' => 'stripe'])
                ->log();
            
            // Update order status
            $order->update(['status' => 'confirmed']);
            
            activity()
                ->by($user)
                ->on($order)
                ->did('confirmed')
                ->changes(['status' => 'confirmed'])
                ->success()
                ->log();
                
        } catch (PaymentException $e) {
            activity()
                ->by($user)
                ->on($order)
                ->failed($e->getMessage())
                ->severity('critical')
                ->tags(['payment', 'error'])
                ->log();
            
            throw $e;
        }
    }
}
```

## 🧪 Testing {#testing}

### Basic Assertions

You can use the built-in test helper to assert activities were logged:

```php
use Dibakar\ActivityScope\Models\Activity;

// Basic database assertions
$this->assertDatabaseHas('activities', [
    'action' => 'shipped',
    'subject_id' => $order->id,
    'subject_type' => 'App\\Models\\Order',
]);

// Count assertions
$this->assertEquals(3, Activity::count());
$this->assertEquals(1, Activity::by($user)->count());

// Model relationship assertions
$this->assertTrue($user->actions->contains('action', 'created'));
$this->assertTrue($order->activities->contains('action', 'updated'));
```

### Advanced Testing

```php
public function test_activity_logging_flow()
{
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    // Perform action that should log activity
    $this->actingAs($user)
        ->post("/posts/{$post->id}/publish");
    
    // Assert activity was logged correctly
    $activity = Activity::where('action', 'published')
        ->by($user)
        ->onSubject($post)
        ->first();
    
    $this->assertNotNull($activity);
    $this->assertEquals('success', $activity->status);
    $this->assertArrayHasKey('published_at', $activity->meta);
    
    // Test message generation
    $expectedMessage = "{$user->name} published Post '{$post->title}'";
    $this->assertEquals($expectedMessage, $activity->message());
}
```

### Test Helpers

```php
// Custom test helper methods
trait ActivityTestHelpers
{
    protected function assertActivityLogged($action, $subject = null, $actor = null)
    {
        $query = Activity::where('action', $action);
        
        if ($subject) {
            $query->onSubject($subject);
        }
        
        if ($actor) {
            $query->by($actor);
        }
        
        $this->assertTrue(
            $query->exists(),
            "Activity '{$action}' was not logged"
        );
    }
    
    protected function assertNoActivityLogged($action, $subject = null)
    {
        $query = Activity::where('action', $action);
        
        if ($subject) {
            $query->onSubject($subject);
        }
        
        $this->assertFalse(
            $query->exists(),
            "Activity '{$action}' was unexpectedly logged"
        );
    }
}
```

Run package tests with:

```bash
composer test
```

For code analysis:

```bash
composer analyse
```

## 📖 API Reference {#api-reference}

For a comprehensive list of all available methods, scopes, and advanced features, check out our detailed **[API Documentation](API.md)**.

### Quick Reference

```php
// Global helper
activity()                    // Get ActivityBuilder instance

// Actor methods
->by($user)                   // Set actor
->bySystem()                  // System actor
->byGuest()                   // Guest actor
->byJob('job-name')           // Job context

// Subject methods
->on($model)                  // Set subject
->for($model)                 // Alias for on()
->onMany([$model1, $model2]) // Multiple subjects

// Action methods
->did('action')               // Set action
->created($model)             // Created action shortcut
->updated($model)             // Updated action shortcut
->deleted($model)             // Deleted action shortcut

// Metadata methods
->with($data)                 // Add metadata
->changes($data)              // Track changes
->tags(['tag1', 'tag2'])     // Add tags
->severity('high')            // Set severity

// Status methods
->success()                   // Success status
->failed('reason')            // Failed status with reason
->warning('message')          // Warning status
->info('message')             // Info status

// Execution
->log()                       // Save activity
->silent()                    // Don't save (dry run)
```

## 🤝 Contributing {#contributing}

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/dibakarmitra/activity-scope.git
cd activity-scope

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse
```

### Guidelines

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation for any API changes
- Ensure all tests pass before submitting

## 📝 Changelog {#changelog}

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## 📄 License {#license}

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support {#support}

- **Issues**: [GitHub Issues](https://github.com/dibakarmitra/activity-scope/issues)
- **Documentation**: [API Reference](API.md)
- **Examples**: Check the `examples/` directory

---

**Made by [Dibakar Mitra](https://github.com/dibakarmitra)**
