<?php

if(!function_exists('___autoloadHaoApiClassByWanYaXing')) {

    function ___autoloadHaoApiClassByWanYaXing($p_className) {
        $_dir = '';

        if (strpos($p_className,'Handler')!==false)
        {
            $classNameV3 = str_replace('Handler', '', $p_className).'/'.$p_className;
            $_dir = AXAPI_ROOT_PATH.'/mhc/handlers';
        }
        else if (strpos($p_className,'Model')!==false)
        {
            $classNameV3 = str_replace('Model', '', $p_className).'/'.$p_className;
            $_dir = AXAPI_ROOT_PATH.'/mhc/models';
        }
        else if (strpos($p_className,'Controller')!==false)
        {
            $classNameV3 = str_replace('Controller', '', $p_className).'/'.$p_className;
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
            if (isset($classNameV3))
            {
                $classNameV3 = preg_replace_callback('/([A-Za-z])/us', function($matches){
                                                        return '['.strtolower($matches[1]).strtoupper($matches[1]).']';
                                                    }, $classNameV3);
                foreach (glob(AXAPI_ROOT_PATH.'/mhc/'.$classNameV3.'.php') as $_file) {
                    include $_file;
                    return true;
                    break;
                }
            }
            $p_className = strtolower($p_className).'.php';
            foreach (glob($_dir.'/*.php') as $_file) {
                if (strtolower(basename($_file)) == $p_className)
                {
                    include $_file;
                    return true;
                    break;
                }
            }
        }
    }
    spl_autoload_register('___autoloadHaoApiClassByWanYaXing');//自动载入类文件（当用到的类未被引入时，自动引入）
}

?>
