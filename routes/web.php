<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    return view('welcome');
});


Route::get('test/{action}', function(App\Http\Controllers\TestController $controller, $action){
    return $controller->$action();
});
Route::get('order/{action}', function(App\Order\Controllers\Api\v1\OrderController $index, $action){
    return $index->$action();
});
Route::any('order/pay/{action}', function(App\Order\Controllers\Api\v1\PayController $index, $action){
    return $index->$action();
});
Route::any('order/mini/{action}', function(App\Order\Controllers\Api\v1\MiniNotifyController $index, $action){
    return $index->$action();
});

Route::any('common/pay/{action}', function(App\Common\Controllers\Api\v1\PayController $index, $action){
    return $index->$action();
});

Route::any('common/job/{action}', function(App\Common\Controllers\Api\v1\JobController $index, $action){
    return $index->$action();
});

Route::any('common/test/{action}', function(App\Common\Controllers\Api\v1\TestController $index, $action){
    return $index->$action();
});

Route::get('users/{action}', function(App\Order\Controllers\Api\v1\UsersController $index, $action){
    return $index->$action();
});

Route::get('return/{action}', function(App\Order\Controllers\Api\v1\ReturnController $index, $action){
    return $index->$action();
});
//还机的回调

Route::any('order/giveback/{action}', function(App\Order\Controllers\Api\v1\GivebackController $index, $action){
    return $index->$action();
});

//下载文件 imeitpl imei模板文件
    Route::any('downloadTools/{action}', function(App\Tools\Controllers\DownloadController $controller, $action){
    return $controller->$action();
});

//下载文件 imeitpl imei模板文件
Route::any('download/{action}', function(App\Warehouse\Controllers\DownloadController $controller, $action){
    return $controller->$action();
});


// 宅急送方位入口
Route::any('zhaijisong/{action}', function(App\Warehouse\Controllers\ZhaijisongController $controller, $action){
    return $controller->$action();
});

// 评论入口
Route::any('comment/{action}', function(App\Comment\Controllers\TestController $controller, $action){
    return $controller->$action();
});