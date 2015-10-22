<?php
/**
 * redis处理库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Redis {

    public static $CACHE_HOST  = null; 	//服务器
    public static $CACHE_PORT  = null; 	//端口
    public static $CACHE_INDEX = null;	//数据库索引，一般是0-20

    public static $CACHE_AUTH = null;   //密码，如果需要的话。

    public static $_ax_connect = null;										  //缓存连接唯一实例

    public static $clearCachedKeyList        = array();                           //设定本次请求结束后需要清理的缓存key列表数组，当本次请求结束后，就去清理列表里 的 key缓存。
    public static $clearCachedKeyPoolList    = array();                           //设定本次请求结束后需要清理的缓存池列表数组，当本次请求结束后，就去清理列表里 的 缓存池里 的 key缓存。
    public static $canBeCached               = false  ;                           //该页面是否可以被缓存。
    public static $canBeCachedKeySpecial     = null   ;                           //该页面的有规则缓存key,若不设定，则使用无规则key数据
    public static $canBeCachedTimeOut        = 600    ;                           //缓存设置：该缓存多久更新一次。单位：秒
    public static $canBeCachedKeyPoolList    = array();                           //设定所在缓存池列表数组，一个页面可以存到多个缓存池中，array()为空数组。

    public static $cachedKeyGotList    = array();                           //记录本次请求中读取过的缓存key。



    /** 缓存工厂，获得缓存连接。 */
    public static function memFactory(){
        if (static::$_ax_connect===null && class_exists('Redis') ) {
            if (static::$CACHE_HOST==null)
            {
                static::$CACHE_HOST    = W2Config::$CACHE_HOST;
                static::$CACHE_PORT    = W2Config::$CACHE_PORT;
                static::$CACHE_INDEX   = W2Config::$CACHE_INDEX;
                static::$CACHE_AUTH    = W2Config::$CACHE_AUTH;
            }
            if (static::$CACHE_HOST!=null)
            {
                static::$_ax_connect = new Redis();
                $_status = static::$_ax_connect->connect(static::$CACHE_HOST,static::$CACHE_PORT);
                if ($_status)
                {
                    if (!is_null(static::$CACHE_AUTH))
                    {
                        $authResult = static::$_ax_connect->auth(static::$CACHE_AUTH);
                        if ($authResult!='OK')
                        {
                            static::$_ax_connect = false;
                            throw new Exception("缓存服务器授权失败，请管理员检查授权设定是否正确。");
                        }
                    }
                    static::$_ax_connect->select(static::$CACHE_INDEX);
                }
                else
                {
                    static::$_ax_connect = false;
                }
            }
        }
        if (static::$_ax_connect===false)
        {
            return null;
        }
        return static::$_ax_connect;
    }


    /** 缓存服务器状态 */
    public static function info()
    {
        $memcached = static::memFactory();
        if (isset($memcached)) {
            return $memcached->INFO();
        }
        return null;
    }

    /**
     * 最强方法，根据key值获得缓存内容
     * @param  [type]  $p_key                   缓存key
     * @param  integer $p_timeout               过期时间
     * @return string                           缓存内容或null或304 Not Modified
     */
    public static function getCache($p_key,$p_timeout=300)
    {
        $memcached = static::memFactory();
        if (isset($memcached, $p_key)) {
            static::cachedKeyGotListPush($p_key);//记录本次请求中取过的key
        	if (static::isCacheCanBeUsed($p_key,$p_timeout))
        	{
                //有可用的缓存，取出缓存给ta就是。
                $_data = $_time = $memcached->get($p_key.'_data');
                if ($_data!==false)
                {
                    return $_data;
                }
        	}
        }
        return null;
    }

    /**
     * 是否有可用的缓存
     * @param  [type]  $p_key                   缓存key
     * @param  integer $p_timeout               过期时间
     * @return boolean            				是，否
     */
    public static function isCacheCanBeUsed($p_key,$p_timeout=300)
    {
        $memcached = static::memFactory();
        if (isset($memcached, $p_key)) {
            $_time = $memcached->get($p_key.'_time');
            if ($_time!==false){
                $_timelock = $memcached->get($p_key.'_timelock');

                $time_step = time()-$_time;

                //判断是否需要生成新缓存。锁状态过期 或 缓存过期且（锁状态不存在），需要生成新缓存.
                if (
                	(isset($_GET['reloadcache'])&& $_GET['reloadcache']=="true")	//强制reload缓存
                	||    ($_timelock!==false && time() > $_timelock)               //有锁，且时间锁已过期（一般是时间锁被重置为1了）
                	||    ($_timelock===false && $time_step > $p_timeout)          	//没有锁，且缓存超时
                	)
                {//不管
                        $memcached -> SETEX( $p_key.'_timelock',600, time()+120);//设定新的缓存锁，此位用户负责生成新的缓存，如果缓存失败，则两分钟后有人会重新触发。
                        AX_DEBUG('缓存失效：'.$p_key);
                        return false;//return false的意思是说，你得请重新请求数据。
                }

                return true;//这个key有旧缓存可用，而且没过期哦，你去取吧。
            }
        }
        return false;//没有可用的缓存，请重新生成吧
    }

    /**
     * 判断缓存标识对应的缓存是否还在。判断指定key是否有变化，若有变化，则更新新的标识
     * @param  [type]  $p_key                   缓存key
     * @param  [type]  &$HTTP_IF_MODIFIED_SINCE HTTP_IF_MODIFIED_SINCE
     * @param  [type]  &$HTTP_IF_NONE_MATCH     HTTP_IF_NONE_MATCH
     * @return boolean                          是，否
     */
    public static function isModified($p_key,&$HTTP_IF_MODIFIED_SINCE=null,&$HTTP_IF_NONE_MATCH=null)
    {
        $memcached = static::memFactory();
        if (isset($memcached, $p_key)) {
            $_time = $memcached->get($p_key.'_time');
            if ($_time!==false){
		        // 判断缓存标识对应的缓存是否还在。

		        // 如果用户请求判断$HTTP_IF_NONE_MATCH，则提取$etag并比对，
		        // 如果缓存没有变化，则重写变量为false(缓存没有改变)，
		        // 如果缓存有变化，则重写变量为新缓存关键字。
		        if (isset($HTTP_IF_NONE_MATCH))
		        {
		        	if ($HTTP_IF_NONE_MATCH===false)
		        	{
		        		throw new Exception("此处不允许传入参数HTTP_IF_NONE_MATCH为false");

		        	}
		            $etag = md5($_time);
		            if ($HTTP_IF_NONE_MATCH == $etag)//这里用Last-Modified的header标识来进行客户端的缓存控制。
		            {
		                return false;//缓存没变化哦，可以考虑直接304的说。
		            }
		            else
		            {
		            	$HTTP_IF_NONE_MATCH = $etag;
		            }
		        }
		        // 如果用户请求判断$HTTP_IF_MODIFIED_SINCE，则提取$eLastModified并比对最后修改时间，
		        // 如果缓存没有变化，则重写变量为false(缓存没有改变)，
		        // 如果缓存有变化，则重写变量为最新最后修改时间。
		        if (isset($HTTP_IF_MODIFIED_SINCE))
		        {
		        	if ($HTTP_IF_MODIFIED_SINCE===false)
		        	{
		        		throw new Exception("此处不允许传入参数HTTP_IF_MODIFIED_SINCE为false");

		        	}
		            $eLastModified = gmdate('D, d M Y H:i:s \G\M\T', $_time);
		            if ($HTTP_IF_MODIFIED_SINCE == $eLastModified)//这里用Last-Modified的header标识来进行客户端的缓存控制。
		            {
		                return false;//缓存没变化哦，可以考虑直接304的说。
		            }
		            else
		            {
		                $HTTP_IF_MODIFIED_SINCE = $eLastModified;
		            }
		        }
            }
        }
        return true;//服务器的缓存已经更新了，请重新读取。
    }

    /**
     * 最强搭档，更新缓存到指定Key，如果有必要，还会修改 HTTP_IF_MODIFIED_SINCE HTTP_IF_NONE_MATCH
     * @param  [type]  $p_key                   缓存key
     * @param  [type] $buffer                  [description]
     * @return null
     */
    public static function setCache($p_key,$buffer){
        $memcached = static::memFactory();
        if (isset($memcached, $p_key)) {
            $_time = time();
            $memcached -> SETEX($p_key.'_data',3600,$buffer);
            $memcached -> SETEX($p_key.'_time',3600,$_time);
            $memcached -> del($p_key.'_timelock');
            static::isModified($_time,$HTTP_IF_MODIFIED_SINCE,$HTTP_IF_NONE_MATCH);//更新缓存标识，如果需要的话。
        }
    }

    /**
     * 读取缓存的变身，可以读取实例呢
     * @param  [type]  $p_key                   缓存key
     * @param  integer $p_timeout               过期时间
     * @return [type]             [description]
     */
    public static function getObj($p_key,$p_timeout=300)
    {
        $_data = static::getCache($p_key,$p_timeout);
        if ($_data!==false && $_data!==null)
        {
            return unserialize($_data);
        }
        return null;
    }

    /**
     * 存储缓存的变身，可以存储实例呢
     * @param  [type]  $p_key                   缓存key
     * @param  object $p_obj               目标实例
     * @return [type]        [description]
     */
    public static function setObj($p_key,$p_obj){
    	static::setCache($p_key,serialize($p_obj));
    }


    /** 在指定缓存池增加缓存key，所谓缓存池，其实是一个特殊数据的存储，其内容是N个缓存key，所以称之为池。其主要用于多个缓存共同触发更新。*/
    public static function addToCacheKeyPool($p_keyPool,$p_key,$p_expire=0)
    {
        $memcached = static::memFactory();
        if (isset($memcached,$p_keyPool, $p_key)) {
            $memcached -> lpush( $p_keyPool.'_keypool', $p_key );
            if ($p_expire>0)
            {
	            $memcached -> EXPIRE( $p_keyPool.'_keypool', 3600 );
            }
        }
    }

    /** 重置缓存 所谓重置缓存，就是设_timelock为1，并不是真的清理缓存，只有当下次下个用户请求对应的缓存数据的时候，才会覆盖更新缓存。（注意，是覆盖更新，如果有并发读取的情况，旧的缓存仍然会被用到哦）*/
    public static function resetCache($p_key)
    {
        $memcached = static::memFactory();
        if (isset($memcached, $p_key)) {
            $memcached -> SETEX( $p_key.'_timelock',600, 1 );
        }
    }

    /** 重置指定缓存池里的所有key，如上所说，缓存池就是用来重置的。此处重置。一般是这样，我们将某个列表的缓存放入列表中各元素独立的缓存池里，一旦某个元素更新，就主动重置其对应的独立缓存池，就可以达到列表类缓存的实时更新了。 */
    public static function resetCacheKeyPool($p_keyPool)
    {
        $memcached = static::memFactory();
        if (isset($memcached, $p_keyPool)) {
            $keysList = $memcached -> lGetRange( $p_keyPool.'_keypool',0,-1);
            foreach ($keysList as $_key => $p_key) {
                static::resetCache($p_key);
            }
            $memcached -> del( $p_keyPool.'_keypool');
        }
        return null;
    }

    /** 重置所有缓存，慎用。 */
    public static function emptyCache()
    {
        // W2Log::debug($p_keyPool);
        $memcached = static::memFactory();
        // $memcached -> FLUSHALL();
        $memcached -> FLUSHDB();//清空当前数据库
        return null;
    }

    /** 进程级变量，追加临时存储需要清理的缓存池key，页面请求成功完成后统一清理，一般用于数据更新后清理对应的缓存池。（需要另写方法辅助） */
    public static function clearCachedKeyPoolListPush($p_key)
    {
        static::$clearCachedKeyPoolList[]=$p_key;
    }

    /** 进程级变量，追加临时存储需要清理的缓存key，页面请求成功完成后统一清理，一般用于数据更新后清理对应的缓存。（需要另写方法辅助） */
    public static function clearCachedKeyListPush($p_key)
    {
        static::$clearCachedKeyList[]=$p_key;
    }

    /** 进程级变量，追加临时存储需要备案的缓存池key，页面请求成功完成后统一将页面key在这些缓存池中备案。（需要另写方法辅助） */
    public static function canBeCachedKeyPoolListPush($p_key)
    {
        static::$canBeCachedKeyPoolList[]=$p_key;
    }

    /** 进程级变量，记录本次请求过程中用过的缓存key */
    public static function cachedKeyGotListPush($p_key)
    {
        static::$cachedKeyGotList[]=$p_key;
    }

    /** 在指定页面池增加缓存key，所谓页面池，其实是一个特殊数据的存储，其内容是N个缓存key，所以称之为池。其主要用于查询指定的页面。*/
    public static function addToCachePagePool($p_pagePool,$p_key,$p_expire=0)
    {
        $memcached = static::memFactory();
        if (isset($memcached,$p_pagePool, $p_key)) {
            $memcached -> lpush( $p_pagePool.'_pagepool', $p_key );
            if ($p_expire>0)
            {
                $memcached -> EXPIRE( $p_pagePool.'_pagepool', 3600 );
            }
        }
    }

    // public static function get

}
