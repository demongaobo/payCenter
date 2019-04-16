<?php

namespace App\Order\Modules\Repository\ShortMessage;
/**
 * Config【平台配置】
 */
class PingtaiConfig {
	
	/**
	 * 默认平台：拿趣用租赁平台
	 */
	const PT_OFFICE = 0;
	
	/**
	 * 其它平台：拿趣用-支付宝生活号
	 */
	const PT_NQY_ALIPAY_LIFE = 1;
	
	/**
	 * 其它平台：拿趣用-支付宝小程序
	 */
	const PT_NQY_ALIPAY_MINI = 36;
	
	/**
	 * 其它平台：拿趣用-APP
	 */
	const PT_NQY_APP_ANDROID = 122;
	
	/**
	 * 其它平台：拿趣用-APP
	 */
	const PT_NQY_APP_IOS = 123;
	
	/**
	 * 其它平台：拿趣用-花呗
	 */
	const PT_NQY_ALIPAY_TOKIO = 137;
	
	/**
	 * 其它平台：拿趣用-微信小程序
	 */
	const PT_NQY_WECHAT_MINI = 140;
	
	/**
	 * 其它平台：拿趣用-支付宝区块链生活号
	 */
	const PT_NQY_ALIPAY_QUKUAI_LIFE = 208;
	
	/**
	 * 其它平台：拿趣用-支付宝区块链小程序
	 */
	const PT_NQY_ALIPAY_QUKUAI_MINI = 211;
	/**
	 * 获取平台列表【appid=>平台名称】
	 */
	public static function getPtList( ) {
		return [
			self::PT_OFFICE => '拿趣用租赁平台',
			self::PT_NQY_ALIPAY_LIFE => '拿趣用-支付宝生活号',
			self::PT_NQY_ALIPAY_MINI => '拿趣用-支付宝小程序',
			self::PT_NQY_APP_ANDROID => '拿趣用-APP',
			self::PT_NQY_APP_IOS => '拿趣用-APP',
			self::PT_NQY_ALIPAY_TOKIO => '拿趣用-花呗',
			self::PT_NQY_WECHAT_MINI => '拿趣用-微信小程序',
			self::PT_NQY_ALIPAY_QUKUAI_LIFE => '拿趣用-支付宝区块链生活号',
			self::PT_NQY_ALIPAY_QUKUAI_MINI => '拿趣用-支付宝区块链小程序',
		];
	}
	
	/**
	 * 获取平台名称
	 * @param int $appid
	 * @param type $scene
	 * @return boolean|string	成功是返回 短信模板ID；失败返回false
	 */
	public static function getName( $appid ){
		$ptList = self::getPtList();
		return isset( $ptList[$appid] ) ? $ptList[$appid] : $ptList[self::PT_OFFICE];
	}
	
}
