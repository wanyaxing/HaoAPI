<?php
/**
* 日期时间类文件
* @package W2
* @author 琐琐
* @since 1.0
* @version 1.0
*/

class W2DateTime extends DateTime{

    /**
     * 格式化为字符串
     * @param string 格式，默认为 Y-m-d H:i:s
     * @return string 格式化后的字符串
     */
    public function formatToString($p_format='Y-m-d H:i:s'){
        return $this->format($p_format);
    }

    /**
     * 格式化为指定时区的字符串
     * @param string 时区
     * @param string 格式，默认为 Y-m-d H:i:s
     * @return string 格式化后的字符串
     */
    public function formatToStringWithTimeZone($p_timeZone,$p_format='Y-m-d H:i:s'){
        $_t = new DateTime($this->formatToString(), $this->getTimezone());
        $_t->setTimezone(new DateTimeZone($p_timeZone));
        return $_t->format($p_format);
    }

    /**
     * 显示友好的时间文字
     * @return string 字符串
     */
    public function friendlyString() {
        $_r = $this->formatToString("Y-m-d H:i");
        $_interval = time() - $this->getTimestamp();
        if($_interval<0){
            $_r = $this->formatToString();
        } else if($_interval<60){
            $_r = sprintf('刚才 %s',$this->formatToString("H:i:s"));
        } else if($_interval<60*60){
            $_r = sprintf('%s分钟前 %s',intval($_interval/60),$this->formatToString("H:i:s"));
        } else if($_interval<60*60*2){
            $_r = sprintf('一小时前 %s',$this->formatToString("H:i:s"));
        } else if($_interval<60*60*24){
            $_r = sprintf('今天 %s',$this->formatToString("H:i:s"));
        } else if($_interval<60*60*24*2){
            $_r = sprintf('昨天 %s',$this->formatToString("H:i:s"));
        } else if($_interval<60*60*24*7) {
            $_r = sprintf('%s天前 %s',intval($_interval/60/60/24),$this->formatToString("m-d H:i"));
        } else if($_interval<60*60*24*30){
            $_r = sprintf('%s周前 %s',intval($_interval/60/60/24/7),$this->formatToString("m-d H:i"));
        } else if($_interval<60*60*24*30*12){
            $_r = sprintf('%s月前 %s',intval($_interval/60/60/24/30),$this->formatToString("m-d H:i"));
        // } else {
        //     $_r = sprintf('%s年前 %s',intval($_interval/60/60/24/30/12),$this->formatToString("m-d H:i"));
        } 
        return $_r;
    }
}

/*
date_default_timezone_set('PRC');
$_d = new W2DateTime('2012-05-25 11:11:32');
var_dump($_d->formatToString());
var_dump($_d->friendlyString());
*/

?>