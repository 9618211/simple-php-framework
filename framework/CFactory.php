<?php
class CFactory
{
    protected static $_instances = array();

    protected static $_config;

    function __construct()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    static function autoload($class)
    {
        if (substr($class, 0, 1) == 'C'){ //框架类
            return self::instance($class,array(),true,false);
        } elseif (substr($class, -6, 6) == 'Filter'){ //扩展的Filter类
            return self::instance($class,array(),true,false);
        }elseif (substr($class, -6, 6) == 'Router'){ //扩展的Router类
            return self::instance($class,array(),true,false);
        }
    }

    /**
     * @param $instanceName
     * @param array $constructParams
     * @param bool $instanceNow
     * @param bool $instanceWithParams
     * return mixed
     */
    static function instance($instanceName, $constructParams=array(), $instanceNow=false, $instanceWithParams=false)
    {
        $instanceid = $instanceName;
        if($instanceWithParams)
            $instanceid .= '_'.hash('crc32',serialize($constructParams));
        if( !isset(self::$_instances[$instanceid]) )
        {
            if($instanceNow)
            {
                self::$_instances[$instanceid] = self::instanceNow($instanceName,$constructParams);
            }
            else
            {
                self::$_instances[$instanceid] = self::proxy($instanceid, $instanceName, $constructParams);
            }
        }
        return self::$_instances[$instanceid];
    }

    /**
     * 框架类加载
     * @param $class
     * @param $constructparams
     * @return mixed
     */
    static function loadFrameClass($class, $constructparams=array())
    {
        $file = FRAMEWORK_ROOT.'/'.$class.'.php';
        self::loadFile($file);
        return new $class($constructparams);
    }

    /**
     * Action类加载
     * @param $class
     * @param $constructparams
     * @return mixed
     */
    static function loadAction($class, $constructparams=array())
    {
        $actionsplit = explode('_',$class);
        $realfname = implode('_',array_slice($actionsplit, 1,-1));
        $file = APP_ROOT.'/action/'.$actionsplit[0].'/'.$realfname.'Action.php';
        self::loadFile($file);
        $action = new $class($constructparams);
        foreach ($action as $key => $ep){
            if (substr($key, -7, 7) == 'Service'){
                $action->$key = self::instance($key, array(), false, false);
            }
        }
        return $action;
    }

    /**
     * Service类加载
     * @param $class
     * @param $constructparams
     * @return mixed
     */
    static function loadService($class, $constructparams=array())
    {
        $file = APP_ROOT.'/service/'.$class.'.php';
        self::loadFile($file);
        $service =  new $class($constructparams);
        foreach ($service as $key => $ep){
            if (substr($key, -3, 3) == 'Dao'){
                $service->$key = self::instance($key, array(), false, false);
            }
        }
        return $service;
    }

    /**
     * @param $class
     * @param array $constructparams
     * @return mixed
     */
    static function loadDao($class, $constructparams=array())
    {
        $actionsplit = explode('_',$class);
        $realfname = implode('_',array_slice($actionsplit, 1,-1));
        $file = APP_ROOT.'/dao/'.$class.'.php';
        self::loadFile($file);
        return new $class($constructparams);
    }

    /**
     * 扩展类加载
     * @param $class
     * @param $type
     * @param array $constructparams
     * @return mixed
     */
    static function loadExtension($class, $type, $constructparams=array())
    {
        $classfile = APP_ROOT.'/extension/'.$type.'/'.$class.'.php';
        self::loadFile($classfile);
        return new $class($constructparams);
    }

    /**
     * @param $name
     * @param $constructparams
     * @return mixed
     */
    static function instanceNow($name, $constructparams=array())
    {
        if (substr($name, 0, 1) == 'C'){
            return self::loadFrameClass($name, $constructparams);
        }elseif(substr($name, -6, 6) == 'Filter'){
            return self::loadExtension($name, 'filter', $constructparams);
        } elseif (substr($name, -6, 6) == 'Router'){
            return self::loadExtension($name, 'router', $constructparams);
        }
        elseif (substr($name, -6, 6) == 'Action')
        {
            return self::loadAction($name, $constructparams);
        }
        elseif (substr($name, -7, 7) == 'Service')
        {
            return self::loadService($name, $constructparams);
        }
        elseif( substr($name, -3, 3) == 'Dao')
        {
            return self::loadDao($name, $constructparams);
        }
    }

    /**
     * @param $instanceid
     * @param $classname
     * @param $constructParams
     * @return proxy_class
     */
    static function proxy($instanceid, $classname, $constructParams)
    {
        return new proxy_class($instanceid, $classname, $constructParams);
    }

    /**
     * 引入文件
     * @param $file
     */
    static function loadFile($file)
    {
        if (is_string($file)){
            require_once $file;
        }else {
            foreach ($file as $ef){
                self::loadFile($ef);
            }
        }
    }

    /**
     * @param $instanceid
     * @param $func
     * @param $params
     * @return mixed
     */
    static function proxyCall($instanceid, $func, $params)
    {
        if (!isset(self::$_instances[$instanceid]) || get_class(self::$_instances[$instanceid]) == 'proxy_class'){
            self::$_instances[$instanceid] = self::instanceNow(self::$_instances[$instanceid]->classname, self::$_instances[$instanceid]->constructParams);
        }
        return call_user_func_array(array(self::$_instances[$instanceid], $func), $params);
    }

    /**
     * @param $file
     * @param $index
     * @return mixed
     * @throws Exception
     */
    static function loadConfig($index=null, $file=null)
    {
        $configPath = APP_ROOT.'/config/';
        if ($file){
            $file = $configPath.$file.'.php';
        } else {
            $file = $configPath.'main.php';
        }
        if (is_array(self::$_config) && array_key_exists($index, self::$_config)) {
            return self::$_config[$index];
        } else {
            require_once $file;
            if ($index && !isset($CONFIG[$index])){
                throw new Exception("can not find {$index} in config file {$file}");
            }
            if (is_array(self::$_config)){
                self::$_config += $CONFIG;
            }else {
                self::$_config = $CONFIG;
            }
            return $index?$CONFIG[$index]:$CONFIG;
        }
    }
}

class proxy_class
{
    public $instanceid;
    public $classname;
    public $constructParams;
    function __construct($instanceid, $classname, $constructParams)
    {
        $this->instanceid = $instanceid;
        $this->classname = $classname;
        $this->constructParams = $constructParams;
    }

    function __call($func, $params)
    {
        return CFactory::proxyCall($this->instanceid, $func, $params);
    }
}