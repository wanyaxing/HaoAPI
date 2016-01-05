<?php
/**
* HaoConnect专属的静态方法类
* @package HaoConnnect
* @author axing
* @version 0.1
*/

//自动载入类文件（当用到的类未被引入时，自动引入）
if(!function_exists('__haoConnectAutoLoad')) {
    function __haoConnectAutoLoad($pClassName) {
        $_dir = '';
        if (strpos($pClassName,'Result')!==false)
        {
            $_dir = __dir__.'/results';
        }
        else if (strpos($pClassName,'Connect')!==false)
        {
            $_dir = __dir__.'/connects';
        }
        if ($_dir!='')
        {
            $pClassName = strtolower($pClassName).'.php';
            foreach (glob($_dir.'/*.php') as $_file) {
                if (strtolower(basename($_file)) == $pClassName)
                {
                    include $_file;
                    break;
                }
            }
        }
    }
    spl_autoload_register('__haoConnectAutoLoad');
}

class HaoUtility {

	/** 判断目标变量是否某类型的model */
	public static function isModelTypeWithTarget($target,$modelType)
	{
		return is_object($target) && is_subclass_of($target,'HaoResult') && $target->isModelType($modelType);
	}

	/** 将数组里的key路径遍历取出 */
	public static function getKeyIndexArray($targetArray)
	{
        $keyIndex = array();
        if (is_array($targetArray))
        {
            foreach ($targetArray as $key => $value) {
                $keyIndex[] = $key;
                if (is_array($value))
                {
                    foreach (static::getKeyIndexArray($value) as $valueSecond) {
                        $keyIndex[] = $key.'>'.$valueSecond;
                    }
                }
            }
        }
        return $keyIndex;
	}

    /** 字符串转换。驼峰式字符串（首字母小写） */
    public static function camelCase($str)
    {
        //使用空格隔开后，每个单词首字母大写
        $str = ucwords(str_replace('_', ' ', $str));
        //小写字符串的首字母，然后删除空格
        $str = str_replace(' ','',lcfirst($str));
        $str = str_replace('Id','ID',$str);
        return $str;
    }

    /** 字符串转换。驼峰转换成下划线的形式 */
    public static function under_score($str) {
        $str = str_replace('ID','Id',$str);
        return strtolower(ltrim(preg_replace_callback('/[A-Z]/', function ($mathes) { return '_' . strtolower($mathes[0]); }, $str), '_'));
    }


    /**
     * 保存Cookie
     * @param string key
     * @param string value
     * @param int 有效时间（秒），默认至浏览器session进程结束时消失
     * @param string path，默认为/
     */
    public static function storeCookie($p_key, $p_value, $p_time=null, $p_path='/') {
        setcookie($p_key, $p_value, $p_value==null?time()-1:(is_int($p_time)?time()+$p_time:0), $p_path);
    }
}
