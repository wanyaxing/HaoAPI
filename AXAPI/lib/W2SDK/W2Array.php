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


}
