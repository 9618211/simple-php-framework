<?php
class CRouter
{
    /**
     * @var CUrlPath
     */
    protected $_url_path;

    /**
     * 设置 UrlPath，REQUEST URI 相关的操作均委托给 UrlPath 处理。
     */
    function setUrlPath(CUrlPath $url_path)
    {
        $this->_url_path = $url_path;
    }

    function __construct($url_path=null)
    {
        if ($url_path)
            $this->setUrlPath($url_path);
    }
	
    function route($controller=null, $action=null, $method=null)
    {
        if (!$controller && !$action){
            $paths = $this->_url_path->getRequestPaths();
            if(sizeof($paths) <= 1)
            {
                if ($paths[0] == '')
                {
                    $controller = 'index';
                    $action = 'index';
                }
                else
                {
                    if( substr($paths[0],0,1)=='~' )
                    {
                        $controller = 'index';
                        $action = 'index';
                    }
                    else
                    {
                        $controller = $paths[0];
                        $action = 'index';
                    }
                }
            }
            else
            {
                $start = 0;
                if( substr($paths[$start],0,1) == '~' )
                {
                    $controller = 'index';
                    $action = 'index';
                    $method = substr($paths[$start], 1);
                    $start +=1 ;
                }
                elseif ( substr($paths[$start+1],0,1) == '~' )
                {
                    $controller = $paths[$start];
                    $action = 'index';
                    $method = substr($paths[$start+1], 1);
                    $start += 2;
                }
                elseif ( isset($paths[$start+2]) && substr($paths[$start+2],0,1) == '~' )
                {
                    $controller = $paths[$start];
                    $action = $paths[$start+1];
                    $method = substr($paths[$start+2], 1);
                    $start += 3;
                }
                else
                {
                    $controller = $paths[$start];
                    $action = $paths[$start+1];
                    $start += 2;
                }
            }
        }
        $actionName = $controller.'_'.$action.'_Action';
        $action = CFactory::instance($actionName, array(), true, false);
        if (isset($method))
            $action->_method = $method;
		$action->_actionName = $actionName;
        return $action;
    }
}