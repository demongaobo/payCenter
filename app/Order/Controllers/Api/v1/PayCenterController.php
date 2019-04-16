<?php
namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\Order\Order;
use Illuminate\Http\Request;
use App\Order\Modules\Repository\Pay;
use App\Order\Modules\Repository\Pay\{PayStatus,PaymentStatus,WithholdStatus,FundauthStatus,PayQuery};
use App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayFactory;

/**
 * 支付控制器 
 * 
 * 
 */
class PayCenterController extends Controller
{
    
    public function __construct()
    {
        
    }
    
    /**
     * 支付入口
     * @access public
     * @author gaobo
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pay(Request $request)
    {
		try{
			//接收请求参数
			$request_params = $request->all();

			\App\Lib\Common\LogApi::setSource('pay_center');
			\App\Lib\Common\LogApi::debug('支付参数',$request_params);

			$to_business_params = $request_params['params'];
			//过滤参数
			 $rule = [
				'business_type'=>'required',
				'business_no'=>'required',
				'pay_channel_id'=>'required',
				'callback_url'=>'required',
			];
			 
			$validator = app('validator')->make($to_business_params, $rule);
			if ($validator->fails()) {
				return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
			} 
			
			$userInfo = $request_params['userinfo'];
			
			//支付 扩展参数
			$ip = isset($userInfo['ip'])?$userInfo['ip']:'';
			//支付 扩展参数
			$extended_params = isset($to_business_params['extended_params'])?$to_business_params['extended_params']:[];
			
			// 微信支付，交易类型：JSAPI，redis读取openid
			if( $to_business_params['pay_channel_id'] == \App\Order\Modules\Repository\Pay\Channel::Wechat ){
				if( isset($extended_params['wechat_params']['trade_type']) && $extended_params['wechat_params']['trade_type']=='JSAPI' ){
					$_key = 'wechat_openid_'.$request_params['auth_token'];
					$openid = \Illuminate\Support\Facades\Redis::get($_key);
					if( $openid ){
						$extended_params['wechat_params']['openid'] = $openid;
					}
				}
			}
			//业务工厂获取业务
			$business = \App\Order\Modules\Repository\Pay\BusinessPay\BusinessPayFactory::getBusinessPay($to_business_params['business_type'], $to_business_params['business_no']);
			\App\Lib\Common\LogApi::setSource('pay_center');
			\App\Lib\Common\LogApi::debug('获取业务1',$business);
			//获取业务详情
			$businessStatus = $business->getBusinessStatus();
			//校验业务状态是否有效
			if(!$businessStatus){
				return apiResponse([],ApiStatus::CODE_0,"该订单无需支付");
			}

			$paymentInfo = $business->getPaymentInfo();
			$fundauthInfo = $business->getFundauthInfo();
			
			//获取支付单
			$pay = \App\Order\Modules\Repository\Pay\PayQuery::getPayByBusinessTest($to_business_params['business_type'], $to_business_params['business_no']);
			/* \App\Lib\Common\LogApi::setSource('pay_center');
			\App\Lib\Common\LogApi::debug('获取支付单',$pay); */
			// 不存在或者已过期
			if( !$pay || $pay->isExpired() ){
				// 已过期的，状态修改为关闭
				if( $pay && $pay->isExpired() ){
					
				}
			}
			
