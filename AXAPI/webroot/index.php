<?php
ini_set('display_errors',1);            //错误信息
ini_set('display_startup_errors',1);    //php启动错误信息
error_reporting(-1);                    //打印出所有的 错误信息

date_default_timezone_set('PRC');

    // define('IS_SQL_PRINT',True);//打印sql语句
    if (array_key_exists('Is_sql_print', getallheaders()))
    {
        define('IS_SQL_PRINT',True);//打印sql语句
    }

    //加载配置文件
    require_once(__dir__.'/../config.php');

    //常用常量
    require_once(AXAPI_ROOT_PATH.'/components/constants.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');


    $results = Utility::getAuthForApiRequest();
    if ($results['errorCode']==RUNTIME_CODE_OK)
    {
        try {
            list ($apiController, $apiAction) = explode ("/", W2HttpRequest::getRequestString('r',false,'/'), 2);
            $method = new ReflectionMethod($apiController.'Controller', 'action'.$apiAction);
            $results = $method->invoke(null,0);
        } catch (Exception $e) {
            $results = Utility::getArrayForResults(RUNTIME_CODE_ERROR_UNKNOWN,$e->getMessage(),null,array('errorContent'=>'Error on line '.$e->getLine().' in '.$e->getFile().': '.$e->getMessage().''));
        }
    }

    if (is_array($results) && array_key_exists('errorCode',$results))
    {
        $data = $results['results'];
        if (is_object($results['results']) && is_subclass_of($results['results'],'AbstractModel'))
        {
            $data = $results['results']->properties();
        }
        else if (is_array($results['results']) && array_key_exists(0, $results['results']))
        {
            $data = array();
            foreach ($results['results'] as $_key => $_value) {
                if (is_object($_value) && is_subclass_of($_value,'AbstractModel'))
                {
                    $data[$_key] = $_value->properties();
                }
                else
                {
                    $data[$_key] = $_value;
                }
            }
        }
        $results['results'] = $data;
        header('Content-Type:text/javascript; charset=utf-8');
        echo json_encode($results, JSON_UNESCAPED_UNICODE);
        // echo Utility::ch_json_encode($results, JSON_UNESCAPED_UNICODE);
        exit;
    }
    else if (is_string($results))
    {
        echo $results;
        exit;
    }

