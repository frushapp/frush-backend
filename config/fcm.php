<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Firebase Cloud Messaging.
    | The project_id should match your Firebase project ID.
    | The service account file should be placed in storage/firebase/
    |
    */

    'project_id' => env('FCM_PROJECT_ID', 'rushh-6b021'),
    
    'service_account_path' => storage_path('firebase/firebase-service-account.json'),
    
    'notification_channel' => 'frush_notifications',
    
    'topics' => [
        'all_customers' => 'all_zone_customer',
        'all_deliverymen' => 'all_zone_delivery_man',
        'all_restaurants' => 'all_zone_restaurant',
    ],
    
    'welcome_notification' => [
        'enabled' => true,
        'title' => '🎉 Welcome to Frush!',
        'body' => 'Welcome to Frush! Get Rs.100 OFF on your first order. Use code: FRUSH100. Order now →',
    ],
];
