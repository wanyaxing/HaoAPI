<?php
/**
 * 短信处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2RSA {


	/**
	 * RSA签名
	 * @param $data 待签名数据
	 * @param $private_key 商户私钥文件路径 或 密钥内容本身
	 * return 签名结果
	 */
	public static function rsaSign($data, $private_key) {
	    $priKey = W2String::getContentsFromFileOrString($private_key);
	    $res = openssl_get_privatekey($priKey);
	    openssl_sign($data, $sign, $res);
	    openssl_free_key($res);
		//base64编码
	    $sign = base64_encode($sign);
	    return $sign;
	}

	/**
	 * RSA验签
	 * @param $data 待签名数据
	 * @param $ali_public_key 支付宝的公钥文件路径 或 密钥内容本身
	 * @param $sign 要校对的的签名结果
	 * return 验证结果
	 */
	public static function rsaVerify($data, $ali_public_key, $sign)  {
		$pubKey = W2String::getContentsFromFileOrString($ali_public_key);
	    $res = openssl_get_publickey($pubKey);
	    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
	    openssl_free_key($res);
	    return $result;
	}

	/**
	 * RSA解密
	 * @param $content 需要解密的内容，密文
	 * @param $private_key 商户私钥文件路径 或 密钥内容本身
	 * return 解密后内容，明文
	 */
	public static function rsaDecrypt($content, $private_key) {
	    $priKey = W2String::getContentsFromFileOrString($private_key);
	    $res = openssl_get_privatekey($priKey);
	    //用base64将内容还原成二进制
	    $content = base64_decode($content);
	    //把需要解密的内容，按128位拆开解密
	    $result  = '';
	    for($i = 0; $i < strlen($content)/128; $i++  ) {
	        $data = substr($content, $i * 128, 128);
	        openssl_private_decrypt($data, $decrypt, $res);
	        $result .= $decrypt;
	    }
	    openssl_free_key($res);
	    return $result;
	}

	/**
	 * RSA加密
	 * @param $content 需要加密的内容
	 * @param $public_key 商户公钥文件路径 或 密钥内容本身
	 * return 加密后内容，明文
	 */
	public static function rsaEncrypt($content, $public_key) {
	    $priKey = W2String::getContentsFromFileOrString($public_key);
	    $res = openssl_get_publickey($priKey);
	    //把需要加密的内容，按128位拆开加密
	    $result  = '';
	    for($i = 0; $i < ((strlen($content) - strlen($content)%117)/117+1); $i++  ) {
	        $data = mb_strcut($content, $i*117, 117, 'utf-8');
	        openssl_public_encrypt($data, $encrypted, $res);
	        $result .= $encrypted;
	    }
	    openssl_free_key($res);
		//用base64将二进制编码
	    $result = base64_encode($result);
	    return $result;
	}

}
