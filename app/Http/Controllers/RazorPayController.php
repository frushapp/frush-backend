<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

class RazorPayController extends Controller
{
    public function payWithRazorpay()
    {
        return view('razor-pay');
    }

    public function payment(Request $request, $order_id)
    {


        // print_r($request);
        // print_r($order_id) ;       
        // die();

        $order = Order::where(['id' => $order_id])->first();
        //get API Configuration
        $api = new Api(config('razor.razor_key'), config('razor.razor_secret'));
        //Fetch payment information by razorpay_payment_id

        $payment_id = $request['razorpay_payment_id'];

        $payment = $api->payment->fetch($request['razorpay_payment_id']);

        if (count($request->all()) && !empty($request['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($request['razorpay_payment_id'])->capture(array('amount' => $payment['amount']));
                $order = Order::where(['id' => $response->description])->first();
                $tr_ref = $request['razorpay_payment_id'];

                $order->transaction_reference = $tr_ref;
                $order->payment_method = 'razor_pay';
                $order->payment_status = 'paid';
                $order->order_status = 'pending';
                $order->confirmed = now();
                $order->save();
                Helpers::send_order_notification($order);
            } catch (\Exception $e) {
                // print_r($e);
                info($e);
                Order::where('id', $order)
                    ->update([
                        'payment_method' => 'razor_pay',
                        'order_status' => 'failed',
                        'failed' => now(),
                        'updated_at' => now(),
                    ]);
                if ($order->callback != null) {
                    // return 1;
                    return redirect($order->callback . '&status=fail');
                } else {
                    // return 2;
                    return \redirect()->route('payment-fail');
                }
            }
        }

        if ($order->callback != null) {
            // return 3;
            return redirect($order->callback . '&status=success');
        } else {
            // return 4;
            return \redirect()->route('payment-success');
        }
    }

    public function payment_new(Request $request)
    {


        // print_r($request);
        // print_r($order_id) ;       
        // die();
        $order_id = $request['order_id'];
        $payment_id = $request['payment_id'];

        $order = Order::where(['id' => $order_id])->first();
        //get API Configuration
        $api = new Api(config('razor.razor_key'), config('razor.razor_secret'));
        //Fetch payment information by razorpay_payment_id


        $payment = $api->payment->fetch($payment_id);

        if (count($request->all()) && !empty($payment_id)) {
            try {
                $response = $api->payment->fetch($payment_id)->capture(array('amount' => $payment['amount']));
                $order = Order::where(['id' => $response->description])->first();
                $tr_ref = $payment_id;

                $order->transaction_reference = $tr_ref;
                $order->payment_method = 'razor_pay';
                $order->payment_status = 'paid';
                $order->order_status = 'pending';
                $order->confirmed = now();
                $order->save();
                Helpers::send_order_notification($order);
            } catch (\Exception $e) {
                // print_r($e);
                info($e);
                Order::where('id', $order)
                    ->update([
                        'payment_method' => 'razor_pay',
                        'order_status' => 'failed',
                        'failed' => now(),
                        'updated_at' => now(),
                    ]);
                if ($order->callback != null) {
                    // return 1;
                    return redirect($order->callback . '&status=fail');
                } else {
                    // return 2;
                    return \redirect()->route('payment-fail');
                }
            }
        }

        if ($order->callback != null) {
            // return 3;
            return redirect($order->callback . '&status=success');
        } else {
            // return 4;
            return \redirect()->route('payment-success');
        }
    }
}
