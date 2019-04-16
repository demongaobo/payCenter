<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Risk\Risk;
use App\Order\Models\OrderUserCertified;
use App\Order\Modules\Repository\OrderRiskRepository;
use App\Order\Modules\Repository\OrderUserCertifiedRepository;
use App\Order\Modules\Repository\OrderYidunRepository;
use Mockery\Exception;

class RiskComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $userInfo;
    private $flag = true;

    //风控信息
    private $knight;

    //人脸是否通过
    private $isFace =false;

    public function __construct(OrderCreater $componnet)
    {
        $this->componnet = $componnet;
        $data =$this->componnet->getDataSchema();

        //获取风控信息信息
        try{
            $knight =Risk::getKnight(['user_id'=>$data['user']['user_id']]);
            $this->knight =$knight;
        }catch (\Exception $e){
            LogApi::alert("OrderCreate:获取用户风控信息失败",['user_id'=>$data['user']['user_id']],[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-GetRisk-error:".$e->getMessage());
            $this->knight =[];
        }

        $this->isFace = isset($this->knight['is_face']) && $this->knight['is_face']?true:false;

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
     * 1.企业用户 扫脸未通过不能下单
     * @return bool
     */
    public function filter(): bool
    {
        $filter =$this->componnet->filter();
        $appid = $this->getOrderCreater()->getAppid();
        if($appid ==210){
            if(!$this->isFace) {
                $this->getOrderCreater()->setError('您的面部识别未通过,无法下单');
                $this->flag = false;
            }
        }

        return $filter && $this->flag;
    }

    /**
     * 获取数据结构
     * @return array
     */
    public function getDataSchema(): array
    {
       $schema = $this->componnet->getDataSchema();
       $risk['risk'] =$this->knight;
       return array_merge($schema,$risk);
    }

    /**
     * 创建数据
     * 1.保存用户认证信息（学生信息，身份证信息）
     * @return bool
     */
    public function create(): bool
    {

        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }
        $orderNo =$this->componnet->getOrderCreater()->getOrderNo();
        if(empty($this->knight)){
            return true;
        }

        //保存用户风控信息
        $isStudent = isset($this->knight['is_chsi'])&&$this->knight['is_chsi']?1:0;  //判断是否是学生
        $certified = OrderUserCertified::where('order_no','=',$orderNo)->first();
        if (!$certified) return false;
        $certified->user_type = $isStudent;
        $certified->card_img = isset($this->knight['card_img']) && !empty($this->knight['card_img'])?$this->knight['card_img']:"";
        if (!$certified->save()) {
            LogApi::alert("OrderCreate:保存用户风控信息失败",['order_no'=>$orderNo],[config('web.order_warning_user')]);
            LogApi::error(config('app.env')."OrderCreate-Update-UserCertified-error",$this->knight);
            $this->getOrderCreater()->setError('OrderCreate-Update-UserCertified-error');
            return false;
        }


        return true;

    }
}