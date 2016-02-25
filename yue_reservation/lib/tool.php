<?php

function show_shop_name($arr , $html = '|'){
	$id = explode("," , $arr);
	$info = array();
	$str = '';
	$len = strlen($html);
	foreach($id as $r){
		$sql = "SELECT name FROM ".tablename('reservation_shop') . "WHERE id = ".$r;
		$req = pdo_fetch($sql);
		array_push($info , $req);
	}
	foreach($info as $r){
		$str .= $r['name'] . ' ' . $html .' ';
	}
	$str = substr($str,0,strlen($str)- $len - 1);
	echo $str;
}

function show_worker_name($arr , $html = '|'){
	$id = explode("," , $arr);
	$info = array();
	$str = '';
	$len = strlen($html);
	foreach($id as $r){
		$sql = "SELECT name FROM ".tablename('reservation_worker') . "WHERE id = ".$r;
		$req = pdo_fetch($sql);
		array_push($info , $req);
	}
	foreach($info as $r){
		$str .= $r['name'] . ' '.$html.' ';
	}
	$str = substr($str,0,strlen($str)-$len - 1);
	echo $str;
}