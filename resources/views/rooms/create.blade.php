@extends('layouts.app')

@section('title', 'Create Room')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="mb-3">Create Project Room</h2>
            <form method="POST" action="{{ route('rooms.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>
                <button class="btn btn-gradient">Create Room</button>
            </form>
        </div>
    </div>
</div>
@endsection
