<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\ChallengeVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Challenge::query()
            ->orderByDesc('week_start')
            ->get();

        $submissions = ChallengeSubmission::query()
            ->with('user:id,name,slug')
            ->latest()
            ->limit(20)
            ->get();

        return view('challenges.index', compact('challenges', 'submissions'));
    }

    public function storeSubmission(Request $request, Challenge $challenge)
    {
        $request->validate([
            'proof_url' => 'required|url|max:255',
            'note' => 'nullable|string|max:255',
        ]);

        ChallengeSubmission::updateOrCreate(
            [
                'challenge_id' => $challenge->id,
                'user_id' => Auth::id(),
            ],
            [
                'proof_url' => $request->proof_url,
                'note' => $request->note,
            ]
        );

        return back()->with('success', 'Submission saved.');
    }

    public function vote(ChallengeSubmission $submission)
    {
        ChallengeVote::firstOrCreate([
            'submission_id' => $submission->id,
            'user_id' => Auth::id(),
        ]);

        $submission->update([
            'votes_count' => ChallengeVote::where('submission_id', $submission->id)->count(),
        ]);

        return back()->with('success', 'Vote added.');
    }
}
