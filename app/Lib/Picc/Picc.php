<?php
/**
 *  第三方蚂蚁保险接口
 * Created by PhpStorm.
 * User: limin
 * Date: 2019/02/22
 * Time: 16:32
 */

namespace App\Lib\Picc;
use App\Lib\Common\LogApi;
use App\Lib\Curl;

class Picc{

    private static $msg = [];

    //请求头部信息
    private static $header = [
        'accept:*/*',
        'connection:Keep-Alive',
        'User-Agent:Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1;SV1)',
    ];

    /**
     * 蚂蚁保险投保申请（该方法包含投保申请+缴费确认两个接口请求实现）
     * @author limin
     * @param  [
     *      user_id =>2//【必须】 string user_id
     * ]
     * @return string or array
     */
    public static function application($body){


        /**************投保申请********************/
        $head = [
            'RequestId'=>$body['MainId']."-".time(),
            'RequestType'=>'exchangequery',
            'RequestTime'=>date("Y-m-d H:i:s"),
            'Md5Value'=>'',
        ];
        //保险接口请求地址
        $url = env("PICC_SERVER_URL","http://115.238.63.173:9999/bchireserver");
        //密钥
        $md5Key = env("PICC_SERVER_KEY","PicCTest1234");
        //商家名称(由PICC分配ID)
        $body['Seller'] = env("PICC_SERVER_SELLER","bch1901002");

        $head['Md5Value'] = md5($head['RequestId'].$body['MainId'].$md5Key);

        //转xml
        $content = self::xmlConvert($head,$body);

        //发起请求
        $xml = Curl::post($url,$content,self::$header);

        //xml转数组
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($result['Body']['ErrorCode']!="000"){
            return self::$msg = [
                'code'=>1,
                'msg'=>$xml,
                'request'=>$content,
            ];
        }
        //记录投保申请请求和返回
        //$data['exchangequery_request'] = $body;
        $data['picc_info'] = $body;

        /******************缴费确认************************/
        $ExchangeNo = $result['Body']['ExchangeNo'];
        $txHash =  $body['txHash'];
        $AlipayId = $body['alipayOrderNo'];
        $Premium = $result['Body']['Premium'];
        $PayTime = date("Y-m-d H:i:s");
        $ExtendInfos = "";

        //缴费确认
        $body = [
            'MainId'=>$body['MainId'],
            'Seller'=>$body['Seller'],
            'ExchangeNo'=>$ExchangeNo,
            'txHash'=>$txHash,
            'AlipayId'=>$AlipayId,
            'Premium'=>$Premium,
            'PayTime'=>$PayTime,
            'ExtendInfos'=>$ExtendInfos,
        ];

        $head['RequestType'] = "policyquery";
        $content = self::xmlConvert($head,$body);

        $xml = Curl::post($url,$content,self::$header);

        if(empty($xml)){
            LogApi::debug("picc[请求失败]",[$content,$xml]);
        }

        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        if($result['Body']['ErrorCode']!="000"){
            return self::$msg = [
                'code'=>1,
                'msg'=>$xml,
                'requests'=>$content,
            ];
        }
        //记录缴费确认请求和返回
        //$data['policyquery_request'] = $body;
        $data['picc_query'] = $result['Body'];

        //返回成功记录
        return self::$msg = [
            'code'=>0,
            'msg'=>$xml,
            'data'=>$data
        ];
    }

    //数组转xml
    private static function xmlConvert($head,$body){
        $content = "<?xml version=\"1.0\" encoding=\"utf-8\"?><Request><Head>";

        foreach($head as $key=>$val){
            $content.= "<".$key.">".$val."</".$key.">";
        }

        $content.="</Head><Body>";

        foreach($body as $key=>$val){
            $content.= "<".$key.">".$val."</".$key.">";
        }

        $content.="</Body></Request>";

        return $content;
    }
}