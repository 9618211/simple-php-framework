<?php
require_once FRAMEWORK_ROOT.'/web/CHttpAction.php';
class index_index_Action extends CHttpAction
{
	function __construct()
	{
		$this->_config['template'] = null;
		parent::__construct();
	}

	function execute()
	{
	}
}