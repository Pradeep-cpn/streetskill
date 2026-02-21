<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Rating;
use App\Models\SwapRequest;
use App\Models\User;
use App\Support\SkillMatchEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $suggestedUsers = collect();
        $feedUsers = collect();
        $profileCompletion = null;
        $profileChecklist = [];
        $momentum = null;
        $learningPath = [];
        $learningTracks = [];
        $nextBestAction = null;
        $weeklyPlan = [];
        $trendPicks = $this->buildTrendPicks();
        $communityEvents = $this->buildCommunityEvents();
        $reliabilitySignals = $this->buildReliabilitySignals();
        $stats = $this->buildStats();
        $microChallenges = [];
        $streak = [
            'days' => 0,
            'boost' => 0,
        ];

        if (Auth::check()) {
            $currentUser = Auth::user();
            $myOfferedSkills = SkillMatchEngine::parseSkills($currentUser->skills_offered);
            $myWantedSkills = SkillMatchEngine::parseSkills($currentUser->skills_wanted);
            $myAvailability = SkillMatchEngine::parseAvailability($currentUser->availability_slots);

            $profileChecklist = [
                ['label' => 'City added', 'done' => !empty($currentUser->city)],
                ['label' => 'Bio added', 'done' => !empty($currentUser->bio)],
                ['label' => 'Teaching skills added', 'done' => !empty($currentUser->skills_offered)],
                ['label' => 'Learning goals added', 'done' => !empty($currentUser->skills_wanted)],
                ['label' => 'Availability slots selected', 'done' => !empty($currentUser->availability_slots)],
            ];

            $doneCount = collect($profileChecklist)->where('done', true)->count();
            $profileCompletion = (int) round(($doneCount / max(count($profileChecklist), 1)) * 100);

            $acceptedThisMonth = SwapRequest::query()
                ->where('status', 'accepted')
                ->where(function ($q) use ($currentUser) {
                    $q->where('from_user_id', $currentUser->id)
                        ->orWhere('to_user_id', $currentUser->id);
                })
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();

            $ratingsGivenThisMonth = Rating::query()
                ->where('from_user_id', $currentUser->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            $requestsSentTotal = SwapRequest::query()
                ->where('from_user_id', $currentUser->id)
                ->count();

            $ratingsGivenTotal = Rating::query()
                ->where('from_user_id', $currentUser->id)
                ->count();

            $momentum = [
                'accepted_30d' => $acceptedThisMonth,
                'ratings_given_30d' => $ratingsGivenThisMonth,
            ];

            $candidates = User::query()
                ->where('id', '!=', $currentUser->id)
                ->where(function ($query) {
                    $query->whereNotNull('skills_offered')
                        ->orWhereNotNull('skills_wanted');
                })
                ->limit(80)
                ->get();

            $candidateIds = $candidates->pluck('id')->all();
            $fastResponderIds = SkillMatchEngine::fastResponderUserIds($candidateIds);
            $signals = SkillMatchEngine::userSignals($candidateIds);

            $ranked = $candidates
                ->map(function (User $candidate) use ($currentUser, $fastResponderIds, $signals) {
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

                    return $candidate;
                })
                ->sortByDesc('smart_score')
                ->values();

            $suggestedUsers = $ranked->filter(fn (User $candidate) => $candidate->match_score > 0)->take(3)->values();
            $feedUsers = $ranked->take(6)->values();

            $learningTracks = collect($myWantedSkills)
                ->take(3)
                ->map(function (string $skill) use ($ranked) {
                    $mentors = $ranked
                        ->filter(fn (User $candidate) => in_array($skill, $candidate->match_teaches_you ?? [], true))
                        ->take(2)
                        ->values();

                    return [
                        'skill' => mb_convert_case($skill, MB_CASE_TITLE, 'UTF-8'),
                        'mentors_count' => $ranked
                            ->filter(fn (User $candidate) => in_array($skill, $candidate->match_teaches_you ?? [], true))
                            ->count(),
                        'mentor_names' => $mentors->pluck('name')->all(),
                    ];
                })
                ->values()
                ->all();

            $learningPath = [
                [
                    'title' => 'Set your learning focus',
                    'description' => 'Add 1-3 clear skills you want to learn next.',
                    'done' => !empty($myWantedSkills),
                    'cta_label' => 'Update profile',
                    'cta_route' => route('profile.edit'),
                ],
                [
                    'title' => 'Define your teaching value',
                    'description' => 'List what you can teach so swaps stay balanced.',
                    'done' => !empty($myOfferedSkills),
                    'cta_label' => 'Add teachable skills',
                    'cta_route' => route('profile.edit'),
                ],
                [
                    'title' => 'Match your weekly slots',
                    'description' => 'Pick availability to surface people with overlap.',
                    'done' => !empty($myAvailability),
                    'cta_label' => 'Choose slots',
                    'cta_route' => route('profile.edit'),
                ],
                [
                    'title' => 'Send your first swap request',
                    'description' => 'Use Marketplace and send one high-quality request.',
                    'done' => $requestsSentTotal > 0,
                    'cta_label' => 'Open marketplace',
                    'cta_route' => route('marketplace'),
                ],
                [
                    'title' => 'Close the trust loop',
                    'description' => 'After a session, leave a rating to build credibility.',
                    'done' => $ratingsGivenTotal > 0,
                    'cta_label' => 'Go to requests',
                    'cta_route' => route('requests.dashboard'),
                ],
            ];

            $nextBestAction = collect($learningPath)->firstWhere('done', false)
                ?? [
                    'title' => 'Keep your streak active',
                    'description' => 'You are fully set up. Send a new request this week to stay visible.',
                    'cta_label' => 'Find new matches',
                    'cta_route' => route('marketplace'),
                ];

            $weeklyPlan = collect($myAvailability)
                ->take(3)
                ->map(function (string $slot) use ($learningTracks) {
                    $focus = $learningTracks[0]['skill'] ?? 'a target skill';

                    return "Use {$slot} for a 45-min session focused on {$focus}.";
                })
                ->all();

            if (empty($weeklyPlan)) {
                $weeklyPlan[] = 'Pick at least two weekly slots to unlock schedule-based matching.';
                $weeklyPlan[] = 'Start with one focused 30-45 minute session this week.';
            }

            $microChallenges = $this->buildMicroChallenges($currentUser->id, $profileCompletion);
            $streak = $this->buildStreak($currentUser->id);
        }

        return view('home', compact(
            'suggestedUsers',
            'feedUsers',
            'profileCompletion',
            'profileChecklist',
            'momentum',
            'learningPath',
            'learningTracks',
            'nextBestAction',
            'weeklyPlan',
            'trendPicks',
            'microChallenges',
            'communityEvents',
            'reliabilitySignals',
            'stats',
            'streak'
        ));
    }

    private function buildStats(): array
    {
        return Cache::remember('home:stats', now()->addMinutes(20), function () {
            if (!Schema::hasTable('swap_requests') || !Schema::hasTable('users')) {
                return [
                    'active_swaps' => '0',
                    'trust_avg' => '4.8/5',
                    'fast_match' => '12m',
                ];
            }

            $activeSwaps = SwapRequest::query()
                ->where('status', 'accepted')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();

            $trustAverage = (float) User::query()
                ->where('rating', '>', 0)
                ->avg('rating');

            $fastestMatchMinutes = SwapRequest::query()
                ->where('status', 'accepted')
                ->get(['created_at', 'updated_at'])
                ->map(fn (SwapRequest $request) => $request->created_at?->diffInMinutes($request->updated_at) ?? null)
                ->filter()
                ->min();

            $fastestMatchLabel = $fastestMatchMinutes
                ? ($fastestMatchMinutes < 60 ? $fastestMatchMinutes . 'm' : (int) ceil($fastestMatchMinutes / 60) . 'h')
                : '12m';

            return [
                'active_swaps' => $activeSwaps > 0 ? $activeSwaps : '0',
                'trust_avg' => $trustAverage > 0 ? number_format($trustAverage, 1) . '/5' : '4.8/5',
                'fast_match' => $fastestMatchLabel,
            ];
        });
    }

    private function buildTrendPicks(): array
    {
        return Cache::remember('home:trends', now()->addMinutes(30), function () {
            if (!Schema::hasTable('swap_requests') && !Schema::hasTable('users')) {
                return [];
            }

            $skills = SwapRequest::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->get(['skill_offered', 'skill_requested'])
                ->flatMap(function (SwapRequest $request) {
                    return [
                        $request->skill_offered,
                        $request->skill_requested,
                    ];
                })
                ->filter()
                ->map(fn ($skill) => mb_strtolower(trim((string) $skill)))
                ->filter()
                ->values();

            if ($skills->isEmpty() && Schema::hasTable('users')) {
                $skills = User::query()
                    ->whereNotNull('skills_offered')
                    ->orWhereNotNull('skills_wanted')
                    ->limit(120)
                    ->get(['skills_offered', 'skills_wanted'])
                    ->flatMap(function (User $user) {
                        return array_merge(
                            SkillMatchEngine::parseSkills($user->skills_offered),
                            SkillMatchEngine::parseSkills($user->skills_wanted)
                        );
                    })
                    ->filter()
                    ->values();
            }

            return $skills
                ->countBy()
                ->sortDesc()
                ->take(4)
                ->map(function (int $count, string $skill) {
                    return [
                        'title' => mb_convert_case($skill, MB_CASE_TITLE, 'UTF-8'),
                        'meta' => "{$count} swaps last 30d",
                        'tag' => 'Trending',
                    ];
                })
                ->values()
                ->all();
        });
    }

    private function buildCommunityEvents(): array
    {
        return Cache::remember('home:community_events', now()->addMinutes(60), function () {
            if (!Schema::hasTable('users')) {
                return [];
            }

            return User::query()
                ->selectRaw('city, COUNT(*) as count')
                ->whereNotNull('city')
                ->groupBy('city')
                ->orderByDesc('count')
                ->limit(3)
                ->get()
                ->map(function ($row) {
                    return [
                        'title' => 'City Spotlight: ' . $row->city,
                        'meta' => $row->count . ' active members',
                        'city' => $row->city,
                    ];
                })
                ->values()
                ->all();
        });
    }

    private function buildReliabilitySignals(): array
    {
        return Cache::remember('home:reliability', now()->addMinutes(30), function () {
            if (!Schema::hasTable('swap_requests') || !Schema::hasTable('ratings') || !Schema::hasTable('reports')) {
                return [
                    ['label' => 'Verified swaps', 'value' => '0 completed'],
                    ['label' => 'Fast responses', 'value' => '0 within 4h'],
                    ['label' => 'Ratings lock', 'value' => '0 verified ratings'],
                    ['label' => 'Report safety', 'value' => '0 open reviews'],
                ];
            }

            $acceptedSwaps = SwapRequest::query()
                ->where('status', 'accepted')
                ->count();

            $openReports = \App\Models\Report::query()
                ->where('status', 'open')
                ->count();

            $ratings = Rating::query()->count();

            $fastResponses = SwapRequest::query()
                ->whereIn('status', ['accepted', 'rejected'])
                ->get(['created_at', 'updated_at'])
                ->filter(fn (SwapRequest $request) => $request->created_at && $request->updated_at)
                ->filter(function (SwapRequest $request) {
                    return $request->created_at->diffInMinutes($request->updated_at) <= 240;
                })
                ->count();

            return [
                ['label' => 'Verified swaps', 'value' => $acceptedSwaps . ' completed'],
                ['label' => 'Fast responses', 'value' => $fastResponses . ' within 4h'],
                ['label' => 'Ratings lock', 'value' => $ratings . ' verified ratings'],
                ['label' => 'Report safety', 'value' => $openReports . ' open reviews'],
            ];
        });
    }

    private function buildMicroChallenges(int $userId, int $profileCompletion): array
    {
        if (!ActivityLog::enabled()) {
            return [];
        }
        if (!Schema::hasTable('swap_requests') || !Schema::hasTable('ratings')) {
            return [];
        }

        $recentSwap = SwapRequest::query()
            ->where('from_user_id', $userId)
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        $recentRating = Rating::query()
            ->where('from_user_id', $userId)
            ->where('created_at', '>=', now()->subDays(14))
            ->exists();

        $recentMessage = ActivityLog::query()
            ->where('user_id', $userId)
            ->where('type', 'message_sent')
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        $challenges = [];

        if (!$recentSwap) {
            $challenges[] = ['title' => 'Send 1 quality swap request', 'meta' => 'Boost your visibility this week', 'cta' => 'Send a swap'];
        }

        if (!$recentRating) {
            $challenges[] = ['title' => 'Give 2 ratings', 'meta' => 'Strengthen your trust score', 'cta' => 'Review now'];
        }

        if ($profileCompletion < 100) {
            $challenges[] = ['title' => 'Complete your profile', 'meta' => 'Unlock higher match quality', 'cta' => 'Update profile'];
        }

        if (!$recentMessage) {
            $challenges[] = ['title' => 'Start a new chat', 'meta' => 'Improve response rate', 'cta' => 'Message now'];
        }

        return collect($challenges)->take(3)->values()->all();
    }

    private function buildStreak(int $userId): array
    {
        if (!ActivityLog::enabled()) {
            return ['days' => 0, 'boost' => 0];
        }

        $dates = ActivityLog::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(60))
            ->get(['created_at'])
            ->map(function (ActivityLog $log) {
                return $log->created_at?->toDateString();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($dates)) {
            return ['days' => 0, 'boost' => 0];
        }

        $dateSet = array_flip($dates);
        $streak = 0;
        $cursor = Carbon::today();

        while (isset($dateSet[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return [
            'days' => $streak,
            'boost' => min(25, $streak * 2),
        ];
    }
}
