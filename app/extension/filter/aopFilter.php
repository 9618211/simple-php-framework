<?php
class aopFilter implements IFilter
{
	public function routeStartup(){}
	public function routeShutdown($action){return $action;}
	public function dispatchStartup($action){return $action;}
	public function dispatchShutdown(){}
	public function endReturn(){}
}