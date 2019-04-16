<?php
namespace App\Order\Modules\Repository\Overdue;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\Order\Goods;
use App\Order\Modules\Repository\Order\Order;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Modules\Repository\Pay\WithholdQuery;
use App\Order\Modules\Service\OrderGoodsOperate;
use Mockery\Exception;

class Overdue implements BusinessPayInterface{

    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $status      = false;
    private $user_id     = 0;
    private $pay_name    = '';
    private $order_no = '';

    public function __construct(string $business_no){

        $goods = Goods::getByGoodsNo($business_no);

        if($goods){
            $goodsInfo = $goods->getData();
            $this->user_id = $goodsInfo['user_id'];
            $this->order_no = $goodsInfo['order_no'];
            //判断是否逾期
            $overdue = OrderGoodsOperate::isOverdueByGoods($business_no);
            if(!$overdue){
                throw new Exception("改商品未逾期");
            }
            //逾期总金额
            $overdueAmount = normalizeNum($overdue*$goodsInfo['zujin']);
            $this->pay_name = "【拿趣用】订单:".$goodsInfo['order_no']. '设备'.$goodsInfo['goods_no'].' 逾期支付';
            $this->status = true;
            //实例化支付方式并根据业务信息传值
            $this->pamentInfo = new PaymentInfo();
            $this->pamentInfo->setNeedPayment(true);
            $this->pamentInfo->setPaymentAmount($overdueAmount);
            $this->pamentInfo->setPaymentFenqi(0);
            $this->withholdInfo = new WithholdInfo();
            $this->withholdInfo->setNeedWithhold(false);
            $this->fundauthInfo = new FundauthInfo();
            $this->fundauthInfo->setNeedFundauth(false);
        }

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
    

    public function addLog(){
        return true;
    }

}