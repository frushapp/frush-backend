<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FCMService
{
    private $projectId;
    private $client;
    private $fcmUrl;
    private $serviceAccountPath;
    private $accessToken;

    public function __construct()
    {
        $this->projectId = config('fcm.project_id', 'frushapp-bb25b');
        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $this->serviceAccountPath = storage_path('firebase/firebase-service-account.json');

        // Configure Guzzle client with SSL handling for Windows/local development
        $guzzleOptions = [];
        if (config('app.env') === 'local' || config('app.debug') === true) {
            // Disable SSL verification for local development only
            $guzzleOptions['verify'] = false;
        }
        $this->client = new Client($guzzleOptions);
    }


    /**
     * Get OAuth2 access token for Firebase
     *
     * @return string|null
     */
    private function getAccessToken()
    {
        // Try to get from cache first
        $cachedToken = Cache::get('fcm_access_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        if (!file_exists($this->serviceAccountPath)) {
            Log::error('FCM Service: Firebase service account file not found at: ' . $this->serviceAccountPath);
            return null;
        }

        try {
            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

            if (!$serviceAccount) {
                Log::error('FCM Service: Invalid service account JSON');
                return null;
            }

            // Create JWT token
            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];
            $payloadEncoded = base64_encode(json_encode($payload));

            // Sign with private key
            $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
            if (!$privateKey) {
                Log::error('FCM Service: Invalid private key');
                return null;
            }

            $signatureData = $header . '.' . $payloadEncoded;
            openssl_sign($signatureData, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $signatureEncoded = $this->base64UrlEncode($signature);

            $jwt = $header . '.' . $payloadEncoded . '.' . $signatureEncoded;

            // Exchange JWT for access token
            $response = $this->client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);

            $tokenData = json_decode($response->getBody()->getContents(), true);
            $accessToken = $tokenData['access_token'] ?? null;

            if ($accessToken) {
                // Cache the token for 50 minutes (tokens last 60 minutes)
                Cache::put('fcm_access_token', $accessToken, 50 * 60);
            }

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('FCM Service: Failed to get access token - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * URL-safe base64 encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Send notification to a specific device using FCM token
     *
     * @param string $fcmToken Device FCM token
     * @param array|object $data Notification data (can be array or Eloquent model)
     * @return bool
     */
    public function sendToDevice($fcmToken, $data)
    {
        if (empty($fcmToken)) {
            Log::warning('FCM Service: Empty FCM token provided');
            return false;
        }

        // Handle both array and Eloquent model objects
        if (is_object($data)) {
            $title = $data->title ?? 'Frush';
            $description = $data->description ?? '';
            $image = $data->image ?? '';
            $orderId = $data->order_id ?? '';
            $type = $data->type ?? 'general';
        } else {
            $title = $data['title'] ?? 'Frush';
            $description = $data['description'] ?? '';
            $image = $data['image'] ?? '';
            $orderId = $data['order_id'] ?? '';
            $type = $data['type'] ?? 'general';
        }

        $message = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $description,
                ],
                'data' => [
                    'title' => $title,
                    'body' => $description,
                    'image' => strval($image ?? ''),
                    'order_id' => strval($orderId ?? ''),
                    'type' => $type,
                    'is_read' => '0',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'frush_notifications',
                        'sound' => 'default',
                        'notification_priority' => 'PRIORITY_HIGH',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Add image if provided
        if (!empty($image)) {
            $message['message']['notification']['image'] = $image;
            $message['message']['android']['notification']['image'] = $image;
        }

        return $this->sendRequest($message);
    }


    /**
     * Send notification to a topic
     *
     * @param array|object $data Notification data (can be array or Eloquent model)
     * @param string $topic Topic name
     * @param string $type Notification type
     * @return bool
     */
    public function sendToTopic($data, $topic, $type = 'general')
    {
        if (empty($topic)) {
            Log::warning('FCM Service: Empty topic provided');
            return false;
        }

        // Handle both array and Eloquent model objects
        if (is_object($data)) {
            $title = $data->title ?? 'Frush';
            $description = $data->description ?? '';
            $image = $data->image ?? '';
            $orderId = $data->order_id ?? '';
        } else {
            $title = $data['title'] ?? 'Frush';
            $description = $data['description'] ?? '';
            $image = $data['image'] ?? '';
            $orderId = $data['order_id'] ?? '';
        }

        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $description,
                ],
                'data' => [
                    'title' => $title,
                    'body' => $description,
                    'image' => strval($image ?? ''),
                    'order_id' => strval($orderId ?? ''),
                    'type' => $type,
                    'is_read' => '0',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'frush_notifications',
                        'sound' => 'default',
                        'notification_priority' => 'PRIORITY_HIGH',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'content-available' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // Add image if provided
        if (!empty($image)) {
            $message['message']['notification']['image'] = $image;
            $message['message']['android']['notification']['image'] = $image;
        }

        return $this->sendRequest($message);
    }


    /**
     * Send notification to multiple devices
     *
     * @param array $tokens Array of FCM tokens
     * @param array $data Notification data
     * @return array Results for each token
     */
    public function sendToMultipleDevices(array $tokens, array $data)
    {
        $results = [];

        foreach ($tokens as $token) {
            if (!empty($token)) {
                $results[$token] = $this->sendToDevice($token, $data);
            }
        }

        return $results;
    }

    /**
     * Send the actual request to FCM
     *
     * @param array $message The message payload
     * @return bool
     */
    private function sendRequest($message)
    {
        try {
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                Log::error('FCM Service: Could not get access token');
                return false;
            }

            $response = $this->client->post($this->fcmUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $message,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($statusCode === 200) {
                Log::info('FCM Service: Notification sent successfully', [
                    'response' => $responseBody
                ]);
                return true;
            }

            Log::error('FCM Service: Failed to send notification', [
                'status_code' => $statusCode,
                'response' => $responseBody
            ]);
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $errorBody = $response ? json_decode($response->getBody()->getContents(), true) : null;

            Log::error('FCM Service: Client exception', [
                'error' => $e->getMessage(),
                'response' => $errorBody
            ]);

            // If token is expired, clear cache and retry once
            if ($response && $response->getStatusCode() === 401) {
                Cache::forget('fcm_access_token');
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM Service: Exception while sending notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send welcome notification to new user
     *
     * @param string $fcmToken User's FCM token
     * @param string $userName User's name
     * @return bool
     */
    public function sendWelcomeNotification($fcmToken, $userName = '')
    {
        $greeting = !empty($userName) ? "Hi {$userName}! " : "Hi there! ";

        $data = [
            'title' => '🎉 Welcome to Frush!',
            'description' => $greeting . 'Welcome to Frush! Get Rs.100 OFF on your first order. Use code: FRUSH100. Order now →',
            'image' => '',
            'order_id' => '',
            'type' => 'welcome',
        ];

        return $this->sendToDevice($fcmToken, $data);
    }

    /**
     * Send promotional notification
     *
     * @param array $data Notification data with title, description, image
     * @param string $topic Topic to send to (default: all_zone_customer)
     * @return bool
     */
    public function sendPromotionalNotification($data, $topic = 'all_zone_customer')
    {
        return $this->sendToTopic($data, $topic, 'promotional');
    }

    /**
     * Check if FCM service is properly configured
     *
     * @return array Status information
     */
    public function checkConfiguration()
    {
        $status = [
            'configured' => false,
            'service_account_exists' => file_exists($this->serviceAccountPath),
            'project_id' => $this->projectId,
            'errors' => [],
        ];

        if (!$status['service_account_exists']) {
            $status['errors'][] = 'Firebase service account file not found at: ' . $this->serviceAccountPath;
            return $status;
        }

        try {
            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

            if (!$serviceAccount) {
                $status['errors'][] = 'Invalid service account JSON file';
                return $status;
            }

            if (empty($serviceAccount['client_email'])) {
                $status['errors'][] = 'Missing client_email in service account';
            }

            if (empty($serviceAccount['private_key'])) {
                $status['errors'][] = 'Missing private_key in service account';
            }

            if (empty($status['errors'])) {
                $accessToken = $this->getAccessToken();
                if ($accessToken) {
                    $status['configured'] = true;
                } else {
                    $status['errors'][] = 'Could not obtain access token';
                }
            }
        } catch (\Exception $e) {
            $status['errors'][] = 'Error reading service account: ' . $e->getMessage();
        }

        return $status;
    }
}