<?php

/**
 * 预约模块微站定义
 *
 * @author zwent
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');

include 'lib/tool.php';

class Yue_reservationModuleSite extends WeModuleSite {

/*
 移动端(doMobile) 所有方法
*/

//首页显示
	// 首页
	public function doMobileIndex() {
		include $this->template('index');
	}
	// 金牌养生师 按钮
	public function doMobileWorkers()
	{
		$uniacid = intval($_W['uniacid']);
		$sql = 'SELECT * FROM '.tablename('reservation_worker'). ' WHERE uniacid = '.$uniacid;
		$info = pdo_fetchall($sql);
		include $this->template('workers');
	}
	// 我要预约 按钮
	public function doMobileOrder()
	{
		checkauth();
		$sql = 'SELECT id,name FROM '.tablename('reservation_shop');
		$info = pdo_fetchall($sql);
		include $this->template('order');
	}
	// 显示商品 列表 页面
	public function doMobileGoods()
	{
		$sql = 'SELECT * FROM '.tablename('reservation_goods').' ORDER BY id DESC ';
		$info = pdo_fetchall($sql);
		include $this->template('goods');
	}
	// 显示商品 信息 页面
	public function doMobileShowInfo()
	{
		global $_GPC , $_W;
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$sql = 'SELECT * FROM '.tablename('reservation_goods').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$info = pdo_fetch($sql);
		include $this->template('showInfo');
	}
	// 添加订单 (前端显示)
	public function doMobileDoAddOrder()
	{
		global  $_GPC , $_W;
		checkauth();
  		$id = intval($_GPC['id']);
  		$shop = intval($_GPC['shop']);
		$uniacid = intval($_W['uniacid']);
		// 基本的 商品信息
		$sql_goods = 'SELECT * FROM '.tablename('reservation_goods').' WHERE id = '.$id;
		$info['goods'] = pdo_fetch($sql_goods);
		// 根据商品的 id 搜索 该商品的商铺
		$shops_id = $info['goods']['shops'];
		// 将 shops_id 按 ',' 分割 并声称数组
		$shops = explode(',',$shops_id);
		// 生成查找 shop 表的sql语句
		$where_shop = ' WHERE uniacid = '.$uniacid . ' AND ';
		$con = '';
		for ($i=0; $i < count($shops) ; $i++) {
			$con .= ' id = '.$shops[$i].' OR ';
		}
		$con = substr($con, 0 , count($con) - 4 );
		$where_shop .= $con;
		$sql_shop = 'SELECT id,name FROM ' .tablename('reservation_shop') . $where_shop;
		$info['goods']['shops'] = pdo_fetchall($sql_shop);
		// 随机生成17位订单号
		$time = date('Ymdhis' , time());
		$rad_num = rand(100,999);
		$info['orderCode'] = trim($time.$rad_num);
		// 获取当前 fans 的 uid
		$uid = intval($_W['member']['uid']);
		$info['user']['uid'] = $uid;
		$info['user']['mobile'] = trim($_W['member']['mobile']);
		// 根据 uid 获取 mc_members 表中的 realname 字段
		$where_member = ' WHERE uid = '.$uid ." AND uniacid = ".$uniacid;
		$sql_member = 'SELECT realname FROM '.tablename('mc_members').$where_member;
		$info_member = pdo_fetch($sql_member);
		$info['user']['name'] = $info_member['realname'];

		load()->func('tpl');
		include $this->template('addOrderForm');
	}
	// 添加订单 (表单)
	public function doMobilecreateOrder()
	{
		global $_GPC , $_W;
		checkauth();

		$uniacid = intval($_W['uniacid']);
		$code = trim($_GPC['code']);
		$goods_id = intval($_GPC['goods_id']);
		$name = trim($_GPC['name']);
		$price = floatval($_GPC['price']);
		$shop = intval($_GPC['shop']);
		$buyer_uid = intval($_W['member']['uid']);
		$buyer_phone = trim($_GPC['buyer_phone']);
		$buyer_name = trim($_GPC['buyer_name']);
		$worker_id = intval($_GPC['worker_uid']);
		$addtime = trim(time());

		$date = trim($_GPC['date']);
		$time = trim($_GPC['time']);

		$current_date = trim(strtotime(date('Y-m-d' , time())));
		$current_time = time();
		$time = trim(strtotime($time));
		$date = trim(strtotime($date));

		if (!isset($time) or $time == '') die('请您填写预约的时间！');
		if (!isset($date) or $date == '') die('请您填写预约的日期！');

		if ($date < $current_date) die('您选择的日期不能低于现在时间!');
		if ($time < $current_time) die('您选择的时间不能低于现在时间!');

		// 计算出 多了多少天
		$offerdate = $date - $current_date;
		$time = $time + $offerdate;

		$state = 'waiting';
		// 判断空值
		if (!isset($buyer_name) or $buyer_name == '') die('请您填写您的姓名！');
		if (!isset($buyer_phone) or $buyer_phone == '') die('请您填写您的电话！');
		if (!isset($shop) or $shop == '') die('请您填写预约店铺！');
		if (!isset($worker_id) or $worker_id == '') die('请您填写预约的按摩师！');
		// 手机号正则
		$isMobile="/^1[34578]\d{9}$/";
		if (!preg_match($isMobile , $buyer_phone)) die('您输入的电话格式不正确！');
		// 根据 worker_id 获取 name , phone
		$sql_worker = 'SELECT * FROM '.tablename('reservation_worker').' WHERE uniacid = '.$uniacid.' AND id = '.$worker_id;
		$info_worker = pdo_fetch($sql_worker);
		$worker_phone = $info_worker['phone'];
		$worker_name = $info_worker['name'];
		$worker_uid = $info_worker['uid'];
		// 根据 worker_uid 获取 openid
		$sql_worker_openid =  'SELECT openid FROM '.tablename('mc_mapping_fans').' WHERE uniacid = '.$uniacid.' AND uid = '.$worker_uid;
		$info_worker_openid = pdo_fetch($sql_worker_openid);
		$worker_openid = trim($info_worker_openid['openid']);

		// // 整合数据
		$info = array(
			'uniacid'	=>	$uniacid,
			'code'		=>	$code,
			'name'		=>	$name,
			'price'		=>	$price,
			'goods_id'	=>	$goods_id,
			'shop'		=>	$shop,
			'buyer_uid'	=>	$buyer_uid,
			'buyer_phone'	=>	$buyer_phone,
			'buyer_name'	=>	$buyer_name,
			'buyer_openid'	=>	trim($_W['openid']),
			'worker_uid'	=>	$worker_uid,
			'worker_phone'=>	$worker_phone,
			'worker_name'	=>	$worker_name,
			'worker_openid'=>	$worker_openid,
			'addtime'	=>	$addtime,
			'time'		=>	$time,
			'state'		=>	$state
		);
		// print_r($info);
		// 发送信息
		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );
		// 通过goodsid查询goodsname
		$sql_goods_name = 'SELECT name ,price FROM '.tablename('reservation_goods').' WHERE uniacid = '.$uniacid.' AND id = '.$info['goods_id'];
		$req_goods_name = pdo_fetch($sql_goods_name);
		$goods_name = $req_goods_name['name'];
		$goods_price = $req_goods_name['price'];
		// // 将 姓名 ，电话 信息 添加到原表中
		$where_member = array(
			'uniacid' 	=>	$uniacid,
			'uid'		=>	$buyer_uid
		);
		$info_member = array(
			'mobile'	=>	$buyer_phone,
			'realname'	=>	$buyer_name
		);
		$req_member = pdo_update('mc_members' , $info_member , $where_member);
		// 将数据添加到数据库
		$req = pdo_insert('reservation',$info);
		if(!$req){die('预订失败！' );}

		// // 发送成功信息
		$id = intval(pdo_insertid($req));

		$sql = 'SELECT * FROM '.tablename('reservation')  . ' WHERE uniacid = '.$uniacid. ' AND id  = '.$id;
		$info = pdo_fetch($sql);
		if (!$info){die('您的订单还没有预购成功！请检查订单信息！');};

		$touser_buyer = trim($info['buyer_openid']);
		$touser_worker = trim($info['worker_openid']);
		$time = $info['time'];
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => '您好，欢迎使用预订系统预订商品！',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);
		$postdata_worker = array(
			'first' => array(
				'value' => '您好，您有新的预订需要查看！',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);
		$url_buyer = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url_buyer .= '&id='.$id;
		$url_worker = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowResWorker&m=yue_reservation';
		$url_worker .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url_buyer, $topcolor = '#FF683F');
		$req_message_worker = $account->sendTplNotice($touser_worker, $tpl_id_short, $postdata_worker, $url_worker, $topcolor = '#FF683F');
		if ($req_message_buyer and $req_message_worker) {
			return die('您已成功预订！请等待按摩师确认！');
		}else{
			return die('对不起！您的预订失败！请重新填写信息进行预订!');
		}
	}

