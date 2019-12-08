<?php

use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    // return $router->app->version();
    // return \Carbon\Carbon::now()->format('Y-m-d H:m:s.SSS').' '.\Carbon\Carbon::now()->timestamp;
    return Redis::keys('user:*');
});

$router->group(['prefix' => 'customer'], function () use ($router)
{
    $router->post('/', 'Customer@register');
    $router->group(['prefix' => '{customer_id}'], function () use ($router)
    {
        $router->get('/', 'Customer@detail');
        $router->put('/', 'Customer@update');
        $router->get('transactions', 'Transaction@all');
    });
});

$router->group(['prefix' => 'transactions'], function () use ($router)
{
    $router->post('/', 'Transaction@make');
    $router->get('{transaction_id}', 'Transaction@detail');
});