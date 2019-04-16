<?php
namespace App\Order\Modules\Repository\Buyout;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Modules\Repository\OrderLogRepository;
use App\Order\Modules\Repository\GoodsLogRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Inc\OrderBuyoutStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderBuyout;

class Buyout implements BusinessPayInterface{
    
    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $business_no = '';
    private $status      = false;
    private $user_id     = 0;
    private $butout      = null;
    private $pay_name    = '';
    
    public function __construct(string $business_no){
        //find
        $this->business_no = $business_no;
        $this->buyout = OrderBuyout::getInfo($business_no);
        if($this->buyout){
            $this->user_id = $this->buyout['user_id'];
            $this->order_no = $this->buyout['order_no'];
            if($this->buyout['status'] == OrderBuyoutStatus::OrderInitialize){
                $this->status = true;
				
                $this->pay_name = '订单：'.$this->buyout['order_no'].'-买断支付';
//                $this->pay_name = '买断单号'.$this->buyout['buyout_no'].'订单编号'.$this->buyout['order_no'].'商品单号'.$this->buyout['goods_no'].'用户ID'.$this->butout['user_id'];
                
                //实例化支付方式并根据业务信息传值
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment(true);
                $this->pamentInfo->setPaymentAmount($this->buyout['amount']);
                $this->pamentInfo->setPaymentFenqi(0);
                $this->withholdInfo = new WithholdInfo();
                $this->withholdInfo->setNeedWithhold(false);
                $this->fundauthInfo = new FundauthInfo();
                $this->fundauthInfo->setNeedFundauth(false);
            }
        }
    }
    
    /**
     * 
     */
    public function getUserId() : int
    {
        return $this->user_id;
    }
	public function getOrderNo() : string{
		return $this->order_no;
	}
    
    public function getPayName():string{
        return strval($this->pay_name);
    }
    
    public function getBusinessStatus(): bool
    {
        return !!$this->status;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \App\Order\Modules\Repository\BusinessPay\BusinessPayInterface::getPaymentInfo()
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
    
}