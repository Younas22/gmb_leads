<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule subscription expiry check daily at midnight
Schedule::command('subscriptions:check-expired')->daily()->at('00:00');

// Phase 3 — Gmail sending queue (separate from AI). Listed BEFORE the AI worker because
// schedule:run starts commands one after another: with an empty queue this exits at once,
// so emails are never stuck behind a ~50s AI worker run. Jobs: max 4 HTTPS calls, 75s.
Schedule::command(sprintf(
    'queue:work %s --queue=%s --stop-when-empty --max-time=50 --sleep=3 --tries=%d --timeout=%d --memory=128',
    config('gmail.queue.connection', 'mail_database'),
    // Phase 5: the same single worker also drains Resend webhooks and Resend sends (in this
    // priority order), so shared hosting never runs an extra worker process.
    implode(',', [config('gmail.queue.name', 'mail'), config('resend_user.queue.webhook', 'webhook'), config('resend_user.queue.send', 'resend')]),
    (int) config('gmail.queue.tries', 3),
    (int) config('gmail.queue.timeout', 60),
))
    ->everyMinute()
    ->withoutOverlapping(5);

// Sends interrupted mid-request become "unknown outcome" (never auto re-sent)
Schedule::command('email:recover-stuck')->everyTenMinutes()->withoutOverlapping();

// AI Outreach — queue processing without a daemon (Hostinger shared hosting):
// every minute, work the AI queue for up to ~50s, then exit. A worker never starts a
// new job after --max-time, but the job it is running may take up to job_timeout
// (170s), so withoutOverlapping holds a lock for up to 10 minutes to keep ONE worker.
// Needs the standard Laravel cron:  * * * * * cd /path/to/app && php artisan schedule:run
// (See docs/ai-outreach-operations.md for a cron line that works without the scheduler.)
Schedule::command(sprintf(
    'queue:work %s --queue=%s --stop-when-empty --max-time=50 --sleep=3 --tries=%d --timeout=%d --memory=256',
    config('ai.analysis.connection', 'ai_database'),
    config('ai.analysis.queue', 'ai'),
    (int) config('ai.analysis.job_tries', 3),
    (int) config('ai.analysis.job_timeout', 170),
))
    ->everyMinute()
    ->withoutOverlapping(10);

// Close items whose worker was killed (keeps leads from staying "Analyzing" forever)
Schedule::command('ai:recover-stuck')->everyTenMinutes()->withoutOverlapping();

// Retention for website audits
Schedule::command('ai:prune-audits')->daily()->at('03:30');

// Phase 4: retention for link-tracking data (batched deletes, no queue worker needed)
Schedule::command('email:prune-tracking')->daily()->at('03:45')->withoutOverlapping();

// Phase 5: Resend housekeeping (DB only: unmatched webhook retries, scheduled campaigns, receipt retention)
Schedule::command('resend:maintain')->everyFiveMinutes()->withoutOverlapping();

// Lead-folder sends: one AI email per run and folder (the mail worker above then sends it)
Schedule::command('outreach:folder-sends')->everyMinute()->withoutOverlapping(5);
