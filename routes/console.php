<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Backups
Schedule::command('backup:run')->dailyAt('03:00');
Schedule::command('backup:clean')->dailyAt('04:00');

// Horizon
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Activity log cleanup (30 days)
Schedule::command('activitylog:clean')->weekly();

// Health checks
Schedule::command('health:check')->everyMinute();

// Telescope cleanup (48h)
Schedule::command('telescope:prune --hours=48')->everyTwoHours();

// Queue maintenance
Schedule::command('queue:prune-batches --hours=48')->cron('30 2 * * *');
