<?php
/**
 * user接口返回的数据转换成Result实例，用于支持点语法输出数据以及各种筛选
 * @package Model
 * @author axing
 * @since 0.1
 * @version 0.1
 */
class HaoResult {

	/* 特殊变量，没有set和get方法，不推荐外部使用。 */
    public $errorCode;
    public $errorStr;
    public $extraInfo;
    public $results;
    public $modelType;
    public $searchIndexString;

    public $pathCache;

    // ======================== functions ========================

    /** 将数组初始化成对象 */
    public static function instanceModel($results,$errorCode,$errorStr,$extraInfo)
    {

        $modelType = 'HaoResult';

        if (is_array($results))
        {
            if (isset($results['modelType']))
            {
                $modelType = $results['modelType'];
            }
        }

	    $modelName = $modelType . ( $modelType != 'HaoResult' ? 'Result' : '' );
	    if (class_exists($modelName))
	    {
	        $object = new $modelName();
	    }
	    else
	    {
	    	$object = new HaoResult();
	    }

        $object ->errorCode   = $errorCode;
        $object ->errorStr    = $errorStr;
        $object ->extraInfo   = $extraInfo;
        $object ->results     = $results;
        $object ->modelType   = $modelType;

        $object ->pathCache   = array();

        return $object;
    }


    /** 根据路径取数据，默认是results中取，也可以指定extraInfo>路径下取数据。 */
    public function find($path)
    {
        $path = trim($path);

        if (isset($this->pathCache[$path]))
        {
            return $this->pathCache[$path];
        }

        if ( strpos($path,'results>') !== 0 && strpos($path,'extraInfo>') !== 0 )
        {
            $path = 'results>'.$path;
        }

        $paths = explode('>',$path);


        $changeValue = null;

        foreach ($paths as $index=>$keyItem) {
            if ($index==0)
            {
                if ($keyItem=='extraInfo')
                {
                    $changeValue = $this->extraInfo;
                }
                else
                {
                    $changeValue = $this->results;
                }
            }
            else if ($keyItem!='')
            {
                if (isset($changeValue[$keyItem]))
                {
                    $changeValue = $changeValue[$keyItem];
                    continue;
                }
                $changeValue = null;
                break;
            }
        }

        $value = $this->value($changeValue);
        $this->pathCache[$path] = $value;
        return $value;
    }


    /** 传入值如果是model，则以当前Result为框架构建新Result，否则直接返回。 */
    public function value($value)
    {
        if (is_array($value))
        {
            if (isset($value['modelType']))
            {
                return static::instanceModel($value, $this->errorCode, $this->errorStr, $this->extraInfo, $this->resultCount);
            }
            else
            {
                $result = array();
                foreach ($value as $key => $tmpValue)
                {
                    $result[$key] = $this->value($tmpValue);
                }
                $value = $result;
            }
        }
        return $value;
    }

    /**
     * 根据path取值，如果不是数组，就转成数组
     * @param  string $path
     * @return array
     */
    public function findAsList($path)
    {
        $value = $this->find($path);

        if (!is_array($value) || (array_keys($value) !== array_keys(array_keys($value))) )
        {
            $value = array($value);
        }

        return $value;
    }

    /**
     * 根据path取值，如果不是字符串，就转成字符串
     * @param  string $path
     * @return string
     */
    public function findAsString($path)
    {
        $value = $this->find($path);

        if (!is_string($value))
        {
            $value = json_encode($value,JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }


    /**
     * 根据path取值，如果不是数字，就转成数字
     * @param  string $path
     * @return int
     */
    public function findAsInt($path)
    {
        $value = $this->find($path);

        if (!is_int($value))
        {
            $value = intval(json_encode($value,JSON_UNESCAPED_UNICODE));
        }

        return $value;
    }

    /**
     * 根据path取值，如果不是HaoResult类型，就转成HaoResult类型
     * @param  string $path
     * @return HaoResult
     */
    public function findAsResult($path)
    {
        $value = $this->find($path);

        if (!is_subclass_of($value,'HaoResult'))
        {
            $value = HaoResult::instanceModel($value, $this->errorCode, $this->errorStr, $this->extraInfo, $this->resultCount);
        }

        return $value;
    }


    /** 在结果中进行搜索，返回结果是数组（至少也是空数组） */
    public function search($path)
    {
        if ($this->searchIndexString == null)
        {
            $resultsIndex            = HaoUtility::getKeyIndexArray( ['results'=>$this->results] );
            $extraInfoIndex          = HaoUtility::getKeyIndexArray( ['extraInfo'=>$this->extraInfo] );
            $searchIndex             = array_merge( $resultsIndex , $extraInfoIndex );
            $this->searchIndexString = implode("\n",$searchIndex);
        }

        $path = trim($path);

        if ( strpos($path,'results>') !== 0 && strpos($path,'extraInfo>') !== 0 )
        {
        	$path = 'results>'.$path;
        }

        $result = array();
        $searchFoundCount = preg_match_all('/(^|\s)('.$path.')\s+/',$this->searchIndexString,$matches);
        if (is_array($matches) && count($matches)>=2)
        {
            foreach ($matches[2] as $pathMatched) {
                $result[] = $this->find($pathMatched);
            }
        }

        return $result;

    }

    /** 判断当前实例是否目标model */
    public function isModelType($modelType)
    {
        return strtolower($modelType) == strtolower($this->modelType);
    }

    /**
     * 判断是否等于目标ErroCode
     * @param  array  $errorCode  目标errorCode
     * @return boolean            是否一致
     */
    public function isErrorCode($errorCode)
    {
        return $this->errorCode === $errorCode;
    }

    /**
     * 判断是否正确获得结果
     * @return boolean            是否正确获得
     */
    public function isResultsOK()
    {
        return $this->isErrorCode(0) ;
    }

    /** 返回字典类型数据（重新包装成字典） */
    public function properties()
    {
        return array(
                        'errorCode'  => $this->errorCode,
                        'errorStr'   => $this->errorStr,
                        'extraInfo'  => $this->extraInfo,
                        'results'    => $this->results
                    );
    }

}
