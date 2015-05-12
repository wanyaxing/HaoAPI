<?php
/**
 * 信鸽推送处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(dirname(__FILE__) . '/../lib/' . 'Xg-Push-SDK-PHP-1.1.4/XingeApp.php');

class W2PUSH {

	public static $API_KEY_ANDROID    = W2Config::$API_KEY_ANDROID;
	public static $SECRET_KEY_ANDROID = W2Config::$SECRET_KEY_ANDROID;

	public static $API_KEY_IOS        = W2Config::$API_KEY_IOS;
	public static $SECRET_KEY_IOS     = W2Config::$SECRET_KEY_IOS;


	/**
	 * 推送模式（iOS）,1是开发模式  2是正式环境
	 * @var integer
	 */
	public static $DEPLOY_STATUS = 1;


    /**
     * 推送接口
     * @param  int     $push_type       1单个设备 2部分人（*常用）
     * @param  int     $device_type 设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
     * @param  string  $title        标题（仅安卓）
     * @param  string  $content      留言正文
     * @param  int     $customtype   自定义类型,t
     * @param  string  $customvalue  自定义值,v
     * @param  string  $p_deviceToken 用户推送ID，百度里是buserid
     * @param  string  $tag_name     指定标签
     * @return array                 results
     */
    public static function pushMessage($push_type ,$device_type , $title='', $content,$customtype=null,$customvalue = null ,$p_deviceToken=null ,$tag_name=null)
    {
		$push = null;
		$mess = null;

		if ($device_type==4) //IOS 推送
		{
			$push = new XingeApp(W2PUSH::$API_KEY_IOS, W2PUSH::$SECRET_KEY_IOS);
			$mess = new MessageIOS();
			$mess->setExpireTime(86400);
			//$mess->setSendTime("2014-03-13 16:00:00");
			$mess->setAlert($content);
			//$mess->setAlert(array('key1'=>'value1'));
			$mess->setBadge(0);
			$mess->setSound('');
			if (isset($customtype,$customvalue))
			{
				$custom = array('t'=>intval($customtype), 'v'=>$customvalue);
				$mess->setCustom($custom);
			}
		}
		else if ($device_type==3) //安卓推送
		{
			$push = new XingeApp(W2PUSH::$API_KEY_ANDROID, W2PUSH::$SECRET_KEY_ANDROID);
			$mess = new Message();
			if ($title==''){$title=$content;}
			$mess->setType(Message::TYPE_NOTIFICATION);
			$mess->setTitle($title);
			$mess->setContent($content);
			$mess->setExpireTime(86400);
			//$style = new Style(0);
			#含义：样式编号0，响铃，震动，不可从通知栏清除，不影响先前通知
			$style = new Style(0,1,1,0,0);
			$mess->setStyle($style);

			$action = new ClickAction();
			$action->setActionType(ClickAction::TYPE_ACTIVITY);
			$action->setActivity(' ');//扯淡的xinge sdk有点问题
			$mess->setAction($action);

			if (isset($customtype,$customvalue))
			{
				$custom = array('t'=>intval($customtype), 'v'=>$customvalue);
				$mess->setCustom($custom);
			}
		}
		else
		{
			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的设备类型，iOS 还是 安卓');
		}
		$params['production_mode']= static::$DEPLOY_STATUS==2;//是否正式环境

		$ret = array();
    	switch($push_type)
    	{
    		case 1://指定token
    			$p_deviceToken = is_array($p_deviceToken)?$p_deviceToken:explode(',',$p_deviceToken);
    			if (count($p_deviceToken)==0 || (count($p_deviceToken)==1 && $p_deviceToken[0]==null))
    			{
	    			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的用户推送token');
    			}
    			foreach ($p_deviceToken as $token) {
    				if ($device_type==4) //IOS 推送
    				{
    					$ret[] = array(
    								'action'=>'PushSingleDevice'
    								,'token'=>$token
    								,'ret'=>$push->PushSingleDevice($token, $mess,static::$DEPLOY_STATUS==2? XingeApp::IOSENV_PROD : XingeApp::IOSENV_DEV)
    								);
    				}
					else if ($device_type==3) //安卓推送
					{

						$ret[] = array(
									'action'=>'PushSingleDevice'
									,'token'=>$token
									,'ret'=>$push->PushSingleDevice($token, $mess)
									);
					}
					// var_export($mess);
					// print("\n");
    			}

    			break;
    		case 2://指定设备群发
				if ($device_type==4) //IOS 推送
				{
					$ret[] = array(
								'action'=>'PushAllDevices'
								,'token'=>'0'
								,'ret'=>$push->PushAllDevices(0, $mess,static::$DEPLOY_STATUS==2? XingeApp::IOSENV_PROD : XingeApp::IOSENV_DEV)
							);
				}
				else if ($device_type==3) //安卓推送
				{
					$ret[] = array(
								'action'=>'PushAllDevices'
								,'token'=>'0'
								,'ret'=>$push->PushAllDevices(0, $mess)
							);
				}
    			break;
    		default:
    			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'push_type 1:单个人 2部分人 3所有人');
    	}

    	return Utility::getArrayForResults(RUNTIME_CODE_OK,'',array('push_type'=>$push_type,'device_type'=>$device_type, 'messages'=>$mess,'result'=>$ret));
    }

}
