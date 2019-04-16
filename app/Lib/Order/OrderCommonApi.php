<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/4/11 0011
 * Time: 下午 2:44
 * content: 订单相关信息获取接口
 */
namespace App\Lib\Order;
use \App\Lib\BaseCommonApi;

class OrderCommonApi extends BaseCommonApi {

    /**
     * Author: heaven
     * 后台订单列表查询接口
     */
    public static function getOrderList($param, $userinfo=[])
    {

        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.order.orderlist', '1.0', $param, $userinfo);
        return $orderData;

    }


//api.order.orderdetail
//api.order.getOrderStatus
//api.order.getRiskInfo
//api.order.orderLog
    /**
     * Author: heaven
     * 后台订单详情查询接口
     */
    public static function getOrderInfo($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.order.orderdetail', '1.0', $param, $userinfo);
        return $orderData;

    }


    /**
     * Author: heaven
     * 商家后台订单状态流查询接口
     */
    public static function getOrderStatus($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.order.getOrderStatus', '1.0', $param, $userinfo);
        return $orderData;

    }



    /**
     * Author: heaven
     * 商家后台订单日志查询
     */
    public static function getOrderLog($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.order.orderLog', '1.0', $param, $userinfo);
        return $orderData;

    }






    /**
     * Author: heaven
     * 商家后台确认订单接口
     */
    public static function confirmOrder($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.order.confirmOrder', '1.0', $param, $userinfo);
        return $orderData;

    }

    /**
     * Author: heaven
     * 商家后台已支付取消订单接口
     */
    public static function cancelPayOrder($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.Return.returnMoney', '1.0', $param, $userinfo);
        return $orderData;

    }


    /**
     * Author: heaven
     * 商家后台订单设备日志查询
     */
    public static function getOrderGoodsLog($param, $userinfo=[])
    {
        $orderData = self::request(\config('app.APPID'), \config('ordersystem.ORDER_API'),'api.goods.log', '1.0', $param, $userinfo);
        return $orderData;

    }








}
