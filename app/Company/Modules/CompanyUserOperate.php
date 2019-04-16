<?php
/**
 * 企业租赁操作类
 * @access public (访问修饰符)
 * @author yaodongxu <yaodongxu@huishoubao.com>
 * @copyright (c) 2019, Huishoubao
 */

namespace App\Company\Modules;


use App\Company\Models\CompanyUser;

class CompanyUserOperate
{

    /**
     *  新增企业用户审核信息
     * @author yaodongxu
     * @param $data
     *      [
     *          'name'=>'',             //【必须】string 用户姓名
     *          'mobile'=>'',           //【必须】string 用户手机号
     *          'cert_no'=>'',          //【必须】string 用户身份证号
     *          'company_id'=>'',       //【必须】int 用户企业id（关联order_company_info表）
     *          'address'=>'',          //【必须】string 用户邮箱地址
     *          'department'=>'',       //【必须】string 用户所在部门
     *      ]
     * @return bool
     */

    public static function create($data){
        try{
            $data['create_time']=time();
            $data['update_time']=time();
            $res = CompanyUser::create($data);
            $id = $res->getQueueableId();
            if(!$id){
                set_msg("添加失败");
                return false;
            }
            return true;
        }catch (\Exception $e){
            \App\Lib\Common\LogApi::error('companyuser-create'.$e->getMessage());
            return false;
        }
    }
}