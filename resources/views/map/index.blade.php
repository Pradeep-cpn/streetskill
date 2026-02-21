@extends('layouts.app')

@section('title', 'Map')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <h2 class="section-title mb-0">Friends Map</h2>
    <span class="results-pill">24h tags</span>
</div>

<div class="row g-4">
    <div class="col-lg-8 d-flex">
        <section class="card p-3 w-100 position-relative">
            <div id="map" style="height: 520px; border-radius: 16px;"></div>
            <div class="map-legend">
                <span class="legend-dot legend-self"></span>
                <span class="legend-text">My tags</span>
                <span class="legend-dot legend-friend"></span>
                <span class="legend-text">Friends</span>
            </div>
        </section>
    </div>
    <div class="col-lg-4 d-flex">
        <section class="card p-4 w-100">
            <h3 class="h5 mb-3">Share Location</h3>
            <p class="small mb-3">Tag a place. Only your accepted friends can see it. Tags expire in 24 hours.</p>
            <form method="POST" action="{{ route('map.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Practice studio">
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <input type="text" name="note" class="form-control" placeholder="e.g. Free after 6 PM">
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration</label>
                    <select name="duration" class="form-control" required>
                        <option value="1">1 hour</option>
                        <option value="6">6 hours</option>
                        <option value="24" selected>24 hours</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Latitude</label>
                    <input id="lat" type="text" name="lat" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Longitude</label>
                    <input id="lng" type="text" name="lng" class="form-control" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="use-center" class="btn btn-outline-light w-100">Use Map Center</button>
                    <button class="btn btn-gradient w-100">Share Tag</button>
                </div>
            </form>
            <hr class="my-4">
            <h4 class="h6 mb-3">Your Active Tags</h4>
            <form method="POST" action="{{ route('map.destroyAll') }}" class="mb-3">
                @csrf
                <button class="btn btn-outline-light btn-sm w-100">Delete All My Tags</button>
            </form>
            @forelse($myTags as $tag)
                <div class="card p-3 mb-2">
                    <strong>{{ $tag->title ?: 'Location Tag' }}</strong>
                    <small class="d-block">{{ $tag->note ?: 'No note' }}</small>
                    <small class="d-block">Expires {{ $tag->expires_at->diffForHumans() }}</small>
                    <form method="POST" action="{{ route('map.destroy', $tag->id) }}" class="mt-2">
                        @csrf
                        <button class="btn btn-outline-light btn-sm w-100">Delete Tag</button>
                    </form>
                </div>
            @empty
                <p class="small mb-0">No active tags.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const map = L.map('map').setView([20.5937, 78.9629], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const tags = @json($tags);
    const currentUserId = {{ auth()->id() }};

    const myIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="width:14px;height:14px;border-radius:50%;background:#00deff;border:2px solid #fff;box-shadow:0 0 8px rgba(0,222,255,0.8)"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
    });

    const friendIcon = L.divIcon({
        className: 'custom-pin',
        html: '<div style="width:14px;height:14px;border-radius:50%;background:#ff7bd5;border:2px solid #fff;box-shadow:0 0 8px rgba(230,54,255,0.6)"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 9]
    });

    tags.forEach(tag => {
        const isMine = Number(tag.user_id) === Number(currentUserId);
        const marker = L.marker([tag.lat, tag.lng], { icon: isMine ? myIcon : friendIcon }).addTo(map);
        const note = tag.note ? `<br>${tag.note}` : '';
        const owner = tag.user ? `<br><small>Shared by ${tag.user.name}</small>` : '';
        marker.bindPopup(`<strong>${tag.title || 'Location Tag'}</strong>${note}${owner}`);
    });

    map.on('click', function (event) {
        document.getElementById('lat').value = event.latlng.lat.toFixed(6);
        document.getElementById('lng').value = event.latlng.lng.toFixed(6);
    });

    const centerBtn = document.getElementById('use-center');
    if (centerBtn) {
        centerBtn.addEventListener('click', function () {
            const center = map.getCenter();
            document.getElementById('lat').value = center.lat.toFixed(6);
            document.getElementById('lng').value = center.lng.toFixed(6);
        });
    }
})();
</script>
@endpush
