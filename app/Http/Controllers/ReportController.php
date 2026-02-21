<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\SwapRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reported_user_id' => 'required|exists:users,id',
            'swap_request_id' => 'nullable|exists:swap_requests,id',
            'reason' => 'required|in:spam,abuse,no_show,other',
            'details' => 'nullable|string|max:1000',
        ]);

        $rateKey = 'report-submit:' . auth()->id();
        if (RateLimiter::tooManyAttempts($rateKey, 4)) {
            if (ActivityLog::enabled()) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'type' => 'rate_limited',
                    'meta' => [
                        'scope' => 'report_submit',
                        'reported_user_id' => (int) $request->reported_user_id,
                    ],
                ]);
            }
            return back()->with('error', 'Please wait before submitting another report.');
        }

        if ((int) $request->reported_user_id === (int) auth()->id()) {
            return back()->with('error', 'You cannot report yourself.');
        }

        if ($request->swap_request_id) {
            $swapRequest = SwapRequest::findOrFail($request->swap_request_id);

            $related = in_array(auth()->id(), [$swapRequest->from_user_id, $swapRequest->to_user_id], true);
            if (!$related) {
                abort(403);
            }
        }

        $exists = Report::query()
            ->where('reporter_user_id', auth()->id())
            ->where('reported_user_id', $request->reported_user_id)
            ->where('swap_request_id', $request->swap_request_id)
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            return back()->with('error', 'You already submitted an open report for this case.');
        }

        Report::create([
            'reporter_user_id' => auth()->id(),
            'reported_user_id' => $request->reported_user_id,
            'swap_request_id' => $request->swap_request_id,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'open',
        ]);

        RateLimiter::hit($rateKey, 3600);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'report_submitted',
                'meta' => [
                    'reported_user_id' => (int) $request->reported_user_id,
                    'reason' => $request->reason,
                ],
            ]);
        }

        return back()->with('success', 'Report submitted. We will review it.');
    }
}
