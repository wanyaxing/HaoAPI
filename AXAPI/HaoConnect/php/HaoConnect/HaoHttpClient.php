<?php
/**
 * 网络请求处理类
 * @package HaoConnect
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class HaoHttpClient {

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
    public static function loadContent($actionUrl, $params=null, $method = null, $headers=null){
        $pTimeout=30;
        $pResult='body';
        $_curl = curl_init();
        if ($method === null)
        {
            $method =  ($params == null)?METHOD_GET:METHOD_POST;
        }
        if (isset($method)) {
            if (strcasecmp($method,METHOD_POST)==0)
            {
                curl_setopt($_curl, CURLOPT_POST, true);
                if (isset($params)) {
                    curl_setopt($_curl, CURLOPT_POSTFIELDS, $params);
                }
            }
            else
            {
                if (!is_null($params))
                {
                    $_params = $params;
                    if (is_array($params))
                    {
                        $_params = array();
                        foreach ($params as $key => $value) {
                                $_v = ($value===true)?'1':(($value===false)?'0':rawurlencode($value));
                                array_push($_params, sprintf('%s=%s', $key, $_v));
                        }
                        $_params = implode('&',$_params);
                    }
                    $actionUrl .= strpos($actionUrl,'?')===false?'?':'&';
                    $actionUrl .= $_params;
                }
            }
        }
        curl_setopt($_curl, CURLOPT_URL,            $actionUrl);
        curl_setopt($_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($_curl, CURLOPT_TIMEOUT,        30);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);

        if (isset($headers)) {
            if ( is_array($headers) && (array_keys($headers) !== array_keys(array_keys($headers))) )
            {//将字典型的$headers转换成字符串组成的数组。
                $_headerData = array();
                foreach ($headers as $key => $value) {
                    if (!is_int($key))
                    {
                        $_headerData[] = sprintf('%s:%s', $key, $value);
                    }
                    else
                    {
                        $_headerData[] = $value;
                    }
                }
                $headers = array(implode("\n",$_headerData));
            }
            curl_setopt($_curl, CURLOPT_HTTPHEADER, $headers);
        }
        if (isset($pTimeout)) {
            curl_setopt($_curl, CURLOPT_TIMEOUT, $pTimeout);
        }
        if (isset($pResult)) {
            if ($pResult=='all') {
                curl_setopt($_curl, CURLOPT_HEADER, true);
            } else if ($pResult=='header') {
                curl_setopt($_curl, CURLOPT_NOBODY, true);
                curl_setopt($_curl, CURLOPT_HEADER, true);
            }
        }

        $_resp = curl_exec($_curl);
        $_result = $_resp;
        if (isset($pResult) && $pResult=='all'){
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
            print("\n");var_export($actionUrl);
            print("\n");var_export($headers);
            print("\n");var_export($params);
            print("\n");var_export($_result);
        }

        return $_result;
    }

    /**
     * 发送http请求, 返回Json数组
     * @param string url
     * @return array Json 数组
     */
    public static function loadJson($actionUrl,  $params=null,$method=null, $headers=null, $pTimeout=30, $pResult='body'){
        $_c = static::loadContent($actionUrl, $params, $method, $headers, $pTimeout, $pResult);
        return isset($_c)?json_decode($_c, true):$_c;
    }


}
