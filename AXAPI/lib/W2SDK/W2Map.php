<?php
/**
 * 地图处理函数库文件
 * @package W2
 * @author axing
 * @since 1.0
 * @version 1.0
 */

class W2Map {
	const EARTH_RADIUS = 6378.137;// 地球半径
	/**
	 *
	 * 根据经纬度计算距离
	 * @param float $lng1　经度1
	 * @param float $lat1　纬度2
	 * @param float $lng2　经度1
	 * @param float $lat2　纬度2
	 * @return float      单位(公里 KM)
	 */
	public static function getdistance($lng1,$lat1,$lng2,$lat2) {
	    $r = W2Map::EARTH_RADIUS;
	    $dlat = deg2rad($lat2 - $lat1);
	    $dlng = deg2rad($lng2 - $lng1);
	    $a = pow(sin($dlat / 2), 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * pow(sin($dlng / 2), 2);
	    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	    return round($r * $c,2);
	}

	 /**
	 *计算某个经纬度的周围某段距离的正方形的四个点
	 *
	 *@param lng float 经度
	 *@param lat float 纬度
	 *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
	 *@return array 正方形的四个点的经纬度坐标
	 */
	 public static function returnSquarePoint($lng, $lat,$distance = 0.5){

	    $dlng =  2 * asin(sin($distance / (2 * W2Map::EARTH_RADIUS)) / cos(deg2rad($lat)));
	    $dlng = rad2deg($dlng);

	    $dlat = $distance/W2Map::EARTH_RADIUS;
	    $dlat = rad2deg($dlat);

	    return array(
	                'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
	                'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
	                'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
	                'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
	                );
	 }

	 /**
	 *计算某个经纬度的周围某段距离的范围SQL语句
	 *
	 *@param lng float 经度
	 *@param lat float 纬度
	 *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
	 *@return array $p_where
	 */
	 public static function returnWhereForDBToolAroundPoint($lng, $lat,$distance = 0.5)
	 {
		$squares = W2Map::returnSquarePoint($lng, $lat,$distance);
		$p_where = array();
		$p_where['lat > \'%s\''] = $squares['right-bottom']['lat'];
		$p_where['lat < \'%s\''] = $squares['left-top']['lat'];
		$p_where['lng > \'%s\''] = $squares['left-top']['lng'];
		$p_where['lng < \'%s\''] = $squares['right-bottom']['lng'];
		return $p_where;
	 }
}
