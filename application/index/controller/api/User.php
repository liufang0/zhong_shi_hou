<?php

// +----------------------------------------------------------------------
// | ThinkAdmins
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库3：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库3：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\index\controller\api;

use library\Controller;
use think\facade\Session;
use think\Db;
use Exception;

/**
 * 用户
 * Class Index
 * @package app\index\controller\api
 */
class User extends Controller
{

	/**
	 * @description：个人中心
	 * @date: 2020/5/13 0013
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function index() {
        $uid = input('uid');
        if (!$uid) $this->error('用户ID不能为空');
        //自动更新真实姓名 - 已禁用,保留注册时的实名信息
        // $real_name = Db::name('LcBank')->where(['uid' => $uid])->value('name');
        $this->user = Db::name('LcUser')->find($uid);
        // 只有当银行卡姓名存在且与用户姓名不同时才更新
        // if ($real_name && $this->user['name'] != $real_name) {
        //     Db::name('LcUser')->where(['id' => $uid])->update(['name' => $real_name]);
        // }
        $this->chicang = Db::name('LcOrder')->where("uid = $uid AND ostaus = 0")->sum('fee');
        $this->wait_money = $this->user['money']+$this->chicang;
        $this->msg = Db::name('LcMsg')->alias('msg')->where('(msg.uid = ' . $uid . ' or msg.uid = 0 ) and (select count(*) from lc_msg_is as msg_is where  msg.id = msg_is.mid  and ((msg.uid = 0 and msg_is.uid = ' . $uid . ') or ( msg.uid = ' . $uid . ' and msg_is.uid = ' . $uid . ') )) = 0')->count();

        $this->qiandao = 1;
        $today = date('Y-m-d 00:00:00');
        if ($today <= $this->user['qiandao'])
            $this->qiandao = 2;
        $this->lcopen = getlcopen();

        $this->top = Db::name('LcUser')->where(['top' => $uid])->count();

        $now = date('Y-m-d H:i:s');
        $this->yxkj = Db::name('LcMallInvest')->where("time2 >= '$now' and uid = $uid")->count();

        $this->zqhy = Db::name('LcInvest')->where("time2 >= '$now' and uid = $uid")->count();
        $userleveltile = Db::name('LcUserMember')->find($this->user['member']);
        $this->assign('userleveltile', $userleveltile);
        $this->success('查询成功',[
            'user'=>$this->user,
            'wait_money'=>$this->wait_money,
            'chicang'=>$this->chicang,
            'yingkui'=>0,
//            'msg'=>$this->msg,
            'qiandao'=>$this->qiandao,
//            'lcopen'=>$this->lcopen,
//            'top'=>$this->top,
//            'yxkj'=>$this->yxkj,
//            'zqhy'=>$this->zqhy,
            'userleveltile'=>$userleveltile
        ]);
	}

    public function account() {
        $uid = input('uid');
        if (!$uid) $this->error('用户ID不能为空');
        $this->user = Db::name('LcUser')->find($uid);
        if(!$this->user)$this->error('用户信息异常');
        //自动更新真实姓名 - 已禁用,保留注册时的实名信息
        // $real_name = Db::name('LcBank')->where(['uid' => $uid])->value('name');
        // 只有当银行卡姓名存在且与用户姓名不同时才更新
        // if ($real_name && $this->user['name'] != $real_name) {
        //     Db::name('LcUser')->where(['id' => $uid])->update(['name' => $real_name]);
        // }
        
        $this->chicang = Db::name('LcOrder')->where("uid = $uid AND ostaus = 0")->sum('fee');
        $this->wait_money = $this->user['money']+$this->chicang;
        
        // 计算盈亏：持仓订单的盈亏总和
        $this->yingkui = Db::name('LcOrder')->where("uid = $uid AND ostaus = 0")->sum('ploss');
        if (!$this->yingkui) $this->yingkui = 0; // 如果为null则设为0
        
        $this->msg = Db::name('LcMsg')->alias('msg')->where('(msg.uid = ' . $uid . ' or msg.uid = 0 ) and (select count(*) from lc_msg_is as msg_is where  msg.id = msg_is.mid  and ((msg.uid = 0 and msg_is.uid = ' . $uid . ') or ( msg.uid = ' . $uid . ' and msg_is.uid = ' . $uid . ') )) = 0')->count();

        $this->qiandao = 1;
        $today = date('Y-m-d 00:00:00');
        if ($today <= $this->user['qiandao'])
            $this->qiandao = 2;
        $this->lcopen = getlcopen($uid);


        $this->top = Db::name('LcUser')->where(['top' => $uid])->count();

        $now = date('Y-m-d H:i:s');
        $this->yxkj = Db::name('LcMallInvest')->where("time2 >= '$now' and uid = $uid")->count();

        $this->zqhy = Db::name('LcInvest')->where("time2 >= '$now' and uid = $uid")->count();
        $userleveltile = Db::name('LcUserMember')->find($this->user['member']);
        $this->success('查询成功',[
            'user'=>$this->user,
            'wait_money'=>$this->wait_money,
            'chicang'=>$this->chicang,
            'yingkui'=>$this->yingkui,
//            'msg'=>$this->msg,
            'qiandao'=>$this->qiandao,
            'lcopen'=>$this->lcopen,
//            'top'=>$this->top,
//            'yxkj'=>$this->yxkj,
//            'zqhy'=>$this->zqhy,
            'userleveltile'=>$userleveltile
        ]);
	}

	/**
	 * Describe:签到
	 * DateTime: 2020/5/13 23:17
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function sign() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->error('请先登录后再签到');
		$this->user = Db::name('LcUser')->find($uid);
		$today = date('Y-m-d 00:00:00');
		if ($today <= $this->user['qiandao'])
			$this->error('每天只能签到一次');
		$money = getReward('qiandao');
		Db::name('LcUser')->where(['id' => $uid])->update(['qiandao' => date('Y-m-d H:i:s')]);
		addFinance($uid, $money, 1, "每日签到，获得奖励{$money}元");
		setNumber('LcUser', 'money', $money, 1, "id=$uid");
		setNumber('LcUser', 'income', $money, 1, "id=$uid");
		setNumber('LcUser', 'qdnum', 1, 1, "id=$uid");
		$this->success("签到成功，获得{$money}元");
	}

	/**
	 * Describe:站内消息
	 * DateTime: 2020/5/14 0:01
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function msg() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->msgtop = Db::name('LcMsg')->alias('msg')->where('(msg.uid = ' . $uid . ' or msg.uid = 0 ) and (select count(*) from lc_msg_is as msg_is where msg.id = msg_is.mid  and ((msg.uid = 0 and msg_is.uid = ' . $uid . ') or ( msg.uid = ' . $uid . ' and msg_is.uid = ' . $uid . ') )) = 0')->select();
		$this->msgfoot = Db::name('LcMsg')->alias('msg')->where('(select count(*) from lc_msg_is as msg_is where msg.id = msg_is.mid and msg_is.uid = ' . $uid . ') > 0')->select();
		$this->fetch();
	}

	/**
	 * Describe: 信息详情
	 * DateTime: 2020/5/14 0:31
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function msg_view() {
		$id = $this->request->param('id');
		$uid = $this->app->session->get('uid');
		if (!$id || !$uid)
			msg('系统忙碌！', 2, '/index/user/index');
		$where['uid'] = $this->app->session->get('uid');
		$where['mid'] = $id;
		$ret = Db::name('LcMsgIs')->where($where)->find();
		if (!$ret)
			Db::name('LcMsgIs')->insertGetId(['uid' => $uid, 'mid' => $id]);
		$this->msg = Db::name('LcMsg')->find($id);
		$this->fetch();
	}

	/**
	 * Describe: 账户安全
	 * DateTime: 2020/5/14 0:48
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function set_account() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($uid);
		$this->fetch();
	}

	/**
	 * @description：交易密码设置
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function pwd_pay() {
        $uid = input('uid');
        if (!$uid) $this->error('用户ID不能为空');
		$user = Db::name('LcUser')->find($uid);
		if ($this->request->isPost()) {
            if(!input('oldpwd','')) $this->error('原支付密码不能为空');
            if(!input('pwd','')) $this->error('新密码不能为空');
            if(!input('pwd2','')) $this->error('确认密码不能为空');
			$data = $this->request->param();
            if ($user['password'] != md5($data['oldpwd']))
                $this->error('原支付密码错误！');
            if ($user['password'] == md5($data['pwd']))
                $this->error('新密码不能与原密码相同！');
            if (strlen($data['pwd']) < 6 || 16 < strlen($data['pwd']))
                $this->error('请输入6-16位密码！');
            if ($data['pwd'] != $data['pwd2'])
                $this->error('两次密码不一致！');
			if (Db::name('LcUser')->where(['id' => $user['id']])->update(['password2' => md5($data['pwd']), 'mwpassword2' => $data['pwd2']])) {
				$this->success('修改成功！');
			}
			else {
				$this->error('修改失败！');
			}
		}
	}

	/**
	 * Describe: 登录密码设置
	 * DateTime: 2020/5/14 1:00
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function pwd_login() {
		$uid = input('uid');
		if (!$uid) $this->error('用户ID不能为空');
		$user = Db::name('LcUser')->find($uid);
		if ($this->request->isPost()) {
            if(!input('oldpwd','')) $this->error('原登录密码不能为空');
            if(!input('pwd','')) $this->error('新密码不能为空');
            if(!input('pwd2','')) $this->error('确认密码不能为空');
			$data = $this->request->param();
			if ($user['password'] != md5($data['oldpwd']))
				$this->error('原登录密码错误！');
            if ($user['password'] == md5($data['pwd']))
                $this->error('新密码不能与原密码相同！');
			if (strlen($data['pwd']) < 6 || 16 < strlen($data['pwd']))
				$this->error('请输入6-16位密码！');
			if ($data['pwd'] != $data['pwd2'])
				$this->error('两次密码不一致！');
			if (Db::name('LcUser')->where(['id' => $user['id']])->update(['password' => md5($data['pwd']), 'mwpassword' => $data['pwd2']])) {
				$this->success('修改成功！');
			}
			else {
				$this->error('修改失败！');
			}
		}
	}

	/**
	 * @description：身份认证
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function certification() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($uid);
		if ($this->request->isPost()) {
			$data = $this->request->param();
			if ($this->user['auth'] == 1)
				$this->error('您已认证，请勿重复提交！');
			$check = Db::name('LcUser')->where("idcard = '{$data['idcard']}' AND id <> $uid")->find();
			if ($check)
				$this->error('身份证号已存在，请勿重复！');
			if (getInfo('cert') == 1) {
				$auth_check = idCardAuth($data['idcard'], $data['name']);
				if ($auth_check['code'] == 0)
					$this->error($auth_check['msg']);
			}
			else {
				if (!judge($data['name'], 'name'))
					$this->error('姓名不正确！');
				if (!judge($data['idcard'], 'idcard'))
					$this->error('身份证号不正确！');
			}
			$money = getReward('shimingsong');
			addFinance($uid, $money, 1, "实名成功，系统赠送{$money}元");
			setNumber('LcUser', 'money', $money, 1, "id = $uid");
			setNumber('LcUser', 'income', $money, 1, "id = $uid");
			if (Db::name('LcUser')->where(['id' => $uid])->update(['name' => $data['name'], 'idcard' => $data['idcard'], 'auth' => 1])) {
				$this->success('认证成功！');
			}
			else {
				$this->error('认证失败！');
			}
		}
		$this->fetch();
	}

	/**
	 * @description：银行卡管理
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function bank_card() {
        $uid = input('uid','');
        if (!$uid) $this->error('用户ID不能为空');
		$this->user = Db::name('LcUser')->find($uid);
		$this->bank = Db::name('LcBank')->where('uid', $uid)->order("id desc")->select();
		$this->success('查询成功',['banks'=>$this->bank]);
	}

	/**
	 * @description：添加银行卡
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function add_card() {
        $uid = input('uid','');
        if (!$uid) $this->error('用户ID不能为空');
		$this->user = Db::name('LcUser')->find($uid);
		// if($this->user['auth'] != 1) $this->error('请实名认证后再添加！');
		if ($this->request->isPost()) {
            if (!input('name','')) $this->error('姓名不能为空');
            if (!input('bank','')) $this->error('银行名称不能为空');
            if (!input('area','')) $this->error('开户行不能为空');
            if (!input('account','')) $this->error('银行卡号不能为空');
			$data = $this->request->param();
			// 银行卡号格式校验：必须16-19位纯数字
			$account = isset($data['account']) ? trim($data['account']) : '';
			if (!preg_match('/^\d{16,19}$/', $account))
				$this->error('银行卡号格式不正确，请输入16-19位数字！');
			$data['account'] = $account;
			//  if(Db::name('LcBank')->where('account', $data['account'])->find()) $this->error('银行卡号已存在，请勿重复添加！');
			if (getInfo('bank') == 1) {
				if (empty($data['account']))
					$this->error('请输入银行卡号！');
				$auth_check = bankAuth($this->user['name'], $data['account'], $this->user['idcard']);
				if ($auth_check['code'] == 0)
					$this->error($auth_check['msg']);
				$bank = $auth_check['bank'];
			}
			else {
				if (empty($data['bank']) || empty($data['account']))
					$this->error('请输入银行和银行卡号！');
				$bank = $data['bank'];
			}
			// 不再覆盖用户表中的实名信息,保留注册时的实名
			// Db::name('LcUser')->where(['id' => $uid])->update(['name' => $data['name']]);
			$add = ['uid' => $uid, 'bank' => $bank, 'area' => $data['area'], 'account' => $data['account'], 'name' => $data['name']];
			if (Db::name('LcBank')->insert($add))
				$this->success('添加成功！');
			$this->error('操作失败！');
		}
		$this->fetch();
	}

	/**
	 * @description：删除银行卡
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function del_card() {
		$this->applyCsrfToken();
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$id = $this->request->param('id');
		if (Db::name('LcBank')->where(['uid' => $uid, 'id' => $id])->delete()) {
			msg('删除成功！', 2, '/index/user/index');
		}
		msg('操作失败！', 2, '/index/user/index');
	}

	/**
	 * @description：支付宝设置
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function alipay() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($uid);
		if ($this->request->isPost()) {
			$data = $this->request->param();
			if (Db::name('LcUser')->where(['id' => $uid])->update(['alipay' => $data['alipay']])) {
				msg('Successful operation！', 2, '/index/user/index');
			}
			else {
				msg('操作失败！', 2, '/index/user/index');
			}
		}
		$this->fetch();
	}

	/**
	 * @description：资金流水
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function fund() {
        $uid = input('uid','');
        if (!$uid) $this->error('用户ID不能为空');

        $page = input('page',1);
        $num = input('num',10);
		$this->finance = Db::name('LcFinance')->where('uid', $uid)->order("id desc")->page($page,$num)->select();
        $this->success('查询成功',$this->finance);
	}

	/**
	 * @description：投资记录
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function invest() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->invest = Db::name('LcInvest')->where('uid', $uid)->order("id desc")->select();
		$this->fetch();
	}

	/**
	 * @description：收益记录
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function interest() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->interest = Db::name('LcInvestList')->where("uid = $uid AND status = 1 AND pay1 <> 0")->order("time2 desc")->select();
		$this->fetch();
	}

	/**
	 * @description：充值记录
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function recharge_record() {
		$uid = input('uid','');
		if (!$uid) $this->error('用户ID不能为空');

        $page = input('page',1);
        $num = input('num',10);
		$recharge = Db::name('LcRecharge')->where('uid', $uid)->order("id desc")->page($page,$num)->select();
        $this->success('查询成功',$recharge);
	}

	/**
	 * @description：提现记录
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @author: DeeBoo <chan@deeboo.cn>
	 * @date: 2020/5/14 0014
	 */
	public function cash_record() {
        $uid = input('uid','');
        if (!$uid) $this->error('用户ID不能为空');

        $page = input('page',1);
        $num = input('num',10);
		$cash = Db::name('LcCash')->where('uid', $uid)->order("id desc")->page($page,$num)->select();
        $this->success('查询成功',$cash);
	}

