<?php
/**
 * 自定义的一些方法工具
 * @package conf
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class Utility
{
	/**
	 * cookie加密用字段
	 * @var string
	 */
	protected static $userCookieRandCode     = USER_COOKIE_RANDCODE;

	/**
	 * password加密用字段
	 * @var string
	 */
	protected static $passwordRandCode       = PASSWORD_RANDCODE;

	/**
	 * 静态变量，存储优化后的HEADERS信息
	 * @var array
	 */
	protected static $_HEADERS = null;

	/**
	 * 静态变量，存储当前用户ID
	 * @var array
	 */
	protected static $_CURRENTUSERID = false;

	/**
	 * 将用户和登陆时间组成加密字符
	 * @param  integer $p_userID 用户ID
	 * @param  string  $p_time   时间戳
	 * @return string            加密后字符
	 */
	protected static function getCheckCode($p_userID, $p_time)
	{
		return md5($p_userID.md5($p_time.(static::$userCookieRandCode)));
	}

	/**
	 * 将密码再次加密
	 * @param  string $p_password 原始密码（一般此时已经经过初步MD5加密）
	 * @return string             加密后字符串（用于存储到数据库中）
	 */
    public static function getEncodedPwd($p_password)
    {
    	if (!is_null($p_password))
    	{
    		if (strlen($p_password)!=32)
    		{//如果没有经过md5加密，则此处需要先行md5加密一次
    			$p_password = md5($p_password);
    		}
	    	return md5(md5($p_password).static::$passwordRandCode.substr(md5($p_password),3,8));
    	}
    	return null;
    }


    /**
     * 提取请求中的headers信息，
     * 并复制一份首字母大写其他字母小写的key值，
     * 最后存储到$_HEADERS变量中供使用
     * @return array 优化后的headers信息
     */
	public static function getallheadersUcfirst()
	{
		if (static::$_HEADERS === null)
		{
			static::$_HEADERS = getallheaders();
			foreach (static::$_HEADERS as $key => $value) {
				static::$_HEADERS[ucfirst(strtolower($key))] = $value;
			}
		}
		return static::$_HEADERS;
	}

	public static function getHeaderValue($p_key)
	{
		$_headers = Utility::getallheadersUcfirst();
		$p_key = ucfirst(strtolower($p_key));
		if (array_key_exists($p_key,$_headers))
		{
			return $_headers[$p_key];
		}
		return null;
	}

	public static function getCurrentIP()
	{
		$onlineip = null;
	    if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
	    {
	    	$onlineip = $_SERVER['REMOTE_ADDR'];
	    }
		if ( Utility::getHeaderValue('Devicetype') == DEVICE_TYPE::LINUX )
		{
		    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown'))
		    {
		    	$onlineip = getenv('HTTP_CLIENT_IP');
		    }
		    elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown'))
			{
				$onlineip = getenv('HTTP_X_FORWARDED_FOR');
			}
			elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown'))
		    {
		    	$onlineip = getenv('REMOTE_ADDR');
		    }
		}
		return $onlineip;
	}

	public static function setCurrentUserID($p_userID=null)
	{
		static::$_CURRENTUSERID = $p_userID;
	}

	public static function getCurrentUserID()
	{
		if (static::$_CURRENTUSERID === false )
		{
			$p_userID = null;
			$infoHeader = getallheaders();
			if(Utility::getCheckCode(Utility::getHeaderValue('Userid'),Utility::getHeaderValue('Logintime')) == Utility::getHeaderValue('Checkcode'))
			{
				$p_userID = Utility::getHeaderValue('Userid');
			}
			if ($p_userID>0)
			{
				$_clsHandler = USERHANDLER_NAME;
				$tmpModel =  $_clsHandler::loadModelById($p_userID);
				if (is_object($tmpModel))
				{
					if (method_exists($tmpModel,'getLastPasswordTime'))
					{//通过比较用户的最后一次密码修改时间，来确定当前登录用户是否是密码修改之后登录的用户，如果不是，则拒绝承认。
						if (!is_null($tmpModel->getLastPasswordTime()) && W2Time::getTimeBetweenDateTime(Utility::getHeaderValue('Logintime'),$tmpModel->getLastPasswordTime())<0)
						{
							$tmpModel = null;
							static::setCurrentUserID(false);
							if (method_exists('UserController','actionLogOut'))
							{
								UserController::actionLogOut();
							}
						}
					}
				}
				if (is_object($tmpModel))
				{
		            if (method_exists($tmpModel,'getStatus'))
	                {
				        switch($tmpModel->getStatus())
				        {
				            case STATUS_DRAFT:    //未激活
				                break;
				            case STATUS_PENDING:  //待审禁言
				                break;
				            case STATUS_DISABLED: //封号
				            	$tmpModel = null;
				                break;
				            default:
				                break;
				        }
	                }
				}
				if (is_object($tmpModel))
				{
					if (method_exists($tmpModel,'setLastLoginTime'))
					{
						if (W2Time::getTimeBetweenDateTime($tmpModel->getLastLoginTime())<-60*5)
						{
							$tmpModel->setLastLoginTime(W2Time::timetostr());
							$tmpModel = $_clsHandler::saveModel($tmpModel);
						}
					}
				}
				if (!is_object($tmpModel))
				{
					$p_userID = null;
					static::setCurrentUserID($p_userID);
					throw new Exception("您的登录信息已经失效，请重新登录。",RUNTIME_CODE_ERROR_NOT_USER);
				}
			}
            else
            {
                $p_userID = null;
            }
			static::setCurrentUserID($p_userID);
		}
		return static::$_CURRENTUSERID ;
	}

	public static function getHeaderAuthInfoForUserID($p_userID)
	{
		$p_time = time();
		return array(
				'Userid'=>$p_userID
				,'Logintime'=>$p_time
				,'Checkcode'=>Utility::getCheckCode($p_userID,$p_time)
			);
	}

	public static function getUserByID($p_userID)
	{
		if ($p_userID==0)
		{
			return null;
		}
		$_clsHandler = USERHANDLER_NAME;
		return $_clsHandler::loadModelById($p_userID);
	}

	public static function getLngbaidu()
	{
		return W2HttpRequest::getRequestFloat('lngbaidu');
	}

	public static function getLatbaidu()
	{
		return W2HttpRequest::getRequestFloat('latbaidu');
	}

	/**
	 * [getCurrentUserModel description]
	 * @return UserModel   用户
	 */
	public static function getCurrentUserModel()
	{
		$_clsHandler = USERHANDLER_NAME;
		$tmpModel =  $_clsHandler::loadModelById(Utility::getCurrentUserID());
		return $tmpModel;
	}

	/**
	 * 获得组装后的结果数组
	 * @param  integer $errorCode 错误码，0为正常
	 * @param  string  $errorStr  错误描述
	 * @param  array   $result    返回数据
	 * @param  array   $extraInfo 返回额外数据
	 * @return array             结果数组
	 */
    public static function getArrayForResults($errorCode=0,$errorStr='',$result = array(),$extraInfo=array())
    {
    	return HaoResult::init(is_array($errorCode)?$errorCode:array($errorCode,$errorStr,$errorStr),$result,$extraInfo);
    }

    /**
     * 判断结果数组是否正确获得结果
     * @param  array  $tmpResult 结果数组
     * @return boolean            是否正确获得
     */
    public static function isResults($tmpResult=null)
    {
    	return  (is_object($tmpResult) && get_class($tmpResult)=='HaoResult') || (is_array($tmpResult) && array_key_exists('errorCode',$tmpResult) ) ;
    }

    /**
     * 判断结果数组是否正确获得结果
     * @param  array  $tmpResult 结果数组
     * @return boolean            是否正确获得
     */
    public static function isResultsOK($tmpResult=null)
    {
    	return (Utility::isResults($tmpResult) && ((is_object($tmpResult) && $tmpResult->isResultsOK()) || (is_array($tmpResult) && $tmpResult['errorCode']==RUNTIME_CODE_OK)));
    }

    /**
     * 判断结果数组是否正确获得结果，并取出其中的结果
     * @param  array  $tmpResult 结果数组
     * @return boolean            是否正确获得
     */
    public static function getResults($tmpResult=null)
    {
    	if (Utility::isResultsOK($tmpResult))
    	{
    		return is_object($tmpResult)?$tmpResult->getResults():$tmpResult['results'];
    	}
    	return null;
    }

    /**
     * 将数组（或字典）的key和value组成%s=%s字符串
     * 如果是字典则组成 people[height]=180;（注意：没有引号）
     * 如果是数组则组成 people[] = 180;
     * html前端注意，不要混用people[height]和people[]，会导致后者被转成字典哦。
     * 尽量字典和数组使用不同变量。
     * @param  array $array array
     * @param  string $key   前缀
     * @return array        [height=180,people[sex]=1]
     */
    protected static function getTmpArr($array,$key='')
    {
    	$tmpArr = array();
    	if (is_array($array))
    	{
            $isList = W2Array::isList($array);
    		foreach ($array as $_key => $_value) {
    			$_tmp =  static::getTmpArr(
			    						$_value
			    						,$key!=''
					    					?$key
					    						.( ( $isList || is_array($_value) )
					    						 	?'['.$_key.']'
					    						 	:'[]'
					    						 )
					    					:$_key
			    					);
    			$tmpArr = array_merge($tmpArr,$_tmp);
    		}
    	}
    	else
    	{
    		$tmpArr[] = sprintf('%s=%s', $key, $array);
    	}
    	return $tmpArr;
    }

    /**
     * 对请求进行校验
     * @return HaoResult
     */
    public static function getAuthForApiRequest()
    {
    	$isAuthed = false;

		$_HEADERS = Utility::getallheadersUcfirst();

		if (array_key_exists('Signature', $_HEADERS))
		{
			//定义一个空的数组
			$tmpArr = array();

			//将所有头信息和数据组合成字符串格式：%s=%s，存入上面的数组
			foreach (array('Clientversion','Devicetype','Devicetoken','Requesttime','Userid','Logintime','Checkcode') as $_key) {
				if (array_key_exists($_key,$_HEADERS))
				{
					array_push($tmpArr, sprintf('%s=%s', $_key, $_HEADERS[$_key]));
				}
				else
				{
					return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array('errorContent'=>'缺少头信息：'.$_key));
				}
			}

			if (abs($_HEADERS['Requesttime'] - time()) > 5*60 )//300
			{
				return HaoResult::init(ERROR_CODE::$REQUEST_TIME_OUT);
			}

			//加密版本2.0，支持应用识别码和debug模式
			if (!isset($_REQUEST['r']))
			{
				foreach (array('Clientinfo','Isdebug') as $_key) {
					if (array_key_exists($_key,$_HEADERS))
					{
						array_push($tmpArr, sprintf('%s=%s', $_key, $_HEADERS[$_key]));
					}
					else
					{
						return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array('errorContent'=>'缺少头信息：'.$_key));
					}
				}

				array_push($tmpArr, sprintf('%s=%s%s', 'link', $_SERVER['HTTP_HOST'],preg_replace ("/(\/*[\?#].*$|[\?#].*$|\/*$)/", '', $_SERVER['REQUEST_URI'])));
			}
		    //是否开启debug
		    if (isset($_HEADERS['Isdebug']) && $_HEADERS['Isdebug']=='1')
		    {
		        define('IS_SQL_PRINT',True);
		        define('IS_AX_DEBUG',True);
		    }

			//同样的，将所有表单数据也组成字符串后，放入数组。（注：file类型不包含）
			$tmpArr = array_merge($tmpArr , static::getTmpArr($_REQUEST) );

			//最后，将一串约定好的密钥字符串也放入数组。（不同的项目甚至不同的版本中，可以使用不同的密钥）
			switch ($_HEADERS['Devicetype']) {

				case 1://浏览器设备
					array_push($tmpArr, SECRET_HAX_BROWSER);
					break;
				case 2://pc设备，服务器
					array_push($tmpArr, SECRET_HAX_PC);
					break;
				case 3://安卓
					array_push($tmpArr, SECRET_HAX_ANDROID);
					break;
				case 4://iOS
					array_push($tmpArr, SECRET_HAX_IOS);
					break;
				case 5://WP
					array_push($tmpArr, SECRET_HAX_WINDOWS);
					break;

				default:
					array_push($tmpArr, SECRET_HAX_PC);
					break;
			}

			//对数组进行自然排序
			sort($tmpArr, SORT_STRING);

			//将排序后的数组组合成字符串
			$tmpStr = implode( $tmpArr );

			//对这个字符串进行MD5加密，即可获得Signature
			$tmpStr = md5( $tmpStr );

			$isAuthed = true;//默认验证通过

			//如果不通过，则返回调试信息。
			if( $tmpStr != $_HEADERS['Signature'] ){
				$isAuthed = array(
					'status'=>false,
					'tmpArr'=>$tmpArr,
					'tmpArrString'=>implode( $tmpArr ),
					'tmpArrMd5'=>$tmpStr,
					'getallheaders()'=>getallheaders(),
					'_GET'=>$_GET,
					'_POST'=>$_POST,
					'_FILES'=>$_FILES,
					'_SERVER'=>$_SERVER,
					);
			}

		}
		else if (false)
		{
			$isAuthed = true;
		}
		else
		{
			return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array('errorContent'=>'缺少头信息：'.'signature'));
		}
		if ($isAuthed === true)
		{
			return HaoResult::init(ERROR_CODE::$OK,$isAuthed);
		}
		else
		{
			return HaoResult::init(ERROR_CODE::$SIGNATURE_WRONG);
		}

    }

    /** PHP5.4以上使用JSON_UNESCAPED_UNICODE编码json字符，否则只能自己实现了。 */
    public static function json_encode_unicode($data) {
	    if (defined('JSON_UNESCAPED_UNICODE')) {
	        return json_encode($data, JSON_UNESCAPED_UNICODE);
	    }
	    return preg_replace_callback('/(?<!\\\\)\\\\u([0-9a-f]{4})/i',
		    function($m) {
		        $d = pack("H*", $m[1]);
		        $r = mb_convert_encoding($d, "UTF8", "UTF-16BE");
		        return $r !== "?" && $r !== "" ? $r: $m[0];
		    },
		    json_encode($data)
	    );
	}

	public static function getDescriptionsInModel($modelName)
	{
	    $keyList = array();
        $p_className = $modelName.'Model';
         $classNameV3 = str_replace('Model', '', $p_className).'/'.$p_className;
         $_dir = AXAPI_ROOT_PATH.'/mhc/models';
        if (isset($classNameV3))
        {
            $classNameV3 = preg_replace_callback('/([A-Za-z])/us', function($matches){
                                                    return '['.strtolower($matches[1]).strtoupper($matches[1]).']';
                                                }, $classNameV3);
            foreach (glob(AXAPI_ROOT_PATH.'/mhc/'.$classNameV3.'.php') as $_file) {
                $_modelFilePath = $_file;
                break;
            }
        }
        if (!isset($_modelFilePath))
        {
            $p_className = strtolower($p_className).'.php';
            foreach (glob($_dir.'/*.php') as $_file) {
                if (strtolower(basename($_file)) == $p_className)
                {
                    $_modelFilePath =  $_file;
                    break;
                }
            }
        }

		if (isset($_modelFilePath) && file_exists($_modelFilePath))
		{
		    $content = file_get_contents($_modelFilePath);
		    preg_match_all('/(\/\*[^\/]*?\*\/|\/\/.*|)\s+public function get(.*?)\(/',$content,$matches,PREG_SET_ORDER);
		    foreach ($matches as $match) {
		        $description = $match[1];
		        $keyStr = lcfirst($match[2]);
		        $keyList[$keyStr] = $description;
		    }


		    preg_match_all('/(\/\*[^\/]*?\*\/|\/\/.*|)\s+public \$(.*);/',$content,$matches,PREG_SET_ORDER);
		    foreach ($matches as $match) {
		        $description = $match[1];
		        $keyStr = lcfirst(W2String::camelCase($match[2]));
		        if ($description!='' && array_key_exists($keyStr,$keyList) && $keyList[$keyStr]==null)
		        {
		            $keyList[$keyStr] = $description;
		        }
		    }
		}
		return $keyList;
	}

}

