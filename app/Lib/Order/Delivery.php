<?php
/**
 * User: wansq
 * Date: 2018/5/8
 * Time: 10:50
 */

namespace App\Lib\Order;
use App\Lib\Common\LogApi;
use App\Lib\Curl;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Models\OrderLog;
use Illuminate\Support\Facades\Log;

/**
 * Class Delivery
 * 与收发货相关
 */
class Delivery
{
    /**
     * 客户收货或系统自动签收会通知到此方法
     * @param string $orderNo
     * @param array $row  用户信息
     * [
     *      'receive_type'=>签收类型:1管理员，2用户,3系统，4线下,   int    【必传】
     *      'user_id'     =>用户ID（管理员或用户必须）,             int    【必传】
     *      'user_name'  =>用户名（管理员或用户必须）,              string  【必传】
     * ]
     *
     * $userinfo [
     *      'type'=>'',     //【必须】int 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】int 用户ID
     *      'username'=>1, //【必须】string 用户名
     * ]
     *
     * @return  bool
     */
    public static function receive($orderNo,$userInfo)
    {
        try{
            $base_api = config('ordersystem.ORDER_API');
            $params['order_no'] =$orderNo;

            $response = Curl::post($base_api, [
                'appid'=> 1,
                'version' => 1.0,
                'method'=> 'api.order.deliveryReceive',//模拟
                'params' => $params,
                'userinfo'=>$userInfo,
            ]);
            $res = json_decode($response);
            if ($res->code != 0) {
                return false;
            }
        }catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
        return true;
    }
    /**
     * 申请退货审核通过-》客户发货后，会通知此方法
     */
    public static function user_receive($params)
    {
        $base_api = config('ordersystem.ORDER_API');

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.Return.userReceive',//模拟
            'params' => $params
        ]);

        return $response;

    }
    /**
     *  查询订单 确认订单备注
     * @param $orderNo 订单编号
     * @return $string
     */
    public static function order_remark($orderNo)
    {
        if (empty($orderNo)) return '';
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        $order =  Order::query()->where($whereArray)->first();
        if (!$order) return '';
        $res =$order->toArray();
        return $res['remark'];

    }
    /**
     *  查询订单
     * @param $orderNo 订单编号
     * @return $array
     */
    public static function order_remark_row($orderNo)
    {
        if (empty($orderNo)) return '';
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        $order =  Order::query()->where($whereArray)->first();
        if (!$order) return '';
        $res =$order->toArray();
        return $res;

    }
    /**
     *  查询订单商品信息
     * @param $orderNo 订单编号
     * @return $array
     */
    public static function order_goods_row($orderNo)
    {
        if (empty($orderNo)) return '';
        $whereArray = array();
        $whereArray[] = ['order_no', '=', $orderNo];
        $order =  OrderGoods::query()->where($whereArray)->first();
        if (!$order) return '';
        $res =$order->toArray();
        return $res;

    }
    /**
     * 发货更新请求
     * 判断是订单发货还是换货发货
     * 换货发货新商品反馈到此方法 order_good_extend
     * @param $orderDetail array
     * [
     *  'order_no'    =>'',//订单编号   string   【必传】
     *  'logistics_id'=>''//物流渠道ID  int      【必传】
     *  'logistics_no'=>''//物流单号    string   【必传】
     * ]
     * @param $goodsInfo array 商品信息 【必须】 参数内容如下
     * [
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     *   [
     *      'goods_no'=>'abcd',  商品编号   string  【必传】
     *      'imei1'   =>'imei1', 商品imei1  string  【必传】
     *      'imei2'   =>'imei2', 商品imei2  string  【必传】
     *      'imei3'   =>'imei3', 商品imei3  string  【必传】
     *      'serial_number'=>'abcd' 商品序列号  string  【必传】
     *   ]
     * ]
     * @param array $operatorInfo 用户信息参数
     * [
     *      'uid'      =>''     用户id      int      【必传】
     *      'username' =>''    用户名      string   【必传】
     *      'type'     =>''   渠道类型     int      【必传】  1  管理员，2 用户，3 系统自动化
     * ]
     * 需要写成curl形式 供发货系统使用
     */
    public static function delivery($orderDetail,$goodsInfo,$operatorInfo)
    {
        $base_api = config('ordersystem.ORDER_API');
        $params['order_info'] =$orderDetail;
        $params['goods_info'] =$goodsInfo;
        $params['operator_info'] =$operatorInfo;

        $response = Curl::post($base_api, [
            'appid'=> 1,
            'version' => 1.0,
            'method'=> 'api.order.delivery',//模拟
            'params' => $params
        ]);
        return $response;


    }
}