<?php
/**
 * 数组处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class W2Array {

	/**
	 * 将字典组成的数组，转换成，取出字典中某键后作为新键放入新字典，
	 * @param  array $p_array      目标数组，其值为字典
	 * @param  string $p_keyInList 目标数组中字典值里的key
	 * @return list               以p_keyInList为key，以目标数组中的值为值，的字典
	 */
	public static function arrayToList($p_array,$p_keyInList)
	{
		$list = array();
		foreach ($p_array as $d) {
			$list[$d[$p_keyInList]] = $d;
		}
		return $list;
	}

	/**
	 * 在字典组成的数组中，找出指定键值最大的字典
	 * @param  array $p_array     目标数组，其值为字典
	 * @param  string $p_keyInList 目标数组中字典值里的key
	 * @return list              最大值所在的字典
	 */
	public static function maxListInArray($p_array,$p_keyInList)
	{
		$max = null;
		$maxList = null;
		foreach ($p_array as $d) {
			if ($d[$p_keyInList]>$max)
			{
				$max = $d[$p_keyInList];
				$maxList = $d;
			}
		}
		return $maxList;
	}

	/**
	 * 在字典组成的数组中，找出指定键值最小的字典
	 * @param  array $p_array     目标数组，其值为字典
	 * @param  string $p_keyInList 目标数组中字典值里的key
	 * @return list              最小值所在的字典
	 */
	public static function minListInArray($p_array,$p_keyInList)
	{
		$min = null;
		$minList = null;
		foreach ($p_array as $d) {
			if ($d[$p_keyInList]<$min)
			{
				$min = $d[$p_keyInList];
				$minList = $d;
			}
		}
		return $minList;
	}

	/**
	 * 在字典组成的数组中，找出指定键最大的值
	 * @param  array $p_array     目标数组，其值为字典
	 * @param  string $p_keyInList 目标数组中字典值里的key
	 * @return int              最大值
	 */
	public static function maxValueInListArray($p_array,$p_keyInList)
	{

		return static::maxListInArray($p_array,$p_keyInList)[$p_keyInList];
	}

	/**
	 * 在字典组成的数组中，找出指定键最小值的
	 * @param  array $p_array     目标数组，其值为字典
	 * @param  string $p_keyInList 目标数组中字典值里的key
	 * @return int              最小值
	 */
	public static function minValueInListArray($p_array,$p_keyInList)
	{
		return static::minListInArray($p_array,$p_keyInList)[$p_keyInList];
	}

	/**
	 * 将字典重组为key=value的字符串，排序后，连接到一起。
	 * @param  array $p_array  数组
	 * @return string          1=a&2=c&3=b
	 */
	public static function sortAndBuildQuery($p_array,$p_separator='&',$p_ignoreNULL = true)
	{

		$tmpArr = array();
		foreach ($p_array as $_key => $_value) {
			if ($p_ignoreNULL && $_value===null)
			{
				continue;
			}
			if (is_array($_value))
			{
				$_value = W2Array::sortAndBuildQuery($_value);
			}
			array_push($tmpArr, sprintf('%s=%s', $_key, $_value));
		}

		//对数组进行自然排序
		sort($tmpArr, SORT_STRING);

		//将排序后的数组组合成字符串
		$tmpStr = implode($p_separator, $tmpArr );

		return $tmpStr;
	}

	/**
	 * 判断数组是否是字典
	 * @param  array  $p_array   数组变量
	 * @return boolean          [description]
	 */
	public static function isList($p_array)
	{
		return is_array($p_array) && (array_keys($p_array) !== array_keys(array_keys($p_array)));
	}

}
