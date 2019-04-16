<?php

namespace App\Company\Controllers\Api\v1;
use App\Http\Controllers\Controller;
use Dingo\Api\Http\Request;
use App\Lib\ApiStatus;
use App\Company\Modules\Repository\CompanyUserRepository;
use App\Company\Modules\Repository\CompanyRepository;
use App\Company\Modules\CompanyUserOperate;
use App\Lib\Risk\Risk;
use Illuminate\Support\Facades\DB;

class CompanyUserController extends Controller
{
	/**
	 * 获取企业用户信息列表
	 * @param Request $request
	 */
	public function getList(Request $request) {
		$params = $request->all();
		$whereArr = $additionArr = isset($params['params'])? $params['params'] :[];
		$userList = CompanyUserRepository::getList( $whereArr, $additionArr );
		
		//-+--------------------------------------------------------------------
		// | 添加通过、驳回按钮逻辑
		// | 1、审核中：通过按钮+驳回按钮
		// | 2、审核通过+有订单：无按钮
		// | 3、审核通过+无订单：驳回按钮
		// | 4、审核驳回：无按钮
		//-+--------------------------------------------------------------------
		
		if( $userList ){
			foreach ($userList['data'] as  &$value) {
				//1、审核中：通过按钮+驳回按钮【默认】
				$accept_btn = true;//通过按钮
				$reject_btn = true;//驳回按钮
				//4、审核驳回：无按钮
				if( $value['status'] == CompanyUserRepository::STATUS_REJECT ){
					$accept_btn = false;
					$reject_btn = false;
				}elseif( $value['status'] == CompanyUserRepository::STATUS_ACCEPT ){
					$isHaveOrder = \App\Order\Modules\Repository\OrderRepository::getValidOrder(['mobile'=>$value['mobile'],'appid'=> \App\Order\Modules\Repository\ShortMessage\Config::CHANNELID_COMPANY]);
					//2、审核通过+有订单：无按钮
					if( $isHaveOrder ){
						$accept_btn = false;
						$reject_btn = false;
					}
					//3、审核通过+无订单：驳回按钮
					else {
						$accept_btn = false;
					}
				}
				$value['accept_btn'] = $accept_btn;
				$value['reject_btn'] = $reject_btn;
			}
		}
		return apiResponse($userList);
	}
    /**
     * 企业用户信息导出
     * Author: 姚东旭
     * @param Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(Request $request) {
		$params = $request->all();
		$whereArr = $additionArr = [
			'company_id'=>isset($params['company_id']) && $params['company_id']? $params['company_id'] :'',
			'status'=>isset($params['status']) && $params['status']? $params['status'] :'',
		];
		
		$additionArr['size'] = 100000;
		$userList = CompanyUserRepository::getList( $whereArr, $additionArr );
		$total = $userList['total'];//列表总数

		$headers = ['姓名','手机号','身份证号', '企业名称','邮箱地址','所在部门','认证状态','最近更新时间'];
        if ($total) {
            foreach ($userList['data'] as $item) {
                $data[] = [
                    $item['name'],
                    "\t".$item['mobile'],
                    "\t".$item['cert_no'],
                    $item['company_name'],
                    $item['address'],
                    $item['department'],
                    $item['status_name'],
                    $item['update_time'],
                ];
            }

            return \App\Lib\Excel::write($data, $headers,'企业用户列表数据导出');
        } else {
            return \App\Lib\Excel::write([[]], $headers,'企业用户列表数据导出');
        }
	}
	
	/**
	 * 用户企业信息提交
	 * @param Request $request
	 */
	public function info(Request $request) {
		//-+--------------------------------------------------------------------
		// | 验证参数
		//-+--------------------------------------------------------------------
		$params = $request->all();
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
		if( empty($params['appid']) || empty($params['auth_token']) ){
			return apiResponse([],ApiStatus::CODE_20001,'参数有误');
		}
		$paramsArr = isset($params['params'])? $params['params'] :[];
		$rules = [
			'name'     => 'required',//姓名
			'mobile'     => 'required',//手机号 
			'cert_no'     => 'required',//身份证号
			'company_name'     => 'required',//企业名称
			'company_id'     => 'required',//企业id
			'address'     => 'required',//邮箱地址
			'department'     => 'required',//部门
		];
		$validator = app('validator')->make($paramsArr, $rules);
		if ($validator->fails()) {
			return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
		}
		$isModify = isset($paramsArr['is_modify']) ? !!$paramsArr['is_modify'] : 0;
		
		//-+--------------------------------------------------------------------
		// | 验证逻辑
		//-+--------------------------------------------------------------------
		//校验邮箱验证码
		$code = redis_get(self::__getRedisKey($operateUserInfo['uid'],$paramsArr['address']));
		if( $code != $paramsArr['code'] ){
			return apiResponse([],ApiStatus::CODE_20001,'邮箱验证码错误或已过期，请从新进行邮箱验证！');
		}
		
		//组合数据
		$data = [
			'name' => $paramsArr['name'],
			'mobile' => $paramsArr['mobile'],
			'cert_no' => $paramsArr['cert_no'],
			'company_id' => $paramsArr['company_id'],
			'address' => $paramsArr['address'],
			'department' => $paramsArr['department'],
		];
		
		//开启事务
		DB::beginTransaction();
		//-+--------------------------------------------------------------------
		// | 企业用户信息首次提交
		//-+--------------------------------------------------------------------
		if( !$isModify ){
			//校验企业用户是否已经存在
			if( CompanyUserRepository::getInfoByMobile($paramsArr['mobile']) ){
				return apiResponse([],ApiStatus::CODE_50000,'已经提交企业认证信息，请勿重复提交！');
			}
			//-+--------------------------------------------------------------------
			// | 数据存入
			//-+--------------------------------------------------------------------
			$result = CompanyUserOperate::create($data);
		}
		//-+--------------------------------------------------------------------
		// | 企业用户信息重新提交
		//-+--------------------------------------------------------------------
		else{
			//-+--------------------------------------------------------------------
			// | 数据更新[审核状态变成审核中]
			//-+--------------------------------------------------------------------
			$data['status'] = CompanyUserRepository::STATUS_INIT;
			$result = CompanyUserRepository::update(['mobile'=>$paramsArr['mobile']], $data);
		}
		if( !$result ){
			return apiResponse([],ApiStatus::CODE_50000,'用户企业认证信息提交失败，请重新尝试!');
		}
		//-+--------------------------------------------------------------------
		// | 数据同步到风控系统
		//-+--------------------------------------------------------------------
		$riskData = [
			'cert_no' => $paramsArr['cert_no'],
			'name' => $paramsArr['name'],
			'mobile' => $paramsArr['mobile'],
			'auth_token' => $params['auth_token'],
		]; 
		try{
			Risk::companyUserSync($riskData, $params['appid']);
		} catch (Exception $ex) {
			\App\Lib\Common\LogApi::error('企业信息同步风控系统失败'.__FILE__.__LINE__, ['msg'=>$ex->getMessage()]);
			return apiResponse([],ApiStatus::CODE_50000,'用户企业认证信息提交失败，请重新尝试!');
			//事务回滚
			DB::rollBack();
		}
		//事务提交
		DB::commit();
		
		return apiResponse([], ApiStatus::CODE_0, '企业认证信息提交成功，请耐心等待审核结果!');
	}

