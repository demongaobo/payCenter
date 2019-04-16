<?php

/**
 * 业务配置
 * int 业务标识 => 业务class
 */
return [

	'type' => [
        // 业务类型为【租机】的工厂实例
	    \App\Comment\Modules\Inc\CommentStatus::Topic => App\Comment\Modules\Service\Comment\Topic::class,
	    // 业务类型为【买断】的工厂实例
	    \App\Comment\Modules\Inc\CommentStatus::Comment => App\Comment\Modules\Service\Comment\Comment::class,
	],
    'system_key' => [
        1 => 'order',
        2 => 'goods',
    ],
	
	'COMMENT_API'	=> env('COMMENT_API', 'https://dev-api.nqyong.com/api/order/api'),
	
];