// 回馈页面
	// 收到预订后 粉丝
	public function doMobileShowRes()
	{
		global $_W , $_GPC;
		checkauth();
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$buyer_id = intval($_W['member']['uid']);
		$sql_res = 'SELECT * FROM '.tablename('reservation').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$info['res'] = pdo_fetch($sql_res);
		$g_id = $info['res']['goods_id'];
		$sql_goods = 'SELECT * FROM '.tablename('reservation_goods').' WHERE uniacid = '.$uniacid.' AND id = '.$g_id;
		$info['goods'] = pdo_fetch($sql_goods);
		include $this->template('showres');
	}

	// 收到预订后 按摩师
	public function doMobileShowResWorker()
	{
		global $_W , $_GPC;
		checkauth();
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$buyer_id = intval($_W['member']['uid']);
		$sql_res = 'SELECT * FROM '.tablename('reservation').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$info['res'] = pdo_fetch($sql_res);
		$g_id = $info['res']['goods_id'];
		$sql_goods = 'SELECT * FROM '.tablename('reservation_goods').' WHERE uniacid = '.$uniacid.' AND id = '.$g_id;
		$info['goods'] = pdo_fetch($sql_goods);
		include $this->template('showresworker');
	}

	// 订单取消(预订人)
	public function doMobileOrderFail()
	{
		global $_GPC , $_W;
		checkauth();

		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$sql_update = 'UPDATE '.tablename('reservation').' set state = "buyerfail"'.' , overtime = '.time().' WHERE uniacid = '.$uniacid .' AND id =  '.$id;
		$req = pdo_query($sql_update);
		if(!$req){message('取消失败!请重新操作!','','error');}

		$sql = 'SELECT * FROM '.tablename('reservation').' WHERE uniacid = '.$uniacid.' AND id = '.$id;
		$info = pdo_fetch($sql);

		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );
		$touser_buyer = trim($info['buyer_openid']);
		$touser_worker = trim($info['worker_openid']);
		$time = $info['time'];
		$goods_name = trim($info['name']);
		$goods_price = trim($info['price']);
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => '您的预订已经取消！',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);
		$postdata_worker = array(
			'first' => array(
				'value' => '您好，您有订单被取消！您可以联系 '.$info['buyer_name'].' ('.$info['buyer_phone'].')获取具体情况!',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);
		$url_buyer = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url_buyer .= '&id='.$id;
		$url_worker = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowResWorker&m=yue_reservation';
		$url_worker .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url_buyer, $topcolor = '#FF683F');
		$req_message_worker = $account->sendTplNotice($touser_worker, $tpl_id_short, $postdata_worker, $url_worker, $topcolor = '#FF683F');
		if ($req_message_buyer and $req_message_worker) {
			return message('您已成功取消预订！');
		}else{
			return message('对不起！您的操作失败！请重新取消!');
		}
	}

