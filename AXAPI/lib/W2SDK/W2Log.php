<?php
/**
 * 日志处理函数库
 *
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 *
 */
class W2Log {

    public static $LOG_PATH;

    public static $LOG_LEVELS = array('debug', 'info', 'warn', 'error');

    /**
     * 获取日志内容
     */
    private static function buildLog($p_args, $p_showTrace=true ,$p_showFilePath = false){
        $_logMessage = '';
        if (count($p_args)>0) {
            $_format = array_shift($p_args);
            $_logMessage = trim(count($p_args)==0?$_format:vsprintf($_format, $p_args));
        }

        $_logTrace = array();
        $_dbt = debug_backtrace();
        foreach ($_dbt as $_i => $_d) {
            if(!array_key_exists('file', $_d) || $_d['file']=='' || pathinfo($_d['file'],PATHINFO_BASENAME) == 'w2-php-sdk.php' || pathinfo($_d['file'],PATHINFO_BASENAME) == 'AbstractHandler.php'|| pathinfo($_d['file'],PATHINFO_BASENAME) == 'DbConn.php' || $_d['file']==__file__){
                continue;
            }
            array_push($_logTrace,
                sprintf('%s [%d] %s', $_d['file'], $_d['line'], $_d['function'])
            );
        }

        $_l1 = array_shift($_logTrace);
        $_l1 = substr($_l1, 0, strrpos($_l1, ' '));
        $_log = $_l1.': '.$_logMessage;

        if($p_showTrace){
            $_log .= "\n    ".implode("\n    ", $_logTrace);
        }
        return $_log;
    }

    /**
     * 输出日志
     * @param int 日志级别
     * @param string 日志内容
     */
    private static function log($p_level, $p_logArgs, $p_showTrace=false){
        if (!is_dir(static::$LOG_PATH))
        {
            throw new Exception('日志目录不存在：'.static::$LOG_PATH);
            return false;
        }
        $_logLevel = defined('LOG_LEVEL')?LOG_LEVEL:'info';
        $_fileName = null;
        if (!is_null(static::$LOG_FILENAME)) {
            $_fileName = static::$LOG_FILENAME;
        } else {
            $_dbt = debug_backtrace();
            foreach ($_dbt as $_i => $_d) {
                if(!array_key_exists('file', $_d) || $_d['file']=='' || pathinfo($_d['file'],PATHINFO_BASENAME) == 'w2-php-sdk.php'|| pathinfo($_d['file'],PATHINFO_BASENAME) == 'DbConn.php'|| $_d['file']==__file__){
                    continue;
                }
                $_fileName = pathinfo($_d['file'],PATHINFO_FILENAME);
                break;
            }
        }
        if(!isset($_fileName)) {
            return;
        }
        $_s = array_search(strtolower($_logLevel), W2Log::$LOG_LEVELS);
        if ( $_s=== false || $_s>$p_level) {
            return;
        }
        $_ms = microtime(true);

        $_ms = floor(($_ms-floor($_ms))*1000);

        $_log = sprintf("%s.%-03s %7s %s\n", strftime('%F %T'), $_ms, '['.strtoupper(W2Log::$LOG_LEVELS[$p_level]).']', W2Log::buildLog($p_logArgs, $p_showTrace) );

        $_logFile = sprintf('%s/%s-%s.log',static::$LOG_PATH, $_fileName, strftime('%Y%m%d'));

        if(isset($_logFile)) {
            file_put_contents($_logFile,$_log,FILE_APPEND);
        }
    }

    /**
     * 输出DEBUG级别的日志
     */
    public static function debug(){
        $_a = func_get_args();
        W2Log::log(0, $_a);
    }

    /**
     * 输出INFO级别的日志
     */
    public static function info(){
        $_a = func_get_args();
        W2Log::log(1, $_a);
    }

    /**
     * 输出WARN级别的日志
     */
    public static function warn(){
        $_a = func_get_args();
        W2Log::log(2, $_a);
    }

    /**
     * 输出ERROR级别的日志
     */
    public static function error(){
        $_a = func_get_args();
        W2Log::log(3, $_a, true);
    }

    // use with log4php

    /**
     * 输出TRACE级别的日志
     */
    /*
    public static function trace(){
        $_a = func_get_args();
        W2Log::log(1, W2Log::getLog($_a));
    }
    */

