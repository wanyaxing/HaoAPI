<?php
/**
 * HaoConnect的基类，说是基类，其实是用来做项目配置用的
 * @package HaoConnect
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(__dir__.'/HaoHttpClient.php');

class HaoConfig {
    Public static $Clientinfo    = 'haoFrame-client';               //应用信息
    Public static $Clientversion = '1.0';                           //使用本类所在客户端的版本号
    Public static $SECRET_HAX_CONNECT = 'secret=????'; //加密秘钥，这里用的是2号设备类型的密钥
    Public static $apiHost = 'api.???.com'; //加密秘钥，这里用的是2号设备类型的密钥
}
