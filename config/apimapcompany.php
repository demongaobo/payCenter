<?php

//路由映射  --- 企业租赁
return [

    'api.company.create'                        => 'CompanyController@create',       //添加企业信息
    'api.company.companyList'                   => 'CompanyController@companyList',  //企业信息列表
    'api.company.allList'                       => 'CompanyController@allList',      //所有企业信息
    'api.company.unCompled.order'               => 'CompanyController@unCompledOrderByUser',      //该用户是否可以下单
	//用户企业邮箱验证（发送验证码）
    'api.company.user.email.verify'                        => 'CompanyUserController@email',       
	//用户企业信息提交
    'api.company.user.info.verify'                        => 'CompanyUserController@info',         
	//用户企业信息详情
    'api.company.user.details'                        => 'CompanyUserController@details',        
	//用户企业信息审核
    'api.company.user.check'                        => 'CompanyUserController@check',           
	//用户企业信息列表
    'api.company.user.getlist'                        => 'CompanyUserController@getList',     
];
