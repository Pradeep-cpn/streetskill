<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SwapRequest;
use App\Services\AICompatibilityService;
use App\Support\SkillMatchEngine;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $skill = trim((string) $request->query('skill', ''));
        $city = trim((string) $request->query('city', ''));
        $q = trim((string) $request->query('q', ''));
        $prefillOffer = trim((string) $request->query('offer', ''));
        $prefillRequest = trim((string) $request->query('request', ''));

        if ($q !== '' && $skill === '' && $city === '') {
            if (str_contains($q, ',')) {
                [$skillGuess, $cityGuess] = array_pad(explode(',', $q, 2), 2, '');
                $skill = trim($skillGuess);
                $city = trim($cityGuess);
            } elseif (stripos($q, ' in ') !== false) {
                [$skillGuess, $cityGuess] = array_pad(preg_split('/\s+in\s+/i', $q, 2) ?: [], 2, '');
                $skill = trim((string) $skillGuess);
                $city = trim((string) $cityGuess);
            } else {
                $skill = $q;
            }
        }
        $currentUser = Auth::user();

        $myOfferedSkills = SkillMatchEngine::parseSkills($currentUser->skills_offered);
        $myWantedSkills = SkillMatchEngine::parseSkills($currentUser->skills_wanted);

        $query = User::query()
            ->where('id', '!=', $currentUser->id);

        if ($skill !== '') {
            $query->where(function ($q) use ($skill) {
                $q->where('skills_offered', 'like', '%' . $skill . '%')
                    ->orWhere('skills_wanted', 'like', '%' . $skill . '%');
            });
        }

        if ($city !== '') {
            $query->where('city', 'like', '%' . $city . '%');
        }

        $candidates = $query->get();
        $candidateIds = $candidates->pluck('id')->all();
        $fastResponderIds = SkillMatchEngine::fastResponderUserIds($candidateIds);
        $signals = SkillMatchEngine::userSignals($candidateIds);
        $responseEtaLabels = [];
        foreach ($signals as $userId => $signal) {
            $responseEtaLabels[$userId] = $this->responseEtaLabel((float) ($signal['avg_response_minutes'] ?? 0));
        }

        $compatibilityScores = app(AICompatibilityService::class)->bulkScores($currentUser, $candidates);

        $ranked = $candidates
            ->map(function (User $candidate) use ($currentUser, $fastResponderIds, $signals, $compatibilityScores, $responseEtaLabels) {
                if (empty($candidate->slug)) {
                    $candidate->slug = User::generateUniqueSlug($candidate->name);
                    $candidate->save();
                }
                $analysis = SkillMatchEngine::analyze(
                    $currentUser,
                    $candidate,
                    $fastResponderIds,
                    $signals[$candidate->id] ?? []
                );

                $candidate->match_score = $analysis['match_score'];
                $candidate->smart_score = $analysis['smart_score'];
                $candidate->trust_score = $analysis['trust_score'];
                $candidate->match_teaches_you = $analysis['teaches_you'];
                $candidate->match_learns_from_you = $analysis['learns_from_you'];
                $candidate->availability_overlap = $analysis['availability_overlap'];
                $candidate->swap_hint_offered = $analysis['swap_hint_offered'];
                $candidate->swap_hint_requested = $analysis['swap_hint_requested'];
                $candidate->badges = $analysis['badges'];
                $candidate->compatibility_score = $compatibilityScores[$candidate->id]['score'] ?? null;
                $candidate->response_eta = $responseEtaLabels[$candidate->id] ?? null;

                return $candidate;
            })
            ->sortByDesc('smart_score')
            ->values();

        $perPage = 9;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $users = new LengthAwarePaginator(
            $ranked->forPage($currentPage, $perPage)->values(),
            $ranked->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $marketStats = [
            'active_swaps_today' => SwapRequest::query()
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            'trusted_creators' => User::query()
                ->where('rating', '>=', 4.5)
                ->count(),
            'fast_responders' => count($fastResponderIds),
        ];

        $trendingSkills = SwapRequest::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['skill_offered', 'skill_requested'])
            ->flatMap(fn (SwapRequest $swap) => [$swap->skill_offered, $swap->skill_requested])
            ->filter()
            ->map(fn ($skill) => mb_convert_case(mb_strtolower(trim((string) $skill)), MB_CASE_TITLE, 'UTF-8'))
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->keys()
            ->values()
            ->all();

        return view('marketplace.index', compact(
            'users',
            'skill',
            'city',
            'myOfferedSkills',
            'myWantedSkills',
            'marketStats',
            'trendingSkills',
            'prefillOffer',
            'prefillRequest'
        ));
    }

    private function responseEtaLabel(float $avgMinutes): string
    {
        if ($avgMinutes <= 0) {
            return '24h';
        }
        if ($avgMinutes <= 60) {
            return '1h';
        }
        if ($avgMinutes <= 180) {
            return '3h';
        }
        if ($avgMinutes <= 360) {
            return '6h';
        }
        if ($avgMinutes <= 720) {
            return '12h';
        }
        if ($avgMinutes <= 1440) {
            return '24h';
        }

        return '48h';
    }
}
