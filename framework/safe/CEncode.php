<?php
/**
 * 安全encode类
 * Class CEncode
 */
class CSafe
{
	/**
	 * 应用场景：输出url到页面
	 * 防止xss
	 * urlencode
	 */
	function urlEncode($url)
	{
		return 	urlencode($url);
	}

	/**
	 * 应用场景：html富文本提交过滤
	 * 防止xss
	 * 策略----标签白名单(可自行添加)，<a>,<img>,<div>,<td>,<tr>
	 */
	function htmlText()
	{

	}

	/**
	 * html 输出encode
	 */
	function htmlEncode($str)
	{
		return htmlspecialchars($str);
	}
}