	/**
	 * @description：推广记录
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function extend() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->results = Db::name('LcUser')->field("id,name,phone,time")->where('top', $uid)->order("id desc")->select();
		foreach ($this->results as &$vo) {
			$vo['recharge'] = Db::name('LcRecharge')->where(['uid' => $vo['id'], 'status' => 1])->sum('money');
			$vo['cash'] = Db::name('LcCash')->where(['uid' => $vo['id'], 'status' => 1])->sum('money');
		}
		$this->fetch();
	}

	/**
	 * @description：邀请好友
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function recommend() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($uid);
		// if($this->user['auth'] != 1) msg('请实名认证后再进行邀请！', 2, '/index/User/certification');
		$domain = getInfo('domain');
		$this->url = $domain ? $domain : $uid = $this->app->request->host();
		$this->fetch();
	}

	/**
	 * @description：提现
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function cash() {
		$uid = input('uid','');
		if (!$uid) $this->error('用户信息不能为空');
		// 确保 uid 是整数类型
		$uid = intval($uid);
		if ($uid <= 0) $this->error('用户信息不能为空');
		
		$this->user = Db::name('LcUser')->find($uid);
		if(!$this->user) $this->error('用户不存在');
		
		$this->bank = Db::name('LcBank')->where('uid', $uid)->order("id desc")->select();
		$user = $this->user;

		$this->txsxf = getInfo('cash_charge');
		$this->cash_start = getInfo('cash_start');
		$this->cash_end = getInfo('cash_end');
		$this->cash_min = getInfo('cash_min');
		$this->cash_max = getInfo('cash_max');
		$this->cash_day_max = getInfo('cash_max_num');
		$this->cash_max_num = getInfo('cash_day_max');
		$data = [
			'user'=>$this->user,'bank'=>$this->bank,'txsxf'=>$this->txsxf,'cash_start'=>$this->cash_start,'cash_end'=>$this->cash_end,
			'cash_min'=>$this->cash_min,'cash_max'=>$this->cash_max,'cash_day_max'=>$this->cash_day_max,'cash_max_num'=>$this->cash_max_num
		];
		if ($this->app->request->isPost()) {
			// 只要有一张银行卡即可提现
			// 先查询所有银行卡信息用于调试
			$allBanks = Db::name('LcBank')->select();
			$userBanks = Db::name('LcBank')->where('uid', $uid)->select();
			
			\think\facade\Log::info("提现检查银行卡 - uid类型: " . gettype($uid) . ", uid值: {$uid}");
			\think\facade\Log::info("提现检查银行卡 - 数据库所有银行卡: " . json_encode($allBanks, JSON_UNESCAPED_UNICODE));
			\think\facade\Log::info("提现检查银行卡 - 当前用户银行卡: " . json_encode($userBanks, JSON_UNESCAPED_UNICODE));
			
			$bankInfo = Db::name('LcBank')->where('uid', $uid)->order('id desc')->find();
			if(!$bankInfo){
				// 调试信息
				\think\facade\Log::error("提现失败: uid={$uid}(类型:" . gettype($uid) . ") 未找到银行卡");
				$this->error('请先绑定银行卡！uid='.$uid);
			}
			if ($user['isjy'] == 1) {
				$this->error('您的账户已被禁止交易！');
			}
			$time = time();
			$cash_start = strtotime(date('Y-m-d ' . $this->cash_start . ':00'));
			$cash_end = strtotime(date('Y-m-d ' . $this->cash_end . ':00'));
			if ($time < $cash_start || $time > $cash_end) {
				$this->error('提款时间为' . $this->cash_start . '-' . $this->cash_end);
			}

			$money = input('money','');
			if(!$money)$this->error('取款金额不能为空！');
			$pwd = input('pwd','');
			if(!$pwd)$this->error('提现密码不能为空！');
			$data = input('');
			$invest = Db::name('LcInvest')->where('uid', $uid)->find();
			$today = date('Y-m-d 00:00:00');
			// if($this->user['auth'] != 1) $this->error('请实名认证后再提现！');
			if ($this->user['password2'] != md5($data['pwd']))
				$this->error('交易密码不正确！');
			if ($data['money'] < $this->cash_min)
				$this->error('提现金额不能小于' . $this->cash_min . '元');
			if ($data['money'] > $this->cash_max)
				$this->error('提现金额不能大于' . $this->cash_max . '元');
			if ($this->user['money'] < $data['money'])
				$this->error('提现金额大于会员余额！');
			// if(empty($invest)) msg('未投资不能提现！');

			$day_num = Db::name('LcCash')->where("uid = $uid AND time > '$today'")->count();
			if ($day_num >= $this->cash_day_max)
				$this->error('每日提现限制' . $this->cash_day_max . '次！');

			$day_sum = Db::name('LcCash')->where("uid = $uid AND time > '$today'")->sum('money');
			if (($day_sum + $data['money']) > $this->cash_max_num)
				$this->error('当日累计提现金额不能大于' . $this->cash_max_num . '元！');

			$cash_charge = getInfo('cash_charge');
			$sxf = $data['money'] * $cash_charge * 0.01;
			$dzje = $data['money'] - $sxf;
			$sxfbfb = $cash_charge;

			// 默认用最新一张银行卡
			$add = array('uid' => $uid, 'sxf' => $sxf, 'dzje' => $dzje, 'sxfbfb' => $sxfbfb, 'name' => $bankInfo['name'], 'bid' => $bankInfo['id'], 'bank' => $bankInfo['bank'], 'area' => $bankInfo['area'] ?: 0, 'account' => $bankInfo['account'], 'money' => $data['money'], 'status' => 0, 'time' => date('Y-m-d H:i:s'), 'time2' => '0000-00-00 00:00:00');
			if (Db::name('LcCash')->insert($add)) {
				addFinance($uid, $data['money'], 2, "余额提现{$data['money']}元");
				setNumber('LcUser', 'money', $data['money'], 2, "id = $uid");
				$this->success('提现申请成功！');
			} else {
				$this->error('提现失败！');
			}
		}
		$this->success('提现信息查询成功',$data);
	}

	/**
	 * @description：充值
	 * @date: 2020/5/14 0014
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function recharge() {
		$uid = input('uid','');
		if (!$uid) $this->error('用户信息不能为空');
		$this->user = Db::name('LcUser')->find($uid);
		$this->min_recharge = getInfo('min_recharge');
		if ($this->app->request->isPost()) {
            $money = input('money','');
            if (!$money) $this->error('充值金额不能为空');
            $type = input('type','bank');
//            if (!$type) $this->error('充值类型不能为空');
            $truename = input('truename','');
            if (!$truename) $this->error('存款姓名不能为空');
            $reason = input('reason','');
            if (!$reason) $this->error('转账附言不能为空');
			$data = input('');
            if(isset($data['money'])||!$data['money']){}
			if ($data['money'] < $this->min_recharge)
				$this->error('最低充值' . $this->min_recharge . '元');
			if (getPayName($data['type']) == 'unknown payment')
				$this->error('请更换支付方式！');
			// if($this->user['auth'] != 1) $this->error('请实名认证后再充值！');
			$orderid = 'PAY' . date('YmdHis') . rand(1000, 9999) . rand(100, 999);

			$reason = 'payer：' . $data['truename'] . '<br/>transfer postscript：' . $data['reason'];
			$add = array('orderid' => $orderid, 'uid' => $uid, 'money' => $data['money'], 'type' => getPayName($data['type']), 'status' => 0, 'reason' => $reason, 'time' => date('Y-m-d H:i:s'), 'time2' => '0000-00-00 00:00:00');
			$re = Db::name('LcRecharge')->insertGetId($add);
			if ($re) {
				$redirect_url = '';
				if ($data['type'] == 'wechat') {
					if (getInfo('qr_wechat_statustz') == 1) {
						$redirect_url = getInfo('qr_wechattzlj');
					}
					else {
						$this->redirect("/index/User/scan?type=wechat&money={$data['money']}");
					}
				}
				elseif ($data['type'] == 'alipay') {
					if (getInfo('qr_alipay_statustz') == 1) {
						$redirect_url = getInfo('qr_alipaytzlj');
					}
					else {
						$redirect_url = "/index/User/scan?type=alipay&money={$data['money']}";
					}
				}
				else {
					$redirect_url = "/index/user/bank?money={$data['money']}&orderid={$orderid}&type={$data['type']}";
				}
				if (!empty($redirect_url)) {
					$this->success('充值申请成功！', ['orderid' => $orderid, 'money' => $data['money']]);
				}
			}
			else {
				$this->error('充值申请失败！');
			}
		}
		$this->success('充值信息查询成功！',['user'=>$this->user,'min_recharge'=>$this->min_recharge]);
	}

	/**
	 * @description：扫描充值
	 * @date: 2020/5/14 0014
	 */
	public function scan() {
		$type = $this->request->param('type');
		$this->money = $this->request->param('money');
		$this->qr = getInfo('qr_alipay_img');
		if ($type == 'wechat')
			$this->qr = getInfo('qr_wechat_img');
		$this->fetch();
	}

