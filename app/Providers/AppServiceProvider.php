<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $trackedLevels = ['emergency', 'alert', 'critical', 'error', 'warning'];

            if (!in_array(strtolower($event->level), $trackedLevels, true)) {
                return;
            }

            $exception = isset($event->context['exception']) && $event->context['exception'] instanceof Throwable
                ? $event->context['exception']
                : null;

            AuditLogger::logError($event->level, $event->message, $exception);
        });
    }
}