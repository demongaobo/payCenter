<?php
namespace App\Lib;

use \App\Lib\AlipaySdk\sdk\aop\AopClient;
use App\Lib\Common\LogApi;

/**
 * 接口基类
 * 定义了 系统之间交互接口基本方式
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
class BaseApi {
	
	/**
     * 订单接口请求
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param	string	$appid		业务APPID
     * @param	string	$url		接口地址
     * @param	string	$method		接口名称
     * @param	string	$version	接口版本
     * @param	array	$params		业务请求参数（具体业务查阅具体接口协议）
     * @return	array				业务返回参数（具体业务查阅具体接口协议）
	 * @throws \App\Lib\ApiException			请求失败时抛出异常
     */
    public static function request( int $appid, string $url, string $method, string $version, array $params, array $userInfo = [] ){


		//-+--------------------------------------------------------------------
		// | 创建请求
		//-+--------------------------------------------------------------------
		$request = new \App\Lib\ApiRequest();
		$request->setAppid( $appid );// 系统Appid
		$request->setUrl( $url );	// 接口地址
		$request->setMethod( $method );	// 接口名称
		$request->setVersion( $version );
		//请求验签
//        $AopClient  = new AopClient();
//        $sign = $AopClient->generateSign($params);
//        $params['sign'] = $sign;
//        $params['sign_type'] = 'rsa';
		$request->setParams( $params );	// 业务参数
		$request->setUserInfo( $userInfo );	// 业务参数
		//-+--------------------------------------------------------------------
		// | 发送请求
		//-+--------------------------------------------------------------------
		$response = $request->sendPost();
		//-+--------------------------------------------------------------------
		// | 返回值处理
		//-+--------------------------------------------------------------------
        LogApi::info('app_Lib_BaseApi_request',$response->toArray());
		if( $response->isSuccessed() ){ // 判断执行是否成功，成功时返回业务返回值
			return $response->getData();
		}
		//-+--------------------------------------------------------------------
		// | 失败处理
		//-+--------------------------------------------------------------------
		throw new ApiException($response);
	}
}
