<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\Zone;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Scopes\RestaurantScope;

class ReportController extends Controller
{
    public function order_index()
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        return view('admin-views.report.order-index');
    }

    public function day_wise_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        return view('admin-views.report.day-wise-report', compact('zone'));
    }

    public function food_wise_report(Request $request)
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }
        $from = session('from_date');
        $to = session('to_date');
        $status = session("my_report_status");


        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $restaurant_id = $request->query('restaurant_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $restaurant = is_numeric($restaurant_id) ? Restaurant::findOrFail($restaurant_id) : null;
        // $foods = \App\Models\Food::withoutGlobalScope(RestaurantScope::class)->withCount([
        //     'orders' => function($query)use( $from, $to , $status) {
        //         $query->where("order_status",$status)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        //     },
        // ])
        // ->when(isset($zone), function($query)use($zone){
        //     return $query->whereIn('restaurant_id', $zone->restaurants->pluck('id'));
        // })
        // ->when(isset($restaurant), function($query)use($restaurant){
        //     return $query->where('restaurant_id', $restaurant->id);
        // })
        // ->orderBy('orders_count', 'desc')
        // ->paginate(config('default_pagination'))->withQueryString();

        if ($restaurant_id == "all" && $zone_id == "all") {
            $foods = DB::select("
            SELECT 
                order_details.food_id,
                order_details.food_details
                order_details.variation,
                order_details.add_ons,
                order_details.price,
                SUM(order_details.quantity) AS total_qty,
                MAX(orders.zone_id) AS zone_id,
                MAX(orders.order_status) AS order_status
            FROM 
                order_details
            JOIN 
                orders ON order_details.order_id = orders.id
            WHERE 
                DATE(order_details.created_at) >= ? 
                AND DATE(order_details.created_at) <= ? 
                AND orders.zone_id = 15 
                AND orders.order_status = ?
            GROUP BY 
                order_details.food_id, 
                order_details.price;
            ", [$from , $to , $status]);

            
        } else if ($restaurant_id != "all" && $zone_id == "all") {
            $foods = DB::select("Select f.* , s.* , r.restaurant_name , r.zone_name from food f 
            RIGHT JOIN (Select food_id , SUM(quantity) as order_x_count from order_details 
            where order_id IN ( Select id from orders where 
            restaurant_id = ? and order_status = ? and DATE(created_at) >= ? and DATE(created_at) <= ? ) 
            GROUP by food_id) s on f.id = s.food_id 
            LEFT JOIN (Select restaurants.id , restaurants.name as restaurant_name , zones.name as zone_name 
            from restaurants , zones 
            where restaurants.zone_id=zones.id) r on f.restaurant_id = r.id", [$restaurant_id, $status, $from, $to]);
        } else if ($restaurant_id == "all" && $zone_id != "all") {

            $foods = DB::select("Select f.* , s.* , r.restaurant_name , r.zone_name from food f 
            RIGHT JOIN (Select food_id , SUM(quantity) as order_x_count from order_details 
            where order_id IN ( Select id from orders where 
            zone_id = ? and  order_status = ? and DATE(created_at) >= ? and DATE(created_at) <= ? ) 
            GROUP by food_id) s on f.id = s.food_id 
            LEFT JOIN (Select restaurants.id , restaurants.name as restaurant_name , zones.name as zone_name 
            from restaurants , zones 
            where restaurants.zone_id=zones.id) r on f.restaurant_id = r.id", [$zone_id, $status, $from, $to]);
        } else if ($restaurant_id != "all" && $zone_id != "all") {

            $foods = DB::select("Select f.* , s.* , r.restaurant_name , r.zone_name from food f 
            RIGHT JOIN (Select food_id , SUM(quantity) as order_x_count from order_details 
            where order_id IN ( Select id from orders where 
            zone_id = ? and restaurant_id = ? and order_status = ? and DATE(created_at) >= ? and DATE(created_at) <= ? ) 
            GROUP by food_id) s on f.id = s.food_id 
            LEFT JOIN (Select restaurants.id , restaurants.name as restaurant_name , zones.name as zone_name 
            from restaurants , zones 
            where restaurants.zone_id=zones.id) r on f.restaurant_id = r.id", [$zone_id, $restaurant_id, $status, $from, $to]);
        }


        // echo $from;
        // echo $to;
        // echo $status;
        // echo $zone_id;
        // echo $restaurant_id;
        // echo json_encode($foods);
        // die;
        return view('admin-views.report.food-wise-report', compact('zone', 'restaurant', 'foods'));
    }

    public function order_transaction()
    {
        $order_transactions = OrderTransaction::latest()->paginate(config('default_pagination'));
        return view('admin-views.report.order-transactions', compact('order_transactions'));
    }


    public function set_date(Request $request)
    {
        session()->put('from_date', date('Y-m-d', strtotime($request['from'])));
        session()->put('to_date', date('Y-m-d', strtotime($request['to'])));
        if (!empty($request['status'])) {
            session()->put('my_report_status', $request['status']);
        }
        return back();
    }

    public function food_search(Request $request)
    {
        $key = explode(' ', $request['search']);

        $from = session('from_date');
        $to = session('to_date');

        $zone_id = $request->query('zone_id', isset(auth('admin')->user()->zone_id) ? auth('admin')->user()->zone_id : 'all');
        $restaurant_id = $request->query('restaurant_id', 'all');
        $zone = is_numeric($zone_id) ? Zone::findOrFail($zone_id) : null;
        $restaurant = is_numeric($restaurant_id) ? Restaurant::findOrFail($restaurant_id) : null;
        $foods = \App\Models\Food::withoutGlobalScope(RestaurantScope::class)->withCount([
            'orders as order_count' => function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to]);
            },
        ])
            ->when(isset($zone), function ($query) use ($zone) {
                return $query->whereIn('restaurant_id', $zone->restaurants->pluck('id'));
            })
            ->when(isset($restaurant), function ($query) use ($restaurant) {
                return $query->where('restaurant_id', $restaurant->id);
            })
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            })
            ->limit(25)->get();

        return response()->json([
            'count' => count($foods),
            'view' => view('admin-views.report.partials._food_table', compact('foods'))->render()
        ]);
    }
}
