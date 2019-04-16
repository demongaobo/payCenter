<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/4/11 0011
 * Time: 下午 3:07
 */
namespace App\Lib;
use App\Lib\Common\LogApi;
class BaseCommonApi
{

    /**
     * 订单基础接口请求
     * @access public
     * @author liuhongxing <liuhongxing@huishoubao.com.cn>
     * @param    string $appid 业务APPID
     * @param    string $url 接口地址
     * @param    string $method 接口名称
     * @param    string $version 接口版本
     * @param    array $params 业务请求参数（具体业务查阅具体接口协议）
     * @return    array                业务返回参数（具体业务查阅具体接口协议）
     * @throws \App\Lib\ApiException            请求失败时抛出异常
     */
    public static function request(int $appid, string $url, string $method, string $version,  $params,  $userInfo = [])
    {
        //-+--------------------------------------------------------------------
        // | 创建请求
        //-+--------------------------------------------------------------------
        $request = new \App\Lib\ApiRequest();

        $request->setAppid($appid);// 系统Appid

        $request->setUrl($url);    // 接口地址

        $request->setVersion($version);

        $request->setParams($params);    // 业务参数
        $request->setMethod($method);    // 接口名称

        $request->setUserInfo($userInfo);    // 业务参数



        //-+--------------------------------------------------------------------
        // | 发送请求
        //-+--------------------------------------------------------------------
        $response = $request->sendPost();

        //-+--------------------------------------------------------------------
        // | 返回值处理
        //-+--------------------------------------------------------------------
        LogApi::info('app_Lib_BaseCommonApi_request', $response->toArray());

        return $response->toDataArray();

    }


}