<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloNotificationService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.zalo.base_url', 'http://localhost:3001');
        $this->apiKey = config('services.zalo.api_key');
    }

    /**
     * Build headers for API requests with optional accountId
     *
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    protected function buildHeaders(?int $accountId = null): array
    {
        $headers = [
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // Add X-Account-Id header for multi-session support
        if ($accountId !== null) {
            $headers['X-Account-Id'] = (string) $accountId;
        }

        return $headers;
    }

    /**
     * Check if Zalo service is ready
     *
     * @param int|null $accountId Account ID for multi-session support
     */
    public function isReady(?int $accountId = null): bool
    {
        try {
            Log::info('[Zalo] Checking service status', [
                'baseUrl' => $this->baseUrl,
                'hasApiKey' => !empty($this->apiKey),
                'url' => "{$this->baseUrl}/api/auth/status",
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(5)->withHeaders($this->buildHeaders($accountId))
                ->get("{$this->baseUrl}/api/auth/status");

            $isReady = $response->successful() && $response->json('isReady', false);
            
            Log::info('[Zalo] Service status check result', [
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'isReady' => $isReady,
                'response_body' => $response->json(),
            ]);

            return $isReady;
        } catch (\Exception $e) {
            Log::error('[Zalo] Service health check failed', [
                'error' => $e->getMessage(),
                'baseUrl' => $this->baseUrl,
                'hasApiKey' => !empty($this->apiKey),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Initialize Zalo connection
     *
     * @param int|null $accountId Account ID for multi-session support
     * @param bool $forceNew Force new login even if session exists
     */
    public function initialize(?int $accountId = null, bool $forceNew = false): array
    {
        try {
            Log::info('[Zalo] Initialize called', [
                'baseUrl' => $this->baseUrl,
                'hasApiKey' => !empty($this->apiKey),
                'apiKeyLength' => strlen($this->apiKey ?? ''),
                'accountId' => $accountId,
                'forceNew' => $forceNew,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured. Please set ZALO_API_KEY in .env',
                ];
            }

            Log::info('[Zalo] Sending initialize request', [
                'url' => "{$this->baseUrl}/api/auth/initialize",
                'apiKeyPrefix' => substr($this->apiKey, 0, 10) . '...',
                'accountId' => $accountId,
                'forceNew' => $forceNew,
            ]);

            $payload = [
                'forceNew' => $forceNew,
            ];

            // Add accountId to payload if provided
            if ($accountId !== null) {
                $payload['accountId'] = $accountId;
            }

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/auth/initialize", $payload);

            Log::info('[Zalo] Initialize response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::error('[Zalo] Initialize failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers(),
                ]);
                
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to initialize Zalo service',
                    'alreadyLoggedIn' => $errorData['alreadyLoggedIn'] ?? false,
                ];
            }

            $result = $response->json();
            Log::info('[Zalo] Initialize response parsed', [
                'success' => $result['success'] ?? false,
                'hasQrCode' => isset($result['qrCode']),
                'qrCodeLength' => isset($result['qrCode']) ? strlen($result['qrCode']) : 0,
                'alreadyLoggedIn' => $result['alreadyLoggedIn'] ?? false,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Initialize exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message to single recipient
     *
     * @param string $to Phone number or user ID
     * @param string $message Message content
     * @param string $type 'user' or 'group'
     * @param int|null $accountId Account ID for multi-session support
     * @param array|null $styles Rich text styles
     * @param array|null $mentions Mentions for group messages
     * @param array|null $metadata User metadata (sent_by_user_id, etc.) - INTERNAL USE ONLY
     * @return array
     */
    public function sendMessage(string $to, string $message, string $type = 'user', ?int $accountId = null, ?array $styles = null, ?array $mentions = null, ?array $metadata = null): array
    {
        try {
            Log::info('[Zalo] Sending message', [
                'to' => $to,
                'type' => $type,
                'message_length' => strlen($message),
                'has_styles' => !empty($styles),
                'styles_count' => $styles ? count($styles) : 0,
                'has_mentions' => !empty($mentions),
                'mentions_count' => $mentions ? count($mentions) : 0,
                'has_metadata' => !empty($metadata),
                'baseUrl' => $this->baseUrl,
                'hasApiKey' => !empty($this->apiKey),
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            // Validate inputs
            if (empty($to)) {
                Log::error('[Zalo] Missing recipient (to)');
                return [
                    'success' => false,
                    'message' => 'Recipient (to) is required',
                ];
            }
            
            // Allow empty message if we're sending media (image/file)
            // This will be handled by the caller
            if (empty($message)) {
                Log::warning('[Zalo] Message is empty, but continuing (may be media-only message)');
                $message = ''; // Use empty string instead of failing
            }

            $requestPayload = [
                'to' => $to,
                'message' => $message,
                'type' => $type,
            ];
            
            // Add styles if provided (for rich text)
            if ($styles && is_array($styles) && count($styles) > 0) {
                $requestPayload['styles'] = $styles;
            }
            
            // Add mentions if provided (for group messages)
            if ($mentions && is_array($mentions) && count($mentions) > 0) {
                $requestPayload['mentions'] = $mentions;
                Log::info('[Zalo] Adding mentions to message', [
                    'mentions_count' => count($mentions),
                    'mentions' => $mentions,
                ]);
            }
            
            // ✅ ADD METADATA if provided (for tracking sent_by_user_id)
            // This is INTERNAL between Laravel and zalo-service, NOT sent to Zalo API
            if ($metadata && is_array($metadata) && count($metadata) > 0) {
                $requestPayload['metadata'] = $metadata;
                Log::info('[Zalo] Adding metadata to message (internal)', [
                    'metadata' => $metadata,
                    'sent_by_user_id' => $metadata['sent_by_user_id'] ?? null,
                ]);
            }
            
            Log::info('[ZaloNotificationService] Sending HTTP request to zalo-service', [
                'url' => "{$this->baseUrl}/api/message/send",
                'payload' => [
                    'to' => $to,
                    'type' => $type,
                    'message_length' => strlen($message),
                ],
                'has_api_key' => !empty($this->apiKey),
                'accountId' => $accountId,
            ]);
            
            // 🔥 DEBUG: Log FULL payload before sending
            Log::info('[ZaloNotificationService] FULL REQUEST PAYLOAD', [
                'full_payload' => $requestPayload,
                'payload_keys' => array_keys($requestPayload),
                'has_metadata' => isset($requestPayload['metadata']),
            ]);

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/send", $requestPayload);
            
            Log::info('[ZaloNotificationService] HTTP response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Message send HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'to' => $to,
                    'type' => $type,
                    'baseUrl' => $this->baseUrl,
                ]);
                
                // Try to parse error response
                $errorData = [];
                try {
                    $errorData = $response->json();
                } catch (\Exception $e) {
                    Log::warning('[Zalo] Failed to parse error response as JSON', [
                        'body' => $errorBody,
                    ]);
                }
                
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to send message: HTTP ' . $response->status(),
                    'error_details' => $errorBody,
                ];
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                Log::info('[Zalo] Message sent successfully', ['to' => $to]);
            } else {
                Log::error('[Zalo] Message send failed', [
                    'to' => $to,
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Send message exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message to multiple recipients
     *
     * @param array $recipients [['to' => 'phone', 'type' => 'user'], ...]
     * @param string $message Message content
     * @return array
     */
    public function sendBulkMessage(array $recipients, string $message): array
    {
        try {
            Log::info('[Zalo] Sending bulk message', [
                'recipients_count' => count($recipients),
                'message_length' => strlen($message),
            ]);

            $response = Http::timeout(120)->withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/message/send-bulk", [
                'recipients' => $recipients,
                'message' => $message,
            ]);

            $result = $response->json();

            Log::info('[Zalo] Bulk message completed', [
                'success_count' => count($result['results'] ?? []),
                'error_count' => count($result['errors'] ?? []),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Bulk send exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send image to recipient
     *
     * @param string $to Phone number or user ID
     * @param string $imageSource URL or path of image
     * @param string $type 'user' or 'group'
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function sendImage(string $to, string $imageSource, string $type = 'user', ?int $accountId = null): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            // OPTIMIZATION: Send absolute file path directly (NO HTTP download needed!)
            // zalo-service can read file directly from shared filesystem (MUCH FASTER!)
            $isLocalFile = file_exists($imageSource);
            
            if ($isLocalFile) {
                // Send absolute file path (NO download, direct file access)
                $absolutePath = $imageSource;
                $fileSize = filesize($absolutePath);
                
                Log::info('[Zalo] Sending image with absolute path (optimized - no download)', [
                    'to' => $to,
                    'type' => $type,
                    'file_path' => $absolutePath,
                    'file_size' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    'accountId' => $accountId,
                ]);

                // Use 60s timeout (no download, just Zalo upload)
                $response = Http::timeout(60)->withHeaders($this->buildHeaders($accountId))
                    ->post("{$this->baseUrl}/api/message/send-image", [
                        'to' => $to,
                        'imagePath' => $absolutePath, // Send absolute path (no download!)
                        'type' => $type,
                    ]);
            } else {
                // Fallback: Convert to public URL (if file not accessible)
                $relativePath = str_replace(storage_path('app/public/'), '', $imageSource);
                $publicUrl = asset('storage/' . str_replace('\\', '/', $relativePath));
                
                Log::warning('[Zalo] File not accessible locally, using URL (slower)', [
                    'to' => $to,
                    'type' => $type,
                    'image_source' => $imageSource,
                    'public_url' => $publicUrl,
                    'accountId' => $accountId,
                ]);

                // Use 120s timeout (zalo-service will download from URL)
                $response = Http::timeout(120)->withHeaders($this->buildHeaders($accountId))
                    ->post("{$this->baseUrl}/api/message/send-image", [
                        'to' => $to,
                        'imageUrl' => $publicUrl,
                        'type' => $type,
                    ]);
            }

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Send image HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'to' => $to,
                ]);
                
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to send image: HTTP ' . $response->status(),
                ];
            }

            $result = $response->json();
            
            if ($result['success'] ?? false) {
                Log::info('[Zalo] Image sent successfully', ['to' => $to]);
            } else {
                Log::error('[Zalo] Send image failed', [
                    'to' => $to,
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Send image exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send file (document) to a Zalo user or group
     *
     * @param int|null $accountId Account ID for multi-session support
     */
    public function sendFile(string $to, string $fileSource, string $fileName, string $type = 'user', ?int $accountId = null): array
    {
        try {
            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            // Similar to sendImage: prefer absolute file path over URL
            $isLocalFile = file_exists($fileSource);
            
            if ($isLocalFile) {
                // Send absolute file path (direct file access, no download)
                $absolutePath = $fileSource;
                $fileSize = filesize($absolutePath);
                
                Log::info('[Zalo] Sending file with absolute path (optimized)', [
                    'to' => $to,
                    'type' => $type,
                    'file_path' => $absolutePath,
                    'file_name' => $fileName,
                    'file_size' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                    'accountId' => $accountId,
                ]);

                // Use 90s timeout (files can be larger than images)
                $response = Http::timeout(90)->withHeaders($this->buildHeaders($accountId))
                    ->post("{$this->baseUrl}/api/message/send-file", [
                        'to' => $to,
                        'filePath' => $absolutePath,
                        'fileName' => $fileName,
                        'type' => $type,
                    ]);
            } else {
                // Fallback: Use URL (slower, requires download)
                $relativePath = str_replace(storage_path('app/public/'), '', $fileSource);
                $publicUrl = asset('storage/' . str_replace('\\', '/', $relativePath));
                
                Log::warning('[Zalo] File not accessible locally, using URL', [
                    'to' => $to,
                    'type' => $type,
                    'file_source' => $fileSource,
                    'public_url' => $publicUrl,
                    'accountId' => $accountId,
                ]);

                // Use 150s timeout (larger files + download)
                $response = Http::timeout(150)->withHeaders($this->buildHeaders($accountId))
                    ->post("{$this->baseUrl}/api/message/send-file", [
                        'to' => $to,
                        'fileUrl' => $publicUrl,
                        'fileName' => $fileName,
                        'type' => $type,
                    ]);
            }

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Send file HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'to' => $to,
                ]);
                
                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to send file: HTTP ' . $response->status(),
                ];
            }

            $result = $response->json();
            
            if ($result['success'] ?? false) {
                Log::info('[Zalo] File sent successfully', ['to' => $to, 'file_name' => $fileName]);
            } else {
                Log::error('[Zalo] Send file failed', [
                    'to' => $to,
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Send file exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find user by phone number
     *
     * @param string $phone Phone number
     * @return array|null
     */
    public function findUserByPhone(string $phone): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/user/find", [
                'phone' => $phone,
            ]);

            $result = $response->json();

            return $result['success'] ? $result['data'] : null;
        } catch (\Exception $e) {
            Log::error('[Zalo] Find user exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get all friends
     *
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function getFriends(?int $accountId = null): array
    {
        try {
            Log::info('[Zalo] Getting friends', [
                'url' => "{$this->baseUrl}/api/user/friends",
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->get("{$this->baseUrl}/api/user/friends");

            Log::info('[Zalo] Get friends response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            $result = $response->json();

            if (!$response->successful()) {
                $errorMessage = "Zalo API error: HTTP {$response->status()}";

                // 🔥 FIX: Check if rate limited
                if ($response->status() === 429) {
                    $errorMessage = "Rate limited by Zalo API (429). Please try again later.";
                }

                Log::error('[Zalo] Get friends failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'error' => $errorMessage,
                ]);

                // 🔥 FIX: Throw exception instead of returning empty array
                // This allows syncFriends() to catch and update progress with error message
                throw new \Exception($errorMessage);
            }

            $friends = $result['data'] ?? [];
            Log::info('[Zalo] Friends retrieved', ['count' => count($friends)]);

            return $friends;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            // HTTP client exceptions (timeout, connection error, etc.)
            Log::error('[Zalo] Get friends HTTP exception', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // 🔥 FIX: Re-throw instead of swallowing
        } catch (\Exception $e) {
            // Other exceptions
            Log::error('[Zalo] Get friends exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // 🔥 FIX: Re-throw instead of swallowing
        }
    }

    /**
     * Get all groups
     *
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function getGroups(?int $accountId = null): array
    {
        try {
            // 🚀 LAZY LOADING: Fetch all groups in batches to avoid timeout
            $allGroups = [];
            $offset = 0;
            $limit = 50; // Batch size
            $hasMore = true;

            Log::info('[Zalo] 🚀 Starting lazy loading groups', [
                'url' => "{$this->baseUrl}/api/group/list",
                'accountId' => $accountId,
                'limit' => $limit,
            ]);

            while ($hasMore) {
                Log::info('[Zalo] 📦 Fetching batch', [
                    'offset' => $offset,
                    'limit' => $limit,
                    'fetched_so_far' => count($allGroups),
                ]);

                $response = Http::timeout(60)->withHeaders($this->buildHeaders($accountId))
                    ->get("{$this->baseUrl}/api/group/list", [
                        'account_id' => $accountId,
                        'offset' => $offset,
                        'limit' => $limit,
                    ]);

                Log::info('[Zalo] 📨 Batch response', [
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'offset' => $offset,
                ]);

                // 🔥 FIX: Check HTTP status first
                if (!$response->successful()) {
                    $errorMessage = "Zalo API error: HTTP {$response->status()}";

                    // Check if rate limited
                    if ($response->status() === 429) {
                        $errorMessage = "Rate limited by Zalo API (429). Please try again later.";
                    }

                    Log::error('[Zalo] Get groups batch failed - HTTP error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'error' => $errorMessage,
                        'offset' => $offset,
                    ]);

                    throw new \Exception($errorMessage);
                }

                $result = $response->json();

                if (!($result['success'] ?? false)) {
                    // If success=false in response
                    $errorMessage = $result['message'] ?? 'Unknown error from Zalo API';
                    Log::error('[Zalo] Get groups batch failed - API error', [
                        'response' => $result,
                        'error' => $errorMessage,
                        'offset' => $offset,
                    ]);

                    throw new \Exception($errorMessage);
                }

                $batchGroups = $result['data'] ?? [];
                $pagination = $result['pagination'] ?? [];

                Log::info('[Zalo] ✅ Batch retrieved', [
                    'batch_count' => count($batchGroups),
                    'total' => $pagination['total'] ?? 'unknown',
                    'has_more' => $pagination['has_more'] ?? false,
                    'next_offset' => $pagination['next_offset'] ?? null,
                ]);

                // Add batch to all groups
                $allGroups = array_merge($allGroups, $batchGroups);

                // Check if there are more groups to fetch
                $hasMore = $pagination['has_more'] ?? false;
                $offset = $pagination['next_offset'] ?? ($offset + $limit);

                // Safety check: prevent infinite loop
                if ($offset > 10000) {
                    Log::warning('[Zalo] ⚠️ Safety limit reached, stopping at offset', ['offset' => $offset]);
                    break;
                }
            }

            Log::info('[Zalo] 🎉 All groups retrieved', [
                'total_count' => count($allGroups),
            ]);

            return $allGroups;

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // HTTP client exceptions (timeout, connection error, etc.)
            Log::error('[Zalo] Get groups HTTP exception', [
                'error' => $e->getMessage(),
            ]);
            throw $e; // 🔥 FIX: Re-throw instead of swallowing
        } catch (\Exception $e) {
            // Other exceptions
            Log::error('[Zalo] Get groups exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // 🔥 FIX: Re-throw instead of swallowing
        }
    }

    /**
     * Send notification to student
     *
     * @param \App\Models\User $student
     * @param string $message
     * @return bool
     */
    public function notifyStudent($student, string $message): bool
    {
        $phone = $student->phone ?? $student->student->phone ?? null;

        if (!$phone) {
            Log::warning('[Zalo] Student has no phone number', ['student_id' => $student->id]);
            return false;
        }

        $result = $this->sendMessage($phone, $message);

        return $result['success'] ?? false;
    }

    /**
     * Send notification to multiple students
     *
     * @param \Illuminate\Support\Collection $students
     * @param string $message
     * @return array
     */
    public function notifyStudents($students, string $message): array
    {
        $recipients = [];

        foreach ($students as $student) {
            $phone = $student->phone ?? $student->student->phone ?? null;

            if ($phone) {
                $recipients[] = [
                    'to' => $phone,
                    'type' => 'user',
                ];
            }
        }

        if (empty($recipients)) {
            return [
                'success' => false,
                'message' => 'No valid phone numbers found',
            ];
        }

        return $this->sendBulkMessage($recipients, $message);
    }

    /**
     * Get current account information from zalo-service
     *
     * @param int|null $accountId Account ID for multi-session support
     * @return array|null
     */
    public function getAccountInfo(?int $accountId = null): ?array
    {
        try {
            Log::info('[Zalo] Getting account info', [
                'url' => "{$this->baseUrl}/api/auth/account-info",
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->get("{$this->baseUrl}/api/auth/account-info");

            Log::info('[Zalo] Get account info response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                Log::error('[Zalo] Get account info failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                $accountInfo = $result['data'] ?? [];

                // ✅ FIX: Get zalo_account_id from root level (Zalo service returns it there)
                if (isset($result['zalo_account_id']) && !isset($accountInfo['zalo_account_id'])) {
                    $accountInfo['zalo_account_id'] = $result['zalo_account_id'];
                }

                Log::info('[Zalo] Account info retrieved', [
                    'has_zalo_id' => isset($accountInfo['zalo_account_id']),
                    'has_name' => isset($accountInfo['name']),
                    'has_avatar_url' => isset($accountInfo['avatar_url']) && !empty($accountInfo['avatar_url']),
                    'avatar_url_preview' => isset($accountInfo['avatar_url']) ? substr($accountInfo['avatar_url'], 0, 50) . '...' : null,
                    'name' => $accountInfo['name'] ?? null,
                    'zalo_account_id' => $accountInfo['zalo_account_id'] ?? null,
                ]);
                return $accountInfo;
            }

            Log::error('[Zalo] Get account info failed', ['response' => $result]);
            return null;
        } catch (\Exception $e) {
            Log::error('[Zalo] Get account info exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Send reply message with quote
     *
     * @param string $to Phone number or user ID
     * @param string $message Message content
     * @param string $type 'user' or 'group'
     * @param array $quoteData Quote data from original message
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function sendReply(string $to, string $message, string $type = 'user', array $quoteData = [], ?int $accountId = null): array
    {
        try {
            Log::info('[Zalo] Sending reply message', [
                'to' => $to,
                'type' => $type,
                'message_length' => strlen($message),
                'has_quote' => !empty($quoteData),
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            $requestPayload = [
                'to' => $to,
                'message' => $message, // Can be string or object
                'type' => $type,
            ];

            // Add quote if provided
            // Quote should be sent as separate parameter, not nested in message
            if (!empty($quoteData)) {
                $requestPayload['quote'] = $quoteData;
            }

            Log::info('[ZaloNotificationService] Sending HTTP request to zalo-service for reply', [
                'url' => "{$this->baseUrl}/api/message/reply",
                'payload' => [
                    'to' => $to,
                    'type' => $type,
                    'message_length' => strlen($message),
                    'has_quote' => !empty($quoteData),
                ],
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/reply", $requestPayload);

            Log::info('[ZaloNotificationService] HTTP response received for reply', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Reply message HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);

                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to send reply: HTTP ' . $response->status(),
                ];
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                Log::info('[Zalo] Reply message sent successfully', ['to' => $to]);
            } else {
                Log::error('[Zalo] Reply message send failed', [
                    'to' => $to,
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Send reply exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add reaction to a message
     *
     * @param string $reaction Reaction icon (e.g., 'HEART', 'LIKE')
     * @param string $messageId Zalo message ID (msgId)
     * @param string $cliMsgId Zalo CLI message ID
     * @param string $threadId Thread ID (user ID or group ID)
     * @param string $type 'user' or 'group'
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function addReaction(string $reaction, string $messageId, string $cliMsgId, string $threadId, string $type = 'user', ?int $accountId = null): array
    {
        try {
            Log::info('[Zalo] Adding reaction', [
                'reaction' => $reaction,
                'message_id' => $messageId,
                'thread_id' => $threadId,
                'type' => $type,
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            $requestPayload = [
                'reaction' => $reaction,
                'message_id' => $messageId,
                'cli_msg_id' => $cliMsgId,
                'thread_id' => $threadId,
                'type' => $type,
            ];

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/reaction", $requestPayload);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Add reaction HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);

                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to add reaction: HTTP ' . $response->status(),
                ];
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                Log::info('[Zalo] Reaction added successfully');
            } else {
                Log::error('[Zalo] Add reaction failed', ['response' => $result]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Add reaction exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user info from Zalo API
     *
     * @param string $userId Zalo user ID
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function getUserInfo(string $userId, ?int $accountId = null): array
    {
        try {
            Log::info('[Zalo] Getting user info', [
                'user_id' => $userId,
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->get("{$this->baseUrl}/api/user/info/{$userId}");

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Get user info HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);

                $errorData = $response->json();
                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to get user info: HTTP ' . $response->status(),
                ];
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                Log::info('[Zalo] User info retrieved successfully');
            } else {
                Log::error('[Zalo] Get user info failed', ['response' => $result]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Get user info exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send message to class Zalo group
     *
     * @param int $classId Class ID
     * @param string $message Message content
     * @return array
     */
    public function sendToClassGroup(int $classId, string $message): array
    {
        try {
            // Get class with Zalo group info
            $class = \App\Models\ClassModel::find($classId);

            if (!$class) {
                Log::error('[Zalo] Class not found', ['class_id' => $classId]);
                return [
                    'success' => false,
                    'message' => 'Class not found',
                ];
            }

            if (!$class->zalo_group_id) {
                Log::warning('[Zalo] Class has no Zalo group', ['class_id' => $classId]);
                return [
                    'success' => false,
                    'message' => 'Class has no Zalo group configured',
                ];
            }

            // Get Zalo account for this class (or use primary account)
            $accountId = $class->zalo_account_id;

            if (!$accountId) {
                // Fallback to primary account
                $primaryAccount = \App\Models\ZaloAccount::where('is_active', true)
                    ->where('is_primary', true)
                    ->first();

                if (!$primaryAccount) {
                    Log::error('[Zalo] No active Zalo account found', ['class_id' => $classId]);
                    return [
                        'success' => false,
                        'message' => 'No active Zalo account found',
                    ];
                }

                $accountId = $primaryAccount->id;
            }

            // Send message to group using sendMessage method
            return $this->sendMessage($class->zalo_group_id, $message, 'group', $accountId);

        } catch (\Exception $e) {
            Log::error('[Zalo] Send to class group exception', [
                'class_id' => $classId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect zalo-service session for a specific account
     * This forces the session to reload fresh cookies from database on next use
     *
     * @param int $accountId Account ID to disconnect
     * @return bool
     */
    public static function disconnectSession(int $accountId): bool
    {
        try {
            $service = new self();

            Log::info('[Zalo] Disconnecting session', [
                'account_id' => $accountId,
                'url' => "{$service->baseUrl}/api/auth/disconnect",
            ]);

            $response = Http::timeout(10)->withHeaders($service->buildHeaders())
                ->post("{$service->baseUrl}/api/auth/disconnect", [
                    'accountId' => $accountId
                ]);

            if (!$response->successful()) {
                Log::warning('[Zalo] Disconnect session failed', [
                    'account_id' => $accountId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $result = $response->json();
            $success = $result['success'] ?? false;

            if ($success) {
                Log::info('[Zalo] Session disconnected successfully', ['account_id' => $accountId]);
            } else {
                Log::warning('[Zalo] Session disconnect returned false', [
                    'account_id' => $accountId,
                    'response' => $result
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('[Zalo] Disconnect session exception', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    /**
     * Create session alias for multi-branch support
     * Maps a new account_id to an existing session (by zalo_id)
     * 
     * @param int $accountId Account ID to create alias for
     * @return array
     */
    public static function createSessionAlias(int $accountId): array
    {
        try {
            $service = new self();

            Log::info('[Zalo] Creating session alias', [
                'account_id' => $accountId,
                'url' => "{$service->baseUrl}/api/session/create-alias",
            ]);

            $response = Http::timeout(10)->withHeaders($service->buildHeaders())
                ->post("{$service->baseUrl}/api/session/create-alias", [
                    'account_id' => $accountId
                ]);

            if (!$response->successful()) {
                Log::warning('[Zalo] Create session alias failed', [
                    'account_id' => $accountId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to create session alias: ' . $response->body()
                ];
            }

            $result = $response->json();
            
            if ($result['success'] ?? false) {
                Log::info('[Zalo] Session alias created successfully', [
                    'account_id' => $accountId,
                    'zalo_account_id' => $result['zaloId'] ?? null
                ]);
            } else {
                Log::warning('[Zalo] Session alias creation returned false', [
                    'account_id' => $accountId,
                    'response' => $result
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Create session alias exception', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a reminder/event in a Zalo conversation
     *
     * @param string $threadId User ID or Group ID
     * @param string $title Reminder title
     * @param int|string $startTime Unix timestamp (milliseconds) or ISO date string
     * @param string $type 'user' or 'group'
     * @param string|null $emoji Emoji for the reminder (default: ⏰)
     * @param int $repeat Repeat mode (0=None, 1=Daily, 2=Weekly, 3=Monthly)
     * @param int|null $accountId Account ID for multi-session support
     * @return array
     */
    public function createReminder(
        string $threadId,
        string $title,
        $startTime,
        string $type = 'user',
        ?string $emoji = '📚',
        int $repeat = 0,
        ?int $accountId = null
    ): array {
        try {
            // Convert start_time to timestamp if it's a string
            if (is_string($startTime)) {
                $startTime = strtotime($startTime) * 1000; // Convert to milliseconds
            } elseif (is_int($startTime) && $startTime < 10000000000) {
                // If timestamp is in seconds, convert to milliseconds
                $startTime = $startTime * 1000;
            }

            Log::info('[Zalo] Creating reminder', [
                'thread_id' => $threadId,
                'title' => $title,
                'start_time' => $startTime,
                'type' => $type,
                'emoji' => $emoji,
                'repeat' => $repeat,
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            // Validate inputs
            if (empty($threadId)) {
                return [
                    'success' => false,
                    'message' => 'thread_id is required',
                ];
            }

            if (empty($title)) {
                return [
                    'success' => false,
                    'message' => 'title is required',
                ];
            }

            if (empty($startTime) || $startTime < time() * 1000) {
                return [
                    'success' => false,
                    'message' => 'start_time must be a valid future timestamp',
                ];
            }

            $requestPayload = [
                'options' => [
                    'title' => $title,
                    'startTime' => $startTime,
                    'emoji' => $emoji ?? '📚',
                    'repeat' => $repeat,
                ],
                'threadId' => $threadId,
                'type' => $type,
            ];

            Log::info('[ZaloNotificationService] Sending create-reminder request to zalo-service', [
                'url' => "{$this->baseUrl}/api/message/create-reminder",
                'payload' => $requestPayload,
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(30)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/create-reminder", $requestPayload);

            Log::info('[ZaloNotificationService] Create-reminder response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Create reminder HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'thread_id' => $threadId,
                ]);

                $errorData = [];
                try {
                    $errorData = $response->json();
                } catch (\Exception $e) {
                    Log::warning('[Zalo] Failed to parse error response as JSON', [
                        'body' => $errorBody,
                    ]);
                }

                return [
                    'success' => false,
                    'message' => $errorData['message'] ?? 'Failed to create reminder: HTTP ' . $response->status(),
                    'error_details' => $errorBody,
                ];
            }

            $result = $response->json();

            if ($result['success'] ?? false) {
                Log::info('[Zalo] Reminder created successfully', [
                    'thread_id' => $threadId,
                    'title' => $title,
                ]);
            } else {
                Log::error('[Zalo] Reminder creation failed', [
                    'thread_id' => $threadId,
                    'response' => $result,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('[Zalo] Create reminder exception', [
                'thread_id' => $threadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send video message to Zalo
     */
    public function sendVideo(string $to, string $videoSource, ?string $videoPath = null, ?string $msg = null, string $type = 'user', ?int $accountId = null): array
    {
        try {
            Log::info('[Zalo] Sending video', [
                'to' => $to,
                'video_source' => $videoSource,
                'has_video_path' => !empty($videoPath),
                'type' => $type,
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            // Build options object - prefer videoPath over videoUrl
            $options = [];
            
            if (!empty($videoPath) && file_exists($videoPath)) {
                // Send absolute path for direct upload (MUCH FASTER)
                $options['videoPath'] = $videoPath;
                Log::info('[ZaloNotificationService] Using absolute video path', ['path' => substr($videoPath, 0, 100)]);
            } else {
                // Fallback to URL (will be downloaded by zalo-service)
                $options['videoUrl'] = $videoSource;
                Log::info('[ZaloNotificationService] Using video URL', ['url' => substr($videoSource, 0, 100)]);
            }

            if ($msg) {
                $options['msg'] = $msg;
            }

            // New format: wrap in options, use threadId instead of to
            $requestPayload = [
                'options' => $options,
                'threadId' => $to,
                'type' => $type,
            ];

            Log::info('[ZaloNotificationService] Sending video request to zalo-service', [
                'url' => "{$this->baseUrl}/api/message/send-video",
                'payload' => $requestPayload,
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(60)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/send-video", $requestPayload);

            Log::info('[ZaloNotificationService] Send video response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Send video HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'to' => $to,
                ]);

                return [
                    'success' => false,
                    'message' => "Failed to send video: HTTP {$response->status()}",
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('[Zalo] Send video exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send voice/audio message to Zalo
     */
    public function sendVoice(string $to, string $voiceUrl, string $type = 'user', ?int $accountId = null, ?int $ttl = 0): array
    {
        try {
            Log::info('[Zalo] Sending voice', [
                'to' => $to,
                'voice_url' => $voiceUrl,
                'type' => $type,
                'accountId' => $accountId,
            ]);

            if (!$this->apiKey) {
                Log::error('[Zalo] API key not configured');
                return [
                    'success' => false,
                    'message' => 'Zalo API key not configured',
                ];
            }

            $requestPayload = [
                'to' => $to,
                'voiceUrl' => $voiceUrl,
                'type' => $type,
                'ttl' => $ttl ?? 0,
            ];

            Log::info('[ZaloNotificationService] Sending voice request to zalo-service', [
                'url' => "{$this->baseUrl}/api/message/send-voice",
                'payload' => $requestPayload,
                'accountId' => $accountId,
            ]);

            $response = Http::timeout(60)->withHeaders($this->buildHeaders($accountId))
                ->post("{$this->baseUrl}/api/message/send-voice", $requestPayload);

            Log::info('[ZaloNotificationService] Send voice response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('[Zalo] Send voice HTTP error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'to' => $to,
                ]);

                return [
                    'success' => false,
                    'message' => "Failed to send voice: HTTP {$response->status()}",
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('[Zalo] Send voice exception', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

}
