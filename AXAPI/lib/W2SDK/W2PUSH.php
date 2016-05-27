<?php
/**
 * 信鸽推送处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(dirname(__FILE__) . '/../Xg-Push-SDK-PHP/XingeApp.php');

class W2PUSH {

	public static $API_KEY_IOS        = null;
	public static $SECRET_KEY_IOS     = null;
	public static $API_KEY_ANDROID    = null;
	public static $SECRET_KEY_ANDROID = null;

	/**
	 * 推送模式（iOS）,1是开发模式  2是正式环境
	 * @var integer
	 */
	public static $DEPLOY_STATUS = 1;


	/**
	 * 获得推送对象
	 * @param  int $device_type  3安卓  4iOS
	 * @return XingeApp
	 */
	public static function getPush($device_type)
	{
		$push = null;
		if ($device_type==4) //IOS 推送
		{
			if (static::$API_KEY_IOS==null && defined('W2PUSH_API_KEY_IOS'))
			{
				static::$API_KEY_IOS    = W2PUSH_API_KEY_IOS;
				static::$SECRET_KEY_IOS = W2PUSH_SECRET_KEY_IOS;
			}
			$push = new XingeApp(static::$API_KEY_IOS, static::$SECRET_KEY_IOS);
		}
		else if ($device_type==3) //安卓推送
		{
			if (static::$API_KEY_IOS==null && defined('W2PUSH_API_KEY_IOS'))
			{
				static::$API_KEY_ANDROID    = W2PUSH_API_KEY_ANDROID;
				static::$SECRET_KEY_ANDROID = W2PUSH_SECRET_KEY_ANDROID;
			}
			$push = new XingeApp(static::$API_KEY_ANDROID, static::$SECRET_KEY_ANDROID);
		}
		if (function_exists('AX_DEBUG'))
		{
			AX_DEBUG($push);
		}
		// if ($push == null)
		// {
		// 	throw new Exception('推送对象获取失败，无法创建推送任务。');
		// }
		return $push;
	}

	/**
	 * 查询token的信息 (信鸽独有)
	 */
	public static function QueryInfoOfToken($p_deviceToken,$device_type)
	{
		$push = static::getPush($device_type);
		if (!isset($push)){return false;}
		return $push->QueryInfoOfToken($p_deviceToken);
	}

	/**
	 * 查询token的tag (信鸽独有)
	 */
	public static function queryTokenTags($p_deviceToken,$device_type)
	{
		$push = static::getPush($device_type);
		if (!isset($push)){return false;}
		// var_export($device_type);
		$ret = $push->QueryTokenTags($p_deviceToken);
		if (is_array($ret) && array_key_exists('result', $ret)&& array_key_exists('tags', $ret['result']))
		{
			return $ret['result']['tags'];
		}
		return null;
	}

	/**
	 * 以keyvalue的组合方式来重设tag，（先删除已有的keyvalue)
     * @param  string  $p_deviceTokens 用户推送ID，百度里是buserid，支持数组或逗号隔开的字符串
     * @param  int   $device_type 设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
	 * @param string $tag_key        key (指定前缀，如设定sex_1,sex_2，则此处key为sex，values为1,2)
	 * @param string $tag_values     value值，若为空，则删除所有key数据
	 */
	public static function BatchSetTagValue($p_deviceTokens,$device_type,$tag_key,$tag_values)
	{
		$newTagnames = array();
		$addTagnames = array();
		$delTagnames = array();
		$ret = array();
		$tag_values = is_array($tag_values)?$tag_values:explode(',',$tag_values);
		foreach ($tag_values as $tag_value) {
			if ($tag_value!=null)
			{
				$newTagnames[] = $tag_key . $tag_value;
			}
		}
		$p_deviceTokens = is_array($p_deviceTokens)?$p_deviceTokens:explode(',',$p_deviceTokens);
		foreach ($p_deviceTokens as $p_deviceToken) {
			$tagnames = static::queryTokenTags($p_deviceToken,$device_type);
			if (is_array($tagnames))
			{
				$addTagnames = array_diff($newTagnames,$tagnames);
				foreach ($tagnames as $tagname) {
					if (strpos($tagname,$tag_key)===0 && !in_array($tagname,$newTagnames))
					{
						$delTagnames[] = $tagname;
					}
				}
			}
			$ret[] = static::BatchAddTag($p_deviceToken,$device_type,$addTagnames);
			$ret[] = static::BatchDelTag($p_deviceToken,$device_type,$delTagnames);
		}
		return $ret;
	}


	/**
	 * 批量为token添加标签 (信鸽独有)
	 */
	public static function BatchAddTag($p_deviceTokens,$device_type,$tag_names)
	{
		$push = static::getPush($device_type);
		if (!isset($push)){return false;}
		$pairs = array();
		$p_deviceTokens = is_array($p_deviceTokens)?$p_deviceTokens:explode(',',$p_deviceTokens);
		$tag_names      = is_array($tag_names)?$tag_names:explode(',',$tag_names);
		foreach ($p_deviceTokens as $p_deviceToken) {
			if ($p_deviceToken != null)
			{
				foreach ($tag_names as $tag_name) {
					if ($tag_name!=null)
					{
						array_push($pairs,new TagTokenPair($tag_name,$p_deviceToken));
					}
				}
			}
		}
		$ret = array();
		$maxCount = 20;
		for ($i=0; $i < count($pairs) ; $i+= $maxCount)
		{
			$ret[] = array(
						'action'=>'BatchSetTag(add)'
						,'ret'=>$push->BatchSetTag(array_slice($p_deviceTokens,$i, $maxCount))
						);
		}
		return $ret;
	}


	/**
	 * 批量为token删除标签 (信鸽独有)
	 */
	public static function BatchDelTag($p_deviceTokens,$device_type,$tag_names)
	{
		$push = static::getPush($device_type);
		if (!isset($push)){return false;}
		$pairs = array();
		$p_deviceTokens = is_array($p_deviceTokens)?$p_deviceTokens:explode(',',$p_deviceTokens);
		$tag_names      = is_array($tag_names)?$tag_names:explode(',',$tag_names);
		foreach ($p_deviceTokens as $p_deviceToken) {
			if ($p_deviceToken != null)
			{
				foreach ($tag_names as $tag_name) {
					if ($tag_name!=null)
					{
						array_push($pairs,new TagTokenPair($tag_name,$p_deviceToken));
					}
				}
			}
		}
		$ret = array();
		$maxCount = 20;
		for ($i=0; $i < count($pairs) ; $i+= $maxCount)
		{
			$ret[] = array(
						'action'=>'BatchDelTag(del)'
						,'ret'=>$push->BatchDelTag(array_slice($p_deviceTokens,$i, $maxCount))
						);
		}
		return $ret;
	}

    /**
     * 推送接口
     * @param  int     $push_type       1单个设备 2部分人（*常用）
     * @param  int     $device_type 设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
     * @param  string  $title        标题（仅安卓）
     * @param  string  $content      留言正文
     * @param  int     $customtype   自定义类型,t
     * @param  string  $customvalue  自定义值,v
     * @param  string  $p_deviceTokens 用户推送ID，百度里是buserid
     * @param  string  $tag_name     指定标签
     * @param  int     $deploy_status     1是开发模式  2是正式环境
     * @return array                 results
     */
    public static function pushMessage($push_type ,$device_type , $title='', $content,$customtype=null,$customvalue = null ,$p_deviceTokens=null ,$tag_name=null,$deploy_status=2,$isSound=true,$isShake=true)
    {
		$push = null;
		$mess = null;

		/** @var XingeApp */
		$push = static::getPush($device_type);
		if (!isset($push)){return false;}
		if ($device_type==4) //IOS 推送
		{
			$mess = new MessageIOS();
			$mess->setExpireTime(86400);
			//$mess->setSendTime("2014-03-13 16:00:00");
			$mess->setAlert($content);
			//$mess->setAlert(array('key1'=>'value1'));
			$mess->setBadge(0);
			$mess->setSound($isSound?'default':'');
			if (isset($customtype,$customvalue))
			{
				$custom = array('t'=>intval($customtype), 'v'=>$customvalue);
				$mess->setCustom($custom);
			}
		}
		else if ($device_type==3) //安卓推送
		{
			$mess = new Message();
			if ($title==''){$title=$content;}
			$mess->setType(Message::TYPE_NOTIFICATION);
			$mess->setTitle($title);
			$mess->setContent($content);
			$mess->setExpireTime(86400);
			//$style = new Style(0);
			#含义：样式编号0，响铃，震动，不可从通知栏清除，不影响先前通知
			$style = new Style(0,($isSound?1:0),($isShake?1:0),0,0);
			$mess->setStyle($style);

			$action = new ClickAction();
			$action->setActionType(ClickAction::TYPE_ACTIVITY);
			$action->setActivity(' ');//扯淡的xinge sdk，isValid()方法判断m_activity默认值有点问题
			$mess->setAction($action);

			if (isset($customtype,$customvalue))
			{
				$custom = array('t'=>intval($customtype), 'v'=>$customvalue);
				$mess->setCustom($custom);
			}
		}
		else
		{
			return false;
		}
		$params['production_mode']= $deploy_status==2;//是否正式环境

		$ret = array();
    	switch($push_type)
    	{
    		case 1://指定token
    			$p_deviceTokens = is_array($p_deviceTokens)?$p_deviceTokens:explode(',',$p_deviceTokens);
    			if (count($p_deviceTokens)==0 || (count($p_deviceTokens)==1 && $p_deviceTokens[0]==null))
    			{
    				throw new Exception('请传入正确的用户推送token');
	    			return false;
    			}
    			if (count($p_deviceTokens)>5)//设备多的话，就用大批量推送
    			{
    				$retMulti = $push->CreateMultipush($mess,$device_type==3?0:($deploy_status==2? XingeApp::IOSENV_PROD : XingeApp::IOSENV_DEV));
					$ret[] = array(
								'action'=>'CreateMultipush'
								,'ret'=>$retMulti
								);
    				if (is_array($retMulti) && array_key_exists('result',$retMulti) && array_key_exists('push_id',$retMulti['result']) )
    				{
						$maxCount = 1000;//每次最大传输设备量
						for ($i=0; $i < count($p_deviceTokens) ; $i+= $maxCount)
						{
							$ret[] = array(
	    								'action'=>'PushDeviceListMultiple'
	    								,'token'=>$p_deviceTokens
	    								,'ret'=>$push->PushDeviceListMultiple($retMulti['result']['push_id'], array_slice($p_deviceTokens,$i, $maxCount))
	    								);
						}
    				}

    			}
    			else//设备少的话，就单独推送吧
    			{
	    			foreach ($p_deviceTokens as $token) {
	    				if ($device_type==4) //IOS 推送
	    				{
	    					$ret[] = array(
	    								'action'=>'PushSingleDevice'
	    								,'token'=>$token
	    								,'device_type'=>$device_type
	    								,'ret'=>$push->PushSingleDevice($token, $mess,$deploy_status==2? XingeApp::IOSENV_PROD : XingeApp::IOSENV_DEV)
										,'deploy_status'=>$deploy_status
	    								);
	    				}
						else if ($device_type==3) //安卓推送
						{

							$ret[] = array(
										'action'=>'PushSingleDevice'
										,'token'=>$token
										,'device_type'=>$device_type
										,'ret'=>$push->PushSingleDevice($token, $mess)
										);
						}
	    			}
    			}

    			break;
    		case 2://指定设备群发
				if ($device_type==4) //IOS 推送
				{
					$ret[] = array(
								'action'=>'PushAllDevices'
								,'token'=>'0'
								,'device_type'=>$device_type
								,'ret'=>$push->PushAllDevices(0, $mess,$deploy_status==2? XingeApp::IOSENV_PROD : XingeApp::IOSENV_DEV)
								,'deploy_status'=>$deploy_status
							);
				}
				else if ($device_type==3) //安卓推送
				{
					$ret[] = array(
								'action'=>'PushAllDevices'
								,'token'=>'0'
								,'device_type'=>$device_type
								,'ret'=>$push->PushAllDevices(0, $mess)
							);
				}
    			break;
    		default:
    			throw new Exception('push_type 1:单个人 2部分人 3所有人');
    	}

    	return array(ERROR_CODE::$OK,array('push_type'=>$push_type,'device_type'=>$device_type, 'messages'=>$mess,'result'=>$ret));
    }

}
