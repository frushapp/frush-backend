<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'f_name',
        'l_name',
        'phone',
        'email',
        'password',
        'login_medium',
        'social_id',
        'referal_code',
        'parent_referal_code',
        'parent_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'interest',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_phone_verified' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'order_count' => 'integer',
        'wallet_balance' => 'float',
        'loyalty_point' => 'integer',
    ];


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function loyalty_point_transaction()
    {
        return $this->hasMany(LoyaltyPointTransaction::class);
    }

    public function wallet_transaction()
    {
        return $this->hasMany(WalletTransaction::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Generate a unique referral code
            do {
                $code = strtoupper(Str::random(6));
            } while (User::where('referal_code', $code)->exists());

            $user->referal_code = $code;

            // If user entered a parent referral code, attach it
            if (!empty($user->parent_referal_code)) {
                $parent = User::where('referal_code', $user->parent_referal_code)->first();
                if ($parent) {
                    $user->parent_id = $parent->id;
                }
            }
        });
    }
}
