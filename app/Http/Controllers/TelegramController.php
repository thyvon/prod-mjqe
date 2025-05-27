<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        ]);

        return response()->json($response->json());
    }

    // Receive incoming message from Telegram webhook and save to DB
    public function webhook(Request $request)
    {
        $data = $request->all();

        if (isset($data['message'])) {
            $msg = $data['message'];
            $chatId = $msg['chat']['id'];
            $firstName = $msg['chat']['first_name'] ?? '';
            $lastName = $msg['chat']['last_name'] ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $text = $msg['text'] ?? null;

            // Get profile photo (optional, requires extra API call)
            $photoUrl = null;
            try {
                $photosResp = Http::get("{$this->baseUrl}/getUserProfilePhotos", [
                    'user_id' => $chatId,
                    'limit' => 1
                ]);
                $photos = $photosResp->json();
                if (!empty($photos['result']['photos'][0][0]['file_id'])) {
                    $fileId = $photos['result']['photos'][0][0]['file_id'];
                    $fileResp = Http::get("{$this->baseUrl}/getFile", [
                        'file_id' => $fileId
                    ]);
                    $fileData = $fileResp->json();
                    if (!empty($fileData['result']['file_path'])) {
                        $photoUrl = "https://api.telegram.org/file/bot" . env('TELEGRAM_BOT_TOKEN') . "/" . $fileData['result']['file_path'];
                    }
                }
            } catch (\Exception $e) {
                // Ignore photo errors
            }

            Telegram::create([
                'chat_id' => $chatId,
                'direction' => 'incoming',
                'message' => $text,
                'file_url' => null,
                'type' => 'text',
                'name' => $name,
                'photo_url' => $photoUrl,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // Get chat history from DB for a specific chat_id
    public function getHistory($chat_id)
    {
        $messages = Telegram::where('chat_id', $chat_id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function getClients()
    {
        $clients = Telegram::select('chat_id')
            ->whereNotNull('chat_id')
            ->groupBy('chat_id')
            ->get();

        $result = [];
        foreach ($clients as $client) {
            // Try to find user by telegram_id
            $user = User::where('telegram_id', $client->chat_id)->first();

            // Build profile URL if user and profile exists
            $photo_url = null;
            if ($user && $user->profile) {
                $photo_url = asset('storage/' . $user->profile);
            }

            $result[] = [
                'chat_id'    => $client->chat_id,
                'name'       => $user ? $user->name : null,
                'photo_url'  => $photo_url,
            ];
        }

        return response()->json($result);
    }

    public function unreadCounts()
    {
        // Count incoming messages that have not been replied to (no outgoing after them)
        $clients = Telegram::select('chat_id')
            ->where('direction', 'incoming')
            ->groupBy('chat_id')
            ->get();

        $result = [];
        foreach ($clients as $client) {
            $unread = Telegram::where('chat_id', $client->chat_id)
                ->where('direction', 'incoming')
                ->where(function($q) {
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

    public function markRead(Request $request)
    {
        Telegram::where('chat_id', $request->chat_id)
            ->where('direction', 'incoming')
            ->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    }
}