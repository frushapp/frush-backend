<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\ZoneScope;

class Banner extends Model
{
    use HasFactory;
    protected $casts = [
        'data' => 'integer',
    ];
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '=', 1);
    }
    public function food()
    {
        return $this->belongsTo(Food::class, 'data', 'id');
    }
    protected static function booted()
    {
        static::addGlobalScope(new ZoneScope);
    }
}
