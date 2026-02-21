@extends('layouts.app')

@section('title', 'Connections')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Connections</h2>
    <span class="results-pill">{{ $connections->count() }} friends</span>
</div>

<div class="row g-4">
    <div class="col-lg-4 d-flex">
        <section class="card p-4 w-100">
            <h3 class="h5 mb-3">Incoming Requests</h3>
            @forelse($incoming as $request)
                <div class="card p-3 mb-3">
                    <strong>{{ $request->requester->name }}</strong>
                    <small>{{ $request->requester->city ?: 'City not set' }}</small>
                    <div class="d-flex gap-2 mt-2">
                        <form method="POST" action="{{ route('connections.accept', $request->id) }}">
                            @csrf
                            <button class="btn btn-gradient btn-sm">Accept</button>
                        </form>
                        <form method="POST" action="{{ route('connections.reject', $request->id) }}">
                            @csrf
                            <button class="btn btn-outline-light btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="small mb-0">No incoming requests.</p>
            @endforelse
        </section>
    </div>

    <div class="col-lg-4 d-flex">
        <section class="card p-4 w-100">
            <h3 class="h5 mb-3">Outgoing Requests</h3>
            @forelse($outgoing as $request)
                <div class="card p-3 mb-3">
                    <strong>{{ $request->addressee->name }}</strong>
                    <small>{{ $request->addressee->city ?: 'City not set' }}</small>
                    <span class="badge-pill mt-2">Pending</span>
                </div>
            @empty
                <p class="small mb-0">No outgoing requests.</p>
            @endforelse
        </section>
    </div>

    <div class="col-lg-4 d-flex">
        <section class="card p-4 w-100">
            <h3 class="h5 mb-3">Your Friends</h3>
            @forelse($connections as $friend)
                <div class="card p-3 mb-3">
                    <strong>{{ $friend->name }}</strong>
                    <small>{{ $friend->city ?: 'City not set' }}</small>
                    @if($friend->slug)
                        <a href="{{ route('public.profile', $friend->slug) }}" class="btn btn-link btn-sm">View Profile</a>
                    @endif
                </div>
            @empty
                <p class="small mb-0">No connections yet.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
