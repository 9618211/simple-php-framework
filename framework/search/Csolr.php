<?php
require_once dirname(__FILE__).'/../cache/Cmemcache.php';
/**
 * 基于php solr extension
 * Class Csolr
 */
class Csolr
{
    private $_client;

    private $_doc;

    private $_query;

    private $_response;
    private $_cfg = array(
        'hostname' => '',
        'login' => '',
        'password' => '',
        'port' => '',
        'wt' => '',
        'timeout' => 10,
    );

    private static $_instance;

    private $cachetime = 1800;
    private function __construct($cfg)
    {
        $this->_cfg = array_merge($this->_cfg, $cfg);
    }


    static function getInstance($cfg=array())
    {
        if (null == self::$_instance){
            self::$_instance = new self($cfg);
        }
        return self::$_instance;
    }

    function query($query=array(), $fields=null, $start=null, $rows=null, $filters=null, $highlightFields = array())
    {
        $cacheKey = md5(serialize(func_get_args()));
        $this->getClient();
        $this->getQuery();
        if ($query){
            $qstr = '';
            foreach ($query as $key => $val){
                $qstr .= ' && '.$key.':'.$this->escapeStr($val);
            }
            $qstr = substr($qstr, 4);
            $this->_query->setQuery($qstr);
        }
        if ($start && $start >= 0)
            $this->_query->setStart($start);
        if ($rows && $rows >= 0)
            $this->_query->setRows($rows);

        if ($fields){
            $fstr = '';
            foreach ($fields as $ef){
                $fstr .= ','.$ef;
            }
            $fstr = substr($fstr, 1);
            $this->_query->addField($fstr);
        }
        if ($filters){
            foreach($filters as $field => $val){
                $this->_query->addFilterQuery($field.':'.$this->escapeStr($val));
            }
        }
        if ($highlightFields){
            $this->_query->setHighlight(true);
            foreach ($highlightFields as $hf){
                $this->_query->addHighlightField ($hf);
            }
        }
        try{
            $this->_response = @$this->_client->query($this->_query);
        }catch (SolrClientException $e){
            return array('docs' => array() ,'numFound' => 0, 'QTime' => 0, 'status' => 0);
        }

        $rs =  $this->_response->getResponse();
        $rs['response']['QTime'] = $rs['responseHeader']['QTime'];
        $rs['response']['status'] = $rs['responseHeader']['status'];
        if (!empty($rs['highlighting']))
            $rs['response']['highlighting'] = $rs['highlighting'];
        return $rs['response'];
    }

    private function getClient()
    {
        if (null == $this->_client){
            $this->_client = new SolrClient($this->_cfg);
        }
    }

    private function getDocument()
    {
        if (null == $this->_doc){
            $this->_doc = new SolrDocument();
        }
    }

    private function getQuery()
    {
        if (null == $this->_query){
            $this->_query = new SolrQuery();
        }
    }

    private function getResponse()
    {
        if (null == $this->_response){
            $this->_response = new SolrResponse();
        }
    }

    function add($docs)
    {
        $this->getClient();
        $doc = new SolrInputDocument();
        foreach ($docs as $field => $value){
            $doc->addField($field, $value);
        }
        $this->_response = $this->_client->addDocument($doc,$overwrite=true);
    }

    function escapeStr($query)
    {
        $luceneReservedCharacters = preg_quote('+-&|!(){}[]^"~*?:\\');
        $query = preg_replace_callback(
            '/([' . $luceneReservedCharacters . '])/',
            function($matches) {
                return '\\' . $matches[0];
            },
            $query);
        return $query;
    }
}