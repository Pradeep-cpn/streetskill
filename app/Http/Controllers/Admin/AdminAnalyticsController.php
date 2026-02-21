<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Rating;
use App\Models\Report;
use App\Models\SwapRequest;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 14);
        if (!in_array($days, [7, 14, 30], true)) {
            $days = 14;
        }

        $startDate = now()->startOfDay()->subDays($days - 1);

        $totalUsers = User::count();
        $newUsersPeriod = User::where('created_at', '>=', $startDate)->count();
        $totalSwapRequests = SwapRequest::count();
        $acceptedCount = SwapRequest::where('status', 'accepted')->count();
        $acceptanceRate = $totalSwapRequests > 0 ? round(($acceptedCount / $totalSwapRequests) * 100, 1) : 0;

        $openReports = Report::where('status', 'open')->count();
        $reportsPeriod = Report::where('created_at', '>=', $startDate)->count();

        $messagesPeriod = Message::where('created_at', '>=', $startDate)->count();
        $ratingsPeriod = Rating::where('created_at', '>=', $startDate)->count();
        $avgRating = round((float) Rating::avg('rating'), 2);

        $avgFirstResponseMinutes = $this->avgFirstResponseMinutes();

        $dailySeries = collect(range(0, $days - 1))->map(function (int $offset) use ($startDate) {
            $date = (clone $startDate)->addDays($offset);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            return [
                'label' => $date->format('M d'),
                'users' => User::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'requests' => SwapRequest::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'accepted' => SwapRequest::where('status', 'accepted')->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'reports' => Report::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'messages' => Message::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ];
        });

        $peakMessages = max(1, (int) $dailySeries->max('messages'));

        $topSkills = $this->topSkills(8);

        $highRiskUsers = Report::query()
            ->select('reported_user_id', DB::raw('COUNT(*) as reports_count'))
            ->groupBy('reported_user_id')
            ->orderByDesc('reports_count')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->reported_user_id);
                return [
                    'user_id' => $row->reported_user_id,
                    'name' => $user?->name ?? 'Unknown',
                    'reports_count' => (int) $row->reports_count,
                ];
            });

        $slowResponders = SwapRequest::query()
            ->whereIn('status', ['accepted', 'rejected'])
            ->select('to_user_id', DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes'), DB::raw('COUNT(*) as handled_count'))
            ->groupBy('to_user_id')
            ->having('handled_count', '>=', 2)
            ->orderByDesc('avg_minutes')
            ->limit(8)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->to_user_id);
                return [
                    'name' => $user?->name ?? 'Unknown',
                    'avg_minutes' => round((float) $row->avg_minutes),
                    'handled_count' => (int) $row->handled_count,
                ];
            });

        $verificationRequests = User::query()
            ->whereNotNull('verification_requested_at')
            ->orderByDesc('verification_requested_at')
            ->limit(12)
            ->get(['id', 'name', 'email', 'verification_requested_at', 'verified_badge']);

        if (ActivityLog::enabled()) {
            $activityPulse = ActivityLog::query()
                ->where('created_at', '>=', $startDate)
                ->select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->orderByDesc('count')
                ->limit(8)
                ->get()
                ->map(fn ($row) => ['type' => $row->type, 'count' => (int) $row->count]);

            $rateLimitTriggers = ActivityLog::query()
                ->where('type', 'rate_limited')
                ->where('created_at', '>=', $startDate)
                ->select(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.scope')) as scope"), DB::raw('COUNT(*) as count'))
                ->groupBy('scope')
                ->orderByDesc('count')
                ->get()
                ->map(fn ($row) => ['scope' => $row->scope ?: 'unknown', 'count' => (int) $row->count]);
        } else {
            $activityPulse = collect();
            $rateLimitTriggers = collect();
        }

        return view('admin.analytics.index', compact(
            'days',
            'totalUsers',
            'newUsersPeriod',
            'totalSwapRequests',
            'acceptedCount',
            'acceptanceRate',
            'openReports',
            'reportsPeriod',
            'messagesPeriod',
            'ratingsPeriod',
            'avgRating',
            'avgFirstResponseMinutes',
            'dailySeries',
            'peakMessages',
            'topSkills',
            'highRiskUsers',
            'slowResponders',
            'activityPulse',
            'rateLimitTriggers',
            'verificationRequests'
        ));
    }

    private function topSkills(int $limit): array
    {
        $frequency = [];

        User::query()->select('skills_offered', 'skills_wanted')->get()->each(function ($user) use (&$frequency) {
            $skills = collect([$user->skills_offered, $user->skills_wanted])
                ->filter()
                ->flatMap(fn ($text) => preg_split('/[,\n]+/', mb_strtolower((string) $text)) ?: [])
                ->map(fn ($skill) => trim((string) $skill))
                ->filter();

            foreach ($skills as $skill) {
                $frequency[$skill] = ($frequency[$skill] ?? 0) + 1;
            }
        });

        arsort($frequency);

        return collect($frequency)
            ->take($limit)
            ->map(fn ($count, $skill) => ['skill' => $skill, 'count' => $count])
            ->values()
            ->all();
    }

    private function avgFirstResponseMinutes(): int
    {
        $rows = SwapRequest::query()
            ->whereIn('status', ['accepted', 'rejected'])
            ->selectRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) as diff_minutes')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        return (int) round($rows->avg('diff_minutes'));
    }
}
