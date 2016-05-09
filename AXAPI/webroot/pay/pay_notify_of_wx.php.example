<?php
/*
 * 微信支付通知页面
 * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7
 */
    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

$postStr = file_get_contents('php://input');

$postArray = json_decode(json_encode(simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)),TRUE);

if(is_array($postArray) && ($postArray['sign']) == W2PayWx::getSign($postArray) ){
    if($postArray['return_code'] == 'SUCCESS')
    {

        // 商户系统的订单号，与请求一致。
        $out_trade_no = $postArray['out_trade_no'];

        // 微信支付订单号
        $trade_no     = $postArray['transaction_id'];

        // 交易状态  SUCCESS/FAIL
        $trade_status = $postArray['result_code'];

        if ($postArray['result_code'] == 'SUCCESS')
        {

            // ///////////////////////////////////////////////////////////////////////
            //todo 这里根据订单支付成功后，处理对应的数据库中的订单的状态。
            //
            //
            // ///////////////////////////////////////////////////////////////////////
        }
    }
    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    $tmpLog ['verify_result'] = 'success';
}else{
    $tmpLog ['verify_result'] = 'fail';
    echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[SIGN ERROR]]></return_msg></xml>';
}



//记录回调被访问日志
$tmpLog ['ip']            = $_SERVER ['REMOTE_ADDR'];
$tmpLog ['user_agent']    = $_SERVER ['HTTP_USER_AGENT'];
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

