<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        if ((int) $request->to_user_id === (int) auth()->id()) {
            return back()->with('error', 'You cannot rate yourself.');
        }

        $rating = Rating::updateOrCreate(
            [
                'from_user_id' => auth()->id(),
                'to_user_id' => $request->to_user_id,
            ],
            [
                'rating' => $request->rating,
                'review' => $request->review,
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
                ],
            ]);
        }

        $avgRating = (float) Rating::where('to_user_id', $request->to_user_id)->avg('rating');

        User::whereKey($request->to_user_id)->update([
            'rating' => round($avgRating, 1),
        ]);

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

        $avgRating = (float) Rating::where('to_user_id', $rating->to_user_id)->avg('rating');

        User::whereKey($rating->to_user_id)->update([
            'rating' => round($avgRating, 1),
        ]);

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
}
