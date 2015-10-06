<?php
/**
 * 缓存处理库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Cache extends W2Redis{
	// 为啥要重写以下的静态变量，因为继承来的类，静态变量如果不重新声明，则会共用父类的静态变量。
    public static $CACHE_HOST  = null; 	//服务器
    public static $CACHE_PORT  = null; 	//端口
    public static $CACHE_INDEX = null;  //数据库索引，一般是redis服务器用到0-20

    public static $CACHE_AUTH = null;	//密码，如果需要的话。


    public static $clearCachedKeyList        = array();                           //设定本次请求结束后需要清理的缓存key列表数组，当本次请求结束后，就去清理列表里 的 key缓存。
    public static $clearCachedKeyPoolList    = array();                           //设定本次请求结束后需要清理的缓存池列表数组，当本次请求结束后，就去清理列表里 的 缓存池里 的 key缓存。
    public static $canBeCached               = false  ;                           //该页面是否可以被缓存。
    public static $canBeCachedKeySpecial     = null   ;                           //该页面的有规则缓存key,若不设定，则使用无规则key数据
    public static $canBeCachedTimeOut        = 600    ;                           //缓存设置：该缓存多久更新一次。单位：秒
    public static $canBeCachedKeyPoolList    = array();                           //设定所在缓存池列表数组，一个页面可以存到多个缓存池中，array()为空数组。

    public static $_ax_connect = null;										  //缓存连接唯一实例

}
