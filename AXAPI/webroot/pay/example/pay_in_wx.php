<?php
    // 注意：本页面所在的外网路径目录需要被授权，才可以在本页面进行付款。
    // mp.weixin.com -> 微信支付 -》开发配置 -》授权目录
    // 如果是测试页面，别忘了将自己的微信号在上面配置中加入测试白名单。

    date_default_timezone_set ( 'PRC' );

    //加载配置文件
    require_once(__dir__.'/../../../config.php');

    //数据库操作工具
    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

    //加载基础方法
    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');


    //---------------------------------------  这里是临时的openid解决方案，实际开发中，请调用正确的授权逻辑获取openid。

    if (isset($_GET['openid']))                              //从参数里抓取openid
    {
        $openid = $_GET['openid'];                           //当前用户的openid
    }
    else if (isset($_GET['code']))                           //或从参数里抓取code，一般是来自授权成功后的回调地址
    {
        $token = W2Weixin::getUserInfoOfCode($_GET['code']);  //通过code向微信请求用户数据
        if (is_array($token) && isset($token['openid']))
        {
            $openid = $token['openid'];                      //当前用户的openid
        }
    }
    else                                                     //如果获取不了用户数据，转向授权地址。
    {
        header('location:'.W2Weixin::getUrlForWxAuth(null,'snsapi_userinfo'));
        exit;
    }

    if (!isset($openid))
    {
        print('no openid found');exit;
    }
    //-----------------------------------------------------------------------------

    $out_trade_no = 'haoxitechtestorderid'.time();//订单号，请确认唯一。注意引入环境变量，如测试服、正式服不可重复。
    $subject      = '微信内支付标题测试';
    $body         = '微信内支付正文测试';
    $total_fee    = 0.01;//单位元
    $trade_type   = 'JSAPI';
    $payInfo      = W2PayWx::getPayInfo($out_trade_no,$subject,$body,$total_fee,$trade_type,$openid);



?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <title>微信公众号支付测试</title>
</head>
<body>
    <center>
        <?php
            if (isset($token['nickname']))
            {
                printf('你好，来自%s-%s-%s的%s%s<img src="%s" style="width:30px;height:30px;"/><br/>'
                        ,$token['country']
                        ,$token['city']
                        ,$token['province']
                        ,$token['nickname']
                        ,$token['sex']==1?'先生':($token['sex']==2?'女士':'')
                        ,$token['headimgurl']
                    );
            }
            else if (isset($openid))
            {
                print('你好，'.$openid.'<br/>');
            }
        ?>
        <button class="btn btn-block cus-bg-color-green-bright cus-color-white" onclick="callpay()">立即支付</button>
    </center>
<script>
    //调用微信JS api 支付
    function jsApiCall() {
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest',
            <?php echo json_encode($payInfo);?>,
            function (res) {
                WeixinJSBridge.log(res.err_msg);
                if (res.err_msg == 'get_brand_wcpay_request:ok') {
                    alert('支付成功');
                } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                    alert('支付失败');
                }

            }
        );
    }

    function callpay() {
        if (typeof WeixinJSBridge == "undefined") {
            if (document.addEventListener) {
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            } else if (document.attachEvent) {
                document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        } else {
            jsApiCall();
        }
    }


</script>

</body>
</html>
