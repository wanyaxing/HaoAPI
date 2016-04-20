<?php
/*
 * 微信支付通知页面
 */
    date_default_timezone_set ( 'PRC' );
    //加载配置文件
    require_once(__dir__.'/../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

$tmpLog = array ();
$tmpLog ['ip'] = $_SERVER ['REMOTE_ADDR'];
$tmpLog ['user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
$tmpLog ['verify_result'] = 'fail';

    $postStr = file_get_contents('php://input');

    $postArray = json_decode(json_encode(simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)),TRUE);

    if(is_array($postArray) && ($postArray['sign']) == W2PayWx::getSign($postArray) ){
        if($postArray['return_code'] == 'SUCCESS' && $postArray['result_code'] == 'SUCCESS'){

            //todo 这里写相关的业务逻辑
        	$tmpLog ['verify_result'] = 'success';
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[NO OK]]></return_msg></xml>';
        }
    }else{
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[SIGN ERROR]]></return_msg></xml>';
    }



//记录日志
if (is_array($postArray))
{
    $logKeyArray = array('appid','mch_id','device_info','nonce_str','sign','result_code','err_code','err_code_des','openid','is_subscribe','trade_type','bank_type','total_fee','fee_type','cash_fee','cash_fee_type','coupon_fee','transaction_id','out_trade_no','attach','time_end');
    foreach ($logKeyArray as $logKey) {
    	if (array_key_exists($logKey,$postArray))
    	{
    		$tmpLog[$logKey] = $postArray[$logKey];
    	}
    }
}
$tmpLog['create_time'] = date('Y-m-d H:i:s') ;
DBModel::instance('payOrderWxLog')->insert($tmpLog);

