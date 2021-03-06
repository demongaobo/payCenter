<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Certification;
use App\Lib\Common\LogApi;
use App\Lib\Goods;
use App\Lib\Risk\Yajin;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use Mockery\Exception;

class DepositComponnet implements OrderCreater
{
    //组件
    private $componnet;
    //支付方式
    private $payType;
    //订单类型
    private $orderType;

    private $schema;

    private $miniCreditAmount;

    //是否满足押金减免条件
    private $deposit = true;

    private $certifiedFlag =true;

    private $flag = true;

    private $deposit_detail='';
    private $deposit_msg ='';

    private $orderNo='';

    //是否通过风控验证 0未通过 1通过
    private $risk=0;

    public function __construct(OrderCreater $componnet,$certifiedFlag=true,$miniCreditAmount = 0)
    {
        $this->componnet = $componnet;
        $this->certifiedFlag =$certifiedFlag;
        $this->miniCreditAmount =$miniCreditAmount;
        $this->orderType = $this->componnet->getOrderCreater()->getOrderType();


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
        //活动领取订单 不需要走押金接口计算押金
        if($this->orderType == OrderStatus::orderActivityService){
            return $this->flag && $filter;
        }
        $schema = $this->componnet->getDataSchema();
        $this->schema =$schema;
        $this->orderNo =$schema['order']['order_no'];
        $this->payType =$this->getOrderCreater()->getSkuComponnet()->getPayType();
        $isPhone = $this->getOrderCreater()->getSkuComponnet()->getPhoneType();//是否是手机品类

        if($this->deposit && $this->payType >0){
            //支付押金规则
            foreach ($this->schema['sku'] as $k=>$v)
            {
                if( $this->payType == \App\Order\Modules\Inc\PayInc::MiniAlipay) {//小程序入口
                    $this->componnet->getOrderCreater()->getSkuComponnet()->mini_discrease_yajin($this->miniCreditAmount, $v['yajin'], $v['mianyajin'], $v['sku_id']);
                }
                else{//其他入口
                    $arr =[
                        'appid'=>$this->schema['order']['app_id'],
                        'zujin'=>$v['amount_after_discount'] * 100, //优惠后总租金
                        'yajin'=>$v['yajin'] * 100,
                        'market_price'=>$v['market_price']*100,
                        'yiwaixian'=>$v['insurance']*100,
                        'user_id'=>$this->schema['user']['user_id'],
                        'is_order'=>1,
                        'is_mobile'=>$isPhone,
                    ];
                    LogApi::info(config('app.env')."OrderCreate-jisuan_yajin:".$this->orderNo,$arr);
                    //137，139 ,208 ,211,140渠道 走押金计算接口
                    //支持分期支付方式
                    $app = [137,139,208,211,140];
                    if(in_array($this->schema['order']['app_id'],$app)){
                        try{
                            //调用风控押金计算接口
                            $deposit = Yajin::calculate($arr);
                        }catch (\Exception $e){
                            //如果押金接口请求失败 押金不进行减免
                            LogApi::alert("OrderCreate:获取押金接口失败",$arr,[config('web.order_warning_user')]);
                            LogApi::error(config('app.env')."OrderCreate-YajinCalculate-interface-error",$arr);
                            $deposit['jianmian'] =0;
                            $deposit['yajin'] = $v['yajin'] * 100;
                            $deposit['_msg'] ='商品押金接口错误';
                            $deposit['jianmian_detail'] =[];
                        }

                        $this->risk = isset($deposit['risk'])?$deposit['risk']:0;
                    }
                    //企业租赁 免押金
                    else if($this->schema['order']['app_id'] == 210){

                        $deposit['jianmian'] =$v['yajin'] * 100;
                        $deposit['yajin'] = 0;
                        $deposit['_msg'] ='该渠道租赁免押金';
                        $deposit['jianmian_detail'] =[];

                    }else{
                        //除了139,208,210 全押金
                        $deposit['jianmian'] =0;
                        $deposit['yajin'] = $v['yajin'] * 100;
                        $deposit['_msg'] ='全押金';
                        $deposit['jianmian_detail'] =[];
                    }
                    LogApi::info(config('app.env')."OrderCreate-deposit_yajin:".$this->orderNo,$deposit);
                    $jianmian = priceFormat($deposit['jianmian'] / 100);
                    $yajin = priceFormat($deposit['yajin'] / 100);

                    $this->deposit_msg = isset($deposit['_msg'])?$deposit['_msg']:"";

                    //存放押金减免信息
                    if (!empty($deposit['jianmian_detail'])){
                        foreach ($deposit['jianmian_detail'] as $key=>$value){
                            $deposit['jianmian_detail'][$key]['jianmian'] = $deposit['jianmian_detail'][$key]['jianmian']/100;

                        }
                    }

                    $this->deposit_detail = json_encode($deposit['jianmian_detail']);
                    $this->componnet->getOrderCreater()->getSkuComponnet()->discrease_yajin($jianmian, $yajin, $v['yajin_limit'], $v['sku_id']);
                }
            }
        }
        return $this->flag && $filter;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
        $schema = $this->componnet->getDataSchema();
        $deposit['deposit']['risk'] =$this->risk;
        return array_merge($schema,$deposit);
    }

    /**
     * 创建数据
     * 1.保存押金减免详情数据
     * @return bool
     */
    public function create(): bool
    {
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        //活动领取订单 不需要走押金接口计算押金
        if($this->orderType == OrderStatus::orderActivityService){
            return true;
        }
        //保存减免押金详情信息
        $b= OrderUserCertifiedRepository::updateDepoistDetail($this->orderNo,$this->deposit_detail,$this->deposit_msg);
        if(!$b){
            LogApi::alert("OrderCreate:保存押金减免详情信息失败",['order'=>$this->orderNo],[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-UpdateDEpoist-error:".$this->orderNo);
            $this->getOrderCreater()->setError('OrderCreate-UpdateDEpoist-error');
            return false;
        }
        return true;
    }

}