<?php

namespace App\Models;

use App\CentralLogics\CustomerLogic;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Scopes\ZoneScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'restaurant_discount_amount' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        'original_delivery_charge' => 'float',
        'user_id' => 'integer',
        'scheduled' => 'integer',
        'restaurant_id' => 'integer',
        'details_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'original_delivery_charge' => 'float',
        'delivery_gst' => 'float',
        'platform_fees' => 'float'
    ];

    public function setDeliveryChargeAttribute($value)
    {
        $this->attributes['delivery_charge'] = round($value, 3);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function delivery_history()
    {
        return $this->hasMany(DeliveryHistory::class, 'order_id');
    }

    public function dm_last_location()
    {
        // return $this->hasOne(DeliveryHistory::class, 'order_id')->latest();
        return $this->delivery_man->last_location();
    }

    public function transaction()
    {
        return $this->hasOne(OrderTransaction::class);
    }

    public function scopeAccepteByDeliveryman($query)
    {
        return $query->where('order_status', 'accepted');
    }

    public function scopePreparing($query)
    {
        return $query->whereIn('order_status', ['confirmed', 'processing', 'handover']);
    }

    public function scopeOngoing($query)
    {
        return $query->whereIn('order_status', ['accepted', 'confirmed', 'processing', 'handover', 'picked_up']);
    }

    public function scopeFoodOnTheWay($query)
    {
        return $query->where('order_status', 'picked_up');
    }

    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    // public function scopeRefundRequest($query)
    // {
    //     return $query->where('order_status','refund_requested');
    // }

    public function scopeFailed($query)
    {
        return $query->where('order_status', 'failed');
    }

    public function scopeCanceled($query)
    {
        return $query->where('order_status', 'canceled');
    }

    public function scopeDelivered($query)
    {
        return $query->where('order_status', 'delivered');
    }

    public function scopeRefunded($query)
    {
        return $query->where('order_status', 'refunded');
    }

    public function scopeSearchingForDeliveryman($query)
    {
        return $query->whereNull('delivery_man_id')->where('order_type', '=', 'delivery')->whereNotIn('order_status', ['delivered', 'failed', 'canceled', 'refund_requested', 'refunded']);
    }

    public function scopeDelivery($query)
    {
        return $query->where('order_type', '=', 'delivery');
    }

    public function scopeScheduled($query)
    {
        return $query->whereRaw('created_at <> schedule_at')->where('scheduled', '1');
    }

    public function scopeOrderScheduledIn($query, $interval)
    {
        return $query->where(function ($query) use ($interval) {
            $query->whereRaw('created_at <> schedule_at')->where(function ($q) use ($interval) {
                $q->whereBetween('schedule_at', [Carbon::now()->toDateTimeString(), Carbon::now()->addMinutes($interval)->toDateTimeString()]);
            })->orWhere('schedule_at', '<', Carbon::now()->toDateTimeString());
        })->orWhereRaw('created_at = schedule_at');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=', 'pos');
    }

    public function scopeNotpos($query)
    {
        return $query->where('order_type', '<>', 'pos');
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s', strtotime($value));
    }
    public function reverseWalletAndCashback()
    {
        // ---------------------------------------------------------
        // 1. Reverse order_place credit (wallet_used reversal)
        // ---------------------------------------------------------
        $orderPlaceTransactions = WalletTransaction::where('reference', $this->id)
            ->where('transaction_type', 'order_place')
            ->get();
        Log::info('Reversing wallet transactions for Order ID: ' . $orderPlaceTransactions);
        if ($orderPlaceTransactions->count() > 0) {

            foreach ($orderPlaceTransactions as $txn) {

                // Skip if already reversed
                $alreadyReversed = WalletTransaction::where('reference', $this->id)
                    ->where('transaction_type', 'order_place_reversal')
                    ->where('user_id', $txn->user_id)
                    ->exists();

                if (!$alreadyReversed) {
                    CustomerLogic::create_wallet_transaction(
                        $txn->user_id,
                        $txn->debit,            // ✔ ALWAYS positive
                        'order_place_reversal',  // ✔ credit type
                        $this->id
                    );
                }
            }
        }

        // ---------------------------------------------------------
        // 2. Reverse referral cashback (customer + referrer)
        // ---------------------------------------------------------
        $cashbackTransactions = WalletTransaction::where('reference', $this->id)
            ->where('transaction_type', 'referral_cash_back')
            ->get();

        if ($cashbackTransactions->count() > 0) {

            foreach ($cashbackTransactions as $txn) {

                // Skip if already reversed
                $alreadyReversed = WalletTransaction::where('reference', $this->id)
                    ->where('transaction_type', 'referral_cash_back_reversal')
                    ->where('user_id', $txn->user_id)
                    ->exists();

                if (!$alreadyReversed) {
                    CustomerLogic::create_wallet_transaction(
                        $txn->user_id,
                        -abs($txn->amount),              // debit
                        'referral_cash_back_reversal',   // reversal type
                        $this->id
                    );
                }
            }
        }
    }

    public function grantReferralCashbackIfEligible()
    {
        $customer = User::find($this->user_id);

        // must have parent (referrer)
        if (!$customer || !$customer->parent_id) {
            return;
        }

        // cashback amount from settings
        $cashbackSetting = BusinessSetting::where('key', 'first_order_referral_cash_back')->first();
        $cashbackAmount = $cashbackSetting ? (float)$cashbackSetting->value : 0;

        if ($cashbackAmount <= 0) {
            return;
        }

        // count delivered orders EXCLUDING current one
        $previousDeliveredCount = Order::where('user_id', $customer->id)
            ->where('order_status', 'delivered')
            ->where('id', '!=', $this->id)
            ->count();

        // only first delivered order
        $isFirstDelivered = ($previousDeliveredCount == 0);

        if (!$isFirstDelivered) {
            return; // already received cashback before
        }

        // get referrer
        $referrer = User::find($customer->parent_id);

        if (!$referrer) {
            return;
        }

        // grant cashback to referrer
        CustomerLogic::create_wallet_transaction(
            $referrer->id,
            $cashbackAmount,
            'referral_cash_back',
            $this->id
        );

        // grant cashback to customer
        CustomerLogic::create_wallet_transaction(
            $customer->id,
            $cashbackAmount,
            'referral_cash_back',
            $this->id
        );
    }



    protected static function booted()
    {
        static::addGlobalScope(new ZoneScope);
        static::updated(function ($order) {
            if ($order->isDirty('order_status') && $order->order_status == 'canceled') {
                $order->reverseWalletAndCashback();
            }
        });
        static::updated(function ($order) {
            if ($order->isDirty('order_status') && $order->order_status == 'canceled') {
                Log::info('RUn now');
                $order->reverseWalletAndCashback();
            }
        });


        static::updated(function ($order) {
            if ($order->isDirty('order_status') && $order->order_status == 'delivered') {
                $order->grantReferralCashbackIfEligible();
            }
        });
        static::updated(function ($order) {
            if ($order->isDirty('order_status') && $order->order_status == 'delivered') {

                foreach ($order->details as $detail) {

                    if ($detail->food && $detail->quantity > 0) {
                        // subtract quantity from food stock
                        $detail->food->decrement('stock', $detail->quantity);
                    }
                }
            }
        });
    }
}