/*
 电脑端(doWeb) 所有方法
*/

// 订单表
	// 接受 预订
	public function doWebWorkerAccept()
	{
		global $_GPC , $_W;
		checklogin;
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_GPC['openid']);
		if(!isset($id) or empty($id) or $id == '' ){message('查找不到订单ID！','','error');}
		$sql = 'UPDATE '.tablename('reservation').' set state = "accept"'.' , overtime = '.time().' WHERE uniacid = '.$uniacid .' AND id =  '.$id;
		$req = pdo_query($sql);
		if (!$req){message('操作未完成！请重新操作！','','error');};

		// 发送信息
		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );

		$sql = 'SELECT * FROM '.tablename('reservation')  . ' WHERE uniacid = '.$uniacid. ' AND id  = '.$id;
		$info = pdo_fetch($sql);
		$goods_name = $info['name'];
		$goods_price = $info['price'];
		$time = $info['time'];
		$touser_buyer = $openid;
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => '您好！您预订的订单以成功被按摩师接受！请在规定时间内到达.谢谢亲！',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);

		$url = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url, $topcolor = '#FF683F');
		return $req_message_buyer ? message('确认接单！' ,'' , 'success') : message('取消失败！' ,'' ,'error');
	}
	// 成功 预订
	public function doWebOrderSuccess()
	{
		global $_GPC , $_W;
		checklogin;
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_GPC['openid']);
		$content = trim($_GPC['content']);

		// 更改数据库状态
		$sql = 'UPDATE '.tablename('reservation').' set state = "ordered"'.' , overtime = '.time().' WHERE uniacid = '.$uniacid .' AND id =  '.$id;
		$req = pdo_query($sql);
		if (!$req){message('操作未完成！请重新操作！','','error');};
		// 发送信息
		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );

		$sql = 'SELECT * FROM '.tablename('reservation')  . ' WHERE uniacid = '.$uniacid. ' AND id  = '.$id;
		$info = pdo_fetch($sql);
		$goods_name = $info['name'];
		$goods_price = $info['price'];
		$time = $info['time'];

		$touser_buyer = $openid;
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => '您的订单已完成!',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);

		$url = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url, $topcolor = '#FF683F');
		return $req_message_buyer ? message('订单完成！' ,'' , 'success') : message('信息发送失败！' ,'' ,'error');
	}
	// 取消 预订
	public function doWebOrderFail()
	{
		global $_GPC , $_W;
		checklogin();
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_GPC['openid']);
		if(!isset($id) or empty($id) or $id == '' ){message('查找不到订单ID！','','error');}
		$sql = 'UPDATE '.tablename('reservation').' set state = "workerfail"'.' , overtime = '.time().' WHERE uniacid = '.$uniacid .' AND id =  '.$id;
		$req = pdo_query($sql);
		if (!$req){message('操作未完成！请重新操作！','','error');};

		// 发送信息
		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );

		$sql = 'SELECT * FROM '.tablename('reservation')  . ' WHERE uniacid = '.$uniacid. ' AND id  = '.$id;
		$info = pdo_fetch($sql);
		$goods_name = $info['name'];
		$goods_price = $info['price'];
		$time = $info['time'];
		$touser_buyer = $openid;
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => '您好！您预订的订单以被管理员取消,请您联系店方或者再发一单！',
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);

		$url = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url, $topcolor = '#FF683F');
		return $req_message_buyer ? message('取消成功！' ,'' , 'success') : message('取消失败！' ,'' ,'error');
	}
	// 显示 预订信息 所有
	public function doWebReservation() {
		global $_W , $_GPC;
		checklogin();
		// echo $this->createMobileUrl('index');
		$page_size = 12;
		$uniacid = intval($_W['uniacid']);
		$where['type'] = intval($_GPC['type']);
		$where['keywords'] = trim($_GPC['keywords']);
		$where['page'] = max(1, $_GPC['page']);
		$current_page = ($where['page'] - 1) * $page_size;
		$condition = 'uniacid = ' . intval($_W['uniacid']);
        // 如果两个值为空
		if ($where['type'] && $where['keywords']) {
			switch ($where['type']) {
				case 1:
				$type = 'code';
				break;

				case 2:
				$type = 'name';
				break;
				case 3:
				$type = 'buyer_phone';
				break;
			}
			$condition .= ' AND ' . $type . ' LIKE \'%' . $where['keywords'] . '%\'';
		}
		$sql = 'SELECT * FROM ' . tablename('reservation') . ' WHERE ' . $condition . ' ORDER BY id DESC' . ' LIMIT ' . $current_page . ',' . $page_size;
		$sql_count = 'SELECT COUNT(id) FROM ' . tablename('reservation') . ' WHERE ' . $condition;
		$count = pdo_fetchcolumn($sql_count);
		$page = pagination($count, $where['page'], $page_size);
		$info = pdo_fetchall($sql);

		$info = pdo_fetchall($sql);
		include $this->template('reservation');
	}
	// 发送信息
	public function doWebSendMessage()
	{
		global $_GPC , $_W;
		checklogin;
		$id = intval($_GPC['goods_id']);
		$uniacid = intval($_W['uniacid']);
		$openid = trim($_GPC['openid']);
		$content = trim($_GPC['content']);
		// 发送信息
		load()->classs( 'weixin.account' );
		$account = new WeiXinAccount( $_W['account'] );

		$sql = 'SELECT * FROM '.tablename('reservation')  . ' WHERE uniacid = '.$uniacid. ' AND id  = '.$id;
		$info = pdo_fetch($sql);
		$goods_name = $info['name'];
		$goods_price = $info['price'];
		$touser_buyer = $openid;
		$tpl_id_short = 'qWYr_wFTdO1ew2ekDQJIpw-XnKbLoJUOaAv9Te4EOEk';
		$postdata_buyer = array(
			'first' => array(
				'value' => $content,
				'color' => '#FF3030'
			),
			'product' => array(
				'value' => $goods_name,
				'color' => '#FF3030'
			),
			'price' => array(
				'value' => $goods_price.'元',
				'color' => '#FF3030'
			),
			'time' => array(
				'value' => date('Y-m-d h:i:s' , $time),
				'color' => '#FF3030'
			)
		);

		$url = $_W['siteroot'].'/index.php?i=5&c=entry&do=ShowRes&m=yue_reservation';
		$url .= '&id='.$id;
		$req_message_buyer = $account->sendTplNotice($touser_buyer, $tpl_id_short, $postdata_buyer, $url, $topcolor = '#FF683F');
		return $req_message_buyer ? die('发送成功！') : die('发送失败！');
	}

