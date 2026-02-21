<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\SkillEndorsement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EndorsementController extends Controller
{
    public function store(Request $request, User $user)
    {
        $request->validate([
            'skill' => 'required|string|max:120',
        ]);

        $endorserId = Auth::id();
        $endorseeId = $user->id;

        if ($endorserId === $endorseeId) {
            return back()->with('error', 'You cannot endorse yourself.');
        }

        $isConnected = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($endorserId, $endorseeId) {
                $query->where('requester_id', $endorserId)->where('addressee_id', $endorseeId)
                    ->orWhere(function ($sub) use ($endorserId, $endorseeId) {
                        $sub->where('requester_id', $endorseeId)->where('addressee_id', $endorserId);
                    });
            })
            ->exists();

        if (!$isConnected) {
            return back()->with('error', 'You can endorse only your connections.');
        }

        $skill = trim((string) $request->skill);

        SkillEndorsement::firstOrCreate([
            'endorser_id' => $endorserId,
            'endorsee_id' => $endorseeId,
            'skill' => $skill,
        ]);

        return back()->with('success', 'Endorsement added.');
    }
}
