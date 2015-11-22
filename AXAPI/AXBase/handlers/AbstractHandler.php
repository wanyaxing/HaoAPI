<?php
/**
* 抽象Handler类文件
* @package Handler
* @author axing
* @version 0.1
*/

class AbstractHandler {
// * 主要进行对应的表操作，要求表主键为第一个字段，（表名首字母必须小写，其他位置要求类名与表名字母完全一致）,模型类名与工厂类名完全一致(首字母大写），否则，必须手动指定tableIdName、tableName、tableDataKeys
// * PHP 5.3+
// * 再次强调：主要进行对应的表操作，要求表主键为第一个字段，要求类名与表名字母完全一致,模型类名与工厂类名完全一致，否则，必须手动指定tableIdName、tableName、tableDataKeys
// * 再次强调：要求规范如下：
// * 1.表中主键字段为自增id，
// * 2.表名首字母必须小写，类首字母必须大写，模型类名以Model结尾，工厂类名以Handler结尾，其他部分三者字母必须完全一致。如表ax_test 对应模型Ax_testModel.php 和 工厂Ax_testHandler.php
// * 如果表名等不符合以上规范，则必须在handler类里指定相关名称数据。（如果符合规范，则可以不指定）。但话又说回来，不管是否规范都推荐每个handler里直接指定好相关名称，这样就省去智能识别的代码了。
    /**
     * 对应的表的名称
     * @var string
     */
	public static $tableName = null;

    /**
     * 对应的表的主键字段
     * @var string
     */
	public static $tableIdName = null;

    /**
     * 对应表的常用字段数组
     * @var array
     */
	public static $tableDataKeys = null;

    /**
     * 对应的模型的类名
     * @var string
     */
	public static $modelName = null;

    /**
     * 进程级的查询用缓存，这是静态变量，所有的数组都放在这里。
     * @var array
     */
    public static $cache = array();

    /**
     * 是否使用进程级缓存，默认为开启，如果需要的时候，可以关掉。
     * @var boolean
     */
	public static $isUseCache    = True;

	// ================== database ==================

    /**
     * 获得详细的数据表字段数据（如备注信息等）
     * @return array
     */
    public static function getTableColumns()
    {
        return static::queryData('show full columns from '.static::getTabelName());
    }

    /**
     * 获得常规的表格字段数据
     * @return array 表格字段
     */
    public static function getTableDataKeys(){
        if (static::$tableDataKeys == null)
        {
            $_dbModel = static::newDBModel();
            static::$tableDataKeys = $_dbModel->getMeta();
        }
        return static::$tableDataKeys;
    }

    /**
     * 当前handler所面向的主要表名称，默认会从类名中截取字符作为表名
     * @return string 表名
     */
    public static function getTabelName() {
        if (static::$tableName == null)
        {
            static::$tableName = lcfirst(str_replace('Handler','',get_called_class()));
        }
        return static::$tableName;
    }

    /**
     * 当前handler所面向的表的主键名，默认为表字段的第一个字段
     * @return string 表名
     */
    public static function getTabelIdName() {
        if (static::$tableIdName == null)
        {
        	$_fieldList = static::getTableDataKeys();
        	if (count($_fieldList)>0)
        	{
        		static::$tableIdName = $_fieldList[0];
        	}
        }
        return static::$tableIdName;
    }

    /**
     * 创建一个新模型操作实例
     * @return DBModel
     */
    public static function newDBModel(){
        $_dbModel = new DBModel(static::getTabelName());
        $_dbModel->isUseCache(static::isUseCache());
        return $_dbModel;
    }

    /**
     * 当前handler所对应的模型类的类名，默认为从类名中截取字符作为模型名
     * @return string 表名
     */
    public static function getModelName() {
        if (static::$modelName == null)
        {
    		static::$modelName = ucfirst(str_replace('Handler','Model',get_called_class()));//取得对应的model类名
        }
        return static::$modelName;
    }


