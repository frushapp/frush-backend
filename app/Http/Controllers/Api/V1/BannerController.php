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
        // if (!$request->hasHeader('zoneId')) {
        //     $errors = [];
        //     array_push($errors, ['code' => 'zoneId', 'message' => trans('messages.zone_id_required')]);
        //     return response()->json([
        //         'errors' => $errors
        //     ], 403);
        // }
        $zone_id= $request->header('zoneId');
        $banners = Banner::where("type","default")->get();
        return response()->json(['banners'=>$banners], 200);

        // $campaigns = Campaign::whereHas('restaurants', function($query)use($zone_id){
        //     $query->where('zone_id', $zone_id);
        // })->with('restaurants',function($query){
        //     return $query->WithOpen();
        // })->running()->active()->get();
        // try {
        //     // return response()->json(
        //     //     ['campaigns'=>Helpers::basic_campaign_data_formatting($campaigns, true),
        //     //     'banners'=>$banners], 200);
        //         return response()->json(['banners'=>$banners], 200);
        // } catch (\Exception $e) {
        //     return response()->json([], 200);
        // }
    }
}
