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
	protected static $_CURRENTUSERID = null;

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

	public static function setCurrentUserID($p_userID=null)
	{
		static::$_CURRENTUSERID = $p_userID;
	}

	public static function getCurrentUserID()
	{
		if (is_null(static::$_CURRENTUSERID))
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
							throw new Exception("您的登录信息已经失效，请重新登录。",RUNTIME_CODE_ERROR_NOT_USER);
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
				                // return Utility::getArrayForResults(RUNTIME_CODE_ERROR_DATA_EMPTY,'未激活');
				                break;
				            case STATUS_PENDING:  //待审禁言
				                // return Utility::getArrayForResults(RUNTIME_CODE_ERROR_DATA_EMPTY,'禁言用户');
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
				}
				static::setCurrentUserID($p_userID);
			}
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
    	return array(
					'errorCode' => $errorCode,
					'errorStr' => $errorStr,
					'resultCount'=> (is_array($result) && array_values($result)===$result?count($result):1),
					'extraInfo'	=> $extraInfo,
					'results'	=> $result
    		);
    }

    /**
     * 判断结果数组是否正确获得结果
     * @param  array  $tmpResult 结果数组
     * @return boolean            是否正确获得
     */
    public static function isResults($tmpResult=null)
    {
    	return (is_array($tmpResult) && array_key_exists('errorCode',$tmpResult) );
    }

    /**
     * 判断结果数组是否正确获得结果
     * @param  array  $tmpResult 结果数组
     * @return boolean            是否正确获得
     */
    public static function isResultsOK($tmpResult=null)
    {
    	return (Utility::isResults($tmpResult) && $tmpResult['errorCode']==RUNTIME_CODE_OK);
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
    		return $tmpResult['results'];
    	}
    	return null;
    }

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
					return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请求信息错误',array('errorContent'=>'缺少头信息：'.$_key));
				}
			}

			if (abs($_HEADERS['Requesttime'] - time()) > 5*60 )//300
			{
				return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_AUTH,'请求失败了，请检查你的网络状态和系统时间是否准确哦。');
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
						return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请求信息错误',array('errorContent'=>'缺少头信息：'.$_key));
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
			foreach ($_REQUEST as $_key => $_value) {
				array_push($tmpArr, sprintf('%s=%s', $_key, $_value));
			}

			// array_push($tmpArr, sprintf('%s=%s', $_SERVER['HTTP_HOST'], preg_replace ("/(\/*[\?#].*$|[\?#].*$|\/*$)/", '', $_SERVER['REQUEST_URI'])));

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
					);
			}

		}
		else if (false)
		{
			$isAuthed = true;
		}
		else
		{
			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'请求信息错误',array('errorContent'=>'缺少头信息：'.'signature'));
		}
		if ($isAuthed === true)
		{
			return Utility::getArrayForResults(RUNTIME_CODE_OK,'',$isAuthed);
		}
		else
		{
			return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_AUTH,'校验失败',defined('IS_SQL_PRINT')&&IS_SQL_PRINT?$isAuthed:'');
		}

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
