@extends('layouts.app')

@section('title', 'Admin Analytics')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Admin Analytics</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.analytics.index', ['days' => 7]) }}" class="btn btn-sm {{ $days === 7 ? 'btn-gradient' : 'btn-glow' }}">7d</a>
        <a href="{{ route('admin.analytics.index', ['days' => 14]) }}" class="btn btn-sm {{ $days === 14 ? 'btn-gradient' : 'btn-glow' }}">14d</a>
        <a href="{{ route('admin.analytics.index', ['days' => 30]) }}" class="btn btn-sm {{ $days === 30 ? 'btn-gradient' : 'btn-glow' }}">30d</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Total Users</p><h3 class="h4 mb-0">{{ $totalUsers }}</h3><small>+{{ $newUsersPeriod }} in {{ $days }}d</small></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Swap Requests</p><h3 class="h4 mb-0">{{ $totalSwapRequests }}</h3><small>{{ $acceptedCount }} accepted</small></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Acceptance Rate</p><h3 class="h4 mb-0">{{ $acceptanceRate }}%</h3><small>all-time conversion</small></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Open Reports</p><h3 class="h4 mb-0">{{ $openReports }}</h3><small>{{ $reportsPeriod }} new in {{ $days }}d</small></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Messages ({{ $days }}d)</p><h3 class="h4 mb-0">{{ $messagesPeriod }}</h3></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Ratings ({{ $days }}d)</p><h3 class="h4 mb-0">{{ $ratingsPeriod }}</h3></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Avg Rating</p><h3 class="h4 mb-0">{{ number_format($avgRating, 2) }}</h3></div></div>
    <div class="col-md-3"><div class="card p-3 insight-card"><p class="small mb-1">Avg Response Time</p><h3 class="h4 mb-0">{{ $avgFirstResponseMinutes }}m</h3></div></div>
</div>

<div class="card p-4 mb-4">
    <h3 class="h5 mb-3">Daily Message Activity</h3>
    <div class="activity-bars">
        @foreach($dailySeries as $day)
            @php $height = max(6, (int) round(($day['messages'] / $peakMessages) * 100)); @endphp
            <div class="activity-day">
                <div class="activity-col" style="height: {{ $height }}%;" title="{{ $day['label'] }}: {{ $day['messages'] }} messages"></div>
                <small>{{ $day['label'] }}</small>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h3 class="h5 mb-3">Top Skills</h3>
            @forelse($topSkills as $row)
                <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
                    <span>{{ ucfirst($row['skill']) }}</span>
                    <span>{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="mb-0">No skill data yet.</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h3 class="h5 mb-3">High-Risk Users (Reports)</h3>
            @forelse($highRiskUsers as $row)
                <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
                    <span>{{ $row['name'] }}</span>
                    <span>{{ $row['reports_count'] }} reports</span>
                </div>
            @empty
                <p class="mb-0">No reports yet.</p>
            @endforelse
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 h-100">
            <h3 class="h5 mb-3">Slow Responders</h3>
            @forelse($slowResponders as $row)
                <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
                    <span>{{ $row['name'] }}</span>
                    <span>{{ $row['avg_minutes'] }}m avg</span>
                </div>
            @empty
                <p class="mb-0">Not enough data yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h3 class="h5 mb-3">Activity Pulse ({{ $days }}d)</h3>
            @forelse($activityPulse as $row)
                <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
                    <span>{{ ucfirst(str_replace('_', ' ', $row['type'])) }}</span>
                    <span>{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="mb-0">No activity logs yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4 h-100">
            <h3 class="h5 mb-3">Rate Limit Triggers ({{ $days }}d)</h3>
            @forelse($rateLimitTriggers as $row)
                <div class="d-flex justify-content-between border-bottom border-secondary-subtle py-2">
                    <span>{{ ucfirst(str_replace('_', ' ', $row['scope'])) }}</span>
                    <span>{{ $row['count'] }}</span>
                </div>
            @empty
                <p class="mb-0">No rate limit triggers yet.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card p-4 mt-4">
    <h3 class="h5 mb-3">Verification Requests</h3>
    @forelse($verificationRequests as $user)
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2 flex-wrap gap-2">
            <div>
                <strong>{{ $user->name }}</strong>
                <small class="d-block text-white-50">{{ $user->email }}</small>
            </div>
            <form method="POST" action="{{ route('admin.verification.update', $user->id) }}" class="d-flex gap-2 align-items-center">
                @csrf
                <select name="verified_badge" class="form-control form-control-sm">
                    <option value="">No badge</option>
                    <option value="verified">Verified</option>
                    <option value="top_mentor">Top Mentor</option>
                    <option value="five_star_pro">5 Star Pro</option>
                </select>
                <button class="btn btn-gradient btn-sm">Approve</button>
            </form>
        </div>
    @empty
        <p class="mb-0">No verification requests yet.</p>
    @endforelse
</div>
@endsection
