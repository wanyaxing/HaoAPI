<?php
/**
 * 文件上传相关接口
 * @package Controller
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class QiniuController{
	/**
	 * 查询对应的文件是否在七牛存在，并返回对应信息和上传token
	 * 可以判断是否已存在，也可用uploadToken直接上传文件到七牛服务器
	 *
	 */
	public static function actionGetUploadTokenForQiniu()
	{
        $unsetKey = W2HttpRequest::getUnsetRequest('md5,filesize,filetype', $pAllowBlank = false);
        if ( $unsetKey  !== null)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'部分数据未提交，请检查。',array('errorContent'=>'部分参数未提交数据: '.$unsetKey));
        }
		$md5 = W2HttpRequest::getRequestString('md5');
		$filesize = W2HttpRequest::getRequestString('filesize');
		$filetype = W2HttpRequest::getRequestString('filetype');

        sleep(3);

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',W2Qiniu::getUploadTokenForQiniuUploadWithMd5AndFileSize($md5,$filesize,$filetype));
	}

	/**
	 * 表单上传单个文件，请使用name="file"字段上传
	 * <input type="file" accept="image/gif, image/jpeg, image/png, image/jpg" name="file" />
	 * @return string 文件在七牛的预览地址
	 */
	public static function actionUploadSingleFile()
	{
		$fileSizeMax = 5*1024*1024;
		$fileTypeAllowed = array('jpg','png','jpeg','gif','doc','docx','pdf');

        if (!array_key_exists('file', $_FILES) || count($_FILES['file'])==0)
        {
            Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'没有发现上传的文件，请检查。');
        }

        $upload_file = $_FILES['file'];

        $previewUrls = array();
        if ($upload_file["size"]>0)
        {
            $file_tmp_path = $upload_file["tmp_name"];
            $file_type = pathinfo($upload_file["name"],PATHINFO_EXTENSION);
            if (isset($fileTypeAllowed) && !in_array(strtolower($file_type), $fileTypeAllowed))
            {
                Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'提示：不支持上传该'.$file_type.'类型的文件，支持:'.implode('、',$fileTypeAllowed));
            }

            if ($upload_file["size"]> $fileSizeMax )
            {
                Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'错误，文件大小不可超过'.$fileSizeMax.'字节。');
            }

            $file_tmp_path_type = $file_tmp_path.'.'.$file_type;
            rename($file_tmp_path,$file_tmp_path_type);
            $previewUrls[] = W2Qiniu::uploadAndReturnQiniuPreviewUrl($file_tmp_path_type);
        }
        if (count($previewUrls)==0)
        {
	        return Utility::getArrayForResults(RUNTIME_CODE_ERROR_UNKNOWN,'上传失败，请重试。');
        }
        else
        {
	        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',$previewUrls[0]);
        }
	}

	/**
	 * 一次选中多个文件进行上传，请使用name="files[]" multiple="multiple"上传
	 * <input type="file" accept="image/gif, image/jpeg, image/png, image/jpg" name="files[]" multiple="multiple">
	 * @return string[] 多个来自七牛的预览网址
	 */
	public static function actionUploadMultipleFiles()
	{
		$fileSizeMax = 5*1024*1024;
		$fileTypeAllowed = array('jpg','png','jpeg','gif','doc','docx','pdf');

        if (!array_key_exists('files', $_FILES) || count($_FILES['files'])==0)
        {
            return Utility::getArrayForResults(RUNTIME_CODE_ERROR_UNKNOWN,'没有发现上传的文件，请检查。');
        }
        $upload_file = $_FILES['files'];
        $previewUrls = array();
        for ($i=0; $i < count($upload_file['name']) ; $i++) {
            if ($upload_file["size"][$i]>0)
            {
                $file_tmp_path = $upload_file["tmp_name"][$i];
	            $file_type = pathinfo($upload_file["name"][$i],PATHINFO_EXTENSION);
	            if (isset($fileTypeAllowed) && !in_array(strtolower($file_type), $fileTypeAllowed))
	            {
	                Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'提示：不支持上传该'.$file_type.'类型的文件，支持:'.implode('、',$fileTypeAllowed));
	            }

	            if ($upload_file["size"]> $fileSizeMax )
	            {
	                Utility::getArrayForResults(RUNTIME_CODE_ERROR_PARAM,'错误，文件大小不可超过'.$fileSizeMax.'字节。');
	            }
                if ($upload_file["size"][$i]>5*1024*1024)
                {
                    return Utility::getArrayForResults(RUNTIME_CODE_ERROR_UNKNOWN,'错误，文件大小不可超过5MB。');
                }

	            $file_tmp_path_type = $file_tmp_path.'.'.$file_type;
	            rename($file_tmp_path,$file_tmp_path_type);
	            $previewUrls[] = W2Qiniu::uploadAndReturnQiniuPreviewUrl($file_tmp_path_type);
            }
        }
        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',$previewUrls);
	}

    /** 直接抓取网络资源到七牛（同步，所以不要用来抓取太大的文件哦） */
    public static function actionFetchUrlToQiniu()
    {

        $previewUrl = W2Qiniu::fetchUrlToQiniu(W2HttpRequest::getRequestString('url',false),W2HttpRequest::getRequestString('filename'));
        if (!is_string($previewUrl))
        {
            return HaoResult::init(ERROR_CODE::$UNKNOWN_ERROR,$previewUrl);
        }
        return HaoResult::init(ERROR_CODE::$OK,$previewUrl);
    }
    public static function actionTest()
    {
        $result = W2Qiniu::fetchUrlToQiniu('http://mobile-bailihui.haoxitech.com/images/design/non_payment.png');
        var_dump($result);
        exit;
    }

    public static function actionMkzip()
    {
        $fileList = array(
                'dir/1.png'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/bb39671987ff5c5cf0ec849446e6cc37_279255.png'
                ,'dir2/dir3/dir4/2.jpg' => 'http://7u2sdg.com1.z0.glb.clouddn.com/48de418b961b383acbaae9bc935c697f_267289.jpg'
                ,'32.jpg'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/62043631093014eee492a0b501528486_262278.jpg'
                ,'42.jpg'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/d4d1e24d9b4bb414f6f55d75bda93a1a_465032.jpg'
                ,'54.jpg'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/d4c9aa5e087ca11c6a9fc108a8a027ff_374055.jpg'
                ,'63.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'63.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'63.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'63.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6311111111.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6322222222.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6333333333.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6344444444.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6355555555.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6366666666.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6377777777.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6388888888.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'6399999999.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'631010101010101010.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'631111111111111111.JPG'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/b400520dc84ce2fe6946b4f39aaebdaa_203708.JPG'
                ,'7.1212121212121212jpg'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/c80a4cd1ed5bf27fd81336b8e3b4a706_56429.jpg'
                ,'8.jpg'             => 'http://7u2sdg.com1.z0.glb.clouddn.com/04f10ef3f61ed8dae42ae8a19cf67653_319095.jpg'
            );
        $result = W2Qiniu::getZipInfo($fileList);
        var_export( $result );
        exit;
    }

    public static function actionTestSomething()
    {
        W2Cache::$CACHE_HOST = '127.0.0.1';
        W2Cache::$CACHE_PORT = '6379';
        W2Cache::$CACHE_INDEX = 1;

        $pKey = 'xxx';
        $HTTP_IF_MODIFIED_SINCE='Wed, 23 Sep 2015 14:18:02 GMT';$HTTP_IF_NONE_MATCH='a2aa9d091dfd868c03904194ea706dff';

        if (W2Cache::isCacheCanBeUsed($pKey) && !W2Cache::isModified($pKey,$HTTP_IF_MODIFIED_SINCE,$HTTP_IF_NONE_MATCH))
        {
            W2Cache::setCache('xxx','hahaha2');
            return Utility::getArrayForResults(RUNTIME_CODE_OK,'明明没有变化嘛，请直接304吧',array($HTTP_IF_MODIFIED_SINCE,$HTTP_IF_NONE_MATCH));
        }
        W2Cache::setCache('xxx','hahaha3');
        W2Cache::isModified($pKey,$HTTP_IF_MODIFIED_SINCE,$HTTP_IF_NONE_MATCH);

        return Utility::getArrayForResults(RUNTIME_CODE_OK,'',array($HTTP_IF_MODIFIED_SINCE,$HTTP_IF_NONE_MATCH,W2Cache::getCache('xxx',300)));
    }


    /** 根据七牛地址获得下载授权 */
    public static function actionGetPrivateUrl()
    {
        $url = W2HttpRequest::getRequestURL('url');
        $privateUrl = W2Qiniu::getPrivateUrl($url);
        return HaoResult::init(ERROR_CODE::$OK,$privateUrl);
    }

    /** 根据文件名获得下载授权 */
    public static function actionGetPrivateUrlOfKey()
    {
        $key = W2HttpRequest::getRequestString('key');
        if (W2String::startsWith($key,'http') && strpos($key,W2QINIU_QINIU_DOMAIN)==false)
        {//非本项目的七牛地址，直接用
            return HaoResult::init(ERROR_CODE::$OK,$key);
        }
        $key = preg_replace('/^http[^\?]+\//','',$key);
        if (strpos($key,'?')!==false)
        {
            list($key,$param) = explode('?', $key);
        }
        $baseUrl = W2Qiniu::getBaseUrl($key);
        if (isset($param))
        {
            $baseUrl .= '?'.$param;
        }
        $privateUrl = W2Qiniu::getPrivateUrl($baseUrl);
        return HaoResult::init(ERROR_CODE::$OK,$privateUrl);
    }
}
