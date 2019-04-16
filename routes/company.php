<?php

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
    'namespace' => 'App\Company\Controllers\Api\v1',
    'limit' => config('api.rate_limits.access.limit'),
    'expires' => config('api.rate_limits.access.expires'),
    'middleware' => 'api.throttle'
], function($api) {

    $apiMap = config('apimapcompany');

	$method = request()->input('method');
	if (isset($apiMap[$method])) {
		$api->post('/',  $apiMap[$method]);
	}
	//企业用户认证信息列表导出
	$api->any('userexport', 'CompanyUserController@export');
});

