<?php
/**
 * 微信公众号相关接口
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class WechatController extends AbstractController{

    /** 获取微信授权用的地址 */
    public static function actionGetUrlForWxAuth()
    {
        $redirect_uri = W2HttpRequest::getRequestString('redirect_uri');
        $scope = W2HttpRequest::getRequestString('scope');
        $state = W2HttpRequest::getRequestString('state');
        $uri = W2Weixin::getUrlForWxAuth($redirect_uri,$scope,$state);
        return HaoResult::init(ERROR_CODE::$OK,array('oauth_uri'=>$uri));
    }

    /** 通过code换取网页授权access_token并根据token取得用户资料 */
    public static function actionGetWxUserInfoOfCode()
    {
        $code = W2HttpRequest::getRequestString('code');
        $token = W2Weixin::getUserInfoOfCode($code);  //通过code向微信请求用户数据
        if (is_array($token) && isset($token['openid']))
        {
            return HaoResult::init(ERROR_CODE::$OK,$token);
        }
        return HaoResult::init(ERROR_CODE::$UNKNOWN_ERROR,$token);
    }


    /** 根据access_token拉取用户信息(需scope为 snsapi_userinfo) */
    public static function actionGetWxUserInfoOfAccessToken()
    {
        $access_token = W2HttpRequest::getRequestString('access_token');
        $openid = W2HttpRequest::getRequestString('openid');
        $userInfo = W2Weixin::getUserInfoOfAccessToken($access_token,$openid);  //通过code向微信请求用户数据
        if (is_array($userInfo) && isset($userInfo['openid']))
        {
            return HaoResult::init(ERROR_CODE::$OK,$userInfo);
        }
        return HaoResult::init(ERROR_CODE::$UNKNOWN_ERROR,$userInfo);
    }

    /** 取得公众号菜单设定 */
    public static function actionGetMenu()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null) != 'admin')
        {
           return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }
        $result = W2Weixin::getMenu();
        if (isset($result['errcode']))
        {
            return HaoResult::init(array($result['errcode'],$result['errmsg']),$result);
        }
        else
        {
            return HaoResult::init(ERROR_CODE::$OK, $result);
        }
    }


    /** 重设公众号菜单 */
    public static function actionSetMenu()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null) != 'admin')
        {
           return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }

        if (!isset($_REQUEST['menu']) || !is_array($_REQUEST['menu']))
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR);
        }
        $result = W2Weixin::setMenu($_REQUEST['menu']);

        if (isset($result['errcode']))
        {
            return HaoResult::init(array($result['errcode'],$result['errmsg']),$result);
        }
        else
        {
            return HaoResult::init(ERROR_CODE::$OK, $result);
        }
    }

    /** 发送模板消息给用户 */
    public static function sendTemplateMessage($templateid,$userID,$jump,$data)
    {
        $openid = UnionLoginHandler::selectField('unionToken',array('userID'=>$userID,'unionType'=>UNION_TYPE::WEIXIN));
        return W2Weixin::sendTemplateMessage($templateid,$openid,$jump,$data);
    }

    /** 取得公众号菜单设定 */
    public static function actionGetSignatureDataForJS()
    {
        $url = W2HttpRequest::getRequestString('url',true,null,1,255);
        $timestamp = W2HttpRequest::getRequestInt('timestamp');
        $noncestr = W2HttpRequest::getRequestString('noncestr');
        $result = W2Weixin::getSignatureDataForJS($url,$timestamp,$noncestr);
        return HaoResult::init(ERROR_CODE::$OK, $result);
    }

    /** 根据openid取得用户属性（包括是否已关注） */
    public static function actionGetWxUserInfoOfOpenID()
    {
        $openid = W2HttpRequest::getRequestString('openid');
        $userInfo = W2Weixin::getUserInfoOfOpenID($openid);
        if (is_array($userInfo) && isset($userInfo['subscribe']) && $userInfo['subscribe']==1)
        {
            return HaoResult::init(ERROR_CODE::$OK,$userInfo);
        }
        return HaoResult::init(ERROR_CODE::$UNKNOWN_ERROR,$userInfo);
    }

    /** 获取素材列表 */
    public static function actionBatchgetMaterial()
    {
        $type   = W2HttpRequest::getRequestInArray('material_type',array('image','voice','video','news'));
        // $offset = W2HttpRequest::getRequestInt('offset',null,true,0,0);
        $offset = 0;
        $count  = W2HttpRequest::getRequestInt('count',20,false,1,1);
        $page  = W2HttpRequest::getRequestInt('page');
        if ($page>0)
        {
            $offset = ($page-1)*$count;
        }
        $result = W2Weixin::batchgetMaterial($type,$offset,$count);
        return HaoResult::init(ERROR_CODE::$OK, $result);
    }

    /** 获取素材详情(此处直接返回素材数据，不包装成HaoResult) */
    public static function actionGetMaterial()
    {
        $mediaid  = W2HttpRequest::getRequestString('mediaid');
        $result = W2Weixin::getMaterial($mediaid);
        echo $result;
        exit;
        // return HaoResult::init(ERROR_CODE::$OK, $result);
    }


    /** 当前客服列表 */
    public static function actionGetOnlineKfList()
    {
        $result = W2Weixin::getOnlineKfList();
        return HaoResult::init(ERROR_CODE::$OK, $result);
    }
}
