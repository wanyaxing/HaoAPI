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

W2File::deldir($_phpPath . 'results/');
W2File::deldir($_javaPath . 'results/');
W2File::deldir($_iosPath . 'results/');
W2File::deldir($_phpPath . 'connects/');
W2File::deldir($_javaPath . 'connects/');
W2File::deldir($_iosPath . 'connects/');
// chmod($_phpPath . 'results/',0664);
// chmod($_javaPath . 'results/',0664);
// chmod($_iosPath . 'results/',0664);
// chmod($_phpPath . 'connects/',0664);
// chmod($_javaPath . 'connects/',0664);
// chmod($_iosPath . 'connects/',0664);

umask(0);

// ===================== config update  ====================================
$_configExampleFilePath = $_phpPath . 'HaoConfig-example.php';
$_configFilePath = $_phpPath . 'HaoConfig.php';
$_configFileContent = file_get_contents($_configExampleFilePath);
$_configFileContent = str_replace('$apiHost                          = \'???.???.com\'','$apiHost                          = \''.$_SERVER['HTTP_HOST'].'\'',$_configFileContent);
$_configFileContent = str_replace('$SECRET_HAX_CONNECT               = \'?????????????\'','$SECRET_HAX_CONNECT               = \''.SECRET_HAX_PC.'\'',$_configFileContent);
file_put_contents($_configFilePath,$_configFileContent);


$_configExampleFilePath = $_javaPath . 'HaoConfig_example.java';
$_configFilePath = $_javaPath . 'HaoConfig.java';
$_configFileContent = file_get_contents($_configExampleFilePath);
$_configFileContent = str_replace('HAOCONNECT_APIHOST         = "???.???.com"','HAOCONNECT_APIHOST         = "'.$_SERVER['HTTP_HOST'].'"',$_configFileContent);
$_configFileContent = str_replace('HAOCONNECT_SECRET_HAX      = "secret=???"','HAOCONNECT_SECRET_HAX      = "'.SECRET_HAX_ANDROID.'"',$_configFileContent);
file_put_contents($_configFilePath,$_configFileContent);



$_configExampleFilePath = $_iosPath . 'HaoConfig-example.h';
$_configFilePath = $_iosPath . 'HaoConfig.h';
$_configFileContent = file_get_contents($_configExampleFilePath);
$_configFileContent = str_replace('HAOCONNECT_APIHOST             =@"???.???.com"','HAOCONNECT_APIHOST             =@"'.$_SERVER['HTTP_HOST'].'"',$_configFileContent);
$_configFileContent = str_replace('HAOCONNECT_SECRET_HAX          =@"secret=???";','HAOCONNECT_SECRET_HAX          =@"'.SECRET_HAX_IOS.'";',$_configFileContent);
file_put_contents($_configFilePath,$_configFileContent);


// ===================== Models => Results  ====================================
$_modelsPath = AXAPI_ROOT_PATH.'/mhc/models/';
foreach(  (array)glob($_modelsPath . "*Model.php" ) as $_jobFile )/* Match md5_2. */
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
    $_resultFilePath = $_phpPath . 'results/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '<?php