	/**
	 * 企业用户信息详情
	 * @param Request $request
	 */
	public function details(Request $request) {
		
		//-+--------------------------------------------------------------------
		// | 验证参数
		//-+--------------------------------------------------------------------
		$params = $request->all();
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
		$paramsArr = isset($params['params'])? $params['params'] :[];
		$rules = [
			'mobile'     => 'required',//手机号 
		];
		$validator = app('validator')->make($paramsArr, $rules);
		if ($validator->fails()) {
			return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
		}
		
		//-+--------------------------------------------------------------------
		// | 数据查询组合
		//-+--------------------------------------------------------------------
		$userDetails = CompanyUserRepository::getInfoByMobile($paramsArr['mobile']);
		$isVerify = false;
		$companyDetails = [];
		if ( $userDetails ){
			$isVerify = true;
			$companyDetails = CompanyRepository::getCompanyById($userDetails['company_id']);
		}
		return apiResponse([
			'is_verify' => $isVerify,
			'user_details' => $userDetails,
			'company_details' => $companyDetails
		], ApiStatus::CODE_0, '企业用户详情');
	}
	/**
	 * 企业用户信息审核
	 * @param Request $request
	 */
	public function check(Request $request) {
		
		//-+--------------------------------------------------------------------
		// | 验证参数
		//-+--------------------------------------------------------------------
		$params = $request->all();
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
		$paramsArr = isset($params['params'])? $params['params'] :[];
		$rules = [
			'mobile'     => 'required',//手机号 
			'status'     => 'required',//审核状态 
		];
		$validator = app('validator')->make($paramsArr, $rules);
		if ($validator->fails()) {
			return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
		}
		$checkReason = isset($paramsArr['check_reason']) ? $paramsArr['check_reason'] : '' ;//审核原因
		$checkRemark = isset($paramsArr['check_remark']) ? $paramsArr['check_remark'] : '' ;//审核备注
		if( $paramsArr['status'] == CompanyUserRepository::STATUS_REJECT && !$checkReason ){
			return apiResponse([],ApiStatus::CODE_20001,'审核驳回必须选择驳回原因!');
		}
		//组合数据
		$data = [
			'check_reason' => $checkReason,
			'check_remark' => $checkRemark,
			'check_user_id' => $operateUserInfo['uid'],
			'check_user_name' => $operateUserInfo['username'],
			'status' => $paramsArr['status'],
		];
		
		//-+--------------------------------------------------------------------
		// | 发送短信
		//-+--------------------------------------------------------------------
		//获取用户姓名
		$userInfo = CompanyUserRepository::getInfoByMobile($paramsArr['mobile']);
		$username = isset($userInfo['name']) && $userInfo['name']? $userInfo['name']:$paramsArr['mobile'];
		//审核通过短信
		if( $paramsArr['status'] == CompanyUserRepository::STATUS_ACCEPT ){
			//发送短信
			$notice = new \App\Order\Modules\Service\OrderNotice(
				0,'','CompanyCheckAccept',['mobile'=>$paramsArr['mobile'],'username'=>$username]);
			$notice->notify();
		}
		//审核拒绝短信
		elseif( $paramsArr['status'] == CompanyUserRepository::STATUS_REJECT ){
			//发送短信
			//发送短信
			$notice = new \App\Order\Modules\Service\OrderNotice(
				0,'','CompanyCheckReject',['mobile'=>$paramsArr['mobile'],'username'=>$username]);
			$notice->notify();
		}
		
		//-+--------------------------------------------------------------------
		// | 数据更新
		//-+--------------------------------------------------------------------
		$result = CompanyUserRepository::update(['mobile'=>$paramsArr['mobile']], $data);
		if( $result ){
			return apiResponse([], ApiStatus::CODE_0, '企业认证信息审核成功!');
		}
		return apiResponse([],ApiStatus::CODE_50000,'企业认证信息审核失败，请重新尝试!');
	}

