<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WebSocketService
{
    /**
     * Emit event to a specific room
     */
    public static function emitToRoom(string $room, string $event, array $data): void
    {
        try {
            $wsUrl = env('WS_URL', 'http://localhost:3001');

            $client = new Client();
            $client->post("{$wsUrl}/api/emit", [
                'json' => [
                    'room' => $room,
                    'event' => $event,
                    'data' => $data,
                ],
                'timeout' => 2,
            ]);

            Log::info("[WebSocket] Emitted event to room", [
                'room' => $room,
                'event' => $event,
            ]);
        } catch (\Exception $e) {
            Log::error("[WebSocket] Failed to emit event", [
                'room' => $room,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - WebSocket failures shouldn't break API responses
        }
    }
}
