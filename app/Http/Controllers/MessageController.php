<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SwapRequest;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class MessageController extends Controller
{
    public function inbox()
    {
        $myId = Auth::id();

        $contactIds = SwapRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($myId) {
                $query->where('from_user_id', $myId)->orWhere('to_user_id', $myId);
            })
            ->get(['from_user_id', 'to_user_id'])
            ->flatMap(function ($row) use ($myId) {
                return [(int) $row->from_user_id === (int) $myId ? (int) $row->to_user_id : (int) $row->from_user_id];
            })
            ->unique()
            ->values();

        $contacts = User::query()->whereIn('id', $contactIds)->get(['id', 'name', 'city']);

        $threads = $contacts->map(function (User $contact) use ($myId) {
            $lastMessage = Message::query()
                ->where(function ($q) use ($myId, $contact) {
                    $q->where('from_user_id', $myId)->where('to_user_id', $contact->id);
                })
                ->orWhere(function ($q) use ($myId, $contact) {
                    $q->where('from_user_id', $contact->id)->where('to_user_id', $myId);
                })
                ->latest()
                ->first();

            $unreadCount = Message::query()
                ->where('from_user_id', $contact->id)
                ->where('to_user_id', $myId)
                ->whereNull('read_at')
                ->count();

            return [
                'contact' => $contact,
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount,
                'updated_at' => $lastMessage?->created_at,
            ];
        })->sortByDesc('updated_at')->values();

        return view('chat.inbox', compact('threads'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000',
        ]);

        $rateKey = 'chat-message:' . Auth::id();
        if (RateLimiter::tooManyAttempts($rateKey, 12)) {
            if (ActivityLog::enabled()) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'type' => 'rate_limited',
                    'meta' => [
                        'scope' => 'chat_message',
                        'to_user_id' => (int) $request->to_user_id,
                    ],
                ]);
            }
            return response()->json(['error' => 'Message limit reached. Please wait a minute.'], 429);
        }


        if (!$this->canChatWith((int) $request->to_user_id)) {
            return response()->json(['error' => 'Chat is allowed only after an accepted swap.'], 403);
        }

        $message = Message::create([
            'from_user_id' => auth()->id(),
            'to_user_id' => $request->to_user_id,
            'message' => trim((string) $request->message),
        ]);

        RateLimiter::hit($rateKey, 60);

        if (ActivityLog::enabled()) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'type' => 'message_sent',
                'meta' => [
                    'message_id' => $message->id,
                    'to_user_id' => (int) $request->to_user_id,
                ],
            ]);
        }

        return response()->json(['status' => 'sent']);
    }

    public function fetch($id)
    {
        if (!$this->canChatWith((int) $id)) {
            return response()->json(['error' => 'Chat is allowed only after an accepted swap.'], 403);
        }

        Message::query()
            ->where('from_user_id', (int) $id)
            ->where('to_user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Message::where(function ($q) use ($id) {
                $q->where('from_user_id', Auth::id())
                    ->where('to_user_id', $id);
            })
            ->orWhere(function ($q) use ($id) {
                $q->where('from_user_id', $id)
                    ->where('to_user_id', Auth::id());
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function canChatWith(int $otherUserId): bool
    {
        return SwapRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($otherUserId) {
                $query->where(function ($q) use ($otherUserId) {
                    $q->where('from_user_id', Auth::id())
                        ->where('to_user_id', $otherUserId);
                })->orWhere(function ($q) use ($otherUserId) {
                    $q->where('from_user_id', $otherUserId)
                        ->where('to_user_id', Auth::id());
                });
            })
            ->exists();
    }
}
