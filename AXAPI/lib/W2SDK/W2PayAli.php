<?php
/**
 * 支付宝、微信服务号、微信开放平台、银联支付
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2PayAli {
    public static $PARTNER              = null;
    public static $SELLER_ID            = null;
    public static $PRIVATE_KEY_PATH     = null;
    public static $ALI_PUBLIC_KEY_PATH     = null;
    public static $NOTIFY_URL           = null;

    /**
     * 计算支付宝支付用的订单信息字符串
     * @param  [type] $out_trade_no 商户网站唯一订单号
     * @param  [type] $subject      商品名称
     * @param  [type] $body         商品详情
     * @param  [type] $total_fee    商品金额（单位：元）
     * @return string               订单信息字符串
     * 举例： partner="2088811898816095"&seller_id="qqlp62193@163.com"&out_trade_no="HaoFrame.136"&subject="应用开发服务费"&body="上海浩兮信息技术有限公司"&total_fee="0.01"&notify_url="qqlp62193@163.com"&service="mobile.securitypay.pay"&payment_type="1"&_input_charset="utf-8"&it_b_pay="30m"&return_url="m.alipay.com"&sign="kUXkkyviowVSfIu2NmfnPERq2M6VsdyE2igVnGR9fMkc45G%2FwmK%2BfBm3uCFLLKSuRW%2FgD6SL9lM0nkZVoL3iotVHA40psz3y6A%2F2uOsizvxTjtRXz4q4qUCWy%2B3L%2FLaVrhUTqwo7TF%2BPawVdjuGQkmNSd6KhQZ27MQRMTzJ%2Fxyo%3D"&sign_type="RSA"
     */
    public static function getPayInfo($out_trade_no,$subject,$body,$total_fee)
    {
        $PARTNER          = static::$PARTNER;
        $SELLER           = static::$SELLER_ID;

        $private_key_path = static::$PRIVATE_KEY_PATH;
        $notify_url       = static::$NOTIFY_URL;

        $orderInfo        = 'partner="' . $PARTNER . '"';

        // 签约卖家支付宝账号
        $orderInfo    .= '&seller_id="' . $SELLER . '"';

        // 商户网站唯一订单号
        $orderInfo    .= '&out_trade_no="' . $out_trade_no . '"';

        // 商品名称
        $orderInfo    .= '&subject="' . $subject . '"';

        // 商品详情
        $orderInfo    .= '&body="' .  $body . '"';

        // 商品金额
        $orderInfo    .= '&total_fee="' .$total_fee  . '"';
        // $orderInfo .= '&total_fee="' .'0.01'  . '"';

        // 服务器异步通知页面路径
        $orderInfo    .= '&notify_url="' . $notify_url . '"';

        // 服务接口名称， 固定值
        $orderInfo    .= '&service="mobile.securitypay.pay"';

        // 支付类型， 固定值
        $orderInfo    .= '&payment_type="1"';

        // 参数编码， 固定值
        $orderInfo    .= '&_input_charset="utf-8"';

        // 设置未付款交易的超时时间
        // 默认30分钟，一旦超时，该笔交易就会自动被关闭。
        // 取值范围：1m～15d。
        // m-分钟，h-小时，d-天，1c-当天（无论交易何时创建，都在0点关闭）。
        // 该参数数值不接受小数点，如1.5h，可转换为90m。
        $orderInfo    .= '&it_b_pay="30m"';

        // extern_token为经过快登授权获取到的alipay_open_id,带上此参数用户将使用授权的账户进行支付
        // $orderInfo .= '&extern_token="' . extern_token . '"';

        // 支付宝处理完请求后，当前页面跳转到商户指定页面的路径，可空
        $orderInfo    .= '&return_url="m.alipay.com"';

        // 调用银行卡支付，需配置此参数，参与签名， 固定值 （需要签约《无线银行卡快捷支付》才能使用）
        // $orderInfo .= '&paymethod="expressGateway"';

        //使用客户的密钥加密
        $sign    = urlencode(W2RSA::rsaSign($orderInfo, $private_key_path));
        $payInfo = $orderInfo . '&sign="' . $sign . '"&' . 'sign_type="RSA"';

        return $payInfo;
    }

    /**
     * 字典根据key值按字母排序后获得签名
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=4_3
     * @param  array $list  参与运算的数据
     * @return string       计算后的签名
     */
    public static function getOrderString($list)
    {
        $post_array = array();
        foreach($list as $k=>$v){
            if($k != 'sign' && $k != 'sign_type' && !is_null($v) && !(is_array($v) && count($v)==0) ){
                $post_array[] = $k .'='. $v;
            }
        }
        sort($post_array);
        return implode('&',$post_array);
    }

    /**
     * 判断POST数据是否可验支付宝家的公钥
     * @param  array $post   数据字典
     * @param  string $sign  校验串
     * @return bool       true
     */
    public static function getSignVeryfy($post=null,$sign=null)
    {
        if (!isset($post))
        {
            $post = $_POST;
        }
        if (!isset($sign) && isset($_POST['sign']))
        {
            $sign = $_POST['sign'];
        }
        $orderString = static::getOrderString($post);
        return W2RSA::rsaVerify($orderString,static::$ALI_PUBLIC_KEY_PATH,$sign);
    }

}

if (W2PayAli::$PARTNER==null && defined('W2PAYALI_PARTNER'))
{
    W2PayAli::$PARTNER                   = W2PAYALI_PARTNER;
    W2PayAli::$SELLER_ID                 = W2PAYALI_SELLER_ID;
    W2PayAli::$PRIVATE_KEY_PATH          = W2PAYALI_PRIVATE_KEY_PATH;
    W2PayAli::$ALI_PUBLIC_KEY_PATH       = W2PAYALI_ALI_PUBLIC_KEY_PATH;
    W2PayAli::$NOTIFY_URL                = W2PAYALI_NOTIFY_URL;
}
