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
require_once(AXAPI_ROOT_PATH.'/components/autoload.php');

/** API项目名（最好全网唯一）   */  define('AXAPI_PROJECT_NAME', 'project-haoframe' );

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

define('W2LOG_PATH' , AXAPI_ROOT_PATH . '/logs/');         /** 日志存储目录 */
// define('W2LOG_FILENAME' , 'w2log.log');         * 日志存储文件名

//请酌情配置以下信息
/** xg推送 */
// define('W2PUSH_API_KEY_ANDROID'   , '2100023326834');
// define('W2PUSH_SECRET_KEY_ANDROID', 'cacd0597db93508d874c49c');
// define('W2PUSH_API_KEY_IOS'       , '2100023226814');
// define('W2PUSH_SECRET_KEY_IOS'    , 'b632046c0cewfwe985eabe00');

/** 七牛配置 */
// define('W2QINIU_QINIU_BUCKET'   , 'test');
// define('W2QINIU_QINIU_DOMAIN'   , '7u2sdg.test.z0.glb.clouddn.com');
// define('W2QINIU_QINIU_ACCESSKEY', '_AFIydsfaRbmMRP8aO38y3C9');
// define('W2QINIU_QINIU_SECRETKEY', 'Uv9yBLUeqsdfafmVgAybHBRbT07Jj');

/** SMS用户密码 */
// define('W2SMS_USER'  , 'USERNAME');
// define('W2SMS_PASSWD', '123456');

/** 云之讯相关密钥 */
// defin('UCPASS_ACCOUNTSID', '');
// defin('UCPASS_TOKEN'     , '');
// defin('UCPASS_APPID'     , '');
// defin('UCPASS_TEMPLATEID', '');


/** redis 缓存服务器 */
// define('W2CACHE_HOST'  ,'127.0.0.1');
// define('W2CACHE_PORT'  ,'6379');
// define('W2CACHE_INDEX' ,4);
// define('W2CACHE_AUTH'  ,'thIsIsteStseRverForhAOxitech1305bywYxin151006');

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

/** 常量类，提供了遍历类中所有常量的方法 */
class CONST_CLASS
{
    public static function getAllConstants()
    {
        $oClass = new ReflectionClass(get_called_class());
        $constants = $oClass->getConstants();
        return array_values($constants);
    }
}
