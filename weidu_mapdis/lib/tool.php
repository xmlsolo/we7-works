<?php
/**
 * 计算两个坐标之间的距离(米)
 * @param float $fP1Lat 起点(纬度)
 * @param float $fP1Lon 起点(经度)
 * @param float $fP2Lat 终点(纬度)
 * @param float $fP2Lon 终点(经度)
 * @return int
*/
function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
	$fEARTH_RADIUS = 6378137;
	//角度换算成弧度
	$fRadLon1 = deg2rad($fP1Lon);
	$fRadLon2 = deg2rad($fP2Lon);
	$fRadLat1 = deg2rad($fP1Lat);
	$fRadLat2 = deg2rad($fP2Lat);
	//计算经纬度的差值
	$fD1 = abs($fRadLat1 - $fRadLat2);
	$fD2 = abs($fRadLon1 - $fRadLon2);
	//距离计算
	$fP = pow(sin($fD1/2), 2) + cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
	return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
}
/**
 * 百度坐标系转换成标准GPS坐系
 * @param float $lnglat 坐标(如:106.426, 29.553404)
 * @return string 转换后的标准GPS值:
*/
function BD09LLtoWGS84($lnglat){ // 经度,纬度
	$lnglat = explode(',', $lnglat);
	list($x,$y) = $lnglat;
	$Baidu_Server = "http://api.map.baidu.com/ag/coord/convert?from=0&to=4&x={$x}&y={$y}&callback=BMap.Convertor.cbk_7594";
	$result = @file_get_contents($Baidu_Server);
	$json = json_decode($result);
	if($json->error == 0){
		$bx = base64_decode($json->x);
		$by = base64_decode($json->y);
		$GPS_x = 2 * $x - $bx;
		$GPS_y = 2 * $y - $by;
		return $GPS_x.','.$GPS_y;//经度,纬度
	}else
	return $lnglat;
}

function getdis($pos1 , $pos2)
{
	$p1 = split(',',BD09LLtoWGS84($pos1));
	$p2 = split(',',BD09LLtoWGS84($pos2));
	return distanceBetween($p1[0] , $p1[1] , $p2[0] , $p2[1]);
}

function getModuleSetting($moduleName = '', $uniacid = '') {
	$sql  = 'SELECT * FROM ' . tablename( 'uni_account_modules' ) . ' WHERE module = :module AND uniacid = :uniacid';
	$data = pdo_fetch( $sql, array( ':module' => $moduleName, ':uniacid' => $uniacid ) );
	$data = iunserializer($data['settings']);
	return $data;
}
?>
