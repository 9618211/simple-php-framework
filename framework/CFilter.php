<?php
interface IFilter{
	public function routeStartup();
	public function routeShutdown($action);
	public function dispatchStartup($action);
	public function dispatchShutdown();
	public function endReturn();
}

class CFilter implements IFilter
{
    protected $_filters = array();

    function registerFilter(IFilter $filter)
    {
        if (array_search($filter, $this->_filters, true) !== false) {
            throw new Exception('Filter already registered.');
        }
        $this->_filters[] = $filter;
        return $this;
    }

    function unregisterFilter(IFilter $filter)
    {
        $key = array_search($filter, $this->_filters, true);
        if ($key === false) {
            throw new Exception('Filter never registered.');
        }
        unset($this->_filters[$key]);
        return $this;
    }

    function routeStartup()
    {
        foreach ($this->_filters as $filter) {
            $filter->routeStartup();
        }
    }

    function routeShutdown($action)
    {
        foreach ($this->_filters as $filter) {
            $action = $filter->routeShutdown($action);
        }
        return $action;
    }

    function dispatchShutdown()
    {
        foreach ($this->_filters as $filter) {
            $action = $filter->dispatchShutdown();
        }
    }

    function endReturn()
    {
        foreach ($this->_filters as $filter) {
            $filter->endReturn();
        }
        return $this;
    }

    function dispatchStartup($action)
    {
        foreach ($this->_filters as $filter) {
            $action = $filter->dispatchStartup($action);
        }
        return $action;
    }
}