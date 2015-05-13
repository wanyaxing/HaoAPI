<?php
/**
 * 七牛处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
require_once(__dir__.'/../qiniu/rs.php');
require_once(__dir__.'/../qiniu/io.php');

class W2Qiniu {
	/**
	 * 存储空间对应域名  //todo
	 * @var string
	 */
	public static $bucket = W2Config::$Qiniu_bucket;

	/**
	 * 存储空间对应域名（空间名和域名不一样，但是是一对，以七牛那边设置为准）  //todo
	 * @var string
	 */
	public static $domain = W2Config::$Qiniu_domain;

	/**
	 * 登录密钥 //todo
	 * @var string
	 */
	public static $accessKey = W2Config::$Qiniu_accessKey;

	/**
	 * 登录密钥校验 //todo
	 * @var string
	 */
	public static $secretKey = W2Config::$Qiniu_secretKey;


	/**
	 * 上传文件到七牛前需要先申请上传用的token
	 * @param  string $key    文件名（空间内唯一哦）
	 * @return array         token相关的数组，其中['uploadToken']就是上传用token
	 */
    public static function getUploadTokenForQiniuUpload($key)
    {
		$data = array();
		$data['bucket'] = W2Qiniu::$bucket;
		$data['Expires'] = 3600;
		$data['deadline'] = time() + $data['Expires'];
		$data['deadTime'] = date('Y-m-d H:i:s',$data['deadline']);
		$data['SaveKey'] = null;

		$scope = W2Qiniu::$bucket;
		if ($key !='')
		{
			$scope = W2Qiniu::$bucket .':'.$key;
			$data['SaveKey'] = $key;
			$data['urlPreview'] = 'http://'.(W2Qiniu::$domain).'/'. $key;
		}
		$data['ReturnBody'] = '{
    "urlDownload": "'.$data['urlPreview'].'",
    "urlPreview": "'.$data['urlPreview'].'",
    "name": $(fname),
    "size": $(fsize),
    "type": $(mimeType),
    "hash": $(etag),
    "w": $(imageInfo.width),
    "h": $(imageInfo.height),
    "color": $(exif.ColorSpace.val)
}';
		Qiniu_SetKeys(W2Qiniu::$accessKey, W2Qiniu::$secretKey);
		$putPolicy = new Qiniu_RS_PutPolicy(W2Qiniu::$bucket);
		$putPolicy ->Expires = $data['Expires'];
		$putPolicy ->SaveKey = $data['SaveKey'];
		$putPolicy ->ReturnBody = $data['ReturnBody'];
		$data['uploadToken'] = $putPolicy->Token(null);

		$data['uploadServer'] = 'http://upload.qiniu.com';

		$data['fileInQiniu'] = W2Qiniu::getFileInfoFromQiniu($key);
		$data['isFileExistInQiniu'] = array_key_exists('fsize', $data['fileInQiniu']);

		return $data;
    }

	/**
	 *  组装文件名并取得上传用的token
	 * @param  string $md5      文件md5
	 * @param  int    $filesize 文件大小
	 * @param  string $filetype 文件后缀
	 * @return [type]           [description]
	 */
	public static function getUploadTokenForQiniuUploadWithMd5AndFileSize($md5,$filesize,$filetype)
	{
		$key = $md5.'_'.$filesize.'.'.$filetype;

		return W2Qiniu::getUploadTokenForQiniuUpload($key);

	}

	/**
	 *  根据文件组装其用于七牛的文件名并取得其上传用token
	 * @param  [type] $filePath [description]
	 * @return [type]           [description]
	 */
	public static function getUploadTokenForQiniuUploadWithFile($filePath)
	{
		if (!file_exists($filePath))
		{
			throw new Exception('file not exist : '.$filePath, 1);
		}
		$md5 = md5_file($filePath);
		$filesize = filesize($filePath);
	    $filetype = pathinfo($filePath,PATHINFO_EXTENSION);

		return W2Qiniu::getUploadTokenForQiniuUploadWithMd5AndFileSize($md5,$filesize,$filetype);
	}


    /**
     * 查看指定文件是否存在于七牛，及其信息
     * @param  string $key 文件名
     * @return array      信息
     */
	public static function getFileInfoFromQiniu($key)
	{
		Qiniu_SetKeys(W2Qiniu::$accessKey, W2Qiniu::$secretKey);
		$client = new Qiniu_MacHttpClient(null);

		list($ret, $err) = Qiniu_RS_Stat($client, W2Qiniu::$bucket, $key);
		if ($err !== null) {
		    return $err;
		} else {
			$ret['urlPreview'] = 'http://'.(W2Qiniu::$domain).'/'. $key;
		    return $ret;
		}
	}

	/**
	 * 上传文件到七牛，并获得其预览地址
	 * @param  string $filePath 本地文件路径
	 * @param  string $key      存储目标文件名（默认为 md5_filesize.type
	 * @return string           存储后的预览URL
	 */
	public static function uploadAndReturnQiniuPreviewUrl($filePath,$key=null)
	{
		if (!is_null($key))
		{
			$uploadToken = W2Qiniu::getUploadTokenForQiniuUpload($key);
		}
		else
		{
			$uploadToken = W2Qiniu::getUploadTokenForQiniuUploadWithFile($filePath);
		}
		if (defined('IS_SQL_PRINT') && IS_SQL_PRINT)
		{
			var_export($uploadToken);
		}
		if (is_array($uploadToken) && array_key_exists('uploadToken',$uploadToken))
		{
			if (array_key_exists('isFileExistInQiniu',$uploadToken) && $uploadToken['isFileExistInQiniu'])
			{
				return $uploadToken['urlPreview'];
			}
			else
			{
				$putExtra = new Qiniu_PutExtra();
				$putExtra->Crc32 = 1;
				list($ret, $err) = Qiniu_PutFile($uploadToken['uploadToken'], $uploadToken['SaveKey'], $filePath, $putExtra);
				if ($err !== null) {
				    throw new Exception($err, 1);
				} else {
				    return $uploadToken['urlPreview'];
				}
			}
		}
	}

	public static function deleteFile($key)
	{
		Qiniu_SetKeys(W2Qiniu::$accessKey, W2Qiniu::$secretKey);
		$client = new Qiniu_MacHttpClient(null);

		$err = Qiniu_RS_Delete($client, W2Qiniu::$bucket, $key);
		if ($err !== null) {
			if (defined('IS_SQL_PRINT') && IS_SQL_PRINT)
			{
			    var_dump($err);
			}
		    return false;
		} else {
		    return true;
		}
	}

	public static function setKeyContent($key,$content=null)
	{
		if (W2Qiniu::getKeyContent($key)!=null)
		{
			if (!W2Qiniu::deleteFile($key))
			{
				throw new Exception('无法删除文件', 1);
			}
		}
		Qiniu_SetKeys(W2Qiniu::$accessKey, W2Qiniu::$secretKey);
		$putPolicy = new Qiniu_RS_PutPolicy( W2Qiniu::$bucket);
		$upToken = $putPolicy->Token(null);
		list($ret, $err) = Qiniu_Put($upToken, $key, $content, null);
		if ($err !== null) {
			if (defined('IS_SQL_PRINT') && IS_SQL_PRINT)
			{
			    var_dump($err);
			}
		    return false;
		} else {
		    return true;
		}
	}

	public static function getKeyContent($key)
	{
		$file = W2Qiniu::getFileInfoFromQiniu($key);
		if (is_array($file) && array_key_exists('urlPreview',$file))
		{
			return file_get_contents($file['urlPreview']);
		}
		return null;
	}

	public static function getPersistentInfo($persistentId)
	{
		return json_decode(file_get_contents('http://api.qiniu.com/status/get/prefop?id='.$persistentId),true);
	}

	public static function getZipInfo($fileList=array(),$saveas='')
	{

		require_once(__dir__.'/../lib/qiniu/http.php');
		require_once(__dir__.'/../lib/qiniu/auth_digest.php');
		require_once(__dir__.'/../lib/qiniu/utils.php');

		$fops ='mkzip/2';
		$extraKey = null;
		foreach ($fileList as $key => $value) {
			if (preg_match('/^http:\/\//', $value))
			{
				if (is_int($key))
				{
					$fops .= '/url/'.base64_encode($value);
				}
				else
				{
					$fops .= '/url/'.base64_encode($value);
					$fops .= '/alias/'.base64_encode($key);
				}
				if ($extraKey==null)
				{
					$extraKey = $value;
				}
			}
		}
		if ($saveas==null)
		{
			$saveas = md5($fops).'.zip';
		}
		if ($saveas!=null)
		{
			$fops .= '|saveas/'.base64_encode(W2Qiniu::$bucket.':'.$saveas);
		}
		$persistentKey = 'mkzip.'.$saveas.'.pfop';

		$saveFileInfo = W2Qiniu::getFileInfoFromQiniu($saveas);
		if (is_array($saveFileInfo) && array_key_exists('fsize',$saveFileInfo))
		{
			return $saveFileInfo;
		}

		$persistentId = W2Qiniu::getKeyContent($persistentKey);

		if (!is_null($persistentId))
		{
			return W2Qiniu::getPersistentInfo($persistentId);
		}

		if (strpos($extraKey,W2Qiniu::$domain)!=false)
		{
			$extraKey = str_replace('http://'.(W2Qiniu::$domain).'/','',$extraKey);
		}

		$notifyURL = "";
		$force = 0;

		$encodedBucket = urlencode(W2Qiniu::$bucket);
		$encodedKey = urlencode($extraKey);
		$encodedFops = urlencode($fops);
		$encodedNotifyURL = urlencode($notifyURL);

		$apiHost = "http://api.qiniu.com";
		$apiPath = "/pfop/";
		$requestBody = "bucket=$encodedBucket&key=$encodedKey&fops=$encodedFops&notifyURL=$encodedNotifyURL";
		if ($force !== 0) {
		    $requestBody .= "&force=1";
		}

		$mac = new Qiniu_Mac(W2Qiniu::$accessKey, W2Qiniu::$secretKey);
		$client = new Qiniu_MacHttpClient($mac);

		list($ret, $err) = Qiniu_Client_CallWithForm($client, $apiHost . $apiPath, $requestBody);
		if ($err !== null) {
			var_dump($err) ;
		} else {
			if (is_array($ret) && array_key_exists('persistentId',$ret))
			{
				W2Qiniu::setKeyContent($persistentKey,$ret['persistentId']);
				return W2Qiniu::getPersistentInfo($ret['persistentId']);
			}
		    return $ret;
		}
	}

}
