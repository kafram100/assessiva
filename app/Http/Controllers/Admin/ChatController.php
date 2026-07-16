<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function tickets()
    {
        $tickets = SupportTicket::withCount('messages')
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($ticket) {
                $lastMsg = $ticket->messages->first();
                return [
                    'id' => $ticket->id,
                    'phone' => $ticket->phone,
                    'name' => $ticket->name,
                    'email' => $ticket->email,
                    'status' => $ticket->status,
                    'messages_count' => $ticket->messages_count,
                    'last_message' => $lastMsg ? substr($lastMsg->message, 0, 120) : null,
                    'last_sender' => $lastMsg ? $lastMsg->sender_type : null,
                    'last_activity' => $ticket->updated_at->diffForHumans(),
                    'created_at' => $ticket->created_at->format('M d, Y h:i A'),
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }

    public function messages($id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $msgs = $ticket->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'sender_type' => $m->sender_type,
                'created_at' => $m->created_at->diffForHumans(),
            ]);

        return response()->json([
            'ticket' => [
                'id' => $ticket->id,
                'phone' => $ticket->phone,
                'name' => $ticket->name,
                'email' => $ticket->email,
                'status' => $ticket->status,
            ],
            'messages' => $msgs,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $ticket = SupportTicket::findOrFail($id);

        $msg = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'message' => $validated['message'],
        ]);

        if ($ticket->status === 'open') {
            $ticket->status = 'replied';
        }
        $ticket->touch();
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'created_at' => $msg->created_at->diffForHumans(),
            ],
        ]);
    }
}
