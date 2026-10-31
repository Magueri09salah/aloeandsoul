<?php

use App\Models\AuditLog;
use App\Models\Cart;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
| Shared hosting has no long-running workers: a single cron entry runs
| `php artisan schedule:run` every minute (see docs/DEPLOYMENT.md).
| Hostinger disables proc_open, so every task runs inside that process
| (Artisan::call) instead of as a separate command.
*/

// Process queued jobs (e.g. notification emails), one at a time, for up to
// 50 seconds. `--once` avoids the daemon loop, which needs pcntl.
Schedule::call(function () {
    $retryAfter = (int) config('queue.connections.database.retry_after', 90);
    $pending = fn () => DB::table(config('queue.connections.database.table', 'jobs'))
        ->where('available_at', '<=', now()->getTimestamp())
        ->where(fn ($q) => $q->whereNull('reserved_at')->orWhere('reserved_at', '<=', now()->getTimestamp() - $retryAfter))
        ->exists();

    $until = now()->addSeconds(50);
    while (now()->lt($until) && $pending()) {
        Artisan::call('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 3]);
    }
})->everyMinute()->name('queue-work')->withoutOverlapping(5);

// Remove abandoned carts and old audit entries.
Schedule::call(fn () => Artisan::call('model:prune', ['--model' => [Cart::class, AuditLog::class]]))
    ->daily()->at('03:10')->name('prune-models');

// Expired password reset tokens.
Schedule::call(fn () => Artisan::call('auth:clear-resets'))->daily()->at('03:20')->name('clear-resets');

// The database cache store (used for rate limits) does not delete expired rows itself.
Schedule::call(fn () => DB::table(config('cache.stores.database.table', 'cache'))
    ->where('expiration', '<', now()->getTimestamp())
    ->delete())->hourly()->name('prune-expired-cache');
