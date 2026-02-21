@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="mb-4">Edit Profile</h2>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $user->city) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="3">{{ old('bio', $user->bio) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Professional Headline</label>
                    <input type="text" name="headline" class="form-control" value="{{ old('headline', $user->headline) }}" placeholder="e.g. Guitar Mentor | 5+ years coaching">
                </div>

                <div class="mb-3">
                    <label class="form-label">Skills You Can Teach</label>
                    <textarea name="skills_offered" class="form-control" rows="3" placeholder="Comma separated, e.g. Photoshop, Guitar, Excel">{{ old('skills_offered', $user->skills_offered) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Skills You Want to Learn</label>
                    <textarea name="skills_wanted" class="form-control" rows="3" placeholder="Comma separated, e.g. Public Speaking, Cooking">{{ old('skills_wanted', $user->skills_wanted) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Portfolio Links</label>
                    <textarea name="portfolio_links" class="form-control" rows="3" placeholder="One per line or comma separated">{{ old('portfolio_links', isset($user->portfolio_links) ? implode("\n", $user->portfolio_links) : '') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Website</label>
                    <input type="url" name="website_url" class="form-control" value="{{ old('website_url', $user->website_url) }}" placeholder="https://yourwebsite.com">
                </div>

                <div class="mb-3">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $user->linkedin_url) }}" placeholder="https://linkedin.com/in/username">
                </div>

                <div class="mb-3">
                    <label class="form-label">Instagram</label>
                    <input type="url" name="instagram_url" class="form-control" value="{{ old('instagram_url', $user->instagram_url) }}" placeholder="https://instagram.com/username">
                </div>

                <div class="mb-4">
                    <label class="form-label">YouTube</label>
                    <input type="url" name="youtube_url" class="form-control" value="{{ old('youtube_url', $user->youtube_url) }}" placeholder="https://youtube.com/@channel">
                </div>

                <div class="mb-4">
                    <label class="form-label d-block mb-2">Availability Slots</label>
                    <div class="availability-grid">
                        @foreach($availabilityOptions as $slot)
                            <label class="availability-item">
                                <input
                                    type="checkbox"
                                    name="availability_slots[]"
                                    value="{{ $slot }}"
                                    {{ in_array($slot, old('availability_slots', $user->availability_slots ?? []), true) ? 'checked' : '' }}
                                >
                                <span>{{ $slot }}</span>
                            </label>
                        @endforeach
                    </div>
                    <small>Select when you are usually available for skill swaps.</small>
                </div>

                <button type="submit" class="btn btn-gradient">Update Profile</button>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 mb-4">
            <h3 class="h5 mb-3">Reliability Snapshot</h3>
            <div class="profile-metrics">
                <div class="metric-card">
                    <span>Accepted swaps</span>
                    <strong>{{ $acceptedSwaps }}</strong>
                </div>
                <div class="metric-card">
                    <span>Public profile</span>
                    <strong><a href="{{ url('/user/'.$user->slug) }}" class="btn btn-link">{{ url('/user/'.$user->slug) }}</a></strong>
                </div>
                <div class="metric-card">
                    <span>Ratings received</span>
                    <strong>{{ $ratingsReceived }}</strong>
                </div>
                <div class="metric-card">
                    <span>Open reports</span>
                    <strong>{{ $openReports }}</strong>
                </div>
                <div class="metric-card">
                    <span>Email status</span>
                    <strong>{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</strong>
                </div>
                <div class="metric-card">
                    <span>Mentor badge</span>
                    <strong>{{ $user->verified_badge ? ucwords(str_replace('_', ' ', $user->verified_badge)) : 'None' }}</strong>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <h3 class="h5 mb-3">Recent Activity</h3>
            <form method="GET" action="{{ route('profile.edit') }}" class="activity-filters mb-3">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-1">Type</label>
                        <select name="activity_type" class="form-control form-control-sm">
                            <option value="">All activity</option>
                            @foreach($activityTypes as $type)
                                <option value="{{ $type }}" {{ $activityType === $type ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">From</label>
                        <input type="date" name="activity_from" class="form-control form-control-sm" value="{{ $activityFrom }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1">To</label>
                        <input type="date" name="activity_to" class="form-control form-control-sm" value="{{ $activityTo }}">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-glow btn-sm">Apply filters</button>
                    <a class="btn btn-outline-light btn-sm" href="{{ route('profile.edit') }}">Reset</a>
                </div>
            </form>

            <a
                class="btn btn-gradient btn-sm mb-3 w-100"
                href="{{ route('profile.activity.export', ['type' => $activityType, 'from' => $activityFrom, 'to' => $activityTo]) }}"
            >Export activity CSV</a>
            @if($activityLogs->isEmpty())
                <p class="small mb-0">No activity yet. Start a swap to build your history.</p>
            @else
                <div class="activity-list">
                    @foreach($activityLogs as $log)
                        <div class="activity-item">
                            <strong>{{ ucfirst(str_replace('_', ' ', $log->type)) }}</strong>
                            <small>{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card p-4 mt-4">
            <h3 class="h5 mb-3">Mentor Verification</h3>
            @if($user->verified_badge)
                <p class="small mb-2">You are verified as {{ ucwords(str_replace('_', ' ', $user->verified_badge)) }}.</p>
            @elseif($user->verification_requested_at)
                <p class="small mb-2">Request submitted. Pending review.</p>
            @else
                <p class="small mb-3">Apply to get a verified mentor badge.</p>
                <form method="POST" action="{{ route('profile.verification.request') }}">
                    @csrf
                    <button class="btn btn-glow btn-sm w-100">Request Verification</button>
                </form>
            @endif
        </div>

        <div class="card p-4 mt-4">
            <h3 class="h5 mb-3">Location Privacy</h3>
            @if($user->hide_tags_until && $user->hide_tags_until->isFuture())
                <p class="small mb-3">Location tags are hidden until {{ $user->hide_tags_until->diffForHumans() }}.</p>
                <form method="POST" action="{{ route('profile.location.show') }}">
                    @csrf
                    <button class="btn btn-glow btn-sm w-100">Make Tags Visible</button>
                </form>
            @else
                <p class="small mb-3">Your location tags are visible to accepted friends.</p>
                <form method="POST" action="{{ route('profile.location.hide') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm w-100">Hide Tags For 24 Hours</button>
                </form>
            @endif
        </div>

        <div class="card p-4 mt-4">
            <h3 class="h5 mb-3">Change Password</h3>
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <div class="input-group">
                        <input
                            id="current-password"
                            type="password"
                            name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            required
                        >
                        <button class="btn btn-outline-light password-toggle" type="button" data-toggle-password data-target="#current-password" aria-label="Show password">
                            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M5.1 5.1C3.4 6.3 2 8 2 8s4 6 10 6c1.4 0 2.7-.2 3.8-.6" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M19 10s-1.2 1.8-3.6 3.3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </div>
                    @error('current_password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <input
                            id="new-password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            required
                            minlength="8"
                            maxlength="12"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,12}$"
                            title="8-12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character."
                        >
                        <button class="btn btn-outline-light password-toggle" type="button" data-toggle-password data-target="#new-password" aria-label="Show password">
                            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M5.1 5.1C3.4 6.3 2 8 2 8s4 6 10 6c1.4 0 2.7-.2 3.8-.6" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M19 10s-1.2 1.8-3.6 3.3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small>Password must be 8-12 characters with 1 uppercase, 1 lowercase, 1 number, 1 special.</small>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input
                            id="confirm-password"
                            type="password"
                            name="password_confirmation"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            required
                            minlength="8"
                            maxlength="12"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,12}$"
                            title="8-12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character."
                        >
                        <button class="btn btn-outline-light password-toggle" type="button" data-toggle-password data-target="#confirm-password" aria-label="Show password">
                            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M5.1 5.1C3.4 6.3 2 8 2 8s4 6 10 6c1.4 0 2.7-.2 3.8-.6" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M19 10s-1.2 1.8-3.6 3.3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <button class="btn btn-gradient w-100">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
