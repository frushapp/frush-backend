<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\SMS_module;
use Illuminate\Support\Facades\Http;

class PasswordResetController extends Controller
{
    public function reset_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $testphone=$request['phone'];
        $firstThreeChars = substr($request['phone'] , 0, 3);
        
        if ($firstThreeChars === "+91") {
            
        } else {
            
            $testphone =  '+91'.$request['phone'];

        }

        $customer = User::Where(['phone' => $request['phone']])->first();
        $customerWithcode = User::Where(['phone' => $testphone])->first();
        

        if (isset($customer)) {
            if(env('APP_MODE')=='demo')
            {
                return response()->json(['message' => trans('messages.otp_sent_successfull')], 200);
            }
            $token = rand(1000,9999);
            DB::table('password_resets')->insert([
                'email' => $customer['email'],
                'token' => $token,
                'created_at' => now(),
            ]);
            // Mail::to($customer['email'])->send(new \App\Mail\PasswordResetMail($token, $customer->f_name));
            // return response()->json(['message' => 'Email sent successfully.'], 200);
            $this->send_sms($testphone,$customer['f_name'] , $token);
            // $response = SMS_module::send($request['phone'],$token);
            // if($response == 'success')
            // {
                return response()->json(['code'=>$token , 'message' => trans('messages.otp_sent_successfull')], 200);
            // }
            // else
            // {
            //     return response()->json([
            //         'errors' => [
            //             ['code' => 'otp', 'message' => trans('messages.failed_to_send_sms')]
            //     ]], 405);
            // }
        }
        else if (isset($customerWithcode)) {
            if(env('APP_MODE')=='demo')
            {
                return response()->json(['message' => trans('messages.otp_sent_successfull')], 200);
            }
            $token = rand(1000,9999);
            DB::table('password_resets')->insert([
                'email' => $customerWithcode['email'],
                'token' => $token,
                'created_at' => now(),
            ]);
            // Mail::to($customer['email'])->send(new \App\Mail\PasswordResetMail($token, $customer->f_name));
            // return response()->json(['message' => 'Email sent successfully.'], 200);
            $this->send_sms($testphone,$customerWithcode['f_name'] , $token);
            // $response = SMS_module::send($request['phone'],$token);
            // if($response == 'success')
            // {
                return response()->json(['code'=>$token , 'message' => trans('messages.otp_sent_successfull')], 200);
            // }
            // else
            // {
            //     return response()->json([
            //         'errors' => [
            //             ['code' => 'otp', 'message' => trans('messages.failed_to_send_sms')]
            //     ]], 405);
            // }
        }
        else{
            
            return response()->json(['errors' => [
                ['code' => 'not-found', 'message' => 'Phone number not found!']
            ]], 404);
        }
    }

    public function verify_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'reset_token'=> 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        
        $testphone=$request->phone;
        $firstThreeChars = substr($request->phone , 0, 3);
        
        if ($firstThreeChars === "+91") {
            
        } else {
            
            $testphone =  '+91'.$request->phone;

        }
        
        
        $user=User::where('phone', $request->phone)->first();
        $userOther=User::where('phone', $testphone)->first();
        
        if(isset($user)){
            if(env('APP_MODE')=='demo')
            {
                if($request['reset_token']=="1234")
                {
                    return response()->json(['message'=>"OTP found, you can proceed"], 200);
                }
                return response()->json(['errors' => [
                    ['code' => 'invalid', 'message' => 'Invalid OTP.']
                ]], 400);
            }
    
            $data = DB::table('password_resets')->where(['token' => $request['reset_token'],'email'=>$user->email])->first();
            if (isset($data)) {
                return response()->json(['message'=>"OTP found, you can proceed"], 200);
            }
        }
        else if(isset($userOther)){
            if(env('APP_MODE')=='demo')
            {
                if($request['reset_token']=="1234")
                {
                    return response()->json(['message'=>"OTP found, you can proceed"], 200);
                }
                return response()->json(['errors' => [
                    ['code' => 'invalid', 'message' => 'Invalid OTP.']
                ]], 400);
            }
    
            $data = DB::table('password_resets')->where(['token' => $request['reset_token'],'email'=>$userOther->email])->first();
            if (isset($data)) {
                return response()->json(['message'=>"OTP found, you can proceed"], 200);
            }
        }
        else {
            return response()->json(['errors' => [
                ['code' => 'not-found', 'message' => 'Phone number not found!']
            ]], 404);
        }

        
        return response()->json(['errors' => [
            ['code' => 'invalid', 'message' => 'Invalid OTP.']
        ]], 400);
    }

    public function reset_password_submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'reset_token'=> 'required',
            'password'=> 'required|min:6',
            'confirm_password'=> 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $testphone=$request['phone'];
        $firstThreeChars = substr($request['phone'] , 0, 3);
        if ($firstThreeChars === "+91") {
        } else {
            $testphone =  '+91'.$request['phone'];
        }

        if(env('APP_MODE')=='demo')
        {
            if($request['reset_token']=="1234")
            {
                DB::table('users')->where(['phone' => $request['phone']])->update([
                    'password' => bcrypt($request['confirm_password'])
                ]);
                return response()->json(['message' => 'Password changed successfully.'], 200);
            }
            return response()->json([
                'message' => 'Phone number and otp not matched!'
            ], 404);
        }

        
        $data = DB::table('password_resets')->where(['token' => $request['reset_token']])->first();
        if (isset($data)) {
            if ($request['password'] == $request['confirm_password']) {
                DB::table('users')->where(['email' => $data->email])->update([
                    'password' => bcrypt($request['confirm_password'])
                ]);
                DB::table('password_resets')->where(['token' => $request['reset_token']])->delete();
                return response()->json(['message' => 'Password changed successfully.'], 200);
            }
            return response()->json(['errors' => [
                ['code' => 'mismatch', 'message' => 'Password did,t match!']
            ]], 401);
        }
        return response()->json(['errors' => [
            ['code' => 'invalid', 'message' => trans('messages.invalid_otp')]
        ]], 400);
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
