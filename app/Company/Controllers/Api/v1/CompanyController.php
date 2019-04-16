<?php

namespace App\Company\Controllers\Api\v1;
use App\Company\Modules\CompanyOperate;
use App\Lib\ApiStatus;
use Dingo\Api\Http\Request;


class CompanyController extends Controller
{


    public function __construct()
    {

    }
    /**
     *  增加合作企业
     * @author wuhaiyan
     * @param Request $request['params']
     *      [
     *          'company_name'=>'',         //【必须】string 企业名称
     *          'company_address'=>'',      //【必须】string 企业地址
     *          'email_suffix'=>'',         //【必须】string 企业邮箱后缀
     *          'link_name'=>'',            //【必须】string 联系人姓名
     *          'link_phone'=>'',           //【必须】string 联系人电话
     *          'status'=>'',               //【必须】int 合作状态
     *      ]
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $params =$request->all();
        $rules = [
            'company_name'      =>'required',
            'company_address'   =>'required',
            'email_suffix'      =>'required',
            'link_name'         =>'required',
            'link_phone'        =>'required',
            'status'            =>'required|int',
        ];
        $validateParams =$this->validateParams($rules,$params);

        if (empty($validateParams) || $validateParams['code']!=0) {

            return apiResponse([],$validateParams['code'],"参数必须或参数类型错误");
        }
        $params =$params['params'];
        $res = CompanyOperate::CompanyCreate($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000,get_msg());
        }
        return apiResponse([],ApiStatus::CODE_0);
    }

    /**
     * 企业信息列表
     * @author wuhaiyan
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */


    public function allList(Request $request){
        $res = CompanyOperate::AllList();
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000,get_msg());
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }
    /**
     * 企业信息列表
     * @author wuhaiyan
     * @param Request $request
     * [
     *   'page'             =>'' ,  //【可选】 string 页数
     *   'size'             =>'' ,  //【可选】 string 每页数量
     * ]
     * @return \Illuminate\Http\JsonResponse
     */


    public function companyList(Request $request){
        $params =$request->all();
        $params =$params['params'];
        $res = CompanyOperate::CompanyList($params);
        if(!$res){
            return apiResponse([],ApiStatus::CODE_50000,get_msg());
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }

    /**
     * 判断 该用户是否可以下单
     * @author wuhaiyan
     * @param Request $request
     * $request['userinfo']     //【必须】array 用户信息  - 转发接口获取
     * $userinfo [
     *      'type'=>'',     //【必须】string 用户类型:1管理员，2用户,3系统，4线下,
     *      'uid'=>1,   //【必须】string 用户ID
     *      'username'=>1, //【必须】string 用户名
     *      'mobile'=>1,    //【必须】string手机号
     * ]
     * $appid 【必须】
     * @return \Illuminate\Http\JsonResponse
     */
    public function unCompledOrderByUser(Request $request){
        $params =$request->all();
        $userInfo   = isset($params['userinfo'])?$params['userinfo']:[];
        if(empty($userInfo)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[用户信息错误]");
        }
        $appid = $params['appid'];
        //判断参数是否设置
        if(empty($appid)){
            return apiResponse([],ApiStatus::CODE_20001,"参数错误[appid]");
        }
        $res = CompanyOperate::unCompledOrderByUser($appid,$userInfo);
        if(!$res){
            return apiResponse([],get_code(),get_msg());
        }
        return apiResponse($res,ApiStatus::CODE_0);

    }


}
