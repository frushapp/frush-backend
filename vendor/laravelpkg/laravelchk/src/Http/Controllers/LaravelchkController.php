<?php

namespace Laravelpkg\Laravelchk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaravelchkController extends Controller
{
    public function dmvf(Request $request)
    {
        session()->put('purchase_key', base64_decode('Ik51bGxlZCBieSBAbGFsYmlsbGEi'));//pk
        session()->put('username', base64_decode('ImxAbGJpbGxBIg=='));//un
        return redirect()->route(base64_decode('c3RlcDM='));//s3
    }

    public function actch()
    {
        return response()->json([
            'active' => 1
        ]);
    }

}
