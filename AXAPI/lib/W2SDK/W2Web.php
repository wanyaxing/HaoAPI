<?php
/**
 * Web处理函数库文件
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */

class W2Web {

    /**
     * 发送http请求, 返回结果
     * @param string url
     * @param string method get(default)/post
     * @param string post data (a=1&b=2&c=3)
     * @param array request header (array("client:web-server"))
     * @param int timeout default is 30
     * @param string result all/header/body(default)
     * @return array|string 根据设置参数里的result返回结果, 默认是body
     */
    public static function loadStringByUrl($p_url, $p_method='get', $p_postData=null, $p_header=null, $p_timeout=30, $p_result='body',$_curl = null){
        if (is_null($_curl))
        {
            $_curl = curl_init();
        }
        if (isset($p_method)) {
            if (strcasecmp($p_method,'post')==0)
            {
                curl_setopt($_curl, CURLOPT_POST, true);
                if (isset($p_postData)) {
                    curl_setopt($_curl, CURLOPT_POSTFIELDS, $p_postData);
                }
            }
            else
            {
                if (!is_null($p_postData))
                {
                    $_params = $p_postData;
                    if (is_array($p_postData))
                    {
                        $_params = array();
                        foreach ($p_postData as $key => $value) {
                                $_v = ($value===true)?'1':(($value===false)?'0':rawurlencode($value));
                                array_push($_params, sprintf('%s=%s', $key, $_v));
                        }
                        $_params = implode('&',$_params);
                    }
                    $p_url .= strpos($p_url,'?')===false?'?':'&';
                    $p_url .= $_params;
                }
            }
        }
        curl_setopt($_curl, CURLOPT_URL,            $p_url);
        curl_setopt($_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($_curl, CURLOPT_TIMEOUT,        30);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);

        if (isset($p_header)) {
            if (W2Array::isList($p_header))
            {//将字典型的$p_header转换成字符串组成的数组。
                $_headerData = array();
                foreach ($p_header as $key => $value) {
                    if (!is_int($key))
                    {
                        $_headerData[] = sprintf('%s:%s', $key, $value);
                    }
                    else
                    {
                        $_headerData[] = $value;
                    }
                }
                $p_header = array(implode("\n",$_headerData));
            }
            curl_setopt($_curl, CURLOPT_HTTPHEADER, $p_header);
        }
        if (isset($p_timeout)) {
            curl_setopt($_curl, CURLOPT_TIMEOUT, $p_timeout);
        }
        if (isset($p_result)) {
            if ($p_result=='all') {
                curl_setopt($_curl, CURLOPT_HEADER, true);
            } else if ($p_result=='header') {
                curl_setopt($_curl, CURLOPT_NOBODY, true);
                curl_setopt($_curl, CURLOPT_HEADER, true);
            }
        }

        // var_export(curl_getinfo($_curl));

        $_resp = curl_exec($_curl);
        $_result = $_resp;
        if (isset($p_result) && $p_result=='all'){
            $_info = curl_getinfo($_curl);
            $_headerSize = $_info['header_size'];
            $_result = array(
                'header'=>trim(substr($_resp,0,$_headerSize)),
                'body'=>trim(substr($_resp,$_headerSize))
            );
        }
        curl_close($_curl);

        if (function_exists('AX_DEBUG')){AX_DEBUG('curl');}
        if (defined('IS_AX_DEBUG'))
        {
            print("\n");var_export($p_url);
            print("\n");var_export($p_header);
            print("\n");var_export($p_postData);
            print("\n");var_export($_result);
        }

        return $_result;
    }

    /**
     * 发送http请求, 返回Json数组
     * @param string url
     * @return array Json 数组
     */
    public static function loadJsonByUrl($p_url, $p_method='get', $p_postData=null, $p_header=null, $p_timeout=30, $p_result='body',$_curl = null){
        $_c = W2Web::loadStringByUrl($p_url, $p_method, $p_postData, $p_header, $p_timeout, $p_result, $_curl);
        return isset($_c)?json_decode($_c, true):$_c;
    }

    /**
     * 根据网址获得图片，并存储到本地目录
     * @param string url
     * @return array Json 数组
     */
    public static function SaveImageByUrl($p_url,$p_imgFilePath,$p_quality=null){
        $_c = file_get_contents($p_url);
        if (isset($_c) && strlen($_c)>0)
        {
            $_p = dirname($p_imgFilePath);
            if (!is_dir ($_SERVER['DOCUMENT_ROOT'] . $_p))
            {
                @mkdir ($_SERVER['DOCUMENT_ROOT'] .$_p, 0777, true);
            }
            try {
                $imgFileOrignel = $_SERVER['DOCUMENT_ROOT'].$_p.'/'.basename($p_url);
                $imgFileOrignel = str_replace('?', '-', $imgFileOrignel);
                $imgFileOut = $_SERVER['DOCUMENT_ROOT'].$p_imgFilePath;
                // exit($imgFileOut);
                file_put_contents($imgFileOrignel,$_c);
                rename($imgFileOrignel,$imgFileOut);
            // exit($imgFileOrignel);
                if (isset($p_quality))
                {
                    W2Image::copyImage($imgFileOut, $imgFileOut, $p_quality);
                }
                else
                {
                    W2Image::copyImage($imgFileOut, $imgFileOut);
                }
                // unlink($imgFileOrignel);
            } catch (Exception $e) {
                var_export($e);
            }
            // return $_SERVER['DOCUMENT_ROOT'].$p_imgFilePath;
            return $_c;
        }
        return null;
    }

    /**
     * 保存Cookie
     * @param string key
     * @param string value
     * @param int 有效时间（秒），默认至浏览器session进程结束时消失
     * @param string path，默认为/
     */
    public static function storeCookie($p_key, $p_value, $p_time=null, $p_path='/') {
        setcookie($p_key, $p_value, is_int($p_time)?time()+$p_time:0, $p_path);
    }

    /**
     * 删除Cookie
     * @param string key
     * @param string path，默认为/
     */
    public static function removeCookie($p_key, $p_path='/') {
        setcookie($p_key, '', time()-1, $p_path);
    }

    /**
     * 删除所有Cookie
     */
    public static function clearCookie(){
        if(is_array($_COOKIE)){
            foreach ($_COOKIE as $key => $value) {
                W2Web::removeCookie($key);
            }
        }
    }

    /**
     * 读取Cookie
     * @param string key
     * @return string|null value
     */
    public static function loadCookie($p_key){
        return (array_key_exists($p_key, $_COOKIE))?$_COOKIE[$p_key]:null;
    }

    public static function headerStringOfCode($code){
        $messages = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
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

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        );
        return sprintf('HTTP/1.0 %d %s',$code,$messages[$code]);
    }

    //php获取当前访问的完整url地址
    public static function getCurrentUrl(){
        $url='http://';
        if(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'){
            $url='https://';
        }
        if($_SERVER['SERVER_PORT']!='80'){
            $url.=$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
        }else{
            $url.=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }
        return $url;
    }
}
