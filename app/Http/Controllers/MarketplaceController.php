<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LocationTag;
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
        $category = trim((string) $request->query('category', ''));
        $availabilityStatus = trim((string) $request->query('availability', ''));
        $sort = trim((string) $request->query('sort', 'smart'));
        $ratingMin = max(0.0, (float) $request->query('rating_min', 0));
        $distanceKm = max(0, (int) $request->query('distance', 0));
        $priceMin = $request->query('price_min');
        $priceMax = $request->query('price_max');
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
            ->with('profile')
            ->where('id', '!=', $currentUser->id);

        if ($skill !== '') {
            $query->where(function ($q) use ($skill) {
                $q->where('skills_offered', 'like', '%' . $skill . '%')
                    ->orWhere('skills_wanted', 'like', '%' . $skill . '%');
            });
        }

        if ($category !== '') {
            $query->where(function ($q) use ($category) {
                $q->where('skills_offered', 'like', '%' . $category . '%')
                    ->orWhere('skills_wanted', 'like', '%' . $category . '%');
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

        $locationUserIds = array_unique(array_merge($candidateIds, [$currentUser->id]));
        $locationTags = LocationTag::query()
            ->whereIn('user_id', $locationUserIds)
            ->where('expires_at', '>=', now())
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($group) => $group->first());

        $myLocationTag = $locationTags->get($currentUser->id);
        $distanceError = null;
        if ($distanceKm > 0 && !$myLocationTag) {
            $distanceError = 'Add a location tag to enable distance filtering.';
        }

        $ranked = $candidates
            ->map(function (User $candidate) use (
                $currentUser,
                $fastResponderIds,
                $signals,
                $compatibilityScores,
                $responseEtaLabels,
                $locationTags,
                $myLocationTag
            ) {
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
                $candidateTag = $locationTags->get($candidate->id);
                if ($candidate->hide_tags_until && $candidate->hide_tags_until->isFuture()) {
                    $candidateTag = null;
                }
                $candidate->distance_km = $this->distanceFromUser($myLocationTag, $candidateTag);

                return $candidate;
            })
            ->filter(function (User $candidate) use ($category, $availabilityStatus, $ratingMin, $priceMin, $priceMax, $distanceKm, $distanceError) {
                $profile = $candidate->profile;
                if ($availabilityStatus !== '' && $availabilityStatus !== 'any') {
                    if (!$profile || $profile->availability_status !== $availabilityStatus) {
                        return false;
                    }
                }

                if ($ratingMin > 0 && (float) $candidate->rating < $ratingMin) {
                    return false;
                }

                if ($category !== '') {
                    $categoryLower = mb_strtolower($category, 'UTF-8');
                    $skillsOffered = mb_strtolower((string) $candidate->skills_offered, 'UTF-8');
                    $skillsWanted = mb_strtolower((string) $candidate->skills_wanted, 'UTF-8');
                    $tagMatch = collect($profile?->skill_tags ?? [])
                        ->map(fn ($tag) => mb_strtolower((string) $tag, 'UTF-8'))
                        ->contains($categoryLower);

                    if (!str_contains($skillsOffered, $categoryLower) && !str_contains($skillsWanted, $categoryLower) && !$tagMatch) {
                        return false;
                    }
                }

                if ($priceMin !== null || $priceMax !== null) {
                    if (!$profile || (!$profile->price_min && !$profile->price_max)) {
                        return false;
                    }
                    $rangeMin = $profile->price_min ?? $profile->price_max;
                    $rangeMax = $profile->price_max ?? $profile->price_min;
                    $minFilter = $priceMin !== null && $priceMin !== '' ? (int) $priceMin : null;
                    $maxFilter = $priceMax !== null && $priceMax !== '' ? (int) $priceMax : null;

                    if ($minFilter !== null && $maxFilter !== null) {
                        if ($rangeMin > $maxFilter || $rangeMax < $minFilter) {
                            return false;
                        }
                    } elseif ($minFilter !== null) {
                        if ($rangeMax < $minFilter) {
                            return false;
                        }
                    } elseif ($maxFilter !== null) {
                        if ($rangeMin > $maxFilter) {
                            return false;
                        }
                    }
                }

                if ($distanceKm > 0 && !$distanceError) {
                    if ($candidate->distance_km === null || $candidate->distance_km > $distanceKm) {
                        return false;
                    }
                }

                return true;
            })
            ->sortByDesc('smart_score')
            ->values();

        if ($distanceError) {
            $ranked = collect();
        } elseif ($sort === 'nearest') {
            $ranked = $ranked->sortBy(function (User $candidate) {
                return $candidate->distance_km ?? PHP_INT_MAX;
            })->values();
        } elseif ($sort === 'rated') {
            $ranked = $ranked->sortByDesc(function (User $candidate) {
                return (float) $candidate->rating;
            })->values();
        } elseif ($sort === 'active') {
            $ranked = $ranked->sortByDesc(function (User $candidate) {
                return $candidate->last_active_at?->timestamp ?? 0;
            })->values();
        }

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
            'category',
            'availabilityStatus',
            'ratingMin',
            'distanceKm',
            'priceMin',
            'priceMax',
            'sort',
            'myOfferedSkills',
            'myWantedSkills',
            'marketStats',
            'trendingSkills',
            'prefillOffer',
            'prefillRequest',
            'distanceError'
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

    private function distanceFromUser($myTag, $theirTag): ?int
    {
        if (!$myTag || !$theirTag) {
            return null;
        }

        $lat1 = deg2rad((float) $myTag->lat);
        $lon1 = deg2rad((float) $myTag->lng);
        $lat2 = deg2rad((float) $theirTag->lat);
        $lon2 = deg2rad((float) $theirTag->lng);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = 6371 * $c;

        return (int) round($km);
    }
}
