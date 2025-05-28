<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Telegram;
use App\Models\User;

class TelegramController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN');
    }

    // Send outgoing message to Telegram and save to DB
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        // Send message to Telegram API
        $response = Http::post("{$this->baseUrl}/sendMessage", [
            'chat_id' => $validated['chat_id'],
            'text' => $validated['message'],
        ]);

        // Save outgoing message to DB
        Telegram::create([
            'chat_id' => $validated['chat_id'],
            'direction' => 'outgoing',
            'message' => $validated['message'],
            'file_url' => null,
            'type' => 'text',
            'name' => 'You',
            'photo_url' => null,
            'is_read' => true,
        ]);

        return response()->json($response->json());
    }

    // Ask OpenRouter API for AI response
    private function askOpenRouter(array $messages): string
    {
        $apiKey = env('OPENROUTER_API_KEY');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'openai/gpt-4.1',  // or 'openai/gpt-4o' if preferred
            'messages' => $messages,
            'max_tokens' => 800,
        ]);

        if ($response->successful()) {
            return $response['choices'][0]['message']['content'] ?? "🤖 Sorry, I couldn't generate a response.";
        }

        return "⚠️ API error: " . $response->body();
    }


    // Receive incoming message from Telegram webhook, save, get AI reply, respond, save outgoing
    public function webhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['message'])) {
            $msg = $data['message'];
            $chatId = $msg['chat']['id'];
            $firstName = $msg['chat']['first_name'] ?? '';
            $lastName = $msg['chat']['last_name'] ?? '';
            $name = trim($firstName . ' ' . $lastName);

            // (Your existing code for getting photo URL and message text/type...)

            // Save incoming message to DB
            Telegram::create([
                'chat_id' => $chatId,
                'direction' => 'incoming',
                'message' => $messageText ?? '',
                'file_url' => $fileUrl,
                'type' => $type,
                'name' => $name,
                'photo_url' => $photoUrl,
                'is_read' => false,
            ]);

            // Retrieve last 15 messages for this chat for context (adjust limit as needed)
            $history = Telegram::where('chat_id', $chatId)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get()
                ->reverse() // to get chronological order oldest->newest
                ->map(function ($msg) {
                    return [
                        'role' => $msg->direction === 'incoming' ? 'user' : 'assistant',
                        'content' => $msg->message,
                    ];
                })
                ->values()
                ->all();

            // Append the current user message as the last entry (optional, since saved already)
            // Usually, it's already in history, so no need to add again.

            // Optionally, add system prompt at the start
            array_unshift($history, [
                'role' => 'system',
                'content' => 'You are a helpful assistant.',
            ]);

            // Get AI reply from OpenRouter with full chat history
            $aiReply = $this->askOpenRouter($history);

            // Send AI reply to Telegram user
            Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $aiReply,
            ]);

            // Save AI outgoing message to DB
            Telegram::create([
                'chat_id' => $chatId,
                'direction' => 'outgoing',
                'message' => $aiReply,
                'file_url' => null,
                'type' => 'text',
                'name' => 'AI Bot',
                'photo_url' => null,
                'is_read' => true,
            ]);
        }

        return response()->json(['ok' => true]);
    }
    
    // Get chat history for a specific chat_id
    public function getHistory($chat_id)
    {
        $messages = Telegram::where('chat_id', $chat_id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    // Get clients with their latest incoming message info
    public function getClients()
    {
        $latestMessages = Telegram::select('chat_id', DB::raw('MAX(id) as latest_id'))
            ->whereNotNull('chat_id')
            ->where('direction', 'incoming')
            ->groupBy('chat_id');

        $clients = Telegram::joinSub($latestMessages, 'latest_messages', function ($join) {
            $join->on('telegrams.id', '=', 'latest_messages.latest_id');
        })
        ->orderByDesc(DB::raw('telegrams.is_read = 0')) // unread first
        ->orderByDesc('telegrams.id') // then latest messages
        ->get();

        $result = [];
        foreach ($clients as $client) {
            $user = User::where('telegram_id', $client->chat_id)->first();

            $name = $user ? $user->name : $client->name;
            $photo_url = $user && $user->profile ? asset('storage/' . $user->profile) : $client->photo_url;

            $result[] = [
                'chat_id'   => $client->chat_id,
                'name'      => $name,
                'photo_url' => $photo_url,
            ];
        }

        return response()->json($result);
    }

    // Get unread counts per client
    public function unreadCounts()
    {
        $clients = Telegram::select('chat_id')
            ->where('direction', 'incoming')
            ->groupBy('chat_id')
            ->get();

        $result = [];
        foreach ($clients as $client) {
            $unread = Telegram::where('chat_id', $client->chat_id)
                ->where('direction', 'incoming')
                ->where(function ($q) {
                    $q->whereNull('is_read')->orWhere('is_read', false);
                })
                ->count();

            $result[] = [
                'chat_id' => $client->chat_id,
                'unread_count' => $unread
            ];
        }

        return response()->json($result);
    }

    // Mark messages as read for a chat_id
    public function markRead(Request $request)
    {
        $request->validate(['chat_id' => 'required|string']);

        Telegram::where('chat_id', $request->chat_id)
            ->where('direction', 'incoming')
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }
}
