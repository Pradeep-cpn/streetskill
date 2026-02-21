@extends('layouts.app')

@section('title', 'Roadmaps')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Learning Roadmaps</h2>
    <a href="{{ route('roadmaps.create') }}" class="btn btn-gradient btn-sm">Create Roadmap</a>
</div>

<div class="row g-4">
    @forelse($roadmaps as $roadmap)
        <div class="col-lg-6 d-flex">
            <article class="card p-4 w-100">
                <h3 class="h5 mb-2">{{ $roadmap->title }}</h3>
                <p class="small mb-2">{{ $roadmap->description }}</p>
                <span class="badge-pill">{{ $roadmap->steps_count }} steps</span>
                <a href="{{ route('roadmaps.show', $roadmap->id) }}" class="btn btn-link btn-sm mt-2">View Roadmap</a>
            </article>
        </div>
    @empty
        <p>No roadmaps created yet.</p>
    @endforelse
</div>
@endsection
