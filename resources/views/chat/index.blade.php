@extends('layouts.app')

@section('title', 'Chat')

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <section class="chat-shell card p-3 p-md-4">
            <div class="chat-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Chat with {{ $user->name }}</h2>
                    <small class="text-muted" id="chat-status">Checking status...</small>
                </div>
                <a href="{{ route('chat.inbox') }}" class="btn btn-glow btn-sm">Back to Inbox</a>
            </div>

            <div class="chat-box mt-3" id="chat-box"></div>
            <div class="mt-2" id="chat-loading">
                <div class="placeholder-glow mb-2">
                    <span class="placeholder col-6"></span>
                </div>
                <div class="placeholder-glow mb-2">
                    <span class="placeholder col-8"></span>
                </div>
                <div class="placeholder-glow">
                    <span class="placeholder col-4"></span>
                </div>
            </div>
            <div class="small text-warning mt-2" id="chat-wakeup" style="display:none;">Waking server... this can take a few seconds.</div>
            <div class="small text-danger mt-2" id="chat-error" style="display:none;">
                Chat unavailable right now.
                <button type="button" class="btn btn-outline-light btn-sm ms-2" id="chat-retry">Retry</button>
            </div>
            <div class="small text-muted mt-2" id="typing-indicator" style="display:none;">{{ $user->name }} is typing...</div>

            <form id="chat-form" class="chat-input mt-2" autocomplete="off">
                <input type="text" id="message" class="form-control" placeholder="Type your message" maxlength="1000">
                <button type="submit" class="btn btn-gradient">Send</button>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const chatBox = document.getElementById('chat-box');
    const messageInput = document.getElementById('message');
    const form = document.getElementById('chat-form');
    const typingIndicator = document.getElementById('typing-indicator');
    const chatStatus = document.getElementById('chat-status');
    const chatLoading = document.getElementById('chat-loading');
    const chatWakeup = document.getElementById('chat-wakeup');
    const chatError = document.getElementById('chat-error');
    const chatRetry = document.getElementById('chat-retry');
    let firstLoad = true;

    function escapeHtml(str) {
        return str
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function fetchMessages() {
        if (firstLoad) {
            chatLoading.style.display = 'block';
        }
        chatError.style.display = 'none';
        chatWakeup.style.display = 'none';

        const wakeupTimer = setTimeout(function () {
            if (firstLoad) {
                chatWakeup.style.display = 'block';
            }
        }, 1500);

        fetch('/chat/fetch/{{ $user->id }}')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Unable to load messages.');
                }
                return res.json();
            })
            .then(data => {
                clearTimeout(wakeupTimer);
                chatLoading.style.display = 'none';
                chatWakeup.style.display = 'none';
                firstLoad = false;
                chatBox.innerHTML = '';
                const messages = data.messages || [];
                const typing = Boolean(data.typing);
                const online = Boolean(data.online);

                typingIndicator.style.display = typing ? 'block' : 'none';
                chatStatus.textContent = online ? 'Online now' : 'Offline';

                messages.forEach(msg => {
                    const align = Number(msg.from_user_id) === Number({{ auth()->id() }}) ? 'sent' : 'received';
                    let body = '';

                    if (msg.image_url) {
                        body += `<img src="${msg.image_url}" alt="Shared image" style="max-width: 220px; border-radius: 10px; margin-bottom: 6px;">`;
                    }
                    if (msg.message) {
                        body += `<div>${escapeHtml(msg.message)}</div>`;
                    }

                    const seenTick = align === 'sent' ? `<span class="small text-muted ms-2">${msg.read_at ? '✓✓' : '✓'}</span>` : '';
                    chatBox.innerHTML += `<div class="message ${align}">${body}${seenTick}</div>`;
                });

                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(() => {
                clearTimeout(wakeupTimer);
                chatLoading.style.display = 'none';
                chatWakeup.style.display = 'none';
                chatError.style.display = 'block';
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

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage();
    });

    let typingTimeout = null;
    messageInput.addEventListener('input', function () {
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }
        fetch('/chat/typing/{{ $user->id }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        typingTimeout = setTimeout(() => {}, 1200);
    });

    setInterval(fetchMessages, 4000);
    fetchMessages();

    if (chatRetry) {
        chatRetry.addEventListener('click', function () {
            fetchMessages();
        });
    }
})();
</script>
@endpush
