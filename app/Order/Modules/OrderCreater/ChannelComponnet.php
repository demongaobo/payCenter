<?php
/**
 * PhpStorm
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Order\Modules\OrderCreater;


use App\Lib\Channel\Channel;
use App\Lib\Common\LogApi;
use App\Order\Modules\Repository\OrderRepository;
use Mockery\Exception;

class ChannelComponnet implements OrderCreater
{
    //组件
    private $componnet;
    private $flag = true;
    /**
     * app_id 主键
     * @var int
     */
    private $appId = 0;
    /**
     * app_id 名称
     * @var string
     */
    private $appName = '';
    /**
     * app_id 类型
     * @var int
     */
    private $appType = 0;
    /**
     * app_id 状态： 0：禁用；1：启用
     * @var int
     */
    private $appStatus = 0;

    /**
     * 渠道 主键
     * @var int
     */
    private $channelId = 0;
    /**
     * 渠道 名称
     * @var string
     */
    private $channelName = '';
    /**
     * 渠道商品是否独立
     * @var string
     */
    private $channelAloneGoods = '';
    /**
     * 渠道 状态： 0：禁用；1：启用
     * @var int
     */
    private $channelStatus = 0;

    /**
     * 渠道信息
     * @var array
     */
    private $channelInfo=[];

    public function __construct(OrderCreater $componnet, array $ChannelInfo)
    {
        $this->componnet = $componnet;

        $this->appId = intval($ChannelInfo['appid']['id']);
        $this->appName = $ChannelInfo['appid']['name'];
        $this->appType = intval($ChannelInfo['appid']['type']);
        $this->appStatus = intval($ChannelInfo['appid']['status'])?1:0;
        $this->channelId = intval($ChannelInfo['_channel']['id']);
        $this->channelName = $ChannelInfo['_channel']['name'];
        $this->channelAloneGoods = intval($ChannelInfo['_channel']['alone_goods'])?1:0;
        $this->channelStatus = intval($ChannelInfo['_channel']['status'])?1:0;
        $this->channelInfo =$ChannelInfo;

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
        $filter = $this->componnet->filter();
        $schema =$this->componnet->getDataSchema();

        if( $this->appStatus == 0 ){
            $this->getOrderCreater()->setError('appid已禁用');
            $this->flag = false;
        }
        if( $this->channelStatus == 0 ){
            $this->getOrderCreater()->setError('渠道已禁用');
            $this->flag = false;
        }

        if( $this->channelAloneGoods ==1 ){
            foreach ($schema['sku'] as $k=>$v){
                if($v['channel_id'] != $this->channelId){
                    $this->getOrderCreater()->setError('商品渠道错误');
                    $this->flag = false;
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
        return array_merge($schema,[
            'channel' => $this->channelInfo,
        ]);
    }

    /**
     * 创建数据
     * 1.判断商品是否有所属供应商渠道 有则替换渠道ID
     * 2.保存订单渠道信息（来源）
     * @return bool
     */
    public function create(): bool
    {
        $data =$this->getOrderCreater()->getDataSchema();
        $b = $this->componnet->create();
        if( !$b ){
            return false;
        }

        /*
         * 获取商品所属供应商渠道Id
         * 如果存在供应商渠道ID 则 替换 来源渠道ID
         */
        $supplierChannelId =$this->getOrderCreater()->getSkuComponnet()->getSupplierChannelId();
        if($supplierChannelId){
            $this->channelId = $supplierChannelId;
        }

        //保存订单渠道信息
        $b= OrderRepository::updateChannel($data['order']['order_no'],$this->channelId);
        if(!$b){
            LogApi::error(config('app.env')."OrderCreate-UpdateOrderChannel-error:",$data['order']['order_no']);
            $this->getOrderCreater()->setError('OrderCreate-UpdateOrderChannel-error');
            return false;
        }
        return true;
    }
}