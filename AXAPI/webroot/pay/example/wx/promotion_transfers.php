<?php
    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');


    $partner_trade_no = 'haoxitechtesttransfersid1461057500';//订单号，请确认唯一。注意引入环境变量，如测试服、正式服不可重复。
    $openid = 'oQ0zpwpfn5uQfj-_7VWCuxdX5URM';
    $amount = 3.68;
    $desc = '给axing付款，测试一下。';

    $result = W2PayWx::promotionTransfers($partner_trade_no,$openid,$amount,$desc);

    print_r($result);
