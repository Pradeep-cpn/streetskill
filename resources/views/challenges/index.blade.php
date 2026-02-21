@extends('layouts.app')

@section('title', 'Challenges')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Weekly Skill Challenges</h2>
    <span class="results-pill">{{ $challenges->count() }} active</span>
</div>

<div class="row g-4 mb-4">
    @forelse($challenges as $challenge)
        <div class="col-lg-6 d-flex">
            <article class="card p-4 w-100">
                <h3 class="h5 mb-2">{{ $challenge->title }}</h3>
                <p class="small mb-3">{{ $challenge->description }}</p>
                <span class="badge-pill">Week {{ $challenge->week_start->format('M d') }} - {{ $challenge->week_end->format('M d') }}</span>
                <form method="POST" action="{{ route('challenges.submit', $challenge->id) }}" class="mt-3">
                    @csrf
                    <input type="url" name="proof_url" class="form-control mb-2" placeholder="Proof URL (Drive, YouTube, GitHub, etc)" required>
                    <input type="text" name="note" class="form-control mb-2" placeholder="Short note (optional)">
                    <button class="btn btn-gradient btn-sm">Submit Proof</button>
                </form>
            </article>
        </div>
    @empty
        <p>No challenges yet.</p>
    @endforelse
</div>

<section class="card p-4">
    <h3 class="h5 mb-3">Latest Submissions</h3>
    @forelse($submissions as $submission)
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2 flex-wrap gap-2">
            <div>
                <strong>{{ $submission->user->name }}</strong>
                <small class="d-block text-white-50">{{ $submission->note ?: 'No note' }}</small>
                <a href="{{ $submission->proof_url }}" target="_blank" rel="noopener" class="btn btn-link btn-sm p-0">View Proof</a>
            </div>
            <form method="POST" action="{{ route('challenges.vote', $submission->id) }}">
                @csrf
                <button class="btn btn-outline-light btn-sm">Vote ({{ $submission->votes_count }})</button>
            </form>
        </div>
    @empty
        <p class="mb-0">No submissions yet.</p>
    @endforelse
</section>
@endsection
