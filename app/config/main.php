<?php
//php.ini设置
ini_set('open_basedir', __DIR__.'/../../'); //限制文件包含目录---
date_default_timezone_set("Asia/Shanghai");
gc_enable();
define('ENVIRONMENT', 'production');//环境变量
$CONFIG['projectName'] = 'FRAMEWORK';//项目名称

switch(ENVIRONMENT){
	case 'production':
		//env config
		date_default_timezone_set('Asia/Shanghai');
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
		//db config
		$CONFIG['db'] = array(
			'charset' => 'utf8',
			'host' => '127.0.0.1',
			'username' => 'xxx',
			'password' => 'xxx',
			'dbname' => 'xxx',
			'profiler' => 1,
			'reconnect' => false
		);
		//db table config
		$CONFIG['db_table_perfix'] = '';
		//memache config
		$CONFIG['redis'] = array(
			'host' => '127.0.0.1',
			'port' => 6379,
			'prefix' => $CONFIG['projectName']
		);

		//静态资源配置
		define('STATIC_VERSION','201504011132');
		define('SELF_DOMAIN', 'http://xxxxx');
		define('RES_DOMAIN', 'http://xxxxx');

		//上传模块配置
		define('UPLOAD_DIR', dirname(dirname(dirname(__FILE__))).'/web/res/upload');
		ini_set('upload_tmp_dir', UPLOAD_DIR.'/tmp');//临时上传目录

		//session模块配置
		$CONFIG['sessionConfig'] = array(
			'save_handler' => 'redis', //session handler
			'cache_prefix' => $CONFIG['projectName'].':SESSION:', //session cache prefix
			'save_path' => 'tcp://127.0.0.1:6379?prefix='.$CONFIG['projectName'].':SESSION:', //session save path
			'session_name' => $CONFIG['projectName'].'SSID',//session name
			'session_timeout' => 3600 //session timeout
		);

		//安全模块配置
		$CONFIG['safeModuleConfig'] = array(
			'csrfTokenValid' => 0, //是否开启anti csrf token
		);


		//框架extension 模块
		$CONFIG['extensions'] = array(
			'filter' => array('sessionFilter', 'aopFilter'),
		);
		break;
}