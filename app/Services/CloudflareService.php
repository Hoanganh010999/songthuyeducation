<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected $accountId;
    protected $apiToken;
    protected $imagesBaseUrl;
    protected $streamBaseUrl;

    public function __construct()
    {
        $this->accountId = config('services.cloudflare.account_id');
        $this->apiToken = config('services.cloudflare.api_token');
        $this->imagesBaseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1";
        $this->streamBaseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
    }

    /**
     * Upload an image to Cloudflare Images
     */
    public function uploadImage($file, $metadata = [])
    {
        try {
            Log::info('[Cloudflare] Uploading image', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            $response = Http::withToken($this->apiToken)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->imagesBaseUrl, [
                    'metadata' => json_encode($metadata),
                    'requireSignedURLs' => false, // Allow public access
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('[Cloudflare] Image uploaded successfully', [
                    'id' => $data['result']['id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'id' => $data['result']['id'],
                    'filename' => $data['result']['filename'],
                    'variants' => $data['result']['variants'] ?? [],
                    'uploaded' => $data['result']['uploaded'],
                ];
            }

            Log::error('[Cloudflare] Image upload failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['errors'][0]['message'] ?? 'Upload failed',
            ];
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Image upload exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload a video to Cloudflare Stream
     */
    public function uploadVideo($file, $metadata = [])
    {
        try {
            Log::info('[Cloudflare] Uploading video', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            // For large videos, use TUS upload
            // For now, use direct upload (max 200MB)
            $response = Http::withToken($this->apiToken)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post($this->streamBaseUrl, [
                    'meta' => json_encode($metadata),
                    'requireSignedURLs' => false,
                    'allowedOrigins' => ['*'], // Or specify your domains
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('[Cloudflare] Video uploaded successfully', [
                    'uid' => $data['result']['uid'] ?? null,
                ]);

                return [
                    'success' => true,
                    'uid' => $data['result']['uid'],
                    'thumbnail' => $data['result']['thumbnail'] ?? null,
                    'playback' => $data['result']['playback'] ?? null,
                    'preview' => $data['result']['preview'] ?? null,
                    'status' => $data['result']['status'] ?? [],
                ];
            }

            Log::error('[Cloudflare] Video upload failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['errors'][0]['message'] ?? 'Upload failed',
            ];
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Video upload exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an image from Cloudflare Images
     */
    public function deleteImage($imageId)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->delete("{$this->imagesBaseUrl}/{$imageId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Failed to delete image', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a video from Cloudflare Stream
     */
    public function deleteVideo($videoUid)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->delete("{$this->streamBaseUrl}/{$videoUid}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Failed to delete video', [
                'video_uid' => $videoUid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get image details
     */
    public function getImage($imageId)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->imagesBaseUrl}/{$imageId}");

            if ($response->successful()) {
                return $response->json()['result'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Failed to get image', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get video details
     */
    public function getVideo($videoUid)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->streamBaseUrl}/{$videoUid}");

            if ($response->successful()) {
                return $response->json()['result'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Failed to get video', [
                'video_uid' => $videoUid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify API token
     */
    public function verifyToken()
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get('https://api.cloudflare.com/client/v4/user/tokens/verify');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[Cloudflare] Token verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

