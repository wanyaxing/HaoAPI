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

// if ((!isset($argv) || count($argv)<=1) && count(array_keys($_REQUEST))<=1)
// {
//     print("\n".'需要参数：');
//     print("\n".'-t 表名');
//     print("\n".'-name 中文标题');
//     print("\n".'-rm yes');
//     print("\n".'-update yes');
//     exit;
// }


function getValueInArgv($argv_key)
{
    if (isset($_REQUEST[$argv_key]))
    {
        return $_REQUEST[$argv_key];
    }
    global $argv;

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
    return null;
}


global $_phpPath,$_javaPath,$_iosPath;
$_phpPath         = AXAPI_ROOT_PATH.'/../HaoConnect/php/HaoConnect/';
$_javaPath        = AXAPI_ROOT_PATH.'/../HaoConnect/android/HaoConnect/src/main/java/com/haoxitech/HaoConnect/';
$_iosPath         = AXAPI_ROOT_PATH.'/../HaoConnect/ios/HaoConnect/';

global $_modelList;
$_modelList = array();

// 开工前清空相关文件夹
if (getValueInArgv('-clear') == 'yes')
{
    W2File::deldir($_phpPath . 'results/');
    W2File::deldir($_javaPath . 'results/');
    W2File::deldir($_iosPath . 'results/');
    W2File::deldir($_phpPath . 'connects/');
    W2File::deldir($_javaPath . 'connects/');
    W2File::deldir($_iosPath . 'connects/');
    print('已删除相关文件，重新生成中：'."\n");
}
else
{
    print('即将开始更新对应文件：'."\n");
}
// chmod($_phpPath . 'results/',0664);
// chmod($_javaPath . 'results/',0664);
// chmod($_iosPath . 'results/',0664);
// chmod($_phpPath . 'connects/',0664);
// chmod($_javaPath . 'connects/',0664);
// chmod($_iosPath . 'connects/',0664);

umask(0002);

// ===================== config update  ====================================
// $_configExampleFilePath = $_phpPath . 'HaoConfig-example.php';
// $_configFilePath = $_phpPath . 'HaoConfig.php';
// $_configFileContent = file_get_contents($_configExampleFilePath);
// $_configFileContent = str_replace('$apiHost                          = \'???.???.com\'','$apiHost                          = \''.$_SERVER['HTTP_HOST'].'\'',$_configFileContent);
// $_configFileContent = str_replace('$SECRET_HAX_CONNECT               = \'?????????????\'','$SECRET_HAX_CONNECT               = \''.SECRET_HAX_PC.'\'',$_configFileContent);
// file_put_contents($_configFilePath,$_configFileContent);


// $_configExampleFilePath = $_javaPath . 'HaoConfig_example.java';
// $_configFilePath = $_javaPath . 'HaoConfig.java';
// $_configFileContent = file_get_contents($_configExampleFilePath);
// $_configFileContent = str_replace('HAOCONNECT_APIHOST         = "???.???.com"','HAOCONNECT_APIHOST         = "'.$_SERVER['HTTP_HOST'].'"',$_configFileContent);
// $_configFileContent = str_replace('HAOCONNECT_SECRET_HAX      = "secret=???"','HAOCONNECT_SECRET_HAX      = "'.SECRET_HAX_ANDROID.'"',$_configFileContent);
// file_put_contents($_configFilePath,$_configFileContent);



// $_configExampleFilePath = $_iosPath . 'HaoConfig-example.m';
// $_configFilePath = $_iosPath . 'HaoConfig.h';
// $_configFileContent = file_get_contents($_configExampleFilePath);
// $_configFileContent = str_replace('HAOCONNECT_APIHOST             =@"???.???.com"','HAOCONNECT_APIHOST             =@"'.$_SERVER['HTTP_HOST'].'"',$_configFileContent);
// $_configFileContent = str_replace('HAOCONNECT_SECRET_HAX          =@"secret=???";','HAOCONNECT_SECRET_HAX          =@"'.SECRET_HAX_IOS.'";',$_configFileContent);
// file_put_contents($_configFilePath,$_configFileContent);