//商品表
	// 方法 添加 商品 信息
	public function doWebDoAddGoods()
	{
		global $_GPC , $_W;
		checklogin();
		$name = trim($_GPC['name']);
		$thumb = trim($_GPC['thumb']);
		$price = number_format(floatval($_GPC['price']) , 2);
		$description = trim($_GPC['description']);
		$shops = $_GPC['shops'];
		$workers = $_GPC['workers'];
		$content = trim($_GPC['content']);
		$uniacid = intval($_W['uniacid']);
		if (!isset($name) or $name == '') die('商品名称不能为空！');
		if (!isset($price) or $price == '') die('商品价格不能为空！');
		if (!isset($shops) or $shops == '') die('所在商铺不能为空！');
		if (!isset($workers) or $workers == '') die('按摩师不能为空！');
		// 将shop 和 works 的字段存储为 以 '，' 分割的字符串
		$str_shops = '';
		foreach ($shops as $r) {
			$str_shops .= $r . ',';
		}
		$str_shops = substr($str_shops,0,strlen($shops)-1);
		foreach ($workers as $r) {
			$str_workers .= $r . ',';
		}
		$str_workers = substr($str_workers,0,strlen($workers)-1);
		// 整合数据
		$info = array(
			'uniacid' 	=>	 $uniacid,
			'name' 		=>	 $name,
			'thumb' 	=>	 $thumb,
			'price' 		=>	 $price,
			'description' 	=>	 $description,
			'shops' 		=>	 $str_shops,
			'workers' 	=>	 $str_workers,
			'content' 	=>	 $content,
		);
		// 将数据添加到数据库
		$req = pdo_insert('reservation_goods' , $info);
		return $req ? die('添加成功！') : die('添加失败！');
	}
	// 方法 删除 商品 信息
	public function doWebDelGoods()
	{
		global $_GPC , $_W;
		checklogin();
		$id = intval($_GPC['id']);
		$where = array(
			'uniacid' => intval($_W['uniacid']),
			'id' => $id,
		);
		// 查找图片文件
		// $sql = 'SELECT thumb FROM '.tablename('reservation_goods').' WHERE uniacid = '.$where['uniacid'].' AND id = '.$where['id'];
		// $info = pdo_fetch($sql);
		// $thumb = '/attachment'.$info['thumb'];
		// // 删除文件
		// load()->func('file');
		// file_delete( $thumb );
		$req = pdo_delete('reservation_goods' , $where);
		return $req ? die('删除成功！'.$thumb) : die('删除失败！'.$thumb);
	}
	// 方法 修改 商品 信息
	public function doWebDoEditGoods()
	{
		global $_GPC , $_W;
		checklogin();
		// 接收传值
		$id = intval($_GPC['id']);
		$name = trim($_GPC['name']);
		$thumb = trim($_GPC['thumb']);
		$price = floatval($_GPC['price']);
		$description = trim($_GPC['description']);
		$shops = $_GPC['shops'];
		$workers = $_GPC['workers'];
		$content = trim($_GPC['content']);
		// // 判断空值
		if (!isset($name) or $name == '') die('商品名称不能为空！');
		if (!isset($price) or $price == '') die('商品价格不能为空！');
		if (!isset($shops) or $shops == '') die('所在商铺不能为空！');
		if (!isset($workers) or $workers == '') die('按摩师不能为空！');
		// 将shop 和 works 的字段存储为 以 '，' 分割的字符串
		$str_shops = '';
		foreach ($shops as $r) {
			$str_shops .= $r . ',';
		}
		$str_shops = substr($str_shops,0,strlen($shops)-1);
		foreach ($workers as $r) {
			$str_workers .= $r . ',';
		}
		$str_workers = substr($str_workers,0,strlen($workers)-1);
		// 整合数据
		$info = array(
			'name' 		=>	 $name,
			'thumb' 	=>	 $thumb,
			'price' 		=> 	$price,
			'description' 	=> 	$description,
			'shops' 		=> 	$str_shops,
			'workers' 	=> 	$str_workers,
			'content' 	=> 	$content,
		);
		// 执行存储方法
		$where = array(
			'uniacid' => intval($_W['uniacid']),
			'id' => $id,
		);
		$req = pdo_update('reservation_goods' , $info , $where);
		return $req ? die('修改成功！') : die('修改失败！');
	}
	// 显示 添加 商品 信息
	public function doWebAddNewGoods()
	{
		checklogin();
		// 读取所有商铺信息
		$sql_shops = 'SELECT id,name FROM '.tablename('reservation_shop');
		$info['shops'] = pdo_fetchall($sql_shops);
		// 读取所有按摩师信息
		$sql_worker = 'SELECT id,name FROM '.tablename('reservation_worker');
		$info['workers'] = pdo_fetchall($sql_worker);
		include $this->template('addGoods');
	}
	// 显示 修改 商品 信息
	public function doWebEditGoods()
	{
		global $_GPC , $_W;
		checklogin();
		$id = intval($_GPC['id']);
		$sql = 'SELECT * FROM '.tablename('reservation_goods').' WHERE uniacid = '.intval($_W['uniacid']). ' AND id = ' .$id;
		// 基本信息
		$info['goods'] = pdo_fetch($sql);
		// 商铺信息
		$sql_shop = 'SELECT * FROM '.tablename('reservation_shop');
		$info['shops'] = pdo_fetchall($sql_shop);
		// 按摩师信息
		$sql_worker = 'SELECT * FROM '.tablename('reservation_worker');
		$info['workers'] = pdo_fetchall($sql_worker);
		include $this->template('editgoods');
	}
	// 显示 商品 列表
	public function doWebGoods() {
		global $_GPC , $_W;
		checklogin();
		$page_size = 10;
		// 获取传值
		$where['type'] = intval($_GPC['type']);
		$where['keywords'] = trim($_GPC['keywords']);
		$where['page'] = max(1,$_GPC['page']);
		$current_page = ($where['page'] - 1) * $page_size;
		$condition = 'uniacid = ' . intval($_W['uniacid']);
		// 如果两个值为空
		if ($where['type'] && $where['keywords']) {
			switch ($where['type']) {
				case 1:
					$type = 'id';
					break;

				case 2:
					$type ='name';
					break;
			}
			$condition .= ' AND ' . $type . ' LIKE \'%' . $where['keywords'] . '%\'';
		}
		$sql = 'SELECT * FROM ' . tablename('reservation_goods'). ' WHERE ' .$condition .' ORDER BY id DESC' . ' LIMIT ' . $current_page . ',' . $page_size;
		$info = pdo_fetchall( $sql );
		$sql_count = 'SELECT COUNT(id) FROM ' . tablename( 'reservation_goods' ) . ' WHERE ' . $condition;
		$count     = pdo_fetchcolumn( $sql_count );
		$page = pagination($count , $where['page'], $page_size );
		include $this->template('goods');
	}

