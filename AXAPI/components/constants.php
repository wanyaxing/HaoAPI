<?php
/**
* 常量文件
* @package conf
* @author axing
* @version 0.1
*/

//================= 系统运行代码 =================
/** Success */
define('RUNTIME_CODE_OK',        0);
/** Unkown error */
define('RUNTIME_CODE_ERROR_UNKNOWN',        1);
/** Database error */
define('RUNTIME_CODE_ERROR_DB',        2);
/** Param error */
define('RUNTIME_CODE_ERROR_PARAM',        3);
/** No data return */
define('RUNTIME_CODE_ERROR_DATA_EMPTY',        4);
/** 没有权限 */
define('RUNTIME_CODE_ERROR_NO_AUTH',        5);
/** 用户验证失败，非当前用户或密码已修改，需重新登录 */
define('RUNTIME_CODE_ERROR_NOT_USER',        6);
/** 错误的模型对象 */
define('RUNTIME_CODE_ERROR_NOT_MODEL',        7);
/** 无文件上传 */
define('NO_FILE_UPLOAD',        8);
/** 非法的userid */
define('INVALID_USER_ID',        9);
/** Param error */
define('RUNTIME_CODE_ERROR_NO_CHANGE',        10);


/** 状态  - 不存在 */
define('STATUS_DISABLED',         0);
/** 状态  - 正常 */
define('STATUS_NORMAL',           1);
/** 状态  - 草稿 */
define('STATUS_DRAFT',            2);
/** 状态  - 待审 */
define('STATUS_PENDING',          3);

/*
 *  1：浏览器设备
 * 	2：pc设备
 *	3：Android设备
 *	4：ios设备
 *	5：windows phone设备
*/
class DEVICE_TYPE
{
	const BROWSER  = 1;
	const PC       = 2;
	const LINUX    = 2;
	const ANDROID  = 3;
	const IOS 	   = 4;
	const WINDOWS  = 5;
}



?>
