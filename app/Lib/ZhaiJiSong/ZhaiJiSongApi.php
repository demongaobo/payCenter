<?php
/**
 *  与宅急送交互类
 * Created by PhpStorm.
 * User: qinliping
 * Date: 2019/02/27
 * Time: 18:50
 */

namespace App\Lib\ZhaiJiSong;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Curl;

class ZhaiJiSongApi{
	
	private static $client_flag = 'test';
	private static $key = 'aafc04a1bacb487fa8d03f2a7bfdb555';
	private static $constant = 'z宅J急S送g';
	
    private static $info = '';//返回信息
    private static $code = '';
    private static $error = '';
    private static $state = '';//状态码  未查询到该用户:40001，MD5不正确:40003,未知异常:50000,MD5生产异常:50001,数据验证错误:50002,保存数据异常:50003,订单数超过50条:50004,成功:20000
	
	public static function getError(){
		return self::$error;
	}
	
    /**
     * 请求宅急送参数
     * @params  $messageData  array //请求参数
     * @return array
     */
    public static function createParams($messageData){
       $messageDataJson = json_encode($messageData);
        $varifyData = self::__sign($messageDataJson);//签名
        $params = array(
            'clientFlag' => env('ZHAIJISONG_CLIENT_FLAG','test'),// 客户标识
            'verifyData' => $varifyData,//签名
            'data'        => $messageDataJson //请求数据
        );
        return $params;
    }
    /**
     * 生成签名规则
     * @params $messageData 报文数据
     * @return string
     */
    private static function __sign($messageData){
        $var1      = rand(1000, 9999);//四位随机数1
        $var2      = rand(1000, 9999);//四位随机数1
        $clickFlag = env('ZHAIJISONG_CLIENT_FLAG','test'); //客户标识
        $secretKey = env('ZHAIJISONG_KEY','aafc04a1bacb487fa8d03f2a7bfdb555'); //秘钥
        $constant  = env('ZHAIJISONG_CONSTANT','z宅J急S送g');  //常量
        $str = $var1.$clickFlag.$messageData.$secretKey.$constant.$var2;
        $strMd5 = MD5($str);
        $strRes = substr($strMd5,7,21);
        $varifyData = $var1.$strRes.$var2;
        return $varifyData;
    }
	
	/**
	 * 签名校验
	 * @param array $params		通知内容
	 * @return bool true：验签成功；false；验签失败
	 */
	public static function verifySignature( array $params ):bool{
		
        $var1      = substr($params['verifyData'],0,4);//四位随机数1
        $var2      = substr($params['verifyData'],-4);//四位随机数2
        $clickFlag = env('ZHAIJISONG_CLIENT_FLAG','test'); //客户标识
        $secretKey = env('ZHAIJISONG_KEY','aafc04a1bacb487fa8d03f2a7bfdb555'); //秘钥
        $constant  = env('ZHAIJISONG_CONSTANT','z宅J急S送g');  //常量
        $str = $var1.$clickFlag.$params['data'].$secretKey.$constant.$var2;
        $strMd5 = MD5($str);
        $strRes = substr($strMd5,7,21);
        $varifyData = $var1.$strRes.$var2;
		//var_dump( '异步通知内容生成签名：',$varifyData );
		if( $params['verifyData'] == $varifyData ){
			return true;
		}
		return false;
	}

