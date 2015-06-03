<?php
abstract class CAction
{
    public $_method = 'execute';
	public $_config = array();
	public $_ret = array();
	public $_actionName = '';
    function execute(){}

    function __construct(){}

    function response(){}
}