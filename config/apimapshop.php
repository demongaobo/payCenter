<?php
/**
 *
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2019/4/11 0011
 * Time: 下午 2:16
 */
//商家店铺路由映射

return [
    /***************************************************************************************************
     ************************************************** 商家管理平台 ****************************************
     ***************************************************************************************************/
  //  'api.AgentRegister.AgentRegister'        => 'AgentRegisterController@AgentRegister',                                    //代理商注册
    'api.Shops.AddShops'                       => '\App\Shop\Controllers\Api\v1\ShopsController@AddShops',                  //添加店铺
    'api.Shops.getShops'                       => '\App\Shop\Controllers\Api\v1\ShopsController@getShops',                  //店铺列表
    'api.Shops.editShops'                      => '\App\Shop\Controllers\Api\v1\ShopsController@EditShops',                 //编辑店铺
    'api.Shops.AgentList'                      => '\App\Shop\Controllers\Api\v1\AgentLoginController@AgentList',                 //代理商列表
    'api.AgentLogin.AgentLogin'              => '\App\Shop\Controllers\Api\v1\AgentLoginController@AgentLogin',          //代理商登录
    'api.AgentLogin.smsCode'                 => '\App\Shop\Controllers\Api\v1\AgentLoginController@smsCode',             //发送短信验证码
    'api.AgentLogin.getMenu'                 => '\App\Shop\Controllers\Api\v1\AgentLoginController@getMenu',            //获取无限极菜单
    'api.AgentLogin.logout'                  => '\App\Shop\Controllers\Api\v1\AgentLoginController@logout',              //退出登录
    'api.Shops.getShopInfo'                  => '\App\Shop\Controllers\Api\v1\ShopsController@getShopInfo',             //根据店铺id获取店铺信息
    'api.AgentRegister.getAgentInfo'       => '\App\Shop\Controllers\Api\v1\AgentLoginController@getAgentInfo',          //获取代理商信息


    
    /***************************************************************************************************
     ************************************************** 订单管理平台 ****************************************
     ***************************************************************************************************/
    'api.Shop.Orderlist'                      => '\App\Shop\Controllers\Api\v1\OrderController@orderList',                  //获取订单列表

    'api.Shop.orderdetail'                      => '\App\Shop\Controllers\Api\v1\OrderController@orderInfo',                  //获取商家后台订单详情

    'api.Shop.getOrderStatus'                      => '\App\Shop\Controllers\Api\v1\OrderController@getOrderStatus',             //获取订单状态流

    'api.Shop.orderLog'                      => '\App\Shop\Controllers\Api\v1\OrderController@orderLog',                        //获取订单日志


    'api.Shop.confirmOrder'                      => '\App\Shop\Controllers\Api\v1\OrderController@confirmOrder',              //商家后台确认订单接口

    'api.Shop.cancelPayOrder'                      => '\App\Shop\Controllers\Api\v1\OrderController@cancelPayOrder',              //商家后台取消订单接口

    'api.Shop.orderGoodsLog'                      => '\App\Shop\Controllers\Api\v1\OrderController@goodsLog',                        //获取订单日志
    
    
    /***************************************************************************************************
     ************************************************** 商品管理平台 ****************************************
     ***************************************************************************************************/
    //商品附属元素
    'zuji.shop.getShopComponents'    => '\App\Shop\Controllers\Api\v1\ShopGoodsController@getShopComponents',
    //添加编辑商品
    'zuji.shop.addGoods'             => '\App\Shop\Controllers\Api\v1\ShopGoodsController@addGoods',
    //商户商品列表
    'zuji.shop.shopSpuList'          => '\App\Shop\Controllers\Api\v1\ShopGoodsController@shopSpuList',
    //ajax方式修改商品
    'zuji.shop.editAjax'             => '\App\Shop\Controllers\Api\v1\ShopGoodsController@editAjax',
    //商品审核、取消审核
    'zuji.shop.checkStatus'          => '\App\Shop\Controllers\Api\v1\ShopGoodsController@checkStatus',
    //删除
    'zuji.shop.remove'               => '\App\Shop\Controllers\Api\v1\ShopGoodsController@remove',
    //下架
    'zuji.shop.getDown'              => '\App\Shop\Controllers\Api\v1\ShopGoodsController@getDown',
    //上传图片
    'zuji.shop.uploadImage'          => '\App\Shop\Controllers\Api\v1\UploadImageController@goodsUploadImage',


    /***************************************************************************************************
     ************************************************** 商品管理平台 ****************************************
     ***************************************************************************************************/
    //发货单列表接口
    'api.warehouse.deliveryList'           => '\App\Shop\Controllers\Api\v1\WarehouseController@deliveryList',
    //发货单发货接口
    'api.warehouse.deliverySend'           => '\App\Shop\Controllers\Api\v1\WarehouseController@deliverySend',
    //发货单签收接口
    'api.warehouse.deliveryReceive'        => '\App\Shop\Controllers\Api\v1\WarehouseController@deliveryReceive',
    //获取店铺对应的仓库地址(状态为正常的)
    'api.warehouse.shopLists'              => '\App\Shop\Controllers\Api\v1\WarehouseController@shopLists',
    //仓库公共数据
    'api.warehouse.publicsAddress'         => '\App\Shop\Controllers\Api\v1\WarehouseController@publicsAddress',


];