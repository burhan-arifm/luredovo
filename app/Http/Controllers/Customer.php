<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class Customer extends Controller
{
    public function register(Request $request)
    {
        $customer->name         = $request->input('name');
        $customer->phone        = $request->input('phone');
        $customer->email        = $request->input('email');
        $customer->balance      = $request->input('balance', 0);
        $customer->premium      = false;
        $customer->created_at   = Carbon::now();

        Redis::hMSet("customer:$phone", [
            'name'                  => $customer->name,
            'email'                 => $customer->email,
            'balance'               => $customer->balance,
            'premium'               => $customer->premium,
            'transactions_history'  => "history:$phone",
            'created_at'            => $customer->created_at,
            'updated_at'            => $customer->created_at
        ]);

        return response()->json([ 'customer' => $customer ], 201);
    }

    public function all()
    {
        $customer_keys = Redis::keys('customer:*');
        $customers = [];

        for ($index=0; $index < count($customer_keys); $index++)
        {
            array_push($customers, customer($customer_keys[$index]));
        }

        return response()->json([ 'customers' => $customers ], 200);
    }

    public function detail($customer_id)
    {
        return response()->json([ 'customer' => customer($customer_id) ], 200);
    }

    public function update(Request $request, $customer_id)
    {
        $customer = customer($customer_id);
    }

    public function customer($id)
    {
        $customer_key = "user:".$id;

        $customer->name         = Redis::hGet($customer_key, 'name');
        $customer->phone        = Redis::hGet($customer_key, 'phone');
        $customer->email        = Redis::hGet($customer_key, 'email');
        $customer->balance      = Redis::hGet($customer_key, 'balance');
        $customer->premium      = Redis::hGet($customer_key, 'premium');
        $customer->created_at   = Redis::hGet($customer_key, 'created_at');

        return $customer;
    }
}