class '.$modelName.'Result extends HaoResult {
';
    foreach ($keyList as $keyStr => $description) {
        $resultFileContent .= '    '.$description."\n";
        $resultFileContent .= '    public function find'.W2String::camelCaseWithUcFirst($keyStr).'()
    {
        return $this->find(\''.$keyStr.'\');
    }

';
    }
    $resultFileContent .= '}';
    file_put_contents($_resultFilePath.$modelName.'Result.php',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Result.php',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Result.php'."\n");
    // print($resultFileContent);

    //--------------------------     java           --------------------------------
    $_resultFilePath = $_javaPath . 'results/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '';
        $resultFileContent .= 'package com.haoxitech.HaoConnect.results;

import com.haoxitech.HaoConnect.HaoResult;

';
    $resultFileContent .= 'public class '.$modelName.'Result extends HaoResult {
';
    foreach ($keyList as $keyStr => $description) {
        $resultFileContent .= '    '.$description."\n";
        $resultFileContent .= '    public Object find'.W2String::camelCaseWithUcFirst($keyStr).'()
    {
        return find("'.$keyStr.'");
    }

';
    }
    $resultFileContent .= '}';
    file_put_contents($_resultFilePath.$modelName.'Result.java',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Result.java',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Result.java'."\n");
    // print($resultFileContent);

    //--------------------------     ios .m          --------------------------------
    $_resultFilePath = $_iosPath . 'results/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '#import "'.$modelName.'Result.h"

@implementation '.$modelName.'Result
';
    foreach ($keyList as $keyStr => $description) {
        $resultFileContent .= ''.$description."\n";
        $resultFileContent .= '-(id)find'.W2String::camelCaseWithUcFirst($keyStr).'
{
    return [self find:@"'.$keyStr.'"];
}

';
    }
    $resultFileContent .= '@end';
    file_put_contents($_resultFilePath.$modelName.'Result.m',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Result.m',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Result.m'."\n");
    // print($resultFileContent);

    //--------------------------     ios .h          --------------------------------
    $_resultFilePath = $_iosPath . 'results/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '#import "HaoResult.h"

@interface '.$modelName.'Result : HaoResult
';
    foreach ($keyList as $keyStr => $description) {
        $resultFileContent .= ''.$description."\n";
        $resultFileContent .= '-(id)find'.W2String::camelCaseWithUcFirst($keyStr).';

';
    }
    $resultFileContent .= '@end';
    file_put_contents($_resultFilePath.$modelName.'Result.h',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Result.h',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Result.h'."\n");
    // print($resultFileContent);

}


//================== apitest_config => Connects  ===================================
$_apitestConfigPath = AXAPI_ROOT_PATH.'/webroot/apitest/conf/';
foreach(  (array)glob($_apitestConfigPath . "apitest_config.*.js" ) as $_jobFile )/* Match md5_2. */
{
    createConnectFromConfig($_jobFile);
}
$_apitestConfigPath = AXAPI_ROOT_PATH.'/webroot/apitest/';
foreach(  (array)glob($_apitestConfigPath . "apitest-config.*.js" ) as $_jobFile )/* Match md5_2. */
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
    $_resultFilePath = $_phpPath . 'connects/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '<?php
class '.$modelName.'Connect extends HaoConnect {
';
    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];
        foreach ($apiObjs as $apiObj) {
            $resultFileContent .= "\n"."\n".'    /**'."\n".'    * '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $resultFileContent .= '    * @param  list $params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $resultFileContent .= '    *                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
        }
        $resultFileContent .= '    * @return '.(in_array($modelName,$_modelList)?$modelName:'Hao').'Result'."\n".'    */'."\n";

        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));
        $isDoSomethingForResult = ($modelName=='User' && $funcName=='Login')
                                    || ($modelName=='User' && $funcName=='LogOut')
                                    ;
        $resultFileContent .= '    public static function request'.$funcName.'($params = null)
    {
        '.($isDoSomethingForResult?'$result =':'return').' '.(strpos($apiObj['action'],'http')===0?'HaoHttpClient::loadContent':'static::request').'(\''.$apiObj['action'].'\',$params,'.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').');';
        if ($isDoSomethingForResult)
        {
            if ($modelName=='User' && $funcName=='Login')
            {
                $resultFileContent .= "\n".'        if ($result->isResultsOK())
        {
            $authInfo = $result->find(\'extraInfo>authInfo\');
            if (is_array($authInfo))
            {
                HaoConnect::setCurrentUserInfo($authInfo[\'Userid\'],$authInfo[\'Logintime\'],$authInfo[\'Checkcode\']);
            }
        }';
            }
            else if ($modelName=='User' && $funcName=='LogOut')
            {
                $resultFileContent .= "\n".'        if ($result->isResultsOK())
        {
            HaoConnect::setCurrentUserInfo(\'\',\'\',\'\');
        }';
            }
            $resultFileContent .= "\n".'        return $result;';
        }
        $resultFileContent .= "\n".'    }

';
    }
    if ($modelName=='Qiniu')
    {
        $resultFileContent .= "\n".'
    /** 传输指定路径文件到七牛 （使用接口） */
    public static function requestUploadFileToQiniu($filePath)
    {
        if (!file_exists($filePath))
        {
            throw new Exception(\'目标文件不存在：\'.$filePath);
        }
        $tokenParams = array(\'md5\'=>md5_file($filePath),\'filesize\'=>filesize($filePath),\'filetype\'=> pathinfo($filePath,PATHINFO_EXTENSION) );
        $tokenResult = static::requestGetUploadTokenForQiniu($tokenParams);
        if ($tokenResult->isResultsOK())
        {
            if ($tokenResult->find(\'isFileExistInQiniu\') == true)
            {
                return HaoResult::instanceModel($tokenResult->find(\'urlPreview\'),0,\'\',\'has exists in qiniu.\');
            }
            else
            {
                $params = array();
                $params[\'token\'] = $tokenResult->find(\'uploadToken\');
                if (function_exists(\'curl_file_create\'))
                {
                    $params[\'file\'] = curl_file_create($filePath);
                }
                else
                {
                    $params[\'file\'] = \'@\'.$filePath;
                }
                $qiniuContent = static::requestUploadQiniuCom($params);
                try {
                    $qiniuResult = json_decode($qiniuContent,true);
                    if (is_array($qiniuResult) && isset($qiniuResult[\'urlPreview\']))
                    {
                        return HaoResult::instanceModel($qiniuResult[\'urlPreview\'],0,\'\',\'upload to qiniu success.\');
                    }
                } catch (Exception $e) {
                }
                return HaoResult::instanceModel($qiniuContent,-1,\'上传文件到七牛失败，请联系管理员\',$params);
            }
        }
        return HaoResult::instanceModel($tokenResult,-1,\'获取Token失败，请联系管理员\',$tokenParams);
    }

    /** 上传base64编码的字符串文件到七牛 （使用接口）*/
    public static function requestUploadBase64ToQiniu($base64,$filetype=\'tmp\')
    {
        $tmpFilePath = \'/tmp/b64_\'.uniqid().\'.\'.$filetype;

        $fhandle = fopen($tmpFilePath, \'w+\');
        stream_filter_append($fhandle, \'convert.base64-decode\', STREAM_FILTER_WRITE);
        fwrite($fhandle, $base64);
        fclose($fhandle);

        return static::requestUploadFileToQiniu($tmpFilePath);
    }

';

    }
    $resultFileContent .= '}';
    file_put_contents($_resultFilePath.$modelName.'Connect.php',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Connect.php',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Connect.php'."\n");
    // print($resultFileContent);


    //--------------------------     java           --------------------------------
    $_resultFilePath = $_javaPath . 'connects/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '';
    $resultFileContent .= 'package com.haoxitech.HaoConnect.connects;
import com.haoxitech.HaoConnect.HaoConnect;
import com.haoxitech.HaoConnect.HaoResultHttpResponseHandler;
import com.loopj.android.http.RequestHandle;
';
    if ($modelName == 'User')
    {
        $resultFileContent .= "\n".'import com.google.gson.JsonObject;';
        $resultFileContent .= "\n".'import com.haoxitech.HaoConnect.HaoResult;';
    }
    $resultFileContent .= "\n".'import java.util.Map;
import android.content.Context;

public class '.$modelName.'Connect extends HaoConnect {
';
    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];
        foreach ($apiObjs as $apiObj) {
            $resultFileContent .= "\n"."\n".'    /**'."\n".'    * '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $resultFileContent .= '    * @param  params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $resultFileContent .= '    *                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
            $resultFileContent .= '    * @param  response 异步方法'."\n";
            $resultFileContent .= '    * @param  context  请求所在的页面对象'."\n";
            $resultFileContent .= '    */'."\n";
        }
        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        $isDoSomethingForResult = ($modelName=='User' && $funcName=='Login')
                                    || ($modelName=='User' && $funcName=='LogOut')
                                    ;
        $resultFileContent .= '    public static RequestHandle request'.$funcName.'(Map<String, Object> params, '.($isDoSomethingForResult?'final':'').' HaoResultHttpResponseHandler response, Context context)
    {
        return request("'.$apiObj['action'].'", params, '.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').', ';
        if ($isDoSomethingForResult)
        {
            if ($modelName=='User' && $funcName=='Login')
            {
                $resultFileContent .= 'new HaoResultHttpResponseHandler() {
            @Override
            public void onSuccess(HaoResult result) {
                if (result.isResultsOK()) {
                    Object authInfo = result.find("extraInfo>authInfo");
                    if (authInfo instanceof JsonObject) {
                        HaoConnect.setCurrentUserInfo(((JsonObject) authInfo).get("Userid").getAsString(), ((JsonObject) authInfo).get("Logintime").getAsString(), ((JsonObject) authInfo).get("Checkcode").getAsString());
                    }
                }
                response.onSuccess(result);
            }

            @Override
            public void onStart() {
                response.onStart();
            }

            @Override
            public void onFail(HaoResult result) {
                response.onFail(result);
            }
        }';
            }
            else if ($modelName=='User' && $funcName=='LogOut')
            {
                $resultFileContent .= 'new HaoResultHttpResponseHandler() {
            @Override
            public void onSuccess(HaoResult result) {
                if (result.isResultsOK())
                {
                    HaoConnect.setCurrentUserInfo("","","");
                    response.onSuccess(result);
                }
            }

            @Override
            public void onStart() {
                response.onStart();
            }

            @Override
            public void onFail(HaoResult result) {
                response.onFail(result);
            }
        }';
            }
        }
        else
        {
            $resultFileContent .= 'response';
        }
            $resultFileContent .= ', context);
    }

';
    }

    $resultFileContent .= '}';
    file_put_contents($_resultFilePath.$modelName.'Connect.java',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Connect.java',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Connect.java'."\n");
    // print($resultFileContent);

    //--------------------------     ios .m          --------------------------------
    $_resultFilePath = $_iosPath . 'connects/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }
    $resultFileContent = '#import "'.$modelName.'Connect.h"

@implementation '.$modelName.'Connect
';
    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];
        foreach ($apiObjs as $apiObj) {
            $resultFileContent .= "\n"."\n".'/**'."\n".'* '.$apiObj['title']."\n";
            if (is_array($apiObj['request']))
            {
                $resultFileContent .= '* @param  NSMutableDictionary * params  参数'."\n";
                foreach ($apiObj['request'] as $request) {
                     $resultFileContent .= '*                        '
                                            . str_pad($request['key'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['type'],20,' ',STR_PAD_RIGHT)
                                            . str_pad($request['required']?'*':'',10,' ',STR_PAD_RIGHT)
                                            . $request['title']
                                            . "\n"
                                         ;
                }
            }
            $resultFileContent .= '* @param completionBlock(HaoResult *result)   请求成功'."\n";
            $resultFileContent .= '* @param      errorBlock(HaoResult *error)         请求失败'."\n";
            $resultFileContent .= '*/'."\n";
        }
        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));

        $isDoSomethingForResult = ($modelName=='User' && $funcName=='Login')
                                    || ($modelName=='User' && $funcName=='LogOut')
                                    ;


        $resultFileContent .= '+ (MKNetworkOperation *)request'.$funcName.':(NSMutableDictionary *)params'."\n"
                                                .str_pad('',18+strlen($funcName)-12,' ',STR_PAD_LEFT)
                                                .'OnCompletion:(void (^)(HaoResult *result))completionBlock'."\n"
                                                .str_pad('',18+strlen($funcName)-7,' ',STR_PAD_LEFT)
                                                     .'onError:(void (^)( HaoResult *error))errorBlock
{

    return [self request:@"'.$apiObj['action'].'" params:params httpMethod:'.(strtoupper($apiObj['method'])=='POST'?'METHOD_POST':'METHOD_GET').' onCompletion:^(HaoResult *result) {';
        if ($isDoSomethingForResult)
        {
            if ($modelName=='User' && $funcName=='Login')
            {
                $resultFileContent .= "\n".'        if ([result isResultsOK]) {
            id extraInfo = [result find:@"extraInfo>authInfo"];
            if ([extraInfo isKindOfClass:[NSDictionary class]]) {
                NSString * loginTime = [extraInfo objectForKey:@"Logintime"];
                NSString * userid    = [extraInfo objectForKey:@"Userid"];
                NSString * checkCode = [extraInfo objectForKey:@"Checkcode"];
                [self setCurrentUserInfo:userid :loginTime :checkCode];
            }
        }';
            }
            else if ($modelName=='User' && $funcName=='LogOut')
            {
                $resultFileContent .= "\n".'        if ([result isResultsOK]) {//注销成功
            [self setCurrentUserInfo:@"" :@"" :@""];
        }';
            }
        }
        $resultFileContent .= "\n".'        completionBlock(result);
    } onError:^(HaoResult *error) {
        errorBlock(error);
    }];

}

';
    }

    $resultFileContent .= '@end';
    file_put_contents($_resultFilePath.$modelName.'Connect.m',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Connect.m',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Connect.m'."\n");
    // print($resultFileContent);

    //--------------------------     ios .h          --------------------------------
    $_resultFilePath = $_iosPath . 'connects/';
    if(!W2File::directory($_resultFilePath))
    {
        print('不存在目标文件夹：'.$_resultFilePath);
        exit;
    }

    $resultFileContent = '#import "HaoConnect.h"

@interface '.$modelName.'Connect : HaoConnect
';

    foreach ($apiList as $action => $apiObjs) {
        $apiObj = $apiObjs[0];

        if (!isset($apiObj['action']))
        {
            $resultFileContent .= "\n"."\n".'/** 此处有接口代码丢失，请联系管理员。 */'."\n";
            continue;
        }

        foreach ($apiObjs as $apiObj) {
            $resultFileContent .= "\n".'/**     '.$apiObj['title']."*/\n";
        }

        $funcName = W2String::camelCaseWithUcFirst(preg_replace('/.*?\//','',$apiObj['action']));
        $resultFileContent .= '+ (MKNetworkOperation *)request'.$funcName.':(NSMutableDictionary *)params'."\n"
                                                .str_pad('',18+strlen($funcName)-12,' ',STR_PAD_LEFT)
                                                .'OnCompletion:(void (^)(HaoResult *result))completionBlock'."\n"
                                                .str_pad('',18+strlen($funcName)-7,' ',STR_PAD_LEFT)
                                                     .'onError:(void (^)( HaoResult * error))errorBlock;';
        $resultFileContent .= "\n";
    }

    $resultFileContent .= '@end';
    file_put_contents($_resultFilePath.$modelName.'Connect.h',$resultFileContent);
    chmod($_resultFilePath.$modelName.'Connect.h',0664);
    print('已更新：'.$_resultFilePath.$modelName.'Connect.h'."\n");
    // print($resultFileContent);

}



print("done\n");
