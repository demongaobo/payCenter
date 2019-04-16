<?php
namespace App\Company\Modules\Inc;

/**
 * 企业信息合作状态
 * Class CompanyStatus
 * @package App\Company\Modules\Inc
 */


class CompanyStatus{

    const Cooperation_on = 1;
    const Cooperation_close = 2;

    public $enum_type = [
        self::Cooperation_on => '合作中',
        self::Cooperation_close => '已关闭',
    ];


    /**
     * 状态列表
     * @return array
     */
    public static function getStatusList(){
        return [
            self::Cooperation_on => '合作中',
            self::Cooperation_close => '已关闭',
        ];
    }

    /**
     * 获取状态名称
     * @param int $status
     * @return string 状态名称
     */
    public static function getStatusName($status){
        $list = self::getStatusList();
        if( isset($list[$status]) ){
            return $list[$status];
        }
        return '';
    }


}