	/**
	 * 给企业用户邮箱验证发送验证码
	 * @param Request $request
	 */
    public function email(Request $request)
    {
		
		//-+--------------------------------------------------------------------
		// | 验证参数、生成验证码
		//-+--------------------------------------------------------------------
		$params = $request->all();
		$operateUserInfo = isset($params['userinfo'])? $params['userinfo'] :[];
		if( empty($operateUserInfo['uid']) || empty($operateUserInfo['username']) || empty($operateUserInfo['type']) ) {
			return apiResponse([],ApiStatus::CODE_20001,'用户信息有误');
		}
		$paramsArr = isset($params['params'])? $params['params'] :[];
		$rules = [
			'address'     => 'required',//邮箱地址
			'name'     => 'required',//姓名
		];
		$validator = app('validator')->make($paramsArr, $rules);
		if ($validator->fails()) {
			return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
		}
		//生成随机验证码
		$code = rand(1000,9999);
		
		
		//-+--------------------------------------------------------------------
		// | 验证邮箱是否已经绑定
		//-+--------------------------------------------------------------------
		$userInfo = CompanyUserRepository::getInfoByAddress($paramsArr['address']);
		if( $userInfo && $userInfo['mobile'] != $operateUserInfo['username']){
			return apiResponse([],ApiStatus::CODE_20001,'当前邮箱已经被绑定');
		}
		
		
		//-+--------------------------------------------------------------------
		// | 发送邮件
		//-+--------------------------------------------------------------------
		
        $sendEmail = new \App\Lib\Email\SendEmailApi();
        $adderss = [[
			'address' => $paramsArr['address'],
			'name' => $paramsArr['name'],
		]];
        $sendEmail->setSendAdd( $adderss );//设置收件人邮箱地址
        $sendEmail->setSubject( '欢迎使用拿趣用企业租赁，为了您的账户安全请验证邮箱' );//设置邮件标题
        $sendEmail->setBody( self::__getEmailBody($paramsArr['name'], $code) );//设置邮件内容
        $result = $sendEmail->send();
		
		if( $result ){
			//设置验证码缓存
			redis_set(self::__getRedisKey($operateUserInfo['uid'],$paramsArr['address']), $code, 1800);
			return apiResponse([], ApiStatus::CODE_0, '验证码已经发送至您的邮箱!');
		}else{
			return apiResponse([], ApiStatus::CODE_60002, '验证码发送失败!');
		}
    }
	
