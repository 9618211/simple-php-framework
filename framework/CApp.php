<?php
require_once FRAMEWORK_ROOT.'/CFactory.php';
require_once FRAMEWORK_ROOT.'/helper/function.php';
require_once FRAMEWORK_ROOT.'/CException.php';
class CApp
{
    private static $_instance;
    /**
     * @var CRouter
     */
    public $_router;
    /**
     * @var CUrlPath
     */
    public $_urlpath;
    /**
     * @var CFilter
     */
    public $_filter;
    /**
     * @var CFactory
     */
    private $_factory;
    static function getInstance()
    {
        if(null == self::$_instance){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        $this->_initFactory();
        $this->_initUrlPath();
        $this->_initRouter();
        $this->_initFilter();
    }

    protected function _initFactory()
    {
        $this->_factory = new CFactory();
    }

    function run()
    {
		try{
			$this->_filter->routeStartup(); //路由开始
			$action = $this->_router->route();
			$action = $this->_filter->routeShutdown($action); //路由关闭

			$action = $this->_filter->dispatchStartup($action); //方法调度

			$method = $action->_method;

			$action->$method();

			$this->_filter->dispatchShutdown(); //方法调度完成

			$action->response();

			$this->_filter->endReturn(); //返回结果
		}catch(CException $e){//异常处理
			if ($e->exceptionType == 'biz'){ //业务逻辑异常--抛到业务层处理
				$actionclass = $e->exActionMethod.'_Action';
				$action = CFactory::instance($actionclass,$e->params,true,true);
				$method = $action->_method;
				$action->$method();
				$action->response();
				$this->_filter->endReturn(); //返回结果
			}else { //核心异常--直接抛出异常
				throw new Exception($e);
			}
		}
    }

    private function _initUrlPath()
    {
        if (null == $this->_urlpath){
            $this->_urlpath = new CUrlPath();
        }
    }

    private function _initRouter()
    {
        if (null == $this->_router){
            $this->_router = new CRouter();
        }
        $this->_router->setUrlPath($this->_urlpath);
    }

    private function _initFilter()
    {
        if (null == $this->_filter){
            $this->_filter = new CFilter();
        }
    }
}