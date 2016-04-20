<?php
    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');


    $out_trade_no = 'haoxitechtestorderid1461057499';//订单号，请确认唯一。注意引入环境变量，如测试服、正式服不可重复。

    $result = W2PayWx::orderQuery($out_trade_no);

    print_r($result);
