<?php
ini_set('display_errors',1);            //错误信息
ini_set('display_startup_errors',1);    //php启动错误信息

error_reporting(-1);                    //打印出所有的 错误信息

date_default_timezone_set('Asia/Shanghai');//设定时区

define("AX_TIMER_START", microtime (true));//记录请求开始时间


    //加载配置文件
    require_once(__dir__.'/../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

    AX_DEBUG('start');

    /**
     * 插入日志，之所以方法放这里，是因为index.php代码改动最少，这个方法存活率最高，因为是用来记日志的嘛。
     * @param  string|array $p_content [description]
     * @param  string $p_type    类型，用于组成文件名
     */
    function file_put_log($p_content='',$p_type='access')
    {
        try {
            $currentUserId = Utility::getCurrentUserID();
        } catch (Exception $e) {
            $currentUserId = 0;
        }
        file_put_contents(sprintf('%s/%s-%s.log'
                            ,AXAPI_ROOT_PATH.'/logs/'
                            ,$p_type
                            ,strftime('%Y%m%d'))
                            ,sprintf("[%s] (%s s) [%s] [%d] [%s] [%s/%s]: %s\n"
                                        ,W2Time::microtimetostr(AX_TIMER_START)
                                        ,number_format(microtime (true) - AX_TIMER_START, 5, '.', '')
                                        ,Utility::getCurrentIP()
                                        ,Utility::getCurrentUserID()
                                        ,count($_POST)>0?'POST':'GET'
                                        ,$GLOBALS['apiController'], $GLOBALS['apiAction']
                                        ,is_string($p_content)?$p_content:Utility::json_encode_unicode($p_content)
                                    )
                            ,FILE_APPEND);
    }

    /**
     * 主要用于捕捉致命错误，每次页面处理完之后执行检查
     * @return [type] [description]
     */
    function catch_fatal_error()
    {
      // Getting Last Error
       $last_error =  error_get_last();

        // Check if Last error is of type FATAL
        if(isset($last_error['type']))
        {
            // Fatal Error Occurs
            // Do whatever you want for FATAL Errors
            $errorMsg = null;
            switch ($last_error['type']) {
                case E_ERROR:
                    $errorMsg = '严重错误：服务器此时无法处理您的请求，请稍后或联系管理员。';
                    break;
                case E_PARSE:
                    $errorMsg = '代码拼写错误：是Peter干的吗，请向管理员举报Peter。';
                    break;
                case E_WARNING:
                    $errorMsg = '警告：出现不严谨的代码逻辑，请告知管理员这个问题。';
                    break;
                case E_NOTICE:
                    $errorMsg = '警告：出现不严谨的代码逻辑，请告知管理员这个问题。';
                    break;
            }

            if (!is_null($errorMsg))
            {
                //记录错误日志
                file_put_log($_REQUEST,'error');
                file_put_log($last_error,'error');

                //返回错误信息
                @ob_end_clean();//要清空缓冲区， 从而删除PHPs " 致命的错误" 消息。
                $results = HaoResult::init(array(RUNTIME_CODE_ERROR_UNKNOWN,$errorMsg,$errorMsg),null,defined('IS_AX_DEBUG')?array('errorContent'=>'Error on line '.$last_error['line'].' in '.$last_error['file'].': '.$last_error['message'].''):null);
                echo Utility::json_encode_unicode($results->properties());
                exit;
            }
        }

    }
    register_shutdown_function('catch_fatal_error');

    $apiPaths = explode('/', preg_replace ("/(\/*[\?#].*$|[\?#].*$|\/*$)/", '', $_SERVER['REQUEST_URI']));
    if (count($apiPaths)<3)
    {
        list ($apiController, $apiAction) = explode ("/", W2HttpRequest::getRequestString('r',false,'/'), 2);
    }
    else
    {
        $apiController = $apiPaths[1];
        $apiAction = $apiPaths[2];
    }

    //接口格式校验
    $results = Utility::getAuthForApiRequest();
    if ( $results->isResultsOK())
    {
        //调用对应接口方法
        try {

            $method = new ReflectionMethod(W2String::camelCase($apiController.'Controller'), W2String::camelCase('action'.$apiAction));
            $results = $method->invoke(null,0);
            // $apiController .= 'Controller';
            // $apiAction = 'action'.$apiAction;
            // $apiController::$apiAction();
        } catch (Exception $e) {
            $results = HaoResult::init(array($e->getCode()==0?RUNTIME_CODE_ERROR_UNKNOWN:$e->getCode(),$e->getMessage(),$e->getMessage()),null,defined('IS_AX_DEBUG')?array('errorContent'=>'Error on line '.$e->getLine().' in '.$e->getFile().': '.$e->getMessage().''):null);
        }
    }

    //打印接口返回的数据
    if (is_object($results) && get_class($results) == 'HaoResult' )
    {
        header('Content-Type:text/javascript; charset=utf-8');
        echo Utility::json_encode_unicode($results->properties());
    }
    else if (is_string($results))
    {
        echo $results;
    }
    else
    {
        echo Utility::json_encode_unicode($results);
    }

    //记录接口日志
    file_put_log($_REQUEST,'access');

    exit;
