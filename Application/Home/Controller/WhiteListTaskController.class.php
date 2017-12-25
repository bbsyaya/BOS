<?php
/** 白名单定时任务  把已支付的流程数据中客户等信息同步到白名单表中
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-07-20
 * Time: 11:07
 */
namespace Home\Controller;
use Common\Controller\BaseController;

class WhiteListTaskController extends BaseController {

    public function index(){

        $ml = M('white_list');

        //OA信息
        $sup = M('oa_66')->field("a.id,x739c8a_10,x739c8a_11,x739c8a_12,DATE_FORMAT(b.overtime,'%Y-%m-%d') as pay_date,x739c8a_9,x739c8a_1 as run_id")
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2')
            ->where("a.wl_status=0 and x739c8a_10 <> '' and x739c8a_11 <> '' and x739c8a_12<> ''")->select();
        foreach($sup as $key=>$val) {

            $white_list = $ml->field('opening_bank')->where(" name='".$val['x739c8a_10']."' and type=2 and bank_no='" . $val['x739c8a_12'] . "'")->find();//判断白名单中是否有此账号
            if (empty($white_list)) {//为空则添加 第一次为异常支付账户 根据客户名称和账号判断
                $add = array();
                $add['name'] = $val['x739c8a_10'];//客户名称
                $add['opening_bank'] = $val['x739c8a_11'];//客户账户
                $add['bank_no'] = $val['x739c8a_12'];//客户账号
                $add['date'] = $val['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['money'] = $val['x739c8a_9'];//金额
                $add['oa'] = $val['run_id'];//OA号
                $add['type'] = 2;
                $add['status'] = 0;
                $wl_id = $ml->add($add);
                if($wl_id){
                    //修改流程表中的状态
                    $data = array();
                    $data['id'] = $val['id'];
                    $data['wl_status'] = 1;
                    M('oa_66')->save($data);

                    //加到系统通知里面
                    M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增供应商异常收支账户'.$val['x739c8a_10'].'  '.$data['x739c8a_12'].' 点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                }
            }
        }

        //认款信息
        $advertiser = M('pay')->field('id,paymentname,adddate,money,remarks2,opening_bank,bank_no')->where("paymentname <>'' and opening_bank<>'' and bank_no<>'' and wl_status=0")->select();
        foreach($advertiser as $val1){
            if($val1['paymentname']){//根据客户名称和账号判断
                $white_list = $ml->field('opening_bank')->where("name='".$val1['paymentname']."' && bank_no='".$val1['bank_no']."' && type=1")->find();
                if(empty($white_list)){//为空则添加
                    $add = array();
                    $add['name'] = $val1['paymentname'];
                    $add['opening_bank'] = $val1['opening_bank'];
                    $add['bank_no'] = $val1['bank_no'];
                    $add['date'] = $val1['adddate'];
                    $add['addtime'] = date('Y-m-d H:i:s',time());
                    $add['money'] = $val1['money'];
                    $add['remark'] = $val1['remarks2'];
                    $add['type'] = 1;
                    $add['status'] = 0;
                    $wl_id = $ml->add($add);
                    if($wl_id){
                        //修改流程表中的状态
                        $data = array();
                        $data['id'] = $val1['id'];
                        $data['wl_status'] = 1;
                        M('pay')->save($data);

                        //加到系统通知里面
                        M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增广告主异常收支账户'.$val1['paymentname']. '点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                    }
                }
            }

        }

        //费用报销等流程数据
        // 1.费用报销审批单(排除核销的) xb27546_12='否'
        $cost = M('oa_40')->field('a.id,xb27546_15,xb27546_17,xb27546_18,b.overtime as pay_date,xb27546_9,xb27546_3 as run_id')
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.xb27546_3=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.xb27546_3 AND c.`status`=2')
            ->where("a.wl_status=0 and a.xb27546_15 <>'' and xb27546_17<>'' and xb27546_18<>'' and xb27546_12='否'")->select();

        foreach($cost as $val2) {
            $white_list = $ml->field('opening_bank')->where(" name='".$val2['xb27546_15']."' and type=2 and bank_no='" . $val2['xb27546_18'] . "'")->find();//判断白名单中是否有此账号
            if (empty($white_list)) {//为空则添加 第一次为异常支付账户 根据客户名称和账号判断
                $add = array();
                $add['name'] = $val2['xb27546_15'];//客户名称
                $add['opening_bank'] = $val2['xb27546_17'];//客户账户
                $add['bank_no'] = $val2['xb27546_18'];//客户账号
                $add['date'] = $val2['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['money'] = $val2['xb27546_9'];//金额
                $add['oa'] = $val2['run_id'];//OA号
                $add['type'] = 2;
                $add['status'] = 0;
                $wl_id = $ml->add($add);
                if($wl_id){
                    //修改流程表中的状态
                    $data = array();
                    $data['id'] = $val2['id'];
                    $data['wl_status'] = 1;
                    M('oa_40')->save($data);

                    //加到系统通知里面  (先初始化后再添加到提醒系统)
                    M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增支出异常账户'.$val2['xb27546_15'].'  '.$val2['xb27546_18'].' 点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                }
            }
        }

        //借支/行政类预付款申请单
        $yufu_cost = M('oa_60')->field('a.id,x782c92_7,x782c92_8,x782c92_9,b.overtime as pay_date,x782c92_5,x782c92_3 as run_id')
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x782c92_3=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x782c92_3 AND c.`status`=2')
            ->where("a.wl_status=0 and a.x782c92_7 <>'' and x782c92_8<>'' and x782c92_9<>'' and x782c92_9 !=''")->select();

        foreach($yufu_cost as $val3) {
            $white_list = $ml->field('opening_bank')->where(" name='".$val3['x782c92_7']."' and type=2 and bank_no='" . $val3['x782c92_9'] . "'")->find();//判断白名单中是否有此账号
            if (empty($white_list)) {//为空则添加 第一次为异常支付账户 根据客户名称和账号判断
                $add = array();
                $add['name'] = $val3['x782c92_7'];//客户名称
                $add['opening_bank'] = $val3['x782c92_8'];//开户行
                $add['bank_no'] = $val3['x782c92_9'];//银行账号
                $add['date'] = $val3['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['money'] = $val3['x782c92_5'];//金额
                $add['oa'] = $val3['run_id'];//OA号
                $add['type'] = 2;
                $add['status'] = 0;
                $wl_id = $ml->add($add);
                if($wl_id){
                    //修改流程表中的状态
                    $data = array();
                    $data['id'] = $val3['id'];
                    $data['wl_status'] = 1;
                    M('oa_60')->save($data);

                    //加到系统通知里面  (先初始化后再添加到提醒系统)
                    M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增支出异常账户'.$val3['x782c92_7'].'  '.$val3['x782c92_9'].' 点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                }
            }
        }

        //退款申请单
        $tuikuan_cost = M('oa_63')->field('a.id,x2a1540_10,x2a1540_13,x2a1540_12,b.overtime as pay_date,x2a1540_8,x2a1540_3 as run_id,x2a1540_9')
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x2a1540_3=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x2a1540_3 AND c.`status`=2')
            ->where("a.wl_status=0 and a.x2a1540_10 <>'' and x2a1540_13<>'' and x2a1540_12<>'' and x2a1540_12 !=''")->select();

        foreach($tuikuan_cost as $val4) {
            $white_list = $ml->field('opening_bank')->where(" name='".$val4['x2a1540_10']."' and type=2 and bank_no='" . $val4['x2a1540_12'] . "'")->find();//判断白名单中是否有此账号
            if (empty($white_list)) {//为空则添加 第一次为异常支付账户 根据客户名称和账号判断
                $add = array();
                $add['name'] = $val4['x2a1540_10'];//客户名称
                $add['opening_bank'] = $val4['x2a1540_13'];//开户行
                $add['bank_no'] = $val4['x2a1540_12'];//银行账号
                $add['date'] = $val4['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['money'] = $val4['x2a1540_8'];//金额
                $add['oa'] = $val4['run_id'];//OA号
                $add['type'] = 2;
                $add['status'] = 0;
                $add['remark'] = $val4['x2a1540_9'];
                $wl_id = $ml->add($add);
                if($wl_id){
                    //修改流程表中的状态
                    $data = array();
                    $data['id'] = $val4['id'];
                    $data['wl_status'] = 1;
                    M('oa_63')->save($data);

                    //加到系统通知里面  (先初始化后再添加到提醒系统)
                    M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增支出异常账户'.$val4['x2a1540_10'].'  '.$val4['x2a1540_12'].' 点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                }
            }
        }

        //差旅费报销单
        $cl_cost = M('oa_64')->field('a.id,xb4cef4_17,xb4cef4_21,xb4cef4_19,b.overtime as pay_date,xb4cef4_13,xb4cef4_3 as run_id,xb4cef4_9')
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.xb4cef4_3=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.xb4cef4_3 AND c.`status`=2')
            ->where("a.wl_status=0 and xb4cef4_11=0 and a.xb4cef4_17 <>'' and xb4cef4_21<>'' and xb4cef4_19<>'' and xb4cef4_19 !=''")->select();

        foreach($cl_cost as $val5) {
            $white_list = $ml->field('opening_bank')->where(" name='".$val5['xb4cef4_17']."' and type=2 and bank_no='" . $val5['xb4cef4_19'] . "'")->find();//判断白名单中是否有此账号
            if (empty($white_list)) {//为空则添加 第一次为异常支付账户 根据客户名称和账号判断
                $add = array();
                $add['name'] = $val5['xb4cef4_17'];//客户名称
                $add['opening_bank'] = $val5['xb4cef4_21'];//开户行
                $add['bank_no'] = $val5['xb4cef4_19'];//银行账号
                $add['date'] = $val5['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s', time());
                $add['money'] = $val5['xb4cef4_13'];//金额
                $add['oa'] = $val5['run_id'];//OA号
                $add['type'] = 2;
                $add['status'] = 0;
                $add['remark'] = $val5['xb4cef4_9'];
                $wl_id = $ml->add($add);
                if($wl_id){
                    //修改流程表中的状态
                    $data = array();
                    $data['id'] = $val5['id'];
                    $data['wl_status'] = 1;
                    M('oa_64')->save($data);

                    //加到系统通知里面  (先初始化后再添加到提醒系统)
                    M('prompt_information')->add(array('send_user'=>'640,680,671','date_time'=>date('Y-m-d H:i:s'),'content'=>'新增支出异常账户'.$val5['xb4cef4_17'].'  '.$val5['xb4cef4_19'].' 点击跳转','a_link'=>'/WhiteList/index?id='.$wl_id));
                }
            }
        }


    }
}