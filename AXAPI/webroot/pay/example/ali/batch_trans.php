<?php
    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

    $batch_no = date('Ymd').'haotransdev'.substr(uniqid(),0,8); //批量付款批次号。11～32位的数字或字母或数字与字母的组合，且区分大小写。注意：批量付款批次号用作业务幂等性控制的依据，一旦提交受理，请勿直接更改批次号再次上传。

    $transList = array(
            array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'asin888@qq.com'
                    ,'trans_name'       =>'万亚星'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给万亚星的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'1458361606@qq.com'
                    ,'trans_name'       =>'唐代正'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给唐代正的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'gabriel1215@aliyun.com'
                    ,'trans_name'       =>'耿加荣'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给耿加荣的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'1282834243@qq.com'
                    ,'trans_name'       =>'朱博'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给朱博的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'969109096@qq.com'
                    ,'trans_name'       =>'朱冰心'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给朱冰心的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'13162961019'
                    ,'trans_name'       =>'向林'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给向林的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'wangtao_0124@163.com'
                    ,'trans_name'       =>'王涛'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给王涛的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'18621986706'
                    ,'trans_name'       =>'黄娟'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给黄娟的小红包-'.rand()
                )
            ,array(
                     'trans_no'         =>date('Ymd').uniqid()
                    ,'trans_account'    =>'xiangjianfu@163.com'
                    ,'trans_name'       =>'向剑夫'
                    ,'trans_fee'        =>0.01
                    ,'trans_desc'       =>'给向剑夫的小红包-'.rand()
                )
        );

    $result = W2PayAli::batchTransMany($batch_no,$transList);

    $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$result['url']."' method='get' target=_blank>";
    while (list ($key, $val) = each ($result['formData'])) {
        $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
    }

    //submit按钮控件请不要含有name属性
    $sHtml = $sHtml."<input type='submit' value='开始批量付款'></form>";

    // $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>支付宝批量付款</title>
</head>
<body>
    <?php
        echo $sHtml;
    ?>
</body>
</html>
