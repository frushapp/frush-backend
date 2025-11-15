<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function transactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $paginator = WalletTransaction::where('user_id', $request->user()->id)->latest()->paginate($request->limit, ['*'], 'page', $request->offset);

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request->limit,
            'offset' => $request->offset,
            'data' => $paginator->items()
        ];
        return response()->json($data, 200);
    }
    public function createTransaction(Request $request)
    {
        $data = [
            'user_id' => '1',
            'credit' => '1',
            'debit' => '1',
            'admin_bonus' => '0',
            'balance' => '0',
            'reference' => '0'
        ];
        $transaction = WalletTransaction::create($data);
        return response()->json(['message' => 'Transaction created successfully', 'transaction' => $transaction], 201);
    }
}