//按摩师 表
	// 方法 添加 按摩师
	public function doWebAddworker()
	{
		global $_GPC , $_W;
		checklogin();
		// 读取传入值并存储
		$uid = intval($_GPC['uid']);
		$name = trim($_GPC['name']);
		$phone = trim($_GPC['phone']);
		$shopid = intval($_GPC['shop']);
		$thumb = trim($_GPC['thumb']);
		$description = trim($_GPC['description']);
		// 根据uid获取mc_mapping_fans表中的openid字段
		$sql_fans = "SELECT openid FROM ".tablename('mc_mapping_fans') . " WHERE uid = ".$uid;
		$info_fans = pdo_fetch($sql_fans);
		$openid = $info_fans['openid'];
		// 判断原表中是否存在uid相同的信息
		$sql_uid = "SELECT uid FROM ".tablename('reservation_worker')." WHERE uid = ".$uid ;
		$req_uid = pdo_fetch($sql_uid);
		if ($req_uid) die('请不要重复输入按摩师信息!');
		// 判断传入值是否为空
		if (!isset($uid) or $uid == '') die('UID不能为空！');
		if (!isset($name) or $name == '') die('姓名不能为空！');
		if (!isset($phone) or $phone == '') die('联系方式不能为空！');
		if (!isset($shopid) or $shopid == '') die('店铺不能为空！');
		// 电话正则判断
		$isMobile="/^1[34578]\d{9}$/"; ;
		if (!preg_match($isMobile , $phone)) die('您输入的电话格式不正确！');
		// 归总数据
		$info = array(
			'uid' => $uid,
			'uniacid' 	=>	$_W['uniacid'],
			'name' 		=>	$name,
			'phone' 	=>	$phone,
			'shopid' 	=>	$shopid,
			'openid' 	=>	$openid,
			'thumb'		=> 	$thumb,
			'description'		=> 	$description,
		);
		// 执行存储方法
		$req = pdo_insert('reservation_worker' , $info);
		$req ? die('添加成功！') : die( '添加失败!');
	}
	// 方法 删除 按摩师
	public function doWebDelWorker()
	{
		global $_GPC ,$_W;
		checklogin();
		$id = intval($_GPC['id']);
		$uniacid = intval($_W['uniacid']);
		$where = array(
			'id' => $id,
			'uniacid'	=>	$uniacid
		);
		$req = pdo_delete("reservation_worker" , $where);
		$req ? die('删除成功！') : die("删除失败！");
	}
	// 方法 修改 按摩师 信息
	public function doWebDoEdit()
	{
		global $_GPC , $_W;
		checklogin();
		// 读取传入值并存储
		$uniacid = intval($_W['uniacid']);
		$uid = intval($_GPC['uid']);
		$name = trim($_GPC['name']);
		$phone = trim($_GPC['phone']);
		$shopid = intval($_GPC['shop']);
		$thumb = trim($_GPC['thumb']);
		$description = trim($_GPC['description']);
		// 判断空值和phone的正则
		if (!isset($uid) or $uid == '') die('UID不能为空！');
		if (!isset($name) or $name == '') die('姓名不能为空！');
		if (!isset($phone) or $phone == '') die('联系方式不能为空！');
		if (!isset($shopid) or $shopid == '') die('店铺不能为空！');
		// 手机号正则
		$isMobile="/^1[34578]\d{9}$/";
		if (!preg_match($isMobile , $phone)) die('您输入的电话格式不正确！');
		// 查询 uid , uniacid 索引
		$where = array(
			'uid' => $uid,
			'uniacid' => $uniacid,
		);
		// 要修改的信息
		$info = array(
			'name' 		=>	 $name,
			'phone' 	=>	 $phone,
			'shopid' 	=>	 $shopid,
			'thumb' 	=>	 $thumb,
			'description' 	=>	 $description,
		);
		$req = pdo_update('reservation_worker' , $info , $where);
		return $req ? die('修改成功！') : die('修改失败！');
	}
	// 显示 按摩师 列表
	public function doWebWorker() {
		checklogin();
		$sql_worker = "SELECT * FROM " . tablename('reservation_worker') . ' ORDER BY id DESC';
		$info['worker'] = pdo_fetchall($sql_worker);
		$sql_shop = "SELECT id,name FROM " .tablename('reservation_shop') . ' ORDER BY id DESC';
		$info['shop'] = pdo_fetchall($sql_shop);
		include $this->template("worker");
	}
	// 显示 修改 按摩师 信息
	public function doWebEditWorker()
	{
		global $_GPC , $_W;
		checklogin();
		$uniacid = $_W['uniacid'];
		$id = $_GPC['id'];
		$where = " WHERE id = ".$id ." AND uniacid = " .$uniacid;
		$sql = "SELECT * FROM ".tablename('reservation_worker').$where;
		$info = pdo_fetch($sql);
		return $info ? json_encode($info) : False;
	}

