<?php
namespace App\Company\Modules\Repository;
use App\Company\Models\CompanyUser;
use Illuminate\Support\Facades\DB;

/**
 *  企业用户信息 操作数据库类
 * @access public (访问修饰符)
 * @author yaodongxu <yaodongxu@huishoubao.com>
 * @copyright (c) 2019, Huishoubao
 */

class CompanyUserRepository
{
	/**
	 * 全部状态
	 */
	const STATUS_ALL = -1;
	/**
	 * 审核初始化
	 */
	const STATUS_INIT = 0;
	/**
	 * 审核通过
	 */
	const STATUS_ACCEPT = 1;
	/**
	 * 审核拒绝
	 */
	const STATUS_REJECT = 2;
	
    /**
     * 根据mobile 判断用户是否已经通过审核
     * @param $mobile
     * @return bool
     */
    public static function isAccept($mobile){
        if(empty($mobile)){
            return false;
        }
		$where = [
			['order_company_user.mobile','=',$mobile],
			['order_company_user.status','=',1],//用户信息已经审核通过
			['order_company_info.status','=',1]//合作中的企业
		];
        $res= CompanyUser::where($where)
				->leftJoin('order_company_info', 'order_company_user.company_id', '=', 'order_company_info.id')
				->first();
        if(!$res){
            return false;
        }
        return true;
    }
    /**
     * 根据mobile 查询企业用户信息
     * @param $mobile
     * @return bool
     */
    public static function getInfoByMobile($mobile){
        if(empty($mobile)){
            return [];
        }
		$where = [
			['order_company_user.mobile','=',$mobile],
		];
        $res= CompanyUser::where($where)->first();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }
    /**
     * 根据address邮箱 查询企业用户信息
     * @param $address
     * @return bool
     */
    public static function getInfoByAddress($address){
        if(empty($address)){
            return [];
        }
		$where = [
			['order_company_user.address','=',$address],
		];
        $res= CompanyUser::where($where)->first();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }
	/**
	 * 获取企业用户信息列表
	 * @param array $where 查询条件
	 * $where = [<br\>
	 *     'begin_time' => '',//企业用户创建的开始时间<br\>
	 *     'end_time' => '',//企业用户创建的结束时间<br\>
	 *     'status' => '',//企业用户审核状态<br\>
	 *     'company_status' => '',//企业合作状态<br\>
	 *     'check_time' => '',//用户审核时间<br\>
	 *     'company_id' => '',//企业id<br\>
	 * ]<br\>
	 * @param array $additional 附加条件
	 * $additonal = [<br\>
	 *		'page' => '',//页数<br\>
	 *		'size' => '',//每页大小<br\>
	 * ]<br\>
	 * @return array $result 返回的数据
	 * $result = [<br\>
	 *		'current_page'=>'',//当前页<br\>
	 *		'first_page_url'=>'',//第一页url<br\>
	 *		'from'=>'',//从第几条数据<br\>
	 *		'last_page'=>'',//最终页<br\>
	 *		'last_page_url'=>'',//最终页url<br\>
	 *		'next_page_url'=>'',//下一页<br\>
	 *		'path'=>'',//当前域名路径<br\>
	 *		'per_page'=>'',//每页大小<br\>
	 *		'prev_page_url'=>'',//上一页<br\>
	 *		'to'=>'',//到第几条数据<br\>
	 *		'total'=>'',//一共几条数据<br\>
	 *      'data' => [ //数据详情<br\>
	 *			'id' => ''//主键id<br\>
	 *			'name' => ''//用户姓名<br\>
	 *			'mobile' => ''//手机号<br\>
	 *			'cert_no' => ''//身份证号<br\>
	 *			'address' => ''//邮箱地址<br\>
	 *			'department' => ''//部门<br\>
	 *			'status' => ''//审核状态<br\>
	 *			'check_reason' => ''//审核原因<br\>
	 *			'check_remark' => ''//审核备注<br\>
	 *			'check_time' => ''//审核时间<br\>
	 *			'check_user_id' => ''//审核用户id<br\>
	 *			'check_user_name' => ''//审核用户姓名<br\>
	 *			'company_name' => ''//企业名称<br\>
	 *			'company_status' => ''//企业合作状态<br\>
	 *			'create_time' => ''//用户认证信息提交时间<br\>
	 *			'update_time' => ''//审核信息更新时间<br\>
	 *      ]
	 * ]
	 */
	public static function getList( $where = [], $additional = [] ) {
		$where = self::__parseWhere( $where );
		$additional = self::__parseAddition( $additional );
        $userList = CompanyUser::where($where)
            ->leftJoin('order_company_info', 'order_company_info.id', '=', 'order_company_user.company_id')
            ->select('order_company_user.*','order_company_info.company_name')
			->orderBy('order_company_user.status', 'asc')
			->orderBy('order_company_user.create_time', 'desc')
//			paginate: 参数
//			perPage:表示每页显示的条目数量
//			columns:接收数组，可以向数组里传输字段，可以添加多个字段用来查询显示每一个条目的结果
//			pageName:表示在返回链接的时候的参数的前缀名称，在使用控制器模式接收参数的时候会用到
//			page:表示查询第几页及查询页码
            ->paginate($additional['size'],['*'], 'p', $additional['page']);
		$userList = objectToArray($userList);
		if( $userList ){
			foreach ($userList['data'] as  &$value) {
				$value['status_name'] = $value['status'] ? ($value['status'] == 1? '审核通过':'审核驳回') : '待审核';
				$value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
				$value['update_time'] = date('Y-m-d H:i:s',$value['update_time']);
				$value['check_time'] = date('Y-m-d H:i:s',$value['check_time']);
			}
		}
		
		\App\Lib\Common\LogApi::info('companyexport5', ['companyexport5',$userList]);
        return $userList;
	}
	