	/**
	 * Describe:提现银行
	 * DateTime: 2020/5/14 21:44
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function bank() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		if ($this->app->request->isPost()) {
			$data = $this->request->param();
			$update = array('reason' => 'payer：' . $data['name'] . '<br/>转账附言：' . $data['reason']);
			if (Db::name('LcRecharge')->where(['uid' => $uid, 'status' => 0, 'orderid' => $data['orderid']])->update($update)) {
				msg('Successful operation！', 2, '/index/user/index');
			}
			else {
				msg('操作失败！', 2, '/index/user/index');
			}
		}
		$this->type = $this->request->param('type');
		$this->orderid = $this->request->param('orderid');
		$this->money = $this->request->param('money');
		if (empty($this->orderid))
			msg('充值失败！', 2, '/index/user/index');
		$this->fetch();
	}

	/**
	 * Describe:合同详情
	 * DateTime: 2020/5/14 21:44
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function contract() {
		$this->uid = $this->app->session->get('uid');
		if (!$this->uid)
			$this->redirect('/index/login');
		$id = $this->request->param('id');
		if (empty($id))
			msg('参数缺失！', 2, '/index/user/index');
		$this->invest = Db::name('LcInvest')->where(['uid' => $this->uid, 'id' => $id])->find();
		$this->list = Db::name('LcInvestList')->where(['uid' => $this->uid, 'iid' => $id])->order('id desc')->select();
		//if(!$this->invest||!$this->list) msg('暂无数据！', 2, '/index/user/index');
		$this->fetch();
	}

	public function mall_contract() {
		$this->uid = $this->app->session->get('uid');
		if (!$this->uid)
			$this->redirect('/index/login');
		$id = $this->request->param('id');
		if (empty($id))
			msg('参数缺失！', 2, '/index/user/index');
		$this->invest = Db::name('LcMallInvest')->where(['uid' => $this->uid, 'id' => $id])->find();
		$this->list = Db::name('LcMallInvestList')->where(['uid' => $this->uid, 'iid' => $id])->order('id desc')->select();
		$this->fetch();
	}

	public function details() {
		$this->uid = $this->app->session->get('uid');
		if (!$this->uid)
			$this->redirect('/index/login');
		$id = $this->request->param('id');
		if (empty($id))
			msg('参数缺失！', 2, '/index/user/index');
		$this->invest = Db::name('LcInvest')->where(['uid' => $this->uid, 'id' => $id])->find();
		$this->list = Db::name('LcInvestList')->where(['uid' => $this->uid, 'iid' => $id])->order('id asc')->select();
		if (!$this->invest || !$this->list)
			msg('暂无数据！', 2, '/index/user/index');
		$this->fetch();
	}

	public function wallet() {
		$this->uid = $this->app->session->get('uid');
		if (!$this->uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($this->uid);
		$now = date('Y-m-d H:i:s');
		$this->kjbz = Db::name('LcMallInvest')->where('uid', $this->uid)->where("time2 >= '$now'")->sum('money');
		$this->hybz = Db::name('LcInvest')->where('uid', $this->uid)->where("time2 >= '$now'")->sum('money');

		$this->kjcl = Db::name('LcMallInvestList')->where(['uid' => $this->uid, 'status' => 1])->sum('money1');
		$this->hysy = Db::name('LcInvestList')->where(['uid' => $this->uid, 'status' => 1])->sum('money1');
		$this->kjsy = Db::name('LcFinance')->where("uid = {$this->uid} AND reason LIKE '%BTC兑换%'")->sum('money');
		$this->fetch();
	}

	public function trade() {
		$this->uid = $this->app->session->get('uid');
		if (!$this->uid)
			$this->redirect('/index/login');
		$this->user = Db::name('LcUser')->find($this->uid);
		$this->fetch();
	}

	public function tradeBTC() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$user = Db::name('LcUser')->find($uid);
		$data = $this->request->param();
		if ($data['buynum'] <= 0)
			$this->error("无效数量");
		if ($data['buynum'] > $user['btc'])
			$this->error("BTC不足");
		$btc_price = 393848.54;
		$money = round($btc_price * $data['buynum'], 2);
		addFinance($uid, $money, 1, "BTC兑换交易{$money}元");
		setNumber('LcUser', 'money', $money, 1, "id = {$uid}");
		setNumber('LcUser', 'btc', $data['buynum'], 2, "id = {$uid}");
		$this->success("交易成功");
	}

	/**
	 * @description:退出登录
	 * @date: 2020/5/13 23:57
	 */
	public function logout() {
		$uid = $this->app->session->get('uid');
		Db::table('lc_user')->where('id', $uid)->limit(1)->update(['access_time' => 0]);
		session('uid', null);
		//$this->app->session->clear();
		//$this->app->session->destroy();
		$this->redirect('/index');
	}

