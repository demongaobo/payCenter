<?php
/**
 * App\Order\Modules\Repository\Pay\PayCreateCenter.php
 * @access public
 * @author gaobo
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\Repository\Pay;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderPayModel;

/**
 * 支付创建器 类
 * 定义 创建支付 方式的接口
 * @access public
 * @author gaobo
 */
class PayCreateCenter {
    
    //基础参数
	private $user_id = 0;
	private $order_no = '';
	private $business_type = 0;
	private $business_no = '';
	private $status = 0;
	private $create_time = 0;
	
	//直付
	private $payment_status = 0;
	private $payment_no = '';
	private $payment_amount = 0.00;
	private $payment_fenqi = 0;
	
	//代扣
	private $withhold_no = '';
	private $withhold_status = 0;
	
	//预授权
	private $fundauth_no = '';
	private $fundauth_status = 0;
	private $fundauth_amount = 0;
	
	//交易号 P W H
	private $trade = '';
	
	//是否继续交易
	private $goingOnPay = false;
		
	
	public function setGoingOnPay(bool $goingon_pay)
	{
	    return $this->goingOnPay = $goingon_pay;
	}
	
	public function getGoingOnPay():bool
	{
	    return $this->goingOnPay;
	}
	
	//设置基础信息
	public function setUserId($user_id) : bool
	{
	    return $this->user_id = $user_id;
	}
	public function setOrderNo($order_no) : bool
	{
	    return $this->order_no = $order_no;
	}
	public function setBusinessType($business_type) : bool
	{
	    return $this->business_type = $business_type;
	}
	public function setBusinessNo($business_no) : bool
	{
	    return $this->business_no = $business_no;
	}
	
	
	//设置直付相关参数
	public function setPaymentStatus($payment_status) : bool
	{
	    return $this->payment_status = $payment_status;
	}
	public function setPaymentNo($payment_no) : bool
	{
	    return $this->payment_no = $payment_no;
	}
	public function setPaymentAmount($payment_amount) : bool
	{
	    return $this->payment_amount = $payment_amount;
	}
	public function setPaymentFenqi($payment_fenqi) : bool
	{
	    return $this->payment_fenqi = $payment_fenqi;
	}
	
	//设置代扣相关参数
	public function setWithhold_no($withhold_no) : bool
	{
	    return $this->withhold_no = $withhold_no;
	}
	public function setWithholdStatus($withhold_status) : bool
	{
	    return $this->withhold_status = $withhold_status;
	}
	
	//设置预授权相关参数
	public function setFundauthNo($fundauth_no) : bool
	{
	    return $this->fundauth_no = $fundauth_no;
	}
	public function setFundauthStatus($fundauth_status) : bool
	{
	    return $this->fundauth_status = $fundauth_status;
	}
	public function setFundauthAmount($fundauth_amount) : bool
	{
	    return $this->fundauth_amount = $fundauth_amount;
	}
	
	public function setTrade($trade) : bool
	{
	    return $this->trade .= $trade;
	}
	
	/**
	 * 创建支付单
	 * @access public
	 * @author gaobo
	 * @see \App\Order\Modules\Repository\Pay\BusinessPay\{PaymentInfo , WithholdInfo , FundauthInfo}
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public function create(): Pay
	{
		LogApi::debug('[支付阶段]'.$this->trade.'创建');

		$data = [];
		
		/*
		 * 注意：
		 *	1、支付优先级：代扣 > 预授权 > 支付
		 *	2、status 赋值顺序 待支付 ->  待预授权 -> 待代扣签约（来保证优先级）
		 */
		
		if($this->payment_status == PaymentStatus::WAIT_PAYMENT){
		    $data['payment_status']	= $this->payment_status;
		    $data['payment_no']		= $this->payment_no;
		    $data['payment_amount']	= $this->payment_amount;
		    $data['payment_fenqi']	= $this->payment_fenqi;
			$data['status']		   = PayStatus::WAIT_PAYMENT;
		    $this->setTrade('P');
		}
		
		
		if($this->fundauth_status == FundauthStatus::WAIT_FUNDAUTH){
		    $data['fundauth_status'] = $this->fundauth_status;
		    $data['fundauth_no']     = $this->fundauth_no;
		    $data['fundauth_amount'] = $this->fundauth_amount;
			$data['status']		   = PayStatus::WAIT_FUNDAUTH;
		    $this->setTrade('F');
		}
		
		if($this->withhold_status == WithholdStatus::WAIT_WITHHOLD){
		    $data['withhold_status'] = $this->withhold_status;
		    $data['withhold_no']	 = $this->withhold_no;
			$data['status']		   = PayStatus::WAIT_WHITHHOLD;
		    $this->setTrade('W');
		}
		
		
		if(empty($data)){
		    LogApi::error('[支付阶段]'.$this->trade.'创建失败',$data);
		    throw new \Exception( '支付单创建错误' );
		}
		
		
		$data['user_id']	   = $this->user_id;//可能不需要
	    $data['order_no']	   = $this->order_no;//可能不需要
        $data['business_type'] = $this->business_type;
        $data['business_no']   = $this->business_no;
        $data['create_time']   = time();
        $data['expire_time']   = time()+7200; // 过期时间戳
        
        $payModel = new OrderPayModel();
        
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('支付单'.$this->trade.'创建失败',$data);
			throw new \Exception( '支付单创建失败' );
		}
		LogApi::debug('支付单'.$this->trade.'创建成功');
		return new Pay($data);
	}

}
