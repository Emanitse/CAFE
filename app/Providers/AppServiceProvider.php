<?php

namespace App\Providers;

use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
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
        // Share global branding settings across all views
        View::composer('*', function ($view) {
            $setting = SystemSetting::current();
            $view->with([
                'systemName' => $setting->system_name,
                'systemLogoUrl' => $setting->logo_url,
                'systemLogoPath' => $setting->logo_public_path,
            ]);
        });

        // Automatic error & warning logging for Audit Trail
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