    /**
     * 宅急送下单
     * @params array		标准输入
     *'[{
     *			'clientFlag'	=> '',// 【必选】     string  客户标识
     *			'mailNo'		=> '',// 【必选】     string  运单号
     *			'orderNo'		=> '',// 【必选】     string  客户单号
     *          'busType'       => '',// 【必选】     string  订单类型  1：发货，2：退货，3：换货，4：其他（默认），5：异调（对外无）
     *          'open_type'     => '',// 【必选】     string  开单类型 0：正常订单，1：取件通知单
     *			'goodsName'		=> '',// 【必选】     string  商品名
     *			'goodsNum'	    => '',// 【必选】     string  订单总件数
     *			'goodsWeight'	=> '',// 【必选】     string  订单总重量   单位：kg
     *			'sendName'		=> '',// 【必选】     string  寄件人名称   取货业务必填
     *			'sendAddress'	=> '',// 【必选】     string  寄件人地址   取货业务必填
     *			'sendMobile'	=> '',// 【必选】     string  寄件人手机号 取货业务必填
     *			'receiveName'	=> '',// 【必选】     string  收件人名称   正向发货必填
     *			'receivePro'	=> '',// 【必选】     string  收件省       正向发货必填
     *			'receiveCity'	=> '',// 【必选】     string  收件市       正向发货必填
     *			'receiveDistrict'=> '',//【必选】     string 收件区/县   正向发货必填
     *			'receiveAddress'=> '',// 【必选】     string  收件详细地址 正向发货必填
     *			'receiveMobile'	=> '',// 【必选】     string  收件人手机号 正向发货必填
     *
     *			'originalNo'	=> '',// 【可选】     string  原单号/换回单号
     *          'serviceAgent'  => '',// 【可选】     string  服务代理号  如果不同业务需要单独结算，双方可以定义一个
     *          'goodsVolume'   => '',// 【可选】     string  订单体积    长*宽*高（单位cm）
     *          'remarks'       => '',// 【可选】     string  重要说明
     *          'invoiceState'  => '',// 【可选】     string  发票状态    1:增值，2：普通
     *          'dataFlag'      => '',// 【可选】     string  分仓标识
     *          'sendPro'       => '',// 【可选】     string  寄件人省
     *          'sendCity'      => '',// 【可选】     string  寄件人市
     *          'senfDistrict'  => '',// 【可选】     string  寄件区
     *          'sendStreet'    => '',// 【可选】     string  寄件街道/乡镇
     *          'sendIdentityCode'=> '',//【可选】     string  寄件人身份证号
     *          'sendUnit'       => '',//【可选】     string  寄件单位
     *          'sendPhone'      => '',//【可选】     string  寄件人电话
     *          'receiveStreet'  => '',//【可选】     string  收件街道/乡镇
     *          'receiveIdentityCode'=> '',//【可选】     string  //收件身份证号
     *          'receiveUnit'     => '', // 【可选】     string  //收件单位
     *          'receivePhone'    => '',//  【可选】     string  收件电话
     *          'insuranceMode'   => '',//  【可选】     string  投保方式       2071：无，2072：委托投保，2073：运费投保，2074：自带投保，2075：租金含保（默认为无）
     *          'insuranceType'   => '', // 【可选】     string  保险类型      2061：特殊保价，2062：特约保价，2063：保价，2064：不投保，2065：小额丢失险，2066：专车险，2067：生鲜险，2068：仓储保险（不投保可不传）
     *          'goodsValue'      => '',//  【可选】     string  声明价值
     *          'codFlag'         => '',//  【可选】     string  是否代收  1：是  0：否（默认）
     *          'codAmount'       => '',//  【可选】     string  代收款
     *          'toPay'           => '', // 【可选】     string  是否到付    暂缓开通
     *          'payMode'         => '',//  【可选】     string  支付方式    1：pos,2:现金，3：扫码支付，4：其他
     *          'pickupTime'      => '',//  【可选】     string  取件时间    eg：2017-12-21 00:00:00
     *          'clientOperatecode' => '',//【可选】     string  门店编码
     *          'extendedInfo'    => {},//  【可选】   Map<string,string>  扩展字段
     *          'orderPackages'   => [{  //包裹信息
                    'packageNo'      => '',// 【可选】     string宅急送条码号  如果有包裹信息二者必填其一
                    'packageBarcode' => '',// 【可选】     string箱单号         如果有包裹信息二者必填其一
                    'packageWeight'  => '',// 【可选】     string包裹重量（单件）
                    'packageValume'  => '',// 【可选】     string包裹体积（单件）  立方米
                     'packageAmount' => '',// 【可选】     string代收款金额（单件）
                     'packageInfo'   => '',// 【可选】     string包裹信息说明   备用字段
     *                'item'         =>[{//商品信息           object   【可选】
                            'itemNo'     => '',// 【可选】     string  商品编码
     *                      'itemName'   => '',// 【可选】     string  商品名称
     *                      'itemNumber' => '',// 【可选】     string  商品数量
     *                       'itemDesc'  => '', //【可选】    string  商品描述
     *                       'itemValue' => ''  //【可选】    string  商品单价
     *                  }]
     *
     *            }],
     *		}]
     * @return  bool true：成功；false：失败
     */
    public static function zjsOrder($params){
        try{
            $base_api = env('ZJSORDER_API_URL','http://businesstest.zjs.com.cn:9200/edi/order/v1/orderTest');
            if(!isset($params) && !is_array($params)){
				self::$error = '参数必须';
                return false;
            }
            //验证参数
            foreach($params as &$item) {
                $rule = [
                    'mailNo'      => 'required',
                    'orderNo'     => 'required',
                    'busType'     => 'required',
                    'openType'  => 'required',
                    'goodsName'  => 'required',
                    'goodsNum'   => 'required',
                    'goodsWeight' => 'required',
                    'sendName'    => 'required',
                    'sendAddress' => 'required',
                    'sendMobile'  => 'required',
                    'receiveName' => 'required',
                    'receivePro'  => 'required',
                    'receiveCity' => 'required',
                    'receiveAddress' => 'required',
                    'receiveDistrict' => 'required',
                    'receiveMobile'    => 'required',
                ];
                $validator = app('validator')->make($item, $rule);
                if ($validator->fails()) {
					self::$error = '参数校验失败';
                    return false;
                }
                $item['clientFlag'] = env('ZHAIJISONG_CLIENT_FLAG','test');
            }
            $data = self::createParams($params); //获取请求参数
			LogApi::debug('宅急送下单请求参数',[
				'url' => $base_api,
				'params' => $data,
			]);
            $output = self::post($base_api,$data);//得到结果
			LogApi::debug('宅急送下单请求返回值',$output);
			
			$result= json_decode($output, true);
			if(!is_array($result)){
				self::$error = '宅急送：下单接口错误';
				LogApi::error(self::$error,$output);
                return false;
			}
			LogApi::debug('宅急送下单请求返回值解析结果',$result);
            if($result['state']!='20000'){
				self::$error = '宅急送下单错误：'.$result['reason'];
				LogApi::error(self::$error,$output);
                return false;
            }
            return true;
        }catch (\Exception $e) {
			self::$error = "宅急送下单异常:".$e->getMessage();
            LogApi::error(self::$error,$e);
            return false;
        }

    }



