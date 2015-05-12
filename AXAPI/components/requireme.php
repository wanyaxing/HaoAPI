<?php

if(!function_exists('___autoload')) {

    function ___autoload($p_className) {
        $_dir = '';
        if (strpos($p_className,'Handler')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/AXBase/handlers';
        }
        else if (strpos($p_className,'Model')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/AXBase/models';
        }
        else if (strpos($p_className,'Controller')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/AXBase/controllers';
        }
        else if (strpos($p_className,'W2')!==false)
        {
            $_dir = AXAPI_ROOT_PATH.'/lib/W2SDK';
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
    spl_autoload_register('___autoload');//自动载入类文件（当用到的类未被引入时，自动引入）
}

?>