	public function hold() {
        $uid = input('uid','');
        if (!$uid)
            $this->error('用户ID不能为空');
        $page = input('page',1);
        $num = input('num',10);
		$this->type = $this->request->param('type');
		$this->hold = Db::name('LcOrder')->where(array('uid' => $uid, 'ostaus' => 0))->order('id desc')->page($page,$num)->select();
		//->field('id,ptitle,buytime,fee,ostyle')
		//var_dump($this->hold);die;
        $this->success('查询成功',$this->hold);
	}

	/**
	 * 下单
	 * @return [type] [description]
	 * @author lukui  2017-07-20
	 */
	public function addorder() {
		// {
		//     "order_type": "0",
		//     "order_pid": "29",
		//     "order_price": "100",
		//     "order_sen": "60",
		//     "order_shouyi": "-3.2740000000000005",
		//     "order_kuishun": "-3.2740000000000005",
		//     "newprice": "275.661"
		// }

		$data =input('');

		$uid = input('uid','');
		if (!$uid)
            $this->error('用户ID不能为空');

		//用户信息
		$user = Db::name('LcUser')->find($uid);
        if (!$user) $this->error('用户信息异常');
		//验证用户是否被冻结
		if ($user['clock'] == 1) {
			// return WPreturn('您的账户已被冻结',-1);
		}
		if ($user['isjy'] == 1) {
            $this->error('您的账户已被禁止交易');
		}
		$adddata['uid'] = $uid;

		$pro = Db::name('LcProduct')->find($data['order_pid']);
		if (!$pro)
			$this->error('产品未找到');
		//验证是否开市
		if ($pro['isopen']) {
			$isopen = ChickIsOpen($pro['id']);
			if ($isopen == 0) {
				$this->error('已休市');
			}
		}
		else {
			$this->error('已休市');
		}

		//持仓限制
		if (getinfo('order_max_amount') > 0) {
			$allfee = Db::name('LcOrder')->where(array('ostaus' => 0, 'uid' => $data['uid']))->sum('fee');
			$allfee = $allfee ? $allfee : 0;

			if ($allfee + $data['order_price'] > getinfo('order_max_amount')) {
				$this->error('最大持仓金额为' . getinfo('order_max_amount') . '！');
			}
		}
		if (getinfo('order_max_count') > 0) {
			$allcount = Db::name('LcOrder')->where(array('ostaus' => 0, 'uid' => $data['uid']))->count();
			// if($allcount >  getinfo('order_max_count')){
			//     return WPreturn('最大持仓单数为'.$conf['order_max_count'].'！',-1);
			// }
		}

//		if ($data['order_price'] > getinfo('order_max')) {
//			$this->error('单笔持仓金额最大为' . getinfo('order_max') . '！');
//		}
//		if ($data['order_price'] < getinfo('order_min')) {
//			$this->error('单笔持仓金额最小为' . getinfo('order_min') . '！');
//		}

		//手续费
		$web_poundage = 0;
		if (getinfo('order_charge') > 0) {
			$web_poundage = round($data['order_price'] * getinfo('order_charge') / 100, 2);
		}
		$moneyfrom = 1;
		//验证余额是否够
		if ($user['money'] < $data['order_price'] + $web_poundage) {
            $this->error('余额不足，请充值！');
			// $getyebuc=Db::table('lc_yuebao_uc')->where('uid = '.$uid)->find();
			// if($getyebuc['balance'] < $data['order_price'] + $web_poundage){
			// 	if($getyebuc['balance'] + $user['money']< $data['order_price'] + $web_poundage){
			// 		return WPreturn('您得余额不足，请充值！',-1);
			// 	};$moneyfrom=3;

			// };$moneyfrom=2;

		}
		if (floatval($data['newprice']) <= 0) {
            $this->error('参数错误，请重试！');
		}

		//建仓
		$adddata['buytime'] = time();
		$adddata['endprofit'] = $data['order_sen'];
		$adddata['pid'] = $data['order_pid'];
		$adddata['ostyle'] = $data['order_type'];
		$adddata['buyprice'] = $data['newprice'];
		$adddata['endloss'] = $data['order_shouyi'];
		$adddata['lossrate'] = $data['order_kuishun'];
		$adddata['eid'] = 2;
		$adddata['selltime'] = $adddata['buytime'] + $adddata['endprofit'];
		$adddata['fee'] = $data['order_price'];
		$adddata['ptitle'] = $pro['title'];
		$adddata['ostaus'] = '0';
		$adddata['sx_fee'] = $web_poundage;
		// $adddata['limit_points'] = $data['limit_points'];
		// $adddata['stop_points'] = $data['stop_points'];

		$allfee = $adddata['fee'] + $adddata['sx_fee'];

		// 使用事务+行锁防止并发重复下单
		Db::startTrans();
		try {
			// 行锁重新读取用户余额，防止并发超卖
			$lockedUser = Db::name('LcUser')->lock(true)->where('id', $uid)->find();
			if ($lockedUser['money'] < $allfee) {
				Db::rollback();
				$this->error('余额不足，请充值！');
			}

			//会员建仓后金额
			$adddata['commission'] = $lockedUser['money'] - $allfee;
			//订单号
			$adddata['orderno'] = date('YmdHis') . $uid . rand(1111, 9999);

			//下单
			$id = Db::name('LcOrder')->insertGetId($adddata);
			if ($id) {
				//下单成功减用户余额
				addFinance($uid, $allfee, 2, '下单[' . $adddata['orderno'] . ']从账户扣除金额 ' . $allfee . '元');
				setNumber('LcUser', 'money', $allfee, 2, "id = $uid");

				Db::commit();

				$nowmoney = $adddata['commission'];
				if ($nowmoney < 0) {
					$nowmoney = 0;
				}
				$adddata['oid'] = $id;
				$order_rand = rand(1, 1000);
				$adddata['order_rand'] = $order_rand;
				$this->success('下单成功',$adddata);
			}
			else {
				Db::rollback();
				$this->error('下单失败，请重试');
			}
		} catch (\Exception $e) {
			Db::rollback();
			$this->error('下单异常：' . $e->getMessage());
		}
	}

