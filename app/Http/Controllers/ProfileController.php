<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use App\Support\PasswordRules;
use App\Support\ProfileMetrics;

class ProfileController extends Controller
{
    public const AVAILABILITY_OPTIONS = [
        'Mon Morning',
        'Mon Evening',
        'Tue Morning',
        'Tue Evening',
        'Wed Morning',
        'Wed Evening',
        'Thu Morning',
        'Thu Evening',
        'Fri Morning',
        'Fri Evening',
        'Sat Morning',
        'Sat Evening',
        'Sun Morning',
        'Sun Evening',
    ];

    public function edit()
    {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $availabilityOptions = self::AVAILABILITY_OPTIONS;

        if (empty($user->slug)) {
            $user->slug = \App\Models\User::generateUniqueSlug($user->name);
            $user->save();
        }
        $activityType = request()->query('activity_type', '');
        $activityFrom = request()->query('activity_from', '');
        $activityTo = request()->query('activity_to', '');

        if (ActivityLog::enabled()) {
            $activityLogsQuery = ActivityLog::query()
                ->where('user_id', $user->id);

            if ($activityType !== '') {
                $activityLogsQuery->where('type', $activityType);
            }

            if ($activityFrom !== '') {
                $activityLogsQuery->whereDate('created_at', '>=', $activityFrom);
            }

            if ($activityTo !== '') {
                $activityLogsQuery->whereDate('created_at', '<=', $activityTo);
            }

            $activityLogs = $activityLogsQuery
                ->latest()
                ->limit(12)
                ->get();
        } else {
            $activityLogs = collect();
        }

        $acceptedSwaps = \App\Models\SwapRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->count();

        $ratingsReceived = \App\Models\Rating::query()
            ->where('to_user_id', $user->id)
            ->count();

        $openReports = \App\Models\Report::query()
            ->where('reported_user_id', $user->id)
            ->where('status', 'open')
            ->count();

        if (ActivityLog::enabled()) {
            $activityTypes = ActivityLog::query()
                ->where('user_id', $user->id)
                ->select('type')
                ->distinct()
                ->orderBy('type')
                ->pluck('type')
                ->values()
                ->all();
        } else {
            $activityTypes = [];
        }

        $metrics = ProfileMetrics::completion($user, $profile);

        return view('profile.edit', compact(
            'user',
            'profile',
            'availabilityOptions',
            'activityLogs',
            'acceptedSwaps',
            'ratingsReceived',
            'openReports',
            'activityType',
            'activityFrom',
            'activityTo',
            'activityTypes',
            'metrics'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'city' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'headline' => 'nullable|string|max:140',
            'skills_offered' => 'nullable|string|max:2000',
            'skills_wanted' => 'nullable|string|max:2000',
            'availability_slots' => 'nullable|array',
            'availability_slots.*' => ['string', Rule::in(self::AVAILABILITY_OPTIONS)],
            'skill_tags' => 'nullable|string|max:2000',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'availability_status' => ['nullable', 'string', 'max:32', Rule::in(['available', 'busy', 'away'])],
            'portfolio_links' => 'nullable|string|max:2000',
            'website_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
        ]);

        if ($request->filled('price_min') && $request->filled('price_max')) {
            if ((int) $request->price_max < (int) $request->price_min) {
                return back()->with('error', 'Price max must be greater than or equal to price min.');
            }
        }

        $user = Auth::user();

        $user->city = $request->city;
        $user->bio = $request->bio;
        $user->headline = $request->headline;
        $user->skills_offered = $request->skills_offered;
        $user->skills_wanted = $request->skills_wanted;
        $user->availability_slots = $request->input('availability_slots', []);
        $user->portfolio_links = collect(preg_split('/[,\n]+/', (string) $request->portfolio_links) ?: [])
            ->map(fn ($link) => trim((string) $link))
            ->filter()
            ->values()
            ->all();
        $user->website_url = $request->website_url;
        $user->linkedin_url = $request->linkedin_url;
        $user->instagram_url = $request->instagram_url;
        $user->youtube_url = $request->youtube_url;

        $user->save();

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $skillTags = collect(preg_split('/[,\n]+/', (string) $request->skill_tags) ?: [])
            ->map(fn ($tag) => mb_strtolower(trim((string) $tag), 'UTF-8'))
            ->filter()
            ->values()
            ->all();
        $profile->fill([
            'skill_tags' => $skillTags,
            'price_min' => $request->price_min,
            'price_max' => $request->price_max,
            'availability_status' => $request->availability_status ?: $profile->availability_status,
        ]);
        $profile->save();

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => $user->id,
                'type' => 'profile_updated',
                'meta' => [
                    'city' => $user->city,
                ],
            ]);
        }

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => PasswordRules::rules(),
        ], array_merge(PasswordRules::messages(), [
            'current_password.required' => 'Current password is required.',
        ]));

        $user = Auth::user();

        if (!Hash::check((string) $request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => $user->id,
                'type' => 'password_updated',
            ]);
        }

        return back()->with('success', 'Password updated successfully.');
    }

    public function requestVerification()
    {
        $user = Auth::user();

        if (!$user->verification_requested_at) {
            $user->verification_requested_at = now();
            $user->save();
        }

        return back()->with('success', 'Verification request submitted. Our team will review it.');
    }

    public function hideLocationTags()
    {
        $user = Auth::user();
        $user->hide_tags_until = now()->addHours(24);
        $user->save();

        return back()->with('success', 'Location tags hidden for 24 hours.');
    }

    public function showLocationTags()
    {
        $user = Auth::user();
        $user->hide_tags_until = null;
        $user->save();

        return back()->with('success', 'Location tags are now visible to friends.');
    }
}
