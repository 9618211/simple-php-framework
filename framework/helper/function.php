<?php
/**
 * 一些框架公用的方法START
 */

/**
 * firephp调试用
 * @param $info
 * @param string $key
 * @param string $showp
 * @param array $options
 * @return mixed
 */
function debug($info,$key = 'Debug_Default_Key',$showp='log', $options=array())
{
	if (ENVIRONMENT == 'production')
		return false;
    require_once dirname(__FILE__) . '/../lib3rd/FirePHP.class.php';
    return FirePHP::getInstance(true)->$showp($info, $key, $options);
}

/**
 * @param $data
 */
function vdump($data)
{
	header('Content-type:text/html;charset=utf8');
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

/**
 * 是否是ajax请求
 * only work for jquery post & get
 * @return bool
 */
function isAJaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

/**
 * 分页函数
 * @param $total
 * @param $currentpage
 * @param string $urlprefix
 * @param string $urlendfix
 * @param int $offset
 * @param $descprefix
 * @param $descendfix
 * @return string
 */
function paginition($total, $currentpage,$urlprefix = '',$urlendfix = '',$offset=10,$descprefix='前一页',$descendfix='后一页')
{
    $pagihtml = '';
    if ($currentpage == 1){ //前一页
        $pagihtml .= '<li class="disabled"><a href="#">&lt;'.$descprefix.'</a></li>';
    } else {
        $pagihtml .= '<li><a href="'.$urlprefix.($currentpage-1).$urlendfix.'">&lt;'.$descprefix.'</a></li>';
    }
    if ($currentpage > ($offset /2) && $currentpage < $total - ($offset /2)){
        $leftcount = (int) ($offset / 2);
    } elseif($currentpage >= $total - ($offset /2)){
        $leftcount = (int) ($offset / 2) + ($currentpage - ($total - ($offset /2))) -1;
    }
    else if($currentpage <= ($offset /2)){
        $leftcount = $currentpage - 1;
    }
    $rightcount = ($offset - $leftcount -1);
    for ($i = $leftcount; $i >=1;$i--){ //left offset
        if ($currentpage -$i >0)
            $pagihtml .= '<li><a href="'.$urlprefix.($currentpage-$i).$urlendfix.'">'.($currentpage-$i).'</a></li>';
    }
    $pagihtml .= '<li class="active"><a href="'.$urlprefix.($currentpage).$urlendfix.'">'.$currentpage.'</a></li>';
    for ($i=1; $i <= $rightcount;$i++){ //right offset
        if ($currentpage+$i <= $total)
            $pagihtml .= '<li><a href="'.$urlprefix.($currentpage+$i).$urlendfix.'">'.($currentpage+$i).'</a></li>';
    }
    if ($currentpage == $total){ //后一页
        $pagihtml .= '<li class="disabled"><a href="#">'.$descendfix.'&gt;</a></li>';
    } else {
        $pagihtml .= '<li><a href="'.$urlprefix.($currentpage+1).$urlendfix.'">'.$descendfix.'&gt;</a></li>';
    }
    return $pagihtml;
}

/**
 * smtp 发送邮件
 * @param $to
 * @param $toname
 * @param $subject
 * @param $body
 * @return array
 */
function sendmail($to, $toname, $subject, $body)
{
    require_once dirname(__FILE__) . '/../lib3rd/phpmailer.class.php';
    if (!$body) {
        $body = '<div style="background: #1A76B7;padding: 2px;"><div style="color: #ffffff;padding: 3px;font-weight: bold;">' . $subject . '</div><div style="background: #E8EEF7;padding: 10px;line-height:26px;">' . $body . '</div></div>';
    }
    // set mailer to use SMTP
    $smtpmail = array(
        'username' => 'lujiansheng@shinezone.com',
        'password' => 'LJS1318##',
        'mailserver' => 'ssl://smtp.gmail.com',
        'mailport' => 465,
        'fromname' => 'dht.stormbro.com(勿回复)',
    );

    $mail = new PHPMailer();

    $mail->IsSMTP();
    $mail->From = $smtpmail['username'];
    $mail->FromName = $smtpmail['fromname'];

    $mail->Username = $smtpmail['username'];
    $mail->Password = $smtpmail['password'];
    $mail->Host = $smtpmail['mailserver'];
    $mail->Port = $smtpmail['mailport'];
    if (is_array($to)) {
        foreach ($to as $eto) {
            $mail->addAddress($eto, $toname);
        }
    } else
        $mail->addAddress($to, $toname);
    $mail->WordWrap = 50;
    $mail->IsHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    if (!$mail->Send()) {
        return array('success' => 0, 'error' => $mail->ErrorInfo);
    } else {
        return array('success' => 1);
    }
}

/**
 * 返回一个带压缩的js文件，并且指定资源域名的script src
 * @return string
 */
function paramJS( )
{
	$domain = RES_DOMAIN . '/';
	$str = "";
	foreach( func_get_args() as $val )
	{
		$str .= '<script src="' . $domain . $val . '?v='.STATIC_VERSION.'" charset="utf-8"></script>' . "\n";
	}
	return $str;
}

/**
 * 返回一个带压缩的css文件，并且指定资源域名的rel href
 * @return string
 */
function paramCSS()
{
	$domain = RES_DOMAIN . '/';
	$str = "";
	foreach( func_get_args() as $val )
	{
		$str .= '<link rel="stylesheet" href="' . ($domain . $val.'?v='.STATIC_VERSION) . '"/>' . "\n";
	}
	return $str;
}

/**
 * get the client ip
 * @return string
 */
function get_client_ip() {
	$ipaddress = '';
	if (getenv('HTTP_CLIENT_IP'))
		$ipaddress = getenv('HTTP_CLIENT_IP');
	else if(getenv('HTTP_X_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
else if(getenv('HTTP_X_FORWARDED'))
		$ipaddress = getenv('HTTP_X_FORWARDED');
	else if(getenv('HTTP_FORWARDED_FOR'))
		$ipaddress = getenv('HTTP_FORWARDED_FOR');
	else if(getenv('HTTP_FORWARDED'))
		$ipaddress = getenv('HTTP_FORWARDED');
	else if(getenv('REMOTE_ADDR'))
		$ipaddress = getenv('REMOTE_ADDR');
	else
		$ipaddress = 'UNKNOWN';
	return $ipaddress;
}

/**
 * get geoinfo by ip
 * @return bool
 */
function getGeoInfo()
{
	require_once FRAMEWORK_ROOT.'/helper/CCurlHelper.php';
	$curl = new CCurlHelper("http://ip.taobao.com/service/getIpInfo.php?ip=".get_client_ip());
	$rs = $curl->curlGet();
	$arr = json_decode($rs, true);
	if ($arr['code'] != 0){
		return false;
	}
	return $arr['data'];
}

/**
 * choose lang
 */
function chooseLang($langSupport)
{
	if (!empty($_REQUEST['lang']) && in_array($_REQUEST['lang'],$langSupport)){
		$_SESSION['lang'] = $_REQUEST['lang'];
		setcookie('lang', $_REQUEST['lang'], time()+31536000,'/');
		return $_REQUEST['lang'];
	}
	//get from session
	if (isset($_SESSION['lang']) && in_array($_SESSION['lang'],$langSupport)){
		return $_SESSION['lang'];
	}
	//get from cookie
	if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $langSupport)){
		return $_COOKIE['lang'];
	}
	//get form geoip
	$geoinfo = getGeoInfo();
	if (!$geoinfo || $geoinfo['country_id'] == 'CN'){
		return 'zh_CN';
	}else {
		return 'en_US';
	}
}

/**
 * @param $val
 * xss过滤
 * @return string
 */
function filterXss($val)
{
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
	// this prevents some character re-spacing such as <java\0script>
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters
	// this prevents like <IMG SRC=@avascript:alert('XSS')>
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

		// @ @ search for the hex values
		$val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
		// @ @ 0{0,7} matches '0' zero to seven times
		$val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
	}

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
	return $val;
}

/**
 * 参数过滤
 * 过滤步骤-----把一些预定义的字符转换为 HTML 实体----再加转义符
 * filter params----
 */
function filterParams($params)
{
	if (is_string($params)){
		return get_magic_quotes_gpc()?htmlspecialchars($params):addslashes(htmlspecialchars($params));
	}
	if (empty($params)){
		return $params;
	}
	return is_array($params) ? array_map('filterParams', $params) :(get_magic_quotes_gpc()?htmlspecialchars($params):addslashes(htmlspecialchars($params)));
}

/**
 * 生成一个csrf token
 */
function antiCsrfToken()
{

}

/**
 * END
 */