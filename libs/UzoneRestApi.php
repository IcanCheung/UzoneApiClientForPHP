<?php
/**
 * UC乐园Rest接口 PHP SDK
 *
 * @category   UzoneRestApi
 * @package    libs
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright  Jiuhong Deng
 * @link       http://u.uc.cn/
 * @since      File available since Release 1.0.0
 */
class UzoneRestApi
{
    /**
     * @desc Rest接口的地址
     * @var  String
     */
    public  $restServer = 'http://api.u.uc.cn/restserver.php';
    /**
     * @desc 单点登录的地址
     * @var  String
     */
    public  $ssoServer  = 'http://u.uc.cn/index.php?r=sso/auth';
    //接口实例
    private static $_api        = null;
	/**
	 * @desc 获取单实例对象
	 */
	public static function getInstance($uzone_token='')
	{
		if(self::$_api == null)
		{
			self::$_api = new self($uzone_token);
		}
		if(!empty($uzone_token))
		self::$_api->uzone_token = $uzone_token;
		return self::$_api;
	}
    /**
     * @desc  初始化UC乐园Rest接口
     *
     * @param String $appKey       - 接入分配到的appKey
     * @param String $secret       - 接入分配到的secret
     * @param String $privateKey   - 接入分配到的privateKey
     * @param String $uzone_token  - 进入应用的时候，乐园传入进来的 uzone_token 校验
     */
    public function __construct($uzone_token = '')
    {
        $this->uzone_token = $uzone_token;
        $config = require dirname(__FILE__) . '/../conf/Api.inc.php';
        $this->methodList = $config['methodList'];
        $this->privateKey = $config['privateKey'];
        $this->secret     = $config['secret'];
        $this->appKey     = $config['appKey'];
        $this->restServer = $config['restServer'];
        $this->ssoServer  = $config['ssoServer'];
        $this -> connectTimeOut = $config['connectTimeOut'];//单位秒
        $this -> streamTimeOut = $config['streamTimeOut'];//单位毫秒
        $this->config     = $config;
        $this->effectiveTime = $config['effectiveTime'];
        $this->_init();
    }
    /**
     * @desc 检查是否为授权的用户
     * @return boolean
     */
    public function checkIsAuth()
    {
        return empty($this->uid) ? false : true;
    }
    /**
     * @desc 获取进入应用的用户的uid
     */
    public function getAuthUid()
    {
        return $this->uid;
    }
    /**
     * @desc 该接口可以判断请求的签名，及时间戳是否有效。自2011-08-05开始，乐园支持时间戳及签名验证。
     * 
     * @param $time    int  - utc时间戳
     * @param $sig    string  - 需要校验的签名
     * @return bool           - 如果通过校验，返回true, 不通过则返回false;
     */
    public function checkIsEffective( $time, $sig )
    {
        if (! is_numeric( $time ) || $time <= 0)
        {
            return false;
        }
        $param = array('time' => $time, 'uzone_token' => $this->uzone_token);
        //检查签名有效性
        if (! $this->_verifySig( $sig, $param ))
        {
            return false;
        }
        //检查时间戳有效性，超时则无效
        if (time() - $time >= $this->effectiveTime)
        {
            return false;
        }
        return true;
    }

