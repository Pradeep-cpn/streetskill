@extends('layouts.app')

@section('title', $roadmap->title)

@section('content')
<div class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="mb-2">{{ $roadmap->title }}</h2>
            <p class="small mb-0">{{ $roadmap->description }}</p>
        </div>
        <form method="POST" action="{{ route('roadmaps.follow', $roadmap->id) }}">
            @csrf
            <button class="btn btn-glow btn-sm">{{ $isFollowing ? 'Following' : 'Follow Roadmap' }}</button>
        </form>
    </div>
</div>

<section class="card p-4">
    <h3 class="h5 mb-3">Steps</h3>
    <div class="path-steps">
        @foreach($roadmap->steps as $step)
            @php $done = in_array($step->id, $progressIds, true); @endphp
            <div class="path-step {{ $done ? 'done' : '' }}">
                <div>
                    <h4 class="h6 mb-1">{{ $step->sort_order }}. {{ $step->title }}</h4>
                </div>
                <form method="POST" action="{{ route('roadmaps.steps.toggle', $step->id) }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">{{ $done ? 'Mark Incomplete' : 'Mark Complete' }}</button>
                </form>
            </div>
        @endforeach
    </div>
</section>
@endsection
