<?php
/**
 * 数学相关处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */
class W2Math {

	/**
	 * 取得某个整数的位子集
	 * @param  int $number    目标数字
	 * @return array        由1248等组成的数组
	 */
	public static function getBitsOfNumber($number)
	{
		$bits = array();
		for ($i = 1 ; $i<=$number ; $i = $i * 2)
		{
			if ( ($i & $number) == $i)
			{
				$bits[] = $i;
			}
		}
		return $bits;
	}

	/**
	 * 取得目标数字的所有子集
	 * @param  int $number     目标数字
	 * @return array         由1...等组成的数组
	 */
	public static function getChildsOfNumber($number)
	{
		$childs = array();
		for ($i = $number ; $i ; $i = ($i - 1) & $number)
		{
			$childs[] = $i;
		}
		return $childs;
	}

	/**
	 * 判断x是否y的子集
	 * @param  int  $x    数字
	 * @param  int  $y    数字
	 * @return boolean
	 */
	public static function isXinY($x,$y)
	{
		return ($x & $y) == $x;
	}

}
