<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/18
 * Time: 17:37
 */
namespace Api\Controller;
class OaController extends ApiController {

    function insert_data(){//合同流程
        $postData= json_decode($_POST['data'],true);

        $contModel = M('flow_data_434');//m(表)   D('model')
        foreach($postData as $val){

            $count = $contModel->where("id=".$val['id']."")->count();
            if($count < 1){

                if ($contModel->add($val) === false) {
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }else{

                if ($contModel->save($val) === false){
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }

        }

        //file_put_contents(LOG_PATH.'test.txt',$postData."\n",FILE_APPEND|LOCK_EX);
    }

    function costPay_data(){//成本支付流程
        //echo "aa";exit;
        $postData = json_decode($_POST['data'],true);

        $contModel = M('flow_data_432');
        foreach($postData as $val){

            $count = $contModel->where("id=".$val['id']."")->count();
            if($count < 1){

                if ($contModel->add($val) === false) {
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }else{

                if ($contModel->save($val) === false){
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }

        }
    }

    function verification_data(){//核销流程

        $postData= json_decode($_POST['data'],true);

        $contModel = M('flow_data_433');
        foreach($postData as $val){

            $count = $contModel->where("id=".$val['id']."")->count();
            if($count < 1){

                if ($contModel->add($val) === false) {
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }else{

                if ($contModel->save($val) === false){
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }

        }
    }

    function BadDebt(){//坏账流程

        $postData= json_decode($_POST['data'],true);

        $contModel = M('flow_data_423');
        foreach($postData as $val){

            $count = $contModel->where("id=".$val['id']."")->count();
            if($count < 1){

                if ($contModel->add($val) === false) {
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }else{

                if ($contModel->save($val) === false){
                    echo $contModel->getError();
                }else{
                    //echo "同步成功";
                }
            }

        }
    }


    function sel_supplier(){

        /*$sp_name = $_POST['sp_name'];
        $type = $_POST['type'];//类型（结算款/预付款）
        $pl_name = $_POST['pl_id'];//业务线
        $where = " sui.name !='' and sui.status=1";

        if(!empty($sp_name)){
            $where .= " and sui.name like '%".$sp_name."%' ";
        }
        $supModel = M('supplier');
        $prefix = C('DB_PREFIX')."settlement_out";
        $prefix_a = C('DB_PREFIX')."supplier_finance";
        $prefix_b = C('DB_PREFIX')."charging_logo_assign";
        $prefix_c = C('DB_PREFIX')."business_line";
        if($type =='结算款') {

            //根据对账单中供应商id和和业务线查询供应商的财务信息
            $sup_data = $supModel->field('sui.id AS ID,sui.name AS NAME,sf.payee_name AS FI_BENEFICIARY,sf.opening_bank AS FI_BANK,sf.bank_no AS FI_ACCOUNTSRECEIVABLE')->join("AS sui left JOIN {$prefix} qic ON sui.id=qic.superid left join {$prefix_a} as sf on sf.sp_id=qic.addresserid AND sf.bl_id=qic.lineid and sf.status=1")->where('qic.status=2 and' . $where . ' ')->group('sui.id')->order('sui.id DESC')->select();

        }else if($type == '预付款'){
            $sup_data = $supModel->field('sui.id AS ID,sui.name AS NAME,sf.payee_name AS FI_BENEFICIARY,sf.opening_bank AS FI_BANK,sf.bank_no AS FI_ACCOUNTSRECEIVABLE')->join("AS sui left join {$prefix_a} as sf on sf.sp_id=sui.id and sf.status=1 left join {$prefix_c} as bbl ON bbl.id=sf.bl_id and bbl.`name`='".$pl_name."'")->where(''.$where.'')->group('sui.id')->order('sui.id DESC')->select();
        }else{
            $sup_data = $supModel->field('sui.id AS ID,sui.name AS NAME,sf.payee_name AS FI_BENEFICIARY,sf.opening_bank AS FI_BANK,sf.bank_no AS FI_ACCOUNTSRECEIVABLE')->join("AS sui left JOIN {$prefix} qic ON sui.id=qic.superid left join {$prefix_a} as sf on sf.sp_id=qic.addresserid AND sf.bl_id=qic.lineid and sf.status=1")->where('qic.status=2 and' . $where . ' ')->group('sui.id')->order('sui.id DESC')->select();
        }
        ob_clean();
        echo json_encode($sup_data);exit;*/

        //2016.12.26
        $sp_name = $_POST['sp_name'];
        $pl_name = $_POST['pl_id'];//业务线
        $supModel = M('supplier_finance');

        $where = " status=1";

        if(!empty($sp_name)){
            $where .= " and payee_name like '%".$sp_name."%' ";
        }
        $sup_data = $supModel->field('sp_id,payee_name AS FI_BENEFICIARY,opening_bank AS FI_BANK,bank_no AS FI_ACCOUNTSRECEIVABLE')->where($where)->group('payee_name')->order('id DESC')->select();
        ob_clean();
        echo json_encode($sup_data);exit;

    }

    //查询广告主
    function sel_adv(){
        $sp_name = $_POST['sp_name'];
        $where = " adv.name !='' and adv.status=1 and bbg.dhxmoney >0";

        if(!empty($sp_name)){
            $where .= " and adv.name like '%".$sp_name."%' ";
        }
        $supModel = M('beforepay_ggz_all');
        $advData  = $supModel->field('adv.id as ACI_ID,adv.`name` as `NAME`,bbg.dhxmoney as MONEY,adv.opening_bank as FI_BANK,adv.bank_no as FI_ACCOUNTSRECEIVABLE')->join('AS bbg
LEFT JOIN `boss_advertiser` AS adv ON bbg.adverid=adv.id')->where($where)->order('adv.id desc')->select();
        //echo $supModel->getLastSql();exit;
        ob_clean();
        echo json_encode($advData);exit;

    }

    function sel_senddata(){

        $pr_name= $_POST['pr_name'];//产品名称
        $sw_name= $_POST['sw_name'];//商务姓名
        $start_date= $_POST['start_date'];//账期开始时间
        $end_date= $_POST['end_date'];//账期结束时间

        //2016.12.26
        $sid =  $_POST['sid'];//收款方名称
        $pl_id =  $_POST['pl_id'];//业务线名称
        //$sid = '深圳市德创网络有限公司';//收款方名称
        //$pl_id = '优效分发平台';//业务线名称
        //$sid= json_decode($_POST['sid'],true);

        $sendModel = M('settlement_out');
        $prefix = C('DB_PREFIX')."supplier";
        $prefix_s = C('DB_PREFIX')."data_dic";//签订主体(字典库)
        $prefix_u = C('DB_PREFIX')."user";

        //根据收款方名称和业务线名称查询供应商id
        $spData = M('supplier_finance')->field('sui.sp_id')->join("AS sui
LEFT JOIN boss_business_line AS bbl ON bbl.id=sui.bl_id")->where("sui.status=1 AND bbl.`name`='".$pl_id."' AND sui.payee_name='".$sid."'")->select();
        //echo json_encode(M('supplier_finance')->getLastSql());exit;
        //echo json_encode($spData);exit;
        $sp_id = "";
        foreach ($spData as $sp_val){
            $sp_id .=$sp_val['sp_id'].",";
        }
        $sp_id = rtrim($sp_id,",");
        //echo json_encode($sp_id);exit;
        $where = " bso.`status`=2";
        if(!empty($sp_id)){
            $where .= " and bso.addresserid in ($sp_id) ";
        }
        if(!empty($pr_name)){
            $where .= " and bso.allcomname like '%".$pr_name."%' ";
        }
        if(!empty($sw_name)){
            $userModel = M('user');
            $userData = $userModel->field('id')->where("real_name like '".$sw_name."' ")->select();
            $uid = "";
            foreach($userData as $u_val){
                $uid .=$u_val['id'].",";
            }
            $uid = rtrim($uid, ",");
            $where .= " and bso.sangwuid in (".$uid.") ";
        }
        if(!empty($start_date)){
            $where .= " and bso.strdate >='".$start_date."' ";
        }
        if(!empty($end_date)){
            $where .= " and bso.enddate <='".$end_date."' ";
        }
        //notaxmoney:不含税金额  settlementmoney结算金额（含税金额）
        $sendData = $sendModel->field('bso.id,bso.allcomname AS pr_name,bso.strdate as perioddatestart,bso.enddate AS perioddate,FORMAT(bso.notaxmoney,2) AS real_money,bsb.`name` AS SB_NAME,bu.real_name AS SO_SALESPERSON')->join("AS bso left join {$prefix} as bs on bso.superid =bs.id left join {$prefix_s} as bsb on bsb.id=bso.jsztid left join {$prefix_u} as bu ON bu.id=bso.sangwuid")->where($where)->order('bso.id DESC')->select();
        //echo $sendModel->getLastSql();exit;
        ob_clean();
        echo json_encode($sendData);exit;

    }

    /*考勤机同步数据到数据库 2017.04.18*/
    function sync_check_time(){

        if(empty($_POST)){
            $result['info'] = 'data not exist';
            $result['status'] = 0;
            echo json_encode($result);
            exit();
        }
        $user = M('user');
        $attend_duty = M('attend_duty');
        $attend_config = M('attend_config');
        $attend_holiday = M('attend_holiday');
        $attend_no_duty_remark = M('attend_no_duty_remark');
        $user_ext = M('user_ext');
        switch($_POST['request_type']){
            case 'getUser':{
                $uid = $_POST['uid'];
                $name = $_POST['name'];
                if(!empty($uid)){
                    $where = " and id=".$uid;
                } else {
                    $where = " and real_name like '%".$name."%'";
                }

                $list = $user->field('*')->where("1=1 ".$where."")->find();
                if(!empty($_POST['debug'])){
                    var_dump($list);
                    echo $user->getLastSql();
                }
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            case 'getDuty':{
                $page = $_POST['page'];
                $pageSize = $_POST['pageSize'];
                if(empty($page)){
                    $page = 1;
                }
                if(empty($pageSize)){
                    $pageSize = 50;
                }
                $limit = ($page-1)*$pageSize.','.$pageSize;
                $where = "";
                if(!empty($_POST['date'])){
                    $where .= " and date_format(REGISTER_TIME,'%Y%m') = '".$_POST['date']."'";
                }
                $list = $attend_duty->field('*')->where("1=1 ".$where."")->limit($limit)->select();
                $count = $attend_duty->where("1=1 ".$where."")->count();
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['count'] = $count;
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            case 'delDuty':{
                $where = "";
                if(!empty($_POST['date'])){
                    $where .= " and date_format(REGISTER_TIME,'%Y%m%d') = '".$_POST['date']."'";
                }
                if(!empty($_POST['user_id'])){
                    $where .= " and USER_ID = '".$_POST['user_id']."'";
                }
                $attend_duty->where("1=1 ".$where."")->delete();
            };break;
            case 'getAllUser':{
                $page = $_POST['page'];
                $pageSize = $_POST['pageSize'];
                if(empty($page)){
                    $page = 1;
                }
                if(empty($pageSize)){
                    $pageSize = 50;
                }
                $limit = ($page-1)*$pageSize.','.$pageSize;
                $where = "";
                $list = $user->field('*')->where("1=1 ".$where."")->limit($limit)->select();
                $count = $user->where("1=1 ".$where."")->count();
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['count'] = $count;
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            case 'getDutyConfig':{
                $list = $attend_config->field('*')->select();
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['count'] = count($list);
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            case 'getHoliday':{
                $list = $attend_holiday->field('*')->select();
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['count'] = count($list);
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            case 'getDutyRemark':{
                $page = $_POST['page'];
                $pageSize = $_POST['pageSize'];
                if(empty($page)){
                    $page = 1;
                }
                if(empty($pageSize)){
                    $pageSize = 50;
                }
                $limit = ($page-1)*$pageSize.','.$pageSize;
                $where = "";
                $list = $attend_no_duty_remark->field('*')->where("1=1 ".$where."")->limit($limit)->select();
                $count = $attend_no_duty_remark->where("1=1 ".$where."")->count();
                foreach($list as $k=>$v){
                    foreach($v as $k2=>$v2){
                        $list[$k][$k2] = $v2;
                    }
                }
                $result['info'] = 'ok';
                $result['data'] = $list;
                $result['count'] = $count;
                $result['status'] = 1;
                echo json_encode($result);
                exit();
            };break;
            default:{
                $uid = $_POST['uid'];
                $check_time = $_POST['check_time'];
                if(!empty($uid)){
                    //--匹配OA系统的用户账号
                    $user = $user->field('username')->where("uid=".$uid)->find();//uid为oa对应id，之前为("id=".$uid)
                    $user_id = $user['username'];
                    if(empty($user_id)){
                        $result['info'] = 'uid not exist';
                        $result['status'] = 0;
                        echo json_encode($result);
                        exit();
                    }

                    //--匹配系统的用户打卡方式
                    $user_ext = $user_ext->field('DUTY_TYPE')->where("USER_ID='".$user_id."'")->find();
                    $duty_type = $user_ext['duty_type'];
                    if(empty($duty_type)){
                        $duty_type = 2;
                    }

                    //--匹配打卡方式的上下班时间
                    $check_way = $attend_config->field('DUTY_TIME1,DUTY_TIME2')->where("DUTY_TYPE = {$duty_type}")->find();//获取打卡方式对应的时间
                    $duty_time = $check_way['duty_time2'];//下班时间
                    $duty_time1 = $check_way['duty_time1'];//上班时间

                    $date = date('Y-m-d',strtotime($check_time));
                    $time = strtotime(date('H:i:s',strtotime($check_time)));
                    $register_type = 1;
                    if($time>strtotime("13:30:59")){
                        $register_type = 2;
                    }

                    //查询单日打卡数据
                    $dutyList = $attend_duty->field('REGISTER_TIME')->where("date_format(REGISTER_TIME,'%Y-%m-%d') = '".$date."' and USER_ID='".$user_id."' and REGISTER_TYPE = ".$register_type."")->limit(1)->select();

                    if($dutyList){
                        if($time>strtotime("11:59:59")){
                            $register_type = 2;
                            $dutyList =$attend_duty->field('REGISTER_TIME')->where("date_format(REGISTER_TIME,'%Y-%m-%d') = '".$date."' and USER_ID='".$user_id."' and REGISTER_TYPE = ".$register_type."")->limit(1)->select();
                        }
                    }

                    if($dutyList){//更新
                        $registerTime = $dutyList[0]['register_time'];
                        //$query = "update attend_duty set REGISTER_TIME = '".$check_time."' where date_format(REGISTER_TIME,'%Y-%m-%d') = '".$date."' and USER_ID='".$user_id."' and REGISTER_TYPE = ".$register_type;
                        switch($register_type){
                            case 1:{//上班
                                if($time<strtotime(date('H:i:s',strtotime($registerTime)))){
                                    //$re = $td_oa->execs($query);
                                    $data['REGISTER_TIME'] = $check_time;
                                    $re = $attend_duty->where("date_format(REGISTER_TIME,'%Y-%m-%d') = '".$date."' and USER_ID='".$user_id."' and REGISTER_TYPE = ".$register_type."")->save($data);
                                    if($re){
                                        $result['info'] = $user_id.' am checktime save ok';
                                        $result['status'] = 1;
                                    } else {
                                        $result['info'] = $user_id.' am checktime save no';
                                        $result['status'] = 0;
                                    }
                                } else {
                                    $result['info'] = $user_id.' am checktime save no';
                                    $result['status'] = 0;
                                }
                            };break;
                            case 2:{//下班
                                if($time>strtotime(date('H:i:s',strtotime($registerTime)))){
                                    //$re = $td_oa->execs($query);
                                    $data['REGISTER_TIME'] = $check_time;
                                    $re = $attend_duty->where("date_format(REGISTER_TIME,'%Y-%m-%d') = '".$date."' and USER_ID='".$user_id."' and REGISTER_TYPE = ".$register_type."")->save($data);
                                    if($re){
                                        $result['info'] = $user_id.' pm checktime save ok';
                                        $result['status'] = 1;
                                    } else {
                                        $result['info'] = $user_id.' pm checktime save no';
                                        $result['status'] = 0;
                                    }
                                } else {
                                    $result['info'] = $user_id.' pm checktime save no';
                                    $result['status'] = 0;
                                }
                            };break;
                            default:{
                                $result['info'] = 'register_type not exist';
                                $result['status'] = 1;
                            };break;
                        }
                    } else {//添加
                        $addData = array();
                        $addData['USER_ID'] = $user_id;
                        $addData['REGISTER_TYPE'] = $register_type;
                        $addData['REGISTER_TIME'] = $check_time;
                        $addData['REGISTER_IP'] = '127.0.0.1';
                        $addData['DUTY_TYPE'] = $duty_type;
                        $re = $attend_duty->add($addData);
                        //$query = "insert into attend_duty(USER_ID,REGISTER_TYPE,REGISTER_TIME,REGISTER_IP,DUTY_TYPE) values('".$user_id."','".$register_type."','".$check_time."','127.0.0.1',".$duty_type.")";
                        //$re = $td_oa->execs($query);
                        if($re){
                            $result['info'] = $user_id.' checktime add ok';
                            $result['status'] = 1;
                        } else {
                            $result['info'] = $user_id.' checktime add no';
                            $result['status'] = 0;
                        }
                    }
                    echo json_encode($result);
                    exit();
                } else {
                    $result['info'] = 'uid data not exist';
                    $result['status'] = 0;
                    echo json_encode($result);
                    exit();
                }
            };break;
        }
    }
}