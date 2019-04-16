<?php
namespace App\Order\Modules\Repository\Relet;
use App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo,WithholdInfo,FundauthInfo};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayInterface;
use App\Order\Models\OrderRelet;
use App\Order\Modules\Inc\ReletStatus;
class ReletPay implements BusinessPayInterface{
    
    private $pamentInfo;
    private $withholdInfo;
    private $fundauthInfo;
    private $business_no = '';
    private $status      = false;
    private $user_id     = 0;
    private $order_no     = '';
    private $relet    = null;
    private $pay_name    = '';
    
    public function __construct(string $business_no){
		$reletService = new Relet(new OrderRelet());
        $this->business_no = $business_no;
        $this->relet = $reletService::getByReletNo($business_no);
        if($this->relet){
			$reletData = $this->relet->getData();
            $this->user_id = intval($reletData['user_id']);
            $this->order_no = $reletData['order_no'];
            if($reletData['status'] == ReletStatus::STATUS1){
                $this->status = true;
                $this->pay_name = '订单：'.$reletData['order_no'].'-续租支付';
                
                //实例化支付方式并根据业务信息传值
                $this->pamentInfo = new PaymentInfo();
                $this->pamentInfo->setNeedPayment(true);
                $this->pamentInfo->setPaymentAmount($reletData['relet_amount']);
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
    public function getUserId(): int
    {
        return intval($this->user_id);
    }
	
    /**
     * 
     */
    public function getOrderNO(): string
    {
        return $this->order_no;
    }
    
    public function getPayName():string
	{
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