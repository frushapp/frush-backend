<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GlobalMessageApiController extends Controller
{
    public function global_sms_api(Request $request)
    {

        $senderId= $request["senderid"];
        $phone= $request["phone"];
        $message= $request["message"];
        $client_id= $request["client_id"];
        $api_key= $request["api_key"];

        $response = Http::get("http://91.108.105.7/api/v2/SendSMS?SenderId=$senderId&Is_Unicode=false&Is_Flash=false&Message=$message&MobileNumbers=$phone&ApiKey=$api_key&ClientId=$client_id");

        if ($response->successful()) {
            // The request was successful
            return $response->json();
        } else {
            // Handle errors
            return response()->json(['error' => 'Failed to fetch data'], $response->status());
        }
    }
}
