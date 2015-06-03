<?php
class CService
{
    function __construct(){}

	function getPageData($dao,$cond=null,$cols=null,$order=null,$count=null,$offset=null)
	{
		return $this->$dao->get($cond,$cols,$order,$count,$offset);
	}

	function getPageCount($dao,$cond)
	{
		return $this->$dao->getCount($cond);
	}
}