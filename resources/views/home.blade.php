@extends('layouts.app')

@section('title', 'Home')

@section('meta')
    @php
        $homeUrl = url('/');
        $homeTitle = 'StreetSkill | Skill Exchange Network';
        $homeDescription = 'Find verified mentors, swap skills, and build a professional learning portfolio on StreetSkill.';
    @endphp
    <link rel="canonical" href="{{ $homeUrl }}">
    <meta name="description" content="{{ $homeDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $homeTitle }}">
    <meta property="og:description" content="{{ $homeDescription }}">
    <meta property="og:url" content="{{ $homeUrl }}">
@endsection

@section('content')
@php
    $trendPicks = $trendPicks ?? [
        ['title' => 'StreetFit 30-min circuits', 'meta' => '+48% local interest', 'tag' => 'Fitness'],
        ['title' => 'Phone video editing basics', 'meta' => 'Top rated this week', 'tag' => 'Creative'],
        ['title' => 'DJ starter mixes', 'meta' => 'Weekend swaps trending', 'tag' => 'Music'],
        ['title' => 'Barista latte art', 'meta' => 'High trust mentors nearby', 'tag' => 'Lifestyle'],
    ];

    $microChallenges = $microChallenges ?? [
        ['title' => 'Teach a 15-min micro-skill', 'meta' => 'Earn +8 trust points', 'cta' => 'Post a swap'],
        ['title' => 'Host 1 local meetup', 'meta' => 'Unlock Host badge', 'cta' => 'Plan event'],
        ['title' => 'Give 2 ratings', 'meta' => 'Boost your match score', 'cta' => 'Review now'],
    ];

    $communityEvents = $communityEvents ?? [
        ['title' => 'StreetSkill Jam Session', 'meta' => 'Sat 5:30 PM', 'city' => 'Downtown Hub'],
        ['title' => 'Weekend Skill Lab', 'meta' => 'Sun 2:00 PM', 'city' => 'River Park'],
    ];

    $reliabilitySignals = $reliabilitySignals ?? [
        ['label' => 'Verified swaps', 'value' => '2x stronger match weight'],
        ['label' => 'Anti-spam filters', 'value' => 'Clean requests only'],
        ['label' => 'Ratings lock', 'value' => 'Only after a real swap'],
        ['label' => 'Report safety', 'value' => 'Fast moderation response'],
    ];
@endphp

<section class="hero card p-4 p-md-5 mb-4">
    <div class="hero-grid">
        <div class="hero-panel">
            <p class="hero-kicker">StreetSkill Learning Network</p>
            <h1>Learn faster by exchanging what you already know.</h1>
            <p class="hero-subtext">
                Build real skills with local people through direct swaps. No endless course lists,
                just practical matches, clear requests, and trust built after each completed swap.
            </p>

            <div class="hero-actions">
                @auth
                    <a href="{{ route('marketplace') }}" class="btn btn-gradient">Explore Marketplace</a>
                    <a href="{{ route('requests.dashboard') }}" class="btn btn-glow">View Requests</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-gradient">Create Free Account</a>
                    <a href="{{ route('login') }}" class="btn btn-glow">Sign In</a>
                @endauth
            </div>

            <div class="hero-stats">
                <div class="stat-pill">
                    <h3>{{ $stats['active_swaps'] ?? '320+' }}</h3>
                    <span>Active Swaps</span>
                </div>
                <div class="stat-pill">
                    <h3>{{ $stats['trust_avg'] ?? '4.8/5' }}</h3>
                    <span>Trust Average</span>
                </div>
                <div class="stat-pill">
                    <h3>{{ $stats['fast_match'] ?? '12m' }}</h3>
                    <span>Fastest Match</span>
                </div>
            </div>
        </div>

        <div class="hero-card-stack">
            <article class="card p-3 glow-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h3 class="h6 mb-0">Quick Match</h3>
                    <span class="match-chip">+92%</span>
                </div>
                <p class="small mb-0">3 mutual skills found near you. Reach high-trust mentors faster.</p>
            </article>

            <article class="card p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h3 class="h6 mb-0">Swap Confidence</h3>
                    <span class="quality-chip">Safe</span>
                </div>
                <p class="small mb-0">Ratings are unlocked only after accepted swaps to keep reviews honest.</p>
            </article>

            <article class="card p-3">
                <h3 class="h6 mb-2">Live in Your City</h3>
                <p class="small mb-0">See active learners nearby, shared availability, and clear next steps.</p>
            </article>
        </div>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-md-4 d-flex">
        <article class="card p-4 w-100 concept-card">
            <h3 class="mb-2">1. Discover</h3>
            <p class="mb-0">Find people by skills, city, and overlapping availability slots.</p>
        </article>
    </div>
    <div class="col-md-4 d-flex">
        <article class="card p-4 w-100 concept-card">
            <h3 class="mb-2">2. Swap</h3>
            <p class="mb-0">Send clear requests with quality scoring and anti-spam protection.</p>
        </article>
    </div>
    <div class="col-md-4 d-flex">
        <article class="card p-4 w-100 concept-card">
            <h3 class="mb-2">3. Trust</h3>
            <p class="mb-0">Chat and ratings unlock only after accepted swaps for safer outcomes.</p>
        </article>
    </div>
