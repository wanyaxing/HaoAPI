<?php
/**
* 基本配置
* @package conf
* @author axing
* @version 0.1
*/

if (!defined('AXAPI_ROOT_PATH'))
{
    define('AXAPI_ROOT_PATH', __dir__ );
}

//加载类 并注册自动加载事件。
require_once(AXAPI_ROOT_PATH.'/components/requireme.php');

/** API项目名（最好全网唯一）   */  define('AXAPI_PROJECT_NAME', 'project-axapi' );

/** 混淆方案- 头信息混淆方案   */  define("SECRET_HAX_BROWSER"    , 'secret=apites894987la9sij');
/** 混淆方案- 头信息混淆方案   */  define("SECRET_HAX_PC"         , 'secret=apites848712ihjwe6');
/** 混淆方案- 头信息混淆方案   */  define("SECRET_HAX_ANDROID"    , 'secret=apites3539989fij3j');
/** 混淆方案- 头信息混淆方案   */  define("SECRET_HAX_IOS"        , 'secret=apites465671yoditc');
/** 混淆方案- 头信息混淆方案   */  define("SECRET_HAX_WINDOWS"    , 'secret=apites2921889iaf94');
/** 混淆方案- 用户信息加密混淆 */  define("USER_COOKIE_RANDCODE"  , 'f9823r2ioeoiwaeefadsafeww');
/** 混淆方案- 密码加密存储混淆 */  define("PASSWORD_RANDCODE"     , 'f983r2ewioeoiwaeefadsafew');

// 数据库配置
if (!defined('DB_HOST'))
{
    define('DB_HOST'    , '127.0.0.1');
    define('DB_DATABASE', 'dbhaoye');
    define('DB_USER'    , 'apihaoye');
    define('DB_PASSWORD', 'weiohaoyeI738');
}


//请酌情配置以下信息
W2Config::$API_KEY_ANDROID    = '2100023326834';                   /** xg推送 */
W2Config::$SECRET_KEY_ANDROID = 'cacd0597db93508d874c49c';         /** xg推送 */
W2Config::$API_KEY_IOS        = '2100023226814';                   /** xg推送 */
W2Config::$SECRET_KEY_IOS     = 'b632046c0cewfwe985eabe00';        /** xg推送 */

W2Config::$API_KEY            = '54d47832fd98c5dbd10001df';        /** 百度推送 */
W2Config::$SECRET_KEY         = 'c3a48b44a21e37210c3e3b7add5c1c7a';/** 百度推送 */

W2Config::$Qiniu_bucket       = 'test';                            /** 七牛配置 */
W2Config::$Qiniu_domain       = '7u2sdg.test.z0.glb.clouddn.com';  /** 七牛配置 */
W2Config::$Qiniu_accessKey    = '_AFIydsfaRbmMRP8aO38y3C9';        /** 七牛配置 */
W2Config::$Qiniu_secretKey    = 'Uv9yBLUeqsdfafmVgAybHBRbT07Jj';   /** 七牛配置 */

W2Config::$SMS_USER    		  = 'USERNAME';  						   /** SMS用户名 */
W2Config::$SMS_PASSWD         = '123456';   					   /** SMS密码 */


W2Config::$UCPASS_ACCOUNTSID    = null;                            /** 云之讯相关密钥 */
W2Config::$UCPASS_TOKEN         = null;                            /** 云之讯相关密钥 */
W2Config::$UCPASS_APPID         = null;                            /** 云之讯相关密钥 */
W2Config::$UCPASS_TEMPLATEID    = null;                            /** 云之讯相关密钥 */

W2Config::$LOG_PATH           = AXAPI_ROOT_PATH . '/logs/';   	   /** 日志存储目录 */

W2Config::$CACHE_HOST  = '127.0.0.1';                                     //缓存服务器
W2Config::$CACHE_PORT  = '6379';                                          //缓存端口
W2Config::$CACHE_INDEX = 1;                                               //缓存数据库索引，一般是0-20
W2Config::$CACHE_AUTH  = null; 											  //缓存密码，如果需要的话。

// 默认分页数据量
define("DEFAULT_PAGE_SIZE", 10);

/**
 * UserHandler的类名
 * 用户User表 推荐必须以下字段
 *   `id` int(11) NOT NULL AUTO_INCREMENT,
 *   `level` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0: 未激活用户 1：普通用户 5：普通管理员  9：超级管理员',
 *   `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0: 不存在  1: 正常 2: 封号  3：禁言',
 *   `lastLoginTime` datetime DEFAULT NULL COMMENT '最后一次登录时间',
 */
define("USERHANDLER_NAME", 'UserHandler');

