<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
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

namespace app\akszadmin\controller;

use library\Controller;
use think\Db;

/**
 * 提现管理
 * Class Item
 * @package app\akszadmin\controller
 */
class Cash extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'LcCash';

    /**
     * 提现记录
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '提现记录';
        $query = $this->_query($this->table)->alias('i')->field('i.*,u.phone,u.name,b.name as bank_name');
        $query->join('lc_user u','i.uid=u.id')->join('lc_bank b','i.bid=b.id','LEFT')->equal('i.status#i_status')->like('u.phone#u_phone,b.name#b_name')->dateBetween('i.time#i_time')->order('i.id desc')->page();
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            // 优先显示银行卡开户姓名，若无则回退到提现记录中的name
            $vo['bank_account_name'] = !empty($vo['bank_name']) ? $vo['bank_name'] : $vo['name'];
        }
    }
    public function edit()
    {
        $this->applyCsrfToken();
		if($this->request->isPost()) {
		   $id = $this->request->param('id');
		   $reaolae = $this->request->param('reaolae');
		   $cash = Db::name($this->table)->find($id);
		   if(!$cash){
		       $this->error('提现订单不存在');
		   }
		   if($cash['status'] != 0){
		       $this->error('提现订单已处理，请勿重复操作');
		   }
		   $admin_id = session('user.id');
		   $admin_name = session('user.username');
		   $admin_ip = $this->request->ip();
		   $log_text = "管理员{$admin_name}(ID:{$admin_id})拒绝 IP:{$admin_ip}";
		   addFinance($cash['uid'], $cash['money'],1, '提现失败，返还金额' . $cash['money'] . '元');
		   setNumber('LcUser', 'money', $cash['money'], 1, "id = {$cash['uid']}");
		   $this->_save($this->table, ['reaolae'=>$reaolae . ' [' . $log_text . ']','status' => '2', 'time2' => date('Y-m-d H:i:s')]);
		}else{
			$this->title = '拒绝提现';
			$id = $this->request->param('id');
			$recharge = Db::name($this->table)->find($id);
			 
			if($recharge){
				$recharge['username'] = Db::name("LcUser")->where(['id'=>$recharge['uid']])->value('phone');
			}
			$this->_form($this->table, 'edit');
		}
    } 
    /**
     * 同意提现
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function agree()
    {
        $this->applyCsrfToken();
        $id = $this->request->param('id');
        $cash = Db::name($this->table)->find($id);
        if (!$cash) {
            $this->error('提现订单不存在');
        }
        if ($cash['status'] != 0) {
            $this->error('提现订单已处理，请勿重复操作');
        }
        // 防止自动审核：提现申请提交后至少需要120秒才能审核通过
        $submit_time = strtotime($cash['time']);
        if (time() - $submit_time < 120) {
            $this->error('提现申请提交不足2分钟，请稍后再审核');
        }
        $admin_id = session('user.id');
        $admin_name = session('user.username');
        $admin_ip = $this->request->ip();
        $log_text = "管理员{$admin_name}(ID:{$admin_id})审核通过 IP:{$admin_ip} " . date('Y-m-d H:i:s');
        $this->_save($this->table, ['status' => '1', 'time2' => date('Y-m-d H:i:s'), 'reaolae' => $log_text]);
    }

    /**
     * 拒绝提现
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function refuse()
    {
        $this->applyCsrfToken();
        $id = $this->request->param('id');
        $cash = Db::name($this->table)->find($id);
        if (!$cash) {
            $this->error('提现订单不存在');
        }
        if ($cash['status'] != 0) {
            $this->error('提现订单已处理，请勿重复操作');
        }
        $admin_id = session('user.id');
        $admin_name = session('user.username');
        $admin_ip = $this->request->ip();
        $log_text = "管理员{$admin_name}(ID:{$admin_id})拒绝 IP:{$admin_ip} " . date('Y-m-d H:i:s');
        addFinance($cash['uid'], $cash['money'], 1, '提现失败，返还金额' . $cash['money'] . '元');
        setNumber('LcUser', 'money', $cash['money'], 1, "id = {$cash['uid']}");
        $this->_save($this->table, ['status' => '2', 'time2' => date('Y-m-d H:i:s'), 'reaolae' => $log_text]);
    }
    
    /**
     * 删除记录
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->applyCsrfToken();
        $this->_delete($this->table);
    }
}
