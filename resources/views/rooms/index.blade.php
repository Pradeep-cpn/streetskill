@extends('layouts.app')

@section('title', 'Project Rooms')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Project Rooms</h2>
    <a href="{{ route('rooms.create') }}" class="btn btn-gradient btn-sm">Create Room</a>
</div>

<div class="row g-4">
    @forelse($rooms as $room)
        <div class="col-lg-6 d-flex">
            <article class="card p-4 w-100">
                <h3 class="h5 mb-2">{{ $room->title }}</h3>
                <p class="small mb-2">{{ $room->description }}</p>
                <span class="badge-pill">{{ $room->members_count }} members</span>
                <a href="{{ route('rooms.show', $room->id) }}" class="btn btn-link btn-sm mt-2">Open Room</a>
            </article>
        </div>
    @empty
        <p>No rooms yet.</p>
    @endforelse
</div>
@endsection
