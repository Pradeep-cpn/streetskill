@extends('layouts.app')

@section('title', 'Chat')

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <section class="chat-shell card p-3 p-md-4">
            <div class="chat-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h2 class="h5 mb-0">Chat with {{ $user->name }}</h2>
                <a href="{{ route('chat.inbox') }}" class="btn btn-glow btn-sm">Back to Inbox</a>
            </div>

            <div class="chat-box mt-3" id="chat-box"></div>

            <div class="starter-row mt-2 mb-2">
                <button type="button" class="starter-chip" data-message="Hi! I'd like to start our skill exchange this week.">Kickoff Message</button>
                <button type="button" class="starter-chip" data-message="Can we schedule a session based on our shared availability slots?">Schedule Prompt</button>
                <button type="button" class="starter-chip" data-message="I can help you with my offered skill first. What time works for you?">Offer First</button>
            </div>

            <form id="chat-form" class="chat-input mt-2" autocomplete="off">
                <input type="text" id="message" class="form-control" placeholder="Type your message" maxlength="1000" required>
                <button type="submit" class="btn btn-gradient">Send</button>
            </form>

            <form method="POST" action="{{ route('reports.store') }}" class="report-form mt-3">
                @csrf
                <input type="hidden" name="reported_user_id" value="{{ $user->id }}">
                <div class="mb-2">
                    <input type="text" name="details" class="form-control form-control-sm" placeholder="Optional details">
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <select name="reason" class="form-control form-control-sm report-reason" required>
                        <option value="">Report reason</option>
                        <option value="spam">Spam</option>
                        <option value="abuse">Abuse</option>
                        <option value="no_show">No Show</option>
                        <option value="other">Other</option>
                    </select>
                    <button class="btn btn-outline-light btn-sm">Report User</button>
                </div>
            </form>
        </section>
    </div>
    <div class="col-lg-4">
        <div class="chat-side">
            <div class="chat-meta">
                <span>Trust Score</span>
                <h3 class="h5 mb-2">{{ $user->trust_score ?? 0 }}/100</h3>
                <div class="d-flex flex-wrap gap-1">
                    @foreach(collect($user->badges ?? [])->take(4) as $badge)
                        <span class="badge-pill">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>
            <div class="chat-meta">
                <span>Shared Swaps</span>
                <h3 class="h5 mb-1">{{ $sharedSwapCount ?? 0 }}</h3>
                <small>
                    Last accepted:
                    {{ $lastSwapAt ? \Illuminate\Support\Carbon::parse($lastSwapAt)->diffForHumans() : 'Not yet' }}
                </small>
            </div>
            <div class="chat-meta">
                <span>Safety</span>
                <p class="small mb-0">Messages are monitored for abuse and spam. Report issues to keep the community clean.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const chatBox = document.getElementById('chat-box');
    const messageInput = document.getElementById('message');
    const form = document.getElementById('chat-form');

    function escapeHtml(str) {
        return str
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function fetchMessages() {
        fetch('/chat/fetch/{{ $user->id }}')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Unable to load messages.');
                }
                return res.json();
            })
            .then(data => {
                chatBox.innerHTML = '';

                data.forEach(msg => {
                    const align = Number(msg.from_user_id) === Number({{ auth()->id() }}) ? 'sent' : 'received';
                    chatBox.innerHTML += `<div class="message ${align}">${escapeHtml(msg.message)}</div>`;
                });

                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                chatBox.innerHTML = '<div class="small text-danger">Chat unavailable for this user.</div>';
            });
    }

    function sendMessage() {
        const message = messageInput.value.trim();
        if (!message) {
            return;
        }

        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                to_user_id: {{ $user->id }},
                message: message
            })
        }).then((res) => {
            if (!res.ok) {
                throw new Error('Message blocked');
            }
            messageInput.value = '';
            fetchMessages();
        }).catch(() => {
            alert('Unable to send. Chat is available only after accepted swaps.');
        });
    }

    document.querySelectorAll('.starter-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            messageInput.value = chip.getAttribute('data-message') || '';
            messageInput.focus();
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage();
    });

    setInterval(fetchMessages, 2500);
    fetchMessages();
})();
</script>
@endpush
