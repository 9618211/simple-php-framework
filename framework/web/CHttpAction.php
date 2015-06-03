<?php
require_once FRAMEWORK_ROOT.'/CAction.php';
require_once FRAMEWORK_ROOT.'/web/CHttpSession.php';
require_once FRAMEWORK_ROOT.'/web/CHttpCookie.php';
class CHttpAction extends CAction
{
    public $_viewfile;
    public $_viewdata;
    public $_config = array(
        'template' => 'template1.php', //模板
        'resulttype' => 'default', //返回模式
        'module' => 1, //模块
		'urlWhiteList' => 1, //开启url白名单
		'csrfTokenValid' => 1, //开启csrf token验证
	);

	public $_ret = array('http_code' => 200, 'code' => 0, 'data' => array(), 'msg' => 'success');

	public $_tips = array('type' => 'error', 'str' => '出错了!');

	public $_seo = array('title' => '', 'keywords' => '', 'description' => '');

	public $csrfToken = null; //csrf token

	private $_csrfTokenName = '';//csrf token name

	/**
	 * @var CHttpSession
	 */
	protected $_session = null; //session class
	/**
	 * @var CHttpCookie
	 */
	protected $_cookie = null; //cookie class
    function execute(){}

    function __construct()
    {
		$this->_session = new CHttpSession();
		$this->_session->start(); //session start
		$this->_cookie = new CHttpCookie();
		$this->_csrfTokenName = $this->_actionName.'_anti_csrf_token';
		$this->_inJectParams(); //http 参数注入
		$saveModuleConfig = CFactory::loadConfig('safeModuleConfig');
		if ($this->_config['csrfTokenValid'] && $this->getRequestType() != 'GET'){//csrf token验证， get请求不需处理
			if (isset($saveModuleConfig['csrfTokenValid']) && $saveModuleConfig['csrfTokenValid'] == 0){ //绕过csrf token验证

			}else {
				$this->_validateCsrfToken();
			}
		}
    }

    function response()
    {
        $resMethod = 'response'.$this->_config['resulttype'];
        $this->$resMethod();
    }

    /**
     * 默认响应， 视图文件格式
     */
    function responseDefault()
    {
        if (null == $this->_viewfile){
            $this->_viewfile = $this->getDefaultViewFile();
        }

        if($this->_config['template'] == null)
        {
            include_once APP_ROOT.'/view/'.$this->_viewfile;
        }
        else
        {
            if (is_array($this->_viewfile)){
                $viewfiles = array();
                foreach ($this->_viewfile as $etk => $etp){
                    $viewfiles[$etk] = APP_ROOT.'/view/'.$etp;
                }
            }else{
                $viewfiles = APP_ROOT.'/view/'.$this->_viewfile;
            }
            include_once APP_ROOT.'/view/_template/'.$this->_config['template'];
            $this->includeview($viewfiles);
        }
    }

    /**
     * 返回json
     */
    function responseJson()
    {
        // do not cache
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Cache-Control: no-cache, must-revalidate,post-check=0, pre-check=0"); // HTTP/1.1
        header("Content-Type: application/x-javascript; charset=UTF-8");
        echo json_encode($this->_ret);
    }

    /**
     * 返回jsonp
     */
    function responseJsonp()
    {
        if (!isset($this->_ret['callback'])){
            $this->_ret['callback'] = 'callback';
        }
        // do not cache
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        header("Cache-Control: no-cache, must-revalidate,post-check=0, pre-check=0"); // HTTP/1.1
        header("Content-Type: application/x-javascript; charset=UTF-8");
        echo $this->_ret['callback'].'('.json_encode($this->_ret).')';
    }

    /**
     * 跳转
     */
    function responseRedirect()
    {
		if ($this->_config['urlWhiteList']){
			$urlWhiteList = CFactory::loadConfig('urlWhiteList','whiteList/url.php');
			if (!in_array($this->_ret,$urlWhiteList)){
				throw new Exception('redirect url not allowd');
			}
		}
        header('Location:'.$this->_ret);
    }

    function responseNotFound()
    {

    }

    function responseExpire()
    {

    }

    private function getDefaultViewFile()
    {
        $path = explode('_', get_class($this));
        return $path[0].'/'.$path[1].'.php';
    }

