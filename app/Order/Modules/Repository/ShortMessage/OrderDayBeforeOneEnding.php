<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Lib\User\User;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderDeliveryRepository;
use App\Order\Modules\Repository\OrderGoodsInstalmentRepository;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;

/**
 * OrderDayBeforeOneEnding 订单到期前一天发送信息-短租
 *
 * @author wuhaiyan
 */
class OrderDayBeforeOneEnding implements ShortMessage {
	
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
		// 根据业务，获取短息需要的数据

		// 查询订单
        $orderInfo = OrderRepository::getOrderInfo(array('order_no'=>$this->business_no));
		if( !$orderInfo ){
			return false;
		}
		// 短息模板
		$code = $this->getCode($orderInfo['channel_id']);
		if( !$code ){
			return false;
		}
        $goods = OrderRepository::getGoodsListByOrderId($this->business_no);
		if(!$goods){
		    return false;
        }
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName=$v['goods_name'];
            $endTime =$v['end_time'];
        }
        //小程序长短租都发 其他渠道只发短租
        if($orderInfo['appid']!=36 && $orderInfo['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH){
            return false;
        }



		// 发送短息
		return \App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName'=>$orderInfo['realname'],
            'goodsName'=>$goodsName,
            'endTime'=>date("Y-m-d H:i:s",$endTime),
		]);
	}

	// 支付宝 短信通知
	public function alipay_notify(){
        return true;
	}

	
}
