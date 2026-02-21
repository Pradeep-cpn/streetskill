@extends('layouts.app')

@section('title', $user->name)

@section('meta')
    @php
        $profileUrl = url('/user/' . $user->slug);
        $title = $user->name . ' | StreetSkill';
        $description = trim(($user->headline ?: '') . ' ' . ($user->bio ?: ''));
        $description = $description !== '' ? $description : 'Skill portfolio and mentoring profile on StreetSkill.';
    @endphp
    <link rel="canonical" href="{{ $profileUrl }}">
    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $profileUrl }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $user->name,
        'url' => $profileUrl,
        'jobTitle' => $user->headline,
        'description' => $description,
        'address' => $user->city ? ['@type' => 'PostalAddress', 'addressLocality' => $user->city] : null,
        'sameAs' => array_values(array_filter([
            $user->website_url,
            $user->linkedin_url,
            $user->instagram_url,
            $user->youtube_url,
        ])),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endsection

@section('content')
<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-2">{{ $user->name }}</h1>
            @if($user->headline)
                <p class="mb-2">{{ $user->headline }}</p>
            @endif
            @if($user->slug && auth()->check() && auth()->id() !== $user->id)
                <form method="POST" action="{{ route('endorse.store', $user->id) }}" class="d-flex gap-2 flex-wrap mt-2">
                    @csrf
                    <input type="text" name="skill" class="form-control form-control-sm" placeholder="Endorse skill (e.g. Guitar)" required>
                    <button class="btn btn-glow btn-sm">Endorse</button>
                </form>
            @endif
            <div class="d-flex flex-wrap gap-2">
                @if($user->verified_badge)
                    <span class="badge-pill">{{ ucwords(str_replace('_', ' ', $user->verified_badge)) }}</span>
                @endif
                <span class="badge-pill">{{ number_format((float) $user->rating, 1) }} ★</span>
                <span class="badge-pill">{{ $completedSwaps }} swaps</span>
                @if($user->city)
                    <span class="badge-pill">{{ $user->city }}</span>
                @endif
            </div>
        </div>
        <div>
            <span class="results-pill">Profile</span>
        </div>
    </div>
    @if($user->bio)
        <p class="mt-3 mb-0">{{ $user->bio }}</p>
    @endif
</section>

@if(!empty($compatibility))
<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 class="h5 mb-2">AI Swap Compatibility</h2>
            <p class="mb-0">Human chemistry score for a better swap fit.</p>
        </div>
        <div class="text-end">
            <div class="display-6 mb-1">{{ $compatibility['score'] }}%</div>
            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#compatibilityBreakdown">
                View breakdown
            </button>
        </div>
    </div>
</section>

<div class="modal fade" id="compatibilityBreakdown" tabindex="-1" aria-labelledby="compatibilityBreakdownLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="compatibilityBreakdownLabel">Compatibility Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Skill match strength</span>
                    <strong>{{ $compatibility['breakdown']['skill_match_strength'] }}%</strong>
                </div>
                <p class="small mb-3">Overlap between what you want to learn and what they offer, plus the reverse.</p>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Rating alignment</span>
                    <strong>{{ $compatibility['breakdown']['rating_alignment'] }}%</strong>
                </div>
                <p class="small mb-3">How closely your average ratings align today.</p>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Swap history similarity</span>
                    <strong>{{ $compatibility['breakdown']['swap_history_similarity'] }}%</strong>
                </div>
                <p class="small mb-3">Similarity in past swap topics and requests.</p>
                <div class="d-flex align-items-center justify-content-between">
                    <span>Activity consistency</span>
                    <strong>{{ $compatibility['breakdown']['activity_consistency'] }}%</strong>
                </div>
                <p class="small mb-3">How steady both users are over the past 30 days.</p>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Tone alignment</span>
                    <strong>{{ $compatibility['breakdown']['tone_alignment'] }}%</strong>
                </div>
                <p class="small mb-3">Communication tone signals from recent chats, when available.</p>
                <div class="d-flex align-items-center justify-content-between">
                    <span>Teaching and learning style</span>
                    <strong>{{ $compatibility['breakdown']['style_compatibility'] }}%</strong>
                </div>
                <p class="small mb-0">Signals from bios and skill intent keywords.</p>
            </div>
        </div>
    </div>
</div>
@endif

@if($user->slug && auth()->check() && auth()->id() !== $user->id)
<section id="swap-request" class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0">Send Swap Request</h2>
        <span class="results-pill">Quick request</span>
    </div>
    <form method="POST" action="{{ route('swap.request') }}" class="swap-form">
        @csrf
        <input type="hidden" name="to_user_id" value="{{ $user->id }}">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="skill_offered" class="form-control" placeholder="Skill you offer" required>
            </div>
            <div class="col-md-6">
                <input type="text" name="skill_requested" class="form-control" placeholder="Skill you want" required>
            </div>
        </div>
        <button class="btn btn-gradient mt-3">Send Swap Request</button>
    </form>
</section>
@endif

