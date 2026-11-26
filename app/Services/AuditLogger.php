<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Throwable;

class AuditLogger
{
    private static bool $recordingError = false;

    public static function logAccess(
        string $event,
        string $status = 'success',
        ?Request $request = null,
        array $meta = []
    ): void {
        try {
            $req = $request ?? request();

            AccessLog::create([
                'accountID'  => $meta['accountID'] ?? session('accountID'),
                'username'   => $meta['username'] ?? session('username'),
                'email'      => $meta['email'] ?? session('email'),
                'role'       => $meta['role'] ?? session('role'),
                'event'      => $event,
                'status'     => $status,
                'ip_address' => $req ? $req->ip() : null,
                'user_agent' => $req ? $req->userAgent() : null,
                'details'    => $meta['details'] ?? null,
                'created_at' => now(),
            ]);
        } catch (Throwable $ignored) {
            // Silently ignore if DB is temporarily unavailable or unmigrated
        }
    }

    public static function logError(
        string $level,
        string $message,
        ?Throwable $exception = null
    ): void {
        if (self::$recordingError) {
            return;
        }

        self::$recordingError = true;

        try {
            $req = app()->runningInConsole() ? null : request();

            ErrorLog::create([
                'accountID'  => session('accountID'),
                'username'   => session('username'),
                'level'      => strtolower($level),
                'exception'  => $exception ? get_class($exception) : null,
                'message'    => $message,
                'file'       => $exception ? str_replace(base_path() . DIRECTORY_SEPARATOR, '', $exception->getFile()) : null,
                'line'       => $exception ? $exception->getLine() : null,
                'url'        => $req ? $req->fullUrl() : 'CLI / Artisan',
                'method'     => $req ? $req->method() : 'CLI',
                'ip_address' => $req ? $req->ip() : null,
                'trace'      => $exception ? mb_substr($exception->getTraceAsString(), 0, 15000) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $ignored) {
            // Prevent infinite recursion if error_logs table is inaccessible
        } finally {
            self::$recordingError = false;
        }
    }
}