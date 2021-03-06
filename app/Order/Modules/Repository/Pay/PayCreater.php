<?php
/**
 * App\Order\Modules\Repository\Pay\PayCreater.php
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2017, Huishoubao
 * 
 */

namespace App\Order\Modules\Repository\Pay;

use App\Lib\Common\LogApi;
use App\Order\Models\OrderPayModel;

/**
 * 支付创建器 类
 * 定义 创建支付 方式的接口
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PayCreater {
	
	/**
	 * 创建第1种支付方式
	 * <p>普通支付</p>
	 * @param	array	$params		普通支付参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
     *
     *      'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
     *      'yiwaixian'         =>'',   //意外险 单位:元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createPayment( array $params ): Pay{
		LogApi::debug('[支付阶段]P创建');
		$params['status'] = PayStatus::WAIT_PAYMENT;
		$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;


		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'payment_status'	=> $params['paymentStatus'],
			'payment_no'		=> \creage_payment_no(),
			'payment_amount'	=> $params['paymentAmount'],
			'payment_fenqi'		=> $params['paymentFenqi'],

			'expire_time'	=> time()+7200,
		];

		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]P创建失败',$data);
			throw new \Exception( '创建支付记录失败' );
		}
		LogApi::debug('[支付阶段]P创建成功');
		return new Pay($data);
	}

	
	/**
	 * 创建第2种支付方式
	 * <p>代扣签约</p>
	 * @param	array	$params		签约参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createWithhold( $params ): Pay{
		LogApi::debug('[支付阶段]W创建');
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
			
			'expire_time'	=> time()+7200,
		];
		//sql_profiler()
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]W创建失败',$data);
			throw new \Exception( '创建支付记录失败' );
		}
		LogApi::debug('[支付阶段]W创建成功');
		return new Pay($data);
	}
	
	/**
	 * 创建第3种支付方式
	 * <p>资金预授权</p>
	 * @param	array	$params		资金预授权参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createFundauth( array $params ): Pay{
		
		LogApi::debug('[支付阶段]F创建');
		$params['status'] = PayStatus::WAIT_FUNDAUTH;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
			
			'expire_time'	=> time()+7200,
		];
		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]F创建失败',$data);
			throw new \Exception( '创建资金预授权记录失败' );
		}
		LogApi::debug('[支付阶段]F创建成功');
		return new Pay( $data );
	}
	
	/**
	 * 创建第4种支付方式
	 * <p>代扣签约+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createWithholdFundauth( array $params ): Pay
	{
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]WF创建');
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
			
			'expire_time'	=> time()+7200,
		];
		//sql_profiler();
		$b = $payModel->insert($data);
		if( !$b )
		{
			LogApi::debug('[支付阶段]WF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]WF创建成功',$data);
		return new Pay( $data );
	}
	
	/**
	 * 创建第5种支付方式 先预授权 再支付
	 * <p>普通支付+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
	 * 
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createPaymentFundauth( array $params ): Pay
	{
		
		$params['status'] = PayStatus::WAIT_FUNDAUTH;
		$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]PF创建');
		$payModel = new OrderPayModel();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'payment_status'	=> $params['paymentStatus'],
			'payment_no'		=> \creage_payment_no(),
			'payment_amount'	=> $params['paymentAmount'],
			'payment_fenqi'		=> $params['paymentFenqi'],
						
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
			
			'expire_time'	=> time()+7200,
		];
		//sql_profiler();
		$b = $payModel->insert($data);
		if( !$b ){
			LogApi::error('[支付阶段]PF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]PF创建成功',$data);
		return new Pay( $data );
	}
	
	/**
	 * 创建第6种支付方式
	 * <p>普通支付+代扣签约+资金预授权</p>
	 * @param	array	$params		参数
	 * [
	 *		'userId'			=> '',	// 业务用户ID
	 *		'orderNo'			=> '',	// 订单编号
	 *		'businessType'		=> '',	// 业务类型
	 *		'businessNo'		=> '',	// 业务编号
	 * 
	 *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
	 *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
	 * 			
	 *		'fundauthNo'		=> '',	// 预授权编号
	 *		'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
	 * ]
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 */
	public static function createPaymentWithholdFundauth( array $params ): Pay
	{
		// 状态
		$params['status'] = PayStatus::WAIT_WHITHHOLD;
		$params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
		$params['withholdStatus'] = WithholdStatus::WAIT_WITHHOLD;
		$params['fundauthStatus'] = FundauthStatus::WAIT_FUNDAUTH;
		
		LogApi::debug('[支付阶段]创建PWF');
		$payModel = new OrderPayModel();
		//sql_profiler();
		$data = [
			'user_id'		=> $params['userId'],
			'order_no'		=> $params['orderNo'],
			'business_type'	=> $params['businessType'],
			'business_no'	=> $params['businessNo'],
			'status'		=> $params['status'],
			'create_time'	=> time(),
			
			'payment_status'	=> $params['paymentStatus'],
			'payment_no'		=> \creage_payment_no(),
			'payment_amount'	=> $params['paymentAmount'],
			'payment_fenqi'		=> $params['paymentFenqi'],
			
			'withhold_no'		=> \creage_withhold_no(),
			'withhold_status'	=> $params['withholdStatus'],
			
			'fundauth_no'		=> \creage_fundauth_no(),
			'fundauth_status'	=> $params['fundauthStatus'],
			'fundauth_amount'	=> $params['fundauthAmount'],
			
			'expire_time'	=> time()+7200,
		];
		//sql_profiler();
		$b = $payModel->insert( $data );
		if( !$b ){
			LogApi::error('[支付阶段]PWF创建失败',$data);
			throw new \Exception( '创建失败' );
		}
		LogApi::debug('[支付阶段]PWF创建成功',$data);
		return new Pay( $data );
	}
    /**
     * 创建第7种支付方式
     * <p>乐百分支付</p>
     * @param	array	$params		普通支付参数
     * [
     *		'userId'			=> '',	// 业务用户ID
     *		'orderNo'			=> '',	// 订单编号
     *		'businessType'		=> '',	// 业务类型
     *		'businessNo'		=> '',	// 业务编号
     *		'paymentAmount'		=> '',	// Price 支付金额，单位：元
     *		'paymentFenqi		=> '',	// int 分期数，取值范围[0,3,6,12]，0：不分期
     *
     *      'fundauthAmount'	=> '',	// Price 预授权金额，单位：元
     *      'yiwaixian'         =>'',   //意外险 单位:元
     * ]
     * @return \App\Order\Modules\Repository\Pay\Pay
     */
    public static function createLebaifenPayment( array $params ): Pay{
        LogApi::debug('[支付阶段]P创建');
        $params['status'] = PayStatus::WAIT_PAYMENT;
        $params['paymentStatus'] = PaymentStatus::WAIT_PAYMENT;
        $params['yiwaixian']  =isset($params['yiwaixian'])?$params['yiwaixian']:0.00;
        $paymentAmountBillList =[
            'zujin'=>normalizeNum($params['paymentAmount']-$params['yiwaixian']),
            'yajin'=>$params['fundauthAmount'],
            'yiwaixian'=>$params['yiwaixian'],
        ];

        $payModel = new OrderPayModel();
        $data = [
            'user_id'		=> $params['userId'],
            'order_no'		=> $params['orderNo'],
            'business_type'	=> $params['businessType'],
            'business_no'	=> $params['businessNo'],
            'status'		=> $params['status'],
            'create_time'	=> time(),

            'payment_status'	=> $params['paymentStatus'],
            'payment_no'		=> \creage_payment_no(),
            'payment_amount'	=> $params['paymentAmount']+$params['fundauthAmount'],
            'payment_fenqi'		=> $params['paymentFenqi'],

            'payment_amount_bill_list' =>json_encode($paymentAmountBillList),
			
			'expire_time'	=> time()+7200,

        ];

        //sql_profiler();
        $b = $payModel->insert( $data );
        if( !$b ){
            LogApi::error('[支付阶段]P创建失败',$data);
            throw new \Exception( '创建支付记录失败' );
        }
        LogApi::debug('[支付阶段]P创建成功');
        return new Pay($data);
    }


}
