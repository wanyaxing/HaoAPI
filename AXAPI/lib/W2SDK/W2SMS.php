<?php
/**
 * 短信处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2SMS {
    public static function sendMessage($p_telephone, $p_msg)
    {
    	$_r = null ;
    	if (W2String::isTelephone($p_telephone))
    	{
			// $strSendMsg = $p_msg;
			// $strSendMsg = iconv('UTF-8', 'GBK', $strSendMsg);
			// $strSendMsg = urlencode($strSendMsg);
			// $_r = '测试期，暂不发送实际短信';
			// $strUrl ="http://125.208.9.42:8080/WS/Send.aspx?CorpID=btapp&Pwd=123456&Mobile={$strTelephone}&Content={$strSendMsg}";
			// $_r = W2Web::loadStringByUrl($strUrl);
			$data = array();
			$data['user'] = W2Config::$SMS_USER;
			$data['passwd'] = W2Config::$SMS_PASSWD;
			$data['msg'] = $p_msg;//短消息内容，UTF-8编码
			$data['mobs'] = $p_telephone;//手机号码，逗号分隔，个数最多100
			$data['ts'] = date('YmdHi',time());//计划发送时间,格式“yyyyMMddHHmm”，默认当前
			$data['dtype'] = 0;//响应数据格式;0,普通字串，1.XML格式，默认0

			$data['passwd'] = md5($data['user'].$data['passwd']);//MD532位加密用户名和API密码
			$_r = W2Web::loadStringByUrl('http://api5.nashikuai.cn/SendSms.aspx','post',$data);
    	}
		return $_r;
    }
	 /**
	  * 获取短信余额
	  * @return int 数量
	  */
	 public static function GetBalance()
	 {
			$user = W2Config::$SMS_USER;
			$passwd = W2Config::$SMS_PASSWD;
			$strPasswd = md5($user.$passwd);
			$strUrl = 'http://api5.nashikuai.cn/GetBalance.aspx?user='.$user.'&passwd='.$strPasswd.'&dtype=1';
			$results = file_get_contents($strUrl);
			$results = simplexml_load_string($results);
			if ($results->overqty)
			{
				return $results->overqty;
			}
		return false;
	 }
}