    /***
     * 物流轨迹查询
	 * 批处理接口
     * @param  array $no_list 运单号数组
     * ['A1111','A2222']
     * @return
     * {
            "clientFlag"=> "客户标识",  //【必选】     string  客户标识
            "description"=> "成功!",    //【必选】     string  状态描述
            "orders"=>  [{              //单号明细
            "mailNo"=>  "宅急送运单号",  //【必选】     string  运单号
            "orderNo"=>  "客户单号",     //【必选】     string  客户单号
            "mailStatus"=>  "SIGNED",    //【必选】     string  当前状态
     *
            "statusTime"=>  "2011-03-09 18:33:30", //当前状态时间    string   【可选】
            "steps"=>  [{                          //物流轨迹明细  object
                 "operationTime"     => "2011-03-08 16:26:42", //操作时间   string   【可选】
                 "operationDescribe" =>  "[XXX] 已揽收"       //操作描述    string   【可选】
            },
            {
                 "operationTime"     =>  "2011-03-08 16:26:42",
                 "operationDescribe" =>  "离开 [XXX] 发往 [XXX]"
            },
            {
                 "operationTime"     =>  "2011-03-08 16:26:42",
                 "operationDescribe" =>  "到达 [XXX]"
            },
            {
                 "operationTime"     =>  "2011-03-08 16:26:42",
                 "operationDescribe" =>  "[XXX] 派送中,递送员:xxx 电话:1234567890"
            },
            {
                 "operationTime"     =>  "2011-03-08 16:26:42",
                 "operationDescribe" =>  "客户已签收 签收人:xxx"
            }]

            }]

    }
     *
     *
     */
    public static function logisticsQuery($no_list){
        try{
            $base_api = env('LOGISTICSQUERY_API_URL','http://cntm.zjs.com.cn/interface/iwc/querystatustest'); //请求地址
            if(!is_array($no_list) || count($no_list)==0){
                return false;
            }
			$order_list = [];
            foreach ($no_list as $no){
				$order_list[] = [
					'mailNo' => $no,
				];
            }
			//请求参数
            $data = self::createParams([
                'clientFlag' => env('ZHAIJISONG_CLIENT_FLAG','test'),
				'orders' => $order_list,
			]); 
            LogApi::debug('宅急送轨迹查询请求参数',[
                'url' => $base_api,
                'params' => $data,
            ]);
            $output = self::post($base_api,$data);//得到结果
            LogApi::debug('宅急送轨迹查询请求返回值',$output);
			$result = json_decode($output, true);
            if(!$result){
				self::$error = '宅急送物流轨迹查询请求失败';
				LogApi::error(self::$error,$output);
                return false;
            }
            return $result['orders'];

        }catch (\Exception $e) {
			self::$error = "宅急送物流轨迹查询异常：".$e->getMessage();
            LogApi::error(self::$error,$e);
            return false;
        }
    }

