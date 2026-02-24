@extends('layouts.app')

@section('title', 'Swap Requests')

@section('content')
@php
    $acceptedCount = $received->where('status', 'accepted')->count() + $sent->where('status', 'accepted')->count();
    $pendingCountDashboard = $received->where('status', 'pending')->count() + $sent->where('status', 'pending')->count();
    $totalCount = $received->count() + $sent->count();
    $successRate = $totalCount > 0 ? round(($acceptedCount / $totalCount) * 100) : 0;
@endphp

<h2 class="section-title mb-3">Swap Requests</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 insight-card">
            <p class="small mb-1">Total Requests</p>
            <h3 class="h4 mb-0">{{ $totalCount }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 insight-card">
            <p class="small mb-1">Pending</p>
            <h3 class="h4 mb-0">{{ $pendingCountDashboard }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 insight-card">
            <p class="small mb-1">Acceptance Rate</p>
            <h3 class="h4 mb-0">{{ $successRate }}%</h3>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6 d-flex">
        <section class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h5 mb-0">Received</h3>
                <span class="results-pill">{{ $received->count() }}</span>
            </div>

            @if($received->isEmpty())
                <p class="mb-0">No requests received yet.</p>
            @else
                <div class="mb-3">
                    <p class="small mb-1"><strong>What happens next</strong></p>
                    <ol class="small mb-0">
                        <li>Review the offer and requested skill.</li>
                        <li>Accept to unlock chat and start scheduling.</li>
                        <li>Decline if it is not a fit right now.</li>
                    </ol>
                </div>
                <div class="request-list">
                    @foreach($received as $req)
                        <article class="request-item card p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <p class="mb-1"><strong>{{ $req->sender->name }}</strong></p>
                                <span class="quality-chip">Q{{ $req->quality_score ?? 0 }}</span>
                            </div>
                            @if(!empty($compatibilityScores[$req->sender->id]['score']))
                                <p class="small mb-1"><strong>Compatibility:</strong> {{ $compatibilityScores[$req->sender->id]['score'] }}%</p>
                            @endif
                            <p class="small mb-1"><strong>Recommended response:</strong> {{ $selfEtaLabel }}</p>
                            <p class="mb-1"><strong>Offers:</strong> {{ $req->skill_offered }}</p>
                            <p class="mb-2"><strong>Needs:</strong> {{ $req->skill_requested }}</p>
                            <span class="status-badge status-{{ $req->status }}">{{ ucfirst($req->status) }}</span>

                            @if($req->status === 'pending')
                                <div class="d-flex gap-2 flex-wrap mt-3">
                                    <form method="POST" action="{{ route('requests.update', [$req->id, 'accepted']) }}">
                                        @csrf
                                        <button class="btn btn-success btn-sm">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('requests.update', [$req->id, 'rejected']) }}">
                                        @csrf
                                        <button class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </div>
                            @endif

                            @if($req->status === 'accepted')
                                <div class="mt-3">
                                    <a href="{{ route('chat.page', $req->sender->id) }}" class="btn btn-glow btn-sm mb-2">Open Chat</a>
                                    @php
                                        $existingRating = $ratingsGiven[$req->id] ?? null;
                                        $skillRated = $req->skill_offered;
                                    @endphp
                                    @if($existingRating)
                                        <div class="card p-3 mb-2">
                                            <div class="rating-stars" aria-label="Rating {{ (int) $existingRating->rating }} out of 5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if($i <= (int) $existingRating->rating)
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                                <span class="rating-value">{{ number_format((float) $existingRating->rating, 1) }}</span>
                                            </div>
                                            <small class="d-block mb-1">Skill: {{ $existingRating->skill ?? $skillRated }}</small>
                                            <small class="text-muted d-block mb-1">Verified review</small>
                                            @if($existingRating->review)
                                                <p class="small mb-2">{{ $existingRating->review }}</p>
                                            @else
                                                <p class="small mb-2">Rating submitted.</p>
                                            @endif
                                            <button type="button" class="btn btn-outline-light btn-sm js-toggle-rating" data-target="#edit-rating-{{ $req->id }}">Edit Rating</button>
                                        </div>

                                        <form
                                            id="edit-rating-{{ $req->id }}"
                                            method="POST"
                                            action="{{ route('rate.user.update', $existingRating->id) }}"
                                            class="rating-form mb-2 d-none"
                                        >
                                            @csrf
                                            <div class="mb-2">
                                                <select name="rating" class="form-control" required>
                                                    <option value="">Select rating</option>
                                                    @for ($i = 5; $i >= 1; $i--)
                                                        <option value="{{ $i }}" {{ (int) $existingRating->rating === $i ? 'selected' : '' }}>
                                                            {{ $i }} - {{ $i === 5 ? 'Excellent' : ($i === 4 ? 'Good' : ($i === 3 ? 'Average' : ($i === 2 ? 'Needs work' : 'Poor'))) }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <textarea name="review" class="form-control" placeholder="Short review (optional)" rows="2">{{ $existingRating->review ?? '' }}</textarea>
                                            </div>
                                            <button class="btn btn-gradient btn-sm">Update Rating</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('rate.user') }}" class="rating-form mb-2">
                                            @csrf
                                            <input type="hidden" name="to_user_id" value="{{ $req->sender->id }}">
                                            <input type="hidden" name="swap_request_id" value="{{ $req->id }}">

                                            <div class="mb-2">
                                                <small class="text-muted">Rating skill: {{ $skillRated }}</small>
                                            </div>
                                            <div class="mb-2">
                                                <select name="rating" class="form-control" required>
                                                    <option value="">Select rating</option>
                                                    <option value="5">5 - Excellent</option>
                                                    <option value="4">4 - Good</option>
                                                    <option value="3">3 - Average</option>
                                                    <option value="2">2 - Needs work</option>
                                                    <option value="1">1 - Poor</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <textarea name="review" class="form-control" placeholder="Short review (optional)" rows="2"></textarea>
                                            </div>
                                            <button class="btn btn-gradient btn-sm">Submit Rating</button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            <form method="POST" action="{{ route('reports.store') }}" class="report-form mt-2">
                                @csrf
                                <input type="hidden" name="reported_user_id" value="{{ $req->sender->id }}">
                                <input type="hidden" name="swap_request_id" value="{{ $req->id }}">
                                <div class="mb-2">
                                    <input type="text" name="details" class="form-control form-control-sm" placeholder="Optional details">
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select name="reason" class="form-control form-control-sm report-reason" required>
                                        <option value="">Report reason</option>
                                        <option value="spam">Spam</option>
                                        <option value="abuse">Abuse</option>
                                        <option value="no_show">No Show</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <button class="btn btn-outline-light btn-sm">Report</button>
                                </div>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="col-lg-6 d-flex">
        <section class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h5 mb-0">Sent</h3>
                <span class="results-pill">{{ $sent->count() }}</span>
            </div>

            @if($sent->isEmpty())
                <p class="mb-0">No requests sent yet.</p>
            @else
                <div class="mb-3">
                    <p class="small mb-1"><strong>What happens next</strong></p>
                    <ol class="small mb-0">
                        <li>The other user receives a notification.</li>
                        <li>They review and accept or decline.</li>
                        <li>Accepted swaps unlock chat for scheduling.</li>
                    </ol>
                </div>
                <div class="request-list">
                    @foreach($sent as $req)
                        <article class="request-item card p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <p class="mb-1"><strong>{{ $req->receiver->name }}</strong></p>
                                <span class="quality-chip">Q{{ $req->quality_score ?? 0 }}</span>
                            </div>
                            @if(!empty($compatibilityScores[$req->receiver->id]['score']))
                                <p class="small mb-1"><strong>Compatibility:</strong> {{ $compatibilityScores[$req->receiver->id]['score'] }}%</p>
                            @endif
                            <p class="small mb-1"><strong>Expected response:</strong> {{ $responseEtaLabels[$req->receiver->id] ?? 'Within 24 hours' }}</p>
                            <p class="mb-1"><strong>You offered:</strong> {{ $req->skill_offered }}</p>
                            <p class="mb-2"><strong>You requested:</strong> {{ $req->skill_requested }}</p>
                            <span class="status-badge status-{{ $req->status }}">{{ ucfirst($req->status) }}</span>

                            @if($req->status === 'accepted')
                                <div class="mt-3">
                                    <a href="{{ route('chat.page', $req->receiver->id) }}" class="btn btn-glow btn-sm">Open Chat</a>
                                    @php
                                        $existingRating = $ratingsGiven[$req->id] ?? null;
                                        $skillRated = $req->skill_requested;
                                    @endphp
                                    @if($existingRating)
                                        <div class="card p-3 mt-2">
                                            <div class="rating-stars" aria-label="Rating {{ (int) $existingRating->rating }} out of 5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    @if($i <= (int) $existingRating->rating)
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                                <span class="rating-value">{{ number_format((float) $existingRating->rating, 1) }}</span>
                                            </div>
                                            <small class="d-block mb-1">Skill: {{ $existingRating->skill ?? $skillRated }}</small>
                                            <small class="text-muted d-block mb-1">Verified review</small>
                                            @if($existingRating->review)
                                                <p class="small mb-2">{{ $existingRating->review }}</p>
                                            @else
                                                <p class="small mb-2">Rating submitted.</p>
                                            @endif
                                            <button type="button" class="btn btn-outline-light btn-sm js-toggle-rating" data-target="#edit-rating-sent-{{ $req->id }}">Edit Rating</button>
                                        </div>

                                        <form
                                            id="edit-rating-sent-{{ $req->id }}"
                                            method="POST"
                                            action="{{ route('rate.user.update', $existingRating->id) }}"
                                            class="rating-form mb-2 d-none"
                                        >
                                            @csrf
                                            <div class="mb-2">
                                                <select name="rating" class="form-control" required>
                                                    <option value="">Select rating</option>
                                                    @for ($i = 5; $i >= 1; $i--)
                                                        <option value="{{ $i }}" {{ (int) $existingRating->rating === $i ? 'selected' : '' }}>
                                                            {{ $i }} - {{ $i === 5 ? 'Excellent' : ($i === 4 ? 'Good' : ($i === 3 ? 'Average' : ($i === 2 ? 'Needs work' : 'Poor'))) }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <textarea name="review" class="form-control" placeholder="Short review (optional)" rows="2">{{ $existingRating->review ?? '' }}</textarea>
                                            </div>
                                            <button class="btn btn-gradient btn-sm">Update Rating</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('rate.user') }}" class="rating-form mt-2">
                                            @csrf
                                            <input type="hidden" name="to_user_id" value="{{ $req->receiver->id }}">
                                            <input type="hidden" name="swap_request_id" value="{{ $req->id }}">

                                            <div class="mb-2">
                                                <small class="text-muted">Rating skill: {{ $skillRated }}</small>
                                            </div>
                                            <div class="mb-2">
                                                <select name="rating" class="form-control" required>
                                                    <option value="">Select rating</option>
                                                    <option value="5">5 - Excellent</option>
                                                    <option value="4">4 - Good</option>
                                                    <option value="3">3 - Average</option>
                                                    <option value="2">2 - Needs work</option>
                                                    <option value="1">1 - Poor</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <textarea name="review" class="form-control" placeholder="Short review (optional)" rows="2"></textarea>
                                            </div>
                                            <button class="btn btn-gradient btn-sm">Submit Rating</button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            <form method="POST" action="{{ route('reports.store') }}" class="report-form mt-2">
                                @csrf
                                <input type="hidden" name="reported_user_id" value="{{ $req->receiver->id }}">
                                <input type="hidden" name="swap_request_id" value="{{ $req->id }}">
                                <div class="mb-2">
                                    <input type="text" name="details" class="form-control form-control-sm" placeholder="Optional details">
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <select name="reason" class="form-control form-control-sm report-reason" required>
                                        <option value="">Report reason</option>
                                        <option value="spam">Spam</option>
                                        <option value="abuse">Abuse</option>
                                        <option value="no_show">No Show</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <button class="btn btn-outline-light btn-sm">Report</button>
                                </div>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-toggle-rating').forEach(function (button) {
        button.addEventListener('click', function () {
            const target = button.getAttribute('data-target');
            if (!target) {
                return;
            }
            const form = document.querySelector(target);
            if (!form) {
                return;
            }
            form.classList.toggle('d-none');
        });
    });
})();
</script>
@endpush
