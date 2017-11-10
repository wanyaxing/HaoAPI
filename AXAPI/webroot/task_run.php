<?php
ob_start();
//这里一定要输出内容给1.php,虽然什么都行,没有内容会失灵的
echo '{"code":0, "msg":"task is running."}';
$size = ob_get_length();
header("Content-Length: $size");
header("Connection: Close");
//使用ob输出缓冲区,提前告诉浏览器脚本运行结束,不同的操作系统用的函数不一样，都写上最好
ob_end_flush();
ob_flush();
flush();
//请求相应已经完成了，后面可以写耗时代码了

date_default_timezone_set('Asia/Shanghai');//设定时区

define("AX_TIMER_START", microtime (true));//记录请求开始时间


sleep(1);//延时测试
set_time_limit(0);

//加载配置文件
require_once(__dir__.'/../config.php');

//数据库操作工具
require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

//加载基础方法
require_once(AXAPI_ROOT_PATH.'/components/Utility.php');


/**
 * 插入日志，之所以方法放这里，是因为index.php代码改动最少，这个方法存活率最高，因为是用来记日志的嘛。
 * @param  string|array $p_content [description]
 * @param  string $p_type    类型，用于组成文件名
 */
function file_put_log($p_content='',$p_type='access')
{
    file_put_contents(sprintf('%s/%s-%s.log'
                        ,AXAPI_ROOT_PATH.'/logs/'
                        ,$p_type
                        ,strftime('%Y%m%d'))
                        ,sprintf("[%s] (%s s) [%s] [%s->%s]: %s [%s]\n"
                                    ,W2Time::microtimetostr(AX_TIMER_START)
                                    ,number_format(microtime (true) - AX_TIMER_START, 5, '.', '')
                                    ,$_SERVER['REQUEST_METHOD']
                                    ,$GLOBALS['class'], $GLOBALS['method']
                                    ,is_string($p_content)?$p_content:Utility::json_encode_unicode($p_content)
                                    ,Utility::json_encode_unicode($GLOBALS['result'])
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
        //记录错误日志
        file_put_log($_REQUEST,'task-error');
        file_put_log($last_error,'task-error');
        exit;
    }
}
register_shutdown_function('catch_fatal_error');

if (isset($_POST['data']))
{
    try {
        /*根据提交来的参数继续执行代码*/
        $result = W2Task::taskRun();
        /*下面的代码主要是为了提取class和method，记录日志，无其他用。*/
        $data = W2String::rc4(TASK_DATA_RANDCODE,W2String::hex2asc($_POST['data']));
        list($class,$method,$args) = unserialize($data);
    } catch (Exception $e) {
        $result = $e->getMessage();
        file_put_log($result,'task-error');
    }
}
file_put_log(json_encode($_REQUEST),'task');