    /**
     * @param $viewfiles
     */
    private function includeview($viewfiles)
    {
        if( is_array($viewfiles) )
        {
            foreach( $viewfiles as $ef )
            {
                include($ef);
            }
        }
        else
            include($viewfiles);
    }

    /**
     * @param $templatefile
     * @param $addfile
     * @param string $type
     * @return array
     */
    private function includeviewmod($templatefile,$addfile,$type='unshift')  //$type='unshift' | 'end'
    {
        if( is_string($templatefile) )
            $templatefile = array($templatefile);
        if( $type=='unshift' )
            array_unshift($templatefile,$addfile);
        else
        {
            $templatefile[] = $addfile;
        }
        return $templatefile;
    }

    /**
     * @param int $status
     * @param string $body
     * @param string $content_type
     */
    private function _sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        // set the status
        $status_header = 'HTTP/1.1 ' . $status . ' ' . $this->_getStatusCodeMessage($status);
        header($status_header);
        // and the content type
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if ($body != '') {
            // send the body
            echo $body;
        } // we need to create the body if none is passed
        else {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch ($status) {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on
            // (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templated in a real-world solution
            $body = '
            <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                <title>' . $status . ' ' . $this->_getStatusCodeMessage($status) . '</title>
            </head>
            <body>
                <h1>' . $this->_getStatusCodeMessage($status) . '</h1>
                <p>' . $message . '</p>
                <hr />
                <address>' . $signature . '</address>
            </body>
            </html>';
            echo $body;
        }
    }

    /**
     * 状态吗===消息映射表
     * @param $code
     * @return string
     */
    private function _getStatusCodeMessage($code)
    {
        $messages = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
        return isset($messages[$code])?$messages[$code]:'';
    }

	/**
	 * 注入action参数----
	 * 只注入通过action类的公有成员变量
	 */
	private function _injectParams()
	{
		//HTTP请求参数处理-----只需支持get，post，不支持其他http1.1 put delete方法
		$params = array_merge($_GET, $_POST);
		foreach ($this as $k => $v){
			if(substr($k,0,1) != '_'){ //_ ,'__'等内部成员变量不能通过外部注入
				if(isset($params[$k])){
					$this->$k = $params[$k]; //转义
				}
			}
		}
		$this->destoryRequestParams();
	}

	/**
	 * 删除http request的参数
	 */
	private function destoryRequestParams()
	{
		unset($_GET);
		unset($_POST);
		unset($_REQUEST);
	}

	/**
	 *
	 */
	private function safeHtml($v)
	{
		$format = '';
		$format  = filterXss($v);
		return $format;
	}

	/**
	 * 生成一个csrf token
	 */
	private function _generateCsrfToken()
	{
		//生成一个csrf token
		$this->_session->add($this->_csrfTokenName, sha1(uniqid(mt_rand(),true)));
	}

	/**
	 * @return mixed
	 */
	public function getCsrfToken()
	{
		$csrfSessionToken = $this->_session->getSession($this->_csrfTokenName);
		if (!empty($csrfSessionToken)){

		}else {
			$this->_generateCsrfToken();
		}
		return $csrfSessionToken;
	}

	/**
	 * 验证csrf token, 验证成功后删除
	 */
	private function _validateCsrfToken()
	{
		$valid = false;
		$csrfSessionToken = $this->_session->getSession($this->_csrfTokenName);
		if (!empty($this->csrfToken) && !empty($csrfSessionToken) && $this->csrfToken == $csrfSessionToken){
			if ($this->csrfToken === $csrfSessionToken){
				$valid = true;
			}
		}
		if (!$valid){
			throw new CException(array('asdfasfasf'),'');
		}
	}

	/**
	 * Returns the request type, such as GET, POST, HEAD, PUT, PATCH, DELETE.
	 * Request type can be manually set in POST requests with a parameter named _method. Useful
	 * for RESTful request from older browsers which do not support PUT, PATCH or DELETE
	 * natively (available since version 1.1.11).
	 * @return string request type, such as GET, POST, HEAD, PUT, PATCH, DELETE.
	 */
	public function getRequestType()
	{
		if(isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])){
			return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
		}
		return strtoupper(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'GET');
	}
}