</section>

@auth
<section class="row g-4 mb-4">
    <div class="col-lg-7 d-flex">
        <article class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0">Profile Completion</h2>
                <span class="match-chip">{{ $profileCompletion }}%</span>
            </div>

            <div class="progress-track mb-3">
                <div class="progress-fill" style="width: {{ $profileCompletion }}%"></div>
            </div>

            <div class="row g-2">
                @foreach($profileChecklist as $item)
                    <div class="col-md-6">
                        <div class="check-item {{ $item['done'] ? 'done' : '' }}">
                            <span>{{ $item['done'] ? '✓' : '•' }}</span>
                            <span>{{ $item['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($profileCompletion < 100)
                <div class="mt-3">
                    <a href="{{ route('profile.edit') }}" class="btn btn-gradient btn-sm">Complete Profile</a>
                </div>
            @endif
        </article>
    </div>

    <div class="col-lg-5 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Next Best Action</h2>
            <div class="match-box mb-3 p-3">
                <h3 class="h6 mb-1">{{ $nextBestAction['title'] ?? 'Take your next step' }}</h3>
                <p class="small mb-0">{{ $nextBestAction['description'] ?? 'Keep your learning momentum active.' }}</p>
            </div>
            <a href="{{ $nextBestAction['cta_route'] ?? route('marketplace') }}" class="btn btn-gradient btn-sm mb-4">
                {{ $nextBestAction['cta_label'] ?? 'Continue' }}
            </a>

            <div class="profile-metrics">
                <div class="metric-card">
                    <span>Accepted swaps (30d)</span>
                    <strong>{{ $momentum['accepted_30d'] ?? 0 }}</strong>
                </div>
                <div class="metric-card">
                    <span>Ratings given (30d)</span>
                    <strong>{{ $momentum['ratings_given_30d'] ?? 0 }}</strong>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-xl-7 d-flex">
        <article class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">Learning Path</h2>
                @php $completedPathSteps = collect($learningPath)->where('done', true)->count(); @endphp
                <span class="match-chip">{{ $completedPathSteps }}/{{ count($learningPath) }} done</span>
            </div>

            <div class="path-steps">
                @foreach($learningPath as $step)
                    <div class="path-step {{ $step['done'] ? 'done' : '' }}">
                        <div>
                            <h3 class="h6 mb-1">{{ $step['title'] }}</h3>
                            <p class="small mb-0">{{ $step['description'] }}</p>
                        </div>
                        @if($step['done'])
                            <span class="path-state done">Done</span>
                        @else
                            <a href="{{ $step['cta_route'] }}" class="btn btn-glow btn-sm">{{ $step['cta_label'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <div class="col-xl-5 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">This Week Plan</h2>
            <ul class="feature-list mb-0">
                @foreach($weeklyPlan as $planItem)
                    <li>{{ $planItem }}</li>
                @endforeach
            </ul>
        </article>
    </div>
</section>

<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="section-title mb-0">Recommended Learning Tracks</h2>
        <a href="{{ route('profile.edit') }}" class="btn btn-glow btn-sm">Edit Skills</a>
    </div>

    @if(empty($learningTracks))
        <p class="mb-0">Add skills you want to learn in your profile to unlock custom tracks.</p>
    @else
        <div class="row g-3">
            @foreach($learningTracks as $track)
                <div class="col-md-4 d-flex">
                    <article class="card p-3 w-100 track-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="h6 mb-0">{{ $track['skill'] }}</h3>
                            <span class="results-pill">{{ $track['mentors_count'] }} mentors</span>
                        </div>
                        @if(!empty($track['mentor_names']))
                            <p class="small mb-0"><strong>Top matches:</strong> {{ implode(', ', $track['mentor_names']) }}</p>
                        @else
                            <p class="small mb-0">No direct mentor found yet. Keep skill tags specific for better matches.</p>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endauth

<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="section-title mb-0">Pulse Trends</h2>
        <span class="nav-pill">Youth picks</span>
    </div>
    @if(empty($trendPicks))
        <p class="small mb-0">No trends yet. Be the first to start a swap.</p>
    @else
        <div class="trend-grid">
            @foreach($trendPicks as $trend)
                <div class="trend-card">
                    <strong>{{ $trend['title'] }}</strong>
                    <small>{{ $trend['meta'] }}</small>
                    <span class="results-pill">{{ $trend['tag'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</section>

@auth
<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="section-title mb-0">Best Matches For You</h2>
        <a href="{{ route('marketplace') }}" class="btn btn-glow btn-sm">See All</a>
    </div>

    @if($suggestedUsers->isEmpty())
        <p class="mb-0">Complete your profile skills and availability slots to unlock personalized matches.</p>
    @else
        <div class="row g-3">
            @foreach($suggestedUsers as $candidate)
                <div class="col-md-4 d-flex">
                    <article class="card p-3 w-100 suggestion-card">
                        <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                            <h3 class="h6 mb-0">{{ $candidate->name }}</h3>
                            <span class="match-chip">{{ $candidate->match_score }}% match</span>
                        </div>

                        <p class="small mb-2"><strong>Trust:</strong> {{ $candidate->trust_score }}/100</p>

                        <div class="mb-2 d-flex flex-wrap gap-1">
                            @foreach($candidate->badges as $badge)
                                <span class="badge-pill">{{ $badge }}</span>
                            @endforeach
                        </div>

                        @if(!empty($candidate->match_teaches_you))
                            <p class="small mb-1"><strong>Can teach you:</strong> {{ implode(', ', array_slice($candidate->match_teaches_you, 0, 2)) }}</p>
                        @endif

                        @if(!empty($candidate->match_learns_from_you))
                            <p class="small mb-1"><strong>Wants from you:</strong> {{ implode(', ', array_slice($candidate->match_learns_from_you, 0, 2)) }}</p>
                        @endif

                        @if(!empty($candidate->availability_overlap))
                            <p class="small mb-0"><strong>Shared slots:</strong> {{ implode(', ', array_slice($candidate->availability_overlap, 0, 2)) }}</p>
                        @endif

                        @if($candidate->slug)
                            <a href="{{ route('public.profile', $candidate->slug) }}" class="btn btn-link btn-sm mt-2">View Profile</a>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    @endif
</section>

@php
    $spotlights = collect($spotlights ?? ($suggestedUsers ?? ($feedUsers ?? [])));
@endphp
<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="section-title mb-0">Creator Spotlight</h2>
        <a href="{{ route('marketplace') }}" class="btn btn-glow btn-sm">See All</a>
    </div>
    @if($spotlights->isEmpty())
        <p class="mb-0">Complete your profile to unlock personalized spotlights.</p>
    @else
        <div class="spotlight-grid">
            @foreach($spotlights->take(4) as $creator)
                <div class="spotlight-card">
                    <div class="spotlight-header">
                        <strong>{{ $creator->name }}</strong>
                        <span class="match-chip">{{ $creator->match_score ?? 88 }}% match</span>
                    </div>
                    <small>{{ $creator->city ?: 'City not set' }}</small>
                    <div class="spotlight-skills">
                        @foreach(collect($creator->badges ?? [])->take(3) as $badge)
                            <span class="skill-chip">{{ $badge }}</span>
                        @endforeach
                    </div>
                    <small>Trust {{ $creator->trust_score ?? 90 }}/100</small>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endauth

<section class="row g-4 mb-4">
    <div class="col-lg-6 d-flex">
        <article class="card p-4 w-100 streak-card">
            <h2 class="h5 mb-3">Daily Streak</h2>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                    <div class="streak-number">{{ $streak['days'] ?? 7 }}</div>
                    <small>Days active in a row</small>
                </div>
                <span class="quality-chip">+{{ $streak['boost'] ?? 12 }}% match boost</span>
            </div>
            <p class="small mb-0">Stay active 3 days weekly to keep your visibility high.</p>
        </article>
    </div>
    <div class="col-lg-6 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Micro-Challenges</h2>
            @if(empty($microChallenges))
                <p class="small mb-0">Log in to unlock personalized challenges.</p>
            @else
                <div class="challenge-row">
                    @foreach($microChallenges as $challenge)
                        <div class="challenge-card">
                            <strong>{{ $challenge['title'] }}</strong>
                            <small>{{ $challenge['meta'] }}</small>
                            <span class="results-pill">{{ $challenge['cta'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-7 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Community Events</h2>
            @if(empty($communityEvents))
                <p class="small mb-0">Add your city to see local activity.</p>
            @else
                <div class="challenge-row">
                    @foreach($communityEvents as $event)
                        <div class="event-card">
                            <strong>{{ $event['title'] }}</strong>
                            <div class="event-meta">
                                <span>{{ $event['meta'] }}</span>
                                <span>{{ $event['city'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </div>
    <div class="col-lg-5 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Reliability Layer</h2>
            <div class="reliability-grid">
                @foreach($reliabilitySignals as $signal)
                    <div class="reliability-card">
                        <span>{{ $signal['label'] }}</span>
                        <strong>{{ $signal['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </article>
    </div>
</section>

<section class="card p-4 p-md-5">
    <h2 class="section-title mb-3">Why This Concept Works</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <ul class="feature-list mb-0">
                <li>Low barrier to start with practical one-to-one exchanges.</li>
                <li>Real outcomes through direct person-to-person learning.</li>
                <li>City-based discovery helps build stronger community retention.</li>
            </ul>
        </div>
        <div class="col-md-6">
            <ul class="feature-list mb-0">
                <li>Smart scoring surfaces better opportunities first.</li>
                <li>Availability overlap improves successful scheduling.</li>
                <li>Trust layer with reports, ratings, and safe messaging.</li>
            </ul>
        </div>
    </div>
</section>
@endsection
