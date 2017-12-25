<?php
/**同步成本支付数据到用友
 * 和测试产品到期提醒
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/21
 * Time: 16:24
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class PaymentTaskController extends BaseController
{

    //同步成本支付数据到用友  2017/6/21
    public function index()
    {
        /*2017.03.08同步付款数据到用友系统 start*/
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        $gcm  = "/finanInter";
        $key = "1qaz#EDC5tgb&UJM";
        $middle = base64_encode($gcm.$key);
        $date_time = date('YmdHi',time());
        $date_time = base64_encode($date_time.'L');
        $token = $uuid.$middle.$date_time;
        $http_r = 'http://bos3api.yandui.com:16088';

        //付款(应付管理 已付清)
        $fk_f = '/payMent/insertPayment';
        $fkModel = M('oa_66');
        $fk_url = $http_r.$gcm.$fk_f.'?token='.$token;
        $fkData = $fkModel->field("DATE_FORMAT(b.overtime,'%Y-%m-%d') as ddate,bf.x739c8a_13 as BillCode,sup.`code` as cVenCode,sup.`name` as cVenName,b_code.`code` AS AccID,fin.financial_tax as TaxRate,bf.x739c8a_9 as Money,bf.x739c8a_1 as OaCode,bf.id")->join('AS bf
        LEFT JOIN boss_oa_tixing b ON bf.x739c8a_1=b.liuchenid AND b.jiedianid=791
        JOIN boss_oa_liuchen c on c.liuchenid=bf.x739c8a_1 AND c.`status`=2

LEFT JOIN boss_settlement_out AS bs ON bs.id = left(bf.x739c8a_13,char_length(bf.x739c8a_13)-1)
LEFT JOIN boss_supplier AS sup ON sup.id=bs.addresserid
LEFT JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid
LEFT JOIN boss_supplier_finance AS fin ON fin.sp_id=bs.superid AND fin.`status`=1 AND bs.lineid=fin.bl_id')->where("yy_status=0 and b.overtime like '%".date('Y-m')."%' and bf.x739c8a_9>0")->order('bf.id desc')->limit(0,50)->select();
        //echo M()->getLastSql();exit;
        $aid = "";
        foreach($fkData as $key=>$val){
            $aid .=$val['id'].",";
            $fkData[$key]['dDate'] = $val['ddate'];
            $fkData[$key]['BillCode'] = $val['billcode'];
            $fkData[$key]['cVenCode'] = $val['cvencode'];
            $fkData[$key]['cVenName'] = $val['cvenname'];
            $fkData[$key]['AccID'] = $val['accid'];
            $fkData[$key]['TaxRate'] = $val['taxrate'];
            $fkData[$key]['Money'] = $val['money'];
            $fkData[$key]['OaCode'] = $val['oacode'];
        }
        $aid = rtrim($aid, ",");
        $fkData = json_encode($fkData);

        $fk_res = bossPostData_json($fk_url,$fkData);
        $fkRes = json_decode($fk_res,true);
        $res = $fkRes['message'];
        if($res == "success"){
            $change = array();
            $change['id'] = array('in',$aid);
            $change['yy_status'] = 1;
            $fkModel->save($change);
        }else{
            echo $res."-成本付款数据同步到用友系统失败";
        }
        /*2017.03.08同步付款数据到用友系统 end*/

        /*测试产品到期提醒 2017.04.17*/
        //日期和金额到期后进行提示
        $userData = M('user')->field('id')->where("username='tanbo'")->find();

        $user_n = M('user')->field('id')->where("username IN ('yaowang','liufanfan','wanglei')")->select();
        $usid = '';
        foreach($user_n as $key2=>$val2){
            $usid .=$val2['id'].",";
        }
        $usid = rtrim($usid,",");

        $proData = M('product')->field('id,`name`,saler_id,order_test_type,order_test_quota')->where("cooperate_state=2")->select();
        foreach($proData as $key=>$val){

            $user_id = $val['saler_id'].','.$userData['id'];
            $content = "产品".'"'.$val['name'].'"'." 测试已到期，请处理";

            if($val['order_test_type'] == 1){//测试类型为时间
                $day = M('daydata')->field('adddate')->where("comid=".$val['id'])->group('adddate')->limit(1)->find();
                //echo date('Y-m-d', strtotime($day['adddate'].' +'.$val['order_test_quota'].' day'));exit;
                if(strtotime(date('Y-m-d',time())) >=strtotime($day['adddate'].' +'.$val['order_test_quota'].' day')){//当前时间大于约定时间
                    //入库约定测试时间+7天后还在测试的
                    if(strtotime(date('Y-m-d',time())) >=strtotime($day['adddate'].' +'.($val['order_test_quota'] +7).' day')){

                        $user_id = $user_id.','.$usid;
                        $content = "产品".'"'.$val['name'].'"'." 测试已逾7天，请处理";
                    }
                    //到期进行提示
                    $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    if(empty($dp['id'])){
                        $add = array();
                        $add['date_time'] = date('Y-m-d H:i:s',time());
                        $add['send_user'] = $user_id;
                        $add['content'] = $content;
                        $add['a_link'] = '/Product/index?id='.$val['id'];
                        $add['oa_number'] = $val['id'];
                        M('prompt_information')->add($add);
                    }
                }
            }elseif($val['order_test_type'] == 2){//量级
                $dayNum = M('daydata')->field('SUM(newdata) AS newdata')->where("comid=".$val['id'])->find();
                if($dayNum['newdata'] >= $val['order_test_quota']){
                    //到期进行提示
                    $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    if(empty($dp['id'])){
                        $add = array();
                        $add['date_time'] = date('Y-m-d H:i:s',time());
                        $add['send_user'] = $val['saler_id'].','.$userData['id'];
                        $add['content'] = "产品".'"'.$val['name'].'"'." 测试已到期，请处理";
                        $add['a_link'] = '/Product/index?id='.$val['id'];
                        $add['oa_number'] = $val['id'];
                        M('prompt_information')->add($add);
                    }

                }
            }elseif($val['order_test_type'] == 3){//金额
                $dayMon = M('daydata')->field('SUM(newmoney) AS newmoney')->where("comid=".$val['id'])->find();
                if($dayMon['newmoney']>=$val['order_test_quota']){
                    if($dayMon['newmoney'] - $val['order_test_quota']>50000){
                        $user_id = $user_id.','.$usid;
                        $content = "产品".'"'.$val['name'].'"'." 测试金额已超过5万，请处理";
                    }
                    $dp = M('prompt_information')->field('id')->where("oa_number=".$val['id'])->find();
                    if(empty($dp['id'])){
                        $add = array();
                        $add['date_time'] = date('Y-m-d H:i:s',time());
                        $add['send_user'] = $user_id;
                        $add['content'] = $content;
                        $add['a_link'] = '/Product/index?id='.$val['id'];
                        $add['oa_number'] = $val['id'];
                        M('prompt_information')->add($add);
                    }
                }
            }
        }
        /*测试产品到期提醒 2017.04.17 end*/
    }
}