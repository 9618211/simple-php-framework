<?php
/**
 * http session class
 * Class CHttpSession
 */
class CHttpSession
{
	private $_config = array();

	/**
	 * @param array $config
	 */
	function __construct($config = array())
	{
		$this->_config = array_merge($this->_config, $config);
		if (empty($this->_config)){
			$this->_config = CFactory::loadConfig('sessionConfig');
		}
	}

	function start()
	{
		ini_set('session.save_handler', $this->_config['save_handler']);
		ini_set('session.save_path', $this->_config['save_path']);
		ini_set('session.name', $this->_config['session_name']);
		ini_set('session.gc_maxlifetime', $this->_config['session_timeout']);
		session_start();
		if (empty($_COOKIE) || empty($_COOKIE[$this->_config['session_name']])){
//			setcookie($this->_config['session_name'], session_id(),)
		}
	}

	/**
	 * 批量写入session
	 * @param $arr
	 * @return mixed
	 */
	function write($arr)
	{
		return ($_SESSION = $arr);
	}

	/**
	 * 当个添加session
	 * @param $key
	 * @param $val
	 * @return mixed
	 */
	function add($key, $val)
	{
		return ($_SESSION[$key] = $val);
	}

	/**
	 *
	 * @param $key
	 * @return bool
	 */
	function delete($key)
	{
		if (isset($_SESSION[$key])){
			unset($_SESSION[$key]);
			return true;
		}
		return false;
	}

	/**
	 * session destory
	 */
	function destory()
	{
		$_SESSION = null;
	}

	/**
	 * 获取session
	 * @param null $key
	 * @return null
	 */
	function getSession($key = null)
	{
		if ($key == null){
			return $_SESSION;
		}
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	/**
	 * 获取当前会话id
	 * @return string
	 */
	function getSesionId()
	{
		return session_id();
	}

	/**
	 * 获取当前回话名
	 * @return string
	 */
	function getSessionName()
	{
		return session_name();
	}

	/**
	 * 重新生成一个sessionId，通常登录完成后需要
	 * @return bool
	 */
	function regenerateSid()
	{
		session_regenerate_id(true);
	}

	/**
	 * 获取缓存中完整的sessionId key,可以用查询跟删除session记录
	 * @return string
	 */
	function getSessionCacheId()
	{
		$prefix = '';
		if (!empty($this->_config['cache_prefix'])){
			$prefix = $this->_config['cache_prefix'];
		}
		return $prefix.session_id();
	}
}