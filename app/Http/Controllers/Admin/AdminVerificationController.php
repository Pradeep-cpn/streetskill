<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminVerificationController extends Controller
{
    public function update(Request $request, User $user)
    {
        $request->validate([
            'verified_badge' => 'nullable|in:verified,top_mentor,five_star_pro',
        ]);

        $user->verified_badge = $request->verified_badge;
        $user->verification_requested_at = null;
        $user->save();

        return back()->with('success', 'Verification badge updated.');
    }
}
