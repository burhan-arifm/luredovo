<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class Customer extends Controller
{
    public function register(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required',
            'phone' => 'required',
            'email' => 'required|email'
        ]);
        
        $customer               = new \stdClass();
        $customer->id           = "customer:".$request->input('phone');
        $customer->name         = $request->input('name');
        $customer->phone        = $request->input('phone');
        $customer->email        = $request->input('email');
        $customer->balance      = $request->input('balance', 0);
        $customer->premium      = 'false';
        $customer->created_at   = Carbon::now()->format('Y-m-d H:m:s');

        Redis::hMSet($customer->id, [
            'name'                  => $customer->name,
            'phone'                 => $customer->phone,
            'email'                 => $customer->email,
            'balance'               => $customer->balance,
            'premium'               => $customer->premium,
            'transactions_history'  => "history:$customer->phone",
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
            array_push($customers, $this->customer($customer_keys[$index]));
        }

        return response()->json([ 'customers' => $customers ], 200);
    }

    public function detail($customer_id)
    {
        if (Redis::keys("customer:$customer_id")) {
            return response()->json([ 'customer' => $this->customer("customer:$customer_id") ], 200);
        } else {
            return response()->json([ 'err_message' => "Customer not found." ], 404);
        }
        
    }

    public function update(Request $request, $customer_id)
    {
        $customer = $this->customer($customer_id);
    }

    public function customer($id)
    {
        $customer = new \stdClass();

        $customer->id           = $id;
        $customer->name         = Redis::hGet($customer->id, 'name');
        $customer->phone        = Redis::hGet($customer->id, 'phone');
        $customer->email        = Redis::hGet($customer->id, 'email');
        $customer->balance      = Redis::hGet($customer->id, 'balance');
        $customer->premium      = Redis::hGet($customer->id, 'premium');
        $customer->created_at   = Redis::hGet($customer->id, 'created_at');

        return $customer;
    }
}
