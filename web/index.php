<?php
/**
 * a demo for index php
 */
define('FRAMEWORK_ROOT',  dirname(__FILE__).'/../framework');
define('APP_ROOT',  dirname(__FILE__).'/../app');

require_once FRAMEWORK_ROOT.'/CApp.php';
//include the app config file
CFactory::loadConfig();
$app = CApp::getInstance();

//注入extension模块
if ($extensions = CFactory::loadConfig('extensions')){
	if (!empty($extensions['filter'])){ //filter
		foreach ($extensions['filter'] as $efilter){
			$app->_filter->registerFilter(new $efilter());
		}
	}
	if (!empty($extensions['router'])){//router
		$app->_router = new $extensions['router']();
	}
}

$app->run();