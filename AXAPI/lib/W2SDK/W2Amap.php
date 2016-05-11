<?php
/**
 * 高德地图处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Amap extends W2Map{
	public static $amap_accessKey; //	客户唯一标识 用户申请，由高德地图API后台自动分配
	public static $amap_secretKey; //   可选，选择数字签名认证的用户必填

	/**
	 * 地理编码：将详细的结构化地址转换为高德经纬度(lng,lat)坐标，且支持名胜景区、标志性建筑物名称解析为高德经纬度坐标。
	 * 例如：北京市朝阳区阜通东大街6号-->116.480881,39.989410
	 * 天安门-->116.397499,39.908722
	 * http://lbs.amap.com/api/webservice/reference/georegeo/
	 * @param  array  $param
							参数名		含义					规则说明													  是否必须	缺省值
							key	        请求服务权限标识		用户在高德地图官网申请REST类型KEY	  							必填		无
							address	    结构化地址信息	    规则： 省+市+区+街道+门牌号		  							必填		无
							city	    查询城市	    		可选值：城市中文、中文全拼、citycode、adcode					可选		无（全国范围内搜索）  如：北京/beijing/010/110000
							sig	        数字签名	    		数字签名获取和使用方法											可选		无
							output	    返回数据格式类型		可选值：JSON,XML												可选		JSON
							callback	回调函数	    		callback值是用户定义的函数名称，此参数只在output=JSON时有效		可选		无
	 * @return [type]        [description]
	 * /geocodes/location  经度,纬度
	 */
	public static function geo($param=array())
	{
		$param['key'] = static::$amap_accessKey;
		$param['output'] = 'JSON';

		if (static::$amap_secretKey != null)
		{
			$param['sig'] = static::getSign($param);
		}

		$geo = W2Web::loadJsonByUrl('http://restapi.amap.com/v3/geocode/geo','get',$param);
		if (isset($geo['geocodes']) && count($geo['geocodes'])>0 )
		{
			foreach ($geo['geocodes'] as &$area) {
				list($area['lng'],$area['lat']) = explode(',',$area['location']);
			}
		}
		return $geo;
	}

	/**
	 * 逆地理编码：将经纬度(lng,lat)转换为详细结构化的地址，且返回附近周边的POI信息，以及该经纬度所在的POI信息。
	 * 例如：116.480881,39.989410-->北京市朝阳区阜通东大街6号
	 *  http://restapi.amap.com/v3/geocode/regeo?parameters
	 * @param  array  $param [description]
						参数名		含义						规则说明								是否必须				缺省值
						key	        请求服务权限标识			用户在高德地图官网申请REST类型KEY				必填	无
						location	经纬度坐标	    		规则： 最多支持20个坐标点。多个点之间用"|"分割。经度在前，纬度在后，经纬度间以“，”分割，经纬度小数点后不得超过6位	必填	无
						poitype	    返回附近POI类型			支持传入POI TYPECODE及名称；支持传入多个POI类型，多值间用“|”分隔，extensions=all时生效，不支持batch=true(逆地理编码批量查询)	可选	1000
						radius	    搜索半径	    			取值范围：0~3000,单位：米	可选	1000
						extensions	返回结果控制	    		此项默认返回基本地址信息；取值为all返回地址信息、附近POI、道路以及道路交叉口信息。	可选	base
						batch	    批量查询控制	    		batch=true为批量查询。batch=false为单点查询，batch=false时即使传入多个点也只返回第一个点结果	可选	FALSE
						roadlevel	道路等级	    			可选值：1，当roadlevel=1时，过滤非主干道路，仅输出主干道路数据	可选	无
						sig	        数字签名	    			数字签名获取和使用方法	可选	无
						output	    返回数据格式类型			可选值：JSON,XML	可选	JSON
						callback	回调函数	    			callback值是用户定义的函数名称，此参数只在output=JSON时有效	可选	无
						homeorcorp	是否优化POI返回顺序			可选参数:0,1,2	可选	0
									默认:0
									0：不优化。
									1：综合大数据将居家相关的主POI结果优先返回，即优化pois字段之中的poi顺序。
									2：综合大数据将公司相关的主POI结果优先返回，即优化pois字段之中的poi顺序。
	 * @return [type]        [description]
	 */
	public static function regeo($param=array())
	{
		$param['key'] = static::$amap_accessKey;
		$param['extensions'] = 'pois';
		$param['output'] = 'JSON';

		if (static::$amap_secretKey != null)
		{
			$param['sig'] = static::getSign($param);
		}

		return W2Web::loadJsonByUrl('http://restapi.amap.com/v3/geocode/regeo','get',$param);
	}

	/**
	 * 输入提示是一类简单的HTTP接口，提供根据用户输入的关键词查询返回建议列表。
	 * http://lbs.amap.com/api/webservice/reference/inputtips/
	 * @param  array  $param [description]
						参数名		含义	规则说明	是否必须	缺省值
						key	        请求服务权限标识	用户在高德地图官网申请REST类型KEY	必填	无
						keywords	查询关键词		必填	无
						type	    POI分类	服务可支持传入多个分类，多个类型剑用“|”分隔	选填	无
								    可选值：POI分类名称、分类代码
						location	坐标	经度,纬度	选填	无
								    建议使用location参数，可在此location附近优先返回搜索关键词信息
						city	    搜索城市	可选值：城市中文、中文全拼、citycode、adcode	选填	无（默认在全国范围内搜索）
								    如：北京/beijing/010/110000
						citylimit	仅返回指定城市数据	可选值：true/false	可选	FALSE
						datatype	返回的数据类型	多种数据类型用“|”分隔，可选值：all-返回所有数据类型、poi-返回POI数据类型、bus-返回公交站点数据类型、busline-返回公交线路数据类型	选填	all
						sig	        数字签名	数字签名获取和使用方法	可选	无
						output	    返回数据格式类型	可选值：JSON,XML	可选	JSON
						callback	回调函数	callback值是用户定义的函数名称，此参数只在output=JSON时有效	可选	无
	 * @return [type] [description]
	 */
	public static function inputtips($param=array())
	{
		$param['key'] = static::$amap_accessKey;
		$param['extensions'] = 'pois';
		$param['output'] = 'JSON';

		if (static::$amap_secretKey != null)
		{
			$param['sig'] = static::getSign($param);
		}

		return W2Web::loadJsonByUrl('http://restapi.amap.com/v3/assistant/inputtips','get',$param);
	}

    /**
     * 字典根据key值按字母排序后获得签名
     * http://lbs.amap.com/yuntu/guide/getsig/
     * @param  array $list  参与运算的数据
     * @return string       计算后的签名
     */
    public static function getSign($list)
    {
        $post_array = array();
        foreach($list as $k=>$v){
            if($k != 'sig' && !is_null($v) && !(is_array($v) && count($v)==0) ){
                $post_array[] = $k .'='. $v;
            }
        }
        sort($post_array);
        return md5(implode('&',$post_array).static::$amap_secretKey);
    }
}

//静态类的静态变量的初始化不能使用宏，只能用这样的笨办法了。
if (W2Amap::$amap_accessKey==null && defined('W2AMAP_AMAP_ACCESSKEY'))
{
	W2Amap::$amap_accessKey    = W2AMAP_AMAP_ACCESSKEY;
}
if (W2Amap::$amap_secretKey==null && defined('W2AMAP_AMAP_SECRETKEY'))
{
	W2Amap::$amap_secretKey    = W2AMAP_AMAP_SECRETKEY;
}
