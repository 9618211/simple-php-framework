<?php
/**
 * http cookie class
 * Class CHttpCookie
 */
class CHttpCookie
{
	/**
	 *
	 */
	function __construct()
	{

	}

	/**
	 * @param $name
	 * @return bool
	 */
	function isSeted($name)
	{
		return isset($_COOKIE[$name]);
	}

	/**
	 * @param $name
	 * @return string
	 */
	function get($name=null)
	{
		if ($name === null) {
			return !empty($_COOKIE) ? $_COOKIE : null;
		}
		$value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
		return $value;
	}

	/**
	 * @param $name
	 * @param $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure use for https
	 * @param bool $httponly 默认使用http only防止xss跟cookie劫持
	 * @return bool
	 */
	function set($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true)
	{
		$ret  = false;
		$expire = ($expire == 0) ? time() + $expire : 0;
		$ret = setcookie($name, $value, $expire, $path, $secure, $httponly);
		if ($ret){
			$_COOKIE[$name] = $value;
		}
		return $ret;
	}

	/**
	 * 删除cookie
	 * @param $name
	 */
	function delete($name)
	{
		$this->set($name, '', -3600);
		unset($_COOKIE[$name]);
	}

	/**
	 * 销毁cookie
	 */
	function destory()
	{
		unset($_COOKIE);
	}

	/**
	 * 输出一段p3p协议的header，允许跨域发送cookie
	 * 只允许在相互信任的站点使用
	 */
	function outputP3PHeader()
	{
		header('P3P: CP=CAO PSA OUR');
	}
}