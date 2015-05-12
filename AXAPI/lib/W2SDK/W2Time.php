<?php
/**
 * 日期快捷处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Time {

    /**
     * 将时间转化成时间戳
     * @param  [string | int | DateTime] $p_time [description]
     * @return [int]         时间戳
     */
    public static function getTimestamp($p_time=null)
    {
        if ($p_time===null || $p_time=='time()')
        {
            $p_time = time();
        }
        $time = null;
        if (is_int($p_time))
        {
            $time = strtotime(date('Y-m-d H:i:s',$p_time));
        }
        else if (is_string($p_time))
        {
            $time = strtotime($p_time);
        }
        else if (is_subclass_of($p_time,'DateTime'))
        {
            $time = $p_time->getTimestamp();
        }
        return $time;
    }

    /**
     * 将时间转化成字符串
     * @param  string $p_format [description]
     * @param  [string | int | DateTime] $p_time [description]
     * @return [string]           时间字符串
     */
    public static function timetostr($p_format='Y-m-d H:i:s',$p_time=null)
    {
        return date($p_format,W2Time::getTimestamp($p_time));
    }

    /**
     * 将时间转化成时间戳
     * @param  [string | int | DateTime] $p_time [description]
     * @return [int]         时间戳
     */
    public static function strtotime($p_time=null)
    {
        return W2Time::getTimestamp($p_time);
    }
    /**
     * 取得两个时间（字符串或时间戳）之间的时间差距（秒）
     * @param  [string | int] $p_datetime  待比较时间
     * @param  [string | int] $p_datetime2 被比较时间（默认是当前时间）
     * @return int              时间差（秒） ，负数 说明前者在后者之前。
     */
    public static function getTimeBetweenDateTime($p_datetime,$p_datetime2=null){
        if ($p_datetime2===null || $p_datetime2=='time()')
        {
            $p_datetime2 = time();
        }
        else if (is_string($p_datetime2))
        {
            $p_datetime2 = strtotime($p_datetime2);
        }
        if (is_string($p_datetime))
        {
            $p_datetime = strtotime($p_datetime);
        }
        return $p_datetime - $p_datetime2 ;
    }

    /**
     * 时间转化成当前时间的对应关系
     * @param  [type] $p_datetime 待转换时间
     * @return [string]           对应关系，如5分钟前
     */
    public static function getTimeToStringBetweenDateTimeWithToday($p_datetime){
        $p_datetime = W2Time::getTimestamp($p_datetime);
        $_interval = W2Time::getTimeBetweenDateTime($p_datetime,time());
        $_r = '未知';
        if($_interval<-60*60*24*30*12){
            $_r = sprintf('%s年前 %s',0 - intval($_interval/60/60/24/30/12),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval<-60*60*24*30){
            $_r = sprintf('%s月前 %s',0 - intval($_interval/60/60/24/30),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval<-60*60*24*7) {
            $_r = sprintf('%s周前 %s',0 - intval($_interval/60/60/24/7),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval<-60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval <- 60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("-1 day"))) < 0){
            $_r = sprintf('前天 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",time())) < 0){
            $_r = sprintf('昨天 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<-60*60*2){
            $_r = sprintf('今天 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<-60*60){
            $_r = sprintf('一小时前 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<-60){
            $_r = sprintf('%s分钟%s %s',0-intval($_interval/60),$_isBeforeOrAfter?'前':'内',W2Time::timetostr('H:i:s',$p_datetime));
        }else if($_interval<0){
            $_r = sprintf('刚才 %s',W2Time::timetostr('H:i:s',$p_datetime));
        }else if($_interval<60){
            $_r = sprintf('马上 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<60*60){
            $_r = sprintf('%s分钟内 %s',intval($_interval/60),W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<60*60*2){
            $_r = sprintf('一小时后 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("+1 day"))) < 0){
            $_r = sprintf('今天 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("+2 day"))) < 0){
            $_r = sprintf('明天 %s',W2Time::timetostr('H:i:s',$p_datetime));
        } else if($_interval<60*60*24*7) {
            $_r = sprintf('%s天后 %s',intval($_interval/60/60/24),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval<60*60*24*30){
            $_r = sprintf('%s周后 %s',intval($_interval/60/60/24/7),W2Time::timetostr('m-d H:i',$p_datetime));
        } else if($_interval<60*60*24*30*12){
            $_r = sprintf('%s月后 %s',intval($_interval/60/60/24/30),W2Time::timetostr('m-d H:i',$p_datetime));
        } else {
            $_r = sprintf('%s年后 %s',intval($_interval/60/60/24/30/12),W2Time::timetostr('m-d H:i',$p_datetime));
        }

        return $_r;
    }
}
