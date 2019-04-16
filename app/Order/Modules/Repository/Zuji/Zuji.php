<?php
namespace App\Order\Modules\Repository\Zuji;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use Mockery\Exception;

class Zuji implements BusinessPayInterface{

    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $status      = false;
    private $user_id     = 0;
    private $pay_name    = '';
    private $order_no = '';

    public function __construct(string $business_no){

        $order = Order::getByNo($business_no);

        if($order){
            $orderInfo = $order->getData();
            $this->user_id = $orderInfo['user_id'];
            $this->order_no = $business_no;

            if($orderInfo['order_status'] == OrderStatus::OrderWaitPaying || $orderInfo['order_status'] == OrderStatus::OrderPaying){
                $this->status = true;
                //$this->pay_name = "订单编号：".$business_no." 用户ID：".$this->user_id;
                $this->pay_name = "【拿趣用】订单:".$business_no."-预授权";

                $goodsInfo = Goods::getOrderNo($business_no);

                $goods = $goodsInfo->getData();
                if(!$goods){
                   throw new Exception("订单商品信息不存在");
                }
                $zuqi = $goods['zuqi'];
                $yajin = $orderInfo['order_yajin'];
                $zujin = $orderInfo['order_amount'] + $orderInfo['order_insurance'];
                $fenqi = $orderInfo['zuqi_type'] == OrderStatus::ZUQI_TYPE_DAY ? 0:$zuqi;

                $arr= $this->getOrderPayInfo([
                         'zujin' =>$zujin,       //【必须】商品租金 + 意外险
                         'yajin' =>$yajin,       //【必须】商品押金
                         'pay_type' =>$orderInfo['pay_type'],    //【必须】支付方式
                ]);

                //实例化支付方式并根据业务信息传值
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment($arr['isPayment']);
                $this->pamentInfo->setPaymentAmount($arr['paymentAmount']);
                $this->pamentInfo->setPaymentFenqi($fenqi);
                $this->withholdInfo = new WithholdInfo();
                $this->withholdInfo->setNeedWithhold($arr['isWithhold']);
                $this->fundauthInfo = new FundauthInfo();
                $this->fundauthInfo->setNeedFundauth($arr['isFundauth']);
                $this->fundauthInfo->setFundauthAmount($arr['fundauthAmount']);

            }

        }

    }

    /**
     * 获取订单支付信息
     * @author wuhaiyan
     * @param $params
     * [
     *      'zujin' =>'',       //【必须】商品租金 + 意外险
     *      'yajin' =>'',       //【必须】商品押金
     *      'pay_type' =>'',    //【必须】支付方式
     * ]
     *
     * @return array
     * [
     *      'isFundauth'=>'',   // 是否需要预授权
     *      'isPayment'=>'',    // 是否需要一次性支付
     *      'paymentAmount'=>'',  // 需要支付金额
     * ]
     */

    public function getOrderPayInfo($params){
        $arr=[
            'isFundauth'=>false,
            'isPayment'=>false,
            'isWithhold'=>false,
            'paymentAmount'=>0,
            'fundauthAmount'=>0,
        ];

        $yajin = $params['yajin'];
        $zujin = $params['zujin'];
        $payType = $params['pay_type'];

        //判断分期

        //支付方式为代扣+预授权  租金走代扣 押金预授权
        if($payType == PayInc::WithhodingPay){
            if($yajin > 0){
                $arr['isFundauth'] = true;
                $arr['fundauthAmount'] =$yajin;
            }
            if($zujin > 0){
                //查询是否签约代扣
                $isWithhold = $this->isWithholdQuery($this->user_id,$payType);
                if(!$isWithhold){//未签约
                    $arr['isWithhold'] = true;
                }

            }
        }
        //花呗分期,银联支付,微信,支付宝一次性支付 为一次性支付 租金+押金
        elseif($payType == PayInc::FlowerStagePay || $payType == PayInc::UnionPay || $payType == PayInc::WeChatPay || $payType == PayInc::AlipayOne){
            if(($yajin +$zujin)>0){
                $arr['isPayment'] = true;
                $arr['paymentAmount'] = $zujin+$yajin;
            }

        }
        //花呗分期+预授权 租金一次性支付 押金预授权
        elseif ($payType == PayInc::PcreditPayInstallment){
            if($yajin >0){
                $arr['isFundauth'] = true;
                $arr['fundauthAmount'] =$yajin;
            }
            if($zujin >0){
                $arr['isPayment'] = true;
                $arr['paymentAmount'] = $zujin;
            }
        }
        //花呗预授权支付  租金+押金 都需要预授权
        elseif ($payType == PayInc::FlowerFundauth){
            if(($yajin +$zujin)>0){
                $arr['isFundauth'] = true;
                $arr['fundauthAmount'] = $zujin+$yajin;
            }
        }

        return $arr;


    }

    /**
     *
     */
    public function getUserId(): int
    {
        return intval($this->user_id);
    }

	public function getOrderNo() : string{
		return $this->order_no;
	}
	
    public function getPayName():string
    {

        return $this->pay_name;
    }

    public function getBusinessStatus(): bool
    {
        return !!$this->status;
    }

    /**
     *
     * {@inheritDoc}
     * @see \App\Order\Modules\Repository\Pay\BusinessPayInterface::getPaymentInfo()
     */
    public function getPaymentInfo(): PaymentInfo
    {
        return $this->pamentInfo;
    }

    /**
     * 代扣
     */
    public function getWithHoldInfo() : WithholdInfo
    {
        return $this->withholdInfo;
    }

    /**
     * 预授权
     */
    public function getFundauthInfo() : FundauthInfo
    {
        return $this->fundauthInfo;
    }

    /**
     *  是否签约代扣查询
     * @param
     * $userId 用户ID
     * $payChannelId 支付渠道
     * @return boolean
     */
    private function isWithholdQuery($userId,$payType):bool {
        try{
            $payChannelId = PayInc::getPayChannelName($payType);
            $withhold = WithholdQuery::getByUserChannel($userId,$payChannelId);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }

    public function addLog(){
        return true;
    }

}