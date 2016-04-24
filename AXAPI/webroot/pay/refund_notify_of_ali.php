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
	if ($_POST['trade_status'] == 'TRADE_FINISHED') {
		$isSuccess = true;
		// 判断该笔订单是否在商户网站中已经做过处理
		// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		// 如果有做过处理，不执行商户的业务程序

		// 注意：
		// 该种交易状态只在两种情况下出现
		// 1、开通了普通即时到账，买家付款成功后。
		// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

		// 调试用，写文本函数记录程序运行情况是否正常
		// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	} else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		$isSuccess = true;
		// 判断该笔订单是否在商户网站中已经做过处理
		// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		// 如果有做过处理，不执行商户的业务程序
		// 注意：
		// 该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

		// 调试用，写文本函数记录程序运行情况是否正常
		// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
	}

	// 获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

	//批次号
	$batch_no = $_POST['batch_no'];

	//批量退款数据中转账成功的笔数
	$success_num = $_POST['success_num'];

	//批量退款数据中的详细信息
	$result_details = $_POST['result_details'];

	$isSuccess = false;
	if ($isSuccess) {
		// ///////////////////////////////////////////////////////////////////////
		//todo 这里根据订单支付成功后，处理对应的数据库中的订单的状态。
		//
		//
		// ///////////////////////////////////////////////////////////////////////
	}
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
DBModel::instance('payRefundAliLog')->insert($tmpLog);