// ===================== Models => Results  ====================================
$_modelsPath = AXAPI_ROOT_PATH.'/mhc/models/';
foreach(  (array)glob($_modelsPath . "*Model.php" ) as $_jobFile )
{
    $fileInfo = pathinfo($_jobFile);
    $modelName = str_replace('Model.php','',$fileInfo['basename']);

    if ($modelName=='Abstract')
    {
        continue;
    }

    $_modelList[] = $modelName;

    $keyList = Utility::getDescriptionsInModel($modelName);

    //--------------------------     php           --------------------------------
    $_resultFileDir = $_phpPath . 'results/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }
    $resultFilePath = $_resultFileDir.$modelName.'Result.php';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '<?php'
                       ."\n".'class '.$modelName.'Result extends HaoResult {'
                       ."\n".'';
        $resultFileContent .= "\n\n" . '}';
    }

    $resultFileContent = substr(trim($resultFileContent),0,strlen($resultFileContent)-3);
    foreach ($keyList as $keyStr => $description) {
        $funcName = W2String::camelCaseWithUcFirst($keyStr);
        $descLine = $description."\n";
        if (strpos($resultFileContent,'find'.$funcName.'(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)\n(\s*public function find'.$funcName.'\(\)'.')/u',"\n"."\n".'    '.str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $resultFileContent .= "\n"."\n".'    '.$descLine;
            $resultFileContent .= '    public function find'.$funcName.'()'.''
                            ."\n".'    {'
                            ."\n".'        return $this->find(\''.$keyStr.'\');'
                            ."\n".'    }';
        }
    }
    $resultFileContent .= "\n\n" . '}';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Result.php',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Result.php'."\n");
    // print($resultFileContent);
    //--------------------------     java           --------------------------------
    $_resultFileDir = $_javaPath . 'results/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }

    $resultFilePath = $_resultFileDir.$modelName.'Result.java';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '';
        $resultFileContent .= 'package com.haoxitech.HaoConnect.results;'
                        ."\n".''
                        ."\n".'import com.haoxitech.HaoConnect.HaoResult;'
                        ."\n".''
                        ."\n".'';
        $resultFileContent .= 'public class '.$modelName.'Result extends HaoResult {'
                        ."\n".'';
        $resultFileContent .= "\n\n" . '}';
    }

    $resultFileContent = substr(trim($resultFileContent),0,strlen($resultFileContent)-3);
    foreach ($keyList as $keyStr => $description) {
        $funcName = W2String::camelCaseWithUcFirst($keyStr);
        $descLine = $description."\n";
        if (strpos($resultFileContent,'find'.$funcName.'(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)\n(\s*public Object find'.$funcName.'\(\)'.')/u',"\n"."\n".'    '.str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $resultFileContent .= "\n"."\n".'    '.$descLine;
            $resultFileContent .= '    public Object find'.$funcName.'()'.''
                            ."\n".'    {'
                            ."\n".'        return find("'.$keyStr.'");'
                            ."\n".'    }';
        }
    }
    $resultFileContent .= "\n\n" . '}';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Result.java',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Result.java'."\n");
    // print($resultFileContent);

    //--------------------------     ios .m          --------------------------------
    $_resultFileDir = $_iosPath . 'results/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }
    $resultFileContent = '#import "'.$modelName.'Result.h"'
                   ."\n".''
                   ."\n".'@implementation '.$modelName.'Result'
                   ."\n".'';

    $resultFilePath = $_resultFileDir.$modelName.'Result.m';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '#import "'.$modelName.'Result.h"'
                       ."\n".''
                       ."\n".'@implementation '.$modelName.'Result'
                       ."\n".'';
    }

    $resultFileContent = rtrim(trim($resultFileContent),"\n\n".'@end ');
    foreach ($keyList as $keyStr => $description) {
        $funcName = W2String::camelCaseWithUcFirst($keyStr);
        $descLine = $description."\n";
        if (strpos($resultFileContent,'find'.$funcName."\n")!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)\n(\s*-\(id\)find'.$funcName.'\n'.')/u',"\n"."\n".'    '.str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $resultFileContent .= "\n"."\n".''.$descLine;
            $resultFileContent .= '-(id)find'.$funcName."\n".'{'
                            ."\n".'    return [self find:@"'.$keyStr.'"];'
                            ."\n".'}';
        }
    }
    $resultFileContent .= "\n\n" . '@end';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Result.m',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Result.m'."\n");
    // print($resultFileContent);

    //--------------------------     ios .h          --------------------------------
    $_resultFileDir = $_iosPath . 'results/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }

    $resultFilePath = $_resultFileDir.$modelName.'Result.h';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '#import "HaoResult.h"'
                       ."\n".''
                       ."\n".'@interface '.$modelName.'Result : HaoResult'
                       ."\n".'';
    }

    $resultFileContent = rtrim(trim($resultFileContent),"\n\n".'@end ');
    foreach ($keyList as $keyStr => $description) {
        $funcName = W2String::camelCaseWithUcFirst($keyStr);
        $descLine = $description."\n";
        if (strpos($resultFileContent,'find'.$funcName.';')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)\n(\s*-\(id\)find'.$funcName.';'.')/u',"\n"."\n".str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $resultFileContent .= "\n"."\n".''.$descLine;
            $resultFileContent .= '-(id)find'.$funcName.';';
        }
    }
    $resultFileContent .= "\n\n" . '@end';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Result.h',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Result.h'."\n");
    // print($resultFileContent);

}


