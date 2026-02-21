<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();
        $type = trim((string) $request->query('type', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        if (!ActivityLog::enabled()) {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="activity_logs.csv"',
            ];

            return response()->stream(function () {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['timestamp', 'type', 'meta']);
                fclose($output);
            }, 200, $headers);
        }

        $query = ActivityLog::query()
            ->where('user_id', $user->id);

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity_logs.csv"',
        ];

        $callback = function () use ($logs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['timestamp', 'type', 'meta']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at?->toDateTimeString(),
                    $log->type,
                    json_encode($log->meta ?? []),
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
