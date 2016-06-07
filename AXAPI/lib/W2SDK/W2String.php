<?php
/**
 * 字符串处理函数库文件
 * @package W2
 * @author 琐琐
 * @since 1.0
 * @version 1.0
 */
class W2String {

    /**
     * 将文本自动加上html超链接标签
     * @param string 原文本
     * @param string 链接打开的窗口
     * @return string 包含超链接的html文本
     */
    public static function wrapLink($p_t, $p_target='_self'){
        $p_t= preg_replace("/(^|[\s ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ \"\n\r\t<]*)/is",
            sprintf("$1$2<a href=\"$3\" target=\"%s\">$3</a>",$p_target), $p_t); /* http[s]//** */
        $p_t= preg_replace("/(^|[\s ])([\w]*?)((www|ftp)\.[^ \,\"\t\n\r<]*)/is",
            sprintf("$1$2<a href=\"http://$3\" target=\"%s\">$3</a>",$p_target), $p_t); /* ftp://** */
        $p_t= preg_replace("/(^|[\n ])([a-z0-9&\-_\.]+?)@([\w\-]+\.([\w\-\.]+)+)/i",
            sprintf("$1<a href=\"mailto:$2@$3\" target=\"%s\">$2@$3</a>",$p_target), $p_t); /* E-mail */
        //$p_t= preg_replace("/@(\w+)/", '<a href="http://www.twitter.com/$1" target="_blank">@$1</a>', $p_t); /* @twitter 用户 */
        //$p_t= preg_replace("/\#(\w+)/", '<a href="http://search.twitter.com/search?q=$1" target="_blank">#$1</a>',$p_t); /* #twitter 搜索 */
        return $p_t;
    }

    /**
     * 判断字符串是否以子串开头
     * @param string 原文本
     * @param string 子串
     * @param bool 是否匹配大小写(默认否)
     * @return bool 是/否
     */
    public static function startsWith($p_haystack, $p_needle, $p_caseSensitiveIgnore=false) {
        $p_h = $p_caseSensitiveIgnore ? strtolower($p_haystack): $p_haystack ;
        $p_n = $p_caseSensitiveIgnore ? strtolower($p_needle): $p_needle ;
        return strpos($p_h, $p_n)===0;
    }

    /** 返回字符串的长度 */
    public static function strlen($p_str,$p_charactset='utf-8')
    {
        if ($p_charactset!='utf-8')
        {
            $tmp = @iconv($p_charactset, 'utf-8', $p_str);
            if(!empty($tmp)){
                $p_str = $tmp;
            }
        }
        preg_match_all('/./us', $p_str, $match);
        return count($match[0]);  // 输出9
        // return mb_strlen($p_str,$p_charactset);
    }

    /**
     * 判断字符串是否以子串结尾
     * @param string 原文本
     * @param string 子串
     * @param bool 是否匹配大小写(默认否)
     * @return bool 是/否
     */
    public static function endsWith($p_haystack, $p_needle, $p_caseSensitiveIgnore=false) {
        $p_h = $p_caseSensitiveIgnore ? strtolower($p_haystack): $p_haystack ;
        $p_n = $p_caseSensitiveIgnore ? strtolower($p_needle): $p_needle ;
        return strrpos($p_h, $p_n) === strlen($p_h)-strlen($p_n);
    }


    /**
     * 判断字符串是否是邮件格式
     * @param string 文本
     * @return bool 是/否
     */
    public static function isEmail($p_str) {
        return (preg_match('/^[_\.0-9a-z-A-Z-]+@([0-9a-z-A-Z-][0-9a-z-A-Z-]+\.)+[a-z-A-Z-]{2,4}$/',$p_str))?true:false;
    }

    /**
     * 判断字符串是否是IP格式
     * @param string 文本
     * @return bool 是/否
     */
    public static function isIP($p_str) {
        return (preg_match('/^\d+\.\d+\.\d+\.\d+$/',$p_str))?true:false;
    }

    /**
     * 判断字符串是否是URL格式
     * @param string 文本
     * @return bool 是/否
     */
    public static function isURL($p_str) {
        return (preg_match('/^(https|http|ftp|rtsp|igmp|file|rtspt|rtspu):\/\/[\w-]*(\.[^\s]*)+/',strtolower($p_str)))?true:false;
    }


    /**
     * 判断字符串是否是URL格式组成的多个字符串
     * @param string 文本
     * @return bool 是/否
     */
    public static function isURLs($p_str) {
        $p_strNew = W2String::getHttpStrings($p_str);
        return $p_strNew == $p_str;
    }


