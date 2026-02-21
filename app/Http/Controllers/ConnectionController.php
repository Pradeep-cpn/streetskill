<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnectionController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $incoming = Connection::query()
            ->with('requester:id,name,city,slug')
            ->where('addressee_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $outgoing = Connection::query()
            ->with('addressee:id,name,city,slug')
            ->where('requester_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $acceptedIds = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userId) {
                $query->where('requester_id', $userId)
                    ->orWhere('addressee_id', $userId);
            })
            ->get()
            ->flatMap(function (Connection $connection) use ($userId) {
                return [(int) $connection->requester_id === (int) $userId ? (int) $connection->addressee_id : (int) $connection->requester_id];
            })
            ->unique()
            ->values();

        $connections = User::query()
            ->whereIn('id', $acceptedIds)
            ->get(['id', 'name', 'city', 'slug']);

        return view('connections.index', compact('incoming', 'outgoing', 'connections'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $fromId = Auth::id();
        $toId = (int) $request->user_id;

        if ($fromId === $toId) {
            return back()->with('error', 'You cannot connect with yourself.');
        }

        $exists = Connection::query()
            ->where(function ($query) use ($fromId, $toId) {
                $query->where('requester_id', $fromId)
                    ->where('addressee_id', $toId);
            })
            ->orWhere(function ($query) use ($fromId, $toId) {
                $query->where('requester_id', $toId)
                    ->where('addressee_id', $fromId);
            })
            ->exists();

        if ($exists) {
            return back()->with('error', 'Connection request already exists.');
        }

        Connection::create([
            'requester_id' => $fromId,
            'addressee_id' => $toId,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Connection request sent.');
    }

    public function accept(Connection $connection)
    {
        $userId = Auth::id();
        if ((int) $connection->addressee_id !== (int) $userId) {
            abort(403);
        }

        $connection->update(['status' => 'accepted']);

        return back()->with('success', 'Connection accepted.');
    }

    public function reject(Connection $connection)
    {
        $userId = Auth::id();
        if ((int) $connection->addressee_id !== (int) $userId) {
            abort(403);
        }

        $connection->update(['status' => 'rejected']);

        return back()->with('success', 'Connection rejected.');
    }
}
