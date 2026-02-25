<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' | StreetSkill' : 'StreetSkill' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta')

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ secure_asset('css/style.css') }}">
</head>
<body>
<div class="animated-bg"></div>

@php
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
    $exploreRoutes = ['challenges.index', 'roadmaps.index', 'rooms.index'];
    $socialRoutes = ['map.index', 'connections.index'];
    $routeLabels = [
        'home' => ['Home'],
        'landing' => ['Home'],
        'marketplace' => ['Marketplace'],
        'feed.index' => ['Feed'],
        'requests.dashboard' => ['Requests'],
        'chat.inbox' => ['Inbox'],
        'chat.page' => ['Inbox', 'Chat'],
        'profile.edit' => ['Profile', 'Edit'],
        'challenges.index' => ['Challenges'],
        'roadmaps.index' => ['Roadmaps'],
        'rooms.index' => ['Rooms'],
        'map.index' => ['Map'],
        'connections.index' => ['Connections'],
        'admin.analytics.index' => ['Admin', 'Analytics'],
        'admin.reports.index' => ['Admin', 'Moderation'],
    ];
    $crumbs = $routeLabels[$currentRoute] ?? ['StreetSkill'];
@endphp

<nav class="navbar navbar-expand-lg navbar-dark glass-nav">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="{{ url('/') }}">
                <img src="/images/logo.png" alt="StreetSkill" height="40" loading="eager">
            </a>
            @auth
                <form method="GET" action="{{ route('marketplace') }}" class="nav-search mb-2 mb-lg-0">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search skill, city">
                </form>
            @endauth
        </div>

        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end d-none d-lg-flex" id="navMenu">
            @auth
                <a href="{{ route('marketplace') }}" class="btn btn-glow me-2 mb-2 mb-lg-0 {{ $currentRoute === 'marketplace' ? 'is-active' : '' }}">Marketplace</a>
                <a href="{{ route('feed.index') }}" class="btn btn-glow me-2 mb-2 mb-lg-0 {{ $currentRoute === 'feed.index' ? 'is-active' : '' }}">Feed</a>

                <div class="dropdown me-2 mb-2 mb-lg-0">
                    <button class="btn btn-glow dropdown-toggle {{ in_array($currentRoute, $exploreRoutes, true) ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Explore
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-dropdown">
                        <li><a class="dropdown-item" href="{{ route('challenges.index') }}">Challenges</a></li>
                        <li><a class="dropdown-item" href="{{ route('roadmaps.index') }}">Roadmaps</a></li>
                        <li><a class="dropdown-item" href="{{ route('rooms.index') }}">Rooms</a></li>
                    </ul>
                </div>

                <div class="dropdown me-2 mb-2 mb-lg-0">
                    <button class="btn btn-glow dropdown-toggle {{ in_array($currentRoute, $socialRoutes, true) ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Social
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark glass-dropdown">
                        <li><a class="dropdown-item" href="{{ route('map.index') }}">Map</a></li>
                        <li><a class="dropdown-item" href="{{ route('connections.index') }}">Connections</a></li>
                    </ul>
                </div>
                <a href="{{ route('requests.dashboard') }}" class="btn btn-glow position-relative me-2 mb-2 mb-lg-0 {{ $currentRoute === 'requests.dashboard' ? 'is-active' : '' }}">
                    Requests
                    @if(($pendingCount ?? 0) > 0)
                        <span class="badge bg-danger notification-badge">{{ $pendingCount }}</span>
                    @endif
                </a>
                <div class="dropdown me-2 mb-2 mb-lg-0">
                    <button class="btn btn-glow dropdown-toggle position-relative {{ $currentRoute === 'notifications' ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Notifications
                        @if(($notificationCount ?? 0) > 0)
                            <span class="badge bg-warning text-dark notification-badge">{{ $notificationCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-dark glass-dropdown p-2" style="min-width: 320px;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="small">Recent</strong>
                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf
                                <button class="btn btn-outline-light btn-sm">Mark all read</button>
                            </form>
                        </div>
                        @if(($notifications ?? collect())->isEmpty())
                            <div class="small text-muted px-2 py-2">No notifications yet.</div>
                        @else
                            <div class="notification-list">
                                @foreach($notifications as $notification)
                                    @php
                                        $data = $notification->data ?? [];
                                        $label = match ($notification->type) {
                                            'swap_request_received' => ($data['from_name'] ?? 'Someone') . ' sent a swap request.',
                                            'swap_request_accepted' => ($data['by_name'] ?? 'Someone') . ' accepted your swap request.',
                                            'message_received' => ($data['from_name'] ?? 'Someone') . ' sent a message.',
                                            'rating_received' => ($data['from_name'] ?? 'Someone') . ' rated you ' . ($data['rating'] ?? '?') . '★.',
                                            default => 'New activity in StreetSkill.',
                                        };
                                        $route = match ($notification->type) {
                                            'message_received' => isset($data['from_user_id']) ? route('chat.page', $data['from_user_id']) : route('chat.inbox'),
                                            'swap_request_received', 'swap_request_accepted' => route('requests.dashboard'),
                                            'rating_received' => route('requests.dashboard'),
                                            default => route('home'),
                                        };
                                    @endphp
                                    <a href="{{ $route }}" class="dropdown-item d-flex flex-column gap-1 {{ $notification->read_at ? '' : 'fw-semibold' }}">
                                        <span class="small">{{ $label }}</span>
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <a href="{{ route('chat.inbox') }}" class="btn btn-glow position-relative me-2 mb-2 mb-lg-0 {{ $currentRoute === 'chat.inbox' ? 'is-active' : '' }}">
                    Inbox
                    @if(($unreadMessageCount ?? 0) > 0)
                        <span class="badge bg-info notification-badge">{{ $unreadMessageCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.edit') }}" class="btn btn-gradient me-2 mb-2 mb-lg-0 {{ $currentRoute === 'profile.edit' ? 'is-active' : '' }}">Profile</a>

                @if($isPrimaryAdmin ?? false)
                    <a href="{{ route('admin.analytics.index') }}" class="btn btn-glow me-2 mb-2 mb-lg-0 {{ $currentRoute === 'admin.analytics.index' ? 'is-active' : '' }}">Analytics</a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-glow position-relative me-2 mb-2 mb-lg-0 {{ $currentRoute === 'admin.reports.index' ? 'is-active' : '' }}">Moderation</a>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="d-inline mb-2 mb-lg-0">
                    @csrf
                    <button class="btn btn-danger btn-sm">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-glow me-2 mb-2 mb-lg-0">Login</a>
                <a href="{{ route('register') }}" class="btn btn-gradient mb-2 mb-lg-0">Register</a>
            @endauth
        </div>
    </div>
</nav>

        <div class="offcanvas offcanvas-end mobile-drawer d-lg-none" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileNavDrawerLabel">StreetSkill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="d-grid gap-2">
                    @auth
                        <a href="{{ route('marketplace') }}" class="btn btn-glow {{ $currentRoute === 'marketplace' ? 'is-active' : '' }}">Marketplace</a>
                        <a href="{{ route('feed.index') }}" class="btn btn-glow {{ $currentRoute === 'feed.index' ? 'is-active' : '' }}">Feed</a>
                        <a href="{{ route('challenges.index') }}" class="btn btn-glow {{ $currentRoute === 'challenges.index' ? 'is-active' : '' }}">Challenges</a>
                        <a href="{{ route('roadmaps.index') }}" class="btn btn-glow {{ $currentRoute === 'roadmaps.index' ? 'is-active' : '' }}">Roadmaps</a>
                        <a href="{{ route('rooms.index') }}" class="btn btn-glow {{ $currentRoute === 'rooms.index' ? 'is-active' : '' }}">Rooms</a>
                        <a href="{{ route('map.index') }}" class="btn btn-glow {{ $currentRoute === 'map.index' ? 'is-active' : '' }}">Map</a>
                        <a href="{{ route('connections.index') }}" class="btn btn-glow {{ $currentRoute === 'connections.index' ? 'is-active' : '' }}">Connections</a>
                        <a href="{{ route('requests.dashboard') }}" class="btn btn-glow {{ $currentRoute === 'requests.dashboard' ? 'is-active' : '' }}">Requests</a>
                        <div class="btn btn-glow position-relative">
                            Notifications
                            @if(($notificationCount ?? 0) > 0)
                                <span class="badge bg-warning text-dark notification-badge">{{ $notificationCount }}</span>
                            @endif
                        </div>
                        <a href="{{ route('chat.inbox') }}" class="btn btn-glow {{ $currentRoute === 'chat.inbox' ? 'is-active' : '' }}">Inbox</a>
                        <a href="{{ route('profile.edit') }}" class="btn btn-glow {{ $currentRoute === 'profile.edit' ? 'is-active' : '' }}">Profile</a>
                        @if($isPrimaryAdmin ?? false)
                            <a href="{{ route('admin.analytics.index') }}" class="btn btn-glow {{ $currentRoute === 'admin.analytics.index' ? 'is-active' : '' }}">Analytics</a>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-glow {{ $currentRoute === 'admin.reports.index' ? 'is-active' : '' }}">Moderation</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST" class="d-grid">
                            @csrf
                            <button class="btn btn-danger btn-sm">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-glow">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-gradient">Register</a>
                    @endauth
                </div>
            </div>
        </div>

<div class="container content-area {{ $currentRoute === 'chat.page' ? 'content-chat' : '' }}">
    @if(session('success'))
        <div class="alert alert-success app-alert" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger app-alert" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger app-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
<script>
(function () {
    @auth
    const sessionCheck = () => {
        fetch('{{ route('session.ping') }}', {
            headers: { 'Accept': 'application/json' }
        }).then((res) => {
            if (res.status === 401) {
                window.location = '/';
            }
        }).catch(() => {});
    };
    setInterval(sessionCheck, 10000);
    @endauth

    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = btn.getAttribute('data-target');
            if (!target) {
                return;
            }
            const input = document.querySelector(target);
            if (!input) {
                return;
            }
            const isVisible = input.getAttribute('type') === 'text';
            input.setAttribute('type', isVisible ? 'password' : 'text');
            btn.classList.toggle('is-visible', !isVisible);
        });
    });
})();
</script>
</body>
</html>