    /***
     * 快递单号获取
     * @param $n  申请数量
     *
     * @return
     * {
        "clientFlag"  => "test",   // 【必返回】  string  客户标识
        "data"        =>["A001417770432","A001417770443","A001417770454","A0014177704
        65", "A001417770476"],    // 【可返】     string 返回单号数据
        "status"      => "Y",    // 【必返回】    string  状态码
        "msg"          =>"成功"  // 【必返回】    string  状态信息
    }
     */
    public static function logisticsNumber(int $n){
        try{
			if( $n<1 || $n>1000 ){
                return false;
			}
            $base_api = env('LOGISTICSNUMBER_API_URL','http://cntm.zjs.com.cn/interface/iwc/ctdanhaochitest');

            $data = self::createParams([
                'clientFlag'  => env('ZHAIJISONG_CLIENT_FLAG','test'),
                'key'          => env('ZJS_KEY','DH100580'),
                'applynum'    => $n
			]); //获取请求参数
			LogApi::debug('宅急送获取运单号请求参数',[
				'url' => $base_api,
				'params' => $data,
			]);
            $output = self::post($base_api,$data);//得到结果
			LogApi::debug('宅急送获取运单号请求返回值',$output);
			$result = json_decode($output,true);
            if(!$result || $result['status']!='Y'){
				self::$error = '宅急送获取运单号错误：'.$result['msg'];
				LogApi::error(self::$error,$output);
                return false;
            }
            return $result['data'];

        }catch (\Exception $e) {
			self::$error = "宅急送获取运单号异常：".$e->getMessage();
            LogApi::error(self::$error,$e);
            return false;
        }


    }

    /***
     * 分单
     * @param $params
     * 必选参数   string
         [
            {
            "orderNo"=> "", // 【必选】  string  订单号
            "address"=> ""  // 【必选】  string  收货地址
            },
            {
            "orderNo"=> "",
            "address"=> ""
             }
         ]
     * @return json
     * {
     * //必返回参数
        "code"        =>0,             // 【必返回】  int 错误编码
        "description" => "OK",        //  【必返回】  string 描述
        "data"        => [{           //json   json
            "orderNo"     => "DD12345678912",  // 【必返回】  string 订单id
            "status"      => 0,                // 【必返回】  int 调用状态 0：成功，1：失败
                //非必返回参数
            "errorCode"   => 0,                // 【可返】  int 错误编码
            "vcityCode"   => "311",            // 【可返】  string 城市编码
            "provinceName" => "河北省",        // 【可返】  string 省名称
            "cityName"     =>"石家庄市",       // 【可返】  string 城市名称
            "townName"     => "裕华区",        // 【可返】  string  区（县）名称
            "siteNo"       => "BSSK",          // 【可返】  string 配送站编码
            "siteName"     =>"河北_开发区营业厅"  // 【可返】  string 配送站名称
        },
        {
            "orderNo"      => "DD12345678911",
            "status"       =>0,
            "errorCode"    => 0,
            "vcityCode"    => "010",
            "provinceName" => "北京市",
            "cityName"     =>"北京市",
            "townName"     => "昌平区",
            "siteNo"       => "H66-9",
            "siteName"     => "天通苑 09"
        }]
    }
     */
    public static function reinsurancePolicy($params){
        try{
            if(!isset($params) && !is_array($params)){
                return false;
            }
            $base_api = env('REINSURANCEPOLICY_API_URL','http://cntm.zjs.com.cn/interface/iwc/nctfendan');
            foreach ($params as $item){
                $rule = [
                    'orderNo'  => 'required',
                    'address'  => 'required',
                ];
                $validator = app('validator')->make($item, $rule);
                if ($validator->fails()) {
                    return false;
                }
            }

            $data = self::createParams($params); //获取请求参数
			LogApi::debug('宅急送分拣请求参数',[
				'url' => $base_api,
				'params' => $data,
			]);
            $output = self::post($base_api,$data);//得到结果
			LogApi::debug('宅急送分拣请求返回值',$output);
			$result = json_decode($output,true);
            if(!is_array($result)){
				self::$error = '宅急送分单接口错误';
				LogApi::error(self::$error,$output);
                return false;
            }
			if( $result['code']!=0 ){
				self::$error = '宅急送分单接口错误:'.$result['description'];
				LogApi::error(self::$error,$output);
                return false;
			}
            return $result['data'];

        }catch (\Exception $e) {
			self::$error = "宅急送分单异常：".$e->getMessage();
            LogApi::error(self::$error,$e);
            return false;
        }

    }
    /**
     * 设置参数
     */
    public static function post($base_api,$params){
        $response = Curl::post($base_api,$params,["content-type=multipart/form-data"]);
       return $response;
    }
    /**
     * 设置请求结果
     */
    public  function setInfo($response){
         $this->info = $response;
    }
   /**
     * 获取请求结果
     */
    public function getInfo(){
        return $this->info;
    }



}