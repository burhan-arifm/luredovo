<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class Transaction extends Controller
{
    public function make(Request $request, $customer_id)
    {
        $this->validate($request, [
            'receiver_type' => 'required',
            'receiver'      => 'required',
            'amount'        => 'required',
            'description'   => 'required'
        ]);
        
        $transaction                = new \stdClass();
        $transaction->id            = "transaction:".Carbon::now()->timestamp;
        $transaction->sender        = "customer:$customer_id";
        $transaction->receiver      = $request->input('receiver_type').':'.$request->input('receiver');
        $transaction->amount        = $request->input('amount');
        $transaction->promo         = $request->input('promo', 'false');
        $transaction->description   = $request->input('description');
        $transaction->created_at    = Carbon::now()->format('Y-m-d H:m:s');

        Redis::hMSet($transaction->id, [
            'sender'        => $transaction->sender,
            'receiver'      => $transaction->receiver,
            'amount'        => $transaction->amount,
            'promo'         => $transaction->promo,
            'description'   => $transaction->description,
            'created_at'    => $transaction->created_at,
        ]);
        
        Redis::hSet("customer:$customer_id", 'balance', Redis::hGet("customer:$customer_id", 'balance') - $request->amount);
        Redis::rPush("history:$customer_id", "transaction:$timestamp");
        
        if (strpos($request->receiver, 'customer:')) {
            Redis::hSet($request->receiver, 'balance', Redis::hGet($request->receiver, 'balance') + $request->amount);
            Redis::rPush($request->receiver, "transaction:$timestamp");
        }

        return response()->json([ 'transaction' => $transaction ], 201);
    }

    public function all($customer_id)
    {
        $histories = [];

        while (count($histories) < Redis::lLen("history:$customer_id")) {
            $transaction->id = Redis::lPop("history:$customer_id");
            $sender = Redis::hGet($transaction->id, 'sender');
            $receiver = Redis::hGet($transaction->id, 'receiver');
            if ($sender != "customer:$customer_id") {
                $transaction->sender->id    = Redis::hGet($sender, 'id');
                $transaction->sender->name  = Redis::hGet($sender, 'name');
                $transaction->sender->phone = Redis::hGet($sender, 'phone');
            }
            if ($receiver != "customer:$customer_id") {
                $transaction->sender->id      = Redis::hGet($sender, 'id');
                $transaction->receiver->name  = Redis::hGet($receiver, 'name');
                $transaction->receiver->phone = Redis::hGet($receiver, 'phone');
            }
            $transaction->amount = Redis::hGet($transaction->id, 'amount');
            $transaction->description = Redis::hGet($transaction->id, 'description');
            array_push($histories, $transaction);
            Redis::rPush("history:$customer_id", $transaction->id);
        }

        $customer->id                   = "customer:$customer_id";
        $customer->name                 = Redis::hGet("customer:$customer_id", 'name');
        $customer->phone                = Redis::hGet("customer:$customer_id", 'phone');
        $customer->email                = Redis::hGet("customer:$customer_id", 'email');
        $customer->balance              = Redis::hGet("customer:$customer_id", 'balance');
        $customer->transaction_history  = $histories;

        return response()->json([ 'customer' => $customer ], 200);
    }

    public function detail($transaction_id)
    {
        $transaction->id                = "transaction:$transaction_id";

        $sender = Redis::hGet($transaction->id, 'sender');
        $transaction->sender->id        = Redis::hGet($sender, 'id');
        $transaction->sender->name      = Redis::hGet($sender, 'name');
        $transaction->sender->phone     = Redis::hGet($sender, 'phone');

        if (strpos($request->receiver, 'customer:')) {
            $receiver = Redis::hGet($transaction->id, 'receiver');
            $transaction->receiver->id      = Redis::hGet($receiver, 'id');
            $transaction->receiver->name    = Redis::hGet($receiver, 'name');
            $transaction->receiver->phone   = Redis::hGet($receiver, 'phone');
        } else {
            $transaction->receiver      = Redis::hGet($transaction->id, 'receiver');
        }

        $transaction->amount            = Redis::hGet($transaction->id, 'amount');
        $transaction->description       = Redis::hGet($transaction->id, 'description');

        return response()->json([ 'transaction' => $transaction ], 200);
    }
}
