<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\SwapRequest;
use App\Models\User;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'swap_request_id' => 'required|exists:swap_requests,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        if ((int) $request->to_user_id === (int) auth()->id()) {
            return back()->with('error', 'You cannot rate yourself.');
        }

        $swap = SwapRequest::findOrFail($request->swap_request_id);
        if ($swap->status !== 'accepted') {
            return back()->with('error', 'Only accepted swaps can be rated.');
        }

        $isParticipant = in_array(auth()->id(), [$swap->from_user_id, $swap->to_user_id], true)
            && in_array((int) $request->to_user_id, [$swap->from_user_id, $swap->to_user_id], true);
        if (!$isParticipant) {
            abort(403);
        }

        $expectedSkill = $this->expectedSkillForSwap($swap, (int) $request->to_user_id);
        if ($expectedSkill === '') {
            return back()->with('error', 'Invalid rating skill for this swap.');
        }

        $weight = $this->ratingWeight(auth()->id());

        $rating = Rating::updateOrCreate(
            [
                'from_user_id' => auth()->id(),
                'to_user_id' => $request->to_user_id,
                'swap_request_id' => $swap->id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
                'skill' => $expectedSkill,
                'verified' => true,
                'weight' => $weight,
            ]
        );

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'rating_given',
                'meta' => [
                    'rating_id' => $rating->id,
                    'to_user_id' => (int) $request->to_user_id,
                    'rating' => (int) $request->rating,
                    'swap_request_id' => $swap->id,
                ],
            ]);
        }

        $this->updateUserRating((int) $request->to_user_id);

        if (Schema::hasTable('notifications')) {
            Notification::create([
                'user_id' => (int) $request->to_user_id,
                'type' => 'rating_received',
                'data' => [
                    'from_user_id' => auth()->id(),
                    'from_name' => auth()->user()?->name,
                    'rating' => (int) $request->rating,
                    'skill' => $expectedSkill,
                ],
            ]);
        }

        return back()->with('success', 'Thanks for your feedback.');
    }

    public function update(Request $request, Rating $rating)
    {
        if ((int) $rating->from_user_id !== (int) auth()->id()) {
            abort(403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $rating->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        $this->updateUserRating((int) $rating->to_user_id);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'rating_updated',
                'meta' => [
                    'rating_id' => $rating->id,
                    'to_user_id' => (int) $rating->to_user_id,
                    'rating' => (int) $request->rating,
                ],
            ]);
        }

        return back()->with('success', 'Rating updated.');
    }

    private function updateUserRating(int $userId): void
    {
        $verifiedStats = Rating::query()
            ->where('to_user_id', $userId)
            ->where('verified', true)
            ->selectRaw('SUM(rating * weight) as weighted_sum, SUM(weight) as weight_sum')
            ->first();

        $weightSum = (float) ($verifiedStats?->weight_sum ?? 0);
        if ($weightSum > 0) {
            $avg = (float) $verifiedStats->weighted_sum / $weightSum;
        } else {
            $avg = (float) Rating::where('to_user_id', $userId)->avg('rating');
        }

        User::whereKey($userId)->update([
            'rating' => round($avg, 1),
        ]);
    }

    private function expectedSkillForSwap(SwapRequest $swap, int $toUserId): string
    {
        if ((int) $swap->from_user_id === $toUserId) {
            return (string) $swap->skill_offered;
        }

        if ((int) $swap->to_user_id === $toUserId) {
            return (string) $swap->skill_requested;
        }

        return '';
    }

    private function ratingWeight(int $raterId): float
    {
        $acceptedCount = SwapRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($raterId) {
                $query->where('from_user_id', $raterId)
                    ->orWhere('to_user_id', $raterId);
            })
            ->count();

        $weight = 1 + ($acceptedCount / 10);

        return (float) min(2.0, round($weight, 2));
    }
}
