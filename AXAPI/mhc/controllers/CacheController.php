<?php
/**
 * 缓存相关接口
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class CacheController extends AbstractController{

    public static function actionEmptyCache()
    {
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'cacheController',null))
        {
            case 'admin'   : //有管理权限
                break;
            default :
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }


        return HaoResult::init(ERROR_CODE::$OK,W2Cache::emptyCache());
    }

	public static function actionInfo()
	{
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'cacheController',null))
        {
            case 'admin'   : //有管理权限
                break;
            default :
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }

        return HaoResult::init(ERROR_CODE::$OK,W2Cache::info());
	}

}
