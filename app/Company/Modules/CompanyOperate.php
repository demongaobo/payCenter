<?php
/**
 * 企业租赁操作类
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2017, Huishoubao
 */

namespace App\Company\Modules;


use App\Company\Models\Company;
use App\Company\Modules\Inc\CompanyStatus;
use App\Company\Modules\Repository\CompanyRepository;
use App\Company\Modules\Repository\CompanyUserRepository;
use App\Lib\ApiStatus;
use App\Order\Modules\Repository\OrderRepository;

class CompanyOperate
{


    /**
     *  新增或修改合作企业
     * @author wuhaiyan
     * @param $data
     *      [
     *          'id'=>'',                   //【可选】int 企业ID 如果存在则修改 不存在则新增
     *          'company_name'=>'',         //【必须】string 企业名称
     *          'company_address'=>'',      //【必须】string 企业地址
     *          'email_suffix'=>'',         //【必须】string 企业邮箱后缀
     *          'link_name'=>'',            //【必须】string 联系人姓名
     *          'link_phone'=>'',           //【必须】string 联系人电话
     *          'status'=>'',               //【必须】int 合作状态
     *      ]
     * @return bool
     */

    public static function CompanyCreate($data){
        try{
            $data['update_time']=time();
            if(isset($data['id'])){
                return Company::where('id', '=', $data['id'])->update($data);
            }else{
                $data['create_time']=time();
                $res = Company::create($data);
                $id = $res->getQueueableId();
                if(!$id){
                    set_msg("添加失败");
                    return false;
                }
            }
            return true;
        }catch (\Exception $e){
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * 合作企业信息列表
     * @author wuhaiyan
     * @param $data
     *      [
     *          'page'             =>'' ,  //【可选】 string 页数
     *          'size'             =>'' ,  //【可选】 string 每页数量
     *      ]
     * @return array|bool
     */

    public static function CompanyList($data){

        $res =CompanyRepository::getCompanyPageList($data);
        $res = objectToArray($res);
        if(!empty($res['data'])){
            foreach ($res['data'] as $k=>$v){
                //状态名称
                $res['data'][$k]['status_name'] = CompanyStatus::getStatusName($v['status']);
            }
        }

        return $res;

    }
    /**
     * 合作企业信息列表
     * @author wuhaiyan
     * @return array|bool
     */

    public static function AllList(){

        $res =CompanyRepository::getAllList();
        return $res;

    }
    /**
     * 判断该渠道是否可以下单
     * @author wuhaiyan
     * $userinfo [
     *      'uid'=>1,   //【必须】string 用户ID
     *      'mobile'=>1,    //【必须】string手机号
     * ]
     * $appid 【必须】int  appid
     * @return bool
     */
    public static function unCompledOrderByUser($appid,$userInfo){
        if($appid !=210){
            return true;
        }
        //查询该用户是否是企业用户并有效
        $company =CompanyUserRepository::isAccept($userInfo['username']);
        if($company){
            //获取有效订单数量
            $b = OrderRepository::getValidOrder(['user_id'=>$userInfo['uid'],'appid'=>$appid]);
            if($b){
                set_msg('企业用户只允许有一个有效订单');
                set_code(ApiStatus::CODE_32100);
                return false;
            }
            //获取身份证号码
            $res = CompanyUserRepository::getInfoByMobile($userInfo['username']);
            $certNo = $res['cert_no'];
            //根据身份证号码进行判断 一个身份证只能下一单
            if($certNo !=''){
                $b =OrderRepository::unCompledOrderByCertNo($certNo,"210","");
                if($b) {
                    set_msg('企业用户只允许有一个有效订单');
                    set_code(ApiStatus::CODE_32100);
                    return false;
                }
            }


            return true;
        }else{
            set_msg('您认证的企业已经停止合作，暂时无法下单');
            set_code(ApiStatus::CODE_32101);
            return false;
        }

    }
}