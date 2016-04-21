<?php
/**
* 返回结果 ，定制的特殊Model，主要用于对Controller的结果进行包装和处理
* @package Model
* @author axing
* @version 0.1
*/

class HaoResult extends AbstractModel{

    public static $authViewDisabled    = array('id');//展示数据时，禁止列表。

    /**
     * 初始化方法，如果需要，各模型必须重写此处
     * @param int|array 如果是整数, 赋值给对象的id,如果是数组, 给对象的逐个属性赋值
     * @return ZipAreaModel
     */
    public static function instance($p_data=null) {
        $_o = parent::instanceModel(__class__, $p_data);
        $tmpVars = get_object_vars($_o);
        $tmpVars['snapshot'] = '';
        $_o->snapshot = $tmpVars;//初始化完成后，记录当前状态
        return $_o;
    }

    //＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝定制的HaoResult方法＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝

    public static function init($errorCode = null,$result = null,$extraInfo = null) {
        return self::instance(array(
                                        'errorCode'  => is_array($errorCode)?$errorCode[0]:$errorCode,
                                        'errorStr'   => is_array($errorCode)?$errorCode[1]:$errorCode,
                                        'resultCount'=> (is_array($result) && array_values($result)===$result?count($result):1),
                                        'results'    => $result,
                                        'extraInfo'  => $extraInfo
                                    )
                            );
    }

    /**
     * 判断是否等于目标ErroCode
     * @param  array  $errorCode  目标errorCode
     * @return boolean            是否一致
     */
    public function isErrorCode($errorCode)
    {
        return $this->getErrorCode()===$errorCode[0];
    }

    /**
     * 判断是否正确获得结果
     * @return boolean            是否正确获得
     */
    public function isResultsOK()
    {
        return $this->isErrorCode(ERROR_CODE::$OK) ;
    }

    /** 增加额外数据 */
    public function addExtraInfo($key=null,$value)
    {
        $extraInfo = $this->getExtraInfo();

        if (!is_array($extraInfo) && $extraInfo!==null)
        {
            $extraInfo =  array($extraInfo);
        }

        if (is_string($key))
        {
            $extraInfo[$key] = $value;
        }
        else
        {
            $extraInfo[] = $value;
        }

        return $this->setExtraInfo($extraInfo);
    }

    /** @var int 错误码 */
    public $errorCode;
    /** @var string 错误信息 */
    public $errorStr;
    /** @var array 额外信息 */
    public $extraInfo;
    /** @var int 结果集的数量 */
    public $resultCount;
    /** @var array|list|string 结果数据（多为model组成的数组，或单个model） */
    public $results;



    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorStr()
    {
        return $this->errorStr;
    }

    public function setErrorStr($errorStr)
    {
        $this->errorStr = $errorStr;

        return $this;
    }

    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    public function setExtraInfo($extraInfo)
    {
        $this->extraInfo = $extraInfo;

        return $this;
    }

    public function getResultCount()
    {
        return $this->resultCount;
    }

    public function setResultCount($resultCount)
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    public function getTimeCost()
    {
        if (defined('AX_TIMER_START'))
        {
            return number_format(microtime (true) - AX_TIMER_START, 5, '.', '');
        }
        return null;
    }

    public function getTimeNow()
    {
        return date('Y-m-d H:i:s');
    }
}

?>
