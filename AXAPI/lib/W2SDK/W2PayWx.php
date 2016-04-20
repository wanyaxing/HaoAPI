<?php
/**
 * 微信开放平台支付（调用微信APP并支付）
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2PayWx {
    public static $APPID           = null; //公众号ID
    public static $MCH_ID          = null; //商户号
    public static $SIGN_KEY        = null; //API密钥 （微信支付-API安全-设置API密钥）
    public static $NOTIFY_URL      = null; //支付成功，回调地址，建议使用本服务器上外界可访问的公开网址。
    public static $APICLIENT_CERT  = null; //退款用密钥文件
    public static $APICLIENT_KEY   = null; //退款用密钥文件

    /**
     * 统一下单
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_1
     * @param  string $out_trade_no 订单对应的随机字符串，不长于32位。
     * @param  string $subject      标题，一般用于附加数据
     * @param  string $body         商品描述
     * @param  string $total_fee    订单总金额，单位为元（在传输到微信前会被转换成分）
     * @param  string $trade_type   交易类型 取值如下：JSAPI，NATIVE，APP，WAP
     * @param  string $openid       用户在商户appid下的唯一标识。 当交易类型为JSAPI时，此参数必传.
     * @return array                客户端或网页可用的发起交易的数据包
     */
    public static function getPayInfo($out_trade_no,$subject,$body,$total_fee,$trade_type='APP',$openid=null) {

        $xmlArray                                   = array();                //发数据给微信服务器用的xml格式
        $xmlArray['appid']                          = static::$APPID;         //公众号ID
        $xmlArray['mch_id']                         = static::$MCH_ID;        //商户号
        $xmlArray['nonce_str']                      = md5($out_trade_no);     //订单对应的随机字符串，不长于32位。
        $xmlArray['out_trade_no']                   = $out_trade_no;          //商户订单号
        $xmlArray['body']                           = $body;                  //商品描述
        $xmlArray['attach']                         = $subject;               //附加数据
        $xmlArray['spbill_create_ip']               = Utility::getCurrentIP();//终端IP APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。
        $xmlArray['notify_url']                     = static::$NOTIFY_URL;    //通知地址 接收微信支付异步通知回调地址

        $money                                      = $total_fee * 100;       //元要转化成分
        $xmlArray['total_fee']                      = $money;                 //订单总金额，单位为分，只能为整数

        $xmlArray['trade_type']                     = $trade_type;            //交易类型 取值如下：JSAPI，NATIVE，APP，WAP

        if (isset($openid))
        {
            $xmlArray['openid']                     = $openid;                //用户在商户appid下的唯一标识。 //JSAPI，此参数必传.
        }

        $xmlArray['sign']                           = static::getSign($xmlArray);

        if ($xmlArray['trade_type']=='JSAPI' && !isset($xmlArray['openid']))
        {
            return 'no openid found';
        }

        $xmlData = static::arrayToXml($xmlArray);
        $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/pay/unifiedorder','post',$xmlData);
        $postArray = static::xmlToArray(($postStr));

        if(is_array($postArray) && ($postArray['sign']) == (static::getSign($postArray)) ){
            if($postArray['return_code'] == 'SUCCESS' && $postArray['result_code'] == 'SUCCESS'){
                $appid       = $postArray['appid'];
                $noncestr    = $postArray['nonce_str'];
                $timestamp   = (string)time();
                $prepayid    = $postArray['prepay_id'];
                $package     = 'Sign=WXPay';
                $partnerid   = static::$MCH_ID;

                $result = array();
                if ($trade_type == 'JSAPI')
                {
                    $package     = 'prepay_id='.$prepayid;


                    $result['appId']     = $appid;
                    $result['nonceStr']  = $noncestr;
                    $result['package']   = $package;
                    $result['timeStamp'] = $timestamp;
                    $result['signType']  = 'MD5';
                    $result['paySign']  = static::getSign($result);
                    return $result;
                }
                else
                {
                    $result['appid']     = $appid;
                    $result['noncestr']  = $noncestr;
                    $result['package']   = $package;
                    $result['partnerid'] = $partnerid;
                    $result['timestamp'] = $timestamp;
                    $result['prepayid']  = $prepayid;
                    $result['sign']  = static::getSign($result);
                    return $result;
                }
            }
        }
        else
        {
            throw new Exception('sign from wx callback is error');
        }

        return 'error when connect with wx';
    }

    /**
     * 生成链接供二维码展示
     * @param  string $out_trade_no   商户系统内部的订单号 或者 商品ID
     * @return [type]               [description]
     */
    public static function getUrlForQRcode($out_trade_no){
        $xmlArray                                     = array();                 //发数据给微信服务器用的xml格式
        $xmlArray['appid']                            = static::$APPID;          //公众号ID
        $xmlArray['mch_id']                           = static::$MCH_ID;         //商户号
        $xmlArray['time_stamp']                       = time();                 //系统当前时间
        $xmlArray['nonce_str']                        = md5(uniqid());            //订单对应的随机字符串，不长于32位。
        $xmlArray['product_id']                       = $out_trade_no;                 //商户定义的商品id 或者订单号

        $xmlArray['sign']                             = static::getSign($xmlArray);

        $query = W2Array::sortAndBuildQuery($xmlArray);

        return 'weixin://wxpay/bizpayurl?'.$query;
    }


    /**
     * 查询订单情况
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_2&index=4
     * @param  string $out_trade_no   商户系统内部的订单号
     * @param  string $transaction_id 微信订单号
     * @return array                 查询到的数据
其中字段： trade_state
        交易状态   :
        SUCCESS—支付成功
        REFUND—转入退款
        NOTPAY—未支付
        CLOSED—已关闭
        REVOKED—已撤销（刷卡支付）
        USERPAYING--用户支付中
        PAYERROR--支付失败(其他原因，如银行返回失败)
     */
    public static function orderQuery($out_trade_no=null, $transaction_id=null) {

        $xmlArray                                     = array();                 //发数据给微信服务器用的xml格式
        $xmlArray['appid']                            = static::$APPID;          //公众号ID
        $xmlArray['mch_id']                           = static::$MCH_ID;         //商户号
        $xmlArray['nonce_str']                        = md5(uniqid());            //订单对应的随机字符串，不长于32位。
        if (isset($out_trade_no))
        {
            $xmlArray['out_trade_no']                 = $out_trade_no;           //商户系统内部的订单号
        }
        else if (isset($transaction_id))
        {
            $xmlArray['transaction_id']               = $transaction_id;         //微信订单号
        }


        $xmlArray['sign']                             = static::getSign($xmlArray);

        $xmlData = static::arrayToXml($xmlArray);
        $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/pay/orderquery','post',$xmlData);
        $postArray = static::xmlToArray(($postStr));

        if( ($postArray['sign']) == (static::getSign($postArray)) )
        {
            if($postArray['return_code'] == 'SUCCESS' )
            {
                if ($postArray['result_code'] == 'SUCCESS')
                {
                    return $postArray;
                }
            }
        }
        else
        {
            throw new Exception('sign from wx callback is error');
        }
        return $postStr;
    }

    /**
     * 关闭订单
以下情况需要调用关单接口：商户订单支付失败需要生成新单号重新发起支付，
要对原订单号调用关单，避免重复支付；
系统下单后，用户支付超时，系统退出不再受理，避免用户继续，请调用关单接口。
注意：订单生成后不能马上调用关单接口，最短调用时间间隔为5分钟。
     * @param  string $out_trade_no   商户系统内部的订单号
     * @return array    操作结果
     */
    public static function closeOrder($out_trade_no) {

        $xmlArray                                     = array();                 //发数据给微信服务器用的xml格式
        $xmlArray['appid']                            = static::$APPID;          //公众号ID
        $xmlArray['mch_id']                           = static::$MCH_ID;         //商户号
        $xmlArray['nonce_str']                        = md5(uniqid());            //订单对应的随机字符串，不长于32位。
        $xmlArray['out_trade_no']                 = $out_trade_no;           //商户系统内部的订单号

        $xmlArray['sign']                             = static::getSign($xmlArray);

        $xmlData = static::arrayToXml($xmlArray);
        $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/pay/closeorder','post',$xmlData);
        $postArray = static::xmlToArray(($postStr));

        if( ($postArray['sign']) == (static::getSign($postArray)) ){
            if($postArray['return_code'] == 'SUCCESS'){
                return $postArray;
            }
        }
        else
        {
            throw new Exception('sign from wx callback is error');
        }
        return $postStr;
    }


    /**
     * 申请退款
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_4&index=6
     * @param  string $out_trade_no  商户订单号
     * @param  string $total_fee     订单总金额，单位为元
     * @param  string $out_refund_no 退款行为的订单号，默认就是商户订单号
     * @param  string $refund_fee    此次退款的金额，单位为元，默认和订单总金额一致
     * @param  string $op_user_id    操作员帐号, 默认为商户号
     * @return string                SUCCESS
     */
    public static function refund($out_trade_no,$total_fee,$out_refund_no=null,$refund_fee=null,$op_user_id=null) {

        $xmlArray                               = array();               //发数据给微信服务器用的xml格式
        $xmlArray['appid']                      = static::$APPID;        //公众号ID
        $xmlArray['mch_id']                     = static::$MCH_ID;       //商户号
        $xmlArray['nonce_str']                  = md5($out_trade_no);    //订单对应的随机字符串，不长于32位。
        $xmlArray['out_trade_no']               = $out_trade_no;         //商户订单号
        if (is_null($out_refund_no)) { $out_refund_no = $out_trade_no; } //默认退款订单号就是订单号
        $xmlArray['out_refund_no']              = $out_refund_no;        //商户系统内部的退款单号，商户系统内部唯一，同一退款单号多次请求只退一笔
        $money                                  = $total_fee * 100;      //元要转化成分
        $xmlArray['total_fee']                  = $money;                //订单总金额，单位为分，只能为整数
        if (is_null($refund_fee)){$refund_fee   = $total_fee;}           //默认全额退款
        $refundMoney                            = $refund_fee * 100;     //元要转化成分
        $xmlArray['refund_fee']                 = $refundMoney;          //退款总金额，订单总金额，单位为分，只能为整数
        // $xmlArray['refund_fee_type  ']       = 'CNY';                 //货币类型，符合ISO 4217标准的三位字母代码，默认人民币：CNY
        if (is_null($op_user_id)){$op_user_id   = static::$MCH_ID;}
        $xmlArray['op_user_id']                 = $op_user_id;           //操作员帐号, 默认为商户号

        $xmlArray['sign']                       = static::getSign($xmlArray);

        if( isset(static::$APICLIENT_CERT,static::$APICLIENT_KEY))
        {
            $_curl = curl_init();
            curl_setopt($_curl,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($_curl,CURLOPT_SSLCERT,static::$APICLIENT_CERT);
            curl_setopt($_curl,CURLOPT_SSLKEY,static::$APICLIENT_KEY);
            $xmlData = static::arrayToXml($xmlArray);
            $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/secapi/pay/refund','post',$xmlData,null,30,'body',$_curl);
            $postArray = static::xmlToArray(($postStr));

            if( ($postArray['sign']) == (static::getSign($postArray)) ){
                if($postArray['return_code'] == 'SUCCESS' && $postArray['result_code'] == 'SUCCESS'){
                    return $postArray;
                }
            }
            else
            {
                throw new Exception('sign from wx callback is error');
            }
        }
        return 'no pem file found';
    }


    /**
     * 查询退款情况
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=9_5&index=7
     * 以下参数，四选一即可。
     * @param  string $out_trade_no   商户系统内部的订单号
     * @param  string $out_refund_no  商户侧传给微信的退款单号
     * @param  string $transaction_id 微信订单号
     * @param  string $refund_id      微信生成的退款单号，在申请退款接口有返回
     * @return array                 查询到的数据
其中字段：refund_status_$n
        退款状态：
        SUCCESS—退款成功
        FAIL—退款失败
        PROCESSING—退款处理中
        NOTSURE—未确定，需要商户原退款单号重新发起
        CHANGE—转入代发，退款到银行发现用户的卡作废或者冻结了，
                导致原路退款银行卡失败，资金回流到商户的现金帐号，
                需要商户人工干预，通过线下或者财付通转账的方式进行退款。
     */
    public static function refundQuery($out_trade_no=null, $out_refund_no=null, $transaction_id=null, $refund_id=null ) {

        $xmlArray                                     = array();                 //发数据给微信服务器用的xml格式
        $xmlArray['appid']                            = static::$APPID;          //公众号ID
        $xmlArray['mch_id']                           = static::$MCH_ID;         //商户号
        $xmlArray['nonce_str']                        = md5(uniqid());            //订单对应的随机字符串，不长于32位。
        if (isset($out_trade_no))
        {
            $xmlArray['out_trade_no']                 = $out_trade_no;           //商户系统内部的订单号
        }
        else if (isset($out_refund_no))
        {
            $xmlArray['out_refund_no']                = $out_refund_no;          //商户侧传给微信的退款单号
        }
        else if (isset($transaction_id))
        {
            $xmlArray['transaction_id']               = $transaction_id;         //微信订单号
        }
        else if (isset($refund_id))
        {
            $xmlArray['refund_id']                    = $refund_id;              //微信生成的退款单号，在申请退款接口有返回
        }

        $xmlArray['sign']                             = static::getSign($xmlArray);

        $xmlData = static::arrayToXml($xmlArray);
        $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/pay/refundquery','post',$xmlData);
        $postArray = static::xmlToArray(($postStr));

        if( ($postArray['sign']) == (static::getSign($postArray)) ){
            // if($postArray['return_code'] == 'SUCCESS' && $postArray['result_code'] == 'SUCCESS'){
            // }
            return $postArray;
        }
        else
        {
            throw new Exception('sign from wx callback is error');
        }
        return $postStr;
    }


    /**
     * 企业付款
     * https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_2
     * @param  string $partner_trade_no 商户订单号，需保持唯一性
     * @param  string $openid           商户appid下，某用户的openid
     * @param  float $amount           企业付款金额，单位为分，默认最少1元。
     * @param  string $desc             企业付款操作说明信息。必填。
     * @param  string $check_name       是否校验姓名，默认NO_CHECK
     * @param  string $re_user_name     如果需要校验姓名，此处传输真实姓名
     * @return array
     */
    public static function promotionTransfers($partner_trade_no,$openid,$amount,$desc,$check_name='NO_CHECK',$re_user_name=null) {

        $xmlArray                                   = array();                   //发数据给微信服务器用的xml格式
        $xmlArray['mch_appid']                      = static::$APPID;            //微信分配的公众账号ID（企业号corpid即为此appId）
        $xmlArray['mchid']                         = static::$MCH_ID;           //商户号
        $xmlArray['nonce_str']                      = md5(uniqid());             //订单对应的随机字符串，不长于32位。
        $xmlArray['partner_trade_no']               = $partner_trade_no;         //商户订单号，需保持唯一性
        $xmlArray['openid']                         = $openid;                   //商户appid下，某用户的openid
// NO_CHECK：不校验真实姓名
// FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账）
// OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
        if (!is_null($re_user_name))
        {
            $xmlArray['check_name']                 = 'NO_CHECK';
            $xmlArray['re_user_name']               = $re_user_name;                //收款用户真实姓名。 如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
        }
        else
        {
            $xmlArray['check_name']                 = $check_name;
        }

        $xmlArray['amount']                         = $amount * 100;                //企业付款金额，单位为分
        $xmlArray['desc']                           = $desc;                        //企业付款操作说明信息。必填。
        $xmlArray['spbill_create_ip']               = Utility::getCurrentIP();      //调用接口的机器Ip地址

        $xmlArray['sign']                           = static::getSign($xmlArray);

        if( isset(static::$APICLIENT_CERT,static::$APICLIENT_KEY))
        {
            $_curl = curl_init();
            curl_setopt($_curl,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($_curl,CURLOPT_SSLCERT,static::$APICLIENT_CERT);
            curl_setopt($_curl,CURLOPT_SSLKEY,static::$APICLIENT_KEY);
            $xmlData = static::arrayToXml($xmlArray);
            $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers','post',$xmlData,null,30,'body',$_curl);
            $postArray = static::xmlToArray(($postStr));

            if(is_array($postArray)){
                // if($postArray['return_code'] == 'SUCCESS')
                // {
                //     if ($postArray['result_code'] == 'SUCCESS')
                //     {
                //     }
                // }
                return $postArray;
            }
            else
            {
                var_export($postStr);
                exit;
            }
        }
        return 'no pem file found';
    }

    /**
     * 查询企业付款结果
     * https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_3
     * @param  string $partner_trade_no 商户订单号，需保持唯一性
     * @return array
     */
    public static function getTransferInfo($partner_trade_no) {

        $xmlArray                                   = array();                   //发数据给微信服务器用的xml格式
        $xmlArray['appid']                          = static::$APPID;            //微信分配的公众账号ID（企业号corpid即为此appId）
        $xmlArray['mch_id']                         = static::$MCH_ID;           //商户号
        $xmlArray['nonce_str']                      = md5(uniqid());             //订单对应的随机字符串，不长于32位。
        $xmlArray['partner_trade_no']               = $partner_trade_no;         //商户订单号，需保持唯一性

        $xmlArray['sign']                           = static::getSign($xmlArray);

        if( isset(static::$APICLIENT_CERT,static::$APICLIENT_KEY))
        {
            $_curl = curl_init();
            curl_setopt($_curl,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($_curl,CURLOPT_SSLCERT,static::$APICLIENT_CERT);
            curl_setopt($_curl,CURLOPT_SSLKEY,static::$APICLIENT_KEY);
            $xmlData = static::arrayToXml($xmlArray);
            $postStr = W2Web::loadStringByUrl('https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo','post',$xmlData,null,30,'body',$_curl);
            $postArray = static::xmlToArray(($postStr));

            if(is_array($postArray)){
                // if($postArray['return_code'] == 'SUCCESS')
                // {
                //     if ($postArray['result_code'] == 'SUCCESS')
                //     {
                //     }
                // }
                return $postArray;
            }
            else
            {
                var_export($postStr);
                exit;
            }
        }
        return 'no pem file found';
    }


    /**
     * 将字典转化成xml
     * @param  array $array
     * @return string
     */
    public static function arrayToXml($array,$isRoot=true)
    {
        if (!is_array($array))
        {
            if (is_numeric($array)){
                return $array;
            }else{
                return '<![CDATA['.$array.']]>';
            }
        }
        $xmalData = array();
        if ($isRoot)
        {
            $xmlData[] = '<xml>';
        }
        foreach($array as $key=>$value)
        {
            $xmlData[] = '<'.$key.'>'.static::arrayToXml($value,false).'</'.$key.'>';
        }
        if ($isRoot)
        {
            $xmlData[] = '</xml>';
        }
        return implode('',$xmlData);
    }

    /**
     * 将xml转化成字典
     * @param string $xml
     * @return array
     */
    public static function xmlToArray($xml,$isRoot=true)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)),TRUE);
    }

    /**
     * 字典根据key值按字母排序后获得签名
     * https://pay.weixin.qq.com/wiki/doc/api/app/app.php?chapter=4_3
     * @param  array $list  参与运算的数据
     * @return string       计算后的签名
     */
    public static function getSign($list)
    {
        $post_array = array();
        foreach($list as $k=>$v){
            if($k != 'sign' && !is_null($v) && !(is_array($v) && count($v)==0) ){
                $post_array[] = $k .'='. $v;
            }
        }
        sort($post_array);
        return strtoupper(md5(implode('&',$post_array).'&key='.static::$SIGN_KEY));
    }


}

//静态类的静态变量的初始化不能使用宏，只能用这样的笨办法了。
if (W2PayWx::$APPID==null && defined('W2PAYWX_APPID'))
{
    W2PayWx::$APPID          = W2PAYWX_APPID;
    W2PayWx::$MCH_ID         = W2PAYWX_MCH_ID;
    W2PayWx::$SIGN_KEY       = W2PAYWX_SIGN_KEY;
    W2PayWx::$NOTIFY_URL     = W2PAYWX_NOTIFY_URL;
}
if (W2PayWx::$APICLIENT_CERT==null && defined('W2PAYWX_APICLIENT_CERT'))
{
    W2PayWx::$APICLIENT_CERT = W2PAYWX_APICLIENT_CERT;
    W2PayWx::$APICLIENT_KEY  = W2PAYWX_APICLIENT_KEY;
}
