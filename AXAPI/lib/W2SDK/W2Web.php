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
    public static function loadStringByUrl($p_url, $p_method='get', $p_postData=null, $p_header=null, $p_timeout=30, $p_result='body'){
        /*
        if (isset($p_method) && is_array($p_method)) {
            return W2Web::loadStringByUrlWithArrayOptions($p_url, $p_method);
        }
        */
        $_curl = curl_init();
        if (isset($p_method)) {
            if (strcasecmp($p_method,'post')==0)
            {
                curl_setopt($_curl, CURLOPT_POST, true);
                if (isset($p_postData)) {
                    curl_setopt($_curl, CURLOPT_POSTFIELDS, $p_postData);
                }
            }
            else if (is_array($p_postData))
            {
                $p_url .= strpos($p_url,'?')===false?'?':'&';
                $_params = array();
                foreach ($p_postData as $key => $value) {
                        $_v = ($value===true)?'1':(($value===false)?'0':rawurlencode($value));
                        array_push($_params, sprintf('%s=%s', $key, $_v));
                }
                $p_url .= implode('&',$_params);
            }
        }
        curl_setopt($_curl, CURLOPT_URL,            $p_url);
        curl_setopt($_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($_curl, CURLOPT_TIMEOUT,        30);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);

        if (isset($p_header)) {
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
        return $_result;
    }

    /**
     * 发送http请求, 返回结果
     * @param string url
     * @param array
     * <pre>
     * array(
     *    'header'         => array(),
     *    'method'         => 'post/get',
     *    'post-data'      => array( 'param1'=>'param1', 'file2'=>'@file2.jpg' ),
     *    'timeout'        => 5,
     *    'result'         => 'all/header/body(default)'
     * );
     * </pre>
     * @return array|string 根据设置参数里的result返回结果, 默认是body
     */
    /*
    public static function loadStringByUrlWithArrayOptions($p_url, $p_options=null){
        $curl = curl_init();
        $opts = array(
            CURLOPT_URL             => $p_url,
            CURLOPT_FOLLOWLOCATION  => 1,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_NOBODY          => 0,
            CURLOPT_HEADER          => 0
        );
        if(isset($p_options)){
            if(array_key_exists('header', $p_options)){
                $opts[CURLOPT_HTTPHEADER] = $p_options['header'];
            }
            if(array_key_exists('method', $p_options) && strcasecmp($p_options['method'], 'post')==0){
                $opts[CURLOPT_POST] = 1;
                if(array_key_exists('post-data', $p_options)){
                    $opts[CURLOPT_POSTFIELDS] = $p_options['post-data'];
                }
            }
            if(array_key_exists('timeout', $p_options)){
                $opts[CURLOPT_TIMEOUT] = $p_options['timeout'];
            }
            if(array_key_exists('result', $p_options)){
                if($p_options['result']=='all'){
                    $opts[CURLOPT_HEADER] = 1;
                } else if($p_options['result']=='header'){
                    $opts[CURLOPT_NOBODY] = 1;
                    $opts[CURLOPT_HEADER] = 1;
                }
            }
        }

        curl_setopt_array($curl, $opts);
        $resp = curl_exec($curl);
        $result = $resp;

        if($p_options!=null && array_key_exists('result', $p_options) && $p_options['result']=='all'){
            $info = curl_getinfo($curl);
            $headerSize = $info['header_size'];
            $result = array(
                'header'=>trim(substr($resp,0,$headerSize)),
                'body'=>trim(substr($resp,$headerSize))
            );
        }
        curl_close($curl);
        return $result;
    }
    */

    /**
     * 发送http请求, 返回Json数组
     * @param string url
     * @return array Json 数组
     */
    public static function loadJsonByUrl($p_url){
        $_c = W2Web::loadStringByUrl($p_url);
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
}


/**
 * unit test
 */
/*
if(array_key_exists('argv', $GLOBALS) && realpath($argv[0]) == __file__){
    var_dump(W2Web::loadStringByUrl('http://api.appjk.com/1.php', 'post', 'a=1,2,3,4,5', array("client:appjk-web-server")));
}
*/



?>