    /**
     * @desc 统一校验签名
     *
     * @param $sig    string  - 需要校验的签名
     * @param $param    array  - 需要校验的参数，没有默认使用$_GET
     * @return bool           - 如果通过校验，返回true, 不通过则返回false;
     */
    private function _verifySig( $sig, $param = array() )
    {
        if (empty( $param ) || ! is_array( $param ))
        {
            $param = $_GET;
        }
        $sig = isset( $param['sig'] ) ? $param['sig'] : $sig;
        
        if (empty($sig))
        {
            return false;
        }
        
        unset( $param['sig'] );
        
        $appSig = $this->_genSignature($param);
        
        if ($appSig == $sig)
        {
            return true;
        }
        return false;
    }
    /**
     * @desc 检查是否成功执行了
     * @return boolean
     */
    public function checkIsCallSuccess()
    {
        return $this->isCallSuccess;
    }
    public function getCallErrorMsg()
    {
        return $this->callErrorMsg;
    }
    /**
     * @desc  调用接口的方法
     *
     * @param String $method   - 接口的名字
     * @param Array  $param    - 需要用到的http参数
     * @return array(
     *      'status' => 'ok' // 接口状态 ok | error
     *      'code'   => '',  // 接口返回代码
     *      'msg'    => ''.  // 接口返回描述性信息,
     *      'data'   => '',  // 接口返回的数据
     * )
     */
    public function callMethod($method, $param = array(), $postParam = array())
    {
        if (!in_array($method, $this->methodList)){
            #throw new Exception ('unknow method ' . $method);
        }
        return $this->_httpRequest($param, $method, $postParam);
    }
    /**
     * @desc 跳转到单点登录接口
     * @param String  $backUrl  - 单点登录流程完成后，需要跳转返回的地址
     */
    public function redirect2SsoServer($backUrl = '')
    {
        if (empty($this->uid)){
            // 跳转到
            $param = array(
                'appKey'  => $this->appKey,
                'backUrl' => $backUrl,
                'v'       => $this->v
            );
            $sig          = $this->_genSignature($param);
            $param['sig'] = $sig;
            $prefix       = strpos($this->ssoServer,'?') !== false ? '&' : '?';
            $url          = $this->ssoServer . $prefix . http_build_query($param);
            $this->_logger("redirect2SsoServer\t" . $url);
            header('status: 302');
            header('location: ' . $url);
        }
    }
    /**
     * @desc 初始化
     */
    private function _init()
    {
        $this->_parseUzoneToken();
    }
    /**
     * @desc  发送http请求
     * @param array  $param  - http请求参数
     * @param String $method - 请求的方法
     * @return 返回接口的数据
     */
    private function _httpRequest($param, $method, $postParam = array())
    {
    	if($param){
	    	foreach($param as $k=>$v){
	        	if (empty($v)){
	        		unset($param[$k]);
	        	}
	        }
    	}
    	if($postParam){
	    	foreach($postParam as $k=>$v){
	        	if (empty($v)){
	        		unset($postParam[$k]);
	        	}
	        }
    	}
        $param['method'] = $method;
        $url             = $this->_buildRequestUrl($param,$postParam);
        $this->_logger("httpRequest\t" . $url);
        $this->isCallSuccess = false;
        $this->callErrorMsg  = '';
        $res = $this->urlOpen($url,$postParam);
        return $this->_parseResponse($res);
    }
    /**
     * @desc 解析uzone_token
     */
    private function _parseUzoneToken()
    {
        if (empty($this->uzone_token)) return false;
        $this->_logger("parseUzoneToken\tstart\t" . $this->uzone_token);
        $token        = base64_decode($this->uzone_token);
        try {
            openssl_private_decrypt($token, $this->uid, $this->privateKey);
            $this->_logger("parseUzoneToken\tfinish\t" . $this->uid);
        } catch(exception $e){
            $this->_logger("parseUzoneToken\t" . $this->uzone_token . "\t" . $e->getMessage(), 'warn');
            return false;
        }
        return true;
    }
    /**
     * @desc  解析接口返回来的数据
     *
     * @param String $response  - 返回的字符数据
     * @return array   - json decode出来的数组
     */
    private function _parseResponse($response)
    {
		$res =  json_decode($response, true);
		$this->_logger("parseResponse\t" . $response . "\t".$res['msg']);
        if (!$res || $res['status'] != 'ok'){
            // 请求接口失败, 记录log
            $this->_logger("parseResponse\t" . $response . "\t" . $res['msg'], "error");
            $this->callErrorMsg = $response;
        } else {
            $this->isCallSuccess = true;
        }
        return isset($res['data']) ? $res['data'] : null;
    }
    /**
     * @desc  建立请求Url
     * @param Array $param  - 请求的参数
     * @return String       - 请求的url
     */
    private function _buildRequestUrl($param,$postParam = array())
    {
        if (!isset($param['appKey'])) $param['appKey'] = $this->appKey;
        if (!isset($param['v'])) $param['v']           = $this->v;
        if (!isset($param['uzone_token']) && !empty($this->uzone_token)) $param['uzone_token'] = $this->uzone_token;
        $sig          = $this->_genSignature(array_merge($param,$postParam));
        $param['sig'] = $sig;
        $prefix       = strpos($this->restServer,'?') !== false ? '&' : '?';
        $url          = $this->restServer . $prefix . http_build_query($param);
        return $url;
    }
    /**
     * @desc  生成签名
     *
     * @param String $param  - 请求的http参数
     * @return String        - 按照规则生成的sig
     */
    private function _genSignature($param = array())
    {
        ksort($param);
        $str = array();
        foreach($param as $key => $v){
            $str[] = $key . '=' . $v;
        }
        
        $str = implode('', $str);
        // 去掉&
        $str = str_replace('&', '', $str);
        // 后面加 screntKey
        $str = $str . $this->secret;
        // md5
        return md5($str);
    }
    /**
     * @desc  记录log
     * @param string $msg
     * @param string $level
     */
    private function _logger($msg, $level = 'debug')
    {
        if (!$this->config['debug'] && $level == 'debug') return false;
        if (!$this->config['time'] && $level == 'time') return false;
        file_put_contents($this->config['logPath'] . 'uzoneSDK_'. $level . '.' . date('Ymd') . '.log', date("Ymd H:i:s") . "\t" . $msg . "\n", FILE_APPEND);
        return true;
    }
	/*
	 * @desc file方式发送请求，支持get,post方式,支持超时断开功能
	 * @param $url 请求地址及GET参数信息
	 * @param $paramStr POST参数信息
	 * @return content
	 */	
	public function urlOpen($url, $paramStr = '') {
		$open_time_start = microtime(1);
		if($this->config['connectType']=='sock'){
			$result = $this->sock_get_contents($url,$paramStr);
		}elseif($this->config['connectType']=='curl'){
			$result = $this->curl_get_contents($url,$paramStr);
		}else{
			$result = $this->file_get_contents($url,$paramStr);
		}
		$open_time_end = microtime(1);
		$open_time = round($open_time_end - $open_time_start, 3);
		$this->_logger(sprintf( "`open_time=%s`api=%s`", $open_time, $url),'time');
         
		$isM = preg_match ( '/{.*}/', $result, $matchs );
		return ($isM) ? $matchs [0] : $result;
	}
	/*
	 * @desc file方式发送请求，支持get,post方式,支持超时断开功能
	 * @param $url 请求地址及GET参数信息
	 * @param $paramStr POST参数信息
	 * @return content
	 */	
	public function file_get_contents($url, $paramStr = '') {
		if($paramStr)$method = 'POST';
		else $method = 'GET';
		$context ['http'] = array ('method' => $method, 'header' => 'Content-type: application/x-www-form-urlencoded', 'timeout' => $this -> connectTimeOut, 'content' => "" );
		if ($paramStr)$context ['http'] ['content'] = http_build_query ( $paramStr, '', '&' );
		$results = file_get_contents ( ($url), true, stream_context_create ( $context ) );
		return $results;
	}
	/*
	 * @desc curl方式发送请求，支持get,post方式,支持超时断开功能
	 * @param $url 请求地址及GET参数信息
	 * @param $paramStr POST参数信息
	 * @return content
	 */
	public function curl_get_contents($url, $paramStr = ''){
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, $this -> connectTimeOut );
		curl_setopt ( $ch, CURLOPT_USERAGENT, _USERAGENT_ );
		curl_setopt ( $ch, CURLOPT_REFERER, _REFERER_ );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		if($paramStr){
			$encoded = http_build_query ( $paramStr, '', '&' );
			curl_setopt($ch, CURLOPT_POST,count($paramStr)) ;
			curl_setopt($ch, CURLOPT_POSTFIELDS,$encoded) ;
		}
		$results = curl_exec ( $ch );
		curl_close ( $ch );
		return $results;
	 }
	/*
	 * @desc sock方式发送请求，支持get,post方式，支持超时断开功能
	 * @param $url 请求地址及GET参数信息
	 * @param $paramStr POST参数信息
	 * @return content
	 */
	public function sock_get_contents($url, $paramStr = ''){
		$url = parse_url($url);  
	    if (!$url){
	    	return "count not parse url";
	    } 
        $url['port']  = isset($url['port']) ? $url['port'] : 80;
	    if (!isset($url['port']) || !$url['port']) $url['port'] = 80;  
	    if($paramStr){
	    	$method = 'POST';
	    	$encoded = http_build_query ( $paramStr, '', '&' );
	    }else{
	    	$method = 'GET';
	    }
	    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr,$this -> connectTimeOut);  
	    if (!$fp){  
	        return "Failed to open socket ERROR: $errno - $errstr";
	    }

	    fputs($fp, sprintf("{$method} %s?%s HTTP/1.0\n", $url['path'],  $url['query']) );  
	    fputs($fp, "Host: {$url['host']}\n");
	    
	    if($paramStr){
		    fputs($fp, "Content-type: application/x-www-form-urlencoded\n");  
		    fputs($fp, "Content-length: " . strlen($encoded) . "\n");
		    fputs($fp, "Connection: close\n\n");
		    fputs($fp, "$encoded\n");
	    }else{
	    	fputs($fp, "Connection: close\n\n");
	    }
	    
		stream_set_blocking($fp, True);
		stream_set_timeout($fp, 0, ($this->streamTimeOut)*1000);//获取流媒体超时设置
		
	    $results = "";  
	    $inheader = 1;  
	    while(!feof($fp))   
	    {  
	        $line = fgets($fp,1024);  
	        if ($inheader && ($line == "\n" || $line == "\r\n"))   
	        {  
	            $inheader = 0;  
	        }  
	        elseif (!$inheader)   
	        {  
	            $results .= $line;  
	        }
	    	$status = stream_get_meta_data($fp);
			if ($status['timed_out']) {
				$results = "stream_timed_out ERROR: {$this->streamTimeOut} ms";
				break;
			}
	    }  
	    fclose($fp);  
		return $results;
	 }
    private $config;
    private $isCallSuccess = false;
    
    /**
     * @desc 请求链接有效时间，时间单位：s
     * @var  int
     */
    private  $effectiveTime = 1800;
    /**
     * @desc 目前乐园提供的可用的接口
     * @var  array
     */
    private  $methodList = array();
    /**
     * @desc 用户进入应用的时候传入的uzone_token
     * @var  String
     */
    private  $uzone_token;
    /**
     * @desc 授权用户的uid
     * @var  numeric
     */
    private  $uid;
    /**
     * @desc 应用接入乐园的时候分配到的appKey
     * @var  String
     */
    private  $appKey;
    /**
     * @desc 应用接入乐园的时候分配到的secret
     * @var  String
     */
    private  $secret;
    /**
     * @desc 应用接入乐园分配到的 RSA解密的 privateKey
     * @var  String
     */
    private  $privateKey;
    /**
     * @desc 当前接口的版本
     * @var  String
     */
    private  $v = '2.0';
}