<?php
namespace App\Company\Modules\Repository;
use App\Company\Models\Company;
use Illuminate\Support\Facades\DB;

/**
 *  企业信息 操作数据库类
 * @access public (访问修饰符)
 * @author wuhaiyan <wuhaiyan@huishoubao.com>
 * @copyright (c) 2019, Huishoubao
 */

class CompanyRepository
{
    /**
     * 根据ID 获取企业信息
     * @param $id
     * @return array
     */
    public static function getCompanyById($id){
        if(empty($id)){
            return false;
        }
        $where[]=['id','=',$id];
        $res= Company::where($where)->first();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }
    /**
     * 获取企业信息列表
     */
    public static  function getAllList($param=array()){

        $companyList =  Company::query()->select(['id','company_name','email_suffix'])->orderBy("create_time", "DESC")->get();
        if($companyList){
            return $companyList;
        }
        return [];
    }
    /**
     * 获取企业信息列表
     * @param $params
     * [
     *   'page'         =>'',   //【可选】 int 页数
     *   'size'         =>''    //【可选】 int 每页数量
     * ]
     */
    public static  function getCompanyPageList($param=array()){
        $page = empty($param['page']) || !isset($param['page']) ? 1 : $param['page'];
        $size = !empty($param['size']) && isset($param['size']) ? $param['size'] : config('web.pre_page_size');
        $whereArray= self::get_where($param);  //获取搜索的条件
        $companyList =  DB::table('order_company_info as a')
            ->select('a.id','a.company_name','a.company_address','a.email_suffix','a.link_name','a.link_phone','a.status','a.create_time','a.update_time')
            ->where($whereArray)
            ->orderBy('a.create_time', 'DESC')
            ->paginate($size,$columns = ['*'], $pageName = 'page', $page);
        if($companyList){
            return $companyList;
        }
        return [];
    }
    /**
     * 列表条件
     */
    public static function get_where($param=array()){
        $whereArray=[];
//        //根据用户手机号
//        if (isset($param['mobile']) && !empty($param['mobile'])) {
//
//            $whereArray[] = ['mobile', '=', $param['mobile']];
//        }
        return $whereArray;
    }
}