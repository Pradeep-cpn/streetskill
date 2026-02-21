@extends('layouts.app')

@section('title', 'Swap Request Sent')

@section('content')
<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="h4 mb-2">Swap Request Sent</h1>
            <p class="mb-0">Your request was delivered. Here is the chemistry and next steps.</p>
        </div>
        <span class="results-pill">Confirmation</span>
    </div>
</section>

<section class="row g-4 mb-4">
    <div class="col-lg-7 d-flex">
        <article class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">Request Summary</h2>
                <span class="quality-chip">Q{{ $swap->quality_score ?? 0 }}</span>
            </div>
            <p class="mb-2"><strong>Sent to:</strong> {{ $swap->receiver->name }}</p>
            <p class="mb-2"><strong>You offered:</strong> {{ $swap->skill_offered }}</p>
            <p class="mb-2"><strong>You requested:</strong> {{ $swap->skill_requested }}</p>
            <p class="mb-2"><strong>Status:</strong> {{ ucfirst($swap->status ?? 'pending') }}</p>
            <p class="mb-0"><strong>Expected response:</strong> {{ $etaLabel }}</p>
        </article>
    </div>
    <div class="col-lg-5 d-flex">
        <article class="card p-4 w-100">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="h5 mb-0">AI Compatibility</h2>
                <span class="results-pill">{{ $compatibility['score'] }}%</span>
            </div>
            <p class="small mb-3">Human chemistry score based on skills, history, and style.</p>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span>Top signals</span>
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#compatibilityBreakdownMini">
                    View breakdown
                </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span>Skill match strength</span>
                <strong>{{ $compatibility['breakdown']['skill_match_strength'] }}%</strong>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <span>Teaching and learning style</span>
                <strong>{{ $compatibility['breakdown']['style_compatibility'] }}%</strong>
            </div>
        </article>
    </div>
</section>

<section class="card p-4 p-md-5">
    <div class="mb-3">
        <h2 class="h5 mb-2">What Happens Next</h2>
        <ol class="mb-0">
            <li>We notify them with your offer and learning request.</li>
            <li>They accept or decline. Accepted swaps unlock chat.</li>
            <li>If accepted, coordinate schedule and start the swap.</li>
        </ol>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('public.profile', $swap->receiver->slug) }}" class="btn btn-glow">View Profile</a>
        <a href="{{ route('requests.dashboard') }}" class="btn btn-outline-light">Go to Requests</a>
        <a href="{{ route('marketplace', ['offer' => $swap->skill_offered, 'request' => $swap->skill_requested]) }}" class="btn btn-gradient">Send Another Request</a>
    </div>
</section>

<div class="modal fade" id="compatibilityBreakdownMini" tabindex="-1" aria-labelledby="compatibilityBreakdownMiniLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="compatibilityBreakdownMiniLabel">Compatibility Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Skill match strength</span>
                    <strong>{{ $compatibility['breakdown']['skill_match_strength'] }}%</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Rating alignment</span>
                    <strong>{{ $compatibility['breakdown']['rating_alignment'] }}%</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Swap history similarity</span>
                    <strong>{{ $compatibility['breakdown']['swap_history_similarity'] }}%</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Activity consistency</span>
                    <strong>{{ $compatibility['breakdown']['activity_consistency'] }}%</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span>Tone alignment</span>
                    <strong>{{ $compatibility['breakdown']['tone_alignment'] }}%</strong>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span>Teaching and learning style</span>
                    <strong>{{ $compatibility['breakdown']['style_compatibility'] }}%</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
