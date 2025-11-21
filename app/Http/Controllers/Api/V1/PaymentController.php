<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Models\Order;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function payment_new(Request $request)
    {
        $order_id = $request['order_id'];
        $payment_id = $request['payment_id'];
        $order = Order::where(['id' => $order_id])->first();
        $api = new Api(config('razor.razor_key'), config('razor.razor_secret'));
        $payment = $api->payment->fetch($payment_id);
        if (!empty($payment_id)) {
            try {

                // if($payment["status"]=="Authorized" || $payment["status"]=="authorized");{
                $api->payment->fetch($payment_id)->capture(array('amount' => $payment['amount']));
                // }

                $order = Order::where(['id' => $order_id])->first();
                $tr_ref = $payment_id;

                $order->transaction_reference = $tr_ref;
                $order->payment_method = 'razor_pay';
                $order->payment_status = 'paid';
                $order->order_status = 'pending';
                $order->confirmed = now();
                $order->save();
                // if ($order->wallet_discount_amount > 0) {
                //     CustomerLogic::create_wallet_transaction(
                //         $order->user_id,
                //         $order->wallet_discount_amount,
                //         'order_place',
                //         $order->id
                //     );
                // }
                // -----------------------------------
                // APPLY REFERRAL CASHBACK (ONLY NOW)
                // -----------------------------------
                // $referCashBackSetting = BusinessSetting::where('key', 'first_order_referral_cash_back')->first();
                // $cashbackAmount = $referCashBackSetting ? $referCashBackSetting->value : 0;

                // $customer = User::find($order->user_id);

                // $previousOrders = Order::where('user_id', $customer->id)
                //     ->where('payment_status', 'paid')     // Only count paid completed orders
                //     ->where('id', '!=', $order->id)
                //     ->count();

                // $isFirstOrder = ($previousOrders == 0);

                // if ($isFirstOrder && $cashbackAmount > 0 && $customer->parent_id != null) {

                //     $referrer = User::find($customer->parent_id);

                //     if ($referrer) {

                //         // Cashback for referrer
                //         CustomerLogic::create_wallet_transaction(
                //             $referrer->id,
                //             $cashbackAmount,
                //             'referral_cash_back',
                //             null
                //         );

                //         // Cashback for customer
                //         CustomerLogic::create_wallet_transaction(
                //             $customer->id,
                //             $cashbackAmount,
                //             'referral_cash_back',
                //             null
                //         );
                //     }
                // }
                Helpers::send_order_notification($order);
            } catch (\Exception $e) {
                $orderModel = Order::find($order);
                // Proper model update so events fire
                $orderModel->payment_method = 'razor_pay';
                $orderModel->order_status   = 'failed';
                $orderModel->failed         = now();
                $orderModel->save();

                if ($orderModel->callback !== null) {
                    return response()->json(['status' => "Failed"], 500);
                } else {
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
    public function payment_failed(Request $request, $oid)
    {
        $orderModel = Order::find($oid);
        $orderModel->payment_method = 'razor_pay';
        $orderModel->order_status   = 'failed';
        $orderModel->failed         = now();
        $orderModel->save();
        return response()->json(['status' => "Success"], 200);
    }
}