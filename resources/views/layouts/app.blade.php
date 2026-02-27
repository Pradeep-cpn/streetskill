<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ trim($__env->yieldContent('title')) ? trim($__env->yieldContent('title')).' | StreetSkill' : 'StreetSkill' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/Browser-logo.png?v=4">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/Browser-logo.png?v=4">
    <link rel="shortcut icon" href="/images/Browser-logo.png?v=4">
    <link rel="apple-touch-icon" href="/images/Browser-logo.png?v=4">
    @yield('meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        media="print"
        onload="this.media='all'"
    >
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>
    <link rel="stylesheet" href="/css/style.css?v={{ @filemtime(public_path('css/style.css')) ?: time() }}">
    <style>
        .card::before,
        .card::after,
        .hero.card::before,
        .hero.card::after,
        [class*="card"]::before,
        [class*="card"]::after {
            content: none !important;
            display: none !important;
            border: 0 !important;
            background: none !important;
            box-shadow: none !important;
        }
    </style>
</head>
<body>

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

<nav class="navbar navbar-expand-lg glass-nav">
    <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
            <a class="navbar-brand d-flex align-items-center gap-2 mb-0" href="{{ url('/') }}">
                <img src="/images/logo.png" alt="StreetSkill" width="160" height="40" loading="eager">
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
                    <ul class="dropdown-menu glass-dropdown">
                        <li><a class="dropdown-item" href="{{ route('challenges.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none"><path d="M12 3l2.2 4.4L19 8l-3.5 3.4.8 4.8L12 14.8 7.7 16.2l.8-4.8L5 8l4.8-.6L12 3z" stroke="currentColor" stroke-width="1.6"/></svg>
                            Challenges
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('roadmaps.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h10M4 18h16" stroke="currentColor" stroke-width="1.6"/></svg>
                            Roadmaps
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('rooms.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none"><path d="M4 7h16v10H4z" stroke="currentColor" stroke-width="1.6"/><path d="M8 7v10M16 7v10" stroke="currentColor" stroke-width="1.6"/></svg>
                            Rooms
                        </a></li>
                    </ul>
                </div>

                <div class="dropdown me-2 mb-2 mb-lg-0">
                    <button class="btn btn-glow dropdown-toggle {{ in_array($currentRoute, $socialRoutes, true) ? 'is-active' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Social
                    </button>
                    <ul class="dropdown-menu glass-dropdown">
                        <li><a class="dropdown-item" href="{{ route('map.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none"><path d="M4 6l6-2 4 2 6-2v14l-6 2-4-2-6 2V6z" stroke="currentColor" stroke-width="1.6"/></svg>
                            Map
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('connections.index') }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none"><path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM17 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM4 22v-2a4 4 0 0 1 4-4h2M14 16h2a4 4 0 0 1 4 4v2" stroke="currentColor" stroke-width="1.6"/></svg>
                            Connections
                        </a></li>
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
                    <div class="dropdown-menu glass-dropdown p-2" style="min-width: 320px;">
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
                <a href="{{ route('profile.edit') }}" class="btn btn-gradient {{ $currentRoute === 'profile.edit' ? 'is-active' : '' }}">Profile</a>
                @if($isPrimaryAdmin ?? false)
                    <a href="{{ route('admin.analytics.index') }}" class="btn btn-glow {{ $currentRoute === 'admin.analytics.index' ? 'is-active' : '' }}">Analytics</a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-glow {{ $currentRoute === 'admin.reports.index' ? 'is-active' : '' }}">Moderation</a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-danger w-100">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-glow">Login</a>
                <a href="{{ route('register') }}" class="btn btn-gradient">Register</a>
            @endauth
        </div>
    </div>
</div>

<div class="container-xl content-area {{ $currentRoute === 'chat.page' ? 'content-chat' : '' }}">
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

<div id="route-loader" class="route-loader" aria-hidden="true">
    <div class="route-loader__spinner" role="status" aria-label="Loading"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

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

    const mobileDrawer = document.getElementById('mobileNavDrawer');
    if (mobileDrawer) {
        const drawerInstance = bootstrap.Offcanvas.getOrCreateInstance(mobileDrawer);
        mobileDrawer.querySelectorAll('a, button[type=\"submit\"], button:not([data-bs-dismiss])').forEach(function (el) {
            el.addEventListener('click', function () {
                drawerInstance.hide();
            });
        });
    }

    const routeLoader = document.getElementById('route-loader');
    const showRouteLoader = function () {
        if (routeLoader) {
            routeLoader.classList.add('is-visible');
        }
    };
    const hideRouteLoader = function () {
        if (routeLoader) {
            routeLoader.classList.remove('is-visible');
        }
    };

    window.addEventListener('load', hideRouteLoader);
    window.addEventListener('pageshow', hideRouteLoader);

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (link.hasAttribute('download') || link.target === '_blank' || link.dataset.noLoader !== undefined) {
            return;
        }
        const href = link.getAttribute('href') || '';
        if (
            href === '' ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:')
        ) {
            return;
        }
        if (link.dataset.bsToggle) {
            return;
        }

        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin) {
            return;
        }
        const current = new URL(window.location.href);
        if (url.pathname === current.pathname && url.search === current.search && url.hash === current.hash) {
            return;
        }

        showRouteLoader();
    });

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (form.target === '_blank' || form.dataset.noLoader !== undefined) {
            return;
        }
        showRouteLoader();
    }, true);
})();
</script>
</body>
</html>
