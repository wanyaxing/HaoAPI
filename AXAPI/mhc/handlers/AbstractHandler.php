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
    public static function getTableDataKeys($isWithSingleQuote = false){
        if (static::$tableDataKeys == null)
        {
            $_dbModel = static::newDBModel();
            static::$tableDataKeys = $_dbModel->getMeta();
        }
        if ($isWithSingleQuote)
        {
            $quotedKeys = array();
            foreach (static::$tableDataKeys as $key => $value) {
                $quotedKeys[] = '`'.trim($value,'`').'`';
            }
            return $quotedKeys;
        }
        return static::$tableDataKeys;
    }

    /**
     * 通过表格字段数据筛选数组
     * @return array
     */
    public static function filterTableDataKeysInArray($tArray,$isConvertNull = false){
        $filteredArray = array();
        foreach ($tArray as $key => $value) {
            if ( in_array($key, static::getTableDataKeys() )  )
            {
                if ($isConvertNull && $value===null)
                {
                    $value = 'NULL';//DBModel的特殊原因，null值在查询或处理时会被忽略，所以如果不想忽略，必须强指定为字符串'NULL'。（而如果想使用字符串NULL赋值，暂不支持。）
                }
                $filteredArray[$key] = $value;
            }
        }
        return $filteredArray;
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
     * 当前handler所面向的表的主键名，如果没有就为空。（开发时最好每个表都指定主键）
     * @return string 表名
     */
    public static function getTabelIdName() {
        // if (static::$tableIdName == null)
        // {

        //  $_fieldList = static::getTableDataKeys();

        //  if (count($_fieldList)>0)

        //  {

        //      static::$tableIdName = $_fieldList[0];

        //  }
        // }
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

    public static function createModel($pData=null){

        $_cls = static::getModelName();
        $_model = $_cls::instance($pData);//创建model实例

        if (static::getTabelIdName()!=null && is_array($pData) && array_key_exists(static::getTabelIdName(),$pData))

        {

            $_model->setId($pData[static::getTabelIdName()]);

        }
        return $_model;

    }

    //＝＝＝＝＝＝＝＝＝＝进程级缓存操作＝＝＝＝＝＝＝＝＝＝

    /**
     * 判断指定key的缓存是否存在
     * @param  string $pId 存储key值
     * @return boolean        是/否
     */
    public static function cacheExsits($pId){
        return static::isUseCache() && array_key_exists($pId, static::$cache);
    }

    /**
     * 读取指定key的缓存
     * @param  string $pId 存储key值
     * @return [type]        对象或数据
     */
    public static function cacheLoad($pId){
        if (static::cacheExsits($pId))
        {
            return clone static::$cache[$pId];
        }
        return null;
    }

    /**
     * 删除指定key的缓存
     * @param  string $pId 存储key值
     * @return boolean        是/否
     */
    public static function cacheRemove($pId){
        if ( array_key_exists($pId, static::$cache) )
        {
            unset(static::$cache[$pId]);
        }
        return true;
    }

    /**
     * 存储数据到指定key
     * @param  string $pId  存储key值
     * @param  object $pData 对象或数据
     * @return boolean         是/否
     */
    public static function cacheSave($pId,$pData)
    {
        static::$cache[$pId] = clone $pData;
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
     * @param  string $pKey  存储key值
     * @param  object $pData 对象或数据
     * @return boolean         是/否
     */
    // public static function cacheEnable($pIsEnable=true)
    // {
    //     static::$isUseCacheTmp = static::$isUseCache;
    //     static::$isUseCache = $pIsEnable;
    //     return $pIsEnable;
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
    //     return $pIsEnable;
    // }


    //====================常规方法=====================

    /**
     * 根据主键值查询单条记录
     * @return AbstractModel 对应的model 实例
     */
    public static function loadModelById($pId=null)
    {
        $_d = static::loadModelListByIds($pId);
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
    public static function loadModelFirstInList($pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=1,&$pCountThis=-1)
    {
        $_d = static::loadModelList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);
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
     * @param  array $pIds 数组id,或逗号隔开的id字符串
     * @return AbstractModel[]        对应的model 实例数组
     */
    public static function loadModelListByIds($pIds=null)
    {
        if (!isset($pIds) || $pIds==0)
        {
            return array();//参数错误
        }
        $pIds = (is_array($pIds)) ? $pIds : explode(',', $pIds);

        $_dbModel = static::newDBModel();
        $_dbModel->isUseCache(false);
        $_objList = array();
        $_idsTmp = array();
        foreach ($pIds as $_key => $pId) {
            //使用请求进程内缓存（仅单次请求内有效）
            if (static::cacheExsits($pId)) {
                $_objList[$pId] = static::cacheLoad($pId);
                AX_DEBUG('使用请求内缓存：'.$pId);
            }
            else if ($pId>0)
            {
                $w2CacheObj = null;
                if (static::isUseCache())
                {
                    //使用W2Cache全局内存型缓存（全局有效）
                    $w2CacheKey = sprintf('ax_%s_model_%s_id_%d',AXAPI_PROJECT_NAME,static::getTabelName(),$pId);
                    if (W2Cache::isCacheCanBeUsed($w2CacheKey))
                    {
                        $w2CacheObj = W2Cache::getObj($w2CacheKey);
                    }
                }
                if (isset($w2CacheObj))
                {
                    static::cacheSave( $pId , $w2CacheObj);
                    $_objList[$pId] = $w2CacheObj;
                    AX_DEBUG('读取缓存成功：'.$w2CacheKey);
                }
                //没找到缓存，去请求。
                else
                {
                    $_idsTmp[] = $pId;
                }

            }
        }

        if (count($_idsTmp)>0)
        {
            if (count($_idsTmp)>1)
            {
                $_dbModel->where(sprintf('%s in (\'%s\')',static::getTabelIdName(),implode('\',\'',$_idsTmp)))
                        ->limit(sprintf('%d,%d'

                                            ,0

                                            ,count($_idsTmp)

                                            ));
            }
            else
            {
                $_dbModel->where(sprintf('%s = \'%s\'',static::getTabelIdName(),implode(',',$_idsTmp)))

                            ->limit(sprintf('%d,%d'
                                    ,0
                                    ,1
                                    ));
            }
            $_data = $_dbModel->field(static::getTableDataKeys(true))->select();
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
        foreach ($pIds as $_key => $pId) {
            if (array_key_exists($pId, $_objList))
            {
                $_dataList[] = $_objList[$pId];
            }
        }

        return $_dataList;
    }

    /**
     * 查询单独字段的值的数组，如不指定$field，则返回首条数据的首个字段的值组成的数组.
     * 如果是多个字段，则返回的是字典组成的数组。
     * @param  string  $pField      [description]
     * @param  array   $pWhere     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $pOrder     排序方式，如'lastmodifytime desc'
     * @param  integer $pPageIndex 分页，第一页为1，第二页为2
     * @param  integer  $pPageSize  分页数据量
     * @param  integer  $pCountThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return array                单独字段的值的数组
     */
    public static function selectFields($pField=null,$pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=DEFAULT_PAGE_SIZE,&$pCountThis=-1)
    {
        $_fieldValues = null;

        if ($pField === null)
        {
            $pField = static::getTabelIdName();
        }
        if ($pField == null)
        {
            $pField = static::getTableDataKeys(true);
        }

        if ( static::isUseCache() )
        {
            $w2CacheKey = sprintf('ax_list_%s_field_%s_where_%s_order_%s_page_%d_size_%d_countthis_%s'
                                        ,static::getTabelName()
                                        ,is_array($pField)?implode(',',$pField):$pField
                                        ,W2Array::sortAndBuildQuery($pWhere)
                                        ,$pOrder
                                        ,$pPageIndex
                                        ,$pPageSize
                                        ,$pCountThis
                                        );

            $w2CacheKey_fieldValues = $w2CacheKey.'_list';
            if ($_SERVER['REQUEST_METHOD'] == 'GET')
            {//只有GET请求才会使用缓存。为安全计，POST请求就耗点性能吧。
                if ( !isset($pWhere['userID']) || $pWhere['userID'] != Utility::getCurrentUserID() )
                {//查询与自己无关的数据，使用缓存。如果用户检索自己相关的数据时，不用缓存
                    if (
                            strpos($w2CacheKey,'rand()')!==false
                            || strpos($w2CacheKey,'now()')!==false
                        )
                    {//查询的语句里可不能出现函数方法啊，那可不能用缓存。

                    }
                    else if (W2Cache::isCacheCanBeUsed($w2CacheKey_fieldValues))
                    {//尝试读取列表检索结果
                         $w2CacheObj_fieldValues = W2Cache::getObj($w2CacheKey_fieldValues);
                    }

                    //如果有结果，再尝试检索总数
                    if (isset($w2CacheObj_fieldValues))
                    {
                        if ($pCountThis != -1)
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
        if (!isset($w2CacheObj_fieldValues) || ( $pCountThis != -1 && !isset($w2CacheObj_countThis) ) )
        {//没有找到缓存，重新请求。
            $_dbModel = static::newDBModel();
            if ($pCountThis != -1)
            {
                $_dbModel->isCountAll(true);
            }
            $_dbModel->where($pWhere)->limit($pPageIndex,$pPageSize)->order($pOrder);
            $_fieldValues = array();
            if ( $pPageSize !== 0 )
            {//只有$pPageSize !== 0 才需要去实际查询数据,如果为0自然连查都不用查了，直接默认空数组就是。
                $_fieldValues = $_dbModel->selectFields($pField);
            }
            if ($pCountThis != -1)
            {
                $pCountThis = $_dbModel->countAll();
            }

            if (isset($w2CacheKey_fieldValues))
            {
                //更新缓存／存储数据
                W2Cache::setObj($w2CacheKey_fieldValues,$_fieldValues);
                AX_DEBUG('更新缓存：'.$w2CacheKey_fieldValues);

                //更新缓存／存储计数
                if (isset($w2CacheKey_countThis))
                {
                    W2Cache::setObj($w2CacheKey_countThis,$pCountThis);
                }

                static::updateCacheKeyPoolOfSql($_dbModel->sqlOfselect(),$w2CacheKey_fieldValues);//追加到缓存池
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
                $pCountThis = $w2CacheObj_countThis;
            }
        }
        return $_fieldValues;

    }


    /**
     * 查询单独数据，如不指定$field，则返回首条数据的首个字段的值
     * @param  string  $pField      [description]
     * @param  array   $pWhere     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $pOrder     排序方式，如'lastmodifytime desc'
     * @param  integer $pPageIndex 分页，第一页为1，第二页为2
     * @param  integer  $pPageSize  分页数据量
     * @param  integer  $pCountThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return string|int
     */
    public static function selectField($pField=null,$pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=DEFAULT_PAGE_SIZE,&$pCountThis=-1)
    {
        $_fieldValues = static::selectFields($pField,$pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);

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
     * @param  array   $pWhere     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array('id'=>'13','replyCount>'=>5,'lastmodifTime>now()'),注意其中的key value的排列方式。
     * @param  string  $pOrder     排序方式，如'lastmodifytime desc'
     * @param  integer $pPageIndex 分页，第一页为1，第二页为2
     * @param  integer  $pPageSize  分页数据量
     * @param  integer  $pCountThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return AbstractModel[]         对象模型数组
     */
    public static function loadModelList($pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=DEFAULT_PAGE_SIZE,&$pCountThis=-1)
    {
        $_fieldValues = static::selectFields(static::getTabelIdName(),$pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);

        if ( static::getTabelIdName() == null )
        {
            $_dataList = array();
            foreach ($_fieldValues as $_d) {
                $_dataList[] = static::createModel($_d);
            }
            return $_dataList;
        }
        else
        {
            return static::loadModelListByIds($_fieldValues);
        }
    }


    /**
     * 存储或更新模型对象
     * @param  AbstractModel $pModel 新建或改动后的模型
     * @return AbstractModel         返回更新后的模型对象
     */
    public static function saveModel($pModel)
    {
        if (!isset($pModel) || get_class($pModel)!= static::getModelName())
        {
            throw new Exception('此处需要传入'.static::getModelName().'类型的对象');
        }

        $_dbModel = static::newDBModel();

        $_updateData = array();
        foreach ($pModel->propertiesModified() as $_key => $_value) {
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

                // if ((is_int($_value) || (is_string($_value) && strlen($_value)<10 ) ))
                // {
                //     $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                //                         ,AXAPI_PROJECT_NAME
                //                         ,static::getTabelName()
                //                         ,$_key
                //                         ,$_value
                //                         );
                //     W2Cache::resetCacheKeyPool($w2CacheKeyPool);
                //     AX_DEBUG('更新缓存池：'.$w2CacheKeyPool);
                // }

                $_valueOriginal = $pModel->properyOriginal($_key);
                if ((is_int($_valueOriginal) || (is_string($_valueOriginal) && strlen($_valueOriginal)<10 ) ))
                {
                    if ($_valueOriginal!==null)
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

        $newWhere = null;
        if ($pModel->isNewModel())
        {/** 新数据 */
            $_dbModel -> insert($_updateData);
            static::updateCacheKeyPoolOfSql($_dbModel->sqlOfInsert($_updateData));//更新缓存池
            if (static::getTabelIdName()!=null)
            {
                $newWhere = $_dbModel->init()
                            ->where($_updateData)->order(sprintf('%s desc',static::getTabelIdName()))
                            ->field(static::getTabelIdName())
                            ->selectSingle();
            }
            else
            {
                $newWhere = $_updateData;
            }
        }
        else
        {
            if (static::getTabelIdName()!=null)
            {
                $newWhere = array(static::getTabelIdName() => $pModel->getId());
                $_dbModel -> where($newWhere)
                          -> limit(1)
                          ->update($_updateData);
                static::resetW2CacheByModelId($pModel->getId());//更新缓存
            }
            else
            {
                $_dbModel -> where(static::filterTableDataKeysInArray($pModel->properiesOriginal(),true))
                          -> limit(1)
                          ->update($_updateData);
                $newWhere = static::filterTableDataKeysInArray($pModel->properiesValue(),true);
            }
            static::updateCacheKeyPoolOfSql($_dbModel->sqlOfUpdate($_updateData));//更新缓存池
        }

        return static::loadModelFirstInList($newWhere);
    }


    /**
     * 直接插入数据
     * @param  array  $pValues  需要插入的数据
     * @return DBModel
     */
    public static function insert($pValues=array())
    {
        $_dbModel = static::newDBModel();
        $_dbModel->insert($pValues);

        static::updateCacheKeyPoolOfSql($_dbModel->sqlOfInsert($pValues));//更新缓存池
    }

    /**
     * 根据筛选结果，直接操作数据库进行数据更新
     * @param  array $pValues 需要更新的数据
     * @param  array  $pWhere  筛选条件
     * @return
     */
    public static function update($pValues,$pWhere=array())
    {
        $_dbModel = static::newDBModel();
        $result = $_dbModel ->update($pValues,$pWhere);
        if (array_key_exists(static::getTabelIdName(),$pWhere))
        {
            static::resetW2CacheByModelId($pWhere[static::getTabelIdName()]);//更新缓存
        }
        static::updateCacheKeyPoolOfSql($_dbModel->sqlOfUpdate($pValues,$pWhere));//更新缓存池
        return $result ;
    }

    /**
     * 根据条件直接删除数据
     * @param  array  $pWhere 筛选条件
     * @return [type]          [description]
     */
    public static function delete($pWhere=array())
    {
        $_dbModel = static::newDBModel();
        $result = $_dbModel->delete($pWhere);
        if (array_key_exists(static::getTabelIdName(),$pWhere))
        {

            static::resetW2CacheByModelId($pWhere[static::getTabelIdName()],null,true);//更新缓存
        }
        static::updateCacheKeyPoolOfSql($_dbModel->sqlOfDelete($pWhere));//更新缓存池
        return $result ;
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

        static::updateCacheKeyPoolOfSql($sql,$w2CacheKey);//追加到缓存池

        return $data;
    }

    /** 执行sql语句     */
    public static function executeSql($sql)
    {
        return DBTool::executeSql($sql);
    }

    /**
     * 分析sql,将缓存key存入对应的池子
     * @param [type] $sql        [description]
     * @param [type] $w2CacheKey [description]
     */
    public static function updateCacheKeyPoolOfSql($sql,$w2CacheKey=null)
    {
        $sqlInfo = DBTool::getKeyInfoOfSql($sql);
        AX_DEBUG($sqlInfo);
        if ( (!is_null($w2CacheKey) && count($sqlInfo['conditions'])==0) || (is_null($w2CacheKey) && ($sqlInfo['action']=='insert' || $sqlInfo['action']=='delete')))
        {//当重置缓存池时,如果是insert或delete操作，则同时重置无筛选的池子 ； 当更新缓存池时，如果没有任何筛选（这种情况不常见，一般是没有status字段的表的接口才会碰到），则添加一个默认方案（无筛选池子））
            foreach ($sqlInfo['tables'] as $_t => $tableName)
            {
                $sqlInfo['conditions'][] = array(
                                    'table' =>$tableName
                                    ,'action'=> ''
                                    ,'key'  =>''
                                    ,'eq'   =>''
                                    ,'value'=>''
                                );
            }
        }
        foreach ($sqlInfo['conditions'] as $info)
        {
            if (is_string($info['value']) && strlen($info['value'])>10 )
            {
                continue;
            }
            $w2CacheKeyPool = sprintf('ax_%s_pool_list_%s_key_%s_value_%s'
                ,AXAPI_PROJECT_NAME
                ,$info['table']
                ,$info['key']
                ,$info['value']
                );
            if (!is_null($w2CacheKey))
            {
                W2Cache::addToCacheKeyPool($w2CacheKeyPool,$w2CacheKey);
                AX_DEBUG('追加到缓存池：'.$w2CacheKeyPool);
            }
            else
            {
                W2Cache::resetCacheKeyPool($w2CacheKeyPool);
                AX_DEBUG('更新缓存池：'.$w2CacheKeyPool);
            }
        }
    }

    /**
     * 更新缓存W2Cache
     * @param  int    $_modelId       主键ID
     * @param  string $_tableName  表名
     * @param  bool   $isDel         是否删除
     * @return [type]           [description]
     */

    public static function resetW2CacheByModelId($_modelId,$_tableName = null,$isDel = false)
    {
        if ($_modelId>0)
        {
            if ($_tableName==null)
            {
                $_tableName = static::getTabelName();
            }
            $w2CacheKey = sprintf('ax_%s_model_%s_id_%d',AXAPI_PROJECT_NAME,$_tableName,$_modelId);
            if ($isDel)
            {
                W2Cache::delCache($w2CacheKey);
            }
            else
            {
                W2Cache::resetCache($w2CacheKey);
            }
            AX_DEBUG('更新缓存：'.$w2CacheKey);

            static::cacheRemove($_modelId);//清理进程内缓存
        }
    }
    /**
     * 来从数据库中删除对象实例
     * @param  object $pModel 对应的model 实例
     * @return boolean          [description]
     */
    public static function removeModel($pModel) {
        if (!isset($pModel) || get_class($pModel)!= static::getModelName() || $pModel->getId()==0)
        {
           throw new Exception('此处需要传入'.static::getModelName().'类型的对象');
        }
        return static::removeModelById($pModel->getId());
    }

    /**
     * 根据主键值删除单条记录
     * @return  integer $pId 对应的modelID主键
     */
    public static function removeModelById($pId=null) {
        return static::removeModelListByIds($pId);
    }

    /**
     * 根据多个主键值删除多条记录
     * @param  array $pIds 数组id主键,或逗号隔开的id字符串
     * @return array        对应的model 实例数组
     */
    public static function removeModelListByIds($pIds=null) {
        if (!isset($pIds) || $pIds==0)
        {
            return false;//参数错误
        }
        $pIds = (is_array($pIds)) ? $pIds : explode(',', $pIds);

        /** @var DBModel */
        $_idsTmp = array();
        foreach ($pIds as $_key => $pId) {
            if ($pId>0)
            {
                $_idsTmp[] = $pId;
            }
        }
        if (count($_idsTmp)>0)
        {
            $pWhere = array();
            if (count($_idsTmp)>1)
            {
                $pWhere[] = sprintf('%s in (%s)',static::getTabelIdName(),implode(',',$_idsTmp));
            }
            else
            {
                $pWhere[static::getTabelIdName()] = implode(',',$_idsTmp);
            }

            return static::delete($pWhere);
        }
        return true;
    }

    /**
     * 根据指定统计类型进行数据统计
     * @param  string $pDateType 统计方法 ： year month day
     * @param  array  $pWhere    筛选条件
     * @return array             统计结果
     */
    public static function _countWithDate($pDateType,$pWhere=array())
    {
        $format = '';
        switch ($pDateType) {
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
        $result = $dbFacotory->useT1(null)->field('DATE_FORMAT(createTime, \''.$format.'\') AS CountDateTime , COUNT(id) AS CountNum')->where($pWhere)->group('CountDateTime')->order('CountDateTime')->select();
        return $result;
    }

    /**
     * 统计符合条件的数量
     * @param  array $pWhere 条件
     * @return int          总数
     */
    public static function count($pWhere=array())
    {
        return static::selectField('count(*)',$pWhere);
    }
    public static function countAll($pWhere=array())
    {
        return static::count($pWhere);
    }

}
