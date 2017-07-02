<?php
/**
* 抽象模型类文件,将对应的表数据数组转化成实例对象，要求类名与表名字母完全一致,模型类名与工厂类名完全一致
* @package Model
* @author axing
* @version 0.1
*/

class AbstractModel {
    public static $authViewDisabled    = array();//展示数据时，禁止列表。

    /**
     * 此处用来在第一次初始化完成后，存储对象属性，便于以后确认新增的属性修改
     * @var array
     */
    protected $snapshot = array();
    // ======================== method ========================

    /**
     * 工厂方法,抽象模型类中，用来将数组初始化成对象，其他现实类中由instance方法来调用父级的instanceModel 数组-》实例
     * @param string subclass name
     * @param int|array  $p_data 如果是整数, 赋值给对象的id,如果是数组, 给对象的逐个属性赋值
     * @return Model 模型对象
     */
    protected static function instanceModel($p_cls, $p_data) {
        $_o = new $p_cls();
        if (is_array($p_data)) {
            foreach ($p_data as $_k => $_v) {
                if (property_exists($_o,$_k))
                {
                    $_o->$_k = $_v;
                }
            }
        } else if(is_string($p_data) || is_int($p_data)){
            $_o -> setId($p_data);
        }
        return $_o;
    }

    /**
     * 初始化方法，各模型必须重写此处，来调用父级的instanceModel 数组-》实例
     * @param int|array  $p_data 如果是整数, 赋值给对象的id,如果是数组, 给对象的逐个属性赋值
     * @return object 对象模型
     */
    public static function instance($p_data=null) {
        $_o = self::instanceModel(__class__, $p_data);
        return $_o;
    }

    /** 指定路径是否被允许打印 */
    public static function isPathAllowed($paths,$isObject=true)
    {
        $isAllow = true;
        $pathsString = implode('>',$paths);
        if (isset(static::$findPaths) && count(static::$findPaths)>0)
        {
            $isAllow = false;
            $findIndexString = implode("\n",static::$findPaths);
            if (preg_match('/(^|\s)('.$pathsString.($isObject?'.*':'').')($|\s+)/',$findIndexString))
            {
                return true;
            }
        }
        if (isset(static::$searchPaths) && count(static::$searchPaths)>0)
        {
            $isAllow = false;
            foreach (static::$searchPaths as $searchPath) {
                if ($isObject)
                {
                    if (W2String::pregPartMatch($searchPath,$pathsString))
                    {
                        return true;
                    }
                }
                else
                {
                    if (preg_match('/^'.$searchPath.'$/',$pathsString))
                    {
                        return true;
                    }
                }
            }
        }
        return $isAllow;
    }

