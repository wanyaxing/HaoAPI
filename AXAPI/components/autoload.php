<?php

if(!function_exists('___autoloadHaoApiClassByWanYaXing')) {

    function ___autoloadHaoApiClassByWanYaXing($p_className) {
        $_dir = '';
        if (strpos($p_className,'Handler')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/mhc/handlers';
        }
        else if (strpos($p_className,'Model')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/mhc/models';
        }
        else if (strpos($p_className,'Controller')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/mhc/controllers';
        }
        else if (strpos($p_className,'W2')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/lib/W2SDK';
        }
        else
        {//如果目标类没有任何特征，那么就在components目录下找找看。
            $_dir = AXAPI_ROOT_PATH.'/components';
        }
        if ($_dir!='')
        {
            $p_className = strtolower($p_className).'.php';
            foreach (glob($_dir.'/*.php') as $_file) {
                if (strtolower(basename($_file)) == $p_className)
                {
                    include $_file;
                    break;
                }
            }
        }
    }
    spl_autoload_register('___autoloadHaoApiClassByWanYaXing');//自动载入类文件（当用到的类未被引入时，自动引入）
}

?>
