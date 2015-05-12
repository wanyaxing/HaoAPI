<?php
/**
 * 推送处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(__dir__.'/../lib/Baidu-Push-SDK/Channel.class.php');

class W2PUSH {
	/**
	 * 百度推送API_KEY //TODO
	 * @var string
	 */
	public static $API_KEY = 'xxx';
	/**
	 * 百度推送SECRET_KEY  //todo
	 * @var string
	 */
	public static $SECRET_KEY = 'xxx';
	/**
	 * 推送模式（iOS）,1是开发模式  2是正式环境
	 * @var integer
	 */
	public static $DEPLOY_STATUS = 1;

    /**
     * 推送接口
     * @param  int     $push_type       1单个设备 2部分人（*常用） 3所有人
     * @param  int     $device_type 设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
     * @param  string  $title        标题（仅安卓）
     * @param  string  $content      留言正文
     * @param  int     $customtype   自定义类型,t
     * @param  string  $customvalue  自定义值,v
     * @param  string  $p_buserid 用户推送ID，百度里是buserid
     * @param  string  $tag_name     指定标签
     * @return array                 results
     */
    public static function pushMessage($push_type ,$device_type , $title='', $content,$customtype=null,$customvalue = null ,$p_buserid=null ,$tag_name=null)
    {
    	$message_keys = uniqid(); //函数基于以微秒计的当前时间，生成一个唯一的 ID。
		$channel = new Channel(W2PUSH::$API_KEY, W2PUSH::$SECRET_KEY);
		if (isset($tag_name))
		{
	    	$optional[Channel::TAG_NAME] = $tag_name;
		}
    	$optional[Channel::MESSAGE_TYPE] = 1; //通知
    	$optional[Channel::DEPLOY_STATUS] = static::$DEPLOY_STATUS ;//1 测试状态  2生产状态
    	switch($push_type)
    	{
    		case 1:
    			if ($p_buserid===null)
    			{
    				return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的用户推送ID');
    			}
    			$optional[Channel::USER_ID] = $p_buserid;
    		case 2:
    			$optional[Channel::DEVICE_TYPE] = $device_type;
    			if ($device_type==4) //IOS 推送
    			{
    				$messages = '{'
    								.'"aps":{'
		    								.'"alert":"'.$content.'",'
		    								.'"sound":"",'
		    								.'"badge":1'
	    									.'}';
    				if (isset($customtype,$customvalue))
    				{
	    				$messages .= ',"t":'.intval($customtype)
		    						.',"v":"'.$customvalue.'"';
    				}
    				$messages .='}';
    			}
    			else if ($device_type==3) //安卓推送
    			{
					$messages = '{'
									.'"title": "'.$title.'",'
									.'"description": "'.$content.'",'
									.'"notification_builder_id": 0,'
									.'"notification_basic_style": 7,'
									.'"open_type": 2,'
									.'"net_support" : 1,'
									.'"pkg_content" : ",'
									.'"custom_content": {"t":'.intval($customtype).',"v":"'.$customvalue.'"}'
								.'}';
    			}
    			else
    			{
    				return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请传入正确的设备类型，iOS 还是 安卓');
    			}
    			$ret = $channel->pushMessage($push_type, $messages, $message_keys, $optional);
    			break;
    		case 3:
    			$ret = $channel->pushMessage($push_type, $messages, $message_keys);
    			break;
    		default:
    			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'push_type 1:单个人 2部分人 3所有人');
    	}
	    if ( false === $ret )
	    {
	    	$ret = array();
	        $ret[] = 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!' ;
	        $ret[] = 'ERROR NUMBER: ' . $channel->errno ( ) ;
	        $ret[] = 'ERROR MESSAGE: ' . $channel->errmsg ( ) ;
	        $ret[] = 'REQUEST ID: ' . $channel->getRequestId ( );
	    }
    	return Utility::getArrayForResults(RUNTIME_CODE_OK,'',array('push_type'=>$push_type, 'messages'=>$messages, 'message_keys'=>$message_keys, 'optional'=>$optional,'result'=>$ret));
    }


	/**
	 * 设定用户的推送时间，使用TAG来标记，默认0-24 意味着全天可接受
     * @param  string  $p_buserid 用户推送ID，百度里是buserid
	 * @param bool $ispushallowed 是否接受推送
	 * @param integer $pushhourstart 推送开始时间0-23
	 * @param integer $pushhourend   推送结束时间1-24
	 */
    public static function setTagWithPrefix($p_buserid,$p_tags=array(),$p_prefix=null)
    {
		$rets = array();
		$channel = new Channel(W2PUSH::$API_KEY, W2PUSH::$SECRET_KEY);

		if (isset($p_prefix) && strlen($p_prefix)>0)
		{
			$tagsList = $channel->queryUserTags( $p_buserid );

			$optional[Channel:: USER_ID] = $p_buserid;

			if (is_array($tagsList) && array_key_exists('response_params',$tagsList) && array_key_exists('tags',$tagsList['response_params']))
			{
				foreach ($tagsList['response_params']['tags'] as $tag) {
					if (strpos($tag['name'],$p_prefix)===0)
					{
						$ret = $channel->deleteTag( $tag['name'],$optional);
					    if ( false === $ret )
					    {
					        $rets[] = array( 'WRONG'=> __FUNCTION__ . ' ERROR!!!!'
									        ,'ERROR NUMBER'=> $channel->errno ( )
									        ,'ERROR MESSAGE'=> $channel->errmsg ( )
									        ,'REQUEST ID'=> $channel->getRequestId ( )
									       );
					    }
					    else
					    {
					        $rets[] = array( 'SUCC'=> __FUNCTION__ . ' OK!!!!!'
									        ,'result'=> print_r ( $ret, true )
									        );
					    }
					}
				}
			}
		}

		$countSucc = 0;
		$countWrong = 0;
		if (isset($p_tags))
		{
			$p_tags = is_array($p_tags)?$p_tags:explode(',',$p_tags);
			foreach ($p_tags as $tarName) {
				$ret = $channel->setTag($tarName,$optional);
			    if ( false === $ret )
			    {
			    	$countWrong++;
			        $rets[] = array( 'WRONG'=> __FUNCTION__ . ' ERROR!!!!'
							        ,'ERROR NUMBER'=> $channel->errno ( )
							        ,'ERROR MESSAGE'=> $channel->errmsg ( )
							        ,'REQUEST ID'=> $channel->getRequestId ( )
							       );
			    }
			    else
			    {
			    	$countSucc++;
			        $rets[] = array( 'SUCC'=> __FUNCTION__ . ' OK!!!!!'
							        ,'result'=> print_r ( $ret, true )
							        );
			    }
			}
		}
	    return array('countSucc'=>$countSucc,'countWrong'=>$countWrong,'rets'=>$rets);
    }

	/**
	 * 设定用户的推送时间，使用TAG来标记，默认0-24 意味着全天可接受
     * @param  string  $p_buserid 用户推送ID，百度里是buserid
	 * @param bool $ispushallowed 是否接受推送
	 * @param integer $pushhourstart 推送开始时间0-23
	 * @param integer $pushhourend   推送结束时间1-24
	 */
    public static function setTagWithPushHour($p_buserid,$ispushallowed=1,$pushhourstart=0,$pushhourend=24)
    {
		$p_tags = null;
		if ($ispushallowed)
		{
			$p_tags = array();
			for ($i=$start; $i <= $end; $i++) {
				$p_tags[] = $i;
			}
		}
    	return W2PUSH::setTagWithPrefix($p_buserid,$p_tags,'t');
    }

	/**
	 * 设定用户的版本号
     * @param  string  $p_buserid 用户推送ID，百度里是buserid
	 * @param string $p_cilentVersion 版本号
	 */
    public static function setTagWithCilentVersion($p_buserid,$p_cilentVersion=null)
    {
    	return W2PUSH::setTagWithPrefix($p_buserid,$p_cilentVersion,'v');
    }


}
