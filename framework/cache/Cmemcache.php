<?php

/**
 * 缓存---memcahe
 * Class Cmemcache
 */
class Cmemcache
{
    private static $_instacne;
    private $memcache;
    private $_cacheprefix;
	private $config = array(
		'host' => '127.0.0.1','port' => 11211, 'connect_timeout' => 10,
	);

    private function __construct($config)
    {
		if (!$config){
			$config = CFactory::loadConfig('memcache');
		}
		$this->config = array_merge($this->config,$config);
        $this->memcache = new Memcache();
		foreach ($this->config['servers'] as $es){
			$this->memcache->addserver($es['host'], $es['port']);
		}
	}

    static function getInstance($cfg=array())
    {
        if (null == self::$_instacne){
            self::$_instacne = new self($cfg);
        }
        return self::$_instacne;
    }
    
    function set($key, $value, $limit = 2592000)
    {
        return $this->memcache->set($key, $value, MEMCACHE_COMPRESSED, $limit);
    }

    function get($key)
    {
        return $this->memcache->get($key);
    }

	function delete($key)
	{
//		return $this->memcache->delete($key);
	}

	function add($key, $val)
	{
		return $this->memcache->add($key, $val);
	}

	/**
	 * 加锁---如果五秒锁还未解除，自动解锁---避免死锁
	 * @param $key
	 * @param $wait 单位微妙
	 * @return mixed
	 */
	function addLock($key, $wait = 5000000)
	{
		$key .= ':LOCK';
		if ($this->memcache->add($key, 1)){
			return true;
		}
		if ($wait){
			usleep($wait);
			if ($this->memcache->add($key,1)){
				return true;
			}
			$this->memcache->delete($key);
			return true;
		}
	}

	/**
	 * 解锁-----
	 * @param $key
	 * @return mixed
	 */
	function releaseLock($key)
	{
		return $this->memcache->delete($key.':LOCK');
	}

	function __call($method,$params)
	{
		return call_user_func_array(array($this->memcache,$method),$params);
	}
}