    /**
     * 判断字符串是否是QQ格式
     * @param string 文本
     * @return bool 是/否
     */
    public static function isQQ($p_str) {
        return (preg_match('/^[1-9][0-9]{4,}$/',$p_str))?true:false;
    }


    /**
     * 判断字符串是否是账号格式 (字母开头，允许5-16字节，允许字母数字下划线)
     * @param string 文本
     * @return bool 是/否
     */
    public static function isAccount($p_str) {
        return (preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,15}$/',$p_str))?true:false;
    }



    /**
     * 判断字符串是否是手机号码
     * @param string 文本
     * @return bool 是/否
     */
    public static function isTelephone($p_str) {
        return (preg_match('/^1[34578]\d{9}$/',$p_str))?true:false;
    }


    /**
     * 目标值是否数字（或数字组成的字符串）
     * @param  int|string  $_v [description]
     * @return boolean      [description]
     */
    public static function is_int($_v)
    {
        return is_int($_v) || (strval(intval($_v))===$_v);
    }

    /**
     * 构建加密字符串
     * @param string 原字符串
     * @return string 加密字符串
     */
    public static function buildHashPassword($p_password){
        if (class_exists('PasswordHash')) {
            $hasher = new PasswordHash(8, TRUE);
            return $hasher->HashPassword($p_password);
        } else {
            return $p_password;
        }
    }


