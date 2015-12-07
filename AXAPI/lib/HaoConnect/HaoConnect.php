<?php
/**
 * HaoConnect，
 * @package HaoConnect
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(__dir__.'/HaoConfig.php');

class HaoConnect extends HaoConfig {

    Public static $Isdebug       = '0';                             //是否打印调试信息

    Public static $Devicetype    = '2';                             //设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
    Public static $Devicetoken   = '0';                             //推送用的设备token

    Public static $Requesttime   = '0';                             //请求时的时间戳，单位：秒

    Public static $Userid        = '0';                             //当前用户ID，登录后可获得。
    Public static $Logintime     = '0';                             //登录时间，时间戳，单位：秒，数据来自服务器
    Public static $Checkcode     = '0';                             //Userid和Logintime组合加密后的产物，用于进行用户信息加密。数据来自服务器
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
    public static function setCurrentUserInfo($Userid='0',$Logintime='0',$Checkcode='0')
    {
        static::$Userid    = $Userid;
        static::$Logintime = $Logintime;
        static::$Checkcode = $Checkcode;
    }

    public static function updateCurrentUserInfo()
    {
    	if (static::$Userid == '0')
    	{
    		if (isset($_COOKIE['Userid']))
    		{
    			static::setCurrentUserInfo($_COOKIE['Userid'],$_COOKIE['Logintime'],$_COOKIE['Checkcode']);
    		}
    	}
    }

    /** 根据链接、参数、头信息，取得加密后的密钥 */
    public static function getSignature($pLink='',$pPostData=array(),$pHeader = array())
    {
        $pHeader = HaoHttpClient::getHeaderList($pHeader);
        $pPostData = HaoHttpClient::getDataList($pPostData,$pLink);
        //定义一个空的数组
        $tmpArr = array();

        //将所有头信息和数据组合成字符串格式：%s=%s，存入上面的数组
        foreach (array('Clientversion','Devicetype','Devicetoken','Requesttime','Userid','Logintime','Checkcode') as $_key) {
            if (array_key_exists($_key,$pHeader))
            {
                array_push($tmpArr, sprintf('%s=%s', $_key, $pHeader[$_key]));
            }
            else
            {
                throw new Exception('缺少头信息：'.$_key);
            }
        }

        if (abs($pHeader['Requesttime'] - time()) > 5*60 )//300
        {
            throw new Exception('请求的时间不正确，请检查网络或重试。');
        }

        //加密版本2.0，支持应用识别码和debug模式
        if (!isset($pPostData['r']))
        {
            foreach (array('Clientinfo','Isdebug') as $_key) {
                if (array_key_exists($_key,$pHeader))
                {
                    array_push($tmpArr, sprintf('%s=%s', $_key, $pHeader[$_key]));
                }
                else
                {
                    throw new Exception('缺少加密方案2.0头信息：'.$_key);
                }
            }

            array_push($tmpArr, sprintf('%s=%s', 'link', preg_replace ('/^http.*?:\/\/(.*?)(\/*[\?#].*$|[\?#].*$|\/*$)/', '$1', $pLink)));
        }

        //同样的，将所有表单数据也组成字符串后，放入数组。（注：file类型不包含）
        foreach ($pPostData as $_key => $_value) {
            array_push($tmpArr, sprintf('%s=%s', $_key, $_value));
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

    /** 请求API地址，获得字符串 */
    public static function loadContent($pControllerAction, $pMethod='get', $pPostData=array(), $pHeader=array())
    {
    	/** 更新下用户信息（如果用户信息尚未更新的话） */
    	static::updateCurrentUserInfo();

    	$pHeader = HaoHttpClient::getHeaderList($pHeader);
    	$pHeader['Clientinfo']    = static::$Clientinfo ;
    	$pHeader['Clientversion'] = static::$Clientversion ;
    	$pHeader['apiHost']       = static::$apiHost ;
    	$pHeader['Isdebug']       = static::$Isdebug ;
    	$pHeader['Devicetype']    = static::$Devicetype ;
    	$pHeader['Devicetoken']   = static::$Devicetoken ;
    	$pHeader['Requesttime']   = time();
    	$pHeader['Userid']        = static::$Userid ;
    	$pHeader['Logintime']     = static::$Logintime ;
    	$pHeader['Checkcode']     = static::$Checkcode ;

    	$pLink = sprintf('http://%s/%s',static::$apiHost , $pControllerAction);

    	$pHeader['Signature'] 		= static::getSignature($pLink,$pPostData,$pHeader);

    	/** 作为转发服务器，也要转发用户的IP，声明自己的代理行为 */
    	if ($pHeader['Clientversion']==2 && isset($_SERVER))
    	{
	    	$pHeader['X_FORWARDED_FOR'] = $_SERVER['REMOTE_ADDR'];
	    	$pHeader['CLIENT_IP']       = $_SERVER['REMOTE_ADDR'];
    	}

    	return HaoHttpClient::loadContent($pLink,$pMethod,$pPostData,$pHeader);
    }

    /** 返回数组 */
    public static function loadJson($pControllerAction, $pMethod='get', $pPostData=array(), $pHeader=array())
    {
    	$content = static::loadContent($pControllerAction,$pMethod,$pPostData,$pHeader);
    	try {
    		return json_decode($content,true);
    	} catch (Exception $e) {
    	}
		return $content;
    }

    /** get请求数据（返回数组） */
    public static function get($pControllerAction, $pPostData=array(), $pHeader=array())
    {
    	$pMethod='get';
    	return static::loadJson($pControllerAction,$pMethod,$pPostData,$pHeader);
    }

    /** post请求数据（返回数组） */
    public static function post($pControllerAction, $pPostData=array(), $pHeader=array())
    {
    	$pMethod='post';
    	return static::loadJson($pControllerAction,$pMethod,$pPostData,$pHeader);
    }
}
