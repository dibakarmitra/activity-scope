<?php

use Dibakar\ActivityScope\Facades\ActivityScope;
use Illuminate\Support\Facades\Auth;

/**
 * dibakar/activity-scope
 * Comprehensive Usage Examples
 * 
 * This file demonstrates the full capabilities of the ActivityScope package.
 */

// ==========================================
// 1. Basic Logging
// ==========================================

// Simple global helper usage
activity()->did('system_booted')->log();

// Verbose syntax using Facade
ActivityScope::did('cache_cleared')->log();

// ==========================================
// 2. Actors (Who did it?)
// ==========================================

// Auto-resolution (default): uses Auth::user()
// activity()->did('viewed_dashboard')->log();

// Explicit Actor
activity()->by($adminUser)->did('banned_user')->log();

// System Actor (background tasks/CLI)
activity()->bySystem()->did('backup_completed')->log();

// Job Actor
activity()->byJob('ImportProductsJob')->did('import_started')->log();

// Guest/Anonymous
activity()->byGuest()->did('public_page_view')->log();

// ==========================================
// 3. Subjects (What was acted upon?)
// ==========================================

// Single Subject
activity()->on($post)->did('published')->log();

// Aliases for readability
activity()->for($order)->did('shipped')->log();

// Manual Subject (Non-Model)
activity()
    ->subject('S3Bucket')
    ->subjectId('exports-2023')
    ->did('purged')
    ->log();

// Multiple Subjects (Polymorhpic)
// Useful for batch operations
activity()
    ->onMany([$post1, $post2, $post3])
    ->did('bulk_deleted')
    ->log();

// ==========================================
// 4. Action Shortcuts
// ==========================================

activity()->created($user)->log();
activity()->updated($profile)->log();
activity()->deleted($comment)->log();
activity()->restored($file)->log();
activity()->approved($application)->log();
activity()->rejected($request)->log();

// Security specific shortcut
activity()->security('login_failed')->log(); // action: "security:login_failed"

// ==========================================
// 5. Context & Metadata
// ==========================================

activity()
    ->did('search_performed')
    ->with(['query' => 'laravel packages', 'results_count' => 50])
    ->context(['filter' => 'popular']) // alias for with()
    ->log();

// Tracking changes
activity()
    ->on($article)
    ->did('updated')
    ->changes($article->getChanges())
    ->oldNew($oldTitle, $newTitle)
    ->log();

// Tagging & Categorization
activity()
    ->category('audit')
    ->tags(['billing', 'urgent'])
    ->did('invoice_generated')
    ->log();

// ==========================================
// 6. Privacy & Security
// ==========================================

activity()
    ->private() // Marks check as private in meta
    ->sensitive() // Flag content as sensitive
    ->ip('1.2.3.4') // Manual IP override
    ->with([
        'api_key' => 'sk_live_123456789', // Will be auto-redacted to [REDACTED]
        'password' => 'secret123'         // Will be auto-redacted
    ])
    ->did('credentials_updated')
    ->log();

// ==========================================
// 7. Status & Severity
// ==========================================

activity()->success()->did('deployment_finished')->log();

activity()
    ->failed('Connection Timeout')
    ->severity('critical')
    ->did('external_api_sync')
    ->log();

activity()
    ->warning('Disk space low')
    ->severity('medium')
    ->did('health_check')
    ->log();

// ==========================================
// 8. Control Flow & Fluency
// ==========================================

// Conditional Execution
activity()
    ->when(Auth::user()->isAdmin(), function ($builder) {
        $builder->tags(['admin_action']);
    })
    ->did('settings_changed')
    ->log();

// Silent Mode (Dry Run)
// Builds the activity but prevents saving/dispatching
activity()
    ->silent()
    ->did('test_run')
    ->log();

// Tap (for advanced customization)
activity()
    ->tap(function ($builder) {
        // Do custom logic
    })
    ->did('tapped')
    ->log();

// ==========================================
// 9. Timestamps & IDs
// ==========================================

activity()
    ->at(now()->subDay()) // Custom timestamp
    ->correlationId('job-123-abc')
    ->requestId('req-xyz-789')
    ->externalRef('stripe_ch_123')
    ->did('historical_import')
    ->log();
