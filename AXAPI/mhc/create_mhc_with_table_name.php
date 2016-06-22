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


//加载配置文件
require_once(__dir__.'/../config.php');

//数据库操作工具
require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

//加载基础方法
require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

if ((!isset($argv) || count($argv)<=1) && count(array_keys($_REQUEST))==0)
{
    print("\n".'需要参数：');
    print("\n".'-t 表名');
    print("\n".'-name 中文标题');
    print("\n".'-pri 主键字段名（可选，默认取PRI且auto_increment的字段。若取不到，则可以在此处填一个字段，否则就是空了哦)');
    print("\n".'-rm yes');
    print("\n".'-update yes');
    exit;
}


function getValueInArgv($argv_key)
{
    if (isset($_REQUEST[$argv_key]))
    {
        return $_REQUEST[$argv_key];
    }
    global $argv;

    if (count(array_keys($_REQUEST))==0)
    {
        if (!is_array($argv))
        {
            print("\n".'无法获得参数'.$argv_key.'的值'."\n");
            exit;
        }
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
    }
    return null;
}

$noAllowKeyInMysql = array('add','all','alter','analyze','and','as','asc','asensitive','before','between','bigint','binary','blob','both','by','call','cascade','case','change','char','character','check','collate','column','condition','connection','constraint','continue','convert','create','cross','current_date','current_time','current_timestamp','current_user','cursor','database','databases','day_hour','day_microsecond','day_minute','day_second','dec','decimal','declare','default','delayed','delete','desc','describe','deterministic','distinct','distinctrow','div','double','drop','dual','each','else','elseif','enclosed','escaped','exists','exit','explain','false','fetch','float','float4','float8','for','force','foreign','from','fulltext','goto','grant','group','having','high_priority','hour_microsecond','hour_minute','hour_second','if','ignore','in','index','infile','inner','inout','insensitive','insert','int','int1','int2','int3','int4','int8','integer','interval','into','is','iterate','join','key','keys','kill','label','leading','leave','left','like','limit','linear','lines','load','localtime','localtimestamp','lock','long','longblob','longtext','loop','low_priority','match','mediumblob','mediumint','mediumtext','middleint','minute_microsecond','minute_second','mod','modifies','natural','not','no_write_to_binlog','null','numeric','on','optimize','option','optionally','or','order','out','outer','outfile','precision','primary','procedure','purge','raid0','range','read','reads','real','references','regexp','release','rename','repeat','replace','require','restrict','return','revoke','right','rlike','schema','schemas','second_microsecond','select','sensitive','separator','set','show','smallint','spatial','specific','sql','sqlexception','sqlstate','sqlwarning','sql_big_result','sql_calc_found_rows','sql_small_result','ssl','starting','straight_join','table','terminated','then','tinyblob','tinyint','tinytext','to','trailing','trigger','true','undo','union','unique','unlock','unsigned','update','usage','use','using','utc_date','utc_time','utc_timestamp','values','varbinary','varchar','varcharacter','varying','when','where','while','with','write','x509','xor','year_month','zerofill','action','bit','date','enum','no','text','time','timestamp');

$noAllowKeyInMysql[] = 'asString';
$noAllowKeyInMysql[] = 'asInt';
$noAllowKeyInMysql[] = 'asArray';

class CMysql2PHP{
    public static $columnTypes=array(
        'varchar'   =>  'string',
        'char'      =>  'string',
        'text'      =>  'text',
        'int'       =>  'integer',
        'float'     =>  'float',
        'double'    =>  'float',
        'decimal'   =>  'float',
        'datetime'  =>  'datetime',
        'timestamp' =>  'datetime',
        'time'      =>  'datetime',
        'date'      =>  'date',
        // 'blob'      =>  'binary',
        'tinyint'   =>  'integer',
        // 'decimal'   =>  'money',
    );

