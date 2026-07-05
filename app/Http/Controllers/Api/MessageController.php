<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\AppNotification;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    // GET /messages/{otherUserId} — full conversation thread
    public function conversation(Request $request, $otherUserId)
    {
        $userId = $request->user()->id;

        $messages = Message::where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
            })
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        // Mark messages from the other user as read
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return MessageResource::collection($messages);
    }

    // GET /messages/conversations — list of threads with latest message
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderByDesc('created_at')
            ->get();

        $seen    = [];
        $threads = [];

        foreach ($messages as $msg) {
            $otherId = $msg->sender_id === $userId ? $msg->receiver_id : $msg->sender_id;

            if (in_array($otherId, $seen)) {
                continue;
            }

            $seen[]   = $otherId;
            $other    = $msg->sender_id === $userId ? $msg->receiver : $msg->sender;
            $unread   = Message::where('sender_id', $otherId)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $threads[] = [
                'other_user_id'        => $otherId,
                'other_user_name'      => $other?->name ?? 'User',
                'other_user_photo_url' => $other?->profile_photo_url,
                'last_message'         => Str::limit($msg->content, 80),
                'last_message_at'      => $msg->created_at?->toDateTimeString(),
                'unread_count'         => $unread,
            ];
        }

        return response()->json(['data' => $threads]);
    }

    // POST /messages — send a message
    public function send(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'required|string|max:2000',
            'booking_id'  => 'nullable|exists:bookings,id',
        ]);

        if ((int) $validated['receiver_id'] === $request->user()->id) {
            return response()->json(['message' => 'Cannot message yourself.'], 422);
        }

        $message = Message::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'content'     => $validated['content'],
            'booking_id'  => $validated['booking_id'] ?? null,
        ]);

        // In-app notification for receiver
        AppNotification::create([
            'user_id' => $validated['receiver_id'],
            'type'    => 'new_message',
            'title'   => 'New message from ' . $request->user()->name,
            'body'    => Str::limit($validated['content'], 80),
            'data'    => [
                'sender_id'  => $request->user()->id,
                'sender_name'=> $request->user()->name,
                'booking_id' => $validated['booking_id'] ?? null,
            ],
        ]);

        return (new MessageResource($message->load('sender')))
            ->response()
            ->setStatusCode(201);
    }

    // GET /messages/unread-count
    public function unreadCount(Request $request)
    {
        $count = Message::where('receiver_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }
}
