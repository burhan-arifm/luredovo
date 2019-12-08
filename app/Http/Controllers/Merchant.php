<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class Customer extends Controller
{
    public function register(Request $request)
    {
        $merchant->name         = $request->input('name');
        $merchant->phone        = $request->input('phone');
        $merchant->email        = $request->input('email');
        $merchant->balance      = $request->input('balance', 0);
        $merchant->created_at   = Carbon::now();

        Redis::hMSet("merchant:$phone", [
            'name'                  => $merchant->name,
            'email'                 => $merchant->email,
            'balance'               => $merchant->balance,
            'premium'               => $merchant->premium,
            'transactions_history'  => "history:$phone",
            'created_at'            => $merchant->created_at,
            'updated_at'            => $merchant->created_at
        ]);

        return response()->json([ 'merchant' => $merchant ], 201);
    }

    public function all()
    {
        $merchant_keys = Redis::keys('merchant:*');
        $merchants = [];

        for ($index=0; $index < count($merchant_keys); $index++)
        {
            array_push($merchants, merchant($merchant_keys[$index]));
        }

        return response()->json([ 'merchants' => $merchants ], 200);
    }

    public function detail($merchant_id)
    {
        return response()->json([ 'merchant' => merchant($merchant_id) ], 200);
    }

    public function update(Request $request, $merchant_id)
    {
        $merchant = merchant($merchant_id);
    }

    public function merchant($id)
    {
        $merchant_key = "merchant:".$id;

        $merchant->name         = Redis::hGet($merchant_key, 'name');
        $merchant->phone        = Redis::hGet($merchant_key, 'phone');
        $merchant->email        = Redis::hGet($merchant_key, 'email');
        $merchant->balance      = Redis::hGet($merchant_key, 'balance');
        $merchant->premium      = Redis::hGet($merchant_key, 'premium');
        $merchant->created_at   = Redis::hGet($merchant_key, 'created_at');

        return $merchant;
    }
}
