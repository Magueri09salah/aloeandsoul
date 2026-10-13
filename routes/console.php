<?php

use App\Models\AuditLog;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
| Shared hosting has no long-running workers: a single cron entry runs
| `php artisan schedule:run` every minute (see docs/DEPLOYMENT.md).
*/

// Process queued jobs (e.g. notification emails), then exit.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Remove abandoned carts and old audit entries.
Schedule::command('model:prune', ['--model' => [Cart::class, AuditLog::class]])->daily()->at('03:10');

// Expired password reset tokens.
Schedule::command('auth:clear-resets')->daily()->at('03:20');

// The database cache store (used for rate limits) does not delete expired rows itself.
Schedule::call(fn () => DB::table(config('cache.stores.database.table', 'cache'))
    ->where('expiration', '<', now()->getTimestamp())
    ->delete())->hourly()->name('prune-expired-cache');