//================== apitest_config => Connects  ===================================
$_apitestConfigPath = AXAPI_ROOT_PATH.'/webroot/apitest/conf/';
foreach(  (array)glob($_apitestConfigPath . "apitest_config.*.js" ) as $_jobFile )
{
    createConnectFromConfig($_jobFile);
}
$_apitestConfigPath = AXAPI_ROOT_PATH.'/webroot/apitest/';
foreach(  (array)glob($_apitestConfigPath . "apitest-config.*.js" ) as $_jobFile )
{
    createConnectFromConfig($_jobFile);
}


function createConnectFromConfig($_jobFile)
{
    global $_phpPath,$_javaPath,$_iosPath;
    global $_modelList;
    $fileInfo = pathinfo($_jobFile);
    $modelName = str_replace('.js','',$fileInfo['basename']);
    $modelName = str_replace('apitest_config.','',$modelName);
    $modelName = str_replace('apitest-config.','',$modelName);

    if ($modelName=='Abstract')
    {
        continue;
    }

    $apiList = array();
    $content = file_get_contents($_jobFile);
    $content = preg_replace('/\s+\/\/.*/','',$content);
    preg_match_all('/(apiList.push\(|apiList\[apiList\.length\] = )(\{[\s\S]*?\})(\);|;)\s+/',$content,$matches,PREG_SET_ORDER);
    foreach ($matches as $match) {
        $apiString = $match[2];
        $apiString = str_replace('\'','"',$apiString);
        try {
            $apiObj = json_decode($apiString,true);
            if (!isset($apiObj['title']))
            {
                var_export($apiString);
                print("此处代码解析失败，请联系管理员。\n\n");
            }
            else
            {
                $apiObj['action'] = trim($apiObj['action'],'/');
                if (!array_key_exists($apiObj['action'] , $apiList))
                {
                    $apiList[$apiObj['action']] = array();
                }
                $apiList[$apiObj['action']][] = $apiObj;
            }
        } catch (Exception $e) {
            var_export($apiString);
            print("\n解析错误，请检查配置js.");
        }
    }
    // var_export($apiList);print("\n");


    //--------------------------     php           --------------------------------
    $_resultFileDir = $_phpPath . 'connects/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }

    $resultFilePath = $_resultFileDir.$modelName.'Connect.php';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
            $resultFileContent = '<?php'
                           ."\n".'class '.$modelName.'Connect extends HaoConnect {'
                           ."\n".'';
        $resultFileContent .= "\n\n" . '}';
    }

    $resultFileContent = substr(trim($resultFileContent),0,strlen($resultFileContent)-3);
    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];

        $descLine = '    /**';

        foreach ($apiObjs as $apiObj) {
            $descLine .= "\n".'    * '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $descLine .= '    * @param  list $params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $descLine .= '    *                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
        }
        $descLine .= '    * @return '.(in_array($modelName,$_modelList)?$modelName:'Hao').'Result'."\n".'    */'."\n";

        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        if (strpos($resultFileContent,'request'.$funcName.'(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)(\s*public static function request'.$funcName.'\(\$params = null\)'.')/u',"\n"."\n".str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $isDoSomethingForResult = ($modelName=='User' && $funcName=='Login')
                                        || ($modelName=='User' && $funcName=='LogOut')
                                        ;
            $resultFileContent .= "\n"."\n".$descLine;
            $resultFileContent .= '    public static function request'.$funcName.'($params = null)'
                            ."\n".'    {'
                            ."\n".'        '.($isDoSomethingForResult?'$result =':'return').' '.(strpos($apiObj['action'],'http')===0?'HaoHttpClient::loadContent':'static::request').'(\''.$apiObj['action'].'\',$params,'.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').');';
            if ($isDoSomethingForResult)
            {
                if ($modelName=='User' && $funcName=='Login')
                {
                    $resultFileContent .= "\n".'        if ($result->isResultsOK())'
                                         ."\n".'        {'
                                         ."\n".'            $authInfo = $result->find(\'extraInfo>authInfo\');'
                                         ."\n".'            if (is_array($authInfo))'
                                         ."\n".'            {'
                                         ."\n".'                HaoConnect::setCurrentUserInfo($authInfo[\'Userid\'],$authInfo[\'Logintime\'],$authInfo[\'Checkcode\']);'
                                         ."\n".'            }'
                                         ."\n".'        }';
                }
                else if ($modelName=='User' && $funcName=='LogOut')
                {
                    $resultFileContent .= "\n".'        if ($result->isResultsOK())'
                                         ."\n".'        {'
                                         ."\n".'            HaoConnect::setCurrentUserInfo(\'\',\'\',\'\');'
                                         ."\n".'        }';
                }
                $resultFileContent .= "\n".'        return $result;';
            }
            $resultFileContent .= "\n".'    }';

        }
    }
    if (!file_exists($resultFilePath))
    {
        if ($modelName=='Qiniu')
        {
            $resultFileContent .= "\n".''
                                 ."\n".'    /** 传输指定路径文件到七牛 （使用接口） */'
                                 ."\n".'    public static function requestUploadFileToQiniu($filePath)'
                                 ."\n".'    {'
                                 ."\n".'        if (!file_exists($filePath))'
                                 ."\n".'        {'
                                 ."\n".'            throw new Exception(\'目标文件不存在：\'.$filePath);'
                                 ."\n".'        }'
                                 ."\n".'        $tokenParams = array(\'md5\'=>md5_file($filePath),\'filesize\'=>filesize($filePath),\'filetype\'=> pathinfo($filePath,PATHINFO_EXTENSION) );'
                                 ."\n".'        $tokenResult = static::requestGetUploadTokenForQiniu($tokenParams);'
                                 ."\n".'        if ($tokenResult->isResultsOK())'
                                 ."\n".'        {'
                                 ."\n".'            if ($tokenResult->find(\'isFileExistInQiniu\') == true)'
                                 ."\n".'            {'
                                 ."\n".'                return HaoResult::instanceModel($tokenResult->find(\'urlPreview\'),0,\'\',\'has exists in qiniu.\');'
                                 ."\n".'            }'
                                 ."\n".'            else'
                                 ."\n".'            {'
                                 ."\n".'                $params = array();'
                                 ."\n".'                $params[\'token\'] = $tokenResult->find(\'uploadToken\');'
                                 ."\n".'                if (function_exists(\'curl_file_create\'))'
                                 ."\n".'                {'
                                 ."\n".'                    $params[\'file\'] = curl_file_create($filePath);'
                                 ."\n".'                }'
                                 ."\n".'                else'
                                 ."\n".'                {'
                                 ."\n".'                    $params[\'file\'] = \'@\'.$filePath;'
                                 ."\n".'                }'
                                 ."\n".'                $qiniuContent = static::requestUploadQiniuCom($params);'
                                 ."\n".'                try {'
                                 ."\n".'                    $qiniuResult = json_decode($qiniuContent,true);'
                                 ."\n".'                    if (is_array($qiniuResult) && isset($qiniuResult[\'urlPreview\']))'
                                 ."\n".'                    {'
                                 ."\n".'                        return HaoResult::instanceModel($qiniuResult[\'urlPreview\'],0,\'\',\'upload to qiniu success.\');'
                                 ."\n".'                    }'
                                 ."\n".'                } catch (Exception $e) {'
                                 ."\n".'                }'
                                 ."\n".'                return HaoResult::instanceModel($qiniuContent,-1,\'上传文件到七牛失败，请联系管理员\',$params);'
                                 ."\n".'            }'
                                 ."\n".'        }'
                                 ."\n".'        return HaoResult::instanceModel($tokenResult,-1,\'获取Token失败，请联系管理员\',$tokenParams);'
                                 ."\n".'    }'
                                 ."\n".'    /** 上传base64编码的字符串文件到七牛 （使用接口）*/'
                                 ."\n".'    public static function requestUploadBase64ToQiniu($base64,$filetype=\'tmp\')'
                                 ."\n".'    {'
                                 ."\n".'        $tmpFilePath = \'/tmp/b64_\'.uniqid().\'.\'.$filetype;'
                                 ."\n".'        $fhandle = fopen($tmpFilePath, \'w+\');'
                                 ."\n".'        stream_filter_append($fhandle, \'convert.base64-decode\', STREAM_FILTER_WRITE);'
                                 ."\n".'        fwrite($fhandle, $base64);'
                                 ."\n".'        fclose($fhandle);'
                                 ."\n".'        return static::requestUploadFileToQiniu($tmpFilePath);'
                                 ."\n".'    }'
                                 ."\n".'';

        }
    }
    $resultFileContent .= "\n\n" . '}';
    file_put_contents($_resultFileDir.$modelName.'Connect.php',$resultFileContent);
    chmod($_resultFileDir.$modelName.'Connect.php',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Connect.php'."\n");
    // print($resultFileContent);


    //--------------------------     java           --------------------------------
    $_resultFileDir = $_javaPath . 'connects/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }
    $resultFilePath = $_resultFileDir.$modelName.'Connect.java';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = 'package com.haoxitech.HaoConnect.connects;'
                ."\n".'import com.haoxitech.HaoConnect.HaoConnect;'
                ."\n".'import com.haoxitech.HaoConnect.HaoResultHttpResponseHandler;'
                ."\n".'import com.loopj.android.http.RequestHandle;'
                ."\n".'';
        if ($modelName == 'User')
        {
            $resultFileContent .= "\n".'import com.google.gson.JsonObject;';
            $resultFileContent .= "\n".'import com.haoxitech.HaoConnect.HaoResult;';
        }
        $resultFileContent .= "\n".'import java.util.Map;'
                             ."\n".'import android.content.Context;'
                             ."\n".'public class '.$modelName.'Connect extends HaoConnect {'
                             ."\n".'';
        $resultFileContent .= "\n\n" . '}';
    }

    $resultFileContent = substr(trim($resultFileContent),0,strlen($resultFileContent)-3);
    foreach ($apiList as $action => $apiObjs)
    {
        $apiObj = $apiObjs[0];

        $descLine = '    /**';

        foreach ($apiObjs as $apiObj) {
            $descLine .= "\n".'    * '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $descLine .= '    * @param  params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $descLine .= '    *                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
            $descLine .= '    * @param  response 异步方法'."\n";
            $descLine .= '    * @param  context  请求所在的页面对象'."\n";
            $descLine .= '    */'."\n";
        }
        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        if (strpos($resultFileContent,'RequestHandle request'.$funcName.'(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)(\s*public static RequestHandle request'.$funcName.'\(Map'.')/u',"\n"."\n".str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $isDoSomethingForResult = ($modelName=='User' && $funcName=='Login')
                                        || ($modelName=='User' && $funcName=='LogOut')
                                        ;
            $resultFileContent .= "\n"."\n".$descLine;
            $resultFileContent .= '    public static RequestHandle request'.$funcName.'(Map<String, Object> params, '.($isDoSomethingForResult?'final':'').' HaoResultHttpResponseHandler response, Context context)'
                            ."\n".'    {'
                            ."\n".'        return request("'.$apiObj['action'].'", params, '.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').', ';
            if ($isDoSomethingForResult)
            {
                if ($modelName=='User' && $funcName=='Login')
                {
                    $resultFileContent .= 'new HaoResultHttpResponseHandler() {'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onSuccess(HaoResult result) {'
                                    ."\n".'                if (result.isResultsOK()) {'
                                    ."\n".'                    Object authInfo = result.find("extraInfo>authInfo");'
                                    ."\n".'                    if (authInfo instanceof JsonObject) {'
                                    ."\n".'                        HaoConnect.setCurrentUserInfo(((JsonObject) authInfo).get("Userid").getAsString(), ((JsonObject) authInfo).get("Logintime").getAsString(), ((JsonObject) authInfo).get("Checkcode").getAsString());'
                                    ."\n".'                    }'
                                    ."\n".'                }'
                                    ."\n".'                response.onSuccess(result);'
                                    ."\n".'            }'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onStart() {'
                                    ."\n".'                response.onStart();'
                                    ."\n".'            }'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onFail(HaoResult result) {'
                                    ."\n".'                response.onFail(result);'
                                    ."\n".'            }'
                                    ."\n".'        }';
                }
                else if ($modelName=='User' && $funcName=='LogOut')
                {
                    $resultFileContent .= 'new HaoResultHttpResponseHandler() {'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onSuccess(HaoResult result) {'
                                    ."\n".'                if (result.isResultsOK())'
                                    ."\n".'                {'
                                    ."\n".'                    HaoConnect.setCurrentUserInfo("","","");'
                                    ."\n".'                    response.onSuccess(result);'
                                    ."\n".'                }'
                                    ."\n".'            }'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onStart() {'
                                    ."\n".'                response.onStart();'
                                    ."\n".'            }'
                                    ."\n".'            @Override'
                                    ."\n".'            public void onFail(HaoResult result) {'
                                    ."\n".'                response.onFail(result);'
                                    ."\n".'            }'
                                    ."\n".'        }';
                }
            }
            else
            {
                $resultFileContent .= 'response';
            }
            $resultFileContent .= ', context);'
                            ."\n".'    }';
        }

    }

    $resultFileContent .= "\n\n" . '}';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Connect.java',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Connect.java'."\n");
    // print($resultFileContent);

    //--------------------------     ios .m          --------------------------------
    $_resultFileDir = $_iosPath . 'connects/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }
    $resultFilePath = $_resultFileDir.$modelName.'Connect.m';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '#import "'.$modelName.'Connect.h"'
                        ."\n".''
                        ."\n".'@implementation '.$modelName.'Connect'
                        ."\n".'';
    }
    $resultFileContent = rtrim(trim($resultFileContent),"\n\n".'@end');

    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];

        $descLine = '/**';

        foreach ($apiObjs as $apiObj) {
            $descLine .= "\n".'* '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $descLine .= '* @param  NSMutableDictionary * params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $descLine .= '*                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
        }
        $descLine .= '* @param completionBlock(HaoResult *result)   请求成功'."\n";
        $descLine .= '* @param      errorBlock(HaoResult *error)         请求失败'."\n";
        $descLine .= '*/'."\n";
        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        if (strpos($resultFileContent,'request'.$funcName.':(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)(\s*.*?\(MKNetworkOperation.*?request'.$funcName.'\:\('.')/u',"\n"."\n".str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $isDoSomethingForResult = ($modelName=='User' && ($funcName=='Login' || $funcName=='UnionLogin'))
                                        || ($modelName=='User' && $funcName=='LogOut')
                                        ;

            $resultFileContent .= "\n"."\n".$descLine;
            $resultFileContent .= '+ (MKNetworkOperation *)request'.$funcName.':(NSMutableDictionary *)params'."\n"
                                                    .str_pad('',31+strlen($funcName)-12,' ',STR_PAD_LEFT)
                                                    .'OnCompletion:(void (^)(HaoResult *result))completionBlock'."\n"
                                                    .str_pad('',31+strlen($funcName)-7,' ',STR_PAD_LEFT)
                                                         .'onError:(void (^)( HaoResult *error))errorBlock'
                            ."\n".'{'
                            ."\n".''
                            ."\n".'    return [self request:@"'.$apiObj['action'].'" params:params httpMethod:'.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').' onCompletion:^(HaoResult *result) {';
            if ($isDoSomethingForResult)
            {
                if ($modelName=='User' && ($funcName=='Login' || $funcName=='UnionLogin'))
                {
                    $resultFileContent .= "\n".'        if ([result isResultsOK]) {'
                                         ."\n".'            id extraInfo = [result find:@"extraInfo>authInfo"];'
                                         ."\n".'            if ([extraInfo isKindOfClass:[NSDictionary class]]) {'
                                         ."\n".'                NSString * loginTime = [extraInfo objectForKey:@"Logintime"];'
                                         ."\n".'                NSString * userid    = [extraInfo objectForKey:@"Userid"];'
                                         ."\n".'                NSString * checkCode = [extraInfo objectForKey:@"Checkcode"];'
                                         ."\n".'                [self setCurrentUserInfo:userid :loginTime :checkCode];'
                                         ."\n".'            }'
                                         ."\n".'        }';
                }
                else if ($modelName=='User' && $funcName=='LogOut')
                {
                    $resultFileContent .= "\n".'        if ([result isResultsOK]) {//注销成功'
                                         ."\n".'            [self setCurrentUserInfo:@"" :@"" :@""];'
                                         ."\n".'        }';
                }
            }
            $resultFileContent .= "\n".'        completionBlock(result);'
                                 ."\n".'    } onError:^(HaoResult *error) {'
                                 ."\n".'        errorBlock(error);'
                                 ."\n".'    }];'
                                 ."\n".'}';
        }
    }

    $resultFileContent .= "\n\n" . '@end';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Connect.m',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Connect.m'."\n");
    // print($resultFileContent);

    //--------------------------     ios .h          --------------------------------
    $_resultFileDir = $_iosPath . 'connects/';
    if(!W2File::directory($_resultFileDir))
    {
        print('不存在目标文件夹：'.$_resultFileDir);
        exit;
    }

    $resultFilePath = $_resultFileDir.$modelName.'Connect.h';
    if (file_exists($resultFilePath))
    {
        $resultFileContent = file_get_contents($resultFilePath);
    }
    else
    {
        $resultFileContent = '#import "HaoConnect.h"'
                       ."\n".''
                       ."\n".'@interface '.$modelName.'Connect : HaoConnect'
                       ."\n".'';
    }
    $resultFileContent = rtrim(trim($resultFileContent),"\n\n".'@end');

    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];

        if (!isset($apiObj['action']))
        {
            $resultFileContent .= "\n"."\n".'/** 此处有接口代码丢失，请联系管理员。 */'."\n";
            continue;
        }

        $descLine = '';
        foreach ($apiObjs as $apiObj) {
            $descLine .= '/**     '.$apiObj['title']."*/\n";
        }
        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        if (strpos($resultFileContent,'request'.$funcName.':(')!==false)
        {
            $resultFileContent = preg_replace('/((\n *\/\*.*|\n *\*.*|\n+)+)(\s*.*?\(MKNetworkOperation.*?request'.$funcName.'\:\('.')/u',"\n"."\n".str_replace('$','\$',$descLine).'$3',$resultFileContent);
        }
        else
        {
            $resultFileContent .= "\n"."\n".$descLine;
            $resultFileContent .= '+ (MKNetworkOperation *)request'.$funcName.':(NSMutableDictionary *)params'."\n"
                                                    .str_pad('',18+strlen($funcName)-12,' ',STR_PAD_LEFT)
                                                    .'OnCompletion:(void (^)(HaoResult *result))completionBlock'."\n"
                                                    .str_pad('',18+strlen($funcName)-7,' ',STR_PAD_LEFT)
                                                         .'onError:(void (^)( HaoResult * error))errorBlock;';
            $resultFileContent .= "\n";
        }
    }

    $resultFileContent .= "\n\n" . '@end';
    file_put_contents($resultFilePath,$resultFileContent);
    chmod($_resultFileDir.$modelName.'Connect.h',0664);
    print('已更新：'.$_resultFileDir.$modelName.'Connect.h'."\n");
    // print($resultFileContent);

}



print("done\n");
