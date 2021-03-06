<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Order\Modules\Repository\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\GoodStatus;
use App\Order\Modules\Inc\OrderFreezeStatus;
use App\Order\Modules\Inc\OrderGoodStatus;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\PayInc;
use App\Order\Modules\Inc\publicInc;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\Pay\PayCreater;
use App\Order\Modules\Repository\ReletRepository;

/**
 * 
 *
 * @author Administrator
 */
class Goods {
	
    /**
     *
     * @var OrderGoods
     */
    private $model = [];

    private $order = null;

    /**
     * 构造函数
     * @param array $data 商品原始数据
     */
    public function __construct( OrderGoods $model ) {
        $this->model = $model;
    }

    /**
     * 读取商品原始数据
     * @return array
     */
    public function getData():array{
        return $this->model->toArray();
    }


    /**
     * 获取商品对应订单
     */
    public function getOrder( ){
        if( is_null($this->order) ){
            $this->order = Order::getByNo( $this->model->order_no, true);
        }
        return $this->order;
    }

    /**
     * 修改评论状态
     * @return bool
     */
    public function updateDiscuss( ):bool{
        if($this->model->is_discuss != 0){
            return false;
        }
        $this->model->is_discuss = 1;  //已评论
        $this->model->update_time = time();
        return $this->model->save();
    }
    //-+------------------------------------------------------------------------
    // | 收货
    //-+------------------------------------------------------------------------
    /**
     *  更新服务周期
     * @param array $data
     * [
     *      'begin_time'=>''// int 服务开始时间
     *      'end_time'=>''// int 服务结束时间
     * ]
     * @return bool
     */
    public function updateGoodsServiceTime($data):bool {
        $this->model->begin_time =$data['begin_time'];
        $this->model->end_time =$data['end_time'];
        $this->model->update_time =time();
        return $this->model->save();
    }
    /**
     * 修改商品状态为租用中
     */
    public function updateStatusInService(){
        $this->model->goods_status =OrderGoodStatus::RENTING_MACHINE;
        $this->model->update_time =time();
        return $this->model->save();
    }
    //-+------------------------------------------------------------------------
    // |订单退款
    //-+------------------------------------------------------------------------
    /**
     * 申请退款
     */
    public function orderRefund( ):bool{
        if( $this->model->goods_status != OrderGoodStatus::INIT){
            return false;
        }
        // 状态改为退款中
        $this->model->goods_status = OrderGoodStatus::REFUND;
        return $this->model->save();
    }
    /**
     * 退款审核拒绝
     */
    public function refundRefuse( ):bool{
        if( $this->model->goods_status == OrderGoodStatus::INIT){
            return false;
        }
        // 状态改为退款中
        $this->model->goods_status = OrderGoodStatus::INIT;
        return $this->model->save();
    }
    //-+------------------------------------------------------------------------
    // | 退货
    //-+------------------------------------------------------------------------
    /**
     * 申请退货
     *
     * @return bool
     */
    public function returnOpen(string $business_no){
        //商品必须为租用中
        if( $this->model->goods_status != OrderGoodStatus::RENTING_MACHINE ){
           return false;
        }
        //业务类型为退货业务
        $this->model->business_key = OrderStatus::BUSINESS_RETURN;
        $this->model->business_no = $business_no;
        // 状态改为退货中
        $this->model->goods_status = OrderGoodStatus::REFUNDS;
        return $this->model->save();
    }
    /**
     * 退换货审核拒绝-取消退货--检测不合格拒绝退款 --换货检测不合格  共用
     * @return bool
     */
    public function returnClose( ):bool{
        if( $this->model->goods_status== OrderGoodStatus::RENTING_MACHINE){
            return false;
        }
        // 状态改为租用中
        $this->model->goods_status = OrderGoodStatus::RENTING_MACHINE;
        return $this->model->save();

    }

    /**
     * 取消退换货
     * @return bool
     */
    public function returnCancel():bool{
        if( $this->model->goods_status== OrderGoodStatus::RENTING_MACHINE){
            return false;
        }
        // 状态改为租用中并且清除business_key 和business_no
        $this->model->business_key = 0;
        $this->model->business_no ='';
        $this->model->goods_status = OrderGoodStatus::RENTING_MACHINE;
        return $this->model->save();
    }

    /**
     * 完成退货
     * @return bool
     */
    public function returnFinish( ):bool{
        $this->model->goods_status=OrderGoodStatus::REFUNDED;  //退货完成
        return $this->model->save();
    }

    /**
     * 拒签，修改状态为退货完成
     * @return bool
     */
    public function returnSign( ):bool{
        $this->model->goods_status = OrderGoodStatus::REFUNDED;  //退货完成
        $this->model->update_time = time();
        return $this->model->save();
    }
	