	/**
	 * 根据用户id和邮箱生成 验证码缓存时间设置的key
	 * @param type $uid
	 * @param type $address
	 */
	private static function __getRedisKey( $uid, $address ){
		return md5($address.$uid);
	}
	
	/**
	 * 生成用户获取邮箱验证码的邮件正文
	 * @param type $code
	 */
	private static function __getEmailBody( $name,$code ){
		return '
			<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> </head>
			<body>
				<div id="contentDiv" onclick="getTop().preSwapLink(event, \'html\');" style="position: relative;font-size:14px;height:auto;padding:15px 15px 10px 15px;*padding:15px 15px 0 15px;overflow:visible;min-height:100px;_height:100px;" class=" body">
				<div id="mailContentContainer" style="font-size: 14px; padding: 0px; height: auto; min-height: auto; font-family: &quot;lucida Grande&quot;, Verdana; position: relative; zoom: 1; margin-right: 170px;">
            <style>
            #outlook a {
                padding: 0;
            }
            .ReadMsgBody {
                width: 100%;
            }
            .ExternalClass {
                width: 100%;
            }
            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }
            .container {
                width: 100%;
                height: 100%;
            }
            table.btn {
                margin: 0 auto;
                width: auto !important;
            }
            table.btn table td {
                background: #f0514a;
                border: 0;
                border-radius: 0;
                color: #fff;
            }
            table.btn:hover table td,
            table.btn:active table td {
                opacity: .9;
            }
            table.btn table td a {
                color: #fff;
                display: inline-block;
                font-size: 14px;
                padding: 8px 36px;
                text-decoration: none;
            }
            </style>
            <table class="container" style="width:100%;">
                <tbody>
                    <tr>
                        <td align="center" valign="top">
                            <center>
                                <table cellpadding="0" cellspacing="0" style="background:#fff;border-collapse:collapse;padding:0;margin:10px auto;max-width:600px;width:600px;">
                                    <thead>
                                        <tr>
                                            <th style="color:#ccc;padding:20px 30px;text-align:right;font-size:13px;"> <a href="https://pro.modao.cc" style="color:#979ca2;text-decoration:none;" target="_blank">来自拿趣用的邮件</a> </th>
                                        </tr>
                                    </thead>
                                    <tbody style="border:1px solid #eaebed;">
                                        <tr>
                                            <td style="text-align:left;color:#636a74;font-size:14px;line-height:24px;padding: 10px 10%">
                                                <p style="margin:10px 0 20px 0;font-weight:800;"> 亲爱的 '.$name.'： </p>
                                                <p style="margin:10px 0 20px 0;"> 欢迎注册拿趣用企业租赁！ </p>
                                                <p style="margin:10px 0 20px 0;"> 您在拿趣用申请的邮箱验证码为：<span style="font-size:18px;font-weight:800;">'.$code.'</span>，请在<span style="font-weight:800;">30分钟</span>内填入此验证码。 </p>
                                                <p style="margin:10px 0 20px 0;"> 如果您未发送过此请求，则可能是因为其他用户在尝试注册账户时误填入了您的邮件地址而使您收到这封邮件，那么您可以放心的忽略此邮件，无需进一步采取任何操作。 </p>
                                                <p style="margin:10px 0 0 0;"> 此致 </p>
                                                <p style="margin:0 0 0 0;"> 拿趣用敬上 </p>
                                                <p style="margin:0 0 20px 0;"> '.date('Y-m-d').' </p>
                                                <p style="margin:10px 0 20px 0;">---------------------------</p>
                                            </td>
                                        </tr>
                                    </tbody>
                    </tr>
                </tbody>
                </table>
                </td>
                </tr>
                </tfoot>
				</table>
				</center>
				</td>
				</tr>
				</tbody>
				</table>
				<!--<![endif]-->
				<style></style>
				</div>
			</div>
		</body>

		</html>';
	}

}
