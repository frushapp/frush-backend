<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Campaign;
use App\Models\Banner;

use App\CentralLogics\BannerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function get_banners(Request $request)
    {
        try {
            $zone_id = $request->header('zoneId');
            $query = Banner::query();
            if ($zone_id) {
                $query->where('zone_id', $zone_id);
            }
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('page')) {
                $query->where('page', $request->page);
            }
            if ($request->has('position')) {
                $query->where('position', $request->position);
            }
            if ($request->has('title')) {
                $query->where('title', 'like', "%{$request->title}%");
            }
            // $banners = $query->with('food')->orderBy('id', 'desc')->get();
            $banners = $query->with('food')->orderBy('id', 'desc')->get();
            $formatted = $banners->map(function ($banner) {
                if ($banner->food) {
                    $food = $banner->food;

                    // Convert JSON string fields to arrays safely
                    $categoryIds    = is_string($food->category_ids) ? json_decode($food->category_ids, true) : ($food->category_ids ?? []);
                    $variations     = is_string($food->variations) ? json_decode($food->variations, true) : ($food->variations ?? []);
                    $addOns         = is_string($food->add_ons) ? json_decode($food->add_ons, true) : ($food->add_ons ?? []);
                    $attributes     = is_string($food->attributes) ? json_decode($food->attributes, true) : ($food->attributes ?? []);
                    $choiceOptions  = is_string($food->choice_options) ? json_decode($food->choice_options, true) : ($food->choice_options ?? []);

                    // Format the food object manually
                    $formattedFood = [
                        'id'                     => $food->id,
                        'name'                   => $food->title ?? $food->name,
                        'description'            => $food->description,
                        'image'                  => $food->image,
                        'daily_opening_stock'    => $food->daily_opening_stock,
                        'stock'                  => $food->stock,
                        'category_id'            => $food->category_id,
                        'category_ids'           => $categoryIds,
                        'variations'             => array_map(fn($v) => ['type' => $v['type'], 'price' => (float)($v['price'] ?? 0)], $variations),
                        'add_ons'                => $addOns,
                        'attributes'             => $attributes,
                        'choice_options'         => $choiceOptions,
                        'price'                  => (float) $food->price,
                        'tax'                    => (float) $food->tax,
                        'tax_type'               => $food->tax_type,
                        'discount'               => (float) $food->discount,
                        'discount_type'          => $food->discount_type,
                        'available_time_starts'  => $food->start_time ?? $food->available_time_starts,
                        'available_time_ends'    => $food->end_time ?? $food->available_time_ends,
                        'veg'                    => $food->veg,
                        'status'                 => $food->status,
                        'restaurant_id'          => $food->restaurant_id,
                        'restaurant_name'        => $food->restaurant->name ?? null,
                        'restaurant_discount'    => $food->restaurant->discount->discount ?? 0,
                        'restaurant_opening_time' => $food->restaurant->opening_time ?? null,
                        'restaurant_closing_time' => $food->restaurant->closeing_time ?? null,
                        'schedule_order'         => $food->restaurant->schedule_order ?? false,
                        'rating_count'           => is_string($food->rating) ? array_sum(json_decode($food->rating, true) ?? []) : array_sum($food->rating ?? []),
                        'avg_rating'             => (float) ($food->avg_rating ?? 0),
                    ];

                    // Assign formatted food back to banner
                    $banner->setRelation('food', $formattedFood);
                }

                return $banner;
            });




            return response()->json([
                'success' => 1,
                'count' => $banners->count(),
                'banners' => $formatted
            ], 200);
            return response()->json([
                'success' => 1,
                'count' => $banners->count(),
                'banners' => $banners
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