    public static function getPhpProp($_fieldRow)
    {
        $type = $_fieldRow['Type'];
        if ($_fieldRow['Field']=='password')
        {
            return 'md5';
        }
        else if(isset(static::$columnTypes[$type]))
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

    public static function getMethodString($_fieldRow,$pAllowBlank=true)
    {
        $methodString = 'W2HttpRequest::getRequestString(\'%s\''.($pAllowBlank?'':',false').')';
        switch (static::getPhpProp($_fieldRow))
        {
            case 'integer':
                $methodString = 'W2HttpRequest::getRequestInt(\'%s\')';
                break;
            case 'float':
                $methodString = 'W2HttpRequest::getRequestFloat(\'%s\')';
                break;
            case 'string':
            case 'text':
                if ($_fieldRow['Field']=='telephone')
                {
                    $methodString = 'W2HttpRequest::getRequestTelephone(\'%s\')';
                }
                else if ($_fieldRow['Field'] == 'email')
                {
                    $methodString = 'W2HttpRequest::getRequestEmail(\'%s\')';
                }
                else
                {
                    $lenMax = static::getTypeLength($_fieldRow['Type']);
                    $methodString = 'W2HttpRequest::getRequestString(\'%s\''.($pAllowBlank?',true':',false').',null,0,'.($lenMax>0?$lenMax:'10000').')';
                }
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
    $filesExists[]=$_handlerFile;
}
if(file_exists($_modelFile))
{
    $filesExists[]=$_modelFile;
}
if(file_exists($_controllerFile))
{
    $filesExists[]=$_controllerFile;
}
if(file_exists($_apitestConfigFile))
{
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
        print('失败，因为不存在目标文件，所以无法使用删除命令。:'."\n");
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
    print('失败，目标文件已存在：'."\n".implode("\n",$filesExists));
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
$_tableIdName = getValueInArgv('-pri');;
foreach ($_filedList as $_fieldRow) {
    if (in_array(strtolower($_fieldRow['Field']),$noAllowKeyInMysql))
    {
        print('警告：您不可以在mysql使用以下字符作为字段：'.$_fieldRow['Field']."\n");
        exit;
    }
    if ($_tableIdName === null && $_fieldRow['Key']=='PRI' && $_fieldRow['Extra']=='auto_increment')
    {
        $_tableIdName = $_fieldRow['Field'];
    }
    else
    {
        if (!in_array($_fieldRow['Field'],array('id','userID','status','createTime','modifyTime')))
        {
            $_tableKeysImportantForAdd[] = W2String::under_score($_fieldRow['Field']);
        }
    }
    $_tableDataKeys[$_fieldRow['Field']] = $_fieldRow;
}

if (!is_array($_tableDataKeys) || count($_tableDataKeys)==0)
{
 print('中止，表字段获取失败：'.$_tableName."\n");
 exit;
}

if ($_tableIdName == null)
{
    print('注意：未发现主键字段。 如果您要自定义主键字段，请使用 -pri 参数。'."\n");
    print('注意：因为没有主键字段，所以默认生成的接口只有「新建」和「列表」两个接口。'."\n");
    print('注意：不支持「更新」和「详情」接口，如有类似需求，请自行实现接口。'."\n");
    print("\n");
}

if (is_null($_tableNameCN))
{
    $createTableSyntaxes = DBTool::queryData('SHOW CREATE TABLE '.$_tableName);
    if (count($createTableSyntaxes)>0)
    {
        $stringCTS = $createTableSyntaxes[0]['Create Table'];
        if (preg_match('/[\s\S].*?ENGINE=.*COMMENT=\'(.*)\'[\s\S]*/',$stringCTS))
        {
            $_tableNameCN = preg_replace('/[\s\S]*?ENGINE=.*COMMENT=\'(.*?)\'[\s\S]*/','$1',$stringCTS);
        }
    }
}

if (is_null($_tableNameCN)){$_tableNameCN = $_tableName;}



if ($_tableName=='user' && array_key_exists('password',$_tableDataKeys) && array_key_exists('telephone',$_tableDataKeys) && array_key_exists('level',$_tableDataKeys))
{
    define('IS_SPECIAL_TABLE','user');
}
else if ($_tableName=='smsVerify' && array_key_exists('verifyCode',$_tableDataKeys))
{
    define('IS_SPECIAL_TABLE','smsVerify');
}
else if ($_tableName=='unionLogin' && array_key_exists('unionToken',$_tableDataKeys))
{
    define('IS_SPECIAL_TABLE','unionLogin');
}
else
{
    define('IS_SPECIAL_TABLE','none');
}
// var_export(IS_SPECIAL_TABLE);
// exit;
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
    public static $tableDataKeys = array('.trim(str_replace('"',"'" , json_encode(array_keys($_tableDataKeys))),'[]').');//对应表的常用字段数组

    public static $modelName     = \''.$_modelName.'\';//对应的模型的类名
    public static $cache         = array();//类自身用缓存空间
    public static $isUseCache    = True;//类是否开启类缓存
    //====================常规方法，如有需要，也可以覆盖loadById 和 loadListByIds等父类方法=====================
    /**
     * 根据主键值查询单条记录
     * @return '.$_modelName.' 对应的model 实例
     */
    public static function loadModelById($pId=null)
    {
        return parent::loadModelById($pId);
    }

    /**
     * 根据筛选条件，筛选获得对象数组的第一个数据
     * @see AbstractHandler::loadModelList()
     * @return '.$_modelName.'         对象模型
     */
    public static function loadModelFirstInList($pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=1,&$pCountThis=-1)
    {
        return parent::loadModelFirstInList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);
    }

    /**
    * 指定ids查询，根据多个主键值查询多条记录,注意，这里返回的数组以传入的id顺序一致
    * @param  array $pIds 数组id,或逗号隔开的id字符串
    * @return '.$_modelName.'[]        对应的model 实例数组
    */
    public static function loadModelListByIds($pIds=null)
    {
        return parent::loadModelListByIds($pIds);
    }

    /**
     * 批量查询，根据筛选条件，筛选获得对象数组
     * @param  array   $pWhere     这是一个数组字典，用来约束筛选条件，支持多种表达方式，如array(\'id\'=>\'13\',\'replyCount>\'=>5,\'lastmodifTime>now()\'),注意其中的key value的排列方式。
     * @param  string  $pOrder     排序方式，如\'lastmodifytime desc\'
     * @param  integer $pPageIndex 分页，第一页为1，第二页为2
     * @param  integer  $pPageSize  分页数据量
     * @param  integer  $pCountThis  计数变量，注意，若需要进行计数统计，则调用此处时需传入一个变量，当方法调用结束后，会将计数赋值给该变量。
     * @return '.$_modelName.'[]         对象模型数组
     */
    public static function loadModelList($pWhere=array(),$pOrder=null,$pPageIndex=1,$pPageSize=DEFAULT_PAGE_SIZE,&$pCountThis=-1)
    {
        return parent::loadModelList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis);
    }

    /**
     * 存储或更新模型对象
     * @param  object $pModel 新建或改动后的模型
     * @return '.$_modelName.'         返回更新后的模型对象
     */
    public static function saveModel($pModel)
    {
        return parent::saveModel($pModel);
    }
';
if (IS_SPECIAL_TABLE == 'unionLogin')
{
    $_handlerSrting .=  '    /**
     * 根据$unionToken,$unionType获得对应设置实例
     * @return UnionLoginModel           设置实例
     */
    public static function loadModelByToken($unionToken,$unionType)
    {
        $tmpModel = static::loadModelFirstInList(array(\'unionToken\'=>$unionToken,\'unionType\'=>$unionType));
        if (!is_object($tmpModel))
        {
            $tmpModel = static::createModel();
            $tmpModel->setCreateTime(date(\'Y-m-d H:i:s\'));
            $tmpModel->setUnionToken($unionToken);
            $tmpModel->setUnionType($unionType);
        }
        return $tmpModel;
    }

    /**
     * 根据$userID,$unionType获得对应设置实例
     * @return UnionLoginModel           设置实例
     */
    public static function loadModelByUserID($userID,$unionType)
    {
        return static::loadModelFirstInList(array(\'userID\'=>$userID,\'unionType\'=>$unionType));
    }';
}
$_handlerSrting .=  '
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
    $strSetGet = "\n".(!is_null($_fieldRow['Comment'])?'    /**'.$_fieldRow['Comment'].'**/'."\n":'').'    public function get'.W2String::camelCaseWithUcFirst($_tableKey).'()
    {
        return $this->'.$_tableKey.';
    }
';

    if (IS_SPECIAL_TABLE=='user' && $_tableKey=='password')
    {
        $strSetGet .= "\n".(!is_null($_fieldRow['Comment'])?'    /**'.$_fieldRow['Comment'].'**/'."\n":'').'    public function setPassword($password,$isNeedEncodePwd = true)//axing edit 密码需要加密后存储
    {

        $this->password = ($isNeedEncodePwd && $password!=null)?Utility::getEncodedPwd($password):$password;

        return $this;
    }';
    }
    else
    {
        $strSetGet .= "\n".(!is_null($_fieldRow['Comment'])?'    /**'.$_fieldRow['Comment'].'**/'."\n":'').'    public function set'.W2String::camelCaseWithUcFirst($_tableKey).'($'.$_tableKey.')
    {
        $this->'.$_tableKey.' = $'.$_tableKey.';

        return $this;
    }';
    }
    if (IS_SPECIAL_TABLE=='user')
    {
        if ($_tableKey=='telephone')
        {
            $strSetGet .= "\n".(!is_null($_fieldRow['Comment'])?'    /**'.$_fieldRow['Comment'].'**/'."\n":'').'    public function get'.W2String::camelCaseWithUcFirst($_tableKey).'Local()
    {
        return preg_replace(\'/(.*?)(\d\d\d\d)(\d\d\d\d)$/\', \'$1****$3\', $this->getTelephone());
    }';
        }
        else if ($_tableKey=='password')
        {
            $strSetGet .= "\n".'    public function isPasswordRight($password)
    {
        return Utility::getEncodedPwd($password)==$this->getPassword();
    }';
        }

    }
    $strSetGet .= "\n"."\n";

    $_modelFucStrings[$_tableKey] = $strSetGet;
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

';
if (IS_SPECIAL_TABLE == 'user')
{
    $_modelString .= '    public static $authViewDisabled    = array(\'password\',\'telephone\');//展示数据时，禁止列表。';
}
else if (IS_SPECIAL_TABLE == 'smsVerify')
{
    $_modelString .= '    public static $authViewDisabled    = array(\'verifyCode\');//展示数据时，禁止列表。';
}
else
{
    $_modelString .= '    public static $authViewDisabled    = array();//展示数据时，禁止列表。';
}

$_modelString .= '

    /**
     * 初始化方法，如果需要，各模型必须重写此处
     * @param int|array 如果是整数, 赋值给对象的id,如果是数组, 给对象的逐个属性赋值
     * @return '.$_modelName.'
     */
    public static function instance($pData=null) {
        $_o = parent::instanceModel(__class__, $pData);
';
if (IS_SPECIAL_TABLE == 'user')
{
    $_modelString .= '        if (array_key_exists(\'password\', $pData))//axing edit
        {
            $_o->setPassword($pData[\'password\'],false);//从数组（来自数据库）转化成UserModel,其密码就是加密后字符串，所以，不要再次加密。
        }';
}
else
{
    $_modelString .= '';
}

$_modelString .= '
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
        ,\'genre\':\'\'
        ,\'action\':\''.W2String::under_score($_tableName).'/columns\'
        ,\'method\':\'get\'
        ,\'request\':[]
      });
';
$_apitestConfigArray[] = "\n".$_apitestConfigSingle."\n";


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
        $_controllerStringTmp = "\n".'                $tmpModel  ->'
                                    .str_pad('set'.W2String::camelCaseWithUcFirst($_fieldRow['Field']),20,' ',STR_PAD_LEFT)
                                    .str_pad('('. sprintf(CMysql2PHP::getMethodString($_fieldRow,false),W2String::under_score($_fieldRow['Field'])) .');',70,' ',STR_PAD_RIGHT)
                                    .(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestAddTmp = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':'.(in_array(W2String::under_score($_fieldRow['Field']),$_tableKeysImportantForAdd)?' true':'false').' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\''.($_isAdmin?'*限管理员可用':'').'\' }';
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
if (IS_SPECIAL_TABLE == 'smsVerify' || IS_SPECIAL_TABLE == 'unionLogin')
{

}
else
{
    $_apitestConfigArray[] = "\n".$_apitestConfigSingleAdd."\n";
}


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
        $_controllerStringUpdateTmp = "\n".'                $tmpModel    ->'
                                          .str_pad('set'.W2String::camelCaseWithUcFirst($_fieldRow['Field']),20,' ',STR_PAD_LEFT)
                                          .str_pad('('. sprintf(CMysql2PHP::getMethodString($_fieldRow,true),W2String::under_score($_fieldRow['Field'])) .');',70,' ',STR_PAD_RIGHT)
                                          .(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestUpdateTmp = '          ,'.'{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\''.($_isAdmin?'*限管理员可用':'').'\' }';
        if (in_array($_fieldRow['Field'],array('createTime','modifyTime','lastLoginTime','lastPasswordTime')))
        {
            $_apitestConfigRequestUpdateTmp = '// '.$_apitestConfigRequestUpdateTmp;
        }
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
$_apitestConfigSingleUpdate .= implode("\n",array_merge($_apitestConfigRequestUpdate,array_merge($_apitestConfigRequestUpdateNormal,$_apitestConfigRequestUpdateAdmin))) .'
        ]
      });
';
if (IS_SPECIAL_TABLE == 'smsVerify' || IS_SPECIAL_TABLE == 'unionLogin' || $_tableIdName=='')
{

}
else
{
    $_apitestConfigArray[] = "\n".$_apitestConfigSingleUpdate."\n";
}


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
    if (in_array(CMysql2PHP::getPhpProp($_fieldRow),array('integer','string','datetime','date','float')))
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
    if (CMysql2PHP::getPhpProp($_fieldRow) == 'datetime' || CMysql2PHP::getPhpProp($_fieldRow) == 'date' )
    {
        $_controllerStringList .= "\n".'        '
                                      .str_pad('$pWhere[\''.$_fieldRow['Field'].' >= \\\'%s\\\'\']',40,' ',STR_PAD_RIGHT)
                                      .str_pad(' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow,false),W2String::under_score($_fieldRow['Field']).'start') .';',70,' ',STR_PAD_RIGHT)
                                      .(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'start'.'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'>=起始时间（之后）：'.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
        $_controllerStringList .= "\n".'        '
                                      .str_pad('$pWhere[\''.$_fieldRow['Field'].' < \\\'%s\\\'\']',40,' ',STR_PAD_RIGHT)
                                      .str_pad(' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow,false),W2String::under_score($_fieldRow['Field']).'end') .';',70,' ',STR_PAD_RIGHT)
                                      .(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'end'.'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'<结束时间（之前）：'.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
    }
    else if ($_fieldRow['Field']=='status')
    {
        $_controllerStringList .= "\n".'        '
                                      .str_pad('$pWhere[\''.$_fieldRow['Field'].' in (%s)\']',40,' ',STR_PAD_RIGHT)
                                      .str_pad(' = STATUS_NORMAL;',70,' ',STR_PAD_RIGHT)
                                      .'//默认列表页只筛选STATUS_NORMAL状态的数据';
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'*限管理员可用\' }';
    }
    // else if ($_fieldRow['Field']=='userID')
    // {
    //     //默认不支持用户筛选，只能筛选登录用户自己的数据;
    //     $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'0\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'默认登录用户只能筛选自己名下数据 ，管理员可筛选指定用户\' }';
    // }
    else
    {
        $_controllerStringList .= "\n".'        '
                                      .str_pad('$pWhere[\''.$_fieldRow['Field'].'\']',40,' ',STR_PAD_RIGHT)
                                      .str_pad(' = '. sprintf(CMysql2PHP::getMethodString($_fieldRow,false),W2String::under_score($_fieldRow['Field'])) .';',70,' ',STR_PAD_RIGHT)
                                      .(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\''.W2String::under_score($_fieldRow['Field']).'\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\''.CMysql2PHP::getPhpProp($_fieldRow).'\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\''.(($_fieldRow['Field']=='userID')?'0':'').'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\''.(!is_null($_fieldRow['Comment'])?$_fieldRow['Comment']:$_fieldRow['Field']).'\' ,\'desc\':\'\' }';
    }

    if (CMysql2PHP::getPhpProp($_fieldRow) == 'string' || CMysql2PHP::getPhpProp($_fieldRow) == 'text')
    {
        $_controllerKeySearchList .= "\n".'            '.str_pad('$keyWhere[] = sprintf(\''.$_fieldRow['Field'].' like \\\'%%%s%%\\\'\',',60,' ',STR_PAD_RIGHT).'$keyWord);'.(!is_null($_fieldRow['Comment'])?'//'.$_fieldRow['Comment']:'');
        $_controllerKeyFieldList[] = $_fieldRow['Field'];
    }
}
$_apitestConfigRequestList[] = '{'.str_pad(' \'key\':\'keyword\'',30,' ',STR_PAD_RIGHT).' '.str_pad(',\'type\':\'string\'',20,' ',STR_PAD_RIGHT).' ,\'required\':false ,\'time\':\'\' '.str_pad(',\'test-value\':\'\'',40,' ',STR_PAD_RIGHT).' ,\'title\':\'检索关键字\' ,\'desc\':\''.'\' }';//.(implode(' ',$_controllerKeyFieldList))

$_apitestConfigSingle .= implode("\n".'          ,',$_apitestConfigRequestList) .'
        ]
      });
';

    $_apitestConfigArray[] = "\n".$_apitestConfigSingle."\n";



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
if (IS_SPECIAL_TABLE == 'smsVerify' || IS_SPECIAL_TABLE == 'unionLogin' || $_tableIdName=='')
{

}
else
{
    $_apitestConfigArray[] = "\n".$_apitestConfigSingle."\n";
}

$authViewDisabled = '';

if (IS_SPECIAL_TABLE=='user')
{
    $authViewDisabled = '\'password\'';
}
else if (IS_SPECIAL_TABLE=='smsVerify')
{
    $authViewDisabled = '\'verifyCode\'';
}

if (IS_SPECIAL_TABLE=='user')
{
    $_apitestConfigArray[] = "\n".'apiList.push({
        \'title\':\'用户:修改密码（不登录，需要验证短信）\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/update_with_verify_code\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'telephone\'             ,\'type\':\'string\'     ,\'required\': true ,\'test-value\':\'13774298448\'                         ,\'title\':\'用户手机号\' ,\'desc\':\'\' }
          ,{ \'key\':\'verify_code\'            ,\'type\':\'string\'    ,\'required\': true ,\'test-value\':\'123456\'                             ,\'title\':\'验证码\' ,\'desc\':\'必需\' }
          ,{ \'key\':\'newpassword\'           ,\'type\':\'md5\'        ,\'required\': true ,\'test-value\':\'123456\'                              ,\'title\':\'密码\' ,\'desc\':\'\' }
        ]
      });

apiList.push({
        \'title\':\'用户:修改密码／邮箱/手机（需要登录，并提供原始密码）\'
        ,\'desc\':\'（修改手机需要验证新手机）<br/>（联合登录用户，初次设定密码不需要原始密码）\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/update_with_oldpassword\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'oldpassword\'              ,\'type\':\'md5\'        ,\'required\': false ,\'test-value\':\'123456\'                              ,\'title\':\'旧密码\' ,\'desc\':\'\' }
          ,{ \'key\':\'newpassword\'              ,\'type\':\'md5\'        ,\'required\': false ,\'test-value\':\'654321\'                              ,\'title\':\'新密码\' ,\'desc\':\'\' }
          ,{ \'key\':\'email\'                 ,\'type\':\'string\'     ,\'required\':false ,\'time\':\'\' ,\'test-value\':\'wyx2@haoxitech.com\'                         ,\'title\':\'邮箱\' ,\'desc\':\'\' }
          ,{ \'key\':\'telephone\'             ,\'type\':\'string\'     ,\'required\': false ,\'test-value\':\'13774298448\'                         ,\'title\':\'用户手机号\' ,\'desc\':\'\' }
          ,{ \'key\':\'verify_code\'            ,\'type\':\'string\'    ,\'required\': false ,\'test-value\':\'123456\'                             ,\'title\':\'验证码\' ,\'desc\':\'如果修改手机号，需要验证新手机号。\' }
        ]
      });


apiList.push({
        \'title\':\'用户:登录\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/login\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'account\'               ,\'type\':\'string\'     ,\'required\':true ,\'test-value\':\'13774298448\'                     ,\'title\':\'支持手机号、用户名、邮箱登录\' ,\'desc\':\'\' }
          ,{ \'key\':\'password\'              ,\'type\':\'md5\'     ,\'required\':true ,\'test-value\':\'123456\'                         ,\'title\':\'密码\' ,\'desc\':\'\' }
       ]
      });

apiList.push({
        \'title\':\'用户:联合登录\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/union_login\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'union_type\'              ,\'type\':\'int\'     ,\'required\':true ,\'test-value\':\'2\'                         ,\'title\':\'登录方式：2QQ 3微博 4微信\' ,\'desc\':\'\' }
          ,{ \'key\':\'union_token\'             ,\'type\':\'string\'     ,\'required\':true ,\'test-value\':\'398ADCFAED79A49ACBE516EE89F7950B\'                     ,\'title\':\'联合登录唯一识别码\' ,\'desc\':\'\' }
       ]
      });

apiList.push({
        \'title\':\'用户:登录后绑定对应联合登录\'
        ,\'desc\':\'登录后调用该接口可新增绑定\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/set_union_login\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'union_type\'              ,\'type\':\'int\'     ,\'required\':true ,\'test-value\':\'2\'                         ,\'title\':\'登录方式：2QQ 3微博 4微信\' ,\'desc\':\'\' }
          ,{ \'key\':\'union_token\'             ,\'type\':\'string\'     ,\'required\':true ,\'test-value\':\'398ADCFAED79A49ACBE516EE89F7950B\'                     ,\'title\':\'联合登录唯一识别码\' ,\'desc\':\'\' }
       ]
      });

apiList.push({
        \'title\':\'用户:注销\'
        ,\'desc\':\'用户点击注销，本地删除其登录信息，同时调用本接口以便服务器解除其账号与设备的绑定信息。\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/log_out\'
        ,\'method\':\'get\'
        ,\'request\':[

       ]
      });

apiList.push({
        \'title\':\'用户:我的信息\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/get_my_detail\'
        ,\'method\':\'get\'
        ,\'request\':[

          ]
      });



apiList.push({
        \'title\':\'用户:删除（仅供管理员测试期间用)\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/user/delete\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'ids\'                   ,\'type\':\'string\'     ,\'required\':false ,\'test-value\':\'\'                         ,\'title\':\'多个id用逗号隔开\' ,\'desc\':\'\' }
          ,{ \'key\':\'id\'                    ,\'type\':\'integer\'    ,\'required\':false ,\'test-value\':\'\'                         ,\'title\':\'\' ,\'desc\':\'\' }
          ,{ \'key\':\'telephone\'             ,\'type\':\'string\'     ,\'required\':false ,\'test-value\':\'13112345678\'              ,\'title\':\'用户手机号\' ,\'desc\':\'\' }
        ]
      });'."\n";
}
if (IS_SPECIAL_TABLE=='smsVerify')
{
    $_apitestConfigArray[] = "\n".'apiList.push({
        \'title\':\'验证码:发送一条验证码到手机\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/sms_verify/send_verify_code\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'telephone\'             ,\'type\':\'string\'     ,\'required\': true ,\'test-value\':\'10000000000\'                         ,\'title\':\'\' ,\'desc\':\'\' }
           ,{ \'key\':\'usefor\'             ,\'type\':\'int\'     ,\'required\': true,\'test-value\':\'2\'                         ,\'title\':\'验证码用途\' ,\'desc\':\'1：注册用 2：登陆用 3：修改密码或修改手机号码用\' }
        ]
      });

apiList.push({
        \'title\':\'验证码:确认验证码是否正确\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/sms_verify/check_verify_code\'
        ,\'method\':\'post\'
        ,\'request\':[
           { \'key\':\'telephone\'             ,\'type\':\'string\'     ,\'required\': true ,\'test-value\':\'13774298448\'                         ,\'title\':\'手机号码\' ,\'desc\':\'\' }
          ,{ \'key\':\'verify_code\'             ,\'type\':\'string\'     ,\'required\': true ,\'test-value\':\'123456\'                         ,\'title\':\'验证码\' ,\'desc\':\'\' }
        ]
      });

apiList.push({
        \'title\':\'验证码:剩余短信量\'
        ,\'desc\':\'\'
        ,\'time\':\''.date('Y-m-d H:i:s').'\'
        ,\'action\':\'/sms_verify/get_balance\'
        ,\'method\':\'get\'
        ,\'request\':[
        ]
      });';
}
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
                                                \'empty\'    =>array('.$authViewDisabled.')
                                                ,\'visitor\' =>array('.$authViewDisabled.')
                                                ,\'disabled\'=>array('.$authViewDisabled.')
                                                ,\'pending\' =>array('.$authViewDisabled.')
                                                ,\'draft\'   =>array('.$authViewDisabled.')
                                                ,\'normal\'  =>array('.$authViewDisabled.')
                                                ,\'self\'    =>array('.$authViewDisabled.')
                                                ,\'admin\'   =>array('.$authViewDisabled.')
                                                );//查看相关字段权限
    public static $IGNORE_METHOD_CHECK = false; //是否忽略表单提交方式的检测
    // ＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝接口方法都在下面定义 action开头的方法是对外接口＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝＝
    //权限
    public static function getAuthIfUserCanDoIt($pUserID,$pAction,$pTargetModel=null)
    {
        $auth = parent::getAuthIfUserCanDoIt($pUserID,$pAction,$pTargetModel);
        $_user = Utility::getUserByID($pUserID);
        if (is_object($_user))
        {';
if (IS_SPECIAL_TABLE == 'user')
{
    $_controllerString .= "\n".'            if (is_object($pTargetModel) && $pTargetModel->getId()==$pUserID )
            {
                $auth = \'self\';
            }';
}
$_controllerString .= '
            switch ($pAction)
            {
                case \'add\'    :    break;
                case \'update\' :    break;
                case \'detail\' :    break;
                case \'list\'   :    break;
            }
        }
        '.$_modelName.'::$authViewDisabled = static::$authViewDisabledList[$auth];
        return $auth;
    }

';
if (IS_SPECIAL_TABLE == 'smsVerify')
{
    $_controllerString .='
    //新建
    public static function actionSendVerifyCode()
    {
        $telephone = W2HttpRequest::getRequestTelephone(\'telephone\',false);
        $_smsVerifyModelFound = SmsVerifyHandler::loadModelFirstInList(array(\'telephone\'=>$telephone),\'id desc\');
        if (isset($_smsVerifyModelFound) && defined(\'SMS_VERIFYCODE_SEND_INTERVAL\') && W2Time::getTimeBetweenDateTime(null,$_smsVerifyModelFound->getCreateTime()) < SMS_VERIFYCODE_SEND_INTERVAL )
        {//此处对发送频率作限制
            return HaoResult::init(ERROR_CODE::$SMS_TOO_OFEN);
        }

        $_useFor = W2HttpRequest::getRequestInt(\'usefor\');

        $_userModel = UserHandler::loadModelFirstInList(array(\'telephone\'=>$telephone));
        switch ($_useFor) {
            case SMS_USEFOR::REGISTER:
                if (is_object($_userModel))
                {
                    return HaoResult::init(ERROR_CODE::$SMS_PHONE_EXISTS);
                }
                break;
            case SMS_USEFOR::LOGIN:
                if (!is_object($_userModel))
                {
                    return HaoResult::init(ERROR_CODE::$SMS_NO_PHONE_FOUND);
                }
                break;
            case SMS_USEFOR::RESTPWD:
                if ( !is_object($_userModel) )
                {
                    return HaoResult::init(ERROR_CODE::$SMS_PHONE_INVAILD);
                }
                else if ( $_userModel->getStatus() == STATUS_DISABLED )
                {
                    return HaoResult::init(ERROR_CODE::$USER_BEEN_DISABLED);
                }
                break;

            default:
                return HaoResult::init(ERROR_CODE::$SMS_PLS_USEFOR);
                break;
        }

        $tmpModel =  new SmsVerifyModel();
        $_verifyCode = W2String::buildRandNumbers(6);

        $tmpModel  ->           setUserID(Utility::getCurrentUserID());
        $tmpModel  ->        setTelephone($telephone);
        $tmpModel  ->       setVerifyCode($_verifyCode);
        $tmpModel  ->       setUseFor($_useFor);

        $result = static::save($tmpModel,$isAdd=true);
        if ($result->isResultsOK())
        {
            $pMsg = \'验证码:\'.$_verifyCode.\' 退订回N【HaoFrame】\';
            $result->setErrorStr( \'短信已发送成功成功，请注意查收。\' );
            // $result[\'extraInfo\'][\'smsResult\'] = W2SMS::sendMessage($telephone,$pMsg);
            // $result[\'extraInfo\'][\'smsResult\'] = W2SMS::sendVerifyCodeWithUcpaas($telephone,$_verifyCode);//使用融云发送验证码
            $result->setErrorStr( $pMsg );//此处默认直接展示了验证码，实际开发过程中，请更改此处逻辑。
        }
        return $result;
    }


    /** 确认验证码是否正确 */
    public static function actionCheckVerifyCode($_useFor = null)
    {

        $pTelephone      = W2HttpRequest::getRequestTelephone(\'telephone\',false);
        $pVerifyCode     =    W2HttpRequest::getRequestString(\'verify_code\',false);

        $_smsVerifyModel = SmsVerifyHandler::loadModelFirstInList(array(\'telephone\'=>$pTelephone,\'useFor\'=>$_useFor),\'id desc\',1,1);

        if (!is_object($_smsVerifyModel) || $_smsVerifyModel->getVerifyCode()!=$pVerifyCode || $_smsVerifyModel->getVerifyTime()!=null)
        {
            return HaoResult::init(ERROR_CODE::$SMS_VERIFYCODE_WRONG,false);
        }

        if (defined(\'SMS_VERIFYCODE_TIME_USEABLE\') && W2Time::getTimeBetweenDateTime(null,$_smsVerifyModel->getCreateTime())>SMS_VERIFYCODE_TIME_USEABLE)
        {
            return HaoResult::init(ERROR_CODE::$SMS_VERIFYCODE_TIMEOUT,false);
        }

        $_smsVerifyModel->setVerifyTime(date(\'Y-m-d H:i:s\'));
        static::save($_smsVerifyModel);
        return HaoResult::init(ERROR_CODE::$OK,true);

    }

    /** 查询剩余短信 */
    public static function actionGetBalance()
    {
        $balance = W2SMS::GetBalance();

        return HaoResult::init(ERROR_CODE::$OK,$balance);
    }
    ';
}
else if (IS_SPECIAL_TABLE=='unionLogin')
{

}
else
{

    $_controllerString .='
    /**
     * 新建数据
     * @return HaoResult
     */
    public static function actionAdd()
    {
        $unsetKey = W2HttpRequest::getUnsetRequest(\''.(IS_SPECIAL_TABLE == 'user'?'telephone,password':strtolower(implode(',',$_tableKeysImportantForAdd))).'\', $pAllowBlank = false);
        if ( $unsetKey  !== null)
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array(\'errorContent\'=>\'部分参数未提交数据: \'.$unsetKey));
        }
        $tmpModel =  new '.$_modelName.'();

        switch ( $auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'add\',$tmpModel))
        {
            case \'admin\'://有管理权限
'.$_controllerStringAdmin.'
            case \'self\'  ://作者
            case \'normal\'://正常用户
';
    if(array_key_exists('userID',$_tableDataKeys))
    {
        $_controllerString .= "\n".'                if ($auth == \'normal\' || $tmpModel->properyValue(\'userID\')===null)
                {
                    $tmpModel  ->       setUserID(Utility::getCurrentUserID());//普通用户创建的数据，默认作者为自己。
                }';
    }
    if (IS_SPECIAL_TABLE == 'user')
    {
        $_controllerString .= "\n".'
            case \'visitor\'://游客
                if ($auth != \'admin\')
                {
                    //检查校验码
                    $_resultSmsCheck = SmsVerifyController::actionCheckVerifyCode(SMS_USEFOR::REGISTER);
                    if (!$_resultSmsCheck->isResultsOK())
                    {
                        return $_resultSmsCheck;
                    }
                }';
    }

$_controllerString .= ''.$_controllerStringNormal.'
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

';
if ($_tableIdName!='')
{//只有存在主键的数据才支持自动创建更新代码。（因为没有主键就没法用默认id更新啦，想要通过其他方式更新的，请自行设计对应接口。）
$_controllerString .= '
        /**
         * 修改数据
         * @return HaoResult
         */
        public static function actionUpdate()
        {
            $pId = W2HttpRequest::getRequestInt(\'id\',null,false,false);

            $tmpModel = '.$_handlerName.'::loadModelById($pId);

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
    ';

}
}

$_controllerString .='
    /**
     * 保存或更新数据
     * @param  '.$_modelName.'  $tmpModel 更改后的模型对象
     * @param  boolean       $isAdd    新增还是更新
     * @return HaoResult
     */
    public static function save($tmpModel,$isAdd=false)
    {
';

    if (IS_SPECIAL_TABLE == 'user')
    {
        $_controllerString .= "\n"
.'        if ($tmpModel->isProperyModified(\'username\') && $tmpModel->properyValue(\'username\')!==\'\' )
        {
            if (W2String::isTelephone($tmpModel->properyValue(\'username\')))
            {
                return HaoResult::init(ERROR_CODE::$USER_UNAME_NO_PHONE);
            }
            if (W2String::isEmail($tmpModel->properyValue(\'username\')))
            {
                return HaoResult::init(ERROR_CODE::$USER_UNAME_NO_EMAIL);
            }
            $existsTargetModel = UserHandler::loadModelFirstInList( array(\'username\'=>$tmpModel->properyValue(\'username\') ) );
            if (is_object( $existsTargetModel ) &&  $existsTargetModel->getId() != $tmpModel->properyValue(\'id\'))
            {
                return HaoResult::init(ERROR_CODE::$USER_DUP_USERNAME);
            }
        }
        if ($tmpModel->isProperyModified(\'telephone\') && $tmpModel->properyValue(\'telephone\')!==\'\' )
        {
            $existsTargetModel = UserHandler::loadModelFirstInList( array(\'telephone\'=>$tmpModel->properyValue(\'telephone\') ) );
            if (is_object( $existsTargetModel ) &&  $existsTargetModel->getId() != $tmpModel->properyValue(\'id\'))
            {
                return HaoResult::init(ERROR_CODE::$USER_DUP_TELEPHONE);
            }
        }
        if ($tmpModel->isProperyModified(\'email\') && $tmpModel->properyValue(\'email\')!==\'\' )
        {
            $existsTargetModel = UserHandler::loadModelFirstInList( array(\'email\'=>$tmpModel->properyValue(\'email\') ) );
            if (is_object( $existsTargetModel ) &&  $existsTargetModel->getId() != $tmpModel->properyValue(\'id\'))
            {
                return HaoResult::init(ERROR_CODE::$USER_DUP_EMAIL);
            }
        }
        //修改密码自动记录其修改时间，第一次创建密码时不记录。
        if ($tmpModel->isProperyModified(\'password\') && $tmpModel->properyOriginal(\'password\')!=null)
        {
            $tmpModel->setLastPasswordTime(date(\'Y-m-d H:i:s\'));
        }
';
    }

$_controllerString .= '
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
        $pWhere = array();
        '.($_tableIdName!=''?str_pad('$pWhere[\''.$_tableIdName.' in (%s)\'] ',40,' ',STR_PAD_RIGHT).' = W2HttpRequest::getRequestArrayString(\'ids\',false,true);
':'').$_controllerStringList.'
'.($_controllerKeySearchList!=''?'        $keyWord                                 = W2HttpRequest::getRequestString(\'keyword\');
        if ($keyWord!=null)
        {
            $keyWhere = array();
            $keyWord = preg_replace(\'/\s+/\',\'%\',$keyWord);

            //------- 自行拼接sql语句，请务必注意sql注入的问题。 -----
            $keyWord = DBTool::wrap2Sql($keyWord);//转义字符，防注入。
'.$_controllerKeySearchList.'

            //将上述模糊查询代码使用or关联后用括号括起来，放入pWhere
            $pWhere[] = \'(\'.implode(\' or \',$keyWhere).\')\';
        }':'').'


                //两表一对一用关联查询
                //$pWhere[\'joinList\'] = array();
                //$pWhere[\'joinList\'][] = array(\'tbl_example t2\',array(\'t2.teacherID = t1.userID\',\'t2.id\'=>$keyWord));
                //两表一对多用exists查询
                //$pWhere[] = \'exists (select t2.id from tbl_example t2 where t2.teacherID = t1.userID limit 1)\';

        //根据权限不同，支持的筛选功能也可以不同
        switch ( $auth = static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'list\'))
        {
            case \'admin\'   : //有管理权限
'
                .(array_key_exists('status',$_tableDataKeys)?'                $pWhere[\'status in (%s)\']           = W2HttpRequest::getRequestArrayString(\'status\',true,true,array(STATUS_NORMAL));//管理员可以筛选数据状态':'')
                .'
            case \'self\'    : //作者
            case \'normal\'  : //正常用户
'
// .(array_key_exists('userID',$_tableDataKeys)?'if ($auth == \'normal\')
//                 {
//                     $pWhere[\'userID\']           = Utility::getCurrentUserID();//普通用户，默认只能筛选自己名下数据。
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
                $pOrder = W2String::camelCase($_order);
                break;
            case \'\':
            case \'default\':
                $pOrder = \''.$_tableIdName.'\';
                break;
            default:
                return HaoResult::init(ERROR_CODE::$ORDER_VALUE_ERROR);
                break;
        }

        $pCountThis = -1; //此处必须设定为-1。除非你知道它的用法。
        return static::aList($pWhere,$pOrder,$pPageIndex=null,$pPageSize=null,$pCountThis,$isDetail = false);
    }
';
if ($_tableIdName!='')
{//有主键才能支持默认用id查详情，不然只能去列表里查了。
$_controllerString .= '
        //详情
        public static function actionDetail()
        {
            return static::detail();
        }
    ';
}
$_controllerString .= '
    /**
     * load数据并进行读取权限判断
     */
    protected static function loadList($pWhere=null,$pOrder=null,$pPageIndex=null,$pPageSize=null,&$pCountThis=null,$isDetail = false)
    {

        $tmpResult = parent::loadList($pWhere,$pOrder,$pPageIndex,$pPageSize,$pCountThis,$isDetail);
        if (is_object($tmpResult) && get_class($tmpResult)==\'HaoResult\')
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

        return $tmpResult;
    }
';
    if (IS_SPECIAL_TABLE == 'user')
    {
        $_controllerString .= "\n".'    //用户:修改密码／邮箱/手机（需要登录，并提供原始密码）（修改手机需要验证新手机）（联合登录用户，初次设定密码不需要原始密码）
    public static function actionUpdateWithOldPassword()
    {

        $pWhere = array();
        $tmpModel                    = Utility::getCurrentUserModel();
        if (!is_object($tmpModel))
        {
            return HaoResult::init(ERROR_CODE::$NOT_USER);
        }

        $oldpassword              = W2HttpRequest::getRequestString(\'oldpassword\');

        if (is_null($oldpassword))
        {
            if ($tmpModel->getPassword()!==null)
            {//除非当前密码为空（第三方登录），否则必须使用当前密码才能继续操作。
                return HaoResult::init(ERROR_CODE::$USER_PLS_OLD_PWD);
            }
        }
        else
        {
            if (!$tmpModel->isPasswordRight($oldpassword))
            {
                return HaoResult::init(ERROR_CODE::$USER_WRONG_OLD_PWD);
            }
        }

        //修改密码
        $newPassword = W2HttpRequest::getRequestString(\'newpassword\');
        if (!is_null($newPassword) && $newPassword == $oldpassword)
        {
            return HaoResult::init(ERROR_CODE::$USER_WRONG_OLD_PWD);
        }
        $tmpModel    ->         setPassword($newPassword);

        //修改手机需要检查新手机的校验码
        $newTelephone = W2HttpRequest::getRequestTelephone(\'telephone\');
        if (!is_null($newTelephone))
        {
            //检查校验码
            $_resultSmsCheck = SmsVerifyController::actionCheckVerifyCode(SMS_USEFOR::RESTTEL);
            if (!$_resultSmsCheck->isResultsOK())
            {
                return $_resultSmsCheck;
            }
            $tmpModel    ->        setTelephone($newTelephone);//用户手机号
        }

        //修改邮箱
        $tmpModel    ->            setEmail(W2HttpRequest::getRequestEmail(\'email\'));//邮箱

        return static::save($tmpModel);
    }

    //用户:修改密码（不登录，通过验证短信修改密码）
    public static function actionUpdateWithVerifyCode()
    {
        $unsetKey = W2HttpRequest::getUnsetRequest(\'telephone,verify_code,newpassword\', $pAllowBlank = false);
        if ( $unsetKey  !== null)
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR,array(\'errorContent\'=>\'部分参数未提交数据: \'.$unsetKey));
        }

        //检查校验码
        $_resultSmsCheck = SmsVerifyController::actionCheckVerifyCode(SMS_USEFOR::RESTPWD);
        if (!$_resultSmsCheck->isResultsOK())
        {
            return $_resultSmsCheck;
        }

        $pWhere = array();
        $pWhere[\'telephone\']                    = W2HttpRequest::getRequestString(\'telephone\',false);
        $pWhere[]                               = \'status <> \'.STATUS_DISABLED;

        $tmpModel = UserHandler::loadModelFirstInList($pWhere);

        if (!is_object($tmpModel))
        {
            return HaoResult::init(ERROR_CODE::$DATA_EMPTY);
        }

        $tmpModel    ->         setPassword(W2HttpRequest::getRequestString(\'newpassword\'));//修改密码
        $tmpModel    ->         setLastPasswordTime(date(\'Y-m-d H:i:s\'));//修改密码的同时，更新密码修改时间。

        return static::save($tmpModel);
    }

    /**
     * 通用方法根据指定条件读取用户信息若成功则返回登录信息
     * @param  array  $pWhere
     * @return HaoResult
     */
    public static function loginWithWhere($pWhere=array())
    {
        if ($_SERVER[\'REQUEST_METHOD\'] != \'POST\' )
        {
            return HaoResult::init(ERROR_CODE::$ONLY_POST_ALLOW);
        }

        if (Utility::getCurrentUserID()>0)
        {
            return HaoResult::init(ERROR_CODE::$ONLY_VISITOR_ALLOW);
        }

        //检查图像校验码
        // $_resultCaptchaCheck = AxapiController::actionCheckCaptcha();
        // if (!$_resultCaptchaCheck->isResultsOK())
        // {
        //     return $_resultCaptchaCheck;
        // }

        $tmpModel = UserHandler::loadModelFirstInList($pWhere);

        if (!is_object($tmpModel))
        {
            return HaoResult::init(ERROR_CODE::$USER_LOGIN_FAIL);
        }
        switch($tmpModel->getStatus())
        {
            case STATUS_DISABLED:
                return HaoResult::init(ERROR_CODE::$USER_BEEN_DISABLED);
                break;
        }

        $tmpModel->setLastLoginTime(date(\'Y-m-d H:i:s\'));
        $savedModel = UserHandler::saveModel($tmpModel);
        if (Utility::getHeaderValue(\'Devicetoken\')!=\'\' && class_exists(\'DeviceController\'))
        {//登录后，绑定对应设备
            DeviceController::setDeviceWithUser(Utility::getHeaderValue(\'Devicetoken\'),$savedModel);
        }
        UserModel::$authViewDisabled = static::$authViewDisabledList[\'self\'];
        return HaoResult::init(ERROR_CODE::$OK,$savedModel,array(\'authInfo\'=>Utility::getHeaderAuthInfoForUserID($savedModel->getId())));
    }

    //登录
    public static function actionLogin()
    {
        $pWhere = array();
        $pWhere[\'password\']                     = Utility::getEncodedPwd(W2HttpRequest::getRequestString(\'password\',false));
        $pWhere[]                                 = \'status <> \'.STATUS_DISABLED;
        $account                                  = W2HttpRequest::getRequestString(\'account\',false);
        if (W2String::isTelephone($account) )
        {
            $pWhere[\'telephone\']                    = $account;
        }
        else if (W2String::isEmail($account) )
        {
            $pWhere[\'email\']                    = $account;
        }
        else if (!is_null($account) )
        {
            $pWhere[\'username\']                    = $account;
        }
        else
        {
            return HaoResult::init(ERROR_CODE::$USER_PLS_ACCOUNT);
        }
        if (is_null($pWhere[\'password\']) )
        {
            return HaoResult::init(ERROR_CODE::$USER_PLS_PWD);
        }
        return UserController::loginWithWhere($pWhere);
    }

    //联合登录
    public static function actionUnionLogin()
    {
        $unionToken                    = W2HttpRequest::getRequestString(\'union_token\');
        $unionType                     = W2HttpRequest::getRequestString(\'union_type\');

        if (is_null($unionToken) || is_null($unionType)  )
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR);
        }

        $pWhere = array();
        $pWhere[]                               = \'status <> \'.STATUS_DISABLED;

        $pWhere[\'joinList\'] = array();
        $pWhere[\'joinList\'][] = array(\'unionLogin t2\',array(\'t2.userID = t1.id\',\'t2.unionToken\'=>$unionToken,\'t2.unionType\'=>$unionType));


        $results = UserController::loginWithWhere($pWhere);

        if ($results->isErrorCode(ERROR_CODE::$USER_LOGIN_FAIL))
        {

            $tmpModel = new UserModel();
            $tmpResult = static::save($tmpModel,$isAdd=true);
            if ($tmpResult->isResultsOK())
            {
                $unionLoginModel = UnionLoginHandler::loadModelByToken($unionToken,$unionType);
                $unionLoginModel->setUserID($tmpResult->getResults()->getId());
                UnionLoginHandler::saveModel($unionLoginModel);
                $results = UserController::loginWithWhere($pWhere);
            }
            else
            {
                return $tmpResult;
            }
        }
        // if ($results->isResultsOK())
        // {

        //     $userModel = $results->getResults();
        //     $userModel  ->         setRealname(W2HttpRequest::getRequestString(\'realname\',true,null,0,20));         //姓名
        //     $userModel  ->           setAvatar(W2HttpRequest::getRequestString(\'avatar\',true,null,0,200));          //头像
        //     if (count($userModel->propertiesModified())>0)
        //     {
        //         $savedModel = UserHandler::saveModel($userModel);
        //         $results->setResults($savedModel);
        //     }
        // }
        UserModel::$authViewDisabled = static::$authViewDisabledList[\'self\'];
        return $results;
    }

    //登录用户可以关联联合登录账号
    public static function actionSetUnionLogin()
    {
        $unionToken                    = W2HttpRequest::getRequestString(\'union_token\');
        $unionType                     = W2HttpRequest::getRequestString(\'union_type\');

        if (is_null($unionToken) || is_null($unionType)  )
        {
            return HaoResult::init(ERROR_CODE::$USER_PLS_PWD);
        }

        if (Utility::getCurrentUserID()>0)//绑定账号
        {
            $unionLoginModel = UnionLoginHandler::loadModelByUserID(Utility::getCurrentUserID(),$unionType);
            if (is_object($unionLoginModel))
            {
                $unionLoginModel->setUserID(\'NULL\');
                $unionLoginModel->setModifyTime(date(\'Y-m-d H:i:s\'));
                UnionLoginHandler::saveModel($unionLoginModel);
            }
            $unionLoginModel = UnionLoginHandler::loadModelByToken($unionToken,$unionType);
            if ($unionLoginModel->getUserID()>0)
            {
                return HaoResult::init(ERROR_CODE::$USER_USED_UNION);
            }
            $unionLoginModel->setUserID(Utility::getCurrentUserID());
            $unionLoginModel->setModifyTime(date(\'Y-m-d H:i:s\'));
            UnionLoginHandler::saveModel($unionLoginModel);
            return HaoResult::init(ERROR_CODE::$OK,$unionLoginModel);
        }
        UserModel::$authViewDisabled = static::$authViewDisabledList[\'self\'];
        return HaoResult::init(ERROR_CODE::$NOT_USER);
    }

    //获得登录者的信息
    public static function actionGetMyDetail()
    {

        $tmpModel = Utility::getCurrentUserModel();
        if (!is_object($tmpModel))
        {
            return HaoResult::init(ERROR_CODE::$NOT_USER);
        }

        $extraInfo = array();
        $extraInfo[\'isPasswordNull\'] = $tmpModel->getPassword()==null;

        UserModel::$authViewDisabled = static::$authViewDisabledList[\'self\'];
        return HaoResult::init(ERROR_CODE::$OK,$tmpModel,$extraInfo);
    }

    /**注销登录*/
    public static function actionLogOut()
    {
        if (Utility::getHeaderValue(\'Devicetoken\')!=null && method_exists(\'DeviceController\',\'setDeviceWithUser\'))
        {//注销设备对应的用户信息
            DeviceController::setDeviceWithUser(Utility::getHeaderValue(\'Devicetoken\'),null);
        }
        return HaoResult::init(ERROR_CODE::$OK);
    }

    /**删除用户*/
    public static function actionDelete()
    {
        if (static::getAuthIfUserCanDoIt(Utility::getCurrentUserID(),\'delete\')!=\'admin\')
        {
            return HaoResult::init(ERROR_CODE::$ONLY_ADMIN_ALLOW);
        }

        $pWhere = array();

        $pWhere[\'id in (%s)\']                   = W2HttpRequest::getRequestArrayString(\'ids\',false,true);
        $pWhere[\'id\']                           = W2HttpRequest::getRequestInt(\'id\');
        $pWhere[\'telephone\']                    = W2HttpRequest::getRequestTelephone(\'telephone\',false);//用户手机号
        $pWhere[\'username\']                     = W2HttpRequest::getRequestString(\'username\',false);    //用户名
        $pWhere[\'email\']                        = W2HttpRequest::getRequestEmail(\'email\',false);        //用户邮箱

        if (count(array_filter(array_values($pWhere)))>0)
        {
            $result = array();
            $targetModels = UserHandler::loadModelList($pWhere);
            foreach ($targetModels as $targetModel) {
                $result[] = array(
                                     \'targetModel\'=>$targetModel
                                    ,\'status\'=>UserHandler::removeModel($targetModel)
                                );
            }
            return HaoResult::init(ERROR_CODE::$OK,$result);
        }
        else
        {
            return HaoResult::init(ERROR_CODE::$PARAM_ERROR);
        }
    }';
    }

$_controllerString .= '
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
    $_fileListAdminString = preg_replace('/[\s\S]*?actionList\(\)[\s\S]*?\$pWhere = array\(\).*([\s\S]+?)switch \([\s\S]*/','$1',$_fileString);
    $_fileListAdminStringTmp = $_fileListAdminString;
    foreach (explode("\n",$_controllerStringList) as $_string) {
        if ($_string!='' && strpos($_fileListAdminStringTmp,preg_replace('/[\s\S]*?(p_where.*?\])[\s\S]*/','$1',$_string))===false)
        {
            $_fileListAdminStringTmp = preg_replace('/([\s\S]*\$pWhere\[.*)/','$1'."\n".$_string.'//todo debug, php auto update ,pls checkit',$_fileListAdminStringTmp);
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
    file_put_contents($_apitestConfigFile,"/*\n".implode("\n",$_apitestConfigArray)."\n*/");
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


chmod($_handlerFile,0664);
chmod($_modelFile,0664);
chmod($_controllerFile,0664);
chmod($_apitestConfigFile,0664);



print("done\n");
