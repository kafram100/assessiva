<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    private function resolveUser(Request $request)
    {
        return $request->user() ?? auth('web')->user();
    }

    public function start(Request $request)
    {
        $user = $this->resolveUser($request);

        if ($user) {
            $existing = SupportTicket::where('user_id', $user->id)
                ->whereIn('status', ['open', 'replied'])
                ->latest()
                ->first();

            if ($existing) {
                if (!$existing->token) {
                    $existing->token = Str::random(64);
                    $existing->save();
                }
                return response()->json([
                    'success' => true,
                    'token' => $existing->token,
                    'ticket' => [
                        'id' => $existing->id,
                        'phone' => $existing->phone,
                        'status' => $existing->status,
                    ],
                ]);
            }

            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'name' => $user->full_name ?? $user->username ?? $user->email,
                'email' => $user->email,
                'status' => 'open',
                'token' => Str::random(64),
            ]);
        } else {
            $validated = $request->validate([
                'phone' => 'required|string|max:20',
            ]);

            $phone = $validated['phone'];

            $existing = SupportTicket::where('phone', $phone)
                ->whereIn('status', ['open', 'replied'])
                ->latest()
                ->first();

            if ($existing) {
                if (!$existing->token) {
                    $existing->token = Str::random(64);
                    $existing->save();
                }
                return response()->json([
                    'success' => true,
                    'token' => $existing->token,
                    'ticket' => [
                        'id' => $existing->id,
                        'phone' => $existing->phone,
                        'status' => $existing->status,
                    ],
                ]);
            }

            $ticket = SupportTicket::create([
                'phone' => $phone,
                'status' => 'open',
                'token' => Str::random(64),
            ]);
        }

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'bot',
            'message' => $user
                ? "Hi there, {$ticket->name}! 👋 Welcome to AirLinks.\n\nI'm the AirLinks bot assistant. I can help you with:\n• 📊 Data prices & packages\n• 📦 Order tracking\n• 📖 How to buy data\n• 💳 Payment info\n\nJust type your question below!"
                : "Hi there! 👋 Welcome to AirLinks.\n\nI'm the AirLinks bot assistant. I can help you with:\n• 📊 Data prices & packages\n• 📦 Order tracking\n• 📖 How to buy data\n• 💳 Payment info\n\nJust type your question below!",
        ]);

        return response()->json([
            'success' => true,
            'token' => $ticket->token,
            'ticket' => [
                'id' => $ticket->id,
                'phone' => $ticket->phone,
                'status' => $ticket->status,
            ],
        ]);
    }

    public function sendMessage(Request $request)
    {
        $ticket = $this->resolveTicket($request);
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'No active chat'], 400);
        }

        if (!$this->canAccess($request, $ticket)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $msg = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'message' => $validated['message'],
        ]);

        $ticket->touch();

        $bot = new ChatbotService;
        $botReply = $bot->respond($validated['message']);

        $botMsg = SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'bot',
            'message' => $botReply,
        ]);

        $ticket->touch();

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_type' => $msg->sender_type,
                'created_at' => $msg->created_at->diffForHumans(),
            ],
            'bot_message' => [
                'id' => $botMsg->id,
                'message' => $botMsg->message,
                'sender_type' => $botMsg->sender_type,
                'created_at' => $botMsg->created_at->diffForHumans(),
            ],
        ]);
    }

    public function getMessages(Request $request)
    {
        $ticket = $this->resolveTicket($request);
        if (!$ticket) {
            return response()->json(['success' => false, 'messages' => []], 400);
        }

        if (!$this->canAccess($request, $ticket)) {
            return response()->json(['success' => false, 'messages' => []], 403);
        }

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
            'success' => true,
            'ticket' => [
                'id' => $ticket->id,
                'phone' => $ticket->phone,
                'status' => $ticket->status,
            ],
            'messages' => $msgs,
        ]);
    }

    private function resolveTicket(Request $request): ?SupportTicket
    {
        $token = $request->input('token', $request->query('token'));
        if (!$token) {
            return null;
        }
        return SupportTicket::where('token', $token)->first();
    }

    private function canAccess(Request $request, SupportTicket $ticket): bool
    {
        $user = $this->resolveUser($request);

        if ($user) {
            return $ticket->user_id && $ticket->user_id == $user->id;
        }

        if (!$ticket->user_id) {
            $phone = $request->input('phone', $request->query('phone'));
            if ($phone) {
                return $phone === $ticket->phone;
            }
            return true;
        }

        return false;
    }
}