    /**
     * 根据数据创建对应的模型
     * @return array 表格字段
     */
	public static function createModel($p_data=null){
		$_cls = static::getModelName();
        $_model = $_cls::instance($p_data);//创建model实例
    	if (is_array($p_data) && array_key_exists(static::getTabelIdName(),$p_data))
    	{
    		$_model->setId($p_data[static::getTabelIdName()]);
    	}
        return $_model;
	}

    //＝＝＝＝＝＝＝＝＝＝进程级缓存操作＝＝＝＝＝＝＝＝＝＝

    /**
     * 判断指定key的缓存是否存在
     * @param  string $p_key 存储key值
     * @return boolean        是/否
     */
    public static function cacheExsits($p_key){
        return static::$isUseCache && array_key_exists($p_key, static::$cache);
    }

    /**
     * 读取指定key的缓存
     * @param  string $p_key 存储key值
     * @return [type]        对象或数据
     */
    public static function cacheLoad($p_key){
        if (static::cacheExsits($p_key))
        {
            return clone static::$cache[$p_key];
        }
        return null;
    }

    /**
     * 删除指定key的缓存
     * @param  string $p_key 存储key值
     * @return boolean        是/否
     */
    public static function cacheRemove($p_key){
        if (static::cacheExsits($p_key))
        {
            unset(static::$cache[$p_key]);
        }
        return true;
    }

    /**
     * 存储数据到指定key
     * @param  string $p_key  存储key值
     * @param  object $p_data 对象或数据
     * @return boolean         是/否
     */
    public static function cacheSave($p_key,$p_data)
    {
        static::$cache[$p_key] = clone $p_data;
        return True;
    }

    /**
     * 是否使用请求内缓存
     */
    public static function isUseCache()
    {
        return ($_SERVER['REQUEST_METHOD'] == 'GET') && static::$isUseCache;//POST数据不用缓存，不然会有各种奇葩问题。
    }

    /**
     * 临时开关缓存
     * @param  string $p_key  存储key值
     * @param  object $p_data 对象或数据
     * @return boolean         是/否
     */
    // public static function cacheEnable($p_isEnable=true)
    // {
    //     static::$isUseCacheTmp = static::$isUseCache;
    //     static::$isUseCache = $p_isEnable;
    //     return $p_isEnable;
    // }

    /**
     * 取消临时开关
     */
    // public static function cacheEnableRestore()
    // {
    //     if (static::$isUseCacheTmp!==null)
    //     {
    //         static::$isUseCache = static::$isUseCacheTmp;
    //         static::$isUseCacheTmp = null;
    //     }
    //     return $p_isEnable;
    // }

	//====================常规方法=====================

    /**
     * 根据主键值查询单条记录
     * @return AbstractModel 对应的model 实例
     */
    public static function loadModelById($p_id=null)
    {
        $_d = static::loadModelListByIds($p_id);
        if (isset($_d) && is_array($_d) && count($_d)>0)
        {
            return $_d[0];
        }
        else
        {
            return null;
        }
    }


