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
                return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_AUTH,'您没有权限执行该操作');
                break;
        }

        W2Cache::emptyCache();

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',null);
    }

	public static function actionInfo()
	{
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'cacheController',null))
        {
            case 'admin'   : //有管理权限
                break;
            default :
                return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_AUTH,'您没有权限执行该操作');
                break;
        }

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',W2Cache::info());
	}

}
