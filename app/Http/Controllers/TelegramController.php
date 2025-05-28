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
        $this->baseUrl = "https://api.telegram.org/bot" . config('services.telegram.bot_token');
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $response = Http::post("{$this->baseUrl}/sendMessage", [
            'chat_id' => $validated['chat_id'],
            'text' => $validated['message'],
        ]);

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

    private function askOpenRouterWithHistory(string $chatId, array $messages): string
    {
        $apiKey = config('services.openrouter.api_key');

        if (empty($messages)) {
            $messages[] = ['role' => 'user', 'content' => 'Hello'];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'meta-llama/llama-4-maverick:free',
            'messages' => $messages,
            // 'max_tokens' => 800,
        ]);

        if ($response->successful()) {
            return $response['choices'][0]['message']['content'] ?? "🤖 Sorry, I couldn't generate a response.";
        }

        return "⚠️ API error: " . $response->body();
    }

    public function webhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['message'])) {
            $msg = $data['message'];
            $chatId = $msg['chat']['id'];
            $firstName = $msg['chat']['first_name'] ?? '';
            $lastName = $msg['chat']['last_name'] ?? '';
            $name = trim($firstName . ' ' . $lastName);

            // 🔄 Get Telegram user's profile photo
            $photoUrl = null;
            $photosResponse = Http::get("{$this->baseUrl}/getUserProfilePhotos", [
                'user_id' => $chatId,
                'limit' => 1,
            ]);

            if ($photosResponse->successful() && $photosResponse['result']['total_count'] > 0) {
                $photo = $photosResponse['result']['photos'][0][0];
                $fileId = $photo['file_id'];

                $fileInfo = Http::get("{$this->baseUrl}/getFile", [
                    'file_id' => $fileId,
                ]);

                if ($fileInfo->successful()) {
                    $filePath = $fileInfo['result']['file_path'];
                    $photoUrl = "https://api.telegram.org/file/bot" . config('services.telegram.bot_token') . "/{$filePath}";
                }
            }

            // Handle /start command
            if (isset($msg['text']) && $msg['text'] === '/start') {
                Http::post("{$this->baseUrl}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => "Hi $name 👋, how can I help you today?",
                ]);
                return response()->json(['ok' => true]);
            }

            // 🧠 Detect message type
            $type = 'text';
            $fileUrl = null;
            $fileId = null;
            $messageText = $msg['caption'] ?? $msg['text'] ?? '';

            if (isset($msg['photo'])) {
                $type = 'photo';
                $photos = $msg['photo'];
                $fileId = $photos[count($photos) - 1]['file_id'] ?? null;
                if (!$messageText) {
                    $messageText = '[User sent a photo]';
                }
            } elseif (isset($msg['document'])) {
                $type = 'document';
                $fileId = $msg['document']['file_id'] ?? null;
                if (!$messageText) {
                    $messageText = '[User sent a document]';
                }
            }

            // 🗂️ Fetch file URL if file_id is available
            if ($fileId) {
                $fileResponse = Http::get("{$this->baseUrl}/getFile", [
                    'file_id' => $fileId,
                ]);
                if ($fileResponse->successful()) {
                    $filePath = $fileResponse['result']['file_path'];
                    $fileUrl = "https://api.telegram.org/file/bot" . config('services.telegram.bot_token') . "/{$filePath}";
                }
            }

            // 💾 Store incoming message
            Telegram::create([
                'chat_id' => $chatId,
                'direction' => 'incoming',
                'message' => $messageText,
                'file_url' => $fileUrl,
                'type' => $type,
                'name' => $name,
                'photo_url' => $photoUrl,
                'is_read' => false,
            ]);

            // 🧠 Fetch recent chat history and generate reply
            $history = Telegram::where('chat_id', $chatId)
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get()
                ->reverse()
                ->map(function ($msg) {
                    $content = $msg->type === 'photo' && !$msg->message ? '[Image sent]' : $msg->message;
                    return [
                        'role' => $msg->direction === 'incoming' ? 'user' : 'assistant',
                        'content' => $content,
                    ];
                })
                ->values()
                ->all();

            array_unshift($history, [
                'role' => 'system',
                'content' => 'You are a helpful assistant.',
            ]);

            $aiReply = $this->askOpenRouterWithHistory($chatId, $history);

            // 📤 Reply to user via Telegram
            Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $aiReply,
            ]);

            // 💾 Store AI's response
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

    public function getHistory($chat_id)
    {
        $messages = Telegram::where('chat_id', $chat_id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function getClients()
    {
        $latestMessages = Telegram::select('chat_id', DB::raw('MAX(id) as latest_id'))
            ->whereNotNull('chat_id')
            ->where('direction', 'incoming')
            ->groupBy('chat_id');

        $clients = Telegram::joinSub($latestMessages, 'latest_messages', function ($join) {
            $join->on('telegrams.id', '=', 'latest_messages.latest_id');
        })
        ->orderByDesc(DB::raw('telegrams.is_read = 0'))
        ->orderByDesc('telegrams.id')
        ->get();

        $result = [];
        foreach ($clients as $client) {
            $user = User::firstWhere('telegram_id', $client->chat_id);
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
                'unread_count' => $unread,
            ];
        }

        return response()->json($result);
    }

    public function markRead(Request $request)
    {
        $request->validate(['chat_id' => 'required|string']);

        Telegram::where('chat_id', $request->chat_id)
            ->where('direction', 'incoming')
            ->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }
}
