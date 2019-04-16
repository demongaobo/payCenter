<?php

/**
 * 业务配置
 * int 业务标识 => 业务class
 */
return [

	'business' => [
        // 业务类型为【租机】的工厂实例
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_ZUJI => App\Order\Modules\Repository\Zuji\Zuji::class,
	    // 业务类型为【买断】的工厂实例
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_BUYOUT => App\Order\Modules\Repository\Buyout\Buyout::class,
		// 业务类型为【分期主动支付】的工厂实例
		\App\Order\Modules\Inc\OrderStatus::BUSINESS_FENQI => App\Order\Modules\Repository\Instalment\Instalment::class,
	    // 业务类型为【还机】的工厂实例
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_GIVEBACK => \App\Order\Modules\Repository\Giveback\GivebackPay::class,
		 // 业务类型为【续租】的工厂实例【短租续租-支付】
	    \App\Order\Modules\Inc\OrderStatus::BUSINESS_RELET => \App\Order\Modules\Repository\Relet\ReletPay::class,
        // 业务类型为【逾期缴费】的工厂实例【订单商品逾期支付】
        \App\Order\Modules\Inc\OrderStatus::BUSINESS_OVERDUE => \App\Order\Modules\Repository\Overdue\Overdue::class,
	],
];
