<?php
/*
 * 支付宝服务器异步通知页面
 * https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.1tBt5r&treeId=59&articleId=103666&docType=1
 */
	date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

// 计算得出通知验证结果
$verify_result = W2PayAli::getSignVeryfy();

if ($verify_result) { // 验证成功

	// 获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

	//批量付款数据中转账成功的详细信息

	$success_details = $_POST['success_details'];

	//批量付款数据中转账失败的详细信息
	$fail_details    = $_POST['fail_details'];

	// ///////////////////////////////////////////////////////////////////////
	//todo 这里根据订单支付成功后，处理对应的数据库中的订单的状态。
	//
	//
	// ///////////////////////////////////////////////////////////////////////
	echo "success"; // 请不要修改或删除
	$tmpLog ['verify_result'] = 'success';
} else {
	// 验证失败
	echo "fail";
	$tmpLog ['verify_result'] = 'fail';
}

//记录回调被访问日志
$tmpLog ['ip']         = $_SERVER ['REMOTE_ADDR'];
$tmpLog ['user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
$logKeyArray = array('notify_type','notify_id','sign_type','sign','batch_no','success_num','result_details','notify_time');
foreach ($logKeyArray as $logKey) {
	if (array_key_exists($logKey,$_POST))
	{
		$tmpLog[$logKey] = $_POST[$logKey];
	}
}
$tmpLog['create_time'] = date('Y-m-d H:i:s') ;
DBModel::instance('payTransAliLog')->insert($tmpLog);
