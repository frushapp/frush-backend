<?php

namespace App\Models;

use App\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $casts = [
        'min_purchase' => 'float',
        'max_discount' => 'float',
        'discount' => 'float',
        'limit' => 'integer',
        'prerequisite_coupon_id' => 'integer',
        'prerequisite_uses_required' => 'integer',
    ];
    
    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }
    
    /**
     * Get the prerequisite coupon that must be used before this coupon becomes available.
     */
    public function prerequisiteCoupon()
    {
        return $this->belongsTo(Coupon::class, 'prerequisite_coupon_id');
    }
    
    /**
     * Get coupons that require this coupon as a prerequisite.
     */
    public function dependentCoupons()
    {
        return $this->hasMany(Coupon::class, 'prerequisite_coupon_id');
    }
    
    // protected static function booted()
    // {
    //     if(auth('vendor')->check())
    //     {
    //         static::addGlobalScope(new RestaurantScope);
    //     } 
    // }
}
