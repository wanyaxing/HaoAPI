<?php
/**
 * mhc的基础工具，通过命令行读取数据库表信息创建对应的Handler,Model,Controller文件
 * @package Tool
 * @author axing (340014824@qq.com)
 * @since 0.1
 * @version 0.1
 */
ini_set('display_errors',1);            //错误信息
ini_set('display_startup_errors',1);    //php启动错误信息
error_reporting(-1);                    //打印出所有的 错误信息
define('IS_SQL_PRINT',True);//打印sql语句

//加载配置文件
require_once(__dir__.'/../config.php');
//常用常量
require_once(AXAPI_ROOT_PATH.'/components/constants.php');

//数据库操作工具
require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

//加载基础方法
require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

if (count($argv)<=1)
{
    print("\n".'需要参数：');
    print("\n".'-t 表名');
    print("\n".'-name 中文标题');
    print("\n".'-rm yes');
    print("\n".'-update yes');
    exit;
}

function getValueInArgv($argv_key)
{
    global $argv;

    $index = array_search($argv_key,$argv);
    if ($index!==false)
    {
        if (count($argv)>$index+1)
        {
            return $argv[$index+1];
        }
        else
        {
            print("\n".'无法获得参数'.$argv_key.'的值'."\n");
            exit;
        }
    }
    return null;
}

$noAllowKeyInMysql = ['add','all','alter','analyze','and','as','asc','asensitive','before','between','bigint','binary','blob','both','by','call','cascade','case','change','char','character','check','collate','column','condition','connection','constraint','continue','convert','create','cross','current_date','current_time','current_timestamp','current_user','cursor','database','databases','day_hour','day_microsecond','day_minute','day_second','dec','decimal','declare','default','delayed','delete','desc','describe','deterministic','distinct','distinctrow','div','double','drop','dual','each','else','elseif','enclosed','escaped','exists','exit','explain','false','fetch','float','float4','float8','for','force','foreign','from','fulltext','goto','grant','group','having','high_priority','hour_microsecond','hour_minute','hour_second','if','ignore','in','index','infile','inner','inout','insensitive','insert','int','int1','int2','int3','int4','int8','integer','interval','into','is','iterate','join','key','keys','kill','label','leading','leave','left','like','limit','linear','lines','load','localtime','localtimestamp','lock','long','longblob','longtext','loop','low_priority','match','mediumblob','mediumint','mediumtext','middleint','minute_microsecond','minute_second','mod','modifies','natural','not','no_write_to_binlog','null','numeric','on','optimize','option','optionally','or','order','out','outer','outfile','precision','primary','procedure','purge','raid0','range','read','reads','real','references','regexp','release','rename','repeat','replace','require','restrict','return','revoke','right','rlike','schema','schemas','second_microsecond','select','sensitive','separator','set','show','smallint','spatial','specific','sql','sqlexception','sqlstate','sqlwarning','sql_big_result','sql_calc_found_rows','sql_small_result','ssl','starting','straight_join','table','terminated','then','tinyblob','tinyint','tinytext','to','trailing','trigger','true','undo','union','unique','unlock','unsigned','update','usage','use','using','utc_date','utc_time','utc_timestamp','values','varbinary','varchar','varcharacter','varying','when','where','while','with','write','x509','xor','year_month','zerofill','action','bit','date','enum','no','text','time','timestamp'];

class CMysql2PHP{
    public static $columnTypes=array(
        'varchar'   =>  'string',
        'char'      =>  'string',
        'text'      =>  'text',
        'int'       =>  'integer',
        'float'     =>  'float',
        'double'    =>  'float',
        // 'decimal'   =>  'decimal',
        'datetime'  =>  'datetime',
        'timestamp' =>  'datetime',
        'time'      =>  'datetime',
        'date'      =>  'date',
        // 'blob'      =>  'binary',
        'tinyint'   =>  'integer',
        // 'decimal'   =>  'money',
    );

    public static function getPhpProp($type)
    {
        if(isset(static::$columnTypes[$type]))
        {
            return static::$columnTypes[$type];
        }
        elseif( ($pos=strpos($type,'('))!==false || ($pos=strpos($type,' '))!==false)
        {
            $t=substr($type,0,$pos);
            return (isset(static::$columnTypes[$t]) ? static::$columnTypes[$t] : $t);//.substr($type,$pos);
        }
        else
        {
            // return $type;
            return 'string';
        }
    }

    /** 取字段支持最大长度 */
    public static function getTypeLength($type)
    {
        $length = null;
        if (strpos($type,'(')!==false)
        {
            $length = intval(preg_replace('/^.*?\((\d+?)\).*/','$1',$type));
        }
        return $length;
    }

    public static function getMethodString($field,$p_allowBlank=true)
    {
        $methodString = 'W2HttpRequest::getRequestString(\'%s\''.($p_allowBlank?'':',false').')';
        switch (static::getPhpProp($field))
        {
            case 'integer':
                $methodString = 'W2HttpRequest::getRequestInt(\'%s\')';
                break;
            case 'float':
                $methodString = 'W2HttpRequest::getRequestFloat(\'%s\')';
                break;
            case 'string':
            case 'text':
                $lenMax = static::getTypeLength($field);
                $methodString = 'W2HttpRequest::getRequestString(\'%s\''.($p_allowBlank?',true':',false').($lenMax>0?',null,0,'.$lenMax:'').')';
                break;
            case 'datetime':
                $methodString = 'W2HttpRequest::getRequestDateTime(\'%s\')';
                break;
            case 'date':
                $methodString = 'W2HttpRequest::getRequestDate(\'%s\')';
                break;
        }
        return $methodString;
    }
}