	public function goorder() {
		// {
		//     "price": "275.661",
		//     "oid": "463",
		//     "order_rand": "547"
		// }
		$data = $this->request->param();
		$oid = $data['oid'];
		$price = $data['price'];
		$order_rand = $data['order_rand'];

		$static = 1;        //1成功返回并继续运行  0失败返回不运行  2 失败返回继续轮询
		if (!$oid || !$price || !$order_rand) {
			die('0' . $oid . '-' . $price . '-' . $order_rand);
		}

		$order = Db::name('LcOrder')->find($oid);

		//没有此订单
		if (!$order) {
			die('1' . $oid . '-' . $price . '-' . $order_rand);
		}

		//没有平仓
		if (isset($order['ostyle']) && $order['ostaus'] == 0) {
			die('2');
		}

		//已平仓 但是价格相同
		if (isset($order['sellprice']) && $order['sellprice'] == $price) {
			cache('goorder_' . $order['id'], null);
			die('1');
		}

		//已平仓 但是无效交易
		if (isset($order['is_win']) && $order['is_win'] == 3) {
			cache('goorder_' . $order['id'], null);
			die('1');
		}
		//该订单指定赢亏
		if (isset($order['kong_type']) && $order['kong_type'] != 0) {
			cache('goorder_' . $order['id'], null);
			die('1');
		}
		die('1');
	}

