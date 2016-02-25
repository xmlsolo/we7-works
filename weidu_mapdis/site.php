<?php

/**
 * 百度地图距离计算模块微站定义
 *
 * @author zwent
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');

include 'lib/tool.php';

class Weidu_mapdisModuleSite extends WeModuleSite {

	public function doMobileGetdis()
	{
		global $_W;
		$setting = getModuleSetting( $this->modulename, $_W['uniacid'] );
		include $this->template('getdis');
	}

	public function doMobileShow()
	{
		global $_W , $_GPC;
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$sql = 'SELECT name,location FROM ' .tablename('weidu_baidumap_dis').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$req = pdo_fetch($sql);
		$location = $req['location'];
		$name = $req['name'];
		$setting = getModuleSetting( $this->modulename, $_W['uniacid'] );
		include $this->template('show');
	}

	public function doMobileLocation()
	{
		// setcookie('lng','',time()-3600);
		// setcookie('lat','',time()-3600);
		global $_W , $_GPC;
		if (!$_COOKIE["lng"] || !$_COOKIE["lat"] || $_COOKIE['lng'] == 0 || $_COOKIE['lat'] == 0){
			$url = $this->createMobileUrl('Getdis');
			header("Location:".$url);
		}
		$setting = getModuleSetting( $this->modulename, $_W['uniacid'] );
		load()->classs( 'weixin.account' );
		$uniacid = intval($_W['uniacid']);
		$sql = 'SELECT * FROM '.tablename('weidu_baidumap_dis').' WHERE uniacid = '.$uniacid;
		$info = pdo_fetchall($sql);
		$location_current = $_COOKIE['lng'] .','.$_COOKIE['lat'];
		for($i = 0; $i<count($info) ; $i++){
			$info[$i]['order'] = getdis($info[$i]['location'] , $location_current);
		}
		// 排序操作
		$ages = array();
		foreach ($info as $user) {
			$ages[] = $user['order'];
		}

		array_multisort($ages, SORT_ASC, $info);
		include $this->template('index');
	}

	public function doWebindex() {

		global $_W;
		checklogin();
		// echo $this->createMobileUrl('Location');
		$uniacid = intval($_W['uniacid']);
		$sql = 'SELECT * FROM '.tablename('weidu_baidumap_dis').' WHERE uniacid = '.$uniacid;
		$info = pdo_fetchall($sql);
		include $this->template('shop');

	}

	public function doWebAddNewShop()
	{
		global $_W;
		checklogin();
		$uniacid = intval($_W['uniacid']);
		include $this->template('addshop');
	}

	public function doWebDoAddShop()
	{
		global $_GPC , $_W;
		checklogin();
		$uniacid = intval($_W['uniacid']);
		$name = trim($_GPC['name']);
		$thumb = trim($_GPC['thumb']);
		$description = trim($_GPC['description']);
		$location = $_GPC['location'];
		$str_location = $location['lng'] .','. $location['lat'];

		if(!isset($name) or $name == '' or empty($name)){return message('店铺名称不能为空!','','error');}
		if(!isset($location)){return message('店铺位置不能为空!请选择您的店铺的位置!','','error');}

		$info = array(
		              'uniacid'	=>	$uniacid,
		              'name'	=>	$name,
		              'thumb'	=>	$thumb,
		              'description'=>$description,
		              'location'=>	$str_location,
		              'bus_time'=>	trim($_GPC['bus_time']),
		              'add'		=>	trim($_GPC['add'])
		              );
		$db = 'weidu_baidumap_dis';
		$req = pdo_insert($db , $info);
		return $req ? message('添加成功!','','success') : message('添加失败!','','error');
	}

	public function doWebEdit()
	{
		global $_W , $_GPC;
		checklogin();
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$sql = 'SELECT * FROM '.tablename('weidu_baidumap_dis').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$info = pdo_fetch($sql);
		$str_location = $info['location'];
		$arr_location = explode(',' , $str_location);
		$location['lng'] = $arr_location[0];
		$location['lat'] = $arr_location[1];
		include $this->template('edit');
	}

	public function doWebDoEdit()
	{
		global $_W , $_GPC;
		checklogin();
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$name = trim($_GPC['name']);
		$thumb = trim($_GPC['thumb']);
		$description = trim($_GPC['description']);
		$location = $_GPC['location'];
		$location = $location['lng'] .','. $location['lat'];
		if(!isset($name)){message('店铺名称不能为空!', '' ,'error');};
		if(!isset($location)){message('店铺位置不能为空!', '' ,'error');};
		$where = array(
		               'uniacid'	=>	$uniacid,
		               'id'			=>	$id
		               );
		$info = array(
		              'name'		=>	$name,
		              'thumb'		=>	$thumb,
		              'description'	=>	$description,
		              'location'	=>	$location,
		              'bus_time'	=>	trim($_GPC['bus_time']),
		              'add'			=>	trim($_GPC['add'])
		              );
		$req = pdo_update('weidu_baidumap_dis' , $info , $where);
		return $req ? message('修改成功!','','success') : mesage('修改失败!','','error');
	}

	public function doWebDel()
	{
		global $_W , $_GPC;
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$where = array(
		               'uniacid'	=>	$uniacid,
		               'id'			=>	$id
		               );
		$req = pdo_delete('weidu_baidumap_dis' , $where);
		return $req ? message('删除成功!','','success') : message('删除失败!','','error');
	}
}
