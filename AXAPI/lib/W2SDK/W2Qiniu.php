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
	//W2Config::$Qiniu_bucket;

	/**
	 * 存储空间对应域名（空间名和域名不一样，但是是一对，以七牛那边设置为准）  //todo
	 * @var string
	 */
	//W2Config::$Qiniu_domain;

	/**
	 * 登录密钥 //todo
	 * @var string
	 */
	//W2Config::$Qiniu_accessKey;

	/**
	 * 登录密钥校验 //todo
	 * @var string
	 */
	//W2Config::$Qiniu_secretKey;


	/**
	 * 上传文件到七牛前需要先申请上传用的token
	 * @param  string $key    文件名（空间内唯一哦）
	 * @return array         token相关的数组，其中['uploadToken']就是上传用token
	 */
    public static function getUploadTokenForQiniuUpload($key)
    {
		$data = array();
		$data['bucket'] = W2Config::$Qiniu_bucket;
		$data['Expires'] = 3600;
		$data['deadline'] = time() + $data['Expires'];
		$data['deadTime'] = date('Y-m-d H:i:s',$data['deadline']);

		$scope = W2Config::$Qiniu_bucket;
		if ($key !='')
		{
			$scope = W2Config::$Qiniu_bucket .':'.$key;
			$data['SaveKey'] = $key;
			$data['urlPreview'] = W2Qiniu::getBaseUrl($key);
			$data['urlDownload'] = W2Qiniu::getPrivateUrl($data['urlPreview']);
		}
		else
		{
			$data['SaveKey'] = null;
			$data['urlPreview'] = W2Qiniu::getBaseUrl('$(fname).$(ext)');
			$data['urlDownload'] = $data['urlPreview'];
		}
		$data['ReturnBody'] = '{
    "urlDownload": "'.$data['urlDownload'].'",
    "urlPreview": "'.$data['urlPreview'].'",
    "name": $(fname),
    "ext": $(ext),
    "size": $(fsize),
    "type": $(mimeType),
    "hash": $(etag),
    "w": $(imageInfo.width),
    "h": $(imageInfo.height),
    "color": $(exif.ColorSpace.val)
}';
		Qiniu_SetKeys(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
		$putPolicy = new Qiniu_RS_PutPolicy(W2Config::$Qiniu_bucket);
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

		$data = W2Qiniu::getUploadTokenForQiniuUpload($key);

		if (is_array($data))
		{
			$data['md5'] = $md5;
			$data['filesize'] = $filesize;
			$data['filetype'] = $filetype;
		}

		return $data;

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
		Qiniu_SetKeys(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
		$client = new Qiniu_MacHttpClient(null);

		list($ret, $err) = Qiniu_RS_Stat($client, W2Config::$Qiniu_bucket, $key);
		if ($err !== null)
		{
		    return $err;
		} else {
			$ret['urlPreview'] = W2Qiniu::getBaseUrl($key);
			$ret['urlDownload'] = W2Qiniu::getPrivateUrl($ret['urlPreview']);
		    return $ret;
		}
	}

	/**
	 * 根据七牛文件的key值，组装文件地址
	 * @param  string $key 文件名
	 * @return string   url
	 */
	public static function getBaseUrl($key)
	{
		return Qiniu_RS_MakeBaseUrl(W2Config::$Qiniu_domain, $key);
	}

	/**
	 * 根据七牛文件的key值，追加下载授权
	 * @param  string $key 文件名
	 * @return string   url
	 */
	public static function getPrivateUrlOfKey($key)
	{
		return W2Qiniu::getPrivateUrl(W2Qiniu::getBaseUrl($key));
	}

	/**
	 * 根据七牛文件地址，追加下载授权
	 * @param  url $baseUrl 文件地址
	 * @param  string $attname 下载后另存为的文件名
	 * @return url          含下载授权的文件地址
	 */
	public static function getPrivateUrl($baseUrl,$attname=null)
	{
		if (!is_null($attname))
		{
			if (strpos($baseUrl,'attname=')!==false)
			{
				$baseUrl = preg_replace('/attname=[^&]*/','attname='.rawurlencode($attname));
			}
			else
			{
				$baseUrl .= (strpos($baseUrl, '?')!==false?'&':'?').'attname='.rawurlencode($attname);
			}
		}

		Qiniu_SetKeys(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);

		$getPolicy = new Qiniu_RS_GetPolicy();

		$getPolicy->Expires = 3600;

		$privateUrl = $getPolicy->MakeRequest($baseUrl, null);

		return $privateUrl;
	}

	/**
	 * 获得七牛格式的压缩图地址
	 * @param  string $baseUrl 地址
	 * @param  int    $width   宽度
	 * @param  int    $height  高度
	 * @param  int    $mode    模式	说明
		* /0/w/<LongEdge>/h/<ShortEdge>	限定缩略图的长边最多为<LongEdge>，短边最多为<ShortEdge>，进行等比缩放，不裁剪。如果只指定 w 参数则表示限定长边（短边自适应），只指定 h 参数则表示限定短边（长边自适应）。
		* /1/w/<Width>/h/<Height>	    限定缩略图的宽最少为<Width>，高最少为<Height>，进行等比缩放，居中裁剪。转后的缩略图通常恰好是 <Width>x<Height> 的大小（有一个边缩放的时候会因为超出矩形框而被裁剪掉多余部分）。如果只指定 w 参数或只指定 h 参数，代表限定为长宽相等的正方图。
		* /2/w/<Width>/h/<Height>	    限定缩略图的宽最多为<Width>，高最多为<Height>，进行等比缩放，不裁剪。如果只指定 w 参数则表示限定宽（长自适应），只指定 h 参数则表示限定长（宽自适应）。它和模式0类似，区别只是限定宽和高，不是限定长边和短边。从应用场景来说，模式0适合移动设备上做缩略图，模式2适合PC上做缩略图。
		* /3/w/<Width>/h/<Height>	    限定缩略图的宽最少为<Width>，高最少为<Height>，进行等比缩放，不裁剪。如果只指定 w 参数或只指定 h 参数，代表长宽限定为同样的值。你可以理解为模式1是模式3的结果再做居中裁剪得到的。
		* /4/w/<LongEdge>/h/<ShortEdge>	限定缩略图的长边最少为<LongEdge>，短边最少为<ShortEdge>，进行等比缩放，不裁剪。如果只指定 w 参数或只指定 h 参数，表示长边短边限定为同样的值。这个模式很适合在手持设备做图片的全屏查看（把这里的长边短边分别设为手机屏幕的分辨率即可），生成的图片尺寸刚好充满整个屏幕（某一个边可能会超出屏幕）。
		* /5/w/<LongEdge>/h/<ShortEdge>	限定缩略图的长边最少为<LongEdge>，短边最少为<ShortEdge>，进行等比缩放，居中裁剪。如果只指定 w 参数或只指定 h 参数，表示长边短边限定为同样的值。同上模式4，但超出限定的矩形部分会被裁剪。
	 * @param  int    $quality 图片质量，取值范围是[1, 100]。默认85，会根据原图质量算出一个修正值，取修正值和指定值中的小值。 注：1. 如果图片的quality值本身大于90，会根据指定quality值进行处理，此时修正值会失效。2. quality后面可以增加 ! ，表示强制使用指定值（eg：100!）3. 支持图片类型：jpg。
	 * @param  string $format  取值范围：jpg，gif，png，webp等，缺省为原图格式。
	 * @param  int    $interlace  是否支持渐进显示 * 取值范围：1 支持渐进显示，0不支持渐进显示(缺省为0) * 适用目标格式：jpg * 效果：网速慢时，图片显示由模糊到清晰。
	 * @return [type]          [description]
	 */
	public static function getThumbImageUrl($baseUrl=null,$width=null,$height=null,$mode=0,$quality=null,$format=null,$interlace=null)
	{
		if (!is_null($baseUrl))
		{
	    	$ops = array($mode);

	    	if (!empty($width)) {
	    		$ops[] = 'w/' . $width;
	    	}
	    	if (!empty($height)) {
	    		$ops[] = 'h/' . $height;
	    	}
	    	if (!empty($quality)) {
	    		$ops[] = 'q/' . $quality;
	    	}
	    	if (!empty($format)) {
	    		$ops[] = 'format/' . $format;
	    	}

	    	if (count($ops)>0)
	    	{
		    	$thumbString = '?imageView2/' . implode('/', $ops);

		    	if (strpos($baseUrl,'?')!==false)
		    	{
		    		$baseUrl = str_replace('?',$thumbString.'&',$baseUrl);
		    	}
		    	else
		    	{
			    	$baseUrl = $baseUrl . $thumbString ;
		    	}
	    	}
		}
    	return $baseUrl;
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
					// var_dump($err);
				    throw new Exception($err->Err, 1);
				} else {
				    return $uploadToken['urlPreview'];
				}
			}
		}
	}

	public static function deleteFile($key)
	{
		Qiniu_SetKeys(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
		$client = new Qiniu_MacHttpClient(null);

		$err = Qiniu_RS_Delete($client, W2Config::$Qiniu_bucket, $key);
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
		Qiniu_SetKeys(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
		$putPolicy = new Qiniu_RS_PutPolicy( W2Config::$Qiniu_bucket);
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

		require_once(__dir__.'/../qiniu/http.php');
		require_once(__dir__.'/../qiniu/auth_digest.php');
		require_once(__dir__.'/../qiniu/utils.php');

		$fops ='mkzip/2';
		$extraKey = null;
		// var_export($fileList);
		foreach ($fileList as $key => $value) {
			if (preg_match('/^http:\/\//', $value))
			{
				if (is_int($key))
				{
					$fops .= '/url/'.Qiniu_Encode($value);
				}
				else
				{
					$fops .= '/url/'.Qiniu_Encode($value);
					// $fops .= '/alias/'.Qiniu_Encode($key);
					// var_export($key);
					// var_export(Qiniu_Encode($key));
					$fops .= '/alias/'.Qiniu_Encode($key);
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
			$fops .= '|saveas/'.Qiniu_Encode(W2Config::$Qiniu_bucket.':'.$saveas);
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

		if (strpos($extraKey,W2Config::$Qiniu_domain)!=false)
		{
			$extraKey = str_replace('http://'.(W2Config::$Qiniu_domain).'/','',$extraKey);
		}

		$notifyURL = "";
		$force = 0;

		$encodedBucket = urlencode(W2Config::$Qiniu_bucket);
		$encodedKey = urlencode($extraKey);
		$encodedFops = urlencode($fops);
		$encodedNotifyURL = urlencode($notifyURL);

		$apiHost = "http://api.qiniu.com";
		$apiPath = "/pfop/";
		$requestBody = "bucket=$encodedBucket&key=$encodedKey&fops=$encodedFops&notifyURL=$encodedNotifyURL";
		if ($force !== 0) {
		    $requestBody .= "&force=1";
		}

		$mac = new Qiniu_Mac(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
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

	/**
	 * 查询空间列表
	 * @param  string  $prefix 前缀
	 * @param  string  $marker 分隔标记（默认第一页为空）
	 * @param  integer $limit  分页大小（默认1000）
	 * @return [type]          [description]
	 */
	public static function getList($prefix = '', $marker = '', $limit = 0, &$markerNext=null)
	{
		$mac = new Qiniu_Mac(W2Config::$Qiniu_accessKey, W2Config::$Qiniu_secretKey);
		$client = new Qiniu_MacHttpClient($mac);

		$QINIU_RSF_HOST = 'http://rsf.qbox.me';
		$query = array('bucket' => W2Config::$Qiniu_bucket);
		if (!empty($prefix)) {
			$query['prefix'] = $prefix;
		}
		if (!empty($marker)) {
			$query['marker'] = $marker;
		}
		if (!empty($limit)) {
			$query['limit'] = $limit;
		}

		$url =  $QINIU_RSF_HOST . '/list?' . http_build_query($query);
		list($ret, $err) = Qiniu_Client_Call($client, $url);
		if ($err !== null) {
			throw new Exception( $err);
		}

		$items = $ret['items'];
		if (empty($ret['marker'])) {
			// return 'EOF';
		} else {
			if (isset($markerNext))
			{
				$markerNext = $ret['marker'];
			}
		}
		return $items;
	}

}