$_tableName         = getValueInArgv('-t');
$_tableNameCN       = getValueInArgv('-name');
if (is_null($_tableNameCN)){$_tableNameCN = $_tableName;}
$_handlerName       = W2String::camelCaseWithUcFirst($_tableName).'Handler';
$_modelName         = W2String::camelCaseWithUcFirst($_tableName).'Model';
$_controllerName    = W2String::camelCaseWithUcFirst($_tableName).'Controller';
$_apitestConfigName = 'apitest_config.'.W2String::camelCaseWithUcFirst($_tableName);

$_handlerFile       = AXAPI_ROOT_PATH.'/mhc/handlers/'.$_handlerName.'.php';
$_modelFile         = AXAPI_ROOT_PATH.'/mhc/models/'.$_modelName.'.php';
$_controllerFile    = AXAPI_ROOT_PATH.'/mhc/controllers/'.$_controllerName.'.php';
$_apitestConfigFile = AXAPI_ROOT_PATH.'/webroot/apitest/conf/'.$_apitestConfigName.'.js';

$filesExists = array();
if(file_exists($_handlerFile))
{
    print('警告，文件已存在：'.$_handlerFile."\n");
    $filesExists[]=$_handlerFile;
}
if(file_exists($_modelFile))
{
    print('警告，文件已存在：'.$_modelFile."\n");
    $filesExists[]=$_modelFile;
}
if(file_exists($_controllerFile))
{
    print('警告，文件已存在：'.$_controllerFile."\n");
    $filesExists[]=$_controllerFile;
}
if(file_exists($_apitestConfigFile))
{
    print('警告，文件已存在：'.$_apitestConfigFile."\n");
    $filesExists[]=$_apitestConfigFile;
}
if ( getValueInArgv('-rm') == 'yes')
{
    if (count($filesExists)>0)
    {
        foreach($filesExists as $_file)
        {
            print('删除文件：'.$_file."\n");
            unlink($_file);
        }
    }
    else
    {
        print('失败，因为不存在目标文件，所以无法使用删除命令。'."\n");
    }
    exit;
}
else if ( getValueInArgv('-update') == 'yes')
{
    if (count($filesExists)>0)
    {

    }
    else
    {
        print('失败，因为不存在目标文件，所以无法使用更新命令。'."\n");
        exit;
    }
}
else if (count($filesExists)>0)
{
    print('可以使用以下参数删除文件： -rm yes'."\n");
    print('可以使用以下参数更新文件： -update yes'."\n");
    exit;
}


// $_dbModel = new DBModel($_tableName);
// $_tableDataKeys = $_dbModel->getMeta();
// if (!is_array($_tableDataKeys) || count($_tableDataKeys)==0)
// {
//  print('中止，表字段获取失败：'.$_tableName."\n");
//  exit;
// }
//


$_filedList = DBTool::queryData('show full columns from '.$_tableName);
$_tableDataKeys = array();
$_tableKeysImportantForAdd = array();
$_tableIdName = '';
foreach ($_filedList as $_fieldRow) {
    if (in_array(strtolower($_fieldRow["Field"]),$noAllowKeyInMysql))
    {
        print('警告：您不可以在mysql使用以下字符作为字段：'.$_fieldRow["Field"]."\n");
        exit;
    }
    if ($_fieldRow['Key']=='PRI')
    {
        $_tableIdName = $_fieldRow["Field"];
    }
    else
    {
        if (!in_array($_fieldRow["Field"],array('id','userID','status','createTime','modifyTime')))
        {
            $_tableKeysImportantForAdd[] = $_fieldRow["Field"];
        }
    }
    $_tableDataKeys[$_fieldRow["Field"]] = $_fieldRow;
}

if (!is_array($_tableDataKeys) || count($_tableDataKeys)==0)
{
 print('中止，表字段获取失败：'.$_tableName."\n");
 exit;
}
//、、、、、、、、、、、、、、、、、、、、、、、、、、、、、、
$_handlerSrting = '<?php
/**
 * 对'.$_tableName.'数据库表进行查改增删操作的类
 * @package Handler
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class '.$_handlerName.' extends AbstractHandler {
// 继承自抽象Handler类，根据开发环境对应数据库中的表而建立的工厂类
// 不管是否规范都推荐每个handler里直接指定好相关名称，这样就省去智能识别的代码了。
    //====================关键字段，若设定为NULL则支持智能识别（需连接数据库）,所以，还是推荐创建时就手动设定好=====================
    public static $tableName     = \''.$_tableName.'\';   //对应表名
    public static $tableIdName   = \''.$_tableIdName.'\'    ;   //对应的表的主键字段
    public static $tableDataKeys = '.str_replace('"',"'" , json_encode(array_keys($_tableDataKeys))).';//对应表的常用字段数组

    public static $modelName     = \''.$_modelName.'\';//对应的模型的类名
    public static $cache         = array();//类自身用缓存空间
    public static $isUseCache    = True;//类是否开启类缓存
    //====================常规方法，如有需要，也可以覆盖loadById 和 loadListByIds等父类方法=====================
    /**
     * 根据主键值查询单条记录
     * @return '.$_modelName.' 对应的model 实例
     */
    public static function loadModelById($p_id=null)
    {
        return parent::loadModelById($p_id);
    }

    /**
     * 根据筛选条件，筛选获得对象数组的第一个数据
     * @see AbstractHandler::loadModelList()
     * @return '.$_modelName.'         对象模型
     */
    public static function loadModelFirstInList($p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=1,&$p_countThis=-1)
    {
        return parent::loadModelFirstInList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);
    }

    /**
    * 指定ids查询，根据多个主键值查询多条记录,注意，这里返回的数组以传入的id顺序一致
    * @param  array $p_ids 数组id,或逗号隔开的id字符串
    * @return '.$_modelName.'[]        对应的model 实例数组
    */
    public static function loadModelListByIds($p_ids=null)
    {
        return parent::loadModelListByIds($p_ids);
    }

    /**
     * 批量查询，根据筛选条件，筛选获得对象数组
     * @param  array   $p_where     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array(\'id\'=>\'13\',\'replyCount>\'=>5,\'lastmodifTime>now()\'),注意其中的key value的排列方式。
     * @param  string  $p_order     排序方式，如\'lastmodifytime desc\'
     * @param  integer $p_pageIndex 分页，第一页为1，第二页为2
     * @param  integer  $p_pageSize  分页数据量
     * @param  integer  $p_countThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return '.$_modelName.'[]         对象模型数组
     */
    public static function loadModelList($p_where=array(),$p_order=null,$p_pageIndex=1,$p_pageSize=DEFAULT_PAGE_SIZE,&$p_countThis=-1)
    {
        return parent::loadModelList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis);
    }

    /**
     * 存储或更新模型对象
     * @param  object $p_model 新建或改动后的模型
     * @return '.$_modelName.'         返回更新后的模型对象
     */
    public static function saveModel($p_model)
    {
        return parent::saveModel($p_model);
    }
}';
if (!file_exists($_handlerFile))
{
    file_put_contents($_handlerFile,$_handlerSrting);
    print('成功，创建文件：'.$_handlerFile."\n");
}
else if (getValueInArgv('-update') == 'yes')
{
    $_fileString = file_get_contents($_handlerFile);
    $_fileString = preg_replace('/public static \$tableDataKeys =.*/','public static $tableDataKeys = '.str_replace('"',"'" , json_encode(array_keys($_tableDataKeys))).';//对应表的常用字段数组',$_fileString);
    file_put_contents($_handlerFile,$_fileString);
    print('成功，已更新文件：'.$_handlerFile."\n");
}
else
{
    print('失败，目标文件已存在：'.$_handlerFile."\n");
}

