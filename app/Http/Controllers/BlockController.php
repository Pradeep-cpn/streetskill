<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBlock;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'blocked_user_id' => 'required|exists:users,id',
        ]);

        $blockedUserId = (int) $request->blocked_user_id;
        if ($blockedUserId === (int) auth()->id()) {
            return back()->with('error', 'You cannot block yourself.');
        }

        UserBlock::firstOrCreate([
            'blocker_user_id' => auth()->id(),
            'blocked_user_id' => $blockedUserId,
        ]);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'user_blocked',
                'meta' => [
                    'blocked_user_id' => $blockedUserId,
                ],
            ]);
        }

        return back()->with('success', 'User blocked. You will no longer receive chats from them.');
    }

    public function destroy(User $user)
    {
        UserBlock::query()
            ->where('blocker_user_id', auth()->id())
            ->where('blocked_user_id', $user->id)
            ->delete();

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'user_unblocked',
                'meta' => [
                    'blocked_user_id' => $user->id,
                ],
            ]);
        }

        return back()->with('success', 'User unblocked.');
    }
}
