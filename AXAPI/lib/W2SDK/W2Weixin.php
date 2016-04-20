<?php
/**
 * 微信公众号相关
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Weixin {
    public static $APPID           = null; //公众号ID
    public static $SECRET          = null; //公众号后台-开发-基本配置-AppSecret

    /**
     * 第一步：用户同意授权，获取code
     * @param  string $redirect_uri 授权后回调地址，需事先设定授权域名： 公众号后台-开发-接口权限，更改授权回调域名为当前域名。
     * @param  string $scope        应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     * @param  string $state        重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
     * @return string               回调指定路径的微信授权地址
     * 如果用户同意授权，页面将跳转至 redirect_uri/?code=CODE&state=STATE。
     * 若用户禁止授权，则重定向后不会带上code参数，仅会带上state参数redirect_uri?state=STATE
     */
    public static function getUrlForWxAuth($redirect_uri=null,$scope='snsapi_base',$state='haoxitech')
    {
        if (is_null($redirect_uri))
        {
            $redirect_uri = 'http://' .  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
        }
        $redirect_uri = urlencode($redirect_uri);
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . static::$APPID . '&redirect_uri=' . $redirect_uri . '&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
    }

    /**
     * 第二步：通过code换取网页授权access_token
     * https://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html
     * 首先请注意，这里通过code换取的是一个特殊的网页授权access_token,
     * 与基础支持中的access_token（该access_token用于调用其他接口）不同。
     * 公众号可通过下述接口来获取网页授权access_token。
     * 如果网页授权的作用域为snsapi_base，则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止。
     * @param  string       $code   填写第一步获取的code参数
     * @return array
{
"access_token":"ACCESS_TOKEN",
"expires_in":7200,
"refresh_token":"REFRESH_TOKEN",
"openid":"OPENID",
"scope":"SCOPE",
"unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
}
     */
    public static function getTokenOfCode($code)
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . static::$APPID . '&secret=' .  static::$SECRET . '&code=' . $code . '&grant_type=authorization_code';
        return W2Web::loadJsonByUrl($url);
    }

    /**
     * 第四步：拉取用户信息(需scope为 snsapi_userinfo)
     * 如果网页授权作用域为snsapi_userinfo，则此时开发者可以通过access_token和openid拉取用户信息了。
     * @param  string $access_token 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * @param  string $openid       用户的唯一标识
     * @param  string $lang         返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     * @return array                用户数据
{
   "openid":" OPENID",
   " nickname": NICKNAME,
   "sex":"1",
   "province":"PROVINCE"
   "city":"CITY",
   "country":"COUNTRY",
    "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
    "privilege":[
    "PRIVILEGE1"
    "PRIVILEGE2"
    ],
    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
}
     */
    public static function getUserInfoOfAccessToken($access_token,$openid,$lang='zh_CN')
    {
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang='.$lang;
        return W2Web::loadJsonByUrl($url);
    }

    /**
     * 获取用户信息
     * 合并了第二步和第四步
     * （如不支持，则只返回token信息）
     * @param  string $code 填写第一步获取的code参数
     * @return array        第二步 或 第四步 的结果
     */
    public static function getUserInfoOfCode($code)
    {
        $token = static::getTokenOfCode($code);
        if (is_array($token) && isset($token['access_token']))
        {
            if ( isset($token['scope']) && strpos($token['scope'], 'snsapi_userinfo')!==false )
            {
                return static::getUserInfoOfAccessToken($token['access_token'],$token['openid']);
            }
        }
        return $token;
    }

}


//静态类的静态变量的初始化不能使用宏，只能用这样的笨办法了。
if (W2Weixin::$APPID==null && defined('W2WEIXIN_APPID'))
{
    W2Weixin::$APPID          = W2WEIXIN_APPID;
    W2Weixin::$SECRET         = W2WEIXIN_SECRET;
}
