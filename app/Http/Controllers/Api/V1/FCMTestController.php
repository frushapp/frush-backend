<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FCMService;
use Illuminate\Http\Request;

class FCMTestController extends Controller
{
    /**
     * Check FCM configuration status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus()
    {
        try {
            $fcmService = new FCMService();
            $status = $fcmService->checkConfiguration();
            
            return response()->json([
                'success' => $status['configured'],
                'status' => $status,
                'message' => $status['configured'] 
                    ? 'FCM is properly configured and ready to send notifications'
                    : 'FCM configuration has issues: ' . implode(', ', $status['errors'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking FCM status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a test notification to a specific device
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'title' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        try {
            $fcmService = new FCMService();
            
            $data = [
                'title' => $request->input('title', '🔔 Test Notification'),
                'description' => $request->input('message', 'This is a test notification from Frush Admin Panel'),
                'image' => '',
                'order_id' => '',
                'type' => 'test',
            ];

            $result = $fcmService->sendToDevice($request->fcm_token, $data);

            return response()->json([
                'success' => $result,
                'message' => $result 
                    ? 'Test notification sent successfully!' 
                    : 'Failed to send test notification. Check logs for details.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a test notification to a topic
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestTopicNotification(Request $request)
    {
        $request->validate([
            'topic' => 'nullable|string',
            'title' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        try {
            $fcmService = new FCMService();
            
            $data = [
                'title' => $request->input('title', '🔔 Test Topic Notification'),
                'description' => $request->input('message', 'This is a test topic notification from Frush Admin Panel'),
                'image' => '',
                'order_id' => '',
                'type' => 'test',
            ];

            $topic = $request->input('topic', 'all_zone_customer');
            $result = $fcmService->sendToTopic($data, $topic, 'test');

            return response()->json([
                'success' => $result,
                'topic' => $topic,
                'message' => $result 
                    ? "Test notification sent to topic '{$topic}' successfully!" 
                    : 'Failed to send test topic notification. Check logs for details.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending test topic notification: ' . $e->getMessage()
            ], 500);
        }
    }
}
