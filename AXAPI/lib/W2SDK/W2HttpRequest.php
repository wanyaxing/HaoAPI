<?php
/**
 * http请求处理函数库文件
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */
// 141031 edit
// 141019 edit

class W2HttpRequest {


    // ================== http request ==================

    /**
     * 判断目标key是否都存在，多个key可用逗号隔开或组成数组
     * @param  string|array $p_keys 多个key可用逗号隔开的字符串或组成数组
     * @param  bool         $p_allowBlank 是否允许空值
     * @return bool         true/false
     */
    public static function issetRequest($p_keys,$p_allowBlank=true){
        return is_null(static::getUnsetRequest($p_keys,$p_allowBlank),$p_default);
    }

    /**
     * 判断目标key是否都存在，返回首个不存在的key
     * @param  string|array $p_keys 多个key可用逗号隔开的字符串或组成数组
     * @param  bool         $p_allowBlank 是否允许空值
     * @return bool         true/false
     */
    public static function getUnsetRequest($p_keys,$p_allowBlank=true){
        $p_keys = explode(',', $p_keys);
        $unsetKey = null;
        foreach ($p_keys as $p_key) {
            if (!array_key_exists($p_key, $_REQUEST) || (!$p_allowBlank && $_REQUEST[$p_key]==null ))
            {
                $unsetKey = $p_key;
                break;
            }
        }
        return $unsetKey;
    }

    /**
     * 从http请求中获得float值
     * @param string key
     * @param float 最大值
     * @param bool 允许为0
     * @param bool 允许为负数
     * @return null|float value
     */
    public static function getRequestFloat($p_key, $p_maxValue=null, $p_allowZero=true, $p_allowMinus=false,$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if ( isset($_v) && strval(floatval($_v)) === $_v ) {
            $_v = floatval($_v);
            if ( $_v>0 || ($_v==0 && $p_allowZero) || ($_v<0 && $p_allowMinus) ) {
                $_r = (isset($p_maxValue)) ? min(intval($p_maxValue), $_v) : $_v;
            }
        }
        return $_r;
    }

    /**
     * 从http请求中获得int值
     * @param string key
     * @param float 最大值
     * @param bool 允许为0
     * @param bool 允许为负数
     * @return null|int value
     */
    public static function getRequestInt($p_key, $p_maxValue=null, $p_allowZero=true, $p_allowMinus=false,$p_default=null){
        $_r = static::getRequestFloat($p_key, $p_maxValue, $p_allowZero, $p_allowMinus,$p_default,$p_default);
        if (isset($_r)) {
            $_r = intval($_r);
        }
        return $_r;
    }

    /**
     * 从http请求中获得bool值
     * @param string key
     * @return null|true|false
     */
    public static function getRequestBool($p_key,$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if(isset($_v) && strlen(trim($_v))>0) {
            $_r = $_v === '1';
        }
        return $_r;
    }

    /**
     * 从http请求中获得数组
     * @param string key
     * @param bool 内容唯一
     * @param bool 仅允许整数
     * @return null|array value
     */
    public static function getRequestArray($p_key, $p_unique=false, $p_intOnly=false,$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if(isset($_v) && strlen(trim($_v))>0) {
            $_r = array();
            $_a = explode(',', $_v);
            foreach ($_a as $_v) {
                if ($p_intOnly) {
                    if (strval(intval($_v)) === trim($_v)) {
                        array_push($_r, $_v);
                    }
                } else {
                    array_push($_r, $_v);
                }
            }
            if (count($_r)==0) {
                $_r = null;
            } else if ($p_unique) {
                $_r = array_unique($_r);
            }
        }
        return $_r;
    }

    /**
     * 从http请求中获得数组并重组成字符串
     * @param string key
     * @param bool 内容唯一
     * @param bool 仅允许整数
     * @return null|array value
     */
    public static function getRequestArrayString($p_key, $p_unique=false, $p_intOnly=false,$p_default=null){
        $_r = static::getRequestArray($p_key, $p_unique, $p_intOnly,$p_default);
        if (!is_null($_r))
        {
            return implode(',',$_r );
        }
        else
        {
            return null;
        }
    }

    /**
     * 从http请求中获得时间字符串
     * @param string key
     * @param string 时间格式
     * @return null|string value
     */
    public static function getRequestDateTime($p_key, $p_format='Y-m-d H:i:s',$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if(isset($_v) && strlen(trim($_v))>0) {
            $_v = trim($_v);
            $_d = strtotime($_v);
            if($_d ){//&& $_d->format($p_format) === $_v
                $_r = date($p_format,$_d);
            }
        }
        return $_r;
    }

    /**
     * 从http请求中获得日期字符串
     * @param string key
     * @param string 时间格式
     * @return null|string value
     */
    public static function getRequestDate($p_key, $p_format='Y-m-d',$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if(isset($_v) && strlen(trim($_v))>0) {
            $_v = trim($_v);
            $_d = strtotime($_v);
            if($_d ){//&& $_d->format($p_format) === $_v
                $_r = date($p_format,$_d);
            }
        }
        return $_r;
    }

    /**
     * 从http请求中获得字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestString($p_key, $p_allowBlank=true,$p_default=null){
        $_r = $p_default;
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : null;
        if (isset($_v) && ($p_allowBlank || strlen(trim($_v))>0)) {
            $_r = $_v;
        }
        return $_r;
    }


    /**
     * 从http请求中获得Email格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestEmail($p_key, $p_allowBlank=true,$p_default=null){
        $_r = $p_default;
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && ($p_allowBlank || W2String::isEmail($_v))) {
            $_r = $_v;
        }
        return $_r;
    }

    /**
     * 从http请求中获得TELEPHONE格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestTelephone($p_key, $p_allowBlank=true,$p_default=null){
        $_r = $p_default;
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && ($p_allowBlank || W2String::isTelephone($_v))) {
            $_r = $_v;
        }
        return $_r;
    }

    /**
     * 从http请求中获得IP格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestIP($p_key, $p_allowBlank=true,$p_default=null){
        $_r = $p_default;
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && ($p_allowBlank || W2String::isIP($_v))) {
            $_r = $_v;
        }
        return $_r;
    }

    /**
     * 从http请求中获得URL格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestURL($p_key, $p_allowBlank=true,$p_default=null){
        $_r = $p_default;
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && ($p_allowBlank || W2String::isURL($_v))) {
            $_r = $_v;
        }
        return $_r;
    }
}

?>
