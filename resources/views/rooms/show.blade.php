@extends('layouts.app')

@section('title', $room->title)

@section('content')
<div class="card p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="mb-2">{{ $room->title }}</h2>
            <p class="small mb-0">{{ $room->description }}</p>
        </div>
        <form method="POST" action="{{ route('rooms.join', $room->id) }}">
            @csrf
            @if($isMember)
                <button class="btn btn-glow btn-sm" disabled>Member</button>
            @else
                <button class="btn btn-gradient btn-sm">Join Room</button>
            @endif
        </form>
    </div>
</div>

<section class="card p-4">
    <h3 class="h5 mb-3">Room Chat</h3>
    <div class="chat-box mb-3" style="height: 360px;">
        @foreach($messages as $message)
            <div class="message {{ $message->user_id === auth()->id() ? 'sent' : 'received' }}">
                <strong class="d-block small">{{ $message->user->name }}</strong>
                {{ $message->message }}
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('rooms.message', $room->id) }}" class="chat-input">
        @csrf
        <input type="text" name="message" class="form-control" placeholder="Message the room" required>
        <button class="btn btn-gradient">Send</button>
    </form>
</section>
@endsection
