<?php

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** The scheduled task with this name. */
function scheduledTask(string $name): CallbackEvent
{
    return collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === $name);
}

describe('scheduled tasks', function () {
    it('all run inside the scheduler process, because the host disables proc_open', function () {
        $events = collect(app(Schedule::class)->events());

        expect($events)->not->toBeEmpty()
            ->and($events->reject(fn ($event) => $event instanceof CallbackEvent))->toBeEmpty();
    });

    it('works through the queued jobs, then stops', function () {
        config(['queue.default' => 'database']);
        dispatch(fn () => Cache::increment('queued-jobs-ran'));
        dispatch(fn () => Cache::increment('queued-jobs-ran'));
        dispatch(fn () => Cache::increment('queued-jobs-ran'))->delay(now()->addHour());

        scheduledTask('queue-work')->run(app());

        expect(Cache::get('queued-jobs-ran'))->toBe(2)
            ->and(DB::table('jobs')->count())->toBe(1);
    });

    it('runs the daily and hourly clean-ups', function () {
        DB::table('cache')->insert(['key' => 'old', 'value' => 's:1:"x";', 'expiration' => now()->subMinute()->getTimestamp()]);

        foreach (['prune-models', 'clear-resets', 'prune-expired-cache'] as $name) {
            scheduledTask($name)->run(app());
        }

        expect(DB::table('cache')->where('key', 'old')->exists())->toBeFalse();
    });
});
