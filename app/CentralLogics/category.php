<?php

namespace App\CentralLogics;

use App\Models\Category;
use App\Models\Food;
use App\Models\Restaurant;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products(int $category_id, int $zone_id, int $limit,int $offset, $type)
    {
        $paginator = Food::whereHas('restaurant', function($query)use($zone_id){
            return $query->where('zone_id', $zone_id);
        })
        ->where(function($query) use($category_id) {
            // Check primary category relationship
            $query->whereHas('category', function($q) use($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            })
            // Also check the category_ids JSON field for additional categories
            ->orWhereRaw("JSON_CONTAINS(category_ids, ?)", [json_encode(['id' => (int)$category_id])]);
        })
        ->active()->type($type)->latest()->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'products' => $paginator->items()
        ];
    }


    public static function restaurants(int $category_id, int $zone_id, int $limit,int $offset, $type)
    {
        $paginator = Restaurant::withOpen()->where('zone_id', $zone_id)
        ->whereHas('foods', function($query) use($category_id) {
            $query->where(function($q) use($category_id) {
                // Check primary category relationship
                $q->whereHas('category', function($subQ) use($category_id) {
                    return $subQ->whereId($category_id)->orWhere('parent_id', $category_id);
                })
                // Also check the category_ids JSON field for additional categories
                ->orWhereRaw("JSON_CONTAINS(category_ids, ?)", [json_encode(['id' => (int)$category_id])]);
            });
        })
        ->active()->type($type)->latest()->paginate($limit, ['*'], 'page', $offset);

        return [
            'total_size' => $paginator->total(),
            'limit' => $limit,
            'offset' => $offset,
            'restaurants' => $paginator->items()
        ];
    }


    public static function all_products($id, $zone_id)
    {
        $cate_ids=[];
        array_push($cate_ids,(int)$id);
        foreach (CategoryLogic::child($id) as $ch1){
            array_push($cate_ids,$ch1['id']);
            foreach (CategoryLogic::child($ch1['id']) as $ch2){
                array_push($cate_ids,$ch2['id']);
            }
        }

        // Check both category_id and category_ids JSON field for multiple categories
        return Food::where(function($query) use($cate_ids) {
            $query->whereIn('category_id', $cate_ids);
            foreach($cate_ids as $catId) {
                $query->orWhereRaw("JSON_CONTAINS(category_ids, ?)", [json_encode(['id' => (int)$catId])]);
            }
        })->get();
    }
}