//、、、、、、、、、、、、、、、、、、、、、、、、、、、、、、
$_modelFileStrings = array();
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    $_modelFileStrings[$_tableKey] = "\n".(!is_null($_fieldRow['Comment'])?'    /**'.$_fieldRow['Comment'].'**/'."\n":'').'    public $'.$_tableKey.';';
}

$_modelFucStrings = array();
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    $_modelFucStrings[$_tableKey] = "\n".'    public function get'.W2String::camelCaseWithUcFirst($_tableKey).'()
    {
        return $this->'.$_tableKey.';
    }

    public function set'.W2String::camelCaseWithUcFirst($_tableKey).'($'.$_tableKey.')
    {
        $this->'.$_tableKey.' = $'.$_tableKey.';

        return $this;
    }'."\n"."\n";
}


$_modelString = '<?php
/**
 * '.$_tableName.'表 模型，支持get set 等常规数据展示和处理
 * @package Model
 * @author axing
 * @since 0.1
 * @version 0.1
 */
class '.$_modelName.' extends AbstractModel {

    public static $authViewDisabled    = array();//展示数据时，禁止列表。

    /**
     * 初始化方法，如果需要，各模型必须重写此处
     * @param int|array 如果是整数, 赋值给对象的id,如果是数组, 给对象的逐个属性赋值
     * @return '.$_modelName.'
     */
    public static function instance($p_data=null) {
        $_o = parent::instanceModel(__class__, $p_data);
        $tmpVars = get_object_vars($_o);
        $tmpVars[\'snapshot\'] = \'\';
        $_o->snapshot = $tmpVars;//初始化完成后，记录当前状态
        return $_o;
    }

    //＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝新的模型属性在下面定义＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
'.implode(array_values($_modelFileStrings)).'

'.implode(array_values($_modelFucStrings)).'

}';

if (!file_exists($_modelFile))
{
    file_put_contents($_modelFile,$_modelString);
    print('成功，创建文件：'.$_modelFile."\n");
}
else if (getValueInArgv('-update') == 'yes')
{
    $_fileString = file_get_contents($_modelFile);

    foreach ($_modelFileStrings as $_tableKey=>$_string) {
        if (strpos($_fileString,'public $'.$_tableKey.';')===false)
        {
            $_fileString = preg_replace('/([\s\S]*public \$.+)/','$1'.$_string.'//todo debug, php auto update ,pls checkit',$_fileString);
        }
    }

    foreach ($_modelFucStrings as $_tableKey=>$_string) {
        if (strpos($_fileString,'public function get'.W2String::camelCaseWithUcFirst($_tableKey).'()')===false)
        {
            $_fileString = preg_replace('/\}$/',$_string.'//todo debug, php auto update ,pls checkit'."\n}",$_fileString);
        }
    }

    file_put_contents($_modelFile,$_fileString);
    print('成功，已更新文件：'.$_modelFile."\n");
}
else
{
    print('失败，目标文件已存在：'.$_modelFile."\n");
}


//、、、、、、、、、、、、、、、、、、、、、、、、、、、、、、
$_apitestConfigArray = array();

$_apitestConfigSingle = 'apiList.push({
        \'title\':\''.($_tableNameCN.':'.'查看表结构（限管理员）').'\'
        ,\'desc\':\'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/columns\'
        ,\'method\':\'get\'
        ,\'request\':[]
      });
';
$_apitestConfigArray[] = "/*\n".$_apitestConfigSingle."\n*/";


//add
$_apitestConfigSingleAdd = 'apiList.push({
        \'title\':\''.($_tableNameCN.':'.'新建').'\'
        ,\'desc\':\'\'
        ,\'time\':\''.(date('Y-m-d H:i:s')).'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/add\'
        ,\'method\':\'post\'
        ,\'request\':[
           ';
