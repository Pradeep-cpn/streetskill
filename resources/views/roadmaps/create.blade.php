@extends('layouts.app')

@section('title', 'Create Roadmap')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4">
            <h2 class="mb-3">Create Roadmap</h2>
            <form method="POST" action="{{ route('roadmaps.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Steps (one per line)</label>
                    <textarea name="steps" class="form-control" rows="6" required></textarea>
                </div>
                <button class="btn btn-gradient">Create Roadmap</button>
            </form>
        </div>
    </div>
</div>
@endsection
