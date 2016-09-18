<?php
/**
 * 环信相关接口
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class EasemobController extends AbstractController{

    /**
     * 根据用户对象换算成环信用户帐号
     * @param  UserModel $userModel [description]
     * @return [type]               [description]
     */
    public static function getAccountOfUserModel(UserModel $userModel)
    {
        $username = AXAPI_PROJECT_NAME . AXAPI_DEPLOY_STATUS . $userModel->getId();
        $username = preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $password = md5($username . $userModel->getLastPasswordTime() . PASSWORD_RANDCODE);
        return array('username'=>$username,'password'=>$password,'appKey'=>W2Easemob::$ORG_NAME . '#' . W2Easemob::$APP_NAME);
    }




    /** 获取环信用户登录信息 */
    public static function actionGetMyAuthInfo()
    {
        $tmpModel = Utility::getCurrentUserModel();
        if (!is_object($tmpModel))
        {
            return HaoResult::init(ERROR_CODE::$NOT_USER);
        }
        $account = EasemobController::getAccountOfUserModel($tmpModel);
        return HaoResult::init(ERROR_CODE::$OK,$account);
    }


    /** 重置环信用户登录信息 */
    public static function actionResetMyAuthInfo(UserModel $userModel=null)
    {
        if (is_null($userModel))
        {
            $userModel = Utility::getCurrentUserModel();
        }

        if (!is_object($userModel))
        {
            return HaoResult::init(ERROR_CODE::$NOT_USER);
        }

        $account = EasemobController::getAccountOfUserModel($userModel);
        $getUser = W2Easemob::getUser($account['username']);
        $createUser = null;
        $resetPassword = null;
        if (is_array($getUser))
        {
            if (isset($getUser['error']))
            {
                if ($getUser['error'] == 'service_resource_not_found')
                {
                    $createUser = W2Easemob::createUser($account['username'],$account['password']);
                }
            }
            else
            {
                $resetPassword = W2Easemob::resetPassword($account['username'],$account['password']);
            }
        }
        return HaoResult::init(ERROR_CODE::$OK,array('account'=>$account,'getUser'=>$getUser,'createUser'=>$createUser,'resetPassword'=>$resetPassword));
    }


    /** 逐个推送信息给用户 */
    public static function pushSingleMessage($pWhere=array(),$content,$customtype=null,$customvalue=null)
    {
        $results = array();

        $push_type = 1; // 1单个设备
        $userModelList = UserHandler::loadModelList($pWhere,'id desc',1,99999);

        $pDeviceTokens = array();

        foreach ($userModelList as $index=>$userModel) {
            $account = EasemobController::getAccountOfUserModel($userModel);
            $pDeviceTokens[] = $account['username'];
        }
        if (defined('IS_AX_DEBUG')){var_export($pDeviceTokens);}
        foreach ($pDeviceTokens as  $deviceTokens)
        {
            $results[] = W2Easemob::pushMessage( $content,$customtype,$customvalue ,$deviceTokens);
        }
        return $results;
    }

    public static function pushMessageToUser($userID,$content,$customtype=null,$customvalue=null)
    {
        $pWhere = array();
        if ($userID>0)
        {
            $pWhere['id'] = $userID;
            return EasemobController::pushSingleMessage($pWhere,$content,$customtype,$customvalue);
        }
        return null;
    }


    public static function pushMessageToTelephone($telephone,$content,$customtype=null,$customvalue=null)
    {
        $pWhere = array();
        if ($userID>0)
        {
            $pWhere['telephone'] = $userID;
            return EasemobController::pushSingleMessage($pWhere,$content,$customtype,$customvalue);
        }
        return null;
    }


    public static function actionPushMessage()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'easemob',null) != 'admin')
        {
           return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }

        $results = array();

        $content      = W2HttpRequest::getRequestString('content',false,null,1,140);
        $type        = W2HttpRequest::getRequestInArray('type',array(1,2)); //1单人 2所有人
        $customtype   = W2HttpRequest::getRequestInt('t');
        $customvalue  = W2HttpRequest::getRequestString('v');

        switch ($type) {
            case 1://1单人
                $userID = W2HttpRequest::getRequestInt('userid');
                $telephone = W2HttpRequest::getRequestTelephone('telephone',false);

                if ($userID!=null)
                {
                    $results = array_merge($results,EasemobController::pushMessageToUser($userID,$content,$customtype,$customvalue));
                }
                else if ($telephone!=null)
                {
                    $results = array_merge($results,EasemobController::pushMessageToTelephone($telephone,$content,$customtype,$customvalue));
                }
                else
                {
                    return HaoResult::init(ERROR_CODE::$DEVICE_PLS_USER_OR_PHONE);
                }

                break;

            case 2://2所有人

                throw new Exception('暂不支持群发所有人');

            default:
                    return HaoResult::init(ERROR_CODE::$DEVICE_PLS_TYPE);
                break;
        }

        return HaoResult::init(ERROR_CODE::$OK,$results);
    }


}
