<?php

namespace App\Company\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *  企业用户信息 数据库model
 * @access public (访问修饰符)
 * @author yaodongxu <yaodongxu@huishoubao.com>
 * @copyright (c) 2019, Huishoubao
 */
class CompanyUser extends Model
{
    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    // Rest omitted for brevity

    protected $table = 'order_company_user';

    protected $primaryKey='id';
    /**
     * 默认使用时间戳戳功能
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['name','mobile','cert_no','company_id','address','department','status','check_reason',
		'check_remark','check_time','check_user_id','check_user_name','update_time','create_time'];

    /**
     * 获取当前时间
     *
     * @return int
     */
    public function freshTimestamp() {
        return time();
    }

    /**
     * 避免转换时间戳为时间字符串
     *
     * @param DateTime|int $value
     * @return DateTime|int
     */
    public function fromDateTime($value) {
        return $value;
    }

}