    //-+------------------------------------------------------------------------
    // | 换货
    //-+------------------------------------------------------------------------
    /**
     * 申请换货
     * @return bool
     */
    public function barterOpen(string $business_no):bool{
        $this->model->business_key = OrderStatus::BUSINESS_BARTER;
        $this->model->business_no = $business_no;
        // 状态改为换货中
        $this->model->goods_status = OrderGoodStatus::EXCHANGE_GOODS;
        return $this->model->save();
    }
    /**
     * 取消换货
     * @return bool
     */
    public function barterClose( ):bool{
        return true;
    }
    /**
     * 完成换货
     * @return bool
     */
    public function barterFinish( ):bool{
        if($this->model->goods_status==OrderGoodStatus::RENTING_MACHINE){
            return false;
        }
        $this->model->goods_status=OrderGoodStatus::RENTING_MACHINE;  //租用中
        return $this->model->save();
    }
	
	
    //-+------------------------------------------------------------------------
    // | 还机
    //-+------------------------------------------------------------------------
	/**
	 * 还机开始
	 * @return bool
	 */
    public function givebackOpen( $givebackNo ):bool {
        $this->model->goods_status = OrderGoodStatus::BACK_IN_THE_MACHINE;
        $this->model->business_key = \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK;
        $this->model->business_no = $givebackNo;
        $this->model->update_time = time();
        return $this->model->save();
    }
	/**
	 * 还机关闭
	 * @return bool
	 */
    public function givebackClose():bool {
        $this->model->business_no = 0;
        $this->model->business_key = 0;
        $this->model->goods_status = OrderGoodStatus::CLOSED_THE_MACHINE;
        $this->model->update_time = time();
        return $this->model->save();
    }
	/**
	 * 还机完成
	 * @return bool
	 */
    public function givebackFinish():bool {
        $this->model->goods_status = OrderGoodStatus::COMPLETE_THE_MACHINE;
        $this->model->update_time = time();
        return $this->model->save();
    }
	
    //-+------------------------------------------------------------------------
    // | 买断
    //-+------------------------------------------------------------------------
	/**
	 * 买断开始
	 * @return bool
	 */
    public function buyoutOpen($data):bool {
        $this->model->goods_status = OrderGoodStatus::BUY_OFF;
        $this->model->business_no = $data['business_no'];
        return $this->model->save();
    }
	/**
	 * 买断关闭
	 * @return bool
	 */
    public function buyoutClose():bool {
        return true;
    }
	/**
	 * 买断完成
	 * @return bool
	 */
    public function buyoutFinish():bool {
        return true;
    }
	
	//-+------------------------------------------------------------------------
	// | 续租
	//-+------------------------------------------------------------------------
    /**
     * 修改设备状态 续租中
     *
     * @return bool
     */
    public function setGoodsStatusReletOn(){
        $this->model->goods_status = OrderGoodStatus::RELET;
        $this->model->update_time = time();
        return $this->model->save();
    }

    /**
     * 修改设备状态 续租完成
     *
     * @return bool
     */
    public function setGoodsStatusReletOff(){
        $this->model->goods_status = OrderGoodStatus::RENEWAL_OF_RENT;
        $this->model->update_time = time();
        return $this->model->save();
    }

    /**
     * 修改小程序商品剩余续租天数
     *
     * @return bool
     */
    public function setReletTime($zuqi){
        $relet_day = $this->model->relet_day;
        if($relet_day>=$zuqi){
            $this->model->relet_day = $relet_day-$zuqi;
            $this->model->update_time = time();
            return $this->model->save();
        }else{
            return false;
        }
    }
	
	//-+------------------------------------------------------------------------
	// | 静态方法
	//-+------------------------------------------------------------------------
	/**
	 * 获取商品列表
	 * @param string	$order_no		订单编号
	 * @param int		$lock			锁
	 * @return array
	 */
	public static function getByOrderNo( string $order_no, int $lock=0 ) {
		
        $builder = \App\Order\Models\OrderGoods::where([
            ['order_no', '=', $order_no],
        ]);
		if( $lock ){
			$builder->lockForUpdate();
		}
        $orderGoodData = $builder->get();
		$list = [];
		foreach( $orderGoodData as $it ) {
			$list[] = new self( $it );
		}
		return $list;
	}
    //-+------------------------------------------------------------------------
    // | 静态方法
    //-+------------------------------------------------------------------------
    /**
     * 通过订单编号获取商品列表
     * @param string	$order_no		订单编号
     * @param int		$lock			锁
     * @return \App\Order\Modules\Repository\Order\Goods
     * @return  bool
     */
    public static function getOrderNo( string $order_no, int $lock=0 ) {

        $builder = \App\Order\Models\OrderGoods::where([
            ['order_no', '=', $order_no],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $orderGoodData = $builder->first();
        if( !$orderGoodData ){
            return false;
        }
        return new Goods($orderGoodData);
    }
	
	/**
	 * 通过商品id获取商品
	 * <p>当订单不存在时，抛出异常</p>
	 * @param int   	$id		    ID
	 * @param int		$lock		锁
	 * @return \App\Order\Modules\Repository\Order\Goods
	 * @return  bool
	 */
	public static function getByGoodsId( int $id, int $lock=0 ) {
        $builder = \App\Order\Models\OrderGoods::where([
            ['id', '=', $id],
        ])->limit(1);
		if( $lock ){
			$builder->lockForUpdate();
		}
		$goods_info = $builder->first();
		if( !$goods_info ){
			return false;
		}
		return new Goods( $goods_info );
	}
    /**
    * 通过商品编号获取商品信息
    * @param int   	$goods_no		    商品编号
    * @param int		$lock		锁
    * @return \App\Order\Modules\Repository\Order\Goods
    * @return  bool
    */
    public static function getByGoodsNo( $goods_no, int $lock=0 ) {
        $builder = \App\Order\Models\OrderGoods::where([
            ['goods_no', '=', $goods_no],
        ])->limit(1);
        if( $lock ){
            $builder->lockForUpdate();
        }
        $goods_info = $builder->first();
        if( !$goods_info ){
            return false;
        }
        return new Goods( $goods_info );
    }

}
