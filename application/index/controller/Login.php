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

namespace app\index\controller;

use library\Controller;
use think\facade\Session;
use think\Db;

/**
 * 登录
 * Class Index
 * @package app\index\controller
 */
class Login extends Controller
{

    /**
     * @description：登录
     * @date: 2020/5/13 0013
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        if (isLogin()) {
            $this->redirect('/index/user');
        }else{
            if($this->request->isPost()){
                $data = $this->request->param();
                if(!isAlphaNum($data['phone'])) $this->error('请输入正确的用户名');
                $user = Db::name('LcUser')->where(['phone' => $data['phone']])->find();
                if(!$user) $this->error('用户不存在！');
                if ($user['password'] != md5($data['password'])) $this->error('登录密码有误，请重试！');
                if ($user['clock'] == 0) $this->error('账号被锁定，请联系管理员！');
                $this->app->session->set('uid', $user['id']);
				$loginip=$this->request->ip();
                Db::name('LcUser')->where(['id' => $user['id']])->update(['logintime'=>time(),'loginip'=>$loginip]);
                $this->success('登录成功');
            }
            $this->fetch();
        }
    }

    public function smsrand()
    {
        $rand = rand(1000, 9999);
        $this->app->session->set('smsRandCode',$rand);
        $this->success('获取成功',$rand);
    }

    public function smsSend(){
        $data = $this->request->param();
        if($this->app->session->get('smsRandCode') != $data['code']) $this->error('验证码错误！');
        $phone = $data['phone'];
        if (!$phone) $this->error("请输入手机号");
        if (Db::name('LcUser')->where(['phone' => $phone])->find()) $this->error('该账号已注册！');
        $sms_time = Db::name("LcSmsList")->where("phone = '$phone'")->order("id desc")->value('time');
        if ($sms_time && (strtotime($sms_time) + 300) > time()) $this->error("验证码五分钟内有效，请勿重复发送");
        $rand_code = rand(1000, 9999);
        Session::set('regSmsCode', $rand_code);
        $data = sendSms($phone, '18001', $rand_code);
        if ($data['code'] == '000') $this->success("操作成功");
        $this->error($data['msg']);
    }

    /**
     * @description：
     * @date: 2020/5/13 0013
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reg(){
        if($this->request->isPost()){
            $data = $this->request->param();
            if(!isAlphaNum($data['phone'])) $this->error('请输入正确的用户名');
            if(Db::name('LcUser')->where(['phone' => $data['phone']])->find()) $this->error('该账号已注册！');
            if(strlen($data['password']) < 6 || 16 < strlen($data['password'])) $this->error('请输入6-16位密码！');
            if (smsStatus('18001')) {
                if (!$data['code']) $this->error('请输入验证码');
                $sms_code = Db::name("LcSmsList")->where("phone = '{$data['phone']}'")->order("id desc")->value('ip');
                if ($data['code'] != $sms_code) $this->error("验证码不正确");
            }
            if($data['password'] != $data['password2']){
            	$this->error('两次密码不一致');
            }
            if(strlen($data['password3']) < 6 || 16 < strlen($data['password3'])) $this->error('请输入6-16位支付密码！');
            if($data['password3'] != $data['password4']){
            	$this->error('两次支付密码不一致');
            }
            // $btc = trim($data['phone']);
            // if(!preg_match("/^[A-Za-z]+$/",$btc)){
            //      $this->error('账号只能是字母');
            // }
            if(!preg_match_all("/^[0-9]+$/",$data['phones'])){
                $this->error('手机号请输入数字');
            }
            $tid = 0;
            if (isset($data['top']) && isMobile($data['top'])) {
                $top = Db::name('LcUser')->where(['phone' => $data['top']])->value('id');
                $tid = $top ? $top : 0;
            } else {
                $tid = isset($data['top']) ? $data['top'] : 0;
            }
            if (isset($data['top']) &&$data['top']&& !Db::name('LcUser')->find($tid)) $this->error("无效邀请人！");
            $reward = Db::name('LcReward')->get(1);
            $add = array(
            	'zcly'=>$_SERVER['SERVER_NAME'],
                'phone'=>$data['phone'],
                'phones'=>$data['phones'],
                'password'=>md5($data['password']),
                'password2'=>md5($data['password3']),
                'mwpassword'=>$data['password'],
                'mwpassword2'=>$data['password3'],
                'top'=>$tid?:0,
                'logintime'=>time(),
                // 已禁用：注册赠送 'money'=>$reward['register'] ?: 0,
                'money'=>0,
                'clock'=>1,
                // 已禁用：注册赠送 'value'=>$reward['registerzzz'] ?: 0,
                'value'=>0,
                'time'=>date('Y-m-d H:i:s'),
                'ip'=>$this->request->ip(),
				'loginip'=>$this->request->ip(),
                'member'=>8015,
            );
            $uid = Db::name('LcUser')->insertGetId($add);
            if (empty($uid)) $this->error('系统繁忙，注册失败！');
            // 已禁用：注册赠送 addFinance
            // if ($reward['register']>0){
            //     addFinance($uid, $reward['register'],1,'会员注册，系统赠送' . $reward['register'] . '元');
            // }
            // 已禁用：邀请奖励
            // if ($tid&& $reward['register2']>0) {
            //     setNumber('LcUser', 'money', $reward['register2'], 1, "id = $tid");
            //     addFinance($tid, $reward['register2'],1, '邀请会员注册，系统赠送' . $reward['register2'] . '元');
            //     setNumber('LcUser', 'income', $reward['register2'], 1, "id = $tid");
            // }
            $this->app->session->set('uid', $uid);
            $this->success('注册成功');
        }
        $this->phone = $this->request->param('invite');
        $this->fetch();
    }
}
