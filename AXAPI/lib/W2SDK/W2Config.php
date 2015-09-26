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


	/** @var string 云之讯短信账号的配置 */
	public static $UCPASS_ACCOUNTSID = 'dec24d3bfb0b62d45f992b52d7285a2f';
	public static $UCPASS_TOKEN      = 'd6b63af5c800a8ab433782f2ee2cbf2e';
	public static $UCPASS_APPID      = '1d19d3d36ef94dac81019061889a0f96';
	public static $UCPASS_TEMPLATEID      = '11646';

	public static $CACHE_HOST      = null;
	public static $CACHE_PORT      = null;
	public static $CACHE_INDEX      = null;
}