			// 存在
			if($pay){
				
				\App\Lib\Common\LogApi::debug('支付单已存在');
				// 判断是否需要重建支付参
				// 1）已过期的，状态修改为关闭
				// 2）校验支付单 支付金额 与 业务金额是否一致，不一致时，重建支付单
				// 3）校验支付单 预授权金额 与 业务金额是否一致，不一致时，重建支付单
				// 
				if( $pay->isExpired() 
						|| ($paymentInfo->getNeedPayment() && $paymentInfo->getPaymentAmount() != $pay->getPaymentAmount()) 
						|| ($fundauthInfo->getNeedFundauth() && $fundauthInfo->getFundauthAmount() != $pay->getFundauthAmount()) ){
					\App\Lib\Common\LogApi::debug('支付单过期或交易金额不一致，关闭支付单');
					// 关闭当前支付单
					if( !$pay->close() ){
						\App\Lib\Common\LogApi::debug('支付单已关闭');
					}
					// 重新创建支付单
					$pay = $this->_create_order($to_business_params['business_type'], $to_business_params['business_no'],$business);
					\App\Lib\Common\LogApi::debug('重新创建业务支付单');
				}
				
				if($pay->getStatus() == PayStatus::UNKNOWN){
					return apiResponse([],ApiStatus::CODE_90000,"该订单支付单无效");
				}
				if($pay->getStatus() == PayStatus::SUCCESS){
					return apiResponse([],ApiStatus::CODE_90004,"该订单支付已完成");
				}
				if($pay->getStatus() == PayStatus::CLOSED){
					return apiResponse([],ApiStatus::CODE_90005,"该订单支付已关闭");
				}

			}
			// 支付单不存在
			else{
				
				$pay = $this->_create_order($to_business_params['business_type'], $to_business_params['business_no'],$business);
				\App\Lib\Common\LogApi::debug('创建业务支付单');
			}
			\App\Lib\Common\LogApi::debug('获取name',$business->getPayName());
			//组装url参数
			$currenturl_params = [
				'name'            => $business->getPayName(),
				'front_url'       => $to_business_params['callback_url'],
				'business_no'     => $to_business_params['business_no'],
				'ip'              => $ip,
				'extended_params' => $extended_params,// 扩展参数
			];
			
			// 获取支付参数
			$paymentUrl = $pay->getCurrentUrl($to_business_params['pay_channel_id'],$currenturl_params);
			\App\Lib\Common\LogApi::debug('获取URl',$paymentUrl);
			return apiResponse($paymentUrl,ApiStatus::CODE_0);
			
		} catch (\Exception $ex) {
			\App\Lib\Common\LogApi::error('支付请求异常：'.$ex->getMessage(),$ex);
			//空请求
			return apiResponse('没有支付方式',ApiStatus::CODE_10100);
		}
    }
	public function _create_order($business_type, $business_no, $business): \App\Order\Modules\Repository\Pay\Pay{
		
		// 创建新支付单
		$create_center = new \App\Order\Modules\Repository\Pay\PayCreateCenter();
		\App\Lib\Common\LogApi::setSource('pay_center');
		\App\Lib\Common\LogApi::debug('获取业务',$business); 

		//设置基础参数
		$create_center->setUserId($business->getUserId());
		$create_center->setOrderNo($business->getOrderNo());
		$create_center->setBusinessType($business_type);
		$create_center->setBusinessNo($business_no);

		//直付
		$paymentInfo = $business->getPaymentInfo();
		if($paymentInfo->getNeedPayment()){
			$create_center->setPaymentAmount($paymentInfo->getPaymentAmount());
			$create_center->setPaymentFenqi($paymentInfo->getPaymentFenqi());
			$create_center->setTrade($paymentInfo->getTrate());
			$create_center->setPaymentNo(\creage_payment_no());
			$create_center->setPaymentStatus(PaymentStatus::WAIT_PAYMENT);
			$create_center->setGoingOnPay(true);
		}

		//预授权
		$fundauthInfo = $business->getFundauthInfo();
		if($fundauthInfo->getNeedFundauth()){
			$create_center->setFundauthAmount($fundauthInfo->getFundauthAmount());
			$create_center->setTrade($fundauthInfo->getTrate());
			$create_center->setFundauthNo(\creage_fundauth_no());
			$create_center->setFundauthStatus(FundauthStatus::WAIT_FUNDAUTH);
			$create_center->setGoingOnPay(true);
		}

		//签约代扣
		$withholdInfo = $business->getWithHoldInfo();
		if($withholdInfo->getNeedWithhold()){
			$create_center->setTrade($withholdInfo->getTrate());
			$create_center->setWithhold_no(\creage_withhold_no());
			$create_center->setWithholdStatus(WithholdStatus::WAIT_WITHHOLD);
			$create_center->setGoingOnPay(true);
		}

		//如果支付方式存在
		if($create_center->getGoingOnPay()){
			return $create_center->create();
		}
		
		throw new Exception('支付单创建失败');
	}
    
}