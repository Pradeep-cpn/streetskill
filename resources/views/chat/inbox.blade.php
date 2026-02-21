@extends('layouts.app')

@section('title', 'Inbox')

@section('content')
@php
    $unreadTotal = $threads->sum('unread_count');
@endphp

<section class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h2 class="section-title mb-0">Inbox</h2>
        <span class="results-pill">{{ $threads->count() }} conversations</span>
    </div>
    <div class="d-flex flex-wrap gap-3">
        <span class="badge-pill">Unread: {{ $unreadTotal }}</span>
        <span class="badge-pill">Active today: {{ $threads->filter(fn ($thread) => $thread['last_message'])->count() }}</span>
        <span class="badge-pill">Swap-only chat enabled</span>
    </div>
</section>

@if($threads->isEmpty())
    <div class="card p-4 text-center">
        <h3 class="mb-2">No conversations yet</h3>
        <p class="mb-0">Accept a swap request to unlock chat.</p>
    </div>
@else
    <div class="row g-3">
        @foreach($threads as $thread)
            <div class="col-12">
                <a href="{{ route('chat.page', $thread['contact']->id) }}" class="card p-3 d-block inbox-item">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $thread['contact']->name }}</h3>
                            <p class="small mb-1">{{ $thread['contact']->city ?: 'City not set' }}</p>
                            <p class="small mb-0 text-truncate">
                                {{ $thread['last_message']?->message ?: 'No messages yet' }}
                            </p>
                        </div>
                        <div class="text-end">
                            @if($thread['last_message'])
                                <small class="d-block mb-2">{{ $thread['last_message']->created_at->diffForHumans() }}</small>
                            @endif
                            @if($thread['unread_count'] > 0)
                                <span class="badge bg-info">{{ $thread['unread_count'] }} new</span>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif
@endsection
