<?php
    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');



    $batch_no = date('Ymd').'haorefunddev'.substr(uniqid(),0,8);

    $refundList = array(
            array(
                     'out_trade_no'=>'2016042421001004080210384626'
                    ,'refund_fee'  =>0.01
                    ,'desc'        =>'退给阿星第一笔交易的退款'
                )
            ,array(
                     'out_trade_no'=>'2016042421001004080208404361'
                    ,'refund_fee'  =>0.01
                    ,'desc'        =>'退给阿星第二笔交易的退款'
                )
        );

    $result = W2PayAli::refundMany($batch_no,$refundList);

    $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$result['url']."' method='POST' target=_blank>";
    while (list ($key, $val) = each ($result['formData'])) {
        $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
    }

    //submit按钮控件请不要含有name属性
    $sHtml = $sHtml."<input type='submit' value='提交退款申请'></form>";

    // $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>支付宝即时到账批量退款有密接口接口</title>
</head>
<body>
    <?php
        echo $sHtml;
    ?>
</body>
</html>
