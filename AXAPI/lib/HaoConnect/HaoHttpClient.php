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
    public static function loadContent($pUrl, $pMethod='get', $pPostData=null, $pHeader=null, $pTimeout=30, $pResult='body'){
        $_curl = curl_init();
        if (isset($pMethod)) {
            if (strcasecmp($pMethod,'post')==0)
            {
                curl_setopt($_curl, CURLOPT_POST, true);
                if (isset($pPostData)) {
                    curl_setopt($_curl, CURLOPT_POSTFIELDS, $pPostData);
                }
            }
            else
            {
                if (!is_null($pPostData))
                {
                    $_params = $pPostData;
                    if (is_array($pPostData))
                    {
                        $_params = array();
                        foreach ($pPostData as $key => $value) {
                                $_v = ($value===true)?'1':(($value===false)?'0':rawurlencode($value));
                                array_push($_params, sprintf('%s=%s', $key, $_v));
                        }
                        $_params = implode('&',$_params);
                    }
                    $pUrl .= strpos($pUrl,'?')===false?'?':'&';
                    $pUrl .= $_params;
                }
            }
        }
        curl_setopt($_curl, CURLOPT_URL,            $pUrl);
        curl_setopt($_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($_curl, CURLOPT_TIMEOUT,        30);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);

        if (isset($pHeader)) {
            if ( is_array($pHeader) && (array_keys($pHeader) !== array_keys(array_keys($pHeader))) )
            {//将字典型的$pHeader转换成字符串组成的数组。
                $_headerData = array();
                foreach ($pHeader as $key => $value) {
                    if (!is_int($key))
                    {
                        $_headerData[] = sprintf('%s:%s', $key, $value);
                    }
                    else
                    {
                        $_headerData[] = $value;
                    }
                }
                $pHeader = $_headerData;
            }
            curl_setopt($_curl, CURLOPT_HTTPHEADER, $pHeader);
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
            print("\n");var_export($pUrl);
            print("\n");var_export($pHeader);
            print("\n");var_export($pPostData);
            print("\n");var_export($_result);
        }

        return $_result;
    }

    /**
     * 发送http请求, 返回Json数组
     * @param string url
     * @return array Json 数组
     */
    public static function loadJson($pUrl, $pMethod='get', $pPostData=null, $pHeader=null, $pTimeout=30, $pResult='body'){
        $_c = static::loadContent($pUrl, $pMethod, $pPostData, $pHeader, $pTimeout, $pResult);
        return isset($_c)?json_decode($_c, true):$_c;
    }

    /** 将头信息拆分成字典 */
    public static function getHeaderList($pHeader=array())
    {
        if (is_string($pHeader))
        {
            $pHeader = explode("\r",$pHeader);
        }
        if ( is_array($pHeader) )
        {
            if (array_keys($pHeader) !== array_keys(array_keys($pHeader)))
            {//已经是字典了
                return $pHeader;
            }
            else
            {//将数组转化字典
                $_headerData = array();
                foreach ($pHeader as $key => $value) {
                    if (is_string($key) && is_string($value))
                    {
                        $_headerData[$key] = $value;
                    }
                    else if (is_int($key) && is_string($value) && strpos($value,':')!==false)
                    {
                        list($_key,$_value) = explode(':',$value,2);
                        $_headerData[$_key] = $_value;
                    }
                    else
                    {
                        throw new Exception('无法处理的头信息'.serialize($pHeader));
                    }
                }
                return $_headerData;
            }
        }
        return array();
    }

    /** 将参数信息拆分成字典 */
    public static function getDataList($pData=array(),$pLink='')
    {
        if (is_string($pData))
        {
            $pData = explode('&',$pData);
        }
        if ( is_array($pData) )
        {
            if (strpos($pLink,'?')!=false)
            {
                $parsedInfo = parse_url($pLink);
                $pData = array_merge($pData,explode('&',$parsedInfo['query']));
            }

            if (array_keys($pData) !== array_keys(array_keys($pData)))
            {//已经是字典了
                return $pData;
            }
            else
            {//将数组转化字典
                $_headerData = array();
                foreach ($pData as $key => $value) {
                    if (is_string($key) && is_string($value))
                    {
                        $_headerData[$key] = $value;
                    }
                    else if (is_int($key) && is_string($value) && strpos($value,'=')!==false)
                    {
                        list($_key,$_value) = explode('=',$value,2);
                        $_headerData[$_key] = $_value;
                    }
                    else
                    {
                        throw new Exception('无法处理的参数信息'.serialize($pData));
                    }
                }
                return $_headerData;
            }
        }
        return array();
    }

}
