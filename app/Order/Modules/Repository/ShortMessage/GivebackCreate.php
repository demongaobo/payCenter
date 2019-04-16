<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;

/**
 * GivebackCreate
 *
 * @author maxiaoyu
 */
class GivebackCreate implements ShortMessage {

    private $business_type;
    private $business_no;
    private $data;

    public function setBusinessType( int $business_type ){
        $this->business_type = $business_type;
    }

    public function setBusinessNo( string $business_no ){
        $this->business_no = $business_no;
    }
    public function setData( array $data ){
        $this->data = $data;
    }

    public function getCode($channel_id){
        $class =basename(str_replace('\\', '/', __CLASS__));
        return Config::getCode($channel_id, $class);
    }

    public function notify(){

        $orderGivebackService = new \App\Order\Modules\Service\OrderGiveback();
        $orderGivebackInfo = $orderGivebackService->getInfoByGoodsNo($this->business_no);



        // 查询订单
        $orderInfo = OrderRepository::getInfoById($orderGivebackInfo['order_no']);
        if( !$orderInfo ){
            LogApi::debug("创建还机单-订单详情错误",$orderGivebackInfo);
            return false;
        }

        // 用户信息
        $userInfo = \App\Lib\User\User::getUser($orderGivebackInfo['user_id']);
        if( !is_array($userInfo )){
            return false;
        }
		
        $goods = OrderRepository::getGoodsListByOrderId($orderInfo['order_no']);
		if(!$goods){
		    return false;
        }
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName=$v['goods_name'];
        }

        // 短息模板
        $code = $this->getCode($orderInfo['channel_id']);
        if( !$code ){
            return false;
        }

        // 短信参数
        $dataSms =[
            'realName'          => $userInfo['realname'],
            'comPany'           => $orderGivebackInfo['logistics_name'],
            'wuliuNo'           => $orderGivebackInfo['logistics_no'],
            'goodsName'           => $goodsName,
        ];
        // 发送短息
        return \App\Lib\Common\SmsApi::sendMessage($userInfo['mobile'], $code, $dataSms);

    }

    // 支付宝内部消息通知
    public function alipay_notify(){
        return true;
    }
}