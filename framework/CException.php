<?php
/**
 * 异常类------CException
 * Class CException
 */
class CException extends Exception
{
	public $params;
	public $exActionMethod;
	public $exceptionType = 'core';
	public function __construct($params=array(), $exActionMethod, $exceptionType = 'core')
	{
		$this->params = $params;
		$this->exActionMethod = $exActionMethod;
		$this->exceptionType = $exceptionType;

		$exceptionMethod = $exceptionType.'Exception';
		$this->$exceptionMethod($params, $exActionMethod);
	}

	/**
	 * 业务逻辑异常
	 */
	private function bizException($params, $exActionMethod)
	{

	}

	/**
	 * 核心框架异常
	 */
	private function coreException($params, $exActionMethod)
	{

	}
}