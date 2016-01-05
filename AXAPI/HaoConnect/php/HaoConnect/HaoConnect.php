<?php
/**
 * HaoConnect，
 * @package HaoConnect
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(__dir__.'/HaoHttpClient.php');
require_once(__dir__.'/HaoConfig.php');
require_once(__dir__.'/HaoUtility.php');
require_once(__dir__.'/HaoResult.php');

define('METHOD_GET','get');
define('METHOD_POST','post');

class HaoConnect {

    Public static $Clientinfo         = null;                           //应用信息
    Public static $Clientversion      = null;                           //使用本类所在客户端的版本号
    Public static $apiHost            = null;                           //接口域名
    Public static $SECRET_HAX_CONNECT = null;                           //加密秘钥，这里用的是2号设备类型的密钥


    Public static $Isdebug       = '0';                                  //是否打印调试信息

    Public static $Devicetype    = '2';                                  //设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
    Public static $Devicetoken   = '0';                                  //推送用的设备token

    Public static $Requesttime   = '0';                                  //请求时的时间戳，单位：秒

    Public static $Userid        = '';                                  //当前用户ID，登录后可获得。
    Public static $Logintime     = '';                                  //登录时间，时间戳，单位：秒，数据来自服务器
    Public static $Checkcode     = '';                                  //Userid和Logintime组合加密后的产物，用于进行用户信息加密。数据来自服务器

    /**
     *  请求加密后的校验串，服务器会使用同样规则加密请求后，比较校验串是否一致，从而防止请求内容被纂改。
     *  取头信息里Clientversion,Devicetype,Requesttime,Devicetoken,Userid,Logintime,Checkcode,Clientinfo,Isdebug  和 表单数据
     *  每个都使用key=value（空则空字符串）格式组合成字符串然后放入同一个数组
     *  再放入请求地址（去除http://和末尾/?#之后的字符）后
     *  并放入私钥字符串后自然排序
     *  连接为字符串后进行MD5加密，获得Signature
     *  将Signature也放入头信息，进行传输。
     */
    Public static $Signature     = '0';//接口加密校验

    /** 更新用户信息（一般是从cookie中取） */
    public static function setCurrentUserInfo($userID='',$loginTime='',$checkCode='')
    {
        static::$Userid    = $userID;
        static::$Logintime = $loginTime;
        static::$Checkcode = $checkCode;
        //todo 存到cookie里
        HaoUtility::storeCookie('Userid',$userID);
        HaoUtility::storeCookie('Logintime',$loginTime);
        HaoUtility::storeCookie('Checkcode',$checkCode);
    }

    /** 更新用户的设备信息 */
    public static function setCurrentDeviceToken($deviceToken='')
    {
        static::$Devicetoken = $deviceToken;
        HaoUtility::storeCookie('Devicetoken',$deviceToken);
    }

    /** 根据链接、参数、头信息，取得加密后的密钥 */
    protected static function getSignature($actionUrl='',$params = array(),$headers = array())
    {
        //定义一个空的数组
        $tmpArr = array();

        if ($params == null)
        {
            $params = array();
        }

        //将所有头信息和数据组合成字符串格式：%s=%s，存入上面的数组
        foreach (array('Clientversion','Devicetype','Devicetoken','Requesttime','Userid','Logintime','Checkcode') as $_key) {
            if (array_key_exists($_key,$headers))
            {
                array_push($tmpArr, sprintf('%s=%s', $_key, $headers[$_key]));
            }
            else
            {
                throw new Exception('缺少头信息：'.$_key);
            }
        }

        if (abs($headers['Requesttime'] - time()) > 5*60 )//300
        {
            throw new Exception('请求的时间不正确，请检查网络或重试。');
        }

        //加密版本2.0，支持应用识别码和debug模式
        if (!isset($params['r']))
        {
            foreach (array('Clientinfo','Isdebug') as $_key) {
                if (array_key_exists($_key,$headers))
                {
                    array_push($tmpArr, sprintf('%s=%s', $_key, $headers[$_key]));
                }
                else
                {
                    throw new Exception('缺少加密方案2.0头信息：'.$_key);
                }
            }

            array_push($tmpArr, sprintf('%s=%s', 'link', preg_replace ('/^http.*?:\/\/(.*?)(\/*[\?#].*$|[\?#].*$|\/*$)/', '$1', $actionUrl)));
        }

        //同样的，将所有表单数据也组成字符串后，放入数组。（注：file类型不包含）
        foreach ($params as $_key => $_value) {
            if (!is_array($_value) && !is_object($_value))
            {
                array_push($tmpArr, sprintf('%s=%s', $_key, $_value));
            }
        }

        //最后，将一串约定好的密钥字符串也放入数组。（不同的项目甚至不同的版本中，可以使用不同的密钥）
        array_push($tmpArr, static::$SECRET_HAX_CONNECT);

        //对数组进行自然排序
        sort($tmpArr, SORT_STRING);

        //将排序后的数组组合成字符串
        $tmpStr = implode( $tmpArr );

        //对这个字符串进行MD5加密，即可获得Signature
        return md5( $tmpStr );
    }

    protected static function getSecretHeaders($urlParam,$params = array())
    {
        if (static::$Userid == '' && isset($_COOKIE['Userid']))
        {
            static::$Userid    = $_COOKIE['Userid'];
            static::$Logintime = $_COOKIE['Logintime'];
            static::$Checkcode = $_COOKIE['Checkcode'];
        }
        if (static::$Devicetoken == '' && isset($_COOKIE['Devicetoken']))
        {
            static::$Devicetoken = $_COOKIE['Devicetoken'];
        }

        if (static::$Clientinfo == null)
        {
            static::$Clientinfo              = HaoConfig::$Clientinfo;
            static::$Clientversion           = HaoConfig::$Clientversion;
            static::$SECRET_HAX_CONNECT      = HaoConfig::$SECRET_HAX_CONNECT;
            static::$apiHost                 = HaoConfig::$apiHost;
        }


        $headers = array();
        $headers['Clientinfo']    = static::$Clientinfo ;
        $headers['Clientversion'] = static::$Clientversion ;
        $headers['apiHost']       = static::$apiHost ;
        $headers['Isdebug']       = static::$Isdebug ;
        $headers['Devicetype']    = static::$Devicetype ;
        $headers['Devicetoken']   = static::$Devicetoken ;
        $headers['Requesttime']   = time();
        $headers['Userid']        = static::$Userid ;
        $headers['Logintime']     = static::$Logintime ;
        $headers['Checkcode']     = static::$Checkcode ;

        $actionUrl = sprintf('http://%s/%s',static::$apiHost , $urlParam);

        $headers['Signature']       = static::getSignature($actionUrl,$params,$headers);

        /** 作为转发服务器，也要转发用户的IP，声明自己的代理行为 */
        if ($headers['Clientversion']==2 && isset($_SERVER))
        {
            $headers['X_FORWARDED_FOR'] = $_SERVER['REMOTE_ADDR'];
            $headers['CLIENT_IP']       = $_SERVER['REMOTE_ADDR'];
        }

        return $headers;
    }

    /** 请求API地址，获得字符串 */
    public static function loadContent($urlParam, $params = array(), $method = METHOD_GET)
    {
    	/** 更新下用户信息（如果用户信息尚未更新的话） */

    	$headers = static::getSecretHeaders($urlParam,$params);

        $actionUrl = sprintf('http://%s/%s',static::$apiHost , $urlParam);

    	return HaoHttpClient::loadContent($actionUrl,$params,$method,$headers);
    }

    public static function loadJson($urlParam,  $params = array(),$method = METHOD_GET)
    {
        $content = static::loadContent($urlParam,$params,$method);
        try {
            return json_decode($content,true);
        } catch (Exception $e) {
            return $e;
        }
        return $content;
    }

    /**
     * 发起请求并返回HaoResult
     * @param  string $urlParam 接口方法
     * @param  array  $params   参数字典
     * @param  string $method   请求方式
     * @return HaoResult        返回结果
     */
    public static function request($urlParam,  $params = array(),$method = METHOD_GET)
    {
    	$content = static::loadContent($urlParam,$params,$method);
    	try {
            $tmpResult = json_decode($content,true);
            if ( isset($tmpResult['modelType']) && $tmpResult['modelType'] == 'HaoResult' )
            {
        		return HaoResult::instanceModel($tmpResult['results'],$tmpResult['errorCode'],$tmpResult['errorStr'],$tmpResult['extraInfo'],$tmpResult['resultCount']);
            }
    	} catch (Exception $e) {
            return HaoResult::instanceModel($content,-1,'数据解析失败，请联系管理员。',null);
        }
        return HaoResult::instanceModel($content,-1,'未解析到正确的数据，请联系管理员。',null);
    }

    /**
     * 发起GET请求并返回HaoResult
     * @param  string $urlParam 接口方法
     * @param  array  $params   参数字典
     * @return HaoResult        返回结果
     */
    public static function get($urlParam,  $params = array())
    {
        return static::request($urlParam,  $params, METHOD_GET);
    }


    /**
     * 发起POST请求并返回HaoResult
     * @param  string $urlParam 接口方法
     * @param  array  $params   参数字典
     * @return HaoResult        返回结果
     */
    public static function post($urlParam,  $params = array())
    {
        return static::request($urlParam,  $params, METHOD_POST);
    }

}