	public function get_price() {
		//此刻产品价格
		$pro = Db::name('LcProduct')->field('id,Price')->where(array('isdelete' => 0))->select();
		$prodata = array();
		foreach ($pro as $k => $v) {
			$prodata[$v['id']] = $v['Price'];
		}
		return base64_encode(json_encode($prodata));;
	}

	/**
	 * ajax 通过产品id 获取用户订单，
	 * @return [type] [description]
	 * @author lukui  2017-07-22
	 */
	public function ajaxorder() {
		$uid = $this->app->session->get('uid');
		$pid = $this->request->param('pid');
		if (empty($uid) || empty($pid)) {
			return false;
		}
		//持仓信息
		$map = [];
		$map[] = ['uid', '=', $uid];
		$map[] = ['pid', '=', $pid];
		$map[] = ['ostaus', '=', 0];
		$map[] = ['selltime', '>', time()];

		$list = Db::name('LcOrder')->where($map)->order('id desc')->select();
		foreach ($list as $key => $value) {
			$list[$key]['time'] = time();
		}
		if ($list) {
			return base64_encode(json_encode($list));
			// return json_encode($list);
		}
		else {
			return false;
		}

	}

	/**
	 * ajax 通过产品id 平仓后弹框提示，
	 * @return [type] [description]
	 * @author lukui  2017-07-22
	 */
	public function ajaxalert() {
		$uid = $this->app->session->get('uid');
		$pid = $this->request->param('pid');
		if (empty($uid) || empty($pid)) {
			return false;
		}
		//持仓信息
		$hold = Db::name('LcOrder')->field('id,ploss,fee,eid')->where(array('uid' => $uid, 'ostaus' => 1, 'pid' => $pid, 'isshow' => 0))->order('id desc')->find();
		//修改持仓信息
		$isedit = Db::name('LcOrder')->where('id', $hold['id'])->setField('isshow', '1');
		if ($hold && $isedit) {
			return $hold;
		}
		else {
			return false;
		}
	}
    /**
     * 交易记录
     * @return [type] [description]
     * @author lukui  2017-07-22
     */
	public function ajaxorder_list() {
		$uid = input('uid','');
		if (empty($uid)) {
			$this->error('用户ID不能为空');
		}

        $type = input('type',0);
        $page = input('page',1);
        $num = input('num',10);
        //持仓信息
        $map = [];
        $map[] = ['ostaus','=',$type];
		$map[] = ['uid', '=', $uid];

		$list = Db::name('LcOrder')->where($map)->order('id desc')->page($page,$num)->select();
		foreach ($list as $key => $value) {
			$list[$key]['time'] = time();
		}
		if ($list) {
			$this->success('查询成功',$list);
		}
		else {
            $this->success('暂无数据',[]);
		}
	}

