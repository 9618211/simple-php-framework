<?php
/**
 * redis 操作类
 * 依赖 Redis 组件
 * 命令参考----https://github.com/nicolasff/phpredis
 * Class redis_cache
 */
class CRedis
{
	private static $_instacne;
	private $redis;
	private $_cacheprefix = '';
	private static $_config = array();
	private function __construct($cfg, $reconnect=false)
	{
		self::$_config = array_merge(self::$_config, $cfg);
		$this->redis = new Redis();
		$flag = $this->redis->connect(self::$_config['host'], self::$_config['port'], 30, null, 1000);
		if (!empty(self::$_config['auth']))
			$this->redis->auth(self::$_config['auth']);
		if (!empty(self::$_config['db']))
			$this->redis->select(self::$_config['db']);
		if (!empty(self::$_config['cacheprefix']))
			$this->redis->setOption(Redis::OPT_PREFIX, self::$_config['cacheprefix']);
	}

	/**
	 * @param array $cfg
	 * @return CRedis
	 */
	static function getInstance($cfg=array(),$reconnect=false)
	{
		if (!self::$_config){
			if (!$cfg)
				$cfg = CFactory::loadConfig('redis');
			else
				self::$_config = $cfg;
		}
		if (null == self::$_instacne)
			self::$_instacne = new self($cfg,$reconnect);
		return self::$_instacne;
	}

	function __call($method,$params)
	{
		try{
			$ret = call_user_func_array(array($this->redis,$method),$params);
		}catch (RedisException $e){ //如果发生异常
			throw new Exception($e);
		}
		return $ret;
	}


	function log($log)
	{
		if (!empty(self::$_config['logFile']))
			error_log(date('Y-m-d H:i:s')."\t\t{$log}\n", 3,self::$_config['logFile']);
		else
			var_dump($log."\n");
	}

	/**
	 * 加锁---如果1秒锁还未解除，自动解锁---避免死锁
	 * @param $key
	 * @param $wait 单位微妙
	 * @return mixed
	 */
	function addLock($key, $wait = 10000)
	{
		$key = "{$key}:LOCK";
		if ($this->redis->setnx($key, 1)){
			return true;
		}
		if ($wait){
			$releaseFlag = false;
			$lockNum = 0;
			while(true){
				if ($lockNum >= 50){ //自动解锁
					$releaseFlag = true;
					break;
				}
				usleep(mt_rand($wait, $wait*5)); //睡眠10到50毫秒
				$lockNum++;
				if ($this->redis->setnx($key,1)){ //解锁
					$releaseFlag = true;
					break;
				}
			}
			return true;
		}
		return true;
	}

	/**
	 * 解锁-----
	 * @param $key
	 * @return mixed
	 */
	function releaseLock($key)
	{
		return $this->redis->delete($key.':LOCK');
	}
}