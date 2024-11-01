<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function payment_new(Request $request)
    {

        $order_id = $request['order_id'];
        $payment_id = $request['payment_id'];

        $order = Order::where(['id' => $order_id])->first();
        //get API Configuration
        $api = new Api(config('razor.razor_key'), config('razor.razor_secret'));
        //Fetch payment information by razorpay_payment_id


        $payment = $api->payment->fetch($payment_id);

        if (!empty($payment_id)) {
            try {
                $response = $api->payment->fetch($payment_id)->capture(array('amount' => $payment['amount']));


                print_r(json_encode($response));
                // print_r($request);

                die();


                $order = Order::where(['id' => $response->description])->first();
                $tr_ref = $payment_id;

                $order->transaction_reference = $tr_ref;
                $order->payment_method = 'razor_pay';
                $order->payment_status = 'paid';
                $order->order_status = 'pending';
                $order->confirmed = now();
                $order->save();
                // Helpers::send_order_notification($order);
            } catch (\Exception $e) {
                print_r($e);
                // info($e);
                Order::where('id', $order)
                    ->update([
                        'payment_method' => 'razor_pay',
                        'order_status' => 'failed',
                        'failed' => now(),
                        'updated_at' => now(),
                    ]);
                if ($order->callback != null) {
                    // return 1;
                    // return redirect($order->callback . '&status=fail');
                    return response()->json(['status' => "Failed"], 500);
                } else {
                    // return 2;
                    return response()->json(['status' => "Failed"], 500);
                }
            }
        }

        if ($order->callback != null) {
            // return 3;
            // return redirect($order->callback . '&status=success');
            return response()->json(['status' => "Success"], 200);
        } else {
            // return 4;
            return response()->json(['status' => "Success"], 200);
        }
    }
}
