<?php
/**
 * 商品创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Common\LogApi;
use App\Lib\Goods\Goods;
use App\Order\Models\OrderGoodsExtend;
use App\Order\Models\OrderGoodsIncrement;
use App\Order\Modules\Inc\CouponStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\Specifications;
use App\Order\Modules\Repository\Order\DeliveryDetail;
use App\Order\Modules\Repository\Order\ServicePeriod;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\OrderGoodsUnitRepository;
use App\Order\Modules\Repository\OrderRepository;
use Mockery\Exception;

/**
 * SKU 组件
 * 处理订单中商品的基本信息
 */
class SkuComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    //租期类型
    private $zuqiType=1;
    private $zuqiTypeName;
    private $orderType=0;//订单类型

    private $goodsArr;
    //支付方式
    private $payType;
    private $deposit=[];
    private $couponInfo=[];
    private $sku=[];

    //规格
    private $specs;

    private $orderYajin=0;   //订单押金
    private $orderZujin=0;  //订单租金+意外险
    private $orderFenqi=0; //订单分期数
    private $orderInsurance=0;//订单 意外险


    //短租租用时间
    private $beginTime=0;
    private $endTime=0;

    //商品所属供应商渠道Id
    private $supplierChannelId =0;


    //是否是手机品类 0 否 1是
    private $isPhone =0;



	/**
	 *  获取商品信息
	 * @param \App\Order\Modules\OrderCreater\OrderCreater $componnet
	 * @param array $sku
	 * [
	 *		'sku_id' => '',		//【必选】SKU ID
	 *		'sku_num' => '',	//【必选】SKU 数量
     *      'increment_info'=>[1,2] //【可选】增值服务
	 * ]
	 * @param int $payType  //创建订单才有支付方式
	 * @throws Exception
	 */
    public function __construct(OrderCreater $componnet, array $sku,int $payType =0, $appId = 1)
    {
        $this->componnet = $componnet;
        $mobile = $this->componnet->getOrderCreater()->getUserComponnet()->getMobile();
        //遍历商品 下的增值服务
        for($i=0;$i<count($sku);$i++){
            if(!isset($sku[$i]['increment_info']) || empty($sku[$i]['increment_info']) || !is_array($sku[$i]['increment_info'])){
                $sku[$i]['increment_info'] = [0];
            }
        }
        try{
            $goodsArr = Goods::getSkuList( $sku,$mobile,$appId);
          //  var_dump($goodsArr[$sku[0]['sku_id']]['spu_info']);die;
        }catch (\Exception $e){
            LogApi::alert("OrderCreate:获取商品接口失败",array_column($sku, 'sku_id'),[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-GetSkuList-error:".$e->getMessage());
            throw new Exception("GetSkuList:".$e->getMessage());
        }


        //商品数量付值到商品信息中
        for($i=0;$i<count($sku);$i++){
            $skuNum =$sku[$i]['sku_num'];
            $skuId =$sku[$i]['sku_id'];
            //查询商品支付列表
            if(empty($goodsArr[$skuId]['spu_info']['payment_list'][0]['id']) || !isset($goodsArr[$skuId]['spu_info']['payment_list'][0]['id'])){
                LogApi::alert("OrderCreate:商品支付方式错误",$goodsArr[$skuId]['spu_info'],[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."OrderCreate-PayType-error:".$skuId);
                throw new Exception("商品支付方式错误");
            }
            //默认 获取 商品列表的第一个支付方式
            $this->payType =$payType?$payType:$goodsArr[$skuId]['spu_info']['payment_list'][0]['id'];
            //获取商品所属供应商渠道ID
            $this->supplierChannelId = isset($goodsArr[$skuId]['spu_info']['supplier_channel_id'])?$goodsArr[$skuId]['spu_info']['supplier_channel_id']:0;
            //租期类型
            $this->zuqiType = $goodsArr[$skuId]['sku_info']['zuqi_type'];
            //如果为短租 商品租期为前端传递过来
            $goodsArr[$skuId]['sku_info']['begin_time'] =isset($sku[$i]['begin_time'])&&$this->zuqiType == 1?$sku[$i]['begin_time']:"";
            $goodsArr[$skuId]['sku_info']['end_time'] =isset($sku[$i]['end_time'])&&$this->zuqiType == 1?$sku[$i]['end_time']:"";
            $goodsArr[$skuId]['sku_info']['sku_num'] = $skuNum;
            $goodsArr[$skuId]['sku_info']['goods_no'] = createNo(6);

            if ($this->zuqiType == OrderStatus::ZUQI_TYPE_DAY) {
                $this->zuqiTypeName = "day";
                //计算短租租期
                $goodsArr[$skuId]['sku_info']['zuqi'] = ((strtotime($goodsArr[$skuId]['sku_info']['end_time']) -strtotime($goodsArr[$skuId]['sku_info']['begin_time']))/86400)+1;
            } elseif ($this->zuqiType == OrderStatus::ZUQI_TYPE_MONTH) {
                $this->zuqiTypeName = "month";
            }

            //手机品类
            $this->isPhone = isset($goodsArr[$skuId]['spu_info']['phone_type'])?$goodsArr[$skuId]['spu_info']['phone_type']:0;

            $spec =$goodsArr[$skuId]['sku_info']['spec'];
            if(!is_array($spec)){
                $spec = json_decode($goodsArr[$skuId]['sku_info']['spec'],true);
            }
            // 格式化 规格
            $_specs = [];
            foreach($spec as $it){
                //不存储租期
                if($it['id'] !=4){
                    $_specs[] = filter_array($it, [
                        'id' => 'required',
                        'name' => 'required',
                        'value' => 'required',
                    ]);
                }
            }
            $this->specs = $_specs;

        }
        $this->goodsArr =$goodsArr;
        $this->orderType =$this->componnet->getOrderCreater()->getOrderType();
    }


    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this->componnet->getOrderCreater();
    }
    /**
     * 过滤
     * <p>注意：</p>
     * <p>在过滤过程中，可以修改下单需要的元数据</p>
     * <p>组件之间的过滤操作互不影响</p>
     * <p>先执行内部组件的filter()，然后再执行组件本身的过滤</p>
     * @return bool
     */
    public function filter(): bool
    {
        //判断租期类型 长租只能租一个商品
        $skuInfo = array_column($this->goodsArr,'sku_info');
        for ($i=0;$i<count($skuInfo);$i++){
            if($this->zuqiType ==2 && (count($skuInfo) >1 || $skuInfo[$i]['sku_num'] >1)){
                $this->getOrderCreater()->setError('不支持多商品添加');
                $this->flag = false;
            }
        }
        $arr =[];
        foreach ($this->goodsArr as $k=>$v){
            $skuInfo =$v['sku_info'];
            $spuInfo =$v['spu_info'];

            // 计算金额
            $amount = $skuInfo['zuqi']*$skuInfo['shop_price'];
            if($amount <0){
                $this->getOrderCreater()->setError('商品金额错误');
                $this->flag = false;
            }
            // 库存量
//            if($skuInfo['number']<$skuInfo['sku_num']){
//                $this->getOrderCreater()->setError('商品库存不足');
//                $this->flag = false;
//            }
            // 商品上下架状态、
            if($skuInfo['status'] !=1){
                $this->getOrderCreater()->setError('商品已下架');
                $this->flag = false;
            }
            // 成色 100,99,90,80,70,60
            if( $skuInfo['chengse']<1 || $skuInfo['chengse']>100 ){
                $this->getOrderCreater()->setError('商品成色错误');
                $this->flag = false;
            }
            if( $this->zuqiType == OrderStatus::ZUQI_TYPE_DAY ){ // 天
                // 租期[3,31]之间的正整数
                if( $skuInfo['zuqi']<1){
                    $this->getOrderCreater()->setError('商品租期错误');
                    $this->flag = false;
                }
                //判断开始日期 和结束日期 必须大于今天
                if( $skuInfo['begin_time'] < date("Y-m-d") || $skuInfo['end_time'] < date("Y-m-d")){
                    $this->getOrderCreater()->setError('商品开始日期或结束日期错误');
                    $this->flag = false;
                }
            }else{
                // 租期[1,12]之间的正整数
                if( $skuInfo['zuqi']<1 || $skuInfo['zuqi']>12 ){
                    $this->getOrderCreater()->setError('商品租期错误');
                    $this->flag = false;
                }
            }
            // 押金必须
            if( $skuInfo['yajin'] < 0){
                $this->getOrderCreater()->setError('商品押金错误');
                $this->flag = false;
            }

        }

        return $this->flag;
    }

    /**
     * 获取品类信息
     * @return int
     */
    public function getPhoneType(){
        return $this->isPhone;
    }

    /**
     * 获取支付方式
     * @return int
     */
    public function getPayType(){
        return $this->payType;
    }
    /**
     * 获取租期类型
     * @return int
     */
    public function getZuqiType(){
        return $this->zuqiType;
    }
    /**
     * 获取租期类型名称
     * @return string
     */
    public function getZuqiTypeName(){
        return $this->zuqiTypeName;
    }
    /**
     * 获取订单押金
     * @return string
     */
    public function getOrderYajin(){
        return $this->orderYajin;
    }
    /**
     * 获取订单租金
     * @return string
     */
    public function getOrderZujin(){
        return $this->orderZujin;
    }
    /**
     * 获取订单分期
     * @return int
     */
    public function getOrderFenqi(){
        return $this->orderFenqi;
    }

    /**
     * 获取订单意外险
     * @return string
     */
    public function getOrderInsurance(){
        return $this->orderInsurance;
    }
    /**
     * 商品所属供应商渠道Id
     * @return int
     */
    public function getSupplierChannelId(){
        return $this->supplierChannelId;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        foreach ($this->goodsArr as $k=>$v) {

            $skuInfo = $v['sku_info'];
            $spuInfo = $v['spu_info'];
            //首月0租金优惠金额
            $first_coupon_amount =isset($this->sku[$skuInfo['sku_id']]['first_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['first_coupon_amount']):"0.00";
            //订单固定金额优惠券
            $order_coupon_amount =isset($this->sku[$skuInfo['sku_id']]['order_coupon_amount'])?normalizeNum($this->sku[$skuInfo['sku_id']]['order_coupon_amount']):"0.00";
            //计算后的押金金额 - 应缴押金金额
            $deposit_yajin =isset($this->deposit[$skuInfo['sku_id']]['deposit_yajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['deposit_yajin']):$skuInfo['yajin'];
            //计算减免金额
            $mianyajin = isset($this->deposit[$skuInfo['sku_id']]['mianyajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['mianyajin']):"0.00";
            //计算免押金金额
            $jianmian =isset($this->deposit[$skuInfo['sku_id']]['jianmian'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['jianmian']):"0.00";
            //计算原始押金
            $yajin =isset($this->deposit[$skuInfo['sku_id']]['yajin'])?normalizeNum($this->deposit[$skuInfo['sku_id']]['yajin']):$skuInfo['yajin'];

            //计算买断金额
            $buyout_amount =    empty($skuInfo['buyout_price']) ? normalizeNum( max(0,normalizeNum($skuInfo['market_price'] * 1.2-$skuInfo['shop_price'] * $skuInfo['zuqi']))  ) :  normalizeNum($skuInfo['buyout_price']);
            //判断 如果优惠金额大于总租金 优惠金额为 总租金
            if($order_coupon_amount>$skuInfo['shop_price']*$skuInfo['zuqi']){
                $order_coupon_amount =$skuInfo['shop_price']*$skuInfo['zuqi'];
            }
            //计算优惠后的总租金
            $amount_after_discount =normalizeNum($skuInfo['shop_price']*$skuInfo['zuqi']-$first_coupon_amount-$order_coupon_amount);
            if($amount_after_discount <0){
                $amount_after_discount =0.00;
            }

            //商品增值服务
            $spuInfo = $v['spu_info'];
            $incrementInfo = $v['increment_info']??[];
            //意外险字段 存放所有增值服务的具体金额
            if(!empty($incrementInfo)){
                $insurance = 0;
                foreach ($incrementInfo as &$v){
                    if(isset($v['amount'])){
                        $insurance += $v['amount'];
                    }
                }
            }else{
                $insurance = $spuInfo['yiwaixian'];
            }



            //设置订单金额的赋值 （目前一个商品 就暂时写死 多个商品后 根据文案 进行修改）
            $this->orderZujin =$amount_after_discount+$insurance;
            $this->orderFenqi =intval($skuInfo['zuqi_type']) ==1?1:intval($skuInfo['zuqi']);
            $this->orderYajin =$deposit_yajin;
            $this->orderInsurance =$insurance;

            //如果是活动领取接口  押金 意外险 租金都 为0
            if($this->orderType == OrderStatus::orderActivityService){
                $jianmian = $this->orderYajin;
                $mianyajin = $this->orderYajin;
                $this->orderZujin = 0.00;
                $this->orderYajin = 0.00;
                $deposit_yajin = 0.00;
                $insurance =0.00; //意外险
                $amount_after_discount =0.00;
                $buyout_amount =  empty($skuInfo['buyout_price']) ?  normalizeNum( max(0,normalizeNum($skuInfo['market_price'] * 1.2))) : normalizeNum($skuInfo['buyout_price']);

            }


            //数据重组
            $arr['sku'][] = [
                    'sku_id' => intval($skuInfo['sku_id']),
                    'spu_id' => intval($skuInfo['spu_id']),
                    'sku_name' => $skuInfo['sku_name'],
                    'spu_name' => $spuInfo['name'],
                    'sku_no' => $skuInfo['sn'],
                    'spu_no' => $spuInfo['sn'],
                    'goods_no'=>$skuInfo['goods_no'],
                    'weight' => $skuInfo['weight'],
                    'edition' => $skuInfo['edition'],
                    'sku_num' => intval($skuInfo['sku_num']),
                    'kucun' => intval($skuInfo['number']),
                    'brand_id' => intval($spuInfo['brand_id']),
                    'category_id' => intval($spuInfo['catid']),
                    'machine_id' => intval($spuInfo['machine_id']),//机型ID
                    'specs' => $this->specs, //规格
                    'thumb' => $spuInfo['thumb'], //商品缩略图
                    'insurance' =>normalizeNum($insurance),//$spuInfo['yiwaixian'], //意外险
                    'insurance_cost' => $spuInfo['yiwaixian_cost'], //意外险成本价
                    'zujin' => $skuInfo['shop_price'], //租金
                    'yajin' => $yajin, //商品押金
                    'shop_id' => isset($spuInfo['shop_id'])?intval($spuInfo['shop_id']):0, //商家ID
                    'agent_id' => isset($spuInfo['shop_info']['agent_id'])?intval($spuInfo['shop_info']['agent_id']):0, //代理商ID
                    'zuqi' => intval($skuInfo['zuqi']),
                    'zuqi_type' => intval($skuInfo['zuqi_type']),
                    'zuqi_type_name' => $this->zuqiTypeName,
                    'buyout_price' => $buyout_amount,
                    'market_price' => $skuInfo['market_price'],
                    'machine_value' => isset($spuInfo['machine_name'])?$spuInfo['machine_name']:"",//机型名称
                    'chengse' => $skuInfo['chengse'],//商品成色
                    'stock' => intval($skuInfo['number']),
                    'pay_type' => $this->payType,
                    'channel_id'=>intval($spuInfo['channel_id']),
                    'discount_amount' => 0,//$skuInfo['buyout_price'], //商品优惠金额 （商品系统为buyout_price字段）
                    'amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$insurance),
                    'all_amount'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])+$insurance),
                    'total_zujin'=>normalizeNum($skuInfo['shop_price']*intval($skuInfo['zuqi'])),
                    'yajin_limit'=>normalizeNum($spuInfo['yajin_limit']), //最小押金值
                    'first_coupon_amount' => normalizeNum($first_coupon_amount),
                    'order_coupon_amount' => normalizeNum($order_coupon_amount),
                    'mianyajin' => $mianyajin,
                    'jianmian' => $jianmian,
                    'deposit_yajin' => $deposit_yajin,//应缴押金
                    'amount_after_discount'=>$amount_after_discount,
                    'begin_time'=>$this->beginTime?:$skuInfo['begin_time'],
                    'end_time'=>$this->endTime?:$skuInfo['end_time'],
                    'increment_info'=>$incrementInfo,
            ];
        }
        return $arr;
    }
    /**
     *  小程序计算押金
     * @param int $amount
     */
    public function mini_discrease_yajin($jianmian,$yajin,$mianyajin,$sku_id): array{
        if( $jianmian<0 ){
            return [];
        }
        // 优惠金额 大于 总金额 时，总金额设置为0.01
        if( $jianmian >= $yajin ){
            $jianmian = $yajin;
        }
        $arr[$sku_id]['deposit_yajin'] = $yajin -$jianmian;// 更新押金
        $arr[$sku_id]['mianyajin'] = $mianyajin +$jianmian;// 更新免押金额
        $arr[$sku_id]['jianmian'] = $jianmian;
        $this->deposit =$arr;
        return $arr;
    }
    /**
     *  计算押金
     * @param int $amount
     */
    public function discrease_yajin($jianmian,$yajin,$yajinLimit,$sku_id): array{
        if( $jianmian<0 ){
            return [];
        }
        //判断如果押金限额 大于 风控押金值 取押金限额
        if($yajinLimit > $yajin){
            $yajin =$yajinLimit;
        }

        // 优惠金额 大于 总金额 时，总金额设置为0.01
        $arr[$sku_id]['deposit_yajin'] = $yajin;// 更新押金
        $arr[$sku_id]['mianyajin'] = $jianmian;// 更新免押金额
        $arr[$sku_id]['jianmian'] = $jianmian;
        $arr[$sku_id]['yajin'] = $yajin+$jianmian;
        $this->deposit =$arr;
        return $arr;
    }
    /**
     *  覆盖 租用时间
     * @param $beginTime
     * @param $endTime
     * @return true
     */
    public function unitTime($beginTime,$endTime): bool {

        $this->beginTime =$beginTime;
        $this->endTime = $endTime;
        return true;
    }
    /**
     * 计算优惠券信息
     * @param $coupon 优惠券信息 二维数组
     * [
     * [
     *  'coupon_id'=>$v['coupon_id'],
     *  'coupon_no'=>$v['coupon_no'],
     *  'coupon_type'=>$v['coupon_type'],// 1,现金券 3,首月0租金
     *  'discount_amount'=>$v['coupon_value']/100,
     *  'coupon_name'=>$v['coupon_name'],
     *  'use_restrictions'=>$v['use_restrictions']/100,//满多少
     *  'is_use'=>0,//是否使用 0未使用s
     * ]
     *]
     * @return array 优惠券 计算后的信息
     */
    public function discrease_coupon($coupon){
        $schema =$this->getDataSchema();
        $sku =$schema['sku'];
        //计算总租金
        $totalAmount =0;
        foreach ($sku as $k=>$v){
            $totalAmount +=($v['zuqi']*$v['zujin'])*$v['sku_num'];
        }
        $zongyouhui=0;
        foreach ($sku as $k => $v) {
            for ($i =0;$i<$v['sku_num'];$i++){
                $youhui =0;
                $zongzujin = $v['zuqi'] * $v['zujin'];
                foreach ($coupon as $key=>$val) {

                    $skuyouhui[$v['sku_id']]['order_coupon_amount'] =0;
                    //首月0租金 coupon_type =3
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFirstMonthRentFree && $v['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH) {
                        $skuyouhui[$v['sku_id']]['first_coupon_amount'] = $v['zujin'];
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = $v['zujin'];
                    }
                    //现金券 coupon_type =1  分期递减 coupon_type =4  总金额和现金券计算同等
                    if ($val['coupon_type'] == CouponStatus::CouponTypeFixed || $val['coupon_type'] == CouponStatus::CouponTypeDecline ) {

                        if ($v['zuqi_type'] == OrderStatus::ZUQI_TYPE_MONTH) {
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'];
                        } else {

                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] = round($val['discount_amount'] / $totalAmount * $zongzujin, 2);
                            if ($k == count($sku) - 1 && $i ==$v['sku_num']-1) {
                                $skuyouhui[$v['sku_id']]['order_coupon_amount'] = $val['discount_amount'] - $zongyouhui;
                            }else{
                                $zongyouhui += $skuyouhui[$v['sku_id']]['order_coupon_amount'];
                            }
                        }
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = $val['discount_amount'];
                    }
                    //租金折扣券 coupon_type =2  四舍五入 保留一位小数
                    if ($val['coupon_type'] == CouponStatus::CouponTypePercentage) {

                        $skuyouhui[$v['sku_id']]['order_coupon_amount'] =round($zongzujin-$zongzujin*$val['discount_amount'],1);
                        $coupon[$key]['is_use'] = 1;
                        $coupon[$key]['discount_amount'] = round($zongzujin-$zongzujin*$val['discount_amount'],1);
                    }
                    //满减券 coupon_type =5
                    if ($val['coupon_type'] == CouponStatus::CouponFullSubtraction) {
                        if($zongzujin>= $val['use_restrictions']){
                            $skuyouhui[$v['sku_id']]['order_coupon_amount'] =$val['discount_amount'];
                            $coupon[$key]['is_use'] = 1;
                            $coupon[$key]['discount_amount'] = $val['discount_amount'];
                        }
                    }
                }
            }
        }
        $this->sku =$skuyouhui;
        return $coupon;
    }
    /**
     * 创建数据
     * 1.生成商品表
     * 2.短租生成商品服务表
     * 3.保存商品还机地址
     * 4.保存商品增值服务信息
     * 5.调用商品减少库存接口
     * @return bool
     */
    public function create(): bool
    {
        $data = $this->componnet->getDataSchema();
        $userId =$this->componnet->getOrderCreater()->getUserComponnet()->getUserId();
        $orderNo=$this->componnet->getOrderCreater()->getOrderNo();
        $goodsRepository = new OrderGoodsRepository();
        $goodsArr=[];
        foreach ($data['sku'] as $k=>$v){
            if($v['kucun']>=$v['sku_num']){
                $goodsArr[] = [
                    'sku_id'=>$v['sku_id'],
                    'spu_id'=>$v['spu_id'],
                    'num'=>$v['sku_num']
                ];
            }
            for($i=0;$i<$v['sku_num'];$i++){
                $goodsData =[
                    'goods_name'=>$v['spu_name'],
                    'zuji_goods_id'=>$v['sku_id'],
                    'zuji_goods_sn'=>$v['sku_no'],
                    'goods_thumb'=>$v['thumb'],
                    'goods_no'=>$v['goods_no'],
                    'prod_id'=>$v['spu_id'],
                    'prod_no'=>$v['spu_no'],
                    'brand_id'=>$v['brand_id'],
                    'category_id'=>$v['category_id'],
                    'machine_id'=>$v['machine_id'],
                    'user_id'=>$userId,
                    'shop_id'=>$v['shop_id'],
                    'agent_id'=>$v['agent_id'],
                    'quantity'=>1,
                    'goods_yajin'=>$v['yajin'],
                    'yajin'=>$v['deposit_yajin'],
                    'surplus_yajin'=>$v['deposit_yajin'],
                    'zuqi'=>$v['zuqi'],
                    'zuqi_type'=>$v['zuqi_type'],
                    'zujin'=>$v['zujin'],
                    'order_no'=>$orderNo,
                    'machine_value'=>$v['machine_value'],
                    'chengse'=>$v['chengse'],
                    'discount_amount'=>$v['discount_amount'],
                    'coupon_amount'=>$v['first_coupon_amount']+$v['order_coupon_amount'],
                    'amount_after_discount'=>$v['amount_after_discount'],
                    'edition'=>$v['edition'],
                    'market_price'=>$v['market_price'],
                    'price'=>$v['amount_after_discount'] + $v['insurance'],
                    'specs'=>Specifications::input_format($v['specs']),
                    'insurance'=>$v['insurance'],
                    'insurance_cost'=>$v['insurance_cost'],
                    'buyout_price'=>$v['buyout_price'],
                    'weight'=>$v['weight'],
                    'create_time'=>time(),
                ];
                //如果是短租 把租期时间写到goods 和goods_unit 中(小程序续租时间为75天)
                if($this->zuqiType ==1){
                    $goodsData['begin_time'] =strtotime($v['begin_time']);
                    $goodsData['end_time'] =strtotime($v['end_time']." 23:59:59");

                    $zuqi =ceil((strtotime($v['end_time'])-strtotime($v['begin_time']))/86400+1);
                    $goodsData['zuqi'] = $zuqi;
                    if( $this->orderType == OrderStatus::orderMiniService ){//小程序
                        $goodsData['relet_day'] = 75;
                    }else{//非小程序
                        $goodsData['relet_day'] = 0;
                    }
                    $unitData['unit_value'] =$zuqi;
                    $unitData['unit'] =1;
                    $unitData['goods_no'] =$goodsData['goods_no'];
                    $unitData['order_no'] =$orderNo;
                    $unitData['user_id'] =$userId;
                    $unitData['begin_time'] =$goodsData['begin_time'];
                    $unitData['end_time'] =$goodsData['end_time'];

                    $b =ServicePeriod::createService($unitData);
                    if(!$b){
                        LogApi::alert("OrderCreate:创建服务失败",$unitData,[config('web.order_warning_user')]);
                        LogApi::error(config('app.env')."OrderCreate-Add-Unit-error",$unitData);
                        $this->getOrderCreater()->setError("OrderCreate-Add-Unit-error");
                        return false;
                    }
                }
                $goodsId =$goodsRepository->add($goodsData);
                if(!$goodsId){
                    LogApi::alert("OrderCreate:增加商品失败",$goodsData,[config('web.order_warning_user')]);
                    LogApi::error(config('app.env')."OrderCreate-AddGoods-error",$goodsData);
                    $this->getOrderCreater()->setError("OrderCreate-AddGoods-error");
                    return false;
                }

                /**
                 * 保存商品的还机回寄地址
                 */
                $returnAddressValue = $this->goodsArr[$v['sku_id']]['return_address_info']['return_address_value']??'';//还机回寄地址
                $returnName = $this->goodsArr[$v['sku_id']]['return_address_info']['return_name']??'';//还机回寄收货人姓名
                $returnPhone = $this->goodsArr[$v['sku_id']]['return_address_info']['return_phone']??'';//还机回寄收货人电话

                $returnInfo =[
                    'order_no'=>$orderNo,
                    'goods_no'=>$v['goods_no'],
                    'return_name'=>$returnName,
                    'return_phone'=>$returnPhone,
                    'return_address_value'=>$returnAddressValue,
                    'create_time'=>time()
                ];
                $info = OrderGoodsExtend::create($returnInfo);
                $b= $info->getQueueableId();
                if(!$b){
                    LogApi::alert("OrderCreate:保存商品还机回寄地址失败",$returnInfo,[config('web.order_warning_user')]);
                    LogApi::error(config('app.env')."OrderCreate:保存商品还机回寄地址失败",$returnInfo);
                    $this->getOrderCreater()->setError("OrderCreate-saveReturnAddress-error");
                    return false;
                }
                /**
                 * 保存商品增值服务['order_no','goods_no','increment_id','name','amount','type','remark','create_time'];
                 */
                if(!empty($v['increment_info'])){
                    foreach ($v['increment_info'] as &$value){
                        if(isset($value['amount'])){
                            $incrementData =[
                                'order_no'=>$orderNo,
                                'goods_no'=>$v['goods_no'],
                                'increment_id'=>$value['id'],
                                'name'=>$value['name'],
                                'amount'=>$value['amount'],
                                'type'=>$value['type'],
                                'remark'=>$value['remark'],
                                'create_time'=>time()
                            ];
                            $info = OrderGoodsIncrement::create($incrementData);
                            $b= $info->getQueueableId();
                            if(!$b){
                                LogApi::alert("OrderCreate:保存商品增值服务失败",$returnInfo,[config('web.order_warning_user')]);
                                LogApi::error(config('app.env')."OrderCreate:保存商品增值服务失败",$returnInfo);
                                $this->getOrderCreater()->setError("OrderCreate-saveIncrementInfo-error");
                                return false;
                            }
                        }
                    }
                }





            }
        }

        /**
         * 在这里要调用减少库存方法
         */
        if(!empty($goodsArr)){
            $b =Goods::reduceStock($goodsArr);
            if(!$b){
                LogApi::alert("OrderCreate:减少库存接口失败",$goodsArr,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."OrderCreate-reduceStock-error",$goodsArr);
                $this->getOrderCreater()->setError("OrderCreate-reduceStock-error");
                return false;
            }
        }

        return true;
    }

}