    /**
     * 根据条件更新数据
	 * @param array $where 更新条件[至少含有一项条件]
	 * $where = [<br/>
	 *		'mobile' => '',//手机号<br/>
	 * ]<br/>
	 * @param array $data 需要更新的数据 [至少含有一项数据]
	 * $data = [<br/>
	 *		'status'=>'',//审核状态<br/>
	 *		'check_reason'=>'',//审核原因<br/>
	 *		'check_reamrk'=>'',//审核备注<br/>
	 *		'name'=>'',//姓名（分）<br/>
	 *		'cert_no'=>'',//身份证号<br/>
	 *		'company_id'=>'',//企业id(关联order_company_info表)<br/>
	 *		'address'=>'',//邮箱地址<br/>
	 *		'department'=>'',//所在部门<br/>
	 * ]
	 */
	public static function update( $where, $data ) {
		$where = filter_array($where, [
			'mobile' => 'required',
		]);
		$data = filter_array($data, [
			'status' => 'required',
			'check_reason' => 'required',
			'check_reamrk' => 'required',
			'check_user_id' => 'required',
			'check_user_name' => 'required',
			'check_remark' => 'required',
			'name' => 'required',
			'cert_no' => 'required',
			'company_id' => 'required',
			'address' => 'required',
			'department' => 'required',
		]);
		if( count( $where ) < 1 ){
			return false;
		}
		if( count( $data ) < 1 ){
			return false;
		}
		$data['update_time'] = time();
		if( isset($data['status']) && $data['status'] ){
			$data['check_time'] = time();
		}
		return CompanyUser::where($where)->update( $data );
	}
	
	private static function __parseWhere( $where ){
		$whereArray = [];
        //根据审核状态
        if (isset($where['status']) && in_array($where['status'], [self::STATUS_INIT,self::STATUS_ACCEPT, self::STATUS_REJECT])) {
            $whereArray[] = ['order_company_user.status', '=', $where['status']];
        }
        //根据企业id
        if (isset($where['company_id']) && $where['company_id']) {
            $whereArray[] = ['order_company_info.id', '=', $where['company_id']];
        }
		return $whereArray;
	}
	private static function __parseAddition( $additional ){
		$additional['page'] = isset($additional['page']) && $additional['page'] ? $additional['page'] : 1;
		$additional['size'] = isset($additional['size']) && $additional['size'] ? max($additional['size'],20) : 20;
		return $additional;
	}
}