$_apitestConfigRequestAdd          = array();
    // $_apitestConfigRequestAdd[] = '{'.str_pad(' \'key\':\'r\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\''.W2String::under_score($_tableName).'/add\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'必须：接口关键字\' ,\'desc\':\'\' }';
$_apitestConfigRequestAddAdmin     = array();
$_apitestConfigRequestAddNormal    = array();
$_controllerStringAdmin            = '';
$_controllerStringNormal           = '';
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    if ($_fieldRow['Field']!='id')
    {
        $_isAdmin = in_array($_fieldRow['Field'],array('status','userID','level','createTime','modifyTime'));
        $_controllerStringTmp = "\n".'                $tmpModel  ->'.str_pad('set'.W2String::camelCaseWithUcFirst($_fieldRow['Field']),20,' ',STR_PAD_LEFT).'('. sprintf(CMysql2PHP::getMethodString($_fieldRow['Type']),W2String::under_score($_fieldRow['Field'])) .');'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestAddTmp = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':'.(in_array($_fieldRow['Field'],$_tableKeysImportantForAdd)?' true':'false').' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\''.($_isAdmin?'*限管理员可用':'').'\' }';
        if ($_isAdmin )
        {
            $_apitestConfigRequestAddAdmin[]   =  $_apitestConfigRequestAddTmp;
            $_controllerStringAdmin           .=  $_controllerStringTmp;
        }
        else
        {
            $_apitestConfigRequestAddNormal[]  =  $_apitestConfigRequestAddTmp;
            $_controllerStringNormal          .=  $_controllerStringTmp;
        }
    }
}
$_apitestConfigSingleAdd .= implode("\n".'          ,',array_merge($_apitestConfigRequestAdd,array_merge($_apitestConfigRequestAddNormal,$_apitestConfigRequestAddAdmin))) .'
        ]
      });
';
$_apitestConfigArray[] = "/*\n".$_apitestConfigSingleAdd."\n*/";


//update
$_apitestConfigSingleUpdate = 'apiList.push({
        \'title\':\''.($_tableNameCN.':'.'更新').'\'
        ,\'desc\':\'\'
        ,\'time\':\''.(date('Y-m-d H:i:s')).'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/update\'
        ,\'method\':\'post\'
        ,\'request\':[
           ';
$_apitestConfigRequestUpdate = array();
    // $_apitestConfigRequestUpdate[] = '{'.str_pad(' \'key\':\'r\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\''.W2String::under_score($_tableName).'/update\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'必须：接口关键字\' ,\'desc\':\'\' }';
    $_apitestConfigRequestUpdate[] = '{'.str_pad(' \'key\':\'id\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'int\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\'1\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'id\' ,\'desc\':\'\' }';

$_apitestConfigRequestUpdateAdmin = array();
$_apitestConfigRequestUpdateNormal = array();
$_controllerStringUpdateAdmin = '';
$_controllerStringUpdateNormal = '';
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    if ($_fieldRow['Field']!='id')
    {
        $_isAdmin = in_array($_fieldRow['Field'],array('userID','level','createTime','modifyTime'));
        $_controllerStringUpdateTmp = "\n".'                $tmpModel    ->'.str_pad('set'.W2String::camelCaseWithUcFirst($_fieldRow['Field']),20,' ',STR_PAD_LEFT).'('. sprintf(CMysql2PHP::getMethodString($_fieldRow['Type']),W2String::under_score($_fieldRow['Field'])) .');'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestUpdateTmp = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\''.($_isAdmin?'*限管理员可用':'').'\' }';
        if ($_isAdmin )
        {
            $_apitestConfigRequestUpdateAdmin[]   =  $_apitestConfigRequestUpdateTmp;
            $_controllerStringUpdateAdmin           .=  $_controllerStringUpdateTmp;
        }
        else
        {
            $_apitestConfigRequestUpdateNormal[]  =  $_apitestConfigRequestUpdateTmp;
            $_controllerStringUpdateNormal          .=  $_controllerStringUpdateTmp;
        }
    }
}
$_apitestConfigSingleUpdate .= implode("\n".'          ,',array_merge($_apitestConfigRequestUpdate,array_merge($_apitestConfigRequestUpdateNormal,$_apitestConfigRequestUpdateAdmin))) .'
        ]
      });
';

$_apitestConfigArray[] = "/*\n".$_apitestConfigSingleUpdate."\n*/";


//list
$_apitestConfigSingle = 'apiList.push({
        \'title\':\''.($_tableNameCN.':'.'列表').'\'
        ,\'desc\':\'\'
        ,\'time\':\''.(date('Y-m-d H:i:s')).'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/list\'
        ,\'method\':\'get\'
        ,\'request\':[
           ';
$_apitestConfigRequestList = array();
    // $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'r\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\''.W2String::under_score($_tableName).'/list\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'必须：接口关键字\' ,\'desc\':\'\' }';
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'page\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'int\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\'1\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'分页，第一页为1，第二页为2，最后一页为-1\' ,\'desc\':\'\' }';
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'size\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'int\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\'10\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'分页大小\' ,\'desc\':\'\' }';
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'iscountall\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'bool\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'是否统计总数 1是 0否\' ,\'desc\':\'\' }';
$_controllerStringAddOrder = '';
$_apitestConfigRequestOrderValues = array();
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    if (in_array(CMysql2PHP::getPhpProp($_fieldRow['Type']),array('integer','string','datetime','date','float')))
    {
        $_orderValue = W2String::under_score($_fieldRow['Field']);
        $_orderDesc = (!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:'');
        $_controllerStringAddOrder .= "\n". '            case '.str_pad('\''.$_orderValue.'\'',20,' ',STR_PAD_RIGHT).':'.($_orderDesc!=''?' //'.$_orderDesc:'') ;
        $_apitestConfigRequestOrderValues[] = $_orderValue ;//. (!is_null($_orderDesc)?':'.$_orderDesc:'');
    }
}
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'order\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'排序方式\' ,\'desc\':\'限以下值（'.implode(' , ', $_apitestConfigRequestOrderValues ).'）\' }';
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'isreverse\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'int\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'是否倒序 0否 1是\' ,\'desc\':\'（默认1）\' }';
    $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'ids\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'多个id用逗号隔开\' ,\'desc\':\'\' }';
