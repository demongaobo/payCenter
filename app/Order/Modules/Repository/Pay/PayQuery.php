<?php
namespace App\Order\Modules\Repository\Pay;

/**
 * 支付单查询
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class PayQuery {
	
	
	//-+------------------------------------------------------------------------
	// | 静态方法方法
	//-+------------------------------------------------------------------------
	/**
	 * 根据业务 获取支付单
	 * @param int		$business_type		业务类型
	 * @param string	$business_no		业务编号
     * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByBusiness( int $business_type, string $business_no, int $lock=0 ){
			$builder = \App\Order\Models\OrderPayModel::where([
				'business_type'	=> $business_type,
				'business_no'	=> $business_no,
			]);
			if( $lock ){
				$builder->lockForUpdate();
			}
			$info =  $builder->first();
			if( $info ){
				return new Pay( $info->toArray() );
			}
			throw new \App\Lib\NotFoundException('支付单不存在getPayByBusiness');
	}
	
	//-+------------------------------------------------------------------------
	// | 静态方法方法gaobo
	//-+------------------------------------------------------------------------
	/**
	 * 根据业务 获取支付单
	 * @param int		$business_type		业务类型
	 * @param string	$business_no		业务编号
	 * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByBusinessTest( int $business_type, string $business_no, int $lock=0 ){
	    
	    $builder = \App\Order\Models\OrderPayModel::where(
	        ['business_type'	=> $business_type,
	        'business_no'	=> $business_no]
	    )->whereIn('status' , [PayStatus::WAIT_PAYMENT,PayStatus::WAIT_WHITHHOLD,PayStatus::WAIT_FUNDAUTH,PayStatus::SUCCESS]);
	    
	    if( $lock ){
	        $builder->lockForUpdate();
	    }
	    $info =  $builder->first();
	    
	    \App\Lib\Common\LogApi::debug('获取支付单',$info);
	    if( $info ){
	        return new Pay( $info->toArray() );
	    }
	    return false;
	}
	
	/**
	 * 根据业务系统支付编号 获取支付单
	 * @param string	$payment_no		支付编号
     * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByPaymentNo( string $payment_no, int $lock=0 ){
		$builder = \App\Order\Models\OrderPayModel::where([
			'payment_no'	=> $payment_no,
		]);
        if( $lock ){
            $builder->lockForUpdate();
        }
		$info =  $builder->first();
		if( $info ){
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在getPayByPaymentNo');
	}
	
	/**
	 * 根据业务系统 代扣协议编号 获取支付单
	 * @param string	$withhold_no		代扣协议编号
     * @param int		$lock			锁
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByWithholdNo( string $withhold_no,int $lock=0 ){
		$builder = \App\Order\Models\OrderPayModel::where([
			'withhold_no'	=> $withhold_no,
		]);
        if( $lock ){
            $builder->lockForUpdate();
        }
		$info =  $builder->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据业务系统 资金授权编号 获取支付单
	 * @param string	$fundauth_no		资金授权编号
	 * @return \App\Order\Modules\Repository\Pay\Pay
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPayByFundauthNo( string $fundauth_no ){
		$info = \App\Order\Models\OrderPayModel::where([
			'fundauth_no'	=> $fundauth_no,
		])->first();
		if( $info ){
			\App\Lib\Common\LogApi::info( '支付单', $info );
			return new Pay( $info->toArray() );
		}
		throw new \App\Lib\NotFoundException('支付单不存在');
	}
	
	/**
	 * 根据 支付编号，获取支付系统支付信息
	 * @param string	$payment_no		支付编号
	 * @return array	
	 * [
	 *		'payment_no'		=> '',	//【必选】string 业务支付编码
	 *		'out_payment_no'	=> '',	//【必选】string  支付系统支付编码
	 *		'create_time'		=> '',	//【必选】int  创建时间戳
	 * ]
	 * @throws \App\Lib\NotFoundException
	 */
	public static function getPaymentInfoByPaymentNo( string $payment_no ){
		$info = \App\Order\Models\OrderPayPaymentModel::where([
			'payment_no'	=> $payment_no,
		])->first();
		if( $info ){
			return $info->toArray();
		}
		throw new \App\Lib\NotFoundException('支付系统支付信息不存在');
	}

    /**
     * 根据预授权编号，获取支付系统预授权信息
     * Author: heaven
     * @param string $authNo
     * @return array
     * @throws \App\Lib\NotFoundException
     */
    public static function getAuthInfoByAuthNo( string $authNo ){
        $info = \App\Order\Models\OrderPayFundauthModel::where([
            'fundauth_no'	=> $authNo,
        ])->first();
        if( $info ){
            return $info->toArray();
        }
        throw new \App\Lib\NotFoundException('预授权协议不存在');
    }
	
    /**
     * 根据订单编号，获取代扣协议信息
     * @param string $orderNo
     * @return array
     * @throws \App\Lib\NotFoundException
     */
    public static function getWithholdInfoByWithholdNo( string $withholdNo ){
        $info = \App\Order\Models\OrderPayWithholdModel::where([
            'withhold_no'	=> $withholdNo,
        ])->first();
        if( $info ){
            return $info->toArray();
        }
        throw new \App\Lib\NotFoundException('代扣协议不存在');
    }
	
    /**
     * 根据业务，获取代扣协议信息
     * @param string $business_type
     * @param string $business_no
     * @return array
     * @throws \App\Lib\NotFoundException
     */
    public static function getWithholdByBusiness( string $business_type, string $business_no ){
        $info = \App\Order\Models\OrderPayWithholdBusinessModel::where([
            'business_type'	=> $business_type,
            'business_no'	=> $business_no,
        ])->first();
        if( $info ){
            return $info->toArray();
        }
        throw new \App\Lib\NotFoundException('业务代扣协议不存在');
    }
}
