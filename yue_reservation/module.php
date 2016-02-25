<?php

/**
 * 预约模块定义
 *
 * @author zwent
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');



class Yue_reservationModule extends WeModule {

	public function fieldsFormDisplay($rid = 0) {
		global $_W, $_GPC;

		if ($rid) {
			$sql  = 'SELECT * FROM ' . tablename( 'reservation_reply' ) . ' WHERE rid = :rid';
			$item = pdo_fetch( $sql, array( ':rid' => $rid ) );
		}

		load()->func( 'tpl' );
		include $this->template( 'rule' );
	}

	public function fieldsFormValidate($rid = 0) {
		global $_W, $_GPC;

		if (!$_GPC['title']) return '请填写标题信息!';
		if (!$_GPC['thumb']) return '请上传封面图片!';

		return '';
	}

	public function fieldsFormSubmit($rid) {
		global $_W, $_GPC;

		$data = array(
			'rid'         => $rid,
			'title'       => trim($_GPC['title']),
			'thumb'       => trim($_GPC['thumb']),
			'description' => trim($_GPC['description']),
			'url'         => trim($_GPC['url'])
		);
		$id = pdo_fetchcolumn( "SELECT id FROM " . tablename( 'reservation_reply' ) . " WHERE rid = :rid", array( ':rid' => $rid ) );
		if (empty($id)) {
			$data['uniacid'] = $_W['uniacid'];
			pdo_insert( 'reservation_reply', $data );
		}
		else {
			pdo_update( 'reservation_reply', $data, array( 'id' => $id ) );
		}
	}

	public function ruleDeleted($rid) {
		$sql = 'SELECT * FROM ' . tablename( 'reservation_reply' ) . ' WHERE rid = :rid ';
		$data = pdo_fetch($sql, array(':rid' => $rid));
		if ($data) {
			load()->func('file');
			file_delete( $data['thumb'] );
		}
		pdo_delete( 'reservation_reply', 'rid = ' . $rid );
	}

}