//-------------------全局方法-----------------------

/** debug 直接打印日志 */
function AX_DEBUG($p_info=null)
{
    if (defined('IS_AX_DEBUG') && !is_null($p_info))
    {
    	print("\n");
        $_dbt = debug_backtrace();
        foreach ($_dbt as $_i => $_d) {
            if(!array_key_exists('file', $_d) || $_d['file']=='' || $_d['file']==__file__)
            {
                continue;
            }
            $_fileName = pathinfo($_d['file'],PATHINFO_BASENAME);
            if ($_fileName == 'DBTool.php' || $_fileName == 'DBModel.php' || $_fileName == 'AbstractHandler.php' )
            {
            	continue;
            }
            $_dFuc = $_d['function'];
            // if (in_array($_dFuc , [ 'loadModelList' , 'loadModelListByIds' , 'loadModelListById' , 'loadModelFirstInList' , 'saveModel', 'update', 'delete' , 'count', 'countAll' ] ) )
            // {
            // 	continue;
            // }
            printf('%s [%d] %s -> %s ' , W2Time::microtimetostr(null,'Y-m-d H:i:s.u') , $_d['line'],  $_fileName, $_dFuc);
            break;
        }
        if (is_string($p_info))
        {
	        print(strlen($p_info)>100?" : \n":' : ');
	        print($p_info);
        }
        else
        {
        	var_export($p_info);
        }
        print("\n");
    }
}
