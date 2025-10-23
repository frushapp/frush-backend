<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Models\BusinessSetting;
use App\Models\EmailVerifications;
use App\Models\User;
use App\Models\UserOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class CustomerAuthController extends Controller
{
    public function verify_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'otp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $user = User::where('phone', $request->phone)->first();
        if ($user) {
            if ($user->is_phone_verified) {
                return response()->json([
                    'message' => trans('messages.phone_number_is_already_varified')
                ], 200);
            }

            if (env('APP_MODE') == 'demo') {
                if ($request['otp'] == "1234") {
                    $user->is_phone_verified = 1;
                    $user->save();

                    return response()->json([
                        'message' => trans('messages.phone_number_varified_successfully'),
                        'otp' => 'inactive'
                    ], 200);
                }
                return response()->json([
                    'message' => trans('messages.phone_number_and_otp_not_matched')
                ], 404);
            }

            $data = DB::table('phone_verifications')->where([
                'phone' => $request['phone'],
                'token' => $request['otp'],
            ])->first();

            if ($data) {
                DB::table('phone_verifications')->where([
                    'phone' => $request['phone'],
                    'token' => $request['otp'],
                ])->delete();

                $user->is_phone_verified = 1;
                $user->save();

                return response()->json([
                    'message' => trans('messages.phone_number_varified_successfully'),
                    'otp' => 'inactive'
                ], 200);
            } else {
                return response()->json([
                    'message' => trans('messages.phone_number_and_otp_not_matched')
                ], 404);
            }
        }
        return response()->json([
            'message' => trans('messages.not_found')
        ], 404);
    }

    public function check_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }


        if (BusinessSetting::where(['key' => 'email_verification'])->first()->value) {
            $token = rand(1000, 9999);
            DB::table('email_verifications')->insert([
                'email' => $request['email'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            try {
                if (config('mail.status')) {
                    Mail::to($request['email'])->send(new EmailVerification($token));
                }
            } catch (\Exception $ex) {
                info($ex);
            }


            return response()->json([
                'message' => 'Email is ready to register',
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Email is ready to register',
                'token' => 'inactive'
            ], 200);
        }
    }

    public function verify_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $verify = EmailVerifications::where(['email' => $request['email'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            $verify->delete();
            return response()->json([
                'message' => trans('messages.token_varified'),
            ], 200);
        }

        $errors = [];
        array_push($errors, ['code' => 'token', 'message' => trans('messages.token_not_found')]);
        return response()->json(
            ['errors' => $errors],
            404
        );
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:6',
        ], [
            'f_name.required' => 'The first name field is required.',
            'l_name.required' => 'The last name field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
        $user = User::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

        $msgresult = "";

        if ($customer_verification && env('APP_MODE') != 'demo') {
            $otp = rand(1000, 9999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request['phone']],
                [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $cphone = $request['phone'];


            $testphone = $cphone;
            $firstThreeChars = substr($request['phone'], 0, 3);

            if ($firstThreeChars === "+91") {
            } else {

                $testphone =  '+91' . $request['phone'];
            }

            $c = $this->send_sms($testphone, $otp);

            // Mail::to($request['email'])->send(new EmailVerification($otp));
            // $response = SMS_module::send($request['phone'],$otp);
            // if($response != 'success')
            // {
            //     $errors = [];
            //     array_push($errors, ['code' => 'otp', 'message' => trans('messages.faield_to_send_sms')]);
            //     return response()->json([
            //         'errors' => $errors
            //     ], 405);
            // }
        } else if (env('APP_MODE') == 'demo') {
            $otp = rand(1000, 9999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $request['phone']],
                [
                    'token' => $otp,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $cphone = $request['phone'];

            $testphone = $cphone;
            $firstThreeChars = substr($request['phone'], 0, 3);

            if ($firstThreeChars === "+91") {
            } else {

                $testphone =  '+91' . $request['phone'];
            }

            $c = $this->send_sms($testphone, $otp);
        }
        // try
        // {
        //     Mail::to($request->email)->send(new \App\Mail\CustomerRegistration($request->f_name.' '.$request->l_name));
        // }
        // catch(\Exception $ex)
        // {
        //     info($ex);
        // }

        return response()->json(['token' => $token, 'otp' => $otp, 'is_phone_verified' => 0, 'phone_verify_end_url' => "api/v1/auth/verify-phone"], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $testphone = "";
        $firstThreeChars = substr($request->phone, 0, 3);

        if ($firstThreeChars === "+91") {
        } else {

            $testphone =  '+91' . $request->phone;
        }

        $data = [
            'phone' => $request->phone,
            'password' => $request->password
        ];
        $data_other = [
            'phone' => $testphone,
            'password' => $request->password
        ];

        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;
        if (Auth::attempt($data)) {
            $user = auth()->user();
            $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
            if (!auth()->user()->status) {
                $errors = [];
                array_push($errors, ['code' => 'auth-003', 'message' => trans('messages.your_account_is_blocked')]);
                return response()->json([
                    'errors' => $errors
                ], 403);
            }
            if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                $otp = rand(1000, 9999);

                DB::table('phone_verifications')->updateOrInsert(
                    ['phone' => $request['phone']],
                    [
                        'token' => $otp,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                // $c = $this->send_sms($request['phone'] , $otp);


                $response = SMS_module::send($request['phone'], $otp);
                if ($response != 'success') {

                    $errors = [];
                    array_push($errors, ['code' => 'otp', 'message' => trans('messages.faield_to_send_sms')]);
                    return response()->json([
                        'errors' => $errors
                    ], 405);
                }
            }

            return response()->json(['token' => $token, 'is_phone_verified' => auth()->user()->is_phone_verified], 200);
        } else {
            if (Auth::attempt($data_other)) {
                $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
                if (!auth()->user()->status) {
                    $errors = [];
                    array_push($errors, ['code' => 'auth-003', 'message' => trans('messages.your_account_is_blocked')]);
                    return response()->json([
                        'errors' => $errors
                    ], 403);
                }
                if ($customer_verification && !auth()->user()->is_phone_verified && env('APP_MODE') != 'demo') {
                    $otp = rand(1000, 9999);

                    DB::table('phone_verifications')->updateOrInsert(
                        ['phone' => $testphone],
                        [
                            'token' => $otp,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    // $c = $this->send_sms($request['phone'] , $otp);


                    $response = SMS_module::send($testphone, $otp);
                    if ($response != 'success') {

                        $errors = [];
                        array_push($errors, ['code' => 'otp', 'message' => trans('messages.faield_to_send_sms')]);
                        return response()->json([
                            'errors' => $errors
                        ], 405);
                    }
                }

                return response()->json(['token' => $token, 'is_phone_verified' => auth()->user()->is_phone_verified], 200);
            } else {
                $errors = [];
                array_push($errors, ['code' => 'auth-001', 'message' => trans('messages.Unauthorized')]);
                return response()->json([
                    'errors' => $errors
                ], 401);
            }
        }
    }
    public function send_otp_mobile(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'mobile' => 'required|digits:10'
        ]);
        if ($validation->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validation)], 403);
        }
        $mobile = $request->mobile;

        // Generate a random 6-digit OTP
        $otp = rand(1000, 9999);

        // Set OTP validity time (e.g., 5 minutes)
        $validTill = Carbon::now()->addMinutes(5);
        $isExists = User::where('phone', $mobile)->first();
        $userOtp = UserOtp::updateOrCreate(
            ['mobile' => $mobile],
            [
                'user_id' => $isExists ? $isExists->id : null,
                'otp' => $otp,
                'valid_till' => $validTill,
                'is_verified' => '0',
            ]
        );
        return response()->json([
            'message' => 'OTP sent successfully.',
            'otp' => $otp, // ⚠️ remove in production
            'valid_till' => $validTill->toDateTimeString(),
        ], 200);
    }
    public function verify_otp_mobile(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
            'otp' => 'required|digits:4',
        ]);

        if ($validation->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validation)], 403);
        }

        $mobile = $request->mobile;
        $otp = $request->otp;

        // Find OTP record
        $userOtp = UserOtp::where('mobile', $mobile)
            ->where('otp', $otp)
            ->latest()
            ->first();

        if (!$userOtp) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        // Check if OTP expired
        if (Carbon::now()->gt(Carbon::parse($userOtp->valid_till))) {
            return response()->json(['message' => 'OTP has expired.'], 400);
        }

        // Mark as verified
        $userOtp->update(['is_verified' => '1']);

        // Find or create user by phone number
        $user = User::firstOrCreate(
            ['phone' => $mobile],
            [
                'name' => 'User_' . substr($mobile, -4), // default name
                'password' => bcrypt('password'), // dummy password
                'login_medium' => 'mobile',
            ]
        );

        // Generate Passport token
        $tokenResult = $user->createToken('authToken')->accessToken;

        // Optionally delete the OTP record to prevent reuse
        $userOtp->delete();

        return response()->json([
            'message' => 'OTP verified successfully.',
            'user' => $user,
            'token' => $tokenResult,
        ], 200);
    }
    function formatPhoneString($input)
    {
        // Check if the string is exactly 10 characters
        if (strlen($input) == 10) {
            // Add prefix '91' if it's exactly 10 characters
            return '91' . $input;
        } elseif (strlen($input) > 10) {
            // If the string has more than 10 characters, remove extra prefix and replace with '91'
            $input = substr($input, -10); // Get the last 10 characters
            return '91' . $input;
        } else {
            // Return as is if the string is less than 10 characters
            return $input;
        }
    }
    function send_sms($cphone, $otp)
    {
        $recipients[0] = [
            // "mobiles" => substr($cphone, 1),
            "mobiles" => $this->formatPhoneString($cphone),
            "var1" => $otp
        ];
        $params = [
            'template_id' => "6572ee39d6fc05081a2b1492",
            'recipients' => $recipients,

        ];

        // $postdata = json_encode($params);

        $url = "https://control.msg91.com/api/v5/flow/";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'authkey' => '306759AN7Of31kVa5ee087bfP1'
        ])->post($url, $params);

        // Get the response body
        $data = $response->json();
        // print_r(substr($cphone, 1));
        // print_r(json_encode($params));
        return 0;

        // $ch = curl_init();

        // curl_setopt($ch, CURLOPT_URL, $datacxurl);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); // Set the POST data

        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: application/json',
        //     'authkey : 306759AN7Of31kVa5ee087bfP1',
        // ]);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $response = curl_exec($ch);
        // $msgresult = json_decode($response);
        // curl_close($ch);
        // print_r($msgresult);
        // return 0;
    }
}