    /**
     * 输出DEBUG级别的日志
     */
    /*
    public static function debug(){
        $_a = func_get_args();
        W2Log::log(2, W2Log::getLog($_a,false));
    }
    */

    /**
     * 输出INFO级别的日志
     */
    /*
    public static function info(){
        $_a = func_get_args();
        W2Log::log(3, W2Log::getLog($_a,false));
    }
    */

    /**
     * 输出WARN级别的日志
     */
    /*
    public static function warn(){
        $_a = func_get_args();
        W2Log::log(4, W2Log::getLog($_a));
    }
    */

    /**
     * 输出ERROR级别的日志
     */
    /*
    public static function error(){
        $_a = func_get_args();
        W2Log::log(5, W2Log::getLog($_a));
    }
    */

    /**
     * 输出FATAL级别的日志
     */
    /*
    public static function fatal(){
        $_a = func_get_args();
        W2Log::log(6, W2Log::getLog($_a));
    }
    */

    /**
     * 获取日志内容
     */
    /*
    private static function getLog($p_args, $p_showTrace=true){
        $_logMessage = '';
        if (count($p_args)>0) {
            $_format = array_shift($p_args);
            $_logMessage = trim(count($p_args)==0?$_format:vsprintf($_format, $p_args));
        }

        $_dbt = debug_backtrace();
        $_logTrace = array();
        foreach ($_dbt as $_i => $_d) {
            if(!array_key_exists('file', $_d) || $_d['file']=='' || pathinfo($_d['file'],PATHINFO_BASENAME) == 'w2-php-sdk.php' || $_d['file']==__file__){
                continue;
            }
            array_push($_logTrace,
                sprintf('%s [%d] %s', $_d['file'], $_d['line'], $_d['function'])
            );
        }

        $_l1 = array_shift($_logTrace);
        $_l1 = substr($_l1, 0, strrpos($_l1, ' '));
        $_log = $_l1.': '.$_logMessage;

        if($p_showTrace){
            $_log .= "\n    ".implode("\n    ", $_logTrace);
        }
        return $_log;
    }
    */

    /**
     * 输出日志
     * @param int 日志级别
     * @param string 日志内容
     */
    /*
    private static function log($p_event, $p_log){
        if(!class_exists('Logger')){
            return;
        }
        $_logger = Logger::getLogger('');
        switch ($p_event) {
            case 1:
                $_logger->trace($p_log);
                break;
            case 2:
                $_logger->debug($p_log);
                break;
            case 3:
                $_logger->info($p_log);
                break;
            case 4:
                $_logger->warn($p_log);
                break;
            case 5:
                $_logger->error($p_log);
                break;
            case 6:
                $_logger->fatal($p_log);
                break;
        }
    }
    */

    /**
     * 输出异常日志
     * @param int 代码
     * @param string 文件
     * @param int 行数
     * @param string 消息
     */
    /*
    public static function printException($p_errorCode, $p_errorFile, $p_errorLine, $p_errorMessage){
        if(!class_exists('Logger')){
            return;
        }
        $_log_level = array(
            0=>'INFO',
            100=>'DEBUG',
            200=>'UNKNOWN_ERROR',
            1=>'ERROR',
            2=>'WARNING',
            4=>'PARSE',
            8=>'NOTICE',
            16=>'CORE_ERROR',
            32=>'CORE_WARNING',
            64=>'COMPILE_ERROR',
            128=>'COMPILE_WARNING',
            256=>'USER_ERROR',
            512=>'USER_WARNING',
            1024=>'USER_NOTICE',
            2048=>'STRICT',
            4096=>'RECOVERABLE_ERROR',
            8192=>'DEPRECATED',
            16384=>'USER_DEPRECATED',
            30719=>'ALL'
        );

        $_log = sprintf("[%s] %s[%d]: %s", $_log_level[$p_errorCode], $p_errorFile, $p_errorLine, $p_errorMessage);
        $_logger = Logger::getLogger('');
        $_logger->error($_log);
    }
    */
}

//静态类的静态变量的初始化不能使用宏，只能用这样的笨办法了。
if (W2SMS::$LOG_PATH == nul && defined('W2LOG_PATH'))
{
    W2SMS::$LOG_PATH      = W2LOG_PATH;
}
if (W2SMS::$LOG_FILENAME == nul && defined('W2LOG_FILENAME'))
{
    W2SMS::$LOG_FILENAME      = W2LOG_FILENAME;
}
