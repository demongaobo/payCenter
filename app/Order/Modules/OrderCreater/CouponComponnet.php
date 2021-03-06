<?php
/**
 *  优惠券组件创建器
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Coupon\Coupon;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderCouponRepository;
use Mockery\Exception;

class CouponComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;


    private $couponInfo=[];
    private $coupon=[];

    //用户信息
    private $userInfo =[];


    public function __construct(OrderCreater $componnet, array $coupon=[],$userInfo)
    {
        $this->componnet = $componnet;
        $appid =$this->componnet->getOrderCreater()->getAppid();
        $this->userInfo = $userInfo;
        if(!empty($coupon)){
            //获取优惠券类型接口
            foreach ($coupon as $k=>$v){
                $couponData[]=[
                    'coupon_no'=>$v,
                    'mobile'=>$userInfo['username'],
                    'appid'=>$appid,
                ];
            }
            try{
                $coupon = Coupon::getCoupon($couponData,$userInfo);
            }catch (\Exception $e){
                LogApi::alert("OrderCreate:获取优惠券接口失败",$couponData,[config('web.order_warning_user')]);
                LogApi::error(config('app.env')."OrderCreate-GetCoupon-error:".$e->getMessage());
                throw new Exception("获取优惠券接口错误:".$e->getMessage());
            }
            if(!is_array($coupon)){
                throw new Exception("优惠券信息错误");
            }
            if(empty($coupon)){
                throw new Exception("该优惠券已使用");
            }
            $couponInfo =[];
            foreach ($coupon as $k=>$v){
                $couponInfo[]=[
                    'coupon_id'=>$v['coupon_id'],
                    'coupon_no'=>$v['coupon_no'],
                    'coupon_type'=>$v['coupon_type'],// 1,现金券 3,首月0租金
                    'discount_amount'=>$v['coupon_value']/100,
                    'coupon_name'=>$v['coupon_name'],
                    'use_restrictions'=>$v['use_restrictions']/100,//满多少
                    'is_use'=>0,//是否使用 0未使用
                ];

            }
            $this->couponInfo = $couponInfo;
        }
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
        $filter =  $this->componnet->filter();
        $coupon =$this->couponInfo;
        //无优惠券
        if(empty($coupon)){
            return $this->flag && $filter;
        }

        //计算优惠券信息
        $this->coupon =$this->componnet->getOrderCreater()->getSkuComponnet()->discrease_coupon($coupon);
        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * 1.判断优惠券是否使用
     * 2.保存优惠券使用信息
     * 3.调用优惠券使用接口
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema =$this->componnet->getDataSchema();
        $coupon['coupon']=$this->coupon;
        return array_merge($schema,$coupon);
    }

    /**
     * 创建数据
     * @return bool
     */
    public function create(): bool
    {

        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        $data =$this->getDataSchema();
        //无优惠券
        if(empty($data['coupon'])){
            return true;
        }
        $orderNo =$this->getOrderCreater()->getOrderNo();
        $coupon =[];
        foreach ($data['coupon'] as $k=>$v){
            //判断 如果优惠券已使用 存放到订单优惠券表中
            if($v['is_use'] ==1){
                $couponData =[
                    'business_type'=>OrderStatus::BUSINESS_ZUJI,
                    'business_no'=>$orderNo,
                    'order_no'=>$orderNo,
                    'coupon_no'=>$v['coupon_no'],
                    'coupon_id'=>$v['coupon_id'],
                    'discount_amount'=>$v['discount_amount'],
                    'coupon_type'=>$v['coupon_type'],
                    'coupon_name'=>$v['coupon_name'],
                ];
                $couponId = OrderCouponRepository::add($couponData);
                if(!$couponId){
                    LogApi::alert("OrderCreate:增加订单优惠券信息失败",$couponData,[config('web.order_warning_user')]);
                    LogApi::error(config('app.env')."OrderCreate-Add-Coupon-error",$couponData);
                    $this->getOrderCreater()->setError("OrderCreate-Add-Coupon-error");
                    return false;
                }
                $coupon[] =intval($v['coupon_id']);
            }

        }
        if(empty($coupon)){
            return true;
        }
        $appid =$this->getOrderCreater()->getAppid();
        /**
         * 调用优惠券使用接口
         */
        $mobile = $this->getOrderCreater()->getUserComponnet()->getMobile();
        $coupon = Coupon::useCoupon($coupon,$this->userInfo,$appid);
        if($coupon !=ApiStatus::CODE_0){
            LogApi::alert("OrderCreate:调用优惠券使用接口失败",$coupon,[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-useCoupon-interface-error",$coupon);
            $this->getOrderCreater()->setError("OrderCreate-useCoupon-interface-error");
            return false;
        }

        return true;

    }
}