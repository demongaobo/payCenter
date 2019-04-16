<?php
/**
 * 宅急送有关配置
 * Created by PhpStorm.
 * User: qinliping
 * Date: 2019/03/01
 * Time: 10:28
 */
return [
    //宅急送签名配置
    'zjs'=>[
        // 四位随机数1
        'var1'        =>mt_rand(1000,9999),
        // 四位随机数2
        'var2'         =>mt_rand(1000,9999),
        //客户标识
        'clickFlag'	=>"test",
        //密钥
        'secretKey'	=> "aafc04a1bacb487fa8d03f2a7bfdb555",
        //常量
        'constant'             =>"z宅J急S送g"
    ]

];