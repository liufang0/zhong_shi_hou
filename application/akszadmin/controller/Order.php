<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )s
// +----------------------------------------------------------------------
// | gitee 代码仓库3：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库3：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\akszadmin\controller;

use library\Controller;
use think\Db;

/**
 * 持仓管理
 * Class Item
 * @package app\akszadmin\controller
 */
class Order extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'LcOrder';

    /**
     * 持仓列表
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
        $this->title = '持仓列表';
		$this->type = $this->request->param('type');
		 
        $query = $this->_query($this->table)->alias('i')->field('i.*,u.phone,u.name as u_name');
        $query->leftjoin('lc_user u','i.uid=u.id')->like('u.phone#u_phone,u.name#u_name')->dateBetween('i.time#i_time')->order('i.id desc')->page();
    }

    /**
     * 编辑持仓
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '查看';
        $this->_form($this->table, 'form');
    }
	 public function edits()
    {
		if($this->app->request->isPost()){
			$param = $this->app->request->param();
			$id=$param['id'];
			
			// 获取原订单信息
			$old_order = Db::name($this->table)->find($id);
			if(!$old_order){
				$this->error('订单不存在');
			}
			
			if($param['pid']){
				$title=Db::name('LcProduct')->where(['id'=>$param['pid']])->value('title');
			};
			
			$datac['uid']=$param['uid'];
			$datac['pid']=$param['pid'];
			$datac['ptitle']=$title;
			$datac['ostyle']=$param['ostyle'];
			$datac['buyprice']=$param['buyprice'];
			$datac['sellprice']=$param['sellprice'];
			$datac['ploss']=$param['ploss'];
			$datac['buytime']=strtotime($param['buytime']);
			$datac['selltime']=strtotime($param['selltime']);
			
			// 计算commission（返还金额 = 本金 + 盈亏）
			$datac['commission'] = $old_order['fee'] + $param['ploss'];
			
			// 如果订单已结算，需要调整用户余额
			if($old_order['ostaus'] == 1){
				$old_ploss = floatval($old_order['ploss']); // 原盈亏
				$new_ploss = floatval($param['ploss']); // 新盈亏
				$ploss_diff = $new_ploss - $old_ploss; // 盈亏差额
				
				if(abs($ploss_diff) > 0.01){ // 有差额才调整
					// 调整用户余额
					if($ploss_diff > 0){
						// 新盈亏更大，增加余额
						setNumber('LcUser', 'money', abs($ploss_diff), 1, "id = {$old_order['uid']}");
						addFinance($old_order['uid'], abs($ploss_diff), 1, "订单[{$old_order['orderno']}]后台调整盈亏+{$ploss_diff}");
					} else {
						// 新盈亏更小，扣减余额
						setNumber('LcUser', 'money', abs($ploss_diff), 2, "id = {$old_order['uid']}");
						addFinance($old_order['uid'], abs($ploss_diff), 2, "订单[{$old_order['orderno']}]后台调整盈亏{$ploss_diff}");
					}
				}
			}
			
            $this->_save($this->table, $datac);
		}
        $this->title = '编辑订单信息';
		$this->goods = Db::name('LcProduct')->field("id,title")->order("sort asc,id desc")->select();
        $this->_form($this->table, 'edits');
    }

    /**
     * 改平仓
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function gaipingcang()
    {
        // $this->applyCsrfToken();
        $id = $this->request->param('id');
        $sellprice = $this->request->param('sellprice');

        $this->_save($this->table, ['sellprice' => $sellprice]);
    }
	
	/*单控操作*/
	public function dankong(){
		  $id = $this->request->param('oid');
          $kong_type = $this->request->param('kong_type');
          // 单控只设置输赢结果,不覆盖产品原有的盈亏比例
          $this->_save($this->table, ['kong_type' => $kong_type]);
	}
	
	public function remove()
    {
        if (in_array('10000', explode(',', $this->request->post('id')))) {
            $this->error('系统超级账号禁止删除！');
        }
   
        $this->_delete($this->table);
    }
}