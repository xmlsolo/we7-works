<?php

/**
 * 积分签到模块微站定义
 *
 * @author zwent
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');

include 'lib/global.php';

class Weidu_signModuleSite extends WeModuleSite
{

    public function doMobiledosign()
    {
        global $_W, $_GPC;
//        checkauth();
        $pre_time = time(); // 今天的日期
        $next_time = $pre_time + 3600;  // 明天的日期
        $order = ' ORDER BY id DESC'; // 倒数第一个字段
        $db_sign_conf = 'weidu_sign_config';
        $db_sign_list = 'weidu_sign_list';
        $sql_sign_conf = 'SELECT * FROM ' . tablename($db_sign_conf);
        $sql_sign_list = 'SELECT * FROM ' . tablename($db_sign_list) . $order;
        $req_sign_conf = pdo_fetch($sql_sign_conf);
        $req_sign_list = pdo_fetchall($sql_sign_list);
//        判断第一次签到 和 非第一次签到 的时间
        $req_sign_list ? $req_time = $req_sign_list[0]['signtime'] : $req_time = time();
        $arr_sign_type = explode(',', $req_sign_conf['signtype']);
        $arr_sign_rand = explode(',', $req_sign_conf['jltype']);
//        签到时间判断
        if (!$req_time < $pre_time AND $req_sign_list) die('您今天已经签到过了~明天再来吧');
//        积分奖励处理
        if (in_array(1, $arr_sign_type)) $jf = $this->jfsign();
//        随机奖励处理
        if (in_array(2, $arr_sign_type) and in_array(1, $arr_sign_rand)) $this->randsign('hb');
        if (in_array(2, $arr_sign_type) and in_array(2, $arr_sign_rand)) $this->randsign('kq');
        if (in_array(2, $arr_sign_type) and in_array(3, $arr_sign_rand)) $this->randsign('sw');
//        return die('签到成功');
    }

    private function randpoint($point)
    {
        $req = rand(1, 100);
        return $req < $point ? true : false;
    }

//    积分奖励
    private function jfsign()
    {
//        checkauth();
        $db = 'weidu_sign_jfconf';
        $sql = 'SELECT * FROM ' . tablename($db);
        $req = pdo_fetch($sql);
        if ($req['random']) {
            if ($this->randpoint(intval($req['point']))) {
                $jf = rand(intval($req['randintmin']), intval($req['randintmax']));
                echo '您以获得 ' . $jf . ' 点积分!';
            }
        };
        echo '您以获得 ' . intval($req['addjf']) . ' 积分奖励!';
    }

//    随机奖励
    private function randsign($type)
    {
//        checkauth();
        $db_kq = 'weidu_sign_kqconf';
        $db_hb = 'weidu_sign_hbconf';
        switch ($type) {
            case 'hb':
                echo '红包奖励!';
                break;
            case 'kq':
                echo '卡券奖励!';
                break;
            case 'sw':
                echo '实物奖励!';
                break;
            default:
                echo '找不到奖励类型!';
                break;
        }
    }

    public function doMobilesign()
    {
        include $this->template('index');
    }

    public function doWebConfig()
    {
        echo $this->createMobileUrl('sign');
        global $_W, $_GPC;
        checklogin();
        $db = tablename('weidu_sign_config');
        $sql = 'SELECT * FROM ' . $db;
        $data = pdo_fetch($sql);
        include $this->template("config");
    }

    public function doWebdelkq()
    {
        global $_W, $_GPC;
        checklogin();
        $db = 'weidu_sign_kqconf';
        $id = intval($_GPC['id']);
        $uniacd = intval($_W['uniacid']);
        $where = array(
            'id' => $id,
            'uniacid' => $uniacd
        );
        $req = pdo_delete($db, $where);
        return $req ? message('删除成功!', '', 'success') : message('删除失败!', '', 'error');
    }

    public function doWebkqconf()
    {
        global $_W, $_GPC;
        checklogin();
        $db = tablename('coupon');
        $sql = 'SELECT * FROM ' . $db;
        $order = ' ORDER BY id DESC';
        $types = $_GPC['type'];
        $where = ' WHERE ';
        $type = '';
        if ($this->is_null($types)) {
            $sql .= $order;
        } else {
            foreach ($types as $v) {
                $type .= ' type = "' . $v . '" OR ';
            }
            $type = substr($type, 0, strlen($type) - 3);
            $where .= '( ' . $type . ' ) ';
            $sql .= $where . 'AND status = 3 AND uniacid = ' . intval($_W['uniacid']) . $order;
        }
        $data = pdo_fetchall($sql);
        include $this->template('kqconf');
    }

    public function doWebkqlist()
    {
        global $_W, $_GPC;
        checklogin();
        $db = 'weidu_sign_kqconf';
        $sql = 'SELECT * FROM ' . tablename($db);
        $data = pdo_fetchall($sql);
        include $this->template('kqlist');
    }

    public function doWebdokqconfadd()
    {
        global $_W, $_GPC;
        checklogin();
        $ids = $_GPC['id'];
        $point = $_GPC['point'];
        $name = $_GPC['name'];
        if (!$ids) message('请选择卡券!', '', 'error');
        if (!$point) message('请您填写获得几率!', 'error');
//        删除 元数据库 中的 所有 信息
        $db = 'weidu_sign_kqconf';
        $sql = 'SELECT id FROM ' . tablename($db);
        $exist = pdo_fetchall($sql);
        foreach ($exist as $v) {
            $req = pdo_delete($db, array('id' => $v));
        }
//        添加 新的信息
        for ($i = 0; $i < count($ids); $i++) {
            $data = array(
                'uniacid' => intval($_W['uniacid']),
                'cardid' => intval($ids[$i]),
                'name' => trim($name[$i]),
                'point' => intval($point[$i])
            );
            $req = pdo_insert($db, $data);
        }
        return $req ? message('修改成功!', '', 'success') : message('修改失败!', '', 'error');
    }

    public function doWebjfconf()
    {
        global $_W, $_GPC;
        checklogin();
        $db = 'weidu_sign_jfconf';
        $sql = 'SELECT * FROM ' . tablename($db);
        $data = pdo_fetch($sql);
        include $this->template("jfconf");
    }

    public function doWebhbconf()
    {
        global $_W, $_GPC;
        checklogin();
        $db = 'weidu_sign_hbconf';
        $sql = "SELECT * FROM " . tablename($db) . ' ORDER BY id DESC';
        $data = pdo_fetchall($sql);
        include $this->template('hbconf');
    }

    private function is_null($parm)
    {
        return (!$parm or empty($parm) or $parm == '' or !isset($parm)) ? true : false;
    }

    public function doWebdoaddhb()
    {
        global $_GPC, $_W;
        checklogin();
        $name = trim($_GPC['name']);
        $price = floatval($_GPC['price']);
        $num = intval($_GPC['num']);
        $point = intval($_GPC['point']);
        if ($this->is_null($name)) message('红包名称不能为空', '', 'error');
        if ($this->is_null($price)) message('红包金额不能为空', '', 'error');
        if ($this->is_null($num)) message('红包数量不能为空', '', 'error');
        if ($this->is_null($point)) message('获得几率不能为空', '', 'error');
        $data = array(
            'uniacid' => intval($_W['uniacid']),
            'name' => $name,
            'price' => $price,
            'point' => $point,
            'num' => $num
        );
        $req = pdo_insert('weidu_sign_hbconf', $data);
        return $req ? message('红包添加成功!', '', 'success') : message('红包添加失败!', '', 'error');
    }

    public function doWebdodelhb()
    {
        global $_W, $_GPC;
        checklogin();
        $id = intval($_GPC['id']);
        if ($this->is_null($id)) message('找不到您要删除的信息!', '', 'error');
        $where = array(
            'uniacid' => intval($_W['uniacid']),
            'id' => $id
        );
        $req = pdo_delete('weidu_sign_hbconf', $where);
        return $req ? message('删除成功!', '', 'success') : message('删除失败!', '', 'error');
    }

    public function doWebedithb()
    {
        global $_W, $_GPC;
        checklogin();
        $db = tablename('weidu_sign_hbconf');
        $sql = 'SELECT * FROM ' . $db . ' WHERE id = ' . intval($_GPC['id']) . ' AND uniacid = ' . intval($_W['uniacid']);
        $data = pdo_fetch($sql);
        return json_encode($data);
    }

    public function doWebdoedithb()
    {
        global $_W, $_GPC;
        checklogin();
        $id = intval($_GPC['id']);
        $name = trim($_GPC['name']);
        $price = floatval($_GPC['price']);
        $num = intval($_GPC['num']);
        $point = intval($_GPC['point']);
        if ($this->is_null($id)) message('找不到修改信息', '', 'error');
        if ($this->is_null($name)) message('红包名称不能为空', '', 'error');
        if ($this->is_null($price)) message('红包金额不能为空', '', 'error');
        if ($this->is_null($num)) message('红包数量不能为空', '', 'error');
        if ($this->is_null($point)) message('获得几率不能为空', '', 'error');
        $where = array(
            'uniacid' => intval($_W['uniacid']),
            'id' => $id
        );
        $data = array(
            'name' => $name,
            'price' => $price,
            'point' => $point,
            'num' => $num
        );
        $req = pdo_update('weidu_sign_hbconf', $data, $where);
        return $req ? message('修改成功!', '', 'success') : message('修改失败!', '', 'error');
    }

    public function doWebdojfconf()
    {
        global $_GPC, $_W;
        checklogin();
        $db = tablename('weidu_sign_jfconf');
        $sql = 'SELECT * FROM ' . $db;
        $daymin = intval($_GPC['daymin']);
        $daymax = intval($_GPC['daymax']);
        $addjf = intval($_GPC['addjf']);
        $random = intval($_GPC['random']);
        $point = intval($_GPC['point']);
        $randmin = intval($_GPC['randmin']);
        $randmax = intval($_GPC['randmax']);
        if (!isset($daymin) or !$daymin or $daymin == 0 or !isset($daymax) or !$daymax or $daymax == 0) message('您的天数填写错误', '', 'error');
        if (!isset($addjf) or !$addjf or $addjf == 0) message('您的 获得积分 填写错误!', '', 'error');
        if ($random and (!isset($randmin) or !$randmin or $randmin == 0 or !isset($randmax) or !$randmax or $randmax == 0 or ($randmin >= $randmax) or ($randmax <= $randmin)) and $this->is_null($point)) message('您的随机积分填写错误', '', 'error');
        $data = array(
            'daymin' => intval($_GPC['daymin']),
            'daymax' => intval($_GPC['daymax']),
            'addjf' => intval($_GPC['addjf']),
            'random' => intval($_GPC['random']),
            'point' => intval($_GPC['point']),
            'randintmin' => intval($_GPC['randmin']),
            'randintmax' => intval($_GPC['randmax'])
        );
        $exist = pdo_fetchall($sql);
        if ($this->is_null($exist)) {
            $data['uniacid'] = intval($_W['uniacid']);
            $req = pdo_insert('weidu_sign_jfconf', $data);
        } else {
            $where = array(
                'id' => $exist[0]['id'],
                'uniacid' => intval($_W['uniacid'])
            );
            $req = pdo_update('weidu_sign_jfconf', $data, $where);
        }
        return $req ? message('修改成功!', '', 'success') : message('修改失败!', '', 'error');
    }

    private function array_to_string($array)
    {
        $str = '';
        $count = count($array);
        foreach ($array as $v) {
            $str .= $v . ',';
        }
        $str = substr($str, 0, strlen($str) - 1);
        return $str;
    }

    public function doWebdoconfig()
    {
        global $_W, $_GPC;
        checklogin();
        $db = tablename('weidu_sign_config');
        $sql = 'SELECT * FROM ' . $db;
        // 判断 参数是否 含有 2
        $signtype = $_GPC['signtype'];
        $jltype = $_GPC['jltype'];
        if (!isset($signtype)) message('请选择签到类型', '', 'error');
        if (in_array(2, $signtype) and !isset($jltype)) message('请选择奖励类型', '', 'error');
        $str_sign = $this->array_to_string($signtype);
        $str_jl = $this->array_to_string($jltype);
        $data = array(
            'uniacid' => intval($_W['uniacid']),
            'signtype' => $str_sign,
            'jltype' => $str_jl,
        );
        $test = pdo_fetchall($sql);
        if (!$test) {
            $req = pdo_insert('weidu_sign_config', $data);
        } else {
            $where = array(
                'id' => $test[0]['id'],
            );
            $req = pdo_update('weidu_sign_config', $data, $where);
        }
        return $req ? message('设置成功!', '', 'success') : message('设置失败!', '', 'error');
    }

    public function doWebSignlist()
    {

        //这个操作被定义用来呈现 管理中心导航菜单

    }


    public function doWebPrizelist()
    {

        //这个操作被定义用来呈现 管理中心导航菜单

    }

}
