<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class Transaction extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function make(Request $request, $customer_id)
    {
        $timestamp = Carbon::now()->timestamp;

        Redis::hMSet("transaction:$timestamp", [
            'sender'        => $customer_id,
            'receiver'      => $request->receiver,
            'amount'        => $request->amount,
            'type'          => $request->type,
            'description'   => $request->description,
            'created_at'    => Carbon::now()->timestamp,
        ]);
        
        Redis::hSet("user:$customer_id", 'balance', Redis::hGet("user:$customer_id", 'balance') - $request->amount);
        Redis::rPush("history:$customer_id", "transaction:$timestamp");
        
        if (strpos($request->receiver, 'user:') || strpos($request->receiver, 'merchant:')) {
            Redis::hSet($request->receiver, 'balance', Redis::hGet($request->receiver, 'balance') + $request->amount);
            Redis::rPush($request->receiver, "transaction:$timestamp");
        }

        return response()->json([], 201);
    }

    public function all($customer_id)
    {
        $histories = [];

        while (count($histories) < Redis::lLen("history:$customer_id")) {
            $history_id = Redis::lPop("history:$customer_id");
            $sender = Redis::hGet($history_id, 'sender');
            if ($sender != "user:$customer_id") {
                $history->sender->phone = Redis::hGet($sender, 'phone');
            }
            $receiver = Redis::hGet($history_id, 'receiver');
            if ($receiver != "user:$customer_id") {
                $history->receiver->phone = Redis::hGet($receiver, 'phone');
            }
            $history->amount = Redis::hGet($history_id, 'amount');
            $history->type = Redis::hGet($history_id, 'type');
            $history->description = Redis::hGet($history_id, 'description');
            array_push($histories, $history);
            Redis::rPush("history:$customer_id", $history);
        }

        $customer->name                 = Redis::hGet("customer:$customer_id", 'name');
        $customer->phone                = Redis::hGet("customer:$customer_id", 'phone');
        $customer->email                = Redis::hGet("customer:$customer_id", 'email');
        $customer->balance              = Redis::hGet("customer:$customer_id", 'balance');
        $customer->transaction_history  = $histories;

        return response()->json([ 'customer' => $customer ], 200);
    }

    public function detail($transaction_id)
    {
        return Redis::hGetAll("history:$transaction_id");
    }
}