	public function getchart() {
		$data['hangqing'] = '商品行情';
		$data['jiaoyijilu'] = '交易记录';
		$data['jiaoyilishi'] = '历史委托';
		$data['chicangmingxi'] = '持仓明细';
		$data['lishimingxi'] = '历史明细';
		$data['gendanjiaoyi'] = '跟单交易';
		$res = base64_encode(json_encode($data));
		return $res;
	}

	public function orderlist() {
		$uid = $this->app->session->get('uid');
		if (empty($uid)) {
			return false;
		}
		$map = [];
		$map[] = ['uid', '=', $uid];
		$map[] = ['ostaus', '=', 1];

		$hold = Db::name('LcOrder')->where($map)->order('id desc')->paginate(20);
		return base64_encode(json_encode($hold));
	}

	/**
	 * 已平仓订单详情
	 * @return [type] [description]
	 * @author lukui  2017-07-21
	 */
	public function orderinfo() {
		$uid = $this->app->session->get('uid');
		$oid = $this->request->param('oid');
		if (!$oid) {
			$this->redirect('orderlist');
		}
		$order = Db::name('LcOrder')->where('id', $oid)->find();
		$this->assign($order);
		return $this->fetch();

	}

	/**
	 * 实时获取以平仓订单
	 * @return [type] [description]
	 */
	public function get_this_order() {
		$oid = $this->request->param('oid');
		$map['id'] = $oid;
		$map['ostaus'] = 1;
		$order = Db::name('LcOrder')->where($map)->find();

		return base64_encode(json_encode($order));
	}

	/**
	 * 实时获取以平仓订单
	 * @return [type] [description]
	 */
	public function get_hold_order() {
		$oid = $this->request->param('oid');
		$map['id'] = $oid;
		$map['ostaus'] = 1;

		$order = Db::name('LcOrder')->where($map)->find();

		return base64_encode(json_encode($order));
	}

	public function inquiries() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');

		$map['uid'] = $uid;
		$map['ostaus'] = 1;

		$this->list = Db::name('LcOrder')->where($map)->order('id desc')->select();

