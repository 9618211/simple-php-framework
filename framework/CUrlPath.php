<?php
class CUrlPath
{
    private $_rewrite_base = '/';
    private $_this_path = null;

    function getRewriteBase()
    {
        return $this->_rewrite_base;
    }

    function setRewriteBase($rewrite_base)
    {
        $this->_rewrite_base = $rewrite_base;
    }

    function setThisPath($_this_path)
    {
        $this->_this_path = $_this_path;
    }

    function getRequestUri()
    {
        if( isset($_SERVER['REQUEST_URI']) )
            return preg_replace('#^http://[^/]+/#i', '/', $_SERVER['REQUEST_URI']);
        elseif( isset($_SERVER['argv']) && isset($_SERVER['argv'][1]) )
            return $_SERVER['argv'][1];
        else
            return '';
    }

    function getRequestPath($path = null)
    {
        if (!$path)
            $path = $this->getRequestUri();

        //过滤前置路径
        if ( $this->_rewrite_base && (strpos($path, $this->_rewrite_base) === 0) ) {
            $path = substr($path, strlen($this->_rewrite_base));
        }

        if( substr($path,0,1)=='?' || substr($path,0,2)=='/?' )
        {
            return null;
        }

        if ( strpos($this->_rewrite_base, '?')===false  ) {
            if ( strpos($path, '?') ) {
                $path = substr($path, 0, strpos($path, '?'));
            }
        }
        else{
            //如果前置路径有?则以&符号截取
            if ( strpos($path, '&') ) {
                $path = substr($path, 0, strpos($path, '&'));
            }
        }
        if (substr($path,0,1)=='&') {
            return null;
        }
        $path = preg_replace('#/+#', '/', $path);
        if( (strpos($path,'/')===false && strpos($path,'%2F')!==false) || strpos($path,'/')>strpos($path,'%2F') )
            $path = str_replace('%2F','/',$path);
        return preg_match('#^/#', $path) ? $path : '/'.$path;
    }

    function getRequestPaths($path = null)
    {
        if( !$path && $this->_this_path )
            $path = $this->_this_path;
        $path  = $this->getRequestPath($path);
        $paths = $this->pathToArray($path);
        return $paths;
    }

    function pathToArray($path)
    {
        return explode('/', trim($path, '/'));
    }
}