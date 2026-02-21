<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\User;
use App\Models\ActivityLog;
use App\Services\AICompatibilityService;
use App\Support\SkillMatchEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class SwapRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'skill_offered' => 'required|string|max:255',
            'skill_requested' => 'required|string|max:255',
        ]);

        if ((int) $request->to_user_id === (int) Auth::id()) {
            return back()->with('error', 'You cannot send a request to yourself.');
        }

        $alreadyPending = SwapRequest::query()
            ->where('from_user_id', Auth::id())
            ->where('to_user_id', $request->to_user_id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('error', 'You already have a pending request with this user.');
        }

        $cooldownUntil = now()->subHours(12);

        $recentRequest = SwapRequest::query()
            ->where('from_user_id', Auth::id())
            ->where('to_user_id', $request->to_user_id)
            ->where('created_at', '>=', $cooldownUntil)
            ->exists();

        if ($recentRequest) {
            return back()->with('error', 'Please wait before sending another request to this user.');
        }

        $dailyCount = SwapRequest::query()
            ->where('from_user_id', Auth::id())
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyCount >= 15) {
            return back()->with('error', 'Daily request limit reached. Try again tomorrow.');
        }

        $rateKey = 'swap-request:' . Auth::id();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            if (ActivityLog::enabled()) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'type' => 'rate_limited',
                    'meta' => [
                        'scope' => 'swap_request',
                        'to_user_id' => (int) $request->to_user_id,
                    ],
                ]);
            }
            return back()->with('error', 'Slow down. Please wait before sending more requests.');
        }

        $sender = Auth::user();
        $receiver = User::findOrFail($request->to_user_id);

        $qualityScore = SkillMatchEngine::requestQualityScore(
            $sender,
            $receiver,
            (string) $request->skill_offered,
            (string) $request->skill_requested
        );

        if ($qualityScore < 40) {
            return back()->with('error', 'Request quality is low. Be more specific about skills for better matching.');
        }

        $swap = SwapRequest::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $request->to_user_id,
            'skill_offered' => $request->skill_offered,
            'skill_requested' => $request->skill_requested,
            'quality_score' => $qualityScore,
        ]);

        RateLimiter::hit($rateKey, 600);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'type' => 'swap_request_sent',
                'meta' => [
                    'swap_request_id' => $swap->id,
                    'to_user_id' => (int) $request->to_user_id,
                    'quality_score' => $qualityScore,
                ],
            ]);
        }

        return redirect()->route('swap.confirm', $swap->id)
            ->with('success', 'Swap request sent.');
    }

    public function confirm(SwapRequest $swap)
    {
        if ((int) $swap->from_user_id !== (int) Auth::id()) {
            abort(403);
        }

        $swap->loadMissing('receiver');
        $compatibility = app(AICompatibilityService::class)->score(Auth::user(), $swap->receiver);
        $signals = SkillMatchEngine::userSignals([$swap->receiver->id]);
        $avgMinutes = (float) ($signals[$swap->receiver->id]['avg_response_minutes'] ?? 0);
        $etaLabel = $this->responseEtaLabel($avgMinutes);

        return view('swap.confirmation', compact('swap', 'compatibility', 'etaLabel'));
    }

    public function dashboard()
    {
        $received = SwapRequest::with('sender:id,name')
            ->where('to_user_id', Auth::id())
            ->latest()
            ->get();

        $sent = SwapRequest::with('receiver:id,name')
            ->where('from_user_id', Auth::id())
            ->latest()
            ->get();

        $counterpartyIds = $received->pluck('from_user_id')
            ->merge($sent->pluck('to_user_id'))
            ->unique()
            ->values()
            ->all();

        $counterparties = $counterpartyIds
            ? User::query()->whereIn('id', $counterpartyIds)->get()
            : collect();

        $compatibilityScores = app(AICompatibilityService::class)
            ->bulkScores(Auth::user(), $counterparties);
        $responseSignals = SkillMatchEngine::userSignals($counterpartyIds);
        $selfSignals = SkillMatchEngine::userSignals([Auth::id()]);
        $selfAvg = (float) ($selfSignals[Auth::id()]['avg_response_minutes'] ?? 0);
        $selfEtaLabel = $this->responseEtaLabel($selfAvg);
        $responseEtaLabels = [];
        foreach ($responseSignals as $userId => $signal) {
            $responseEtaLabels[$userId] = $this->responseEtaLabel((float) ($signal['avg_response_minutes'] ?? 0));
        }

        $ratedUserIds = $received
            ->where('status', 'accepted')
            ->pluck('from_user_id')
            ->unique()
            ->values()
            ->all();

        $ratingsGiven = \App\Models\Rating::query()
            ->where('from_user_id', Auth::id())
            ->whereIn('to_user_id', $ratedUserIds)
            ->latest()
            ->get()
            ->keyBy('to_user_id');

        return view('swap.dashboard', compact(
            'received',
            'sent',
            'ratingsGiven',
            'compatibilityScores',
            'responseSignals',
            'selfEtaLabel',
            'responseEtaLabels'
        ));
    }

    private function responseEtaLabel(float $avgMinutes): string
    {
        if ($avgMinutes <= 0) {
            return 'Within 24 hours';
        }
        if ($avgMinutes <= 60) {
            return 'Within 1 hour';
        }
        if ($avgMinutes <= 180) {
            return 'Within 3 hours';
        }
        if ($avgMinutes <= 360) {
            return 'Within 6 hours';
        }
        if ($avgMinutes <= 720) {
            return 'Within 12 hours';
        }
        if ($avgMinutes <= 1440) {
            return 'Within 24 hours';
        }

        return 'Within 48 hours';
    }

    public function updateStatus($id, $status)
    {
        if (!in_array($status, ['accepted', 'rejected'], true)) {
            abort(404);
        }

        $request = SwapRequest::findOrFail($id);

        if ($request->to_user_id != Auth::id()) {
            abort(403);
        }

        $request->update([
            'status' => $status,
        ]);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'type' => 'swap_request_' . $status,
                'meta' => [
                    'swap_request_id' => $request->id,
                    'from_user_id' => (int) $request->from_user_id,
                ],
            ]);
        }

        return back()->with('success', 'Request status updated.');
    }
}
