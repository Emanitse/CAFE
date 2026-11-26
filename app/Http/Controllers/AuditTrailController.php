<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\ErrorLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index()
    {
        return view('components.audit_trail');
    }

    // Fetch filtered Access Logs
    public function getAccessLogs(Request $request)
    {
        $query = AccessLog::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }

        if ($request->filled('fromDate')) {
            $query->whereDate('created_at', '>=', $request->fromDate);
        }

        if ($request->filled('toDate')) {
            $query->whereDate('created_at', '<=', $request->toDate);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('accesslogID')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->created_at)->format('M d, Y h:i:s A');
                return $row;
            });

        $distinctEvents = AccessLog::select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'events' => $distinctEvents,
            'summary' => [
                'total' => $logs->count(),
                'success' => $logs->where('status', 'success')->count(),
                'failed' => $logs->where('status', 'failed')->count(),
                'warning' => $logs->where('status', 'warning')->count(),
            ],
        ]);
    }

    // Fetch filtered Error Logs
    public function getErrorLogs(Request $request)
    {
        $query = ErrorLog::query();

        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        if ($request->filled('fromDate')) {
            $query->whereDate('created_at', '>=', $request->fromDate);
        }

        if ($request->filled('toDate')) {
            $query->whereDate('created_at', '<=', $request->toDate);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('errorlogID')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->created_at)->format('M d, Y h:i:s A');
                return $row;
            });

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'summary' => [
                'total' => $logs->count(),
                'critical' => $logs->whereIn('level', ['critical', 'alert', 'emergency'])->count(),
                'error' => $logs->where('level', 'error')->count(),
                'warning' => $logs->where('level', 'warning')->count(),
            ],
        ]);
    }

    // Export selected Access Logs as PDF
    public function exportAccessPdf(Request $request)
    {
        $rawIds = $request->input('selected_ids', '[]');
        $selectedIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);

        if (empty($selectedIds) || !is_array($selectedIds)) {
            return back()->with('error', 'Please select at least one access log record to export.');
        }

        $logs = AccessLog::whereIn('accesslogID', $selectedIds)
            ->orderByDesc('created_at')
            ->orderByDesc('accesslogID')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->created_at)->format('M d, Y h:i:s A');
                return $row;
            });

        $summary = [
            'total'   => $logs->count(),
            'success' => $logs->where('status', 'success')->count(),
            'failed'  => $logs->where('status', 'failed')->count(),
            'warning' => $logs->where('status', 'warning')->count(),
        ];

        $meta = [
            'generatedAt' => Carbon::now()->format('M d, Y h:i A'),
            'generatedBy' => session('username') ?? 'Superadmin',
            'dateRange'   => $request->input('date_range_label', 'All Dates'),
            'filterLabel' => $request->input('filter_label', 'All Events / All Statuses'),
        ];

        $pdf = Pdf::loadView('exports.access_logs_pdf', compact('logs', 'summary', 'meta'))
            ->setPaper('a4', 'landscape');

        $filename = 'Cafe-Ordering-System-Access-Logs-' . Carbon::now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    // Export selected Error Logs as PDF
    public function exportErrorPdf(Request $request)
    {
        $rawIds = $request->input('selected_ids', '[]');
        $selectedIds = is_array($rawIds) ? $rawIds : json_decode($rawIds, true);

        if (empty($selectedIds) || !is_array($selectedIds)) {
            return back()->with('error', 'Please select at least one error log record to export.');
        }

        $logs = ErrorLog::whereIn('errorlogID', $selectedIds)
            ->orderByDesc('created_at')
            ->orderByDesc('errorlogID')
            ->get()
            ->map(function ($row) {
                $row->formatted_date = Carbon::parse($row->created_at)->format('M d, Y h:i:s A');
                return $row;
            });

        $summary = [
            'total'    => $logs->count(),
            'critical' => $logs->whereIn('level', ['critical', 'alert', 'emergency'])->count(),
            'error'    => $logs->where('level', 'error')->count(),
            'warning'  => $logs->where('level', 'warning')->count(),
        ];

        $meta = [
            'generatedAt' => Carbon::now()->format('M d, Y h:i A'),
            'generatedBy' => session('username') ?? 'Superadmin',
            'dateRange'   => $request->input('date_range_label', 'All Dates'),
            'filterLabel' => $request->input('filter_label', 'All Severity Levels'),
        ];

        $pdf = Pdf::loadView('exports.error_logs_pdf', compact('logs', 'summary', 'meta'))
            ->setPaper('a4', 'landscape');

        $filename = 'Cafe-Ordering-System-Error-Logs-' . Carbon::now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }
}