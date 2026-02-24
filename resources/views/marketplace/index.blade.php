@extends('layouts.app')

@section('title', 'Marketplace')

@section('meta')
    @php
        $marketUrl = url('/marketplace');
        $marketTitle = 'StreetSkill Marketplace';
        $marketDescription = 'Discover verified mentors and smart skill matches in the StreetSkill marketplace.';
    @endphp
    <link rel="canonical" href="{{ $marketUrl }}">
    <meta name="description" content="{{ $marketDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $marketTitle }}">
    <meta property="og:description" content="{{ $marketDescription }}">
    <meta property="og:url" content="{{ $marketUrl }}">
@endsection

@section('content')
@php
    $prefillOffer = $prefillOffer ?? '';
    $prefillRequest = $prefillRequest ?? '';
@endphp
<section class="card p-4 p-md-5 mb-4">
    <div class="marketplace-hero">
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="section-title mb-0">Skill Marketplace</h2>
                <span class="results-pill">{{ $users->total() }} people found</span>
            </div>
            <p class="mb-0">Discover local creators, compare trust, and send clean swap requests fast.</p>
        </div>
        <div class="marketplace-insights">
            <div class="insight-tile">
                <span class="small text-uppercase text-white-50">Active today</span>
                <h3 class="h5 mb-0">{{ $marketStats['active_swaps_today'] ?? 0 }} swaps</h3>
            </div>
            <div class="insight-tile">
                <span class="small text-uppercase text-white-50">Trusted creators</span>
                <h3 class="h5 mb-0">{{ $marketStats['trusted_creators'] ?? 0 }}</h3>
            </div>
            <div class="insight-tile">
                <span class="small text-uppercase text-white-50">Fast responders</span>
                <h3 class="h5 mb-0">{{ $marketStats['fast_responders'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</section>

<form method="GET" action="{{ route('marketplace') }}" class="card p-3 p-md-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label for="skill" class="form-label">Skill</label>
            <input id="skill" type="text" name="skill" class="form-control" value="{{ $skill }}" placeholder="e.g. Guitar, Excel, UI Design">
        </div>
        <div class="col-md-4">
            <label for="city" class="form-label">City</label>
            <input id="city" type="text" name="city" class="form-control" value="{{ $city }}" placeholder="e.g. New York">
        </div>
        <div class="col-md-4">
            <label for="category" class="form-label">Skill Tag</label>
            <input id="category" type="text" name="category" class="form-control" value="{{ $category }}" placeholder="e.g. Plumbing, Welding">
        </div>
    </div>

    <div class="row g-3 align-items-end mt-1">
        <div class="col-md-3">
            <label for="distance" class="form-label">Distance (km)</label>
            <select id="distance" name="distance" class="form-control">
                @php
                    $distanceValue = (int) $distanceKm;
                @endphp
                <option value="0" {{ $distanceValue === 0 ? 'selected' : '' }}>Any</option>
                <option value="5" {{ $distanceValue === 5 ? 'selected' : '' }}>5 km</option>
                <option value="10" {{ $distanceValue === 10 ? 'selected' : '' }}>10 km</option>
                <option value="25" {{ $distanceValue === 25 ? 'selected' : '' }}>25 km</option>
                <option value="50" {{ $distanceValue === 50 ? 'selected' : '' }}>50 km</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="rating_min" class="form-label">Min Rating</label>
            <select id="rating_min" name="rating_min" class="form-control">
                <option value="0" {{ (float) $ratingMin === 0.0 ? 'selected' : '' }}>Any</option>
                <option value="3" {{ (float) $ratingMin === 3.0 ? 'selected' : '' }}>3.0+</option>
                <option value="3.5" {{ (float) $ratingMin === 3.5 ? 'selected' : '' }}>3.5+</option>
                <option value="4" {{ (float) $ratingMin === 4.0 ? 'selected' : '' }}>4.0+</option>
                <option value="4.5" {{ (float) $ratingMin === 4.5 ? 'selected' : '' }}>4.5+</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="availability" class="form-label">Availability</label>
            <select id="availability" name="availability" class="form-control">
                <option value="any" {{ $availabilityStatus === 'any' || $availabilityStatus === '' ? 'selected' : '' }}>Any</option>
                <option value="available" {{ $availabilityStatus === 'available' ? 'selected' : '' }}>Available</option>
                <option value="busy" {{ $availabilityStatus === 'busy' ? 'selected' : '' }}>Busy</option>
                <option value="away" {{ $availabilityStatus === 'away' ? 'selected' : '' }}>Away</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="sort" class="form-label">Sort</label>
            <select id="sort" name="sort" class="form-control">
                <option value="smart" {{ $sort === 'smart' ? 'selected' : '' }}>Smart</option>
                <option value="nearest" {{ $sort === 'nearest' ? 'selected' : '' }}>Nearest</option>
                <option value="rated" {{ $sort === 'rated' ? 'selected' : '' }}>Highest Rated</option>
                <option value="active" {{ $sort === 'active' ? 'selected' : '' }}>Most Active</option>
            </select>
        </div>
    </div>

    <div class="row g-3 align-items-end mt-1">
        <div class="col-md-3">
            <label for="price_min" class="form-label">Price Min</label>
            <input id="price_min" type="number" name="price_min" class="form-control" value="{{ $priceMin }}" min="0" placeholder="e.g. 100">
        </div>
        <div class="col-md-3">
            <label for="price_max" class="form-label">Price Max</label>
            <input id="price_max" type="number" name="price_max" class="form-control" value="{{ $priceMax }}" min="0" placeholder="e.g. 500">
        </div>
        <div class="col-md-6">
            <div class="d-flex gap-2 align-items-center h-100 marketplace-actions">
                <button class="btn btn-gradient w-100">Search</button>
                <a href="{{ route('marketplace') }}" class="btn btn-outline-light w-100">Clear</a>
            </div>
        </div>
    </div>
    @if(!empty($trendingSkills))
        <div class="mt-3 d-flex flex-wrap gap-2">
            @foreach($trendingSkills as $trend)
                <span class="badge-pill">{{ $trend }}</span>
            @endforeach
        </div>
    @endif
</form>

@if(!empty($distanceError))
    <div class="alert alert-warning app-alert" role="alert">
        {{ $distanceError }} Share your location on the map to enable this filter.
        <a href="{{ route('map.index') }}" class="alert-link">Open map</a>
    </div>
@endif

@if($users->count() === 0)
    <div class="card p-4 text-center">
        <h3 class="mb-2">No matching users found</h3>
        <p class="mb-0">Try another city or broader skill keywords.</p>
    </div>
@else
    <div class="row g-4">
        @foreach($users as $user)
            <div class="col-md-6 col-xl-4 d-flex">
                <article class="card p-4 w-100 d-flex flex-column user-card">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            <div>
                                <h3 class="h5 mb-1">{{ $user->name }}</h3>
                                <p class="small mb-0 text-white-50">{{ $user->city ?: 'City not set' }}</p>
                            </div>
                        </div>
                        <span class="match-chip">{{ $user->match_score }}%</span>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @if($user->email_verified_at)
                            <span class="badge-pill">Email Verified</span>
                        @endif
                        @if(!empty($user->profile?->availability_status))
                            <span class="badge-pill">{{ ucfirst($user->profile->availability_status) }}</span>
                        @endif
                        @if(!is_null($user->distance_km))
                            <span class="badge-pill">{{ $user->distance_km }} km away</span>
                        @endif
                    </div>

                    @if(!is_null($user->compatibility_score))
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="small mb-0"><strong>Compatibility:</strong> {{ $user->compatibility_score }}%</p>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="results-pill">Chemistry</span>
                                @if(!empty($user->response_eta))
                                    <span class="results-pill">ETA {{ $user->response_eta }}</span>
                                @endif
                            </div>
                        </div>
                    @elseif(!empty($user->response_eta))
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="small mb-0"><strong>Response:</strong> {{ $user->response_eta }}</p>
                            <span class="results-pill">ETA</span>
                        </div>
                    @endif

                    <div class="rating-stars mb-3" aria-label="Rating {{ number_format((float) $user->rating, 1) }} out of 5">
                        @for ($i = 1; $i <= 5; $i++)
                            @if($i <= round((float) $user->rating))
                                ★
                            @else
                                ☆
                            @endif
                        @endfor
                        <span class="rating-value">{{ number_format((float) $user->rating, 1) }}</span>
                    </div>

                    @if(!empty($user->profile?->skill_tags))
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @foreach(array_slice($user->profile->skill_tags, 0, 4) as $tag)
                                <span class="badge-pill">{{ ucfirst($tag) }}</span>
                            @endforeach
                        </div>
                    @endif

                    <button class="btn btn-outline-light btn-sm mb-3 js-more-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#user-more-{{ $user->id }}" aria-expanded="false" aria-controls="user-more-{{ $user->id }}">
                        More
                    </button>

                    <div class="collapse user-more-collapse" id="user-more-{{ $user->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="small mb-0"><strong>Trust:</strong> {{ $user->trust_score }}/100</p>
                            <span class="results-pill">Smart {{ $user->smart_score }}</span>
                        </div>

                        <div class="mb-2 d-flex flex-wrap gap-1">
                            @foreach($user->badges as $badge)
                                <span class="badge-pill">{{ $badge }}</span>
                            @endforeach
                        </div>

                        <div class="info-stack mb-3">
                            <p><strong>Teaches:</strong> {{ $user->skills_offered ?: 'Not added yet' }}</p>
                            <p><strong>Wants to learn:</strong> {{ $user->skills_wanted ?: 'Not added yet' }}</p>
                            @if($user->profile?->price_min || $user->profile?->price_max)
                                <p><strong>Price:</strong> Rs {{ $user->profile->price_min ?? 'N/A' }} to Rs {{ $user->profile->price_max ?? 'N/A' }}</p>
                            @endif
                            @if($user->last_active_at)
                                <p><strong>Last active:</strong> {{ $user->last_active_at->diffForHumans() }}</p>
                            @endif
                        </div>

                        <div class="match-box mb-3">
                            @if(!empty($user->match_teaches_you))
                                <p class="small mb-1"><strong>Can teach you:</strong> {{ implode(', ', array_slice($user->match_teaches_you, 0, 2)) }}</p>
                            @endif
                            @if(!empty($user->match_learns_from_you))
                                <p class="small mb-1"><strong>Wants from you:</strong> {{ implode(', ', array_slice($user->match_learns_from_you, 0, 2)) }}</p>
                            @endif
                            @if(!empty($user->availability_overlap))
                                <p class="small mb-0"><strong>Shared slots:</strong> {{ implode(', ', array_slice($user->availability_overlap, 0, 2)) }}</p>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('swap.request') }}" class="mt-auto swap-form">
                        @csrf
                        <input type="hidden" name="to_user_id" value="{{ $user->id }}">

                        <div class="mb-2">
                            <input type="text" name="skill_offered" class="form-control" placeholder="Skill you offer" value="{{ $prefillOffer }}" required>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="skill_requested" class="form-control" placeholder="Skill you want" value="{{ $prefillRequest }}" required>
                        </div>

                        <div class="d-flex gap-2 mb-3 flex-wrap">
                            <button
                                type="button"
                                class="btn btn-outline-light btn-sm js-use-hint"
                                data-offer="{{ $user->swap_hint_offered }}"
                                data-request="{{ $user->swap_hint_requested }}"
                            >Use Match Hint</button>

                            @if(!empty($myOfferedSkills) && !empty($myWantedSkills))
                                <button
                                    type="button"
                                    class="btn btn-outline-light btn-sm js-use-my-default"
                                    data-offer="{{ $myOfferedSkills[0] }}"
                                    data-request="{{ $myWantedSkills[0] }}"
                                >Use My Default</button>
                            @endif
                        </div>

                        <button class="btn btn-gradient w-100">Send Swap Request</button>
                    </form>

                    <form method="POST" action="{{ route('connections.send') }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <button class="btn btn-outline-light btn-sm w-100">Connect</button>
                    </form>

                    @if($user->slug)
                        <a href="{{ route('public.profile', $user->slug) }}" class="btn btn-link btn-sm mt-2">View Profile</a>
                    @endif
                </article>
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    function fillForm(button) {
        const form = button.closest('.swap-form');
        if (!form) {
            return;
        }

        const offered = button.getAttribute('data-offer') || '';
        const requested = button.getAttribute('data-request') || '';

        const offeredInput = form.querySelector('input[name="skill_offered"]');
        const requestedInput = form.querySelector('input[name="skill_requested"]');

        if (offeredInput && offered) {
            offeredInput.value = offered;
        }

        if (requestedInput && requested) {
            requestedInput.value = requested;
        }
    }

    document.querySelectorAll('.js-use-hint, .js-use-my-default').forEach(function (button) {
        button.addEventListener('click', function () {
            fillForm(button);
        });
    });

    const collapses = document.querySelectorAll('.user-more-collapse');
    collapses.forEach(function (collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            collapses.forEach(function (otherEl) {
                if (otherEl !== collapseEl) {
                    const instance = bootstrap.Collapse.getOrCreateInstance(otherEl, { toggle: false });
                    instance.hide();
                }
            });
        });
    });
})();
</script>
@endpush
