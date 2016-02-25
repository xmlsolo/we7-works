<?php

/**
 * 预约模块处理程序
 *
 * @author zwent
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');



class Yue_reservationModuleProcessor extends WeModuleProcessor {

	public function respond() {
		$content = $this->message['content'];
		// 这里定义此模块进行消息处理时的具体过程, 请查看微擎文档来编写你的代码
		// return $this->respImage();
		global $_W, $_GPC;

		$sql = 'SELECT * FROM ' . tablename( 'reservation_reply' ) . ' WHERE uniacid = :uniacid ORDER BY id DESC LIMIT 1';
		$result = pdo_fetch( $sql, array( ':uniacid' => $_W['uniacid'] ) );
		$data = array(
			'title'       => $result['title'],
			'description' => $result['description'],
			'picUrl'      => $result['thumb'],
			'url'         => $result['url']
		);

		return $this->respNews($data);
	}

}
