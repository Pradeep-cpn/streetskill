<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\LocationTag;
use App\Models\SkillEndorsement;
use App\Models\User;
use App\Services\AICompatibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicProfileController extends Controller
{
    public function show(string $slug)
    {
        $user = User::query()->where('slug', $slug)->firstOrFail();
        $currentUser = Auth::user();

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
        if ($currentUser && $currentUser->id !== $user->id) {
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
        }

        return view('public.profile', compact('user', 'completedSwaps', 'tags', 'endorsements', 'compatibility', 'canChat'));
    }
}
