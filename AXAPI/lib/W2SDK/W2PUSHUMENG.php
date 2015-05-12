<?php
/**
 * 推送处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/UmengNotification.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/android/AndroidBroadcast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/android/AndroidFilecast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/android/AndroidGroupcast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/android/AndroidUnicast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/android/AndroidCustomizedcast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/ios/IOSBroadcast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/ios/IOSFilecast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/ios/IOSGroupcast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/ios/IOSUnicast.php');
// require_once(dirname(__FILE__) . '/../lib/' . 'umeng-notification/ios/IOSCustomizedcast.php');
class W2PUSH extends UmengNotification{
	/**
	 * API_KEY //TODO
	 * @var string
	 */
	public static $API_KEY = W2Config::$API_KEY;
	/**
	 * SECRET_KEY  //todo
	 * @var string
	 */
	public static $SECRET_KEY = W2Config::$SECRET_KEY;
	/**
	 * 推送模式（iOS）,1是开发模式  2是正式环境
	 * @var integer
	 */
	public static $DEPLOY_STATUS = 1;


	public function setData($data)
	{
		$this->data = $data;
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}
	function setPredefinedKeyValue($key, $value) {
		return $this;
	}

	//return file_id if SUCCESS, else throw Exception with details.
	function uploadContents($content) {
		if ($this->data["appkey"] == NULL)
			throw new Exception("appkey should not be NULL!");
		if ($this->data["timestamp"] == NULL)
			throw new Exception("timestamp should not be NULL!");
		if (!is_string($content))
			throw new Exception("content should be a string!");

		$post = array("appkey"           => $this->data["appkey"],
					  "timestamp"        => $this->data["timestamp"],
					  "content"          => $content
					  );
		$url = $this->host . $this->uploadPath;
		$postBody = json_encode($post);
		$sign = md5("POST" . $url . $postBody . $this->appMasterSecret);
		$url = $url . "?sign=" . $sign;
		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        // print($result . "\r\n");
        if ($httpCode == "0") //time out
        	throw new Exception("Curl error number:" . $curlErrNo . " , Curl error details:" . $curlErr . "\r\n");
        else if ($httpCode != "200") //we did send the notifition out and got a non-200 response
        	throw new Exception("http code:" . $httpCode . " details:" . $result . "\r\n");
        $returnData = json_decode($result, TRUE);
        if ($returnData["ret"] == "FAIL")
        	throw new Exception("Failed to upload file, details:" . $result . "\r\n");
        else
        	$this->data["file_id"] = $returnData["data"]["file_id"];
	}

	function getFileId() {
		if (array_key_exists("file_id", $this->data))
			return $this->data["file_id"];
		return NULL;
	}
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
    	$notification = new W2PUSH();
    	$notification->setAppMasterSecret(W2PUSH::$SECRET_KEY);

    	$params = $notification->getData();
    	$params['appkey'] = W2PUSH::$API_KEY;
    	$params['timestamp'] = strval(time());
		if ($device_type==4) //IOS 推送
		{
			$params['payload'] = array('aps'=>array('alert'=>$content));
			if (isset($customtype,$customvalue))
			{
				$params['payload']['t']= intval($customtype);
				$params['payload']['v']= $customvalue;
			}
		}
		else if ($device_type==3) //安卓推送
		{
			if ($title==''){$title=$content;}
			$params['payload'] = array();
			$params['payload']['display_type'] = 'notification';
			$params['payload']['body'] = array();
			$params['payload']['body']['ticker'] = $title;
			$params['payload']['body']['title'] = $title;
			$params['payload']['body']['text'] = $content;
			$params['payload']['body']['after_open'] = 'go_app';
			if (isset($customtype,$customvalue))
			{
				$params['payload']['extra'] = array();
				$params['payload']['extra']['t']= intval($customtype);
				$params['payload']['extra']['v']= $customvalue;
			}
		}
		else
		{
			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的设备类型，iOS 还是 安卓');
		}
		$params['production_mode']= static::$DEPLOY_STATUS==2;//是否正式环境

    	switch($push_type)
    	{
    		case 1:
    			$p_deviceToken = is_array($p_deviceToken)?$p_deviceToken:explode(',',$p_deviceToken);
    			if (count($p_deviceToken)==0 || (count($p_deviceToken)==1 && $p_deviceToken[0]==null))
    			{
	    			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的用户推送ID');
    			}
    			else if (count($p_deviceToken)<500)
    			{
	    			if (count($p_deviceToken)>1)
	    			{
		    			$params['type'] = 'listcast';
	    			}
	    			else
	    			{
		    			$params['type'] = 'unicast';
	    			}
	    			$params['device_tokens'] = implode(',',$p_deviceToken);
    			}
    			else
    			{
    				$params['type'] = 'filecast';
    				$notification->uploadContents(implode("\n",$p_deviceToken));
    			}
    			break;
    		case 2:
    			$params['type'] = 'groupcast';
    			$params['filter'] = array(
											'where' => 	array(
												    		'and' 	=>  array()
												   		)
									  	);
    			if ($tag_name!=null)
    			{
	    			$tag_name = is_array($tag_name)?$tag_name:explode(',',$tag_name);
	    			foreach ($tag_name as $tag) {
	    				$params['filter']['where']['and'][] = array('tag'=>$tag);
	    			}
    			}
    			break;
    		default:
    			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'push_type 1:单个人 2部分人 3所有人');
    	}
    	$notification->setData($params);
    	try {
	    	$ret = $notification->send();
    	} catch (Exception $e) {
    		$ret = 'Caught exception: ' . $e->getMessage();
    	}
    	return Utility::getArrayForResults(RUNTIME_CODE_OK,'',array('push_type'=>$push_type, 'messages'=>$params['payload'], 'message_keys'=>$params['timestamp'], 'optional'=>$params,'result'=>$ret));
    }

}