    /**
     * 递归方法，对结果的值进行处理
     * @param  array &$_ps                  结果值
     * @param  string $_n                   键
     * @param  object|array|string $result              值
     * @param  array  $p_foundDeepModelList Model列表，用于防止死循环
     * @param  array  $p_path               路径列表，用于路径筛选
     * @param  AbstractModel $rootModel            根对象，用于确定输出的最终对象的根目录
     * @return
     */
    protected static function propertyDeep(&$_ps,$_n,$result,$p_foundDeepModelList=array(),$p_path=array(),$rootModel=null)
    {
        $_pathTmp = array_merge($p_path,array($_n));
        if (is_null($rootModel) || $rootModel::isPathAllowed($_pathTmp,(is_object($result) || is_array($result))))
        {
            $data = $result;
            if (is_object($result) && is_subclass_of($result,'AbstractModel'))
            {
                $data = $result->properties($p_foundDeepModelList,null,$_pathTmp,$rootModel);
            }
            else if (is_array($result))//&& array_key_exists(0, $result)
            {
                $data = array();
                foreach ($result as $_key => $_value) {
                    $rootModel::propertyDeep($data,$_key,$_value,$p_foundDeepModelList,$_pathTmp,$rootModel);
                }
            }
           $_ps[$_n]=$data;
        }
    }
    /**
     * 获取模型实例的所有属性转化成数组  实例-》数组
     * @param string|array $p_exclude 排除字段
     * @return array 类的所用属性
     */
    public function properties($p_foundDeepModelList=array(),$p_exclude=null,$p_path=array(),$rootModel=null) {
        $_classid = get_class($this).'.'.$this->getId();
        if (in_array($_classid,$p_foundDeepModelList))
        {
            return null;
        }
        else
        {
            $p_foundDeepModelList[] =$_classid;
        }
        $_ps = array();
        if (is_string($p_exclude))
        {
            $p_exclude = explode(',', $p_exclude);
        }
        $isRootModel = is_null($rootModel);
        if (is_null($rootModel))
        {
            $rootModel = get_class($this);
        }
        $_ms = get_class_methods(get_class($this));
        foreach ($_ms as $_name) {
            $_nameGet = null;
            if (substr($_name, 0, 3) == 'get') {
                $_nameGet = $_name;
            }
            if (isset($_nameGet))
            {
                $_n = lcfirst(substr($_nameGet, 3));
                if (($p_exclude===null || (is_array($p_exclude) && !in_array($_n, $p_exclude) )) && (!in_array($_n, static::$authViewDisabled) && !in_array('*', static::$authViewDisabled))  )
                {
                    $result = call_user_func(array($this, $_nameGet));
                    static::propertyDeep($_ps,$_n,$result,$p_foundDeepModelList,$p_path,$rootModel);
                }
            }
        }

        if ($isRootModel && isset($rootModel::$searchPaths) && count($rootModel::$searchPaths)>0 )
        {
            W2Array::unsetEmptyArray($_ps['results']);
        }

        return $_ps;
    }

    /**
     * 与初始化完成时的对象快照相比较，返回新增的修改的属性
     * @return array() 新增的修改后的属性数组
     */
    public function propertiesModified()
    {
        $tmpVars = get_object_vars($this);
        $tmpVars['snapshot'] = '';
        $tmpDiff=array_diff_assoc($tmpVars,$this->snapshot);
        foreach ($tmpDiff as $key => $value) {
            if ($value === null)
            {
                unset($tmpDiff[$key]);
            }
        }
        return $tmpDiff;
    }

    /**
     * 判断属性是否被修改过
     * @param  string  $property 属性
     * @return boolean           是否改动过
     */
    public function isProperyModified($property)
    {
        return array_key_exists($property,$this->propertiesModified());
    }

    /**
     * 取出初始化时的元素数据
     * @param  string  $property 属性
     * @return string            值
     */
    public function properyOriginal($property)
    {
        if (property_exists($this,$property))
        {
            return (isset($this->snapshot[$property]))?$this->snapshot[$property]:null;
        }
        return null;
    }

    /**
     * 取出有效的值
     * @param  string  $property 属性
     * @return string            值
     */
    public function properyValue($property)
    {
        if (property_exists($this,$property))
        {
            return ($this->$property===null && isset($this->snapshot[$property]))?($this->snapshot[$property]):($this->$property);
        }
        return null;
    }

    /**
     * 取出初始化时的所有元素
     */
    public function properiesOriginal()
    {
        $original = $this->snapshot;
        if (!is_array($original))
        {
            $original = array();
        }
        return $original;
    }

    /**
     * 取出所有有效值的组合
     */
    public function properiesValue()
    {
        $modifies = $this->propertiesModified();
        $values = $this->properiesOriginal();
        foreach ($modifies as $key => $value) {
            $values[$key] = $value;
        }
        return $values;
    }

    /** 判断当前model是否新的model(即原快照都是空的值，或者原快照只有个modelType) */
    public function isNewModel()
    {
        return $this->snapshot==null || count(array_filter($this->snapshot))<=1;
    }
    // ======================== variable ========================

    public $id;

    // ======================== proterty ========================

    public function getId() {
        return $this->id;
    }

    public function setId($p_id) {
        $this->id = $p_id;
    }

    public function getModelType() {
        return str_replace('Model', '', get_class($this));
    }

    // ======================== construct/destruct ========================

    public function __construct($p_id=null) {
        $this->id = $p_id;
    }

}

?>
