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
     * @param  [string | int | DateTime] $p_time [description]
     * @param  string $p_format [description]
     * @return [string]           时间字符串
     */
    public static function timetostr($p_time=null,$p_format='Y-m-d H:i:s')
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
    public static function getTimeBetweenDateTime($p_datetime,$p_datetime2=null)
    {
        return W2Time::getTimestamp($p_datetime) - W2Time::getTimestamp($p_datetime2) ;
    }

    /**
     * 时间增减
     * @param  [type] $p_time [description]
     * @param  int|string $p_add      时间戳或 字符串  -1 day  next Thursday
     * @return [type]             [description]
     */
    public static function getTimeAdded($p_time,$p_add=0)
    {
        if (is_int($p_add))
        {
            return W2Time::getTimestamp($p_time) + W2Time::getTimestamp($p_add);
        }
        return W2Time::getTimestamp(W2Time::timetostr($p_time) . ' ' .$p_add);
    }

    public static function getTimesArrayBetweenDateTime($p_time1,$p_time2=null,$p_stepType=null)
    {
        $_interval = abs(W2Time::getTimeBetweenDateTime($p_time1,$p_time2));
        if ($p_stepType=null)
        {
            if($_interval<60){
                $p_stepType = 'second';
            } else if($_interval<60*60){
                $p_stepType = 'minute';
            } else if($_interval<60*60*24){
                $p_stepType = 'hour';
            } else if ($_interval<60*60*365){
                $p_stepType = 'day';
            } else {
                $p_stepType = 'year';
            }
        }
        switch ($p_stepType) {
            case 'second':
                $p_step   = '+1 second';
                $p_format = 'Y-m-d H:i:s';
                break;
            case 'minute':
                $p_step   = '+1 minute';
                $p_format = 'Y-m-d H:i';
                break;
            case 'hour':
                $p_step   = '+1 hour';
                $p_format = 'Y-m-d H';
                break;
            case 'day':
                $p_step   = '+1 day';
                $p_format = 'Y-m-d';
                break;
            case 'week':
                $p_step   = '+1 week';
                $p_format = 'Y-m-d(W)';
                break;
            case 'month':
                $p_step   = '+1 month';
                $p_format = 'Y-m';
                break;
            case 'year':
                $p_step   = '+1 year';
                $p_format = 'Y';
                break;
            default:
                $p_step   = '+1 day';
                $p_format = 'Y-m-d';
                break;
        }
        $results = array();

        $timeStart = min(W2Time::getTimestamp($p_time1),W2Time::getTimestamp($p_time2));
        $timeEnd = max(W2Time::getTimestamp($p_time1),W2Time::getTimestamp($p_time2));
        $timeThis = $timeStart;
        while ($timeThis<=$timeEnd)
        {
            $results[] = date($p_format,$timeThis);
            $timeThis = W2Time::getTimeAdded($timeThis,$p_step);
        }
        array_unique($results);
        return $results;
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
            $_r = sprintf('%s年前 %s',0 - intval($_interval/60/60/24/30/12),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval<-60*60*24*30){
            $_r = sprintf('%s月前 %s',0 - intval($_interval/60/60/24/30),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval<-60*60*24*7) {
            $_r = sprintf('%s周前 %s',0 - intval($_interval/60/60/24/7),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval<-60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval <- 60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("-1 day"))) < 0){
            $_r = sprintf('前天 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",time())) < 0){
            $_r = sprintf('昨天 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<-60*60*2){
            $_r = sprintf('今天 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<-60*60){
            $_r = sprintf('一小时前 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<-60){
            $_r = sprintf('%s分钟%s %s',0-intval($_interval/60),$_isBeforeOrAfter?'前':'内',W2Time::timetostr($p_datetime,'H:i:s'));
        }else if($_interval<0){
            $_r = sprintf('刚才 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        }else if($_interval<60){
            $_r = sprintf('马上 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<60*60){
            $_r = sprintf('%s分钟内 %s',intval($_interval/60),W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<60*60*2){
            $_r = sprintf('一小时后 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("+1 day"))) < 0){
            $_r = sprintf('今天 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_datetime,date("Y-m-d 00:00:00",strtotime("+2 day"))) < 0){
            $_r = sprintf('明天 %s',W2Time::timetostr($p_datetime,'H:i:s'));
        } else if($_interval<60*60*24*7) {
            $_r = sprintf('%s天后 %s',intval($_interval/60/60/24),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval<60*60*24*30){
            $_r = sprintf('%s周后 %s',intval($_interval/60/60/24/7),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else if($_interval<60*60*24*30*12){
            $_r = sprintf('%s月后 %s',intval($_interval/60/60/24/30),W2Time::timetostr($p_datetime,'m-d H:i'));
        } else {
            $_r = sprintf('%s年后 %s',intval($_interval/60/60/24/30/12),W2Time::timetostr($p_datetime,'m-d H:i'));
        }

        return $_r;
    }
}