<section class="row g-4 mb-4">
    <div class="col-lg-6 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Skills & Focus</h2>
            @php
                $teachSkills = \App\Support\SkillMatchEngine::parseSkills($user->skills_offered);
                $learnSkills = \App\Support\SkillMatchEngine::parseSkills($user->skills_wanted);
            @endphp
            <div class="mb-3">
                <p class="small mb-2"><strong>Teaches</strong></p>
                @if(!empty($teachSkills))
                    <div class="spotlight-skills">
                        @foreach($teachSkills as $skill)
                            <span class="skill-chip">{{ ucfirst($skill) }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="small mb-0">Not listed yet.</p>
                @endif
            </div>
            <div>
                <p class="small mb-2"><strong>Wants to learn</strong></p>
                @if(!empty($learnSkills))
                    <div class="spotlight-skills">
                        @foreach($learnSkills as $skill)
                            <span class="skill-chip">{{ ucfirst($skill) }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="small mb-0">Not listed yet.</p>
                @endif
            </div>
        </article>
    </div>
    <div class="col-lg-6 d-flex">
        <article class="card p-4 w-100">
            <h2 class="h5 mb-3">Portfolio</h2>
            @if(!empty($user->portfolio_links))
                <ul class="feature-list mb-0">
                    @foreach($user->portfolio_links as $link)
                        <li><a href="{{ $link }}" target="_blank" rel="noopener" class="btn btn-link">{{ $link }}</a></li>
                    @endforeach
                </ul>
            @else
                <p class="small mb-0">No portfolio links yet.</p>
            @endif
        </article>
    </div>
</section>

<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0">Skill Endorsements</h2>
        @php
            $topSkill = $endorsements->keys()->first();
            $topCount = $endorsements->first() ?? 0;
        @endphp
        @if($topSkill)
            <span class="results-pill">Top Skill: {{ ucfirst($topSkill) }} ({{ $topCount }})</span>
        @endif
    </div>
    @if($endorsements->isEmpty())
        <p class="small mb-0">No endorsements yet.</p>
    @else
        <div class="row g-3">
            @foreach($endorsements as $skill => $count)
                <div class="col-md-4 d-flex">
                    <div class="card p-3 w-100">
                        <strong>{{ ucfirst($skill) }}</strong>
                        <small>{{ $count }} endorsements</small>
                        @if($count >= 50)
                            <span class="badge-pill mt-2">Elite Skill</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

<section class="card p-4 p-md-5 mb-4">
    <h2 class="h5 mb-3">Connect</h2>
    <div class="d-flex flex-wrap gap-2">
        @if($user->website_url)
            <a href="{{ $user->website_url }}" target="_blank" rel="noopener" class="btn btn-glow btn-sm">Website</a>
        @endif
        @if($user->linkedin_url)
            <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener" class="btn btn-glow btn-sm">LinkedIn</a>
        @endif
        @if($user->instagram_url)
            <a href="{{ $user->instagram_url }}" target="_blank" rel="noopener" class="btn btn-glow btn-sm">Instagram</a>
        @endif
        @if($user->youtube_url)
            <a href="{{ $user->youtube_url }}" target="_blank" rel="noopener" class="btn btn-glow btn-sm">YouTube</a>
        @endif
        @if(!$user->website_url && !$user->linkedin_url && !$user->instagram_url && !$user->youtube_url)
            <p class="small mb-0">No social links added yet.</p>
        @endif
    </div>
</section>

<section class="card p-4 p-md-5 mb-4">
    <h2 class="h5 mb-3">Location Tags (24h)</h2>
    @if($user->hide_tags_until && $user->hide_tags_until->isFuture())
        <p class="small mb-0">This user is currently hiding their location tags.</p>
    @elseif($tags->isEmpty())
        <p class="small mb-0">No active location tags.</p>
    @else
        <div id="profile-map" style="height: 380px; border-radius: 16px;"></div>
    @endif
</section>
@endsection

@push('scripts')
@if(!$tags->isEmpty())
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const map = L.map('profile-map').setView([20.5937, 78.9629], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const tags = @json($tags);
    tags.forEach(tag => {
        const marker = L.marker([tag.lat, tag.lng]).addTo(map);
        const note = tag.note ? `<br>${tag.note}` : '';
        marker.bindPopup(`<strong>${tag.title || 'Location Tag'}</strong>${note}`);
    });
})();
</script>
@endif
@endpush

@if($user->slug && auth()->check() && auth()->id() !== $user->id)
@push('scripts')
<script>
(function () {
    const bar = document.querySelector('.mobile-sticky-actions');
    if (!bar) {
        return;
    }
    const hash = window.location.hash;
    if (hash === '#swap-request') {
        bar.classList.add('is-hidden');
    }
})();
</script>
@endpush

<div class="mobile-sticky-actions d-lg-none">
    <a href="#swap-request" class="btn btn-gradient btn-sm">Request Swap</a>
    @if($canChat)
        <a href="{{ route('chat.page', $user->id) }}" class="btn btn-glow btn-sm">Message</a>
    @else
        <button class="btn btn-outline-light btn-sm" disabled>Message locked</button>
    @endif
</div>
@endif
