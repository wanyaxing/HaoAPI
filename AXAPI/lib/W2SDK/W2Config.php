<?php
/**
 * W2库的常见配置类
 * 建议另立统一配置文件来覆盖对应配置。
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Config {

	public static $API_KEY_ANDROID    = '2100023326834';
	public static $SECRET_KEY_ANDROID = 'cacd0597db93508d874c49c';

	public static $API_KEY_IOS        = '2100023226814';
	public static $SECRET_KEY_IOS     = 'b632046c0cewfwe985eabe00';

	public static $API_KEY            = '54d47832fd98c5dbd10001df';
	public static $SECRET_KEY         = 'c3a48b44a21e37210c3e3b7add5c1c7a';

	public static $Qiniu_bucket = 'test';
	public static $Qiniu_domain = '7u2sdg.test.z0.glb.clouddn.com';
	public static $Qiniu_accessKey = '_AFIydsfaRbmMRP8aO38y3C9';
	public static $Qiniu_secretKey = 'Uv9yBLUeqsdfafmVgAybHBRbT07Jj';

	public static $SMS_USER = '1234';
	public static $SMS_PASSWD = '1234';

	public static $LOG_PATH = '/tmp/logs/';
	public static $LOG_FILENAME = null;

}
