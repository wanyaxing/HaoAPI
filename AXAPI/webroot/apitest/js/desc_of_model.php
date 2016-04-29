<?php

	$configPath = __dir__.'/../../../config.php';

	if (file_exists($configPath))
	{
	    //加载配置文件
	    require_once($configPath);

	    if (defined('AXAPI_ROOT_PATH'))
	    {
		    //数据库操作工具
		    require_once(AXAPI_ROOT_PATH.'/lib/DBTool/DBModel.php');

		    //加载基础方法
		    require_once(AXAPI_ROOT_PATH.'/components/Utility.php');

			$modelName = W2HttpRequest::getRequestString('model_name',false);

	        $desc = Utility::getDescriptionsInModel($modelName);

	        echo Utility::json_encode_unicode($desc);

	        exit;
	    }

	}

	echo '{}';
	exit;