//商铺 表
	// 方法 添加 商铺
	public function doWebShop_add()
	{
		global $_GPC , $_W;
		checklogin();
		$info = array(
			'uniacid' 	=>	 intval($_W['uniacid']),
			'name' 		=>	 trim($_GPC['name']),
			'add' 		=>	 trim($_GPC['add']),
			'description' 	=>	 trim($_GPC['description'])
		);
		$req = pdo_insert('reservation_shop',$info);
		return boolval($req);
	}
	// 方法 删除 商铺
	public function doWebDelShop()
	{
		global $_GPC;
		checklogin();
		$where = array(
			'id' => intval($_GPC['id']),
		);
		$req = pdo_delete( 'reservation_shop' , $where );
		return boolval($req);
	}
	// 方法 修改商铺
	public function doWebShop_edit()
	{
		global $_GPC , $_W;
		checklogin();
		$where = array(
			'id' 	=>	 intval($_GPC['id']),
			'uniacid' => 	intval($_W['uniacid']),
		);
		$info = array(
			'name' 	=> trim($_GPC['name']),
			'add' 	=> trim($_GPC['add']),
			'description' => trim($_GPC['description']),
		);
		$req = pdo_update('reservation_shop' ,  $info , $where );
		return boolval($req);
	}
	// 显示 商铺 列表
	public function doWebShop() {
		checklogin();
		$sql = "SELECT * FROM ".tablename('reservation_shop') . 'ORDER BY id DESC';
		$info = pdo_fetchall($sql);
		include $this->template('shop');
	}

