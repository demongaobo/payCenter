<?php

namespace App\Order\Modules\Repository\ShortMessage;

use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;
use App\Order\Modules\Repository\Pay\Channel;
use App\Order\Modules\Repository\OrderReturnRepository;

/**
 * RefundSuccess
 *
 * @author Administrator
 */
class RefundSuccess implements ShortMessage {

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
        //获取退货单信息
       $return= \App\Order\Modules\Repository\GoodsReturn\GoodsReturn::getReturnByRefundNo($this->business_no);
        if( !$return ){
            return false;
        }
       $returnInfo=$return->getData();
        LogApi::debug("[refundSuccess]短信获取退货单信息",$returnInfo);
		// 查询订单
        $order = \App\Order\Modules\Repository\Order\Order::getByNo($returnInfo['order_no']);
        if( !$order ){
            return false;
        }
        $orderInfo=$order->getData();
        LogApi::debug("[refundSuccess]短信查询订单",$orderInfo);
		// 短息模板
		$code = $this->getCode($orderInfo['channel_id']);
		if( !$code ){
			return false;
		}
        LogApi::debug("[refundSuccess]短息模板",$code);
		//获取商品信息
        $goods = OrderRepository::getGoodsListByOrderId($orderInfo['order_no']);
        if(!$goods){
            return false;
        }
        LogApi::debug("[refundSuccess]短信获取商品信息",$goods);
        $goodsName ="";
        foreach ($goods as $k=>$v){
            $goodsName.=$v['goods_name']." ";
        }
        //获取用户认证信息
        $userInfo=OrderRepository::getUserCertified($orderInfo['order_no']);
        if(!$userInfo){
            return false;
        }
        LogApi::debug("[refundSuccess]获取用户认证信息",$userInfo);
        // 发送短息
        $res=\App\Lib\Common\SmsApi::sendMessage($orderInfo['mobile'], $code, [
            'realName' => $userInfo['realname'],
            'orderNo' => $orderInfo['order_no'],
            'goodsName' => $goodsName,
            'serviceTel'=>config('tripartite.Customer_Service_Phone'),
        ],$orderInfo['order_no']);
        return $res;
	}

	// 支付宝 短信通知
	public function alipay_notify(){
		return true;
	}

}
