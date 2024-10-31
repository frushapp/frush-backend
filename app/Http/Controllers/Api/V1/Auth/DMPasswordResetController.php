<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\SMS_module;
use Illuminate\Support\Facades\Http;

class DMPasswordResetController extends Controller
{
    public function reset_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $deliveryman = DeliveryMan::Where(['phone' => $request['phone']])->first();

        if (isset($deliveryman)) {
            $token = rand(1000,9999);
            if(env('APP_MODE') =='demo')
            {
                return response()->json(['message' => trans('messages.otp_sent_successfull')], 200);
            }

            DB::table('password_resets')->updateOrInsert([
                'email' => $deliveryman['email'],
                'token' => $token,
                'created_at' => now(),
            ]);
            $this->send_sms($request['phone'],$deliveryman['f_name'],$token);

            // $response = SMS_module::send($request['phone'],$token);
            $response = true;
            // if($response == 'success')
            if($response)
            {
                return response()->json(['message' => trans('messages.otp_sent_successfull')], 200);
            }
            else
            {
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' => trans('messages.failed_to_send_sms')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }
        }
        $errors = [];
        array_push($errors, ['code' => 'not-found', 'message' => 'Phone number not found!']);
        return response()->json(['errors' => $errors], 404);
    }

    public function verify_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'reset_token'=> 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user=DeliveryMan::where('phone', $request->phone)->first();
        if (!isset($user)) {
            $errors = [];
            array_push($errors, ['code' => 'not-found', 'message' => 'Phone number not found!']);
            return response()->json(['errors' => $errors
            ], 404);
        }
        if(env('APP_MODE')=='demo')
        {
            if($request['reset_token'] == '1234')
            {
                return response()->json(['message'=>"Token found, you can proceed"], 200);
            }
            $errors = [];
            array_push($errors, ['code' => 'reset_token', 'message' => 'Invalid token.']);
            return response()->json(['errors' => $errors
                ], 400);
        }
        $data = DB::table('password_resets')->where(['token' => $request['reset_token'],'email'=>$user->email])->first();
        if (isset($data)) {
            return response()->json(['message'=>"Token found, you can proceed"], 200);
        }
        $errors = [];
        array_push($errors, ['code' => 'reset_token', 'message' => 'Invalid token.']);
        return response()->json(['errors' => $errors
            ], 400);
    }

    public function reset_password_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'reset_token'=> 'required',
            'password'=> 'required|min:6',
            'confirm_password'=> 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        if(env('APP_MODE')=='demo')
        {
            if($request['reset_token']=="1234")
            {
                DB::table('delivery_men')->where(['phone' => $request['phone']])->update([
                    'password' => bcrypt($request['confirm_password'])
                ]);
                return response()->json(['message' => 'Password changed successfully.'], 200);
            }
            $errors = [];
            array_push($errors, ['code' => 'invalid', 'message' => 'Invalid token.']);
            return response()->json(['errors' => $errors], 400);
        }
        $data = DB::table('password_resets')->where(['token' => $request['reset_token']])->first();
        if (isset($data)) {
            if ($request['password'] == $request['confirm_password']) {
                DB::table('delivery_men')->where(['email' => $data->email])->update([
                    'password' => bcrypt($request['confirm_password'])
                ]);
                DB::table('password_resets')->where(['token' => $request['reset_token']])->delete();
                return response()->json(['message' => 'Password changed successfully.'], 200);
            }
            $errors = [];
            array_push($errors, ['code' => 'mismatch', 'message' => 'Password did,t match!']);
            return response()->json(['errors' => $errors], 401);
        }

        $errors = [];
        array_push($errors, ['code' => 'invalid', 'message' => 'Invalid token.']);
        return response()->json(['errors' => $errors], 400);
    }
    // function send_sms($cphone,$otp){
    //         $params = [
    //             'user' => "SAFEMAX",
    //             'key' => "31e3962f54XX",
    //             'mobile' => $cphone,
    //             'message' => "Hi, Use OTP $otp to login to your Safemaxx Deliv Account. Never share the OTP(One Time Password) with anyone else. - Safemaxx Deliv",
    //             'senderid' => "SAFMAX",
    //             'accusage' => "1",
    //             'entityid' => "1201163965226743960",
    //             'tempid' => "1207164000765954460",
    //         ];
    //         $infox = http_build_query($params);
            
    //         $datacxurl = "http://mobicomm.dove-sms.com//submitsms.jsp?".$infox ;
            
    //         $ch = curl_init();

    //         curl_setopt($ch, CURLOPT_URL, $datacxurl);
    //         curl_setopt($ch, CURLOPT_HEADER, 0);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         $response = curl_exec($ch);
    //         $msgresult = json_decode($response);
    //         curl_close($ch); 
    //         return 0;
    // }
    function send_sms($cphone,$email,$otp){
            $recipients[0] = [
                    "mobiles" => substr($cphone, 1),
                    "var1" => $email,
                    "var2" => $otp
                ] ;
            $params = [
                'template_id' => "657316ead6fc05298625b1e2",
                'recipients' => $recipients,
                
            ];
            
            // $postdata = json_encode($params);
            
            $url = "https://control.msg91.com/api/v5/flow/" ;
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'authkey' => '306759AN7Of31kVa5ee087bfP1'
            ])->post($url, $params);

            // Get the response body
            $data = $response->json();

            return 0;
            
    }
}