/*
 Ajax 区
*/

 	// 选择 商品
	public function doMobileChoseGoods()
	{
		global $_GPC , $_W;
		$uniacid = intval($_W['uniacid']);
		$shop = intval($_GPC['id']);
		$sql = 'SELECT * FROM '.tablename('reservation_goods').' WHERE uniacid = '.$uniacid;
		$info_shops = pdo_fetchall($sql);
		// 整合shops
		for ($i=0; $i < count($info_shops); $i++) {
			$req[] = in_array( $shop , explode( ',' , $info_shops[$i]['shops'] ) );
		}
		for ($i=0; $i < count($req); $i++) {
			if ($req[$i] == 1) {
				$did[] = $i;
			}
		}
		// 将 info 赋值
		for ($i=0; $i < count($did); $i++) {
			$id = $did[$i];
			$info[] = $info_shops[$id];
		}
		return $info ? json_encode($info) : False;
	}
	// 选择 按摩师
	public function doMobileChoseWorker()
	{
		global  $_GPC , $_W;
		checkauth();
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC['id']);
		$sql = 'SELECT id , name FROM '.tablename('reservation_worker') . ' WHERE shopid = '.$id;
		$info = pdo_fetchall($sql);
		return $info ? json_encode($info) : False;
	}
	// Ajax 获取 按摩师
	public function doWebGetWorker()
	{
		global $_GPC , $_W;
		$ids = $_GPC['id'] ? $_GPC['id'] : False;
		$uniacid = intval($_W['uniacid']);
		// 如果id为空
		if (!$ids) return False;
		// 获取 reservation_worker 表中和该 shopid 相符的所有 按摩师 的 id
		$where = ' WHERE uniacid = '.$uniacid.' AND (';
		foreach ($ids as $r) {
			$where .= ' shopid = '.$r . ' OR ';
		}
		$where = substr($where,0,strlen($where)-3);
		$where .= ')';
		$sql = 'SELECT id FROM '.tablename('reservation_worker').$where;
		$info = pdo_fetchall($sql);
		return $info ? json_encode($info) : False;
	}
	// ajax读取商铺信息（修改方法用）
	public function doWebGetShop()
	{
		global $_GPC;
		checklogin();
		$sql = 'SELECT * FROM'.tablename('reservation_shop').'WHERE id = '.intval($_GPC['id']);
		$info = pdo_fetch($sql);
		return $info ? json_encode($info) : False;
	}
	// Ajax 获取 添加 按摩师 页面 中的 按摩师
	public function doWebGetWorkerInfo()
	{
		global $_GPC;
		checklogin();
		$id = intval($_GPC['id']);
		$where = 'WHERE uid = '.$id;
		$sql = "SELECT * FROM ".tablename('mc_members') . $where;
		$info = pdo_fetch($sql);
		return $info ? json_encode($info) : False;
	}
}
