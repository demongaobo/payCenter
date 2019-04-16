<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/4/11 0011
 * Time: 下午 2:13
 */

/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register API routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | is assigned the "api" middleware group. Enjoy building your API!
 |
 */

$api = app('Dingo\Api\Routing\Router');
$api->version('v1', [
    'namespace' => 'App\Shop\Controllers\Api\v1',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api.throttle'
], function($api) {

    $apiMap = config('apimapshop');

    $method = request()->input('method');

    if (isset($apiMap[$method])) {
        $api->post('/',  $apiMap[$method]);
    }

});

