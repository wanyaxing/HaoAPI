<?php
/**
 * Axapi相关接口（用来看些日志信息之类的）
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class AxapiController extends AbstractController{

    public static function actionSayHello()
    {
        return HaoResult::init(ERROR_CODE::$OK,array('$_GET'=>$_GET,'$_POST'=>$_POST,'$_FILES'=>$_FILES,'getallheaders()'=>getallheaders(),'$_SERVER'=>$_SERVER));
    }

    public static function actionLoadLogList()
    {
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null))
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
                // return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }

        $p_type = W2HttpRequest::getRequestString('type');
        $p_datetime = W2HttpRequest::getRequestDateTime('datetime');
        $p_pageIndex = W2HttpRequest::getRequestInt('page',null,false,true,1);
        $p_pageSize = W2HttpRequest::getRequestInt('size',null,false,true,100);
        // $p_countThis = W2HttpRequest::getRequestBool('iscountall')?1:-1;

        switch ($p_type) {
            case 'access':
            case 'error':
                break;

            default:
                return HaoResult::init(ERROR_CODE::$LOGLIST_TYPE_WRONG);
                break;
        }


        $logFilePath = sprintf('%s/%s-%s.log'
                            ,AXAPI_ROOT_PATH.'/logs/'
                            ,$p_type
                            ,W2Time::timetostr($p_datetime,'Ymd'));

        if (defined('IS_AX_DEBUG')){print("\n");print(W2Time::microtimetostr());print("\n");var_export($logFilePath);print("\n");}
        if (!file_exists($logFilePath))
        {
            return HaoResult::init(ERROR_CODE::$LOGLIST_NO_LOG_FOUND);
        }

        $lineList = file($logFilePath);//把整个文件读入一个数组中。


        $p_pageIndex = 0 - $p_pageIndex;

        if ($p_pageIndex < 0 && $p_pageSize>0)
        {
            $pageIndexMax = (intval((count($lineList)-1)/$p_pageSize)+1);
            $p_pageIndex += $pageIndexMax+1; //分页从1开始，第一页就是1.
        }

        $logFileSeek = ($p_pageIndex-1)*$p_pageSize;

        $result = array();

        for ($i=$logFileSeek; $i < count($lineList) && $i < $logFileSeek+$p_pageSize ; $i++) {
            $s_line = $lineList[$i];
            try {
                preg_match('/^(.*?\]): (.*+)$/',$s_line,$a_match);
                if (!is_array($a_match) || count($a_match)==0)
                {
                    $result[] = array('info'=>$s_line,'more'=>null);
                }
                else
                {
                    $result[] = array('info'=>$a_match[1],'more'=>json_decode($a_match[2]));
                }
            } catch (Exception $e) {
                $result[] = array('info'=>$s_line,'more'=>null);
            }

        }

        rsort($result);

        return HaoResult::init(ERROR_CODE::$OK,$result);
    }

    public static function actionCreateMhcWithTableName()
    {
        //if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null) != 'admin')
       // {
        //    return HaoResult::init(ERROR_CODE::$NO_AUTH);
        //}

        require_once(AXAPI_ROOT_PATH.'/mhc/create_mhc_with_table_name.php');

        exit;
    }


    public static function actionGetHomeTableForTest()
    {
        $result = array();
        for ($i=0; $i < 10; $i++) {
            switch (rand(0,5)) {
                case 1:
                    // $result[] = UserHandler::loadModelFirstInList(array(),'rand()');
                    break;
                case 2:
                    $result[] = SmsVerifyHandler::loadModelFirstInList(array(),'rand()');
                    break;
                case 3:
                    // $result[] = UnionLoginHandler::loadModelFirstInList(array(),'rand()');
                    break;
                case 4:
                    $result[] = array('suibian'=>'随便','looklook'=>'seesee');
                    break;
                case 5:
                    $result[] = array('one'=>array('two'=>array('three'=>'four')));
                    break;
            }

        }

        $sleep = W2HttpRequest::getRequestInt('sleep');
        if ( $sleep>0)
        {
            sleep($sleep);
        }
        // echo('x');exit;
        // sleep(rand(2,4));
        return HaoResult::init(ERROR_CODE::$OK,$result);
    }

    public static function actionUpdateCodesOfHaoConnect()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),'axapi',null) != 'admin')
        {
            return HaoResult::init(ERROR_CODE::$NO_AUTH);
        }

        require_once(AXAPI_ROOT_PATH.'/HaoConnect/create_haoconnect_codes.php');
    }

}
