@extends('layouts.app')

@section('title', 'Activity Feed')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Professional Activity Feed</h2>
    <span class="results-pill">Friends + You</span>
</div>

<div class="card p-4">
    @forelse($activity as $log)
        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary-subtle py-2 flex-wrap gap-2">
            <div>
                <strong>{{ $log->user->name }}</strong>
                <span class="small text-white-50">· {{ $log->created_at->diffForHumans() }}</span>
                <div class="small mt-1">{{ ucfirst(str_replace('_', ' ', $log->type)) }}</div>
            </div>
            @if($log->user->slug)
                <a href="{{ route('public.profile', $log->user->slug) }}" class="btn btn-link btn-sm">View</a>
            @endif
        </div>
    @empty
        <p class="mb-0">No activity yet.</p>
    @endforelse
</div>
@endsection