$_controllerStringList = '';
$_controllerKeySearchList = '';
$_controllerKeyFieldList = array();
foreach ($_tableDataKeys as $_tableKey=>$_fieldRow) {
    if (CMysql2PHP::getPhpProp($_fieldRow['Type']) == 'datetime' || CMysql2PHP::getPhpProp($_fieldRow['Type']) == 'date' )
    {
        $_controllerStringList .= "\n".'        '.str_pad('$p_where[\''.$_fieldRow['Field'].' >= \\\'%s\\\'\']',40,' ',STR_PAD_RIGHT).' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow['Type'],false),W2String::under_score($_fieldRow['Field']).'start') .';'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'start'.'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'>=起始时间（之后）：'.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
        $_controllerStringList .= "\n".'        '.str_pad('$p_where[\''.$_fieldRow['Field'].' < \\\'%s\\\'\']',40,' ',STR_PAD_RIGHT).' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow['Type'],false),W2String::under_score($_fieldRow['Field']).'end') .';'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'end'.'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'<结束时间（之前）：'.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
    }
    else if ($_fieldRow['Field']=='status')
    {
        $_controllerStringList .= "\n".'        '.str_pad('$p_where[\''.$_fieldRow['Field'].'\']',40,' ',STR_PAD_RIGHT).' = STATUS_NORMAL;//默认列表页只筛选STATUS_NORMAL状态的数据';
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'*限管理员可用\' }';
    }
    // else if ($_fieldRow['Field']=='userID')
    // {
    //     //默认不支持用户筛选，只能筛选登录用户自己的数据;
    //     $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'0\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'默认登录用户只能筛选自己名下数据 ，管理员可筛选指定用户\' }';
    // }
    else
    {
        $_controllerStringList .= "\n".'        '.str_pad('$p_where[\''.$_fieldRow['Field'].'\']',40,' ',STR_PAD_RIGHT).' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow['Type'],false),W2String::under_score($_fieldRow['Field'])) .';'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow['Type']).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\''.(($_fieldRow['Field']=='userID')?'0':'').'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
    }

    if (CMysql2PHP::getPhpProp($_fieldRow['Type']) == 'string')
    {
        $_controllerKeySearchList .= "\n".'            '.str_pad('$keyWhere[] = sprintf(\''.$_fieldRow['Field'].' like \\\'%%%s%%\\\'\',',40,' ',STR_PAD_RIGHT).'$keyWord);'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_controllerKeyFieldList[] = $_fieldRow['Field'];
    }
}
$_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'keyword\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'检索关键字\' ,\'desc\':\''.'\' }';//.(implode(' ',$_controllerKeyFieldList))

$_apitestConfigSingle .= implode("\n".'          ,',$_apitestConfigRequestList) .'
        ]
      });
';
$_apitestConfigArray[] = "/*\n".$_apitestConfigSingle."\n*/";


//详情

$_apitestConfigSingle = 'apiList.push({
        \'title\':\''.($_tableNameCN.':'.'详情').'\'
        ,\'desc\':\'\'
        ,\'time\':\''.(date('Y-m-d H:i:s')).'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/detail\'
        ,\'method\':\'get\'
        ,\'request\':[
           ';
$_apitestConfigRequestDetail = array();
    // $_apitestConfigRequestDetail[] = '{'.str_pad(' \'key\':\'r\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\''.W2String::under_score($_tableName).'/detail\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'必须：接口关键字\' ,\'desc\':\'\' }';
    $_apitestConfigRequestDetail[] = '{'.str_pad(' \'key\':\'id\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'int\'',20,' ',STR_PAD_RIGHT).' ,\'required\': true ,\'time\':\'\' '.str_pad(',\'test-value\':\'1\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'id\' ,\'desc\':\'\' }';
$_apitestConfigSingle .= implode("\n".'          ,',$_apitestConfigRequestDetail) .'
        ]
      });
';
$_apitestConfigArray[] = "/*\n".$_apitestConfigSingle."\n*/";


