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
     * 将字符串转化成时间戳
     * @param  [string | int | DateTime] $p_time [description]
     * @return [int]         时间戳
     */
    public static function strtotime($p_time=null)
    {
        return W2Time::getTimestamp($p_time);
    }

    /**
     * 取得两个时间（字符串或时间戳）之间的时间差距（秒）
     * @param  [string | int] $p_time  待比较时间
     * @param  [string | int] $p_time2 被比较时间（默认是当前时间）
     * @return int              时间差（秒） ，负数 说明前者在后者之前。
     */
    public static function getTimeBetweenDateTime($p_time=null,$p_time2=null)
    {
        return W2Time::getTimestamp($p_time) - W2Time::getTimestamp($p_time2) ;
    }

    /**
     * 时间增减
     * @param  [type] $p_time [description]
     * @param  int|string $p_add      时间戳或 字符串  -1 day  next Thursday
     * @return [type]             [description]
     */
    public static function getTimeAdded($p_time=null,$p_add=0)
    {
        if (is_int($p_add))
        {
            return W2Time::getTimestamp($p_time) + W2Time::getTimestamp($p_add);
        }
        return W2Time::getTimestamp(W2Time::timetostr($p_time) . ' ' .$p_add);
    }

    /**
     * 获得指定时间类型的时间格式（MYSQL用）
     * @param  string $p_dateType 时间类型
     * @return string             时间格式
     */
    public static function getFormatOfMysqlWithDateType($p_dateType)
    {
        $format = '';
        switch ($p_dateType) {
            case 'year':
                $format = '%Y';
                break;
            case 'month':
                $format = '%Y-%m';
                break;
            case 'week':
                $format = '%Y %u';
                break;
            case 'hour':
                $format = '%Y-%m-%d %H';
                break;
            case 'minute':
                $format = '%Y-%m-%d %H:%i';
                break;
            case 'second':
                $format = '%Y-%m-%d %H:%i:%s';
                break;
            case 'day':
            default:
                $format = '%Y-%m-%d';
                break;
        }
        return $format;
    }

    /**
     * 取得两个时间点之间的时间数组
     * @param  [string | int] $p_time1    [description]
     * @param  [string | int] $p_time2    [description]
     * @param  string $p_stepType 分隔方案 second minute hour day week year
     * @param  string $p_format 时间串格式化方案，可以不传
     * @return array             格式化后的时间字符串数组
     */
    public static function getTimesArrayBetweenDateTime($p_time1=null,$p_time2=null,$p_stepType=null,$p_format=null)
    {
        $_interval = abs(W2Time::getTimeBetweenDateTime($p_time1,$p_time2));
        if ($p_stepType==null)
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
        $tmp_format = null;
        switch ($p_stepType) {
            case 'second':
                $p_step   = '+1 second';
                $tmp_format = 'Y-m-d H:i:s';
                break;
            case 'minute':
                $p_step   = '+1 minute';
                $tmp_format = 'Y-m-d H:i';
                break;
            case 'hour':
                $p_step   = '+1 hour';
                $tmp_format = 'Y-m-d H';
                break;
            case 'day':
                $p_step   = '+1 day';
                $tmp_format = 'Y-m-d';
                break;
            case 'week':
                $p_step   = '+1 week';
                $tmp_format = 'Y W';
                break;
            case 'month':
                $p_step   = '+1 month';
                $tmp_format = 'Y-m';
                break;
            case 'year':
                $p_step   = '+1 year';
                $tmp_format = 'Y';
                break;
            default:
                $p_step   = '+1 day';
                $tmp_format = 'Y-m-d';
                break;
        }
        if ($p_format==null)
        {
            $p_format = $tmp_format;
        }
        $results = array();

        $timeStart = min(W2Time::getTimestamp($p_time1),W2Time::getTimestamp($p_time2));
        $timeEnd = max(W2Time::getTimestamp($p_time1),W2Time::getTimestamp($p_time2));
        $timeThis = $timeStart;
        while ($timeThis<=$timeEnd)
        {
            $results[] = date($p_format,$timeThis);
            if ($timeThis == $timeEnd)
            {
                break;
            }
            $timeThis = W2Time::getTimeAdded($timeThis,$p_step);
            if ($timeThis > $timeEnd)
            {
                $timeThis = $timeEnd;
            }
        }

        return array_unique($results);
    }

    /**
     * 时间转化成当前时间的对应关系
     * @param  [type] $p_time 待转换时间
     * @return [string]           对应关系，如5分钟前
     */
    public static function getTimeToStringBetweenDateTimeWithToday($p_time=null){
        $p_time = W2Time::getTimestamp($p_time);
        $_interval = W2Time::getTimeBetweenDateTime($p_time,time());
        $_r = '未知';
        if($_interval<-60*60*24*30*12){
            $_r = sprintf('%s年前 %s',0 - intval($_interval/60/60/24/30/12),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval<-60*60*24*30){
            $_r = sprintf('%s月前 %s',0 - intval($_interval/60/60/24/30),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval<-60*60*24*7) {
            $_r = sprintf('%s周前 %s',0 - intval($_interval/60/60/24/7),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval<-60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval <- 60*60*24*2){
            $_r = sprintf('%s天前 %s',0-intval($_interval/60/60/24),W2Time::timetostr($p_time,'m-d H:i'));
        } else if (W2Time::getTimeBetweenDateTime($p_time,date("Y-m-d 00:00:00",strtotime("-1 day"))) < 0){
            $_r = sprintf('前天 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_time,date("Y-m-d 00:00:00",time())) < 0){
            $_r = sprintf('昨天 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<-60*60*2){
            $_r = sprintf('今天 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<-60*60){
            $_r = sprintf('一小时前 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<-60){
            $_r = sprintf('%s分钟%s %s',0-intval($_interval/60),$_isBeforeOrAfter?'前':'内',W2Time::timetostr($p_time,'H:i:s'));
        }else if($_interval<0){
            $_r = sprintf('刚才 %s',W2Time::timetostr($p_time,'H:i:s'));
        }else if($_interval<60){
            $_r = sprintf('马上 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<60*60){
            $_r = sprintf('%s分钟内 %s',intval($_interval/60),W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<60*60*2){
            $_r = sprintf('一小时后 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_time,date("Y-m-d 00:00:00",strtotime("+1 day"))) < 0){
            $_r = sprintf('今天 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if (W2Time::getTimeBetweenDateTime($p_time,date("Y-m-d 00:00:00",strtotime("+2 day"))) < 0){
            $_r = sprintf('明天 %s',W2Time::timetostr($p_time,'H:i:s'));
        } else if($_interval<60*60*24*7) {
            $_r = sprintf('%s天后 %s',intval($_interval/60/60/24),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval<60*60*24*30){
            $_r = sprintf('%s周后 %s',intval($_interval/60/60/24/7),W2Time::timetostr($p_time,'m-d H:i'));
        } else if($_interval<60*60*24*30*12){
            $_r = sprintf('%s月后 %s',intval($_interval/60/60/24/30),W2Time::timetostr($p_time,'m-d H:i'));
        } else {
            $_r = sprintf('%s年后 %s',intval($_interval/60/60/24/30/12),W2Time::timetostr($p_time,'m-d H:i'));
        }

        return $_r;
    }

    /**
     * 将N秒转化成其他单位的字符串
     * @param  [type] $p_time 待转换时间
     * @return [string]           对应关系，如5分钟前
     */
    public static function secondsToStr($_interval=null,$p_format='Y年m月d天H小时i分钟s秒'){
        $_r = $p_format;

        if( strpos($_r,'Y')!==false ){
            $_y         = intval($_interval/60/60/24/30/12);
            $_interval -= $_y * 60*60*24*30*12;
            $_r         = str_replace('Y',sprintf('%02d',$_y),$_r);
        }

        if( strpos($_r,'m')!==false ){
            $_y         = intval($_interval/60/60/24/30);
            $_interval -= $_y * 60*60*24*30;
            $_r         = str_replace('m',sprintf('%02d',$_y),$_r);
        }

        if( strpos($_r,'d')!==false ){
            $_y         = intval($_interval/60/60/24);
            $_interval -= $_y * 60*60*24;
            $_r         = str_replace('d',sprintf('%02d',$_y),$_r);
        }

        if( strpos($_r,'H')!==false ){
            $_y         = intval($_interval/60/60);
            $_interval -= $_y * 60*60;
            $_r         = str_replace('H',sprintf('%02d',$_y),$_r);
        }

        if( strpos($_r,'i')!==false ){
            $_y         = intval($_interval/60);
            $_interval -= $_y * 60;
            $_r         = str_replace('i',sprintf('%02d',$_y),$_r);
        }

        if( strpos($_r,'s')!==false ){
            $_y         = intval($_interval);
            $_interval -= $_y;
            $_r         = str_replace('s',sprintf('%02d',$_y),$_r);
        }

        return $_r;
    }

    /**
     * 获得指定时间所在当月的第一天的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getFirstDayInSameMonth($p_time=null)
    {
        return W2Time::timetostr($p_time,'Y-m-1');
    }

    /**
     * 获得指定时间所在当月的最后一天的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getLastDayInSameMonth($p_time=null)
    {
        return W2Time::timetostr((W2Time::getTimeAdded(W2Time::timetostr($p_time,'Y-m-1'),'+1 month') - 1),'Y-m-d');
    }

    /**
     * 获得指定时间所在当年的第一天的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getFirstDayInSameYear($p_time=null)
    {
        return W2Time::timetostr($p_time,'Y-1-1');
    }

    /**
     * 获得指定时间所在当年的最后一天的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getLastDayInSameYear($p_time=null)
    {
        return W2Time::getLastDayInSameMonth(W2Time::timetostr($p_time,'Y-12-1'));
    }

    /**
     * 获得指定时间所在当周的第一天(星期一)的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getFirstDayInSameWeek($p_time=null)
    {
        $w = W2Time::timetostr($p_time,'w');
        $w = ($w==0?7:$w);
        return W2Time::timetostr((W2Time::getTimeAdded(W2Time::timetostr($p_time,'Y-m-d'),'-'.(($w-1)).' day')),'Y-m-d');
    }

    /**
     * 获得指定时间所在当周的最后一天(星期日)的日期 Y-m-d
     * @param  [type] $p_time [description]
     * @return string             格式 Y-m-d
     */
    public static function getLastDayInSameWeek($p_time=null)
    {
        $w = W2Time::timetostr($p_time,'w');
        $w = ($w==0?7:$w);
        return W2Time::timetostr((W2Time::getTimeAdded(W2Time::timetostr($p_time,'Y-m-d'),'+'.(7-($w-1)).' day'))-1,'Y-m-d');
    }

}
