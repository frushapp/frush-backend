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

            foreach ($banners as $banner) {
                if ($banner->food) {
                    $formatted = Helpers::product_data_formatting([$banner->food], true, true, 'en')[0];

                    // force convert object → array
                    $banner->setRelation('food', (array) $formatted);
                }
            }


            return response()->json([
                'success' => 1,
                'count' => $banners->count(),
                'banners' => $banners
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