    /**
     * 创建一个随机数字
     * @param integer 数字长度
     * @return string 加密字符串
     */
    public static function buildRandNumbers($p_length=6)
    {
        $chars = '0123456789';
        $verifyCode = $chars[ mt_rand(1, strlen($chars) - 1) ];//首位排除0
        for ( $i = 1; $i < $p_length; $i++ )
        {
          $verifyCode .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $verifyCode;
    }

    /**
     * 创建一个随机字符
     * @param integer 字符长度
     * @param string 字符集
     * @return string 加密字符串
     */
    public static function buildRandCharacters($p_length=6,$chars='23456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ')
    {
        $verifyCode = '';//首位排除0
        for ( $i = 0; $i < $p_length; $i++ )
        {
          $verifyCode .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $verifyCode;
    }

    /**
     * 比较加密字符串
     * @param string 原字符串
     * @param string 加密字符串
     * @return bool 是/否
     */
    public static function checkHashPassword($p_originalPassword, $p_hashPassword){
        if (class_exists('PasswordHash')) {
            $hasher = new PasswordHash(8, TRUE);
            return $hasher->CheckPassword($p_originalPassword,$p_hashPassword);
        } else {
            return $p_originalPassword == $p_hashPassword;
        }
    }

    /**
     * 转换字符串中的ubb代码使可读
     * @param string 原字符串
     * @param string 目标平台 pc ios5 ios4
     * @return bool 是/否
     */
    public static function ubbDecodeLite($s, $p_device='ios5'){
        $s = preg_replace('/</','&lt;',$s);
        $s = preg_replace('/>/','&gt;',$s);
        $s = preg_replace('/"/','&quot;',$s);
        $s = preg_replace('/\'/','&#39;',$s);
        $s = preg_replace('/&lt;br.*?&gt;/','<br />',$s);
        $s = preg_replace('/&lt;p&gt;/','<p>',$s);
        $s = preg_replace('/&lt;\/p&gt;/','</p>',$s);
        $s = preg_replace('/([^\]^=]|^)(https?)(:\S+)\.(gif|png|bmp|jpg)/i','$1[img]$2$3.$4[/img]',$s);//[ur,$sl]
        $s = preg_replace('/@([^\.\s<>]*)(\s)/i','[url=/$1]@$1[/url]$2',$s);//[ur,$sl]
        $s = preg_replace('/([^\]=]|^)(https?|ftp|gopher|news|telnet|mms|rtsp)(:[\w\-\.\/?\@\%\!\&=\+\~\:\#\;\,]+)/i','$1[url=$2$3]$2$3[/url]',$s);//[ur,$sl]
        $s = preg_replace('/\[url\](https?|ftp|gopher|news|telnet|mms|rtsp)(:.+?)\[\/url\]/i','[url=$1$2]$1$2[/url]',$s);//[ur,$sl]
        $s = preg_replace('/\[url=(.*?)\](.+?)\[\/url\]/i','<a href="$1" >$2</a>',$s);

//for ios
    $s = preg_replace('/\[item=(\d+?)-(\d+?)-(.*?)\](.*?)\[\/item\]/i','<a href="http://www.appjk.com/item/$1-$2-$3"> [item$1:$4]</a>',$s);
    $s = preg_replace('/\[app.*?(\d{9}).*?\[\/app\]/i','<a href="http://www.appjk.com/app/$1"> [app]</a>',$s);
    $s = preg_replace('/\[video](.+?)\[\/video\]/i','<a href="$1"> [video]</a>',$s);
    $s = preg_replace('/\[img(\d{0,3})\](.+?)\[\/img\]/i','<img src="$2"/>',$s);

        $s = W2String::convertEmojiForDevice($s,$p_device);//emoji

        return $s;
    }
    /**
     * 转换字符串中的ubb代码使可读
     * @param string 原字符串
     * @param string 目标平台 pc ios5 ios4
     * @return bool 是/否
     */
    public static function ubbDecode($s, $p_device='ios5'){
    $s = preg_replace('/\n/i',"</p>\n<p>",$s);
    // $s = '<p>'.$s.'</p>';

    $s = W2String::ubbDecodeLite($s,$p_device);

    $s = preg_replace('/&lt;br&gt;/i','<br />',$s);

    //quote
    do{
        $s = preg_replace('/\[quote\]([\s\S]*)\[\/quote\]/','<div class="ubb-quote">$1</div>',$s);
    }
    while(preg_match('/\[quote\]([\s\S]*)\[\/quote\]/',$s)) ;





    $s = preg_replace('/\[b\]([\s\S]*?)\[\/b\]/','<b>$1</b>',$s);
    $s = preg_replace('/\[del\](.+?)\[\/del\]/i',"<del class='gray'> $1 </del>",$s);//[de,$sl]
    $s = preg_replace('/\[u\](.+?)\[\/u\]/i',"<u>$1</u>",$s);//[,$su]
    $s = preg_replace('/\[i\](.+?)\[\/i\]/i',"<i style='font-style:italic'>$1</i>",$s);//[,$si]
    $s = preg_replace('/\[color=(skyblue|royalblue|blue|darkblue|orange|orangered|crimson|red|firebrick|darkred|green|limegreen|seagreen|teal|deeppink|tomato|coral|purple|indigo|burlywood|sandybrown|sienna|chocolate|silver|gray)\](.+?)\[\/color\]/i',"<span style='color:$1'>$2</span>",$s);//[colo,$sr]
    $s = preg_replace('/\[size=(\d{1,3})%?\](.*?)\[\/size\]/i','<span style="font-size:$1%;line-height:183%"></span>',$s);
    $s = preg_replace('/\[font=(simsun|simhei|Arial|Arial Black|Book Antiqua|Century Gothic|Comic Sans MS|Courier New|Georgia|Impact|Tahoma|Times New Roman|Trebuchet MS|Script MT Bold|Stencil|Verdana|Lucida Console)\](.+?)\[\/font\]/i',"<span style='font-family:$1'>$2</span>",$s);//[fon,$st]
    $s = preg_replace('/\[align=(left|center|right)\]([\s\S]+?)\[\/align\]/i',"<div style='text-align:$1'>$2</div>",$s);//[fon,$st]


    // );//[table] */

        return $s;
    }

    /**
     * 转换字符串中的ubb代码使可读
     * @param string 原字符串
     * @param string 目标平台 pc ios5 ios4
     * @return bool 是/否
     */
    public static function unUbbCode($s, $p_device='ios5'){
        // $s = '<p>'.$s.'</p>';

        $s = W2String::ubbDecode($s,$p_device);

        $s = preg_replace('/<div class="ubb-quote">/i',"-------------\n",$s);
        $s = preg_replace('/<a.*?href="(.*?)(|\?picUrl.*?)".*?>.*?<\/a>/i',"$1",$s);
        $s = preg_replace('/<.*?src="(.*)".*?>/i',"$1",$s);
        $s = strip_tags($s);
        return $s;
        $s = preg_replace('/<.*?href="(.*)".*?>/i',"$1",$s);
        $s = preg_replace('/<.*?>/i','',$s);

    }

    /**
     * 用逗号分隔字符串,去除无效部分,重新组合成字符串
     * @param  string $p_string 逗号组成的多个网址string
     * @return string           逗号组成的多个网址string
     */
    public static function getArrayStrings($p_string,$p_delimiter=',')
    {
        return implode($p_delimiter, W2String::getStringsArray($p_string));
    }

    /**
     * 用逗号分隔字符串得到数组,去除无效部分
     * @param  string $p_string 逗号组成的多个网址string
     * @return string           逗号组成的多个网址string
     */
    public static function getStringsArray($p_string,$p_delimiter=',')
    {
        if (!is_array($p_string))
        {
            $p_string = explode($p_delimiter, $p_string);
        }
        $p_stringList = array();
        foreach ($p_string as $s) {
            if ($s!=null)
            {
                $p_stringList[] = $s;
            }
        }
        return $p_stringList;
    }

    /**
     * 从string中取出http网址,并以逗号连接
     * @param  string $p_string 逗号组成的多个网址string
     * @return string           逗号组成的多个网址string
     */
    public static function getHttpStrings($p_string,$p_delimiter=',')
    {
        $p_stringList = array();
        foreach (explode($p_delimiter, $p_string) as $s) {
            if (W2String::isURL($s))
            {
                $p_stringList[] = $s;
            }
        }
        return implode($p_delimiter, $p_stringList);
    }

    /**
     * 将网址组成的string分解成数组
     * @param  string $p_string 逗号组成的多个网址string
     * @return string[] $p_string 多个网址string组成的数组
     */
    public static function getHttpArray($p_string,$p_delimiter=',')
    {
        return W2String::getStringsArray( W2String::getHttpStrings($p_string) , $p_delimiter);
    }

    /**
     * [getRtfContent 获取富文本内容]
     * @param  string $strContent [description]
     * @return [type]             [description]
     */
    public static function getRtfContent( $strContent = '' )
    {
        $htmlContent = '<!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                            <meta name="viewport" content="width=device-width,initial-scale=1.0">
                        </head>
                        <body>';
        $htmlContent .= $strContent;

        $htmlContent .= '<style type="text/css">
                                 * {padding: 0px; margin: 0px; }
                                img{max-width:100%;text-align:center;vertical-align:middle; }
                                body{padding:2%;}
                            </style>';

        $htmlContent .= '</body> </html>';
        return $htmlContent;
    }


    /**
     * 判定给定文本是否文件地址，如果是文件，则取文件内容，如果不是文件，则返回文本本身。
     * @param  string|path $p_string 文本或文件地址
     * @return string           文本
     */
    public static function getContentsFromFileOrString($p_string)
    {
        if (file_exists($p_string))
        {
            return file_get_contents($p_string);
        }
        return $p_string;
    }

    //驼峰式字符串（首字母大写）
    public static function camelCaseWithUcFirst($str)
    {
        return ucfirst(W2String::camelCase($str));
    }

    //驼峰式字符串（首字母小写）
    public static function camelCase($str)
    {
        $str = preg_replace('/[^a-zA-Z0-9]/', ' ', $str);
        //使用空格隔开后，每个单词首字母大写
        $str = ucwords($str);
        //小写字符串的首字母，然后删除空格
        $str = str_replace(' ','',lcfirst($str));
        $str = str_replace('Id','ID',$str);
        return $str;
    }

    /** 下划线格式的字符串（全部小写） */
    public static function under_score($str) {
        $str = str_replace('ID','Id',$str);
        return strtolower(ltrim(preg_replace_callback('/[A-Z]/', function ($mathes) { return '_' . strtolower($mathes[0]); }, $str), '_'));
    }

    /*
     * rc4加密算法
     * $pwd 密钥
     * $data 要加密的数据
     */
    public static function rc4 ($pwd, $data)//$pwd密钥　$data需加密字符串
    {
        $key[] = "";
        $box[] = "";

        $pwd_length  = strlen($pwd);
        $data_length = strlen($data);

        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }

        for ($j = $i = 0; $i < 256; $i++)
        {
            $j       = ($j + $box[$i] + $key[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;

            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k       = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }

        return $cipher;
    }

    /** 从多个参数中，取出第一个有效值 */
    public static function getValidValue()
    {
        $args = func_get_args();
        foreach ($args as $value) {
            if ($value != null)
            {
                return $value;
            }
        }
    }

    /** 根据提供的键值对，创建options字符串。 */
    public static function getOptionsOfSelect($options,$value=null)
    {
        $oList = array();
        foreach ($options as $oValue => $oName) {
            $oList[] = '<option value="'.$oValue.'"'.($value===$oValue?' selected':'').'>'.$oName.'</option>';
        }
        return implode("\n",$oList);
    }

    /**
     * 通过遍历匹配的开头局部，来判断指定的字符串是否匹配这个局部。
     * 如果局部匹配成功，意味着这个字符串可能是这个匹配的开头部分，后面加上些字符就能完整匹配了。
     * @param  string $pattern 正则
     * @param  string $subject 目标字符串
     * @return bool
     */
    public static function pregPartMatch($pattern,$subject)
    {
        for ($i=1; $i <= strlen($pattern) ; $i++) {
            if (preg_match('/^'.substr($pattern,0,$i).'$/',$subject))
            {
                return true;
            }
        }
        return false;
    }
}
