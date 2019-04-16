<?php

/**
 * 业务配置
 * int 业务标识 => 业务class
 */
return [

	'business_type' => [
        // 业务类型为【租机】的工厂实例
        \App\Tools\Modules\Inc\ToolStatus::Coupon => App\Tools\Modules\Repository\Compute\Coupon::class,
	    // 业务类型为【买断】的工厂实例
	    \App\Tools\Modules\Inc\ToolStatus::Increment => App\Tools\Modules\Repository\Compute\Increment::class,
	],
    'compute_type'  => [
        //立减
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypeFixed => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypePercentage => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypeFirstMonthRentFree => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypeDecline => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypeVoucher => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponTypeFullReduction => 
        //\App\Tools\Modules\Inc\CouponStatus::CouponType => 
        
        //直接加法计算
        \App\Tools\Modules\Inc\IncrementStatus::ComputeAdd => App\Tools\Modules\Repository\ComputeBusiness\Add::class,
    ],
];
