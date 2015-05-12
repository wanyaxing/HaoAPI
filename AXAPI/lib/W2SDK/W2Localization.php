<?php
/**
 * 国际化函数库文件
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */

class W2Localization {

    private static $LOCALE_RESOURCES = array();

    /**
     * 读取指定语言内容,返回字符串(依赖LOCALE_PATH常量定义的国际化语言目录)
     * @param string $p_key 语言key
     * @param string $p_locale 语言标识，默认为zh-cn
     * @return sting 对应的语言内容
     */
    public static function loadText($p_key, $p_locale='zh-cn'){
        if (!array_key_exists($p_locale, W2Localization::$LOCALE_RESOURCES)) {
            if (defined('LOCALIZATION_PATH')) {
                $_lPath = LOCALIZATION_PATH.'/'.$p_locale;
                if(file_exists($_lPath)){
                    W2Localization::$LOCALE_RESOURCES[$p_locale] = W2File::loadArrayByFile($_lPath);
                }
            }
        }
        $_v = $p_key;
        if (array_key_exists($p_locale, W2Localization::$LOCALE_RESOURCES) && array_key_exists($p_key, W2Localization::$LOCALE_RESOURCES[$p_locale])) {
            $_v = W2Localization::$LOCALE_RESOURCES[$p_locale][$p_key];
        }
        return $_v;
    }
}

?>