		$this->fetch();
	}

	public function yeb() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');

		//var_dump($uid);die;
		$where['id'] = $uid;
		$userinfo = Db::table('lc_user')->where($where)->find();
		$userbalance = $userinfo['money'];
		$map['uid'] = $uid;
		$yuebaouc = Db::name('lc_yuebao_uc')->where($map)->find();
		//
		if (empty($yuebaouc)) {
			$yuebaoucdata = array('uid' => $uid, 'remarks' => time() . "系统自动开户");
			Db::name('lc_yuebao_uc')->insert($yuebaoucdata);
			Db::name('lc_yuebao_uclog')->insert($yuebaoucdata);
			$yuebaouc = Db::name('lc_yuebao_uc')->where($map)->find();
		};
		$map['status'] = 1;
		$yuebao = Db::name('lc_yuebao')->where('status = 1')->order('id desc')->select();
		$yuebaolist = array();
		foreach ($yuebao as $x => $v) {
			$v['finishprofit'] = round($v['lowmoney'] * $v['lily'] * $v['days'] / 100 / 365, 4);
			$yuebaolist[$x] = $v;
		};
		$yuebao = $yuebaolist;
		$useryebdoing = Db::name('lc_yuebao_lists')->where($map)->order('id desc')->select();
		$doinglist = array();
		foreach ($useryebdoing as $x => $v) {
			$v['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
			$v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
			$doinglist[$x] = $v;
		};
		$map['status'] = 2;
		$useryebclosed = Db::name('lc_yuebao_lists')->where($map)->order('id desc')->select();
		$closedlist = array();
		foreach ($useryebclosed as $x => $v) {
			$v['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
			$v['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
			$closedlist[$x] = $v;
		};
		$this->assign('userbalance', $userbalance);
		$this->assign('yuebao', $yuebao);
		$this->assign('yuebaouc', $yuebaouc);
		$this->assign('doinglist', $doinglist);
		$this->assign('closedlist', $closedlist);
		$this->fetch();
	}

	public function yebtrans() {
		Db::startTrans();
		try {
			$uid = $this->app->session->get('uid');
			$money = (float)input('post.money');
			if ($money <= 0) {
				return json(['code' => 500, 'msg' => 'The transfer amount is wrong!']);
			}
			$uc = Db::table('lc_yuebao_uc')->lock(true)->where('uid', $uid)->find();
			if ($money > $uc['trans_balance']) {
				return json(['code' => 500, 'msg' => 'Greater than the transferable amount!']);
			}
			Db::table('lc_yuebao_uc')->where('id', $uc['id'])->limit(1)->update(['trans_balance' => round($uc['trans_balance'] - $money, 1)]);
			$userMoney = DB::table('lc_user')->where('id', $uid)->value('money');
			Db::table('lc_user')->where('id', $uid)->limit(1)->update(['money' => round($userMoney + $money, 1)]);
			DB::table('lc_finance')->insert(['uid' => $uid, 'money' => $money, 'type' => 1, 'reason' => '利息宝转出', 'before' => $userMoney, 'time' => date('Y-m-d H:i:s')]);
			return json(['code' => 200, 'msg' => '转出成功!']);
			Db::commit();
		} catch (Exception $e) {
			Db::rollback();
			return json(['code' => 500, 'msg' => '转出失败!']);
		}
	}

	public function yebjoinnow() {
		//
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		//优先核对账户余额，并扣减。
		$where['id'] = $uid;
		$userinfo = Db::table('lc_user')->where($where)->find();
		if ($userinfo['money'] < $_POST['money']) {
			return "The account balance is insufficient, please confirm";
			die;
		};

		//查找余额宝理财方案信息，并校检。
		$where['id'] = $_POST['yebid'];
		$yebinfo = Db::table('lc_yuebao')->where($where)->find();
		if ($yebinfo['lowmoney'] > $_POST['money']) {
			return "您办理的方案有最低存入：" . $yebinfo['lowmoney'] . "元，请确认";
			die;
		};
		$newmoney = $userinfo['money'] - $_POST['money'];
		$res = Db::table('lc_user')->where('id', $userinfo['id'])->update(['money' => $newmoney]);
		if (!$res) {
			return "Network anomalies, please try again later";
			die;
		}
		$res = DB::table('lc_finance')->insert(['uid' => $userinfo['id'], 'money' => $_POST['money'], 'type' => 2, 'reason' => '利息宝转入', 'before' => $userinfo['money'], 'time' => date('Y-m-d H:i:s')]);
		if (!$res) {
			return "Network anomalies, please try again later";
			die;
		}
		//保存办理记录。
		$savelist = array('uid' => $uid, 'username' => $userinfo['phone'], 'yuebaoid' => $yebinfo['id'], 'yebtitle' => $yebinfo['title'], 'lily' => $yebinfo['lily'], 'money' => $_POST['money'], 'days' => $yebinfo['days'], 'start_time' => time(), 'end_time' => time() + $yebinfo['days'] * 86400, 'nowprofit' => 0, 'finishprofit' => round(($_POST['money'] * $yebinfo['lily'] / 100 / 365) * $yebinfo['days'], 4), 'status' => 1);
		$newid = Db::table('lc_yuebao_lists')->insertGetId($savelist);
		if (!$newid) {
			return "Network anomalies, please try again later";
			die;
		}
		//再做UC
		unset($where['id']);
		$where['uid'] = $uid;
		$yebucinfo = Db::table('lc_yuebao_uc')->where($where)->find();
		$newbalance = $yebucinfo['balance'] + $_POST['money'];
		Db::table('lc_yuebao_uc')->where($where)->update(['balance' => $newbalance]);
		//再做UCLOG
		$yebuclog = array('uid' => $uid, 'balance' => $yebucinfo['balance'], 'money' => $_POST['money'], 'addtime' => time(), 'remarks' => "User purchases a financial plan：" . $yebinfo['title']);
		Db::table('lc_yuebao_uclog')->insert($yebuclog);
		return "ok";
		die;
		//扣减余额

		var_dump($_POST);
		die;

	}

	public function yebstop() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');

		$getlistinfo = Db::table('lc_yuebao_lists')->where('id=' . $_POST['id'])->find();

		if ($getlistinfo['status'] != 1 or empty($getlistinfo)) {
			return "操作失败：订单无法操作！";
			die;
		}

		$getuserinfo = Db::table('lc_user')->where('id=' . $getlistinfo['uid'])->find();
		//var_dump($getlistinfo,$getuserinfo);die;
		if (!empty($getuserinfo)) {
			Db::table('lc_yuebao_lists')->where('id=' . $_POST['id'])->update(['status' => 2, 'end_time' => time()]);
			//记录日志！
			unset($getlistinfo['id']);
			$getlistinfo['status'] = 2;
			$getlistinfo['end_time'] = time();
			$getlistinfo['balance'] = $getuserinfo['money'];
			$getlistinfo['closetime'] = time();
			$getlistinfo['remarks'] = "客户手动结算";
			Db::table('lc_yuebao_log')->insert($getlistinfo);
			//更新用户余额
			$newbalance = $getuserinfo['money'] + $getlistinfo['nowprofit'] + $getlistinfo['money'];
			Db::table('lc_user')->where('id=' . $getlistinfo['uid'])->update(['money' => $newbalance]);
			//更新UC
			$where['uid'] = $uid;
			$yebucinfo = Db::table('lc_yuebao_uc')->where($where)->find();
			$newbalance = $yebucinfo['balance'] - $getlistinfo['money'];
			Db::table('lc_yuebao_uc')->where($where)->update(['balance' => $newbalance]);
			//再做UCLOG
			$yebuclog = array('uid' => $uid, 'balance' => $yebucinfo['balance'], 'money' => $getlistinfo['money'], 'addtime' => time(), 'remarks' => "用户购买理财方案：" . $getlistinfo['title']);
			Db::table('lc_yuebao_uclog')->insert($yebuclog);

			return "ok";
			die;

		}
		else {
			return "操作失败：订单无法操作！";
			die;
		}

	}

	public function yebkeep() {
		$uid = $this->app->session->get('uid');
		if (!$uid)
			$this->redirect('/index/login');
		$getlistinfo = Db::table('lc_yuebao_lists')->where('id=' . $_POST['id'])->find();
		if ($getlistinfo['status'] == 2) {
			return "操作失败：订单无法操作！";
			die;
		}
		elseif ($getlistinfo['status'] == 1) {
			$getlistinfo['end_time'] = $getlistinfo['end_time'] + $getlistinfo['days'] * 86400;
		}
		unset($getlistinfo['id']);
		Db::table('lc_yuebao_lists')->where('id=' . $_POST['id'])->update(['end_time' => $getlistinfo['end_time']]);
		return "Successful operation";
		die;
	}
	/**
	 * @description：身份认证
	 * @date: 2020/5/14 0014
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function verified() {
		$uid = input('uid','');
		if (!$uid) $this->error('用户ID不能为空');
		$this->user = Db::name('LcUser')->find($uid);
		if ($this->request->isPost()) {
            if($this->user['rz_status'] == 2){
                $this->error('已认证，请勿重复提交！');
            }
            $name = input('name','');
            if (!$name) $this->error('姓名不能为空！');
            $idcard = input('idcard','');
            if (!$idcard) $this->error('身份证号不能为空！');
            $z_id_card = input('z_id_card','');
            if (!$z_id_card) $this->error('身份证正面照不能为空！');
            $f_id_card = input('f_id_card','');
            if (!$f_id_card) $this->error('身份证反面照不能为空！');
			$data = input('');
			$check = Db::name('LcUser')->where("idcard = '{$data['idcard']}' AND id <> ".$uid)->find();
			if ($check)
				$this->error('该身份证号已被其他账户绑定！');
			if (getInfo('cert') == 1) {
				$auth_check = idCardAuth($data['idcard'], $data['name']);
				if ($auth_check['code'] == 0)
					$this->error($auth_check['msg']);
			}
			else {
				if (!judge($data['name'], 'name'))
					$this->error('姓名不正确！');
				if (!judge($data['idcard'], 'idcard'))
					$this->error('身份证号不正确！');
			}
			if (Db::name('LcUser')->where(['id' => $uid])->update(['name' => $data['name'], 'idcard' => $data['idcard'],'z_id_card' => $data['z_id_card'],'f_id_card' => $data['f_id_card'], 'rz_status' => 1])) {
				$this->success('认证信息提交成功！');
			}
			else {
				$this->error('认证信息提交失败！');
			}
		}
		$this->success('认证信息查询成功',$this->user);
	}
}