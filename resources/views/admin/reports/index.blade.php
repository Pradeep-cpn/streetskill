@extends('layouts.app')

@section('title', 'Report Moderation')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Report Moderation</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.index', ['status' => 'open']) }}" class="btn btn-sm {{ $status === 'open' ? 'btn-gradient' : 'btn-glow' }}">Open</a>
        <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" class="btn btn-sm {{ $status === 'resolved' ? 'btn-gradient' : 'btn-glow' }}">Resolved</a>
        <a href="{{ route('admin.reports.index', ['status' => 'dismissed']) }}" class="btn btn-sm {{ $status === 'dismissed' ? 'btn-gradient' : 'btn-glow' }}">Dismissed</a>
    </div>
</div>

@if($reports->isEmpty())
    <div class="card p-4 text-center">
        <h3 class="mb-2">No reports in this status</h3>
    </div>
@else
    <div class="card p-3 p-md-4">
        <div class="table-responsive">
            <table class="table table-dark table-borderless align-middle mb-0 moderation-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Reporter</th>
                    <th>Reported</th>
                    <th>Reason</th>
                    <th>Details</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td>#{{ $report->id }}</td>
                        <td>{{ $report->reporter?->name }}</td>
                        <td>{{ $report->reported?->name }}</td>
                        <td>{{ strtoupper(str_replace('_', ' ', $report->reason)) }}</td>
                        <td>{{ $report->details ?: '-' }}</td>
                        <td>
                            <span class="status-badge status-{{ $report->status }}">{{ ucfirst($report->status) }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="d-flex gap-2">
                                @csrf
                                <select name="status" class="form-control form-control-sm" style="min-width: 120px;">
                                    <option value="open" {{ $report->status === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="dismissed" {{ $report->status === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                                </select>
                                <button class="btn btn-gradient btn-sm">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $reports->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
@endsection
