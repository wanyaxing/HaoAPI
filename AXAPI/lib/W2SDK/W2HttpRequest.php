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
     * 从http请求中获得匹配正则的字符串
     * @param string key
     * @param string reg 如 '/^[\s\S]*$/
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestMatch($p_key, $p_match='/^[\s\S]*$/', $p_allowBlank=true,$p_default=null)
    {
        $_v = array_key_exists($p_key, $_REQUEST) ? $_REQUEST[$p_key] : $p_default;
        if (isset($_v) && $_v!=$p_default)
        {
            if ($_v==='' && !$p_allowBlank)
            {
                throw new Exception('参数'.$p_key.'不接受空字符。');
            }

            if ($p_match!=null && !preg_match($p_match,$_v))
            {
                throw new Exception('参数'.$p_key.'请输入正确的值。');
            }

        }
        if (defined('IS_AX_DEBUG')){print("\n");var_export($p_key);print(" : ");var_export($_v);}
        return $_v;
    }




    /**
     * 从http请求中获得字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestString($p_key, $p_allowBlank=true,$p_default=null,$p_lenMin=null,$p_lenMax=null){

        $_v = static::getRequestMatch($p_key,null,$p_allowBlank,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            if (isset($p_lenMin) && strlen($_v)<$p_lenMin)
            {
                throw new Exception('参数'.$p_key.'必须包含'.$p_lenMin.'个以上的字符哦。');
            }
            if (isset($p_lenMax) && strlen($_v)>$p_lenMax)
            {
                throw new Exception('参数'.$p_key.'最多只能包含'.$p_lenMax.'个以内的字符哦。');
            }
        }
        return $_v;
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
        $_v = static::getRequestMatch($p_key,null,false,$p_default);

        if (isset($_v) && $_v!=$p_default)
        {
            if (strval(floatval($_v)) !== $_v)
            {
                throw new Exception('参数'.$p_key.'请使用正确的数字值。');
            }
            $_v = floatval($_v);


            if ($_v==0 && !$p_allowZero)
            {
                throw new Exception('参数'.$p_key.'的值不可以d为0。');
            }

            if (isset($p_allowMinus) && $_v<$p_allowMinus)
            {
                throw new Exception('参数'.$p_key.'只接受大于等于'.$p_allowMinus.'的数字。');
            }

            if (isset($p_maxValue) && $_v > $p_maxValue)
            {
                throw new Exception('参数'.$p_key.'只接受小于等于'.$p_maxValue.'的数字。');
            }
        }

        return $_v;
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

        $_v = static::getRequestFloat($p_key, $p_maxValue, $p_allowZero, $p_allowMinus,$p_default);
        if (isset($_v) && $_v!==$p_default)
        {
            if (strval(intval($_v)) != $_v)
            {
                throw new Exception('参数'.$p_key.'请使用正确的数字值。');
            }
            $_v = intval($_v);

        }
        return $_v;
    }

    /**
     * 从http请求中获得bool值
     * @param string key
     * @return null|true|false
     */
    public static function getRequestBool($p_key,$p_default=null){

        $_v = static::getRequestInt($p_key , 1 , true , 0 , $p_default);

        if (isset($_v) && $_v!=$p_default)
        {
            $_v = $_v === 1;
        }

        return $_v;
    }

    /**
     * 从http请求中获得数组
     * @param string key
     * @param bool 内容唯一
     * @param bool 仅允许整数
     * @return null|array value
     */
    public static function getRequestArray($p_key, $p_unique=false, $p_intOnly=false,$p_default=null){

        $_v = static::getRequestString($p_key,true,$p_default);

        if (isset($_v) && $_v!=$p_default)
        {
            $_r = array();
            $_a = explode(',', $_v);
            foreach ($_a as $_v) {
                if ($_v!='')
                {
                    if ($p_intOnly) {
                        if (strval(intval($_v)) !== trim($_v)) {
                            throw new Exception('参数'.$p_key.'请使用正确数字与逗号组成的字符串。');
                        }
                    }
                    array_push($_r, $_v);
                }
            }
            if (count($_r)==0) {
                $_r = null;
            } else if ($p_unique) {
                $_r = array_unique($_r);
            }
            $_v = $_r;
        }
        return $_v;
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

        $_v = static::getRequestString($p_key,false,$p_default);

        if (isset($_v) && $_v!=$p_default)
        {
            $_v = W2Time::timetostr(W2Time::strtotime($_v),$p_format);
        }
        return $_v;

    }

    /**
     * 从http请求中获得日期字符串
     * @param string key
     * @param string 时间格式
     * @return null|string value
     */
    public static function getRequestDate($p_key, $p_format='Y-m-d',$p_default=null){
        $_v = static::getRequestDateTime($p_key, $p_format,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            // $_v = W2Time::timetostr(W2Time::strtotime($_v),$p_format);
        }
        return $_v;
    }

    /**
     * 从http请求中获得Email格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestEmail($p_key, $p_allowBlank=true,$p_default=null){
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            if (!W2String::isEmail($_v))
            {
                throw new Exception('参数'.$p_key.'请使用正确格式的邮箱地址。');
            }
        }
        return $_v;
    }

    /**
     * 从http请求中获得TELEPHONE格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestTelephone($p_key, $p_allowBlank=true,$p_default=null){
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            if (!W2String::isTelephone($_v))
            {
                throw new Exception('参数'.$p_key.'请使用正确格式的手机号码。');
            }
        }
        return $_v;
    }

    /**
     * 从http请求中获得IP格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestIP($p_key, $p_allowBlank=true,$p_default=null){
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            if (!W2String::isIP($_v))
            {
                throw new Exception('参数'.$p_key.'请使用正确格式的IP地址。');
            }
        }
        return $_v;

    }

    /**
     * 从http请求中获得URL格式字符串
     * @param string key
     * @param bool 允许空白
     * @return null|string value
     */
    public static function getRequestURL($p_key, $p_allowBlank=true,$p_default=null){
        $_v = static::getRequestString($p_key, $p_allowBlank,$p_default);
        if (isset($_v) && $_v!=$p_default)
        {
            if (!W2String::isURL($_v))
            {
                throw new Exception('参数'.$p_key.'请使用正确格式的网址。');
            }
        }
        return $_v;

    }
}

?>