$_controllerString = '<?php
/**
 * '.$_tableName.'表相关接口
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class '.$_controllerName.' extends AbstractController{
    public static $handlerlName     = \''.$_handlerName.'\';//对应的工厂的类名
    public static $modelName        = \''.$_modelName.'\';//对应的模型的类名

    protected static $authType = \'default\';//默认权限类型
    public static $authViewDisabledList     = array(
                                                \'empty\'    =>array()
                                                ,\'visitor\' =>array()
                                                ,\'disabled\'=>array()
                                                ,\'pending\' =>array()
                                                ,\'draft\'   =>array()
                                                ,\'normal\'  =>array()
                                                ,\'self\'    =>array()
                                                ,\'admin\'   =>array()
                                                );//查看相关字段权限

    // ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝接口方法都在下面定义 action开头的方法是对外接口＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
    //权限
    // public static function getAuthIfUserCanDoIt($p_userID,$p_action,$p_targetModel=null)
    // {
    //     $auth = parent::getAuthIfUserCanDoIt($p_userID,$p_action,$p_targetModel);
    //     $_user = Utility::getUserByID($p_userID);
    //     if (is_object($_user))
    //     {
    //         switch ($p_action)
    //         {
    //             case \'add\'    :    break;
    //             case \'update\' :    break;
    //             case \'detail\' :    break;
    //             case \'list\'   :    break;
    //         }
    //     }
    //     return $auth;
    // }


    /**
     * 新建数据
     * @return HaoResult
     */
    public static function actionAdd()
    {
        $unsetKey = W2HttpRequest::getUnsetRequest(\''.strtolower(implode(',',$_tableKeysImportantForAdd)).'\', $p_allowBlank = false);
        if ( $unsetKey  !== null)
        {
            return HaoResult::init(ERROR_CODE::PARAM_ERROR,array(\'errorContent\'=>\'部分参数未提交数据: \'.$unsetKey));
        }
        $tmpModel =  new '.$_modelName.'();

        switch ( $auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'add\',$tmpModel))
        {
            case \'admin\'://有管理权限
'.$_controllerStringAdmin.'
            case \'self\'  ://作者
            case \'normal\'://正常用户
'
.(array_key_exists('userID',$_tableDataKeys)?'                if ($auth == \'normal\' || $tmpModel->properyValue(\'userID\')===null)
                {
                    $tmpModel  ->       setUserID(Utility::getCurrentUserID());//普通用户创建的数据，默认作者为自己。
                }':'')
.'
'.$_controllerStringNormal.'
                break;
            case \'draft\'://未激活
            case \'pending\'://待审禁言
            case \'disabled\'://封号
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;

            case \'visitor\'://游客
                return HaoResult::init(ERROR_CODE::$ONLY_USER_ALLOW);
                break;

            default:
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }
        if (method_exists($tmpModel,\'setStatus\') && ( $tmpModel->getStatus()===null || !array_key_exists(\'status\',$tmpModel->propertiesModified())))
        {
            $tmpModel ->setStatus( STATUS_NORMAL );
        }
        return static::save($tmpModel,$isAdd=true);
    }


    /**
     * 修改数据
     * @return HaoResult
     */
    public static function actionUpdate()
    {
        $p_id = W2HttpRequest::getRequestInt(\'id\',null,false,false);

        $tmpModel = '.$_handlerName.'::loadModelById($p_id);

        switch ( $auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'update\',$tmpModel))
        {
            case \'admin\'://有管理权限
'.$_controllerStringUpdateAdmin.'
            case \'self\'://作者
'.$_controllerStringUpdateNormal.'
                break;
            case \'normal\'://正常用户
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
            case \'draft\'://未激活
            case \'pending\'://待审禁言
            case \'disabled\'://封号
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;

            case \'visitor\'://游客
                return HaoResult::init(ERROR_CODE::$ONLY_USER_ALLOW);
                break;

            case \'empty\':
                return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
                break;

            default:
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }

        return static::save($tmpModel);
    }

    /**
     * 保存或更新数据
     * @param  '.$_modelName.'  $tmpModel 更改后的模型对象
     * @param  boolean       $isAdd    新增还是更新
     * @return HaoResult
     */
    public static function save($tmpModel,$isAdd=false)
    {
        return parent::save($tmpModel,$isAdd);
        // $tmpResult =  parent::save($tmpModel,$isAdd);
        // if ($tmpResult->isResultsOK())
        // {
        //     /** @var '.$_modelName.' */
        //     $savedModel = $tmpResult->getResults();
        //     $tmpResult->addExtraInfo(\'key\',\'value\');
        // }
        // return $tmpResult;
    }

    /**
     * 列表查询
     * @return HaoResult
     */
    public static function actionList()
    {
        $p_where = array();
        '.str_pad('$p_where[\''.$_tableIdName.' in (%s)\'] ',40,' ',STR_PAD_RIGHT).' = W2HttpRequest::getRequestArrayString(\'ids\',false,true);
'.$_controllerStringList.'
'.($_controllerKeySearchList!=''?'        $keyWord                                 = W2HttpRequest::getRequestString(\'keyword\',false);
        if ($keyWord!=null)
        {
            $keyWhere = array();
            $keyWord = preg_replace(\'/\s+/\',\'%\',$keyWord);
'.$_controllerKeySearchList.'
            $p_where[] = \'(\'.implode(\' or \',$keyWhere).\')\';
        }':'').'

                //两表一对一用关联查询
                //$p_where[\'joinList\'] = array();
                //$p_where[\'joinList\'][] = array(\'tbl_example t2\',array(\'t2.teacherID = t1.userID\',\'t2.id\'=>$keyWord));
                //两表一对多用exists查询
                //$p_where[] = \'exists (select t2.id from tbl_example t2 where t2.teacherID = t1.userID limit 1)\';

        //根据权限不同，支持的筛选功能也可以不同
        switch ( $auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'list\'))
        {
            case \'admin\'   : //有管理权限
                '
                .(array_key_exists('status',$_tableDataKeys)?'$p_where[\'status\']                       = W2HttpRequest::getRequestInt(\'status\',null,true,false,STATUS_NORMAL);//管理员可以筛选数据状态':'')
                .'
                '
                // .(array_key_exists('userID',$_tableDataKeys)?'$p_where[\'userID\']                       = W2HttpRequest::getRequestInt(\'userid\');//管理员可以筛选用户ID':'')
                .'
            case \'self\'    : //作者
            case \'normal\'  : //正常用户
                '
// .(array_key_exists('userID',$_tableDataKeys)?'if ($auth == \'normal\')
//                 {
//                     $p_where[\'userID\']           = Utility::getCurrentUserID();//普通用户，默认只能筛选自己名下数据。
//                 }':'')
.'
            case \'draft\'   : //未激活
            case \'pending\' : //待审禁言
            case \'disabled\': //封号
            case \'visitor\' : //游客
                break;

            default :
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }

        $_order = W2HttpRequest::getRequestString(\'order\',false,\'\');
        switch ( strtolower($_order) )
        {
'.$_controllerStringAddOrder.'
                $p_order = $_order;
                break;
            case \'\':
            case \'default\':
                $p_order = \''.$_tableIdName.'\';
                break;
            default:
                return HaoResult::init(ERROR_CODE::$ORDER_VALUE_ERROR);
                break;
        }

        $p_countThis=-1;
        return static::aList($p_where,$p_order,$p_pageIndex=null,$p_pageSize=null,$p_countThis,$isDetail = false);
    }

    //详情
    public static function actionDetail()
    {
        return static::detail();
    }

    /**
     * load数据并进行读取权限判断
     */
    protected static function loadList($p_where=null,$p_order=null,$p_pageIndex=null,$p_pageSize=null,&$p_countThis=null,$isDetail = false)
    {

        $tmpResult = parent::loadList($p_where,$p_order,$p_pageIndex,$p_pageSize,$p_countThis,$isDetail);
        if (is_array($tmpResult) && array_key_exists(\'errorCode\',$tmpResult))
        {
            return $tmpResult;
        }
        switch ($auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),$isDetail?\'detail\':\'list\',$tmpResult))
        {
            case \'admin\'   : //有管理权限
            case \'self\'    : //作者
                break;//可见
            case \'normal\'  : //正常用户
            case \'draft\'   : //未激活
            case \'pending\' : //待审禁言
            case \'disabled\': //封号
            case \'visitor\' : //游客
                return HaoResult::init(ERROR_CODE::$NO_AUTH);//其他用户不可见
                break;
            case \'empty\' : //空
                return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
                break;
            default :
                return HaoResult::init(ERROR_CODE::$NO_AUTH);
                break;
        }

        '.$_modelName.'::$authViewDisabled = static::$authViewDisabledList[$auth];

        return $tmpResult;
    }

}
';
if (!file_exists($_controllerFile))
{
    file_put_contents($_controllerFile,$_controllerString);
    print('成功，创建文件：'.$_controllerFile."\n");
}
else if (getValueInArgv('-update') == 'yes')
{
    $_fileString = file_get_contents($_controllerFile);

    print("actionAdd - admin\n");
    $_fileAdminString = preg_replace('/[\s\S]*?actionAdd\(\)[\s\S]*?case.*?\'admin\'.*([\s\S]+?)case \'self\'[\s\S]*/','$1',$_fileString);
    $_fileAdminStringTmp = $_fileAdminString;
    foreach (explode("\n",$_controllerStringAdmin) as $_string) {
        if ($_string!='' && strpos($_fileAdminStringTmp,preg_replace('/[\s\S]*?(set.*?)\([\s\S]*/','$1',$_string))===false)
        {
            $_fileAdminStringTmp = preg_replace('/([\s\S]*\$tmpModel    ->set.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileAdminStringTmp);
        }
    }
    $_fileString = str_replace($_fileAdminString,$_fileAdminStringTmp,$_fileString);

    print("actionAdd - normal\n");
    $_fileNormalString = preg_replace('/[\s\S]*?actionAdd\(\)[\s\S]*?case.*?\'self\'.*([\s\S]+?)default:[\s\S]*/','$1',$_fileString);
    $_fileNormalStringTmp = $_fileNormalString;
    foreach (explode("\n",$_controllerStringNormal) as $_string) {
        if ($_string!='' && strpos($_fileNormalStringTmp,preg_replace('/[\s\S]*?(set.*?)\([\s\S]*/','$1',$_string))===false)
        {
            $_fileNormalStringTmp = preg_replace('/([\s\S]*\$tmpModel.*?set.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileNormalStringTmp);
        }
    }
    $_fileString = str_replace($_fileNormalString,$_fileNormalStringTmp,$_fileString);

    print("actionUpdate - admin\n");
    $_fileUpdateAdminString = preg_replace('/[\s\S]*?actionUpdate\(\)[\s\S]*?case.*?\'admin\'.*([\s\S]+?)case \'self\'[\s\S]*/','$1',$_fileString);
    $_fileUpdateAdminStringTmp = $_fileUpdateAdminString;
    foreach (explode("\n",$_controllerStringUpdateAdmin) as $_string) {
        if ($_string!='' && strpos($_fileUpdateAdminStringTmp,preg_replace('/[\s\S]*?(set.*?)\([\s\S]*/','$1',$_string))===false)
        {
            $_fileUpdateAdminStringTmp = preg_replace('/([\s\S]*\$tmpModel    ->set.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileUpdateAdminStringTmp);
        }
    }
    $_fileString = str_replace($_fileUpdateAdminString,$_fileUpdateAdminStringTmp,$_fileString);

    print("actionUpdate - normal\n");
    $_fileUpdateNormalString = preg_replace('/[\s\S]*?actionUpdate\(\)[\s\S]*?case.*?\'self\'.*([\s\S]+?)default:[\s\S]*/','$1',$_fileString);
    $_fileUpdateNormalStringTmp = $_fileUpdateNormalString;
    foreach (explode("\n",$_controllerStringUpdateNormal) as $_string) {
        if ($_string!='' && strpos($_fileUpdateNormalStringTmp,preg_replace('/[\s\S]*?(set.*?)\([\s\S]*/','$1',$_string))===false)
        {
            $_fileUpdateNormalStringTmp = preg_replace('/([\s\S]*\$tmpModel    ->set.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileUpdateNormalStringTmp);
        }
    }
    $_fileString = str_replace($_fileUpdateNormalString,$_fileUpdateNormalStringTmp,$_fileString);


    print("actionList - p_where\n");
    $_fileListAdminString = preg_replace('/[\s\S]*?actionList\(\)[\s\S]*?\$p_where = array\(\).*([\s\S]+?)switch \([\s\S]*/','$1',$_fileString);
    $_fileListAdminStringTmp = $_fileListAdminString;
    foreach (explode("\n",$_controllerStringList) as $_string) {
        if ($_string!='' && strpos($_fileListAdminStringTmp,preg_replace('/[\s\S]*?(p_where.*?\])[\s\S]*/','$1',$_string))===false)
        {
            $_fileListAdminStringTmp = preg_replace('/([\s\S]*\$p_where\[.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileListAdminStringTmp);
        }
    }
    $_fileString = str_replace($_fileListAdminString,$_fileListAdminStringTmp,$_fileString);


    print("actionList - order\n");
    $_fileOrderString = preg_replace('/[\s\S]*?actionList\(\)[\s\S]*?switch.*?\$_order.*([\s\S]+?)break[\s\S]*/','$1',$_fileString);
    $_fileOrderStringTmp = $_fileOrderString;
    foreach (explode("\n",$_controllerStringAddOrder) as $_string) {
        if ($_string!='' && strpos($_fileOrderStringTmp,preg_replace('/[\s\S]*?case.*?(\'.*?\')[\s\S]*/','$1',$_string))===false)
        {
            $_fileOrderStringTmp = preg_replace('/([\s\S]*case.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileOrderStringTmp);
        }
    }
    $_fileString = str_replace($_fileOrderString,$_fileOrderStringTmp,$_fileString);

    file_put_contents($_controllerFile,$_fileString);
    print('成功，已更新文件：'.$_controllerFile."\n");
}
else
{
    print('失败，目标文件已存在：'.$_controllerFile."\n");
}


if (!file_exists($_apitestConfigFile))
{
    file_put_contents($_apitestConfigFile,implode("\n",$_apitestConfigArray));
    print('成功，创建文件：'.$_apitestConfigFile."\n");
}
else if (getValueInArgv('-update') == 'yes')
{
    $_fileString = file_get_contents($_apitestConfigFile);

    print("apitest - add \n");
    $_fileConfigAddString = preg_replace('/[\s\S]*?(\'key\':\'r\'.*?,\'test-value\':\'.*?\/add\'.*[\s\S]+?)      \};[\s\S]*/','$1',$_fileString);
    $_fileConfigAddStringTmp = $_fileConfigAddString;
    foreach (array_merge($_apitestConfigRequestAddNormal,$_apitestConfigRequestAddAdmin) as $_string) {
        if ($_string!='' && strpos($_fileConfigAddStringTmp,preg_replace('/[\s\S]*?(\'key\':\'.*?\')[\s\S]*/','$1',$_string))===false)
        {
            $_fileConfigAddStringTmp = preg_replace('/([\s\S]*\'key\':\'.*)/','$1'."\n".'          ,'.$_string.'//todo debug, php auto update ,pls checkit',$_fileConfigAddStringTmp);
        }
    }
    $_fileString = str_replace($_fileConfigAddString,$_fileConfigAddStringTmp,$_fileString);

    print("apitest - update \n");
    $_fileConfigUpdateString = preg_replace('/[\s\S]*?(\'key\':\'r\'.*?,\'test-value\':\'.*?\/update\'.*[\s\S]+?)      \};[\s\S]*/','$1',$_fileString);
    $_fileConfigUpdateStringTmp = $_fileConfigUpdateString;
    foreach (array_merge($_apitestConfigRequestUpdateNormal,$_apitestConfigRequestUpdateAdmin) as $_string) {
        if ($_string!='' && strpos($_fileConfigUpdateStringTmp,preg_replace('/[\s\S]*?(\'key\':\'.*?\')[\s\S]*/','$1',$_string))===false)
        {
            $_fileConfigUpdateStringTmp = preg_replace('/([\s\S]*\'key\':\'.*)/','$1'."\n".'          ,'.$_string.'//todo debug, php auto update ,pls checkit',$_fileConfigUpdateStringTmp);
        }
    }
    $_fileString = str_replace($_fileConfigUpdateString,$_fileConfigUpdateStringTmp,$_fileString);


    print("apitest - list \n");
    $_fileConfigListString = preg_replace('/[\s\S]*?(\'key\':\'r\'.*?,\'test-value\':\'.*?\/list\'.*[\s\S]+?)      \};[\s\S]*/','$1',$_fileString);
    $_fileConfigListStringTmp = $_fileConfigListString;
    foreach ($_apitestConfigRequestList as $_string) {
        if ($_string!='' && strpos($_fileConfigListStringTmp,preg_replace('/[\s\S]*?(\'key\':\'.*?\')[\s\S]*/','$1',$_string))===false)
        {
            $_fileConfigListStringTmp = preg_replace('/([\s\S]*\'key\':\'.*)/','$1'."\n".'          ,'.$_string.'//todo debug, php auto update ,pls checkit',$_fileConfigListStringTmp);
        }
    }
    $_fileString = str_replace($_fileConfigListString,$_fileConfigListStringTmp,$_fileString);
    // var_export($_fileConfigListString);
    // var_export($_fileConfigListStringTmp);
    file_put_contents($_apitestConfigFile,$_fileString);
    print('成功，已更新文件：'.$_apitestConfigFile."\n");
}
else
{
    print('失败，目标文件已存在：'.$_apitestConfigFile."\n");
}





print("done\n");