    /**
     * 根据筛选条件，筛选获得对象数组的第一个数据
     * @see AbstractHandler::loadModelList()
     * @return AbstractModel         对象模型数组
     */
    public static function loadModelFirstInList($p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=1,&$p_countThis=-1)
    {
        $_d = static::loadModelList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);
        if (isset($_d) && is_array($_d) && count($_d)>0)
        {
            return $_d[0];
        }
        else
        {
            return null;
        }
    }
    /**
     * 指定ids查询，根据多个主键值查询多条记录,注意，这里返回的数组以传入的id顺序一致
     * @param  array $p_ids 数组id,或逗号隔开的id字符串
     * @return AbstractModel[]        对应的model 实例数组
     */
    public static function loadModelListByIds($p_ids=null)
    {
        if (!isset($p_ids) || $p_ids==0)
        {
            return array();//参数错误
        }
        $p_ids = (is_array($p_ids)) ? $p_ids : explode(',', $p_ids);

        $_dbModel = static::newDBModel();
        $_dbModel->isUseCache(false);
        $_objList = array();
        $_idsTmp = array();
        foreach ($p_ids as $_key => $p_id) {
            //使用请求进程内缓存（仅单次请求内有效）
            if (static::cacheExsits($p_id)) {
                $_objList[$p_id] = static::cacheLoad($p_id);
                AX_DEBUG('使用请求内缓存：'.$p_id);
            }
            else if ($p_id>0)
            {
                $w2CacheObj = null;
                if (static::isUseCache())
                {
                    //使用W2Cache全局内存型缓存（全局有效）
                    $w2CacheKey = sprintf('ax_%s_model_%s_id_%d',AXAPI_PROJECT_NAME,static::getTabelName(),$p_id);
                    if (W2Cache::isCacheCanBeUsed($w2CacheKey))
                    {
                        $w2CacheObj = W2Cache::getObj($w2CacheKey);
                    }
                }
                if (isset($w2CacheObj))
                {
                    static::cacheSave( $p_id , $w2CacheObj);
                    $_objList[$p_id] = $w2CacheObj;
                    AX_DEBUG('读取缓存成功：'.$w2CacheKey);
                }
                //没找到缓存，去请求。
                else
                {
                    $_idsTmp[] = $p_id;
                }

            }
        }

        if (count($_idsTmp)>0)
        {
            if (count($_idsTmp)>1)
            {
                $_dbModel->where(sprintf('%s in (%s)',static::getTabelIdName(),implode(',',$_idsTmp)))
                		->limit(sprintf('%d,%d'
    		                                ,0
    		                                ,count($_idsTmp)
    		                                ));
            }
            else
            {
                $_dbModel->where(sprintf('%s = %s',static::getTabelIdName(),implode(',',$_idsTmp)))
                			->limit(sprintf('%d,%d'
                                    ,0
                                    ,1
                                    ));
            }
            $_data = $_dbModel->field(static::getTableDataKeys())->select();
            if (count($_data)>0) {
                foreach ($_data as $_index=>$_d) {
                    $_obj = static::createModel($_d);
                    $_objList[$_obj->getId()] = $_obj;
                    //存储到单次请求进程内缓存（其实就是变量）
                    static::cacheSave( $_obj->getId() , $_obj);
                    //存储到全局缓存（用redis等方法存起来）
                    $w2CacheKey = sprintf('ax_%s_model_%s_id_%d',AXAPI_PROJECT_NAME,static::getTabelName(),$_obj->getId());
                    AX_DEBUG('更新缓存：'.$w2CacheKey);
                    W2Cache::setObj($w2CacheKey,$_obj);
                }
            }
        }

        $_dataList = array();
        foreach ($p_ids as $_key => $p_id) {
            if (array_key_exists($p_id, $_objList))
            {
                $_dataList[] = $_objList[$p_id];
            }
        }

        return $_dataList;
    }

    /**
     * 查询单独字段的值的数组，如不指定$field，则返回首条数据的首个字段的值组成的数组
     * @param  string  $p_field      [description]
     * @param  array   $p_where     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $p_order     排序方式，如'lastmodifytime desc'
     * @param  integer $p_pageIndex 分页，第一页为1，第二页为2
     * @param  integer  $p_pageSize  分页数据量
     * @param  integer  $p_countThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return array                单独字段的值的数组
     */
    public static function selectFields($p_field=null,$p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=DEFAULT_PAGE_SIZE,&$p_countThis=-1)
    {
        $_fieldValues = null;
        if ($p_field===null)
        {
            $p_field = static::getTabelIdName();
        }
        if ( static::isUseCache() )
        {
            $w2CacheKey = sprintf('ax_list_%s_field_%s_where_%s_order_%s_page_%d_size_%d_countthis_%s'
                                        ,static::getTabelName()
                                        ,$p_field
                                        ,W2Array::sortAndBuildQuery($p_where)
                                        ,$p_order
                                        ,$p_pageIndex
                                        ,$p_pageSize
                                        ,$p_countThis
                                        );
            $w2CacheKey_fieldValues = $w2CacheKey.'_list';
            if ($_SERVER['REQUEST_METHOD'] == 'GET')
            {//只有GET请求才会使用缓存。为安全计，POST请求就耗点性能吧。
                if ( !isset($p_where['userID']) || $p_where['userID'] != Utility::getCurrentUserID() )
                {//查询与自己无关的数据，使用缓存。如果用户检索自己相关的数据时，不用缓存

                    //尝试读取列表检索结果
                    if (W2Cache::isCacheCanBeUsed($w2CacheKey_fieldValues))
                    {
                         $w2CacheObj_fieldValues = W2Cache::getObj($w2CacheKey_fieldValues);
                    }

                    //如果有结果，再尝试检索总数
                    if (isset($w2CacheObj_fieldValues))
                    {
                        if ($p_countThis != -1)
                        {
                            $w2CacheKey_countThis = $w2CacheKey.'_count';
                            if (W2Cache::isCacheCanBeUsed($w2CacheKey_countThis))
                            {
                                $w2CacheObj_countThis = W2Cache::getObj($w2CacheKey_countThis);
                            }
                        }
                    }
                }
            }
        }

        //如果两个缓存任意一个没取成功，就重新取数据。
        if (!isset($w2CacheObj_fieldValues) || ( $p_countThis != -1 && !isset($w2CacheObj_countThis) ) )
        {//没有找到缓存，重新请求。
            $_dbModel = static::newDBModel();
            if ($p_countThis != -1)
            {
                $_dbModel->isCountAll(true);
            }
            $_dbModel->where($p_where)->limit($p_pageIndex,$p_pageSize)->order($p_order);
            $_fieldValues = array();
            if ( $p_pageSize !== 0 )
            {//只有$p_pageSize !== 0 才需要去实际查询数据,如果为0自然连查都不用查了，直接默认空数组就是。
                $_fieldValues = $_dbModel->selectFields($p_field);
            }
            if ($p_countThis != -1)
            {
                $p_countThis = $_dbModel->countAll();
            }

            if (isset($w2CacheKey_fieldValues))
            {
                //更新缓存／存储数据
                W2Cache::setObj($w2CacheKey_fieldValues,$_fieldValues);
                AX_DEBUG('更新缓存：'.$w2CacheKey_fieldValues);

                //更新缓存／存储计数
                if (isset($w2CacheKey_countThis))
                {
                    W2Cache::setObj($w2CacheKey_countThis,$p_countThis);
                }

                //追加到缓存池
                foreach ($p_where as $_key => $_value) {
                    if ($_value===null)
                    {
                        continue;
                    }
                    if (is_string($_key) && in_array($_key,static::getTableDataKeys()))
                    {
                        if ((is_int($_value) || (is_string($_value) && strlen($_value)<10 ) ))
                        {
                            $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                                                ,AXAPI_PROJECT_NAME
                                                ,static::getTabelName()
                                                ,$_key
                                                ,$_value
                                                );
                            W2Cache::addToCacheKeyPool($w2CacheKeyPool,$w2CacheKey_fieldValues);
                            AX_DEBUG('追加到缓存池：'.$w2CacheKeyPool);
                        }
                    }
                    else
                    {
                        if ($_key == 'joinList' && is_array($_value))
                        {
                            foreach ($_value as $_join) {
                                $_tblParams = explode(' ',trim($_join[0]));
                                if (count($_tblParams)==2)
                                {
                                    $_tbl = $_tblParams[0];
                                    $_tblT2 = $_tblParams[1];
                                }
                                foreach ($_join[1] as $_tblWhereKey => $_tblWhereValue) {
                                    if ((strpos($_tblWhereKey,$_tblT2)!==false) && (is_int($_tblWhereValue) || (is_string($_tblWhereValue) && strlen($_tblWhereValue)<10 ) ) )
                                    {
                                        $_tblWhereKey = preg_replace('/^.*?'.$_tblT2.'\.([^\s]+).*/','$1',$_tblWhereKey);
                                        $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                                                            ,AXAPI_PROJECT_NAME
                                                            ,$_tbl
                                                            ,$_tblWhereKey
                                                            ,$_tblWhereValue
                                                            );
                                        W2Cache::addToCacheKeyPool($w2CacheKeyPool,$w2CacheKey_fieldValues);
                                        AX_DEBUG('追加到额外缓存池：'.$w2CacheKeyPool);
                                    }
                                }
                            }
                        }
                        else if ( is_int($_key) && strpos($_value,'exists')!==false)
                        {
                            $_tbl = preg_replace('/^[\s\S]*? from\s+?(\S+)\s+(\S+)\s[\s\S]+$/','$1',$_value);
                            $_tblT2 = preg_replace('/^[\s\S]*? from\s+?(\S+)\s+(\S+)\s[\s\S]+$/','$2',$_value);
                            AX_DEBUG('/'.$_tblT2.'\.(\S+)\s*=\s*([^\s\)]+)/');
                            preg_match_all('/'.$_tblT2.'\.(\S+)\s*=\s*([^\s\)]+)/',$_value,$matches,PREG_SET_ORDER);
                            foreach ($matches as $match) {
                                $_tblWhereKey = $match[1];
                                $_tblWhereValue = trim($match[2],'\'');
                                if (!preg_match('/[^\s\.]\.[^\s\.]/',$_tblWhereValue))
                                {
                                    if (is_int($_tblWhereValue) || (is_string($_tblWhereValue) && strlen($_tblWhereValue)<10 ) )
                                    {
                                        $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                                                            ,AXAPI_PROJECT_NAME
                                                            ,$_tbl
                                                            ,$_tblWhereKey
                                                            ,$_tblWhereValue
                                                            );
                                        W2Cache::addToCacheKeyPool($w2CacheKeyPool,$w2CacheKey_fieldValues);
                                        AX_DEBUG('追加到额外缓存池：'.$w2CacheKeyPool);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        else
        {
            AX_DEBUG('读取缓存成功：'.$w2CacheKey_fieldValues);
            if (isset($w2CacheObj_fieldValues))
            {
                $_fieldValues = $w2CacheObj_fieldValues;
            }
            if (isset($w2CacheObj_countThis))
            {
                $p_countThis = $w2CacheObj_countThis;
            }
        }
        return $_fieldValues;

    }


    /**
     * 查询单独数据，如不指定$field，则返回首条数据的首个字段的值
     * @param  string  $p_field      [description]
     * @param  array   $p_where     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $p_order     排序方式，如'lastmodifytime desc'
     * @param  integer $p_pageIndex 分页，第一页为1，第二页为2
     * @param  integer  $p_pageSize  分页数据量
     * @param  integer  $p_countThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return string|int
     */
    public static function selectField($p_field=null,$p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=DEFAULT_PAGE_SIZE,&$p_countThis=-1)
    {
        $_fieldValues = static::selectFields($p_field,$p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);

        if (is_array($_fieldValues) && count($_fieldValues)>0)
        {
            return $_fieldValues[0];
        }
        else
        {
            return null;
        }
    }
    /**
     * 批量查询，根据筛选条件，筛选获得对象数组
     * @param  array   $p_where     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $p_order     排序方式，如'lastmodifytime desc'
     * @param  integer $p_pageIndex 分页，第一页为1，第二页为2
     * @param  integer  $p_pageSize  分页数据量
     * @param  integer  $p_countThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return AbstractModel[]         对象模型数组
     */
    public static function loadModelList($p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=DEFAULT_PAGE_SIZE,&$p_countThis=-1)
    {
        $_pIds = static::selectFields(static::getTabelIdName(),$p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);
        return static::loadModelListByIds($_pIds);
    }


    /**
     * 存储或更新模型对象
     * @param  AbstractModel $p_model 新建或改动后的模型
     * @return AbstractModel         返回更新后的模型对象
     */
    public static function saveModel($p_model)
    {
        if (!isset($p_model) || get_class($p_model)!= static::getModelName())
        {
            throw new Exception('此处需要传入'.static::getModelName().'类型的对象');
        }

        $_dbModel = static::newDBModel();

        $_updateData = array();
        foreach ($p_model->propertiesModified() as $_key => $_value) {
            if ($_value===null)
            {
                continue;
            }
            /** 更新缓存池 */
            if ( in_array($_key, static::getTableDataKeys() )  )
            {
                // if ($_key!='id' && $_key!=static::getTabelIdName() )
                // {
                    $_updateData[$_key] = $_value;
                // }

                if ((is_int($_value) || (is_string($_value) && strlen($_value)<10 ) ))
                {
                    $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                                        ,AXAPI_PROJECT_NAME
                                        ,static::getTabelName()
                                        ,$_key
                                        ,$_value
                                        );
                    W2Cache::resetCacheKeyPool($w2CacheKeyPool);
                    AX_DEBUG('更新缓存池：'.$w2CacheKeyPool);
                }

                $_valueOriginal = $p_model->properyOriginal($_key);
                if ((is_int($_valueOriginal) || (is_string($_valueOriginal) && strlen($_valueOriginal)<10 ) ))
                {
                    if ($_valueOriginal!=null)
                    {
                        $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                                            ,AXAPI_PROJECT_NAME
                                            ,static::getTabelName()
                                            ,$_key
                                            ,$_valueOriginal
                                            );
                        W2Cache::resetCacheKeyPool($w2CacheKeyPool);
                        AX_DEBUG('更新缓存池：'.$w2CacheKeyPool);
                    }
                }
            }
        }

        AX_DEBUG($_updateData);

        $_modelId = null;

        if ( is_object( static::loadModelById( $p_model->getId() ) ) )//如果目标数据已存在，则更新
        {
            /** 更新数据 */
            $_dbModel -> where(array(static::getTabelIdName() => $p_model->getId()))
                      -> limit(1)
                      ->update($_updateData);
            $_modelId = $p_model->getId();

            //更新请求缓存
            static::cacheRemove($p_model->getId());
        }
        else
        {
            /** 新数据 */
            $_dbModel -> insert($_updateData);
            $_modelId = $_dbModel->init()
                        ->where($_updateData)->order(sprintf('%s desc',static::getTabelIdName()))
                        ->field(static::getTabelIdName())
                        ->selectSingle();
            $_modelId = is_array($_modelId)?$_modelId[static::getTabelIdName()]:0;
        }

        static::resetW2CacheByModelId($_modelId);//更新缓存

        return static::loadModelById($_modelId);
    }


    /**
     * 直接插入数据
     * @param  array  $p_values  需要插入的数据
     * @return [type]          [description]
     */
    public static function insert($p_values=array())
    {
        $_dbModel = static::newDBModel();
        $_dbModel->insert($p_values);
    }

    /**
     * 根据筛选结果，直接操作数据库进行数据更新
     * @param  array $p_values 需要更新的数据
     * @param  array  $p_where  筛选条件
     * @return
     */
    public static function update($p_values,$p_where=array())
    {
        $_dbModel = static::newDBModel();
        $result = $_dbModel ->update($p_values,$p_where);
        if (isset($p_where['id']))
        {
            static::resetW2CacheByModelId($p_where['id']);//更新缓存
        }
        return $result ;
    }

    /**
     * 根据条件直接删除数据
     * @param  array  $p_where 筛选条件
     * @return [type]          [description]
     */
    public static function delete($p_where=array())
    {
        $_dbModel = static::newDBModel();
        $_dbModel->delete($p_where);
    }

    /** 执行sql语句 */
    public static function queryData($sql)
    {
        //使用W2Cache全局内存型缓存（全局有效）
        $w2CacheKey = sprintf('ax_%s_query_%s_sql_%s',AXAPI_PROJECT_NAME,static::getTabelName(),$sql);
        if (W2Cache::isCacheCanBeUsed($w2CacheKey))
        {
            if ($_SERVER['REQUEST_METHOD'] == 'GET')
            {//只有GET请求才会使用缓存。为安全计，POST请求就耗点性能吧。
                if ( !( Utility::getCurrentUserID()>0 && preg_match('/userID.{0,10}'.Utility::getCurrentUserID().'/',$sql) ) )
                {
                    AX_DEBUG('读取缓存成功：'.$w2CacheKey);
                    return W2Cache::getObj($w2CacheKey);
                }
            }
        }

        $data = DBTool::queryData($sql);

        //存储到全局缓存（用redis等方法存起来）
        AX_DEBUG('更新缓存：'.$w2CacheKey);
        W2Cache::setObj($w2CacheKey,$data);

        return $data;
    }

    /**
     * 更新缓存W2Cache
     * @param  int $_modelId [description]
     * @return [type]           [description]
     */
    public static function resetW2CacheByModelId($_modelId,$_tableName = null)
    {
        if ($_modelId>0)
        {
            if ($_tableName==null)
            {
                $_tableName = static::getTabelName();
            }
            $w2CacheKey = sprintf('ax_%s_model_%s_id_%d',AXAPI_PROJECT_NAME,$_tableName,$_modelId);
            W2Cache::resetCache($w2CacheKey);
            AX_DEBUG('更新缓存：'.$w2CacheKey);
        }
    }
    // /**
    //  * 来从数据库中删除对象实例
    //  * @param  object $p_model 对应的model 实例
    //  * @return boolean          [description]
    //  */
    // public static function removeModel($p_model) {
    //     if (!isset($p_model) || get_class($p_model)!= static::getModelName() || $p_model->getId()==0)
    //     {
    //        throw new Exception('此处需要传入'.static::getModelName().'类型的对象');
    //     }

    //     return static::removeModelById($p_model->getId());
    // }

    // /**
    //  * 根据主键值删除单条记录
    //  * @return  integer $p_id 对应的modelID主键
    //  */
    // public static function removeModelById($p_id=null) {
    //     return static::removeModelListByIds($p_id);
    // }

    // /**
    //  * 根据多个主键值删除多条记录
    //  * @param  array $p_ids 数组id主键,或逗号隔开的id字符串
    //  * @return array        对应的model 实例数组
    //  */
    // public static function removeModelListByIds($p_ids=null) {
    //     if (!isset($p_ids) || $p_ids==0)
    //     {
    //         return false;//参数错误
    //     }
    //     $p_ids = (is_array($p_ids)) ? $p_ids : explode(',', $p_ids);

    //     $_dbModel = static::newDBModel();
    //     $_idsTmp = array();
    //     foreach ($p_ids as $_key => $p_id) {
    //         if ($p_id>0)
    //         {
    //             $_idsTmp[] = $p_id;
    //         }
    //     }
    //     if (count($_idsTmp)>0)
    //     {
    //         if (count($_idsTmp)>1)
    //         {
    //             $_dbModel->where(sprintf('%s in (%s)',static::getTabelIdName(),implode(',',$_idsTmp)))
    //                       ->limit(sprintf('%d,%d'
    //                                         ,0
    //                                         ,count($_idsTmp)
    //                                         ));
    //         }
    //         else
    //         {
    //             $_dbModel ->where(sprintf('%s = %s',static::getTabelIdName(),implode(',',$_idsTmp)))
    //                        ->limit(sprintf('%d,%d'
    //                                 ,0
    //                                 ,1
    //                                 ));
    //         }
    //         $_data = $_dbModel->isUseCache(false)->delete();
    //         if ($_data>0) {
    //             foreach ($_idsTmp as $_index=>$_d) {
    //                 static::cacheRemove( $_d);
    //             }
    //         }
    //     }
    //     return true;
    // }

    /**
     * 根据指定统计类型进行数据统计
     * @param  string $p_dateType 统计方法 ： year month day
     * @param  array  $p_where    筛选条件
     * @return array             统计结果
     */
    public static function _countWithDate($p_dateType,$p_where=array())
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
        $dbFacotory = static::newDBModel();
        $result = $dbFacotory->useT1(null)->field('DATE_FORMAT(createTime, \''.$format.'\') AS CountDateTime , COUNT(id) AS CountNum')->where($p_where)->group('CountDateTime')->order('CountDateTime')->select();
        return $result;
    }

    /**
     * 统计符合条件的数量
     * @param  array $p_where 条件
     * @return int          总数
     */
    public static function count($p_where=array())
    {
        return static::selectField('count(*)',$p_where);
    }
    public static function countAll($p_where=array())
    {
        return static::count($p_where);
    }

}
