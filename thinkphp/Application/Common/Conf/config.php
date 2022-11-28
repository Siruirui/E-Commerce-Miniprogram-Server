<?php
return array(
	//'配置项'=>'配置值'
	'DB_TYPE'=>'mysql',// 数据库类型
	'DB_HOST'=>'127.0.0.1',
	'DB_NAME'=>'news',
	'DB_USER'=>'admin',
	'DB_PWD'=>'66666666',
	'DB_PORT'=>3306,
	'DB_PARAMS'=>array(),
	'DB_CHARSET'=>'utf8',
	'DB_DEBUG'=>TRUE,
	
    'MODULE_ALLOW_LIST'    =>    array('Home'),
    'DEFAULT_MODULE'       =>    'Home',
    'DEFAULT_CONTROLLER' => 'Index', // 默认控制器名称
);