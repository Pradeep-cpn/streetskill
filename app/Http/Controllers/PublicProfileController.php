<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\LocationTag;
use App\Models\SkillEndorsement;
use App\Models\Rating;
use App\Models\UserBlock;
use App\Models\User;
use App\Services\AICompatibilityService;
use App\Support\ProfileMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicProfileController extends Controller
{
    public function show(string $slug)
    {
        $user = User::query()->where('slug', $slug)->firstOrFail();
        $currentUser = Auth::user();
        $profile = $user->profile;

        $completedSwaps = SwapRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->count();

        $tags = collect();
        if (!$user->hide_tags_until || $user->hide_tags_until->isPast()) {
            $tags = LocationTag::query()
                ->where('user_id', $user->id)
                ->where('expires_at', '>=', now())
                ->latest()
                ->get();
        }

        $endorsements = SkillEndorsement::query()
            ->where('endorsee_id', $user->id)
            ->get()
            ->groupBy('skill')
            ->map(fn ($items) => $items->count())
            ->sortDesc();

        $compatibility = null;
        $canChat = false;
        $isBlocked = false;
        if ($currentUser && $currentUser->id !== $user->id) {
            $isBlocked = UserBlock::query()
                ->where(function ($query) use ($currentUser, $user) {
                    $query->where('blocker_user_id', $currentUser->id)
                        ->where('blocked_user_id', $user->id);
                })
                ->orWhere(function ($query) use ($currentUser, $user) {
                    $query->where('blocker_user_id', $user->id)
                        ->where('blocked_user_id', $currentUser->id);
                })
                ->exists();
            $compatibility = app(AICompatibilityService::class)->score($currentUser, $user);
            $canChat = SwapRequest::query()
                ->where('status', 'accepted')
                ->where(function ($query) use ($currentUser, $user) {
                    $query->where(function ($q) use ($currentUser, $user) {
                        $q->where('from_user_id', $currentUser->id)
                            ->where('to_user_id', $user->id);
                    })->orWhere(function ($q) use ($currentUser, $user) {
                        $q->where('from_user_id', $user->id)
                            ->where('to_user_id', $currentUser->id);
                    });
                })
                ->exists();
            if ($isBlocked) {
                $canChat = false;
            }
        }

        $ratingQuery = Rating::query()
            ->where('to_user_id', $user->id)
            ->where('verified', true);

        $verifiedCount = (int) $ratingQuery->count();

        $ratingCounts = $ratingQuery
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->all();

        if ($verifiedCount === 0) {
            $ratingCounts = Rating::query()
                ->where('to_user_id', $user->id)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->all();
        }

        $ratingBreakdown = collect(range(5, 1))
            ->map(function (int $star) use ($ratingCounts) {
                return [
                    'star' => $star,
                    'count' => (int) ($ratingCounts[$star] ?? 0),
                ];
            })
            ->all();

        $totalRatings = array_sum($ratingCounts);

        $skillRatings = Rating::query()
            ->where('to_user_id', $user->id)
            ->whereNotNull('skill')
            ->where('verified', true)
            ->selectRaw('skill, SUM(rating * weight) / NULLIF(SUM(weight), 0) as avg_rating, COUNT(*) as count')
            ->groupBy('skill')
            ->orderByDesc('avg_rating')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        $metrics = ProfileMetrics::completion($user, $profile);

        return view('public.profile', compact(
            'user',
            'profile',
            'completedSwaps',
            'tags',
            'endorsements',
            'compatibility',
            'canChat',
            'ratingBreakdown',
            'totalRatings',
            'isBlocked',
            'metrics',
            'skillRatings'
        ));
    }
}
