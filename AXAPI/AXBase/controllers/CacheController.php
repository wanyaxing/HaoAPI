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
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'emptyCache',null))
        {
            case 'admin'   : //有管理权限
                break;
            case 'self'    : //作者
            case 'normal'  : //正常用户
            case 'draft'   : //未激活
            case 'pending' : //待审禁言
            case 'disabled': //封号
            case 'visitor' : //游客
            default :
                return Utility::getArrayForResults(RUNTIME_CODE_ERROR_NO_AUTH,'您没有权限执行该操作');
                break;
        }

        W2Cache::emptyCache();

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',null);
	}

}
