<?php
/**
 * 订单创建组件
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Company\Modules\CompanyOperate;
use App\Company\Modules\Repository\CompanyUserRepository;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Modules\Inc\OrderRiskCheckStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Repository\OrderRepository;
use Illuminate\Support\Facades\Redis;
use Mockery\Exception;

class OrderComponnet implements OrderCreater
{
    //订单ID
    private $orderId = null;
    //订单编号
    private $orderNo = null;
    //订单类型
    private $orderType;
    //用户ID
    private $userId=0;
    //支付方式
    private $payType;
    //租期类型
    private $zuqiType;
    //用户组件
    private $userComponnet =null;
    //sku组件
    private $skuComponnet =null;
   //错误提示
    private $error = '';
   //错误码
    private $errno = 0;
    //免押金状态 0：不免押金；1：全免押金
    //appid
    private $appid;
    private $mianyaStatus = 0;

    public function __construct( $orderNo='' ,int $userId,int $appid,int $orderType) {
        $this->orderNo = $orderNo;
        $this->userId =$userId;
        $this->appid=$appid;
        $this->orderType =$orderType;
    }

    /**
     *
     * 设置 User组件
     * @param UserComponnet $user_componnet
     * @return OrderCreater
     */
    public function setUserComponnet(UserComponnet $userComponnet){
        $this->userComponnet = $userComponnet;
        return $this;
    }
    /**
     * 获取 User组件
     * @return UserComponnet
     */
    public function getUserComponnet(){
        return $this->userComponnet;
    }

    /**
     * 设置 Sku组件
     * @param SkuComponnet $sku_componnet
     * @return OrderCreater
     */
    public function setSkuComponnet(SkuComponnet $skuComponnet){
        $this->skuComponnet = $skuComponnet;
        return $this;
    }
    /**
     * 获取 Sku组件
     * @return SkuComponnet
     */
    public function getSkuComponnet(): SkuComponnet{
        return $this->skuComponnet;
    }
    /**
     * 获取订单创建器
     * @return OrderCreater
     */
    public function getOrderCreater():OrderComponnet
    {
        return $this;
    }
    /**
     * 设置 错误提示
     * @param string $error  错误提示信息
     * @return OrderComponnet
     */
    public function setError( string $error ): OrderComponnet
    {
        $this->error = $error;
        return $this;
    }
    /**
     * 获取 错误提示
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * 设置 错误码
     * @param int $errno	错误码
     * @return OrderComponnet
     */
    public function setErrno( $errno ): OrderComponnet
    {
        $this->errno = $errno;
        return $this;
    }
    /**
     * 获取 错误码
     * @return int
     */
    public function getErrno(): int
    {
        return $this->errno;
    }

    /**
     * 获取 订单编号
     * @return string
     */
    public function getOrderNo(): string
    {
        return $this->orderNo;
    }

    /**
     * 获取订单ID
     * @return int
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
 * 获取appid
 * @return int
 */
    public function getAppid(): int
    {
        return $this->appid;
    }

    /**
     * 获取orderType
     * @return int
     */
    public function getOrderType(): int
    {
        return $this->orderType;
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
        //判断是否有其他活跃 未完成订单
        $mobile = $this->getOrderCreater()->getUserComponnet()->getMobile();
        $this->payType =$this->getOrderCreater()->getSkuComponnet()->getPayType();
        $certNo = $this->getOrderCreater()->getUserComponnet()->getCertNo();
        //获取订单租期类型
        $zuqiType = $this->getOrderCreater()->getSkuComponnet()->getZuqiType();
       // $res =Redis::set("OrderWhiteList",json_encode([$mobile]));
        $res = Redis::get("OrderWhiteList");

        $whiteList = json_decode($res,true);

        //企业租赁
        if($this->appid == 210){
            //查询是否可以下单
            $b =CompanyOperate::unCompledOrderByUser($this->appid,['username'=>$mobile,'uid'=>$this->userId]);
            if(!$b){
                $this->getOrderCreater()->setError(get_msg());
                return false;
            }

        }
        //区块链小程序 只允许有两单 和 其他小程序互不影响
        elseif ($this->appid == 211){
            $count =OrderRepository::getValidOrder(['user_id'=>$this->userId,'appid'=>211]);
            if($count>2){
                set_code(ApiStatus::CODE_30006);
                $this->getOrderCreater()->setError('该用户只允许有两个有效订单');
                return false;
            }
            //根据身份证号码进行判断 一个身份证只能下两单
            if($certNo !=''){
                $b =OrderRepository::unCompledOrderByCertNo($certNo,'211');
                if($b>2) {
                    set_code(ApiStatus::CODE_30006);
                    $this->getOrderCreater()->setError('此身份证只允许有两个有效订单');
                    return false;
                }
            }
        }
        //小程序用户 只允许有一个长租订单 除了区块链小程序 appid==211
        elseif ($this->orderType == OrderStatus::orderMiniService && $this->appid !=211){
            if($zuqiType == OrderStatus::ZUQI_TYPE_MONTH){
                $b =OrderRepository::getValidOrder(['user_id'=>$this->userId,'order_type'=>$this->orderType,'zuqi_type'=>$zuqiType]);
                if($b){
                    set_code(ApiStatus::CODE_30006);
                    $this->getOrderCreater()->setError('只允许有一个长租订单');
                    return false;
                }
            }
        }
        //非小程序 和 企业订单
        else{
            //白名单允许下多单
            if(empty($res) || (is_array($whiteList) && !in_array($mobile,$whiteList))){
                //根据用户ID判断
                $b =OrderRepository::getValidOrder(['user_id'=>$this->userId],210);
                if($b) {
                    set_code(ApiStatus::CODE_30006);
                    $this->getOrderCreater()->setError('有未完成订单');
                    return false;
                }
                //根据身份证号码进行判断 一个身份证只能下一单
                if($certNo !=''){
                    $b =OrderRepository::unCompledOrderByCertNo($certNo,'','210');
                    if($b) {
                        set_code(ApiStatus::CODE_30006);
                        $this->getOrderCreater()->setError('有未完成订单');
                        return false;
                    }
                }

            }
        }

        $b = $this->userComponnet->filter();
        if( !$b ){
            return false;
        }
        $b = $this->skuComponnet->filter();
        if( !$b ){
            return false;
        }
        return true;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $userSchema = $this->userComponnet->getDataSchema();
        $skuSchema =$this->skuComponnet->getDataSchema();
        $this->zuqiType =$this->skuComponnet->getZuqiType();
        $zuqiTypeName =$this->skuComponnet->getZuqiTypeName();
        return array_merge(['order'=>[
            'order_no'=>$this->orderNo,
            'zuqi_type'=>$this->zuqiType,
            'zuqi_type_name'=>$zuqiTypeName,
            'pay_type'=>$this->payType,
            'order_type'=>$this->orderType,
            'app_id'=>$this->appid,
        ]],$userSchema,$skuSchema);

    }


    /**
     * 创建数据
     * 1.执行用户组件的create方法
     * 2.执行商品组件的create方法
     * 3.判断是否有货长短租的 预发货时间
     * 4.如果为活动订单 则转换为线下订单
     * 5.保存订单数据
     * @return bool
     */
    public function create(): bool
    {
        $data = $this->getOrderCreater()->getDataSchema();
        // 执行 User组件
        $b = $this->userComponnet->create();
        if (!$b) {
            return false;
        }
        // 执行 Sku组件
        $b = $this->skuComponnet->create();
        if (!$b) {
            return false;
        }
        $order_amount = 0;
        $goods_yajin = 0;
        $order_yajin = 0;
        $order_insurance = 0;
        $coupon_amount = 0;
        $discount_amount = 0;

        foreach ($data['sku'] as $k => $v) {
            for ($i = 0; $i < $v['sku_num']; $i++) {
                $order_amount += $v['amount_after_discount'];
                $goods_yajin += $v['yajin'];
                $order_yajin += $v['deposit_yajin'];
                $order_insurance += $v['insurance'];
                $coupon_amount += ($v['first_coupon_amount']+$v['order_coupon_amount']);
                $discount_amount += $v['discount_amount'];
                $kucun = $v['kucun'];
                $startTime = $v['begin_time'];
                $shopId = $v['shop_id'];
                $agentId = $v['agent_id'];
            }
        }

        //判断库存 如果 无库存的预计发货时间：长租（下单时间+7天） 短租（起租时间 -3天） 有库存的预计发货时间为确认时间当天
        if($kucun<= 0){
            //预计发货时间：长租（下单时间+7天）
            if($this->zuqiType == OrderStatus::ZUQI_TYPE_MONTH){
                $PredictDeliveryTime = strtotime("+4 day");
            }
            //
            if($this->zuqiType == OrderStatus::ZUQI_TYPE_DAY){
                $PredictDeliveryTime = strtotime($startTime)- 3*86400;
            }
        }
        //如果有货
        else{
            //长租 预定发货时间为第二天下午15点
            if($this->zuqiType == OrderStatus::ZUQI_TYPE_MONTH){
                // 如果小于等于 是否小于15点 15点之前为当天发货时间 如果 15点之后为第二天15点前发货
                if(time() >=strtotime("Y-m-d")+3600*15){
                    $PredictDeliveryTime = strtotime(date("Y-m-d",strtotime("+1 day")))+3600*15;
                }else{
                    $PredictDeliveryTime =strtotime("Y-m-d")+3600*15;
                }
            }
            //判断 起租时间 和当前时间差 如果大于三天 预计发货时间： 短租（起租时间 -3天）
            if($this->zuqiType == OrderStatus::ZUQI_TYPE_DAY){
                if((strtotime($startTime)- 3*86400) > strtotime(date("Y-m-d"))){
                    $PredictDeliveryTime = strtotime($startTime)- 3*86400;
                }
                // 如果小于等于 是否小于15点 15点之前为当天发货时间 如果 15点之后为第二天15点前发货
                else{
                    if(time() >=strtotime("Y-m-d")+3600*15){
                        $PredictDeliveryTime = strtotime(date("Y-m-d",strtotime("+1 day")))+3600*15;
                    }else{
                        $PredictDeliveryTime =strtotime("Y-m-d")+3600*15;
                    }

                }
            }

        }


        $checkStatus = OrderRiskCheckStatus::SystemPass;
        if($this->appid ==137 || $this->appid ==139 || $this->appid == 208 || $this->orderType == OrderStatus::orderMiniService){
            $checkStatus = OrderRiskCheckStatus::Non;
        }

        if($this->orderType == OrderStatus::orderActivityService){
            $this->orderType = OrderStatus::orderStoreService;
        }

        $orderData = [
            'order_status' => OrderStatus::OrderWaitPaying,
            'order_no' => $this->orderNo,  // 编号
            'user_id' => $this->userId,
            'pay_type' => $this->payType,
            'zuqi_type'=>$this->zuqiType,
            'order_amount' => $order_amount,
            'goods_yajin' => $goods_yajin,
            'order_yajin' => $order_yajin,
            'order_insurance' => $order_insurance,
            'coupon_amount' => $coupon_amount,
            'discount_amount' => $discount_amount,
            'appid' =>$this->appid,
            'shop_id'=> $shopId,
            'agent_id'=> $agentId,
            'create_time'=>time(),
            'order_type'=>$this->orderType,
            'mobile'=>$data['user']['user_mobile'],
            'predict_delivery_time'=>$PredictDeliveryTime,
            'risk_check'=>$checkStatus,
        ];

        $orderRepository = new OrderRepository();
        $orderId = $orderRepository->add($orderData);
        if (!$orderId) {
            LogApi::alert("OrderCreate:保存订单信息失败",$orderData,[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-Add-OrderData-error",$orderData);
            $this->getOrderCreater()->setError('OrderCreate-Add-OrderData-error');
            return false;
        }

        return true;
    }

}
