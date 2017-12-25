<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/4/11
 * Time: 11:20
 */
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;

/**
 * 考勤管理
 * Class AttendController
 * @package Home\Controller
 */
class AttendController extends BaseController {

    public function index(){

        $where = array();
        $adF = M('attend_remark')->field('name')->select();
        $this->assign('attend_remark', $adF);

        $auth_group = M('auth_group')->field('id,title')->where('status=1')->select();
        $this->assign('auth_group', $auth_group);

        $user_department = M('user_department')->field('id,name')->select();
        $this->assign('user_department', $user_department);

        $list = $this->lists($this, $where);
        $this->assign('uid',UID);
        $this->assign('list',$list);
        $this->display();
    }

    public function getList($where, $field) {

        $user = M('user')->field('username')->where("id=".UID."")->find();
        $weekArr=array("","星期一","星期二","星期三","星期四","星期五","星期六","星期日");
        //echo $weekArr[date('N', time())];exit;
        $user_ext = M('user_ext');
        $configModel = M('attend_config');
        $rem = M('attend_no_duty_remark');
        //获取打卡类型
        $ue = $user_ext->field('DUTY_TYPE')->where("UID=".UID)->find();
        if(empty($ue['duty_type'])){
            $ue['duty_type'] = 2;
        }
        //获取打卡时间
        $cf = $configModel->field('DUTY_TIME1,DUTY_TIME2')->where("DUTY_TYPE=".$ue['duty_type'])->find();
        $time1 = $cf['duty_time1'];
        $time2 = $cf['duty_time2'];

        $s_time = '上班('.$time1.')';
        $e_time = '下班('.$time2.')';

        $DATE1 = I('get.date1');
        $DATE2 = I('get.date2');
        if(empty($DATE1) && empty($DATE2)){
            $this->assign('date1',date('Y-m-01',strtotime("-1 day")));
            $this->assign('date2',date("Y-m-d",strtotime("-1 day")));
        }else{
            $this->assign('date1',$DATE1);
            $this->assign('date2',$DATE2);
        }

        $name = I('get.name');
        $role = I('get.role');//角色
        $depart = I('get.depart');//部门
        $where = 'status=1';
        if($role){
            $where .=" and id in (SELECT uid FROM boss_auth_group_access WHERE group_id=".$role.")";
        }
        if($depart){
            $where .=" and dept_id=".$depart." ";
        }
        if($name){
            $where .= " and real_name like '%".$name."%'";
        }
        if(empty($role) && empty($depart) && empty($name)){
            $where .= " and id=".UID;
        }
        $us = M('user')->field('id,uid,username,real_name')->where($where)->select();
        //echo M('user')->getLastSql();exit;
        if(!isset($DATE1) || $DATE1 == "0000-00-00" || $DATE1 == ""){
            $DATE1 = date('Y-m-01',strtotime("-1 day"));
        }
        if(!isset($DATE2) || $DATE2 == "0000-00-00" || $DATE2 == ""){
            $DATE2 = date("Y-m-d",strtotime("-1 day"));
        }

        if($us) {
            $aa = array();
            foreach ($us as $key => $val) {
                $real_name = $val['real_name'];
                $USER_ID['username'] = $val['username'];
                $n1 = 0;//迟到
                $n2 = 0;//早退
                $n3 = 0;//未打卡
                $J = $DATE1;
                for (; $J <= $DATE2; $J = date("Y-m-d", strtotime($J) + 86400)) {
                    $bb = array();
                    $bb['date'] = $J . '(' . $weekArr[date('N', strtotime($J))] . ")";//日期
                    $bb['date_y'] = $J;
                    $bb['username'] = $real_name;//姓名

                    $attend = M('attend_duty');
                    $attendData = $attend->field('*')->where("USER_ID = '" . $USER_ID['username'] . "' and to_days(REGISTER_TIME)=to_days('" . $J . "')")->order('REGISTER_TIME desc')->select();
                    //请休假流程

                    $flowData = M('oa_44')->field('a.x95f845_8 as data_9,a.x95f845_9 as data_10,a.x95f845_6 as data_7,a.x95f845_7 as data_8,a.x95f845_3 as run_id')->join('a join boss_oa_liuchen b on a.id=b.alldata and b.mid=44')->where("'".$J."'>=date_format(a.x95f845_8,'%Y-%m-%d') and '".$J."'<=date_format(a.x95f845_9,'%Y-%m-%d') and a.nowuserid='".$val['id']."' and b.status=2")->find();
                    //print_r(M()->getLastSql());
                    if ($flowData['data_7']) {

                        if(strlen($flowData['data_9']) == 13 && strlen($flowData['data_10']) == 13 ){
                            $data_9 = $flowData['data_9'] . ':00:00';
                            $data_10 = $flowData['data_10'] . ':00:00';
                        }else{
                            $data_9 = $flowData['data_9'];
                            $data_10 = $flowData['data_10'];
                        }
                        //判断时间区间(是上午还是下午，或者全天)
                        if (date('H', strtotime($data_9)) < '10' && date('H', strtotime($data_10)) < '13') {
                            $bb['content'] = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';

                        } elseif (date('H', strtotime($data_9)) < '10' && date('H', strtotime($data_10)) >= '18') {
                            $bb['content'] = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';
                            $bb['content_a'] = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';

                        } elseif (date('H', strtotime($data_9)) > '13') {
                            $bb['content_a'] = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';
                        }else{

                          $bb['content'] = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';
                        }

                    } else {

                        $bb['content'] = '';
                        $bb['content_a'] = '';
                    }

                    if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                        $bb['in_on'] = '公休日';
                        $bb['off_on'] = '公休日';
                        $bb['ty'] = 0;
                        $bb['tys'] = 0;
                    }

                    if (count($attendData) == 1) {//只有一次打卡
                        if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                            if ($attendData[0]['register_type'] == 1) {
                                $bb['off_on'] = '公休日';
                                $bb['in_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59' . '(公休日)';
                                $bb['remark'] = $attendData[0]['remark'];
                            } elseif ($attendData[0]['register_type'] == 2) {
                                $bb['in_on'] = '公休日';
                                $bb['off_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59' . '(公休日)';
                                $bb['remark'] = $attendData[0]['remark'];
                            }
                        } else {
                            if ($attendData[0]['register_type'] == 1) {
                                $bb['in_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59';
                                $bb['off_on'] = '未打卡';
                                $remk = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$J}' and DUTY_TYPE =2")->find();
                                $bb['remark_a'] = $remk['remark'];
                                $n3 = $n3 + 1;

                                //2017.08.02
                                if (strtotime(date('H:i:' . '59', strtotime($attendData[0]['register_time']))) > strtotime($time1)) {
                                    $n1++;
                                    $bb['ty'] = 1;
                                }

                            } elseif ($attendData[0]['register_type'] == 2) {
                                $bb['in_on'] = '未打卡';
                                $bb['off_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59';
                                $remk = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$J}' and DUTY_TYPE =1")->find();
                                $bb['remark'] = $remk['remark'];
                                $n3 = $n3 + 1;

                                if (strtotime(date('H:i:' . '59', strtotime($attendData[0]['register_time']))) < strtotime($time2)) {
                                    $n2++;
                                    $bb['tys'] = 2;
                                }

                            }
                        }
                    } elseif (count($attendData) > 1) {//两次打卡

                        foreach ($attendData as $key => $val2) {

                            if (!empty($val2['register_time'])) {
                                if ($val2['register_type'] == 1) {
                                    if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                                        $bb['in_on'] = date('H:i', strtotime($val2['register_time'])) . ':59' . '(公休日)';
                                        $bb['remark'] = $val2['remark'];
                                    } else {
                                        if (strtotime(date('H:i:' . '59', strtotime($val2['register_time']))) > strtotime($time1)) {
                                            $n1++;
                                            $bb['ty'] = 1;
                                        }
                                        $bb['in_on'] = date('H:i', strtotime($val2['register_time'])) . ':59';
                                        $bb['remark'] = $val2['remark'];
                                    }


                                } elseif ($val2['register_type'] == 2) {
                                    if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                                        $bb['off_on'] = date('H:i', strtotime($val2['register_time'])) . ':59' . '(公休日)';
                                        $bb['remark_a'] = $val2['remark'];
                                    } else {
                                        if (strtotime(date('H:i:' . '59', strtotime($val2['register_time']))) < strtotime($time2)) {
                                            $n2++;
                                            $bb['tys'] = 2;
                                        }
                                        $bb['off_on'] = date('H:i', strtotime($val2['register_time'])) . ':59';
                                        $bb['remark_a'] = $val2['remark'];
                                    }

                                }
                            }

                        }
                    } elseif (M('attend_holiday')->field('HOLIDAY_NAME')->where("'" . $J . "'>=BEGIN_DATE and '" . $J . "'<=END_DATE")->find()) {

                        //节假日
                        $holiday = M('attend_holiday')->field('HOLIDAY_NAME')->where("'" . $J . "'>=BEGIN_DATE and '" . $J . "'<=END_DATE")->find();
                        if ($holiday) {
                            //$bb['in_on'] = $holiday['holiday_name'];
                            //$bb['off_on'] = $holiday['holiday_name'];
                            $bb['ty'] = 3;
                            if ($attendData[0]['register_type'] == 1) {
                                $bb['off_on'] = $holiday['holiday_name'];
                                $bb['in_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59' . '(' . $holiday['holiday_name'] . ')';
                                $bb['remark'] = $attendData[0]['remark'];
                            } elseif ($attendData[0]['register_type'] == 2) {
                                $bb['in_on'] = $holiday['holiday_name'];
                                $bb['off_on'] = date('H:i', strtotime($attendData[0]['register_time'])) . ':59' . '(' . $holiday['holiday_name'] . ')';
                                $bb['remark_a'] = $attendData[0]['remark'];
                            } else {
                                $bb['in_on'] = $holiday['holiday_name'];
                                $bb['off_on'] = $holiday['holiday_name'];
                            }
                        }

                    } elseif (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {//没有打卡
                        $n3 = $n3 + 2;
                        $bb['in_on'] = '未打卡';
                        $bb['off_on'] = '未打卡';
                        $bb['ty'] = 0;
                        $bb['tys'] = 0;
                        $remk = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$J}' and DUTY_TYPE=1")->find();
                        //echo $rem->getLastSql();exit;
                        $bb['remark'] = $remk['remark'];
                        $remks = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$J}' and DUTY_TYPE=2")->find();
                        $bb['remark_a'] = $remks['remark'];
                    }

                    $aa[] = $bb;
                }
            }
        }
        //print_r($aa);exit;
        $this->assign('s_time',$s_time);
        $this->assign('e_time',$e_time);
        $this->assign('wdk',$n3);
        $this->assign('cd',$n1);
        $this->assign('zt',$n2);
        return $aa;
    }

    public function export(){
        $re = M('attend_remark')->field('name')->select();
        $mark = '';
        foreach($re as $key=>$val){
            $mark .= $val['name'].",";
        }
        $mark = rtrim($mark,",");
        $this->assign('res',$mark);
        $this->display();
    }

    /**
     * 导出考勤数据
     */
    public function exportDataExcel(){
        set_time_limit(300);
        $where = array();

        $date = I('get.date');
        $e_date = I('get.e_date');
        $bidate = date("Y-m-d", strtotime("+1 months", strtotime($date)));
        if($date>$e_date){
            echo "结束时间不能小于开始时间";exit;
        }
        //$user_ext = M('user_ext');
        $configModel = M('attend_config');
        $user = M('user');
        $where[] = 'a.uid>0';
        $where[] = 'a.id !=741';
        $where[] = 'a.status=1';
        $where[] = "d.entry_time<'".$bidate."'";
        //$where[] = "DATE_FORMAT(d.entry_time,'%Y-%m-%d')<'".$bidate."'";
        $userData = $user->field("a.id,a.uid,a.real_name,b.name,e.name as title,DATE_FORMAT(d.entry_time,'%Y-%m-%d') as entry_time, DATE_FORMAT(d.departure_time,'%Y-%m-%d') as departure_time,d.basic_pay,d.job_salary,d.per_pay,d.level,d.company_age")->join('a join boss_user_department b on a.dept_id=b.id left join boss_oa_hr_manage d on a.id=d.user_id left join boss_oa_position e on e.id=d.duty')->where($where)->group('a.id')->select();

        if($userData) {
            foreach ($userData as $key => $val) {

                if($val['entry_time']>$date){//当月入职的情况

                    $cha = $this->get_weekend_days($val['entry_time'],$e_date,true);
                    $J = $val['entry_time'];
                    $ycq_s = $cha;
                }elseif( $val['departure_time']>$date && $val['departure_time']<$e_date){//当月离职的情况
                    
                    $lizhi = $this->get_weekend_days($val['departure_time'],$e_date,true);
                }else{

                    $ycq_s = 21.75;
                    $J = $date;
                }

                $userData[$key]['ycq'] = $ycq_s;
                //明细开始
                $n1 = 0;//迟到
                $n2 = 0;//早退
                $n3 = 0;//未打卡
                $t1 = 0;//30分内
                $t2 = 0;//31-119分内
                $t3 = 0;//120-239分内
                $t4 = 0;//240分以上
                $bingjia = 0;$shijia = 0;$chanjia = 0; $kanhujia = '';$hunjia = 0;$nianjia = 0;$sangjia = 0;$burujia = 0;$chanqianjia = 0;$shengyujia = 0;$daixinjia = 0;$tiaoxiujia =0;$jiabanDay = 0;$kuanggong = 0;$koukuan = 0;$sjcq = 0;

                $uid = $val['id'];

                $stand_salary = $val['basic_pay']+$val['job_salary']+$val['per_pay'];//工资标准=基本工资+职务工资+绩效工资

                    for (; $J <= $e_date; $J = date("Y-m-d", strtotime($J) + 86400)) {
                        if ($uid) {
                            $USER_ID = M('user')->field('username,real_name')->where("id=" . $uid)->find();

                            //获取流程里面的数据
                            $flowData = M('oa_44')->field('a.x95f845_8 as data_9,a.x95f845_9 as data_10,a.x95f845_6 as data_7,a.x95f845_7 as data_8,a.x95f845_3 as run_id')->join('a join boss_oa_liuchen b on a.id=b.alldata and b.mid=44')->where("'".$J."'>=date_format(a.x95f845_8,'%Y-%m-%d') and '".$J."'<=date_format(a.x95f845_9,'%Y-%m-%d') and a.nowuserid='".$val['id']."' and b.status=2")->find();
                            if ($flowData) {
                                if(strlen($flowData['data_9']) == 13 && strlen($flowData['data_10']) == 13 ){
                                    $data_9 = $flowData['data_9'] . ':00:00';
                                    $data_10 = $flowData['data_10'] . ':00:00';
                                }elseif(strlen($flowData['data_9']) == 16 && strlen($flowData['data_10']) == 16 ){
                                    $data_9 = $flowData['data_9'] . ':00';
                                    $data_10 = $flowData['data_10'] . ':00';
                                }else{
                                    $data_9 = $flowData['data_9'];
                                    $data_10 = $flowData['data_10'];
                                }
                                $c_day = (strtotime(date('Y-m-d', strtotime($data_10))) - strtotime(date('Y-m-d', strtotime($data_9)))) / 86400;
                                $hour = floor((strtotime($data_10) - strtotime($data_9)) % 86400 / 3600);
                                if ($hour > 0 && $hour < 8) {

                                    $hour = 4;$days = 0.5;
                                } elseif ($c_day>=0) {

                                    $hour = 8;$days = 1;
                                }
                                //产假周末也算在里面
                                switch ($flowData['data_7']) {
                                    case '产假':
                                        $chanjia = $chanjia + $days;
                                        break;
                                    case '看护假':
                                        $kanhujia = $kanhujia + $days;
                                        break;
                                }

                                if(( date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7)) {
                                    switch ($flowData['data_7']) {
                                        case '病假':
                                            $bingjia = $bingjia + $days;
                                            break;
                                        case '事假':
                                            $shijia = $shijia + $days;
                                            break;
                                        case '婚假':
                                            $hunjia = $hunjia + $days;
                                            break;
                                        case '年休假':
                                            $nianjia = $nianjia + $days;
                                            break;
                                        case '丧假':
                                            $sangjia = $sangjia + $days;
                                            break;
                                        case '哺乳假':
                                            $burujia = $burujia + $days;
                                            break;
                                        case '产检假':
                                            $chanqianjia = $chanqianjia + $days;
                                            break;
                                        case '计划生育假':
                                            $shengyujia = $shengyujia + $days;
                                            break;
                                        case '带薪休假':
                                            $daixinjia = $daixinjia + $days;
                                            break;
                                        case '调休':
                                            $tiaoxiujia = $tiaoxiujia + $hour;
                                            break;
                                    }
                                }
                            }
                            /*$ue = $user_ext->field('DUTY_TYPE')->where("UID=" . $uid)->find();//获取打卡类型
                            if(empty($ue['duty_type'])){
                                $ue['duty_type'] = 2;
                            }*/
                            $ue['duty_type'] = 2;
                            //获取打卡时间
                            $cf = $configModel->field('DUTY_TIME1,DUTY_TIME2')->where("DUTY_TYPE=" . $ue['duty_type'])->find();
                            $time1 = $cf['duty_time1'];
                            $time2 = $cf['duty_time2'];

                            $attend = M('attend_duty');
                            $attendData = $attend->field('*')->where("USER_ID = '" . $USER_ID['username'] . "' and to_days(REGISTER_TIME)=to_days('" . $J . "')")->order('REGISTER_TIME desc')->select();

                            if (count($attendData) == 1) {//只打了一次卡

                                if (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {

                                    if ($attendData[0]['register_type'] == 1) {
                                        $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                                        if($val['level'] == 1) {
                                            if($attendData[0]['remark'] == "忘打卡" or $rem['remark'] == "忘打卡"){
                                                $n3++;
                                            }
                                            /*if (empty($flowData) && ($rem['remark'] != '外出'  && $rem['remark'] != '其他' && $rem['remark'] != '入职报到')) {
                                                $n3 = $n3 + 1;
                                            }*/
                                        }

                                    } elseif ($attendData[0]['register_type'] == 2) {
                                        $rema = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=1")->find();

                                        if($val['level'] == 1) {
                                            if($attendData[0]['remark'] == "忘打卡" or $rema['remark'] == "忘打卡"){
                                                $n3++;
                                            }
                                            /*if (empty($flowData) && ($rema['remark'] != '外出'  && $rema['remark'] != '其他')) {
                                                $n3 = $n3 + 1;
                                            }*/
                                        }
                                    }
                                }
                            } elseif (count($attendData) > 1) {

                                foreach ($attendData as $key1 => $val1) {
                                    if (!empty($val1['register_time'])) {
                                        if (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {

                                            if ($val1['register_type'] == 1) {

                                                if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) > strtotime($time1)) {

                                                    $minute = floor((strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) - strtotime($time1)) % 86400 / 60);
                                                    //是否需要判断迟到有没有走流程 start $flowData为请休假流程数据
                                                    if(strlen($flowData['data_9']) == 13){
                                                        $data_9 = $flowData['data_9'] . ':00:00';
                                                    }elseif(strlen($flowData['data_9']) == 16){
                                                        $data_9 = $flowData['data_9'] . ':00';
                                                    }else{
                                                        $data_9 = $flowData['data_9'];
                                                    }
                                                    if($val['level'] == 1) {//基层才统计迟到、早退
                                                        //查询是否忘打卡
                                                        $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=1")->find();

                                                        if($val1['remark'] == '忘打卡' or $rem['remark'] == '忘打卡'){
                                                            $n3++;
                                                        }
                                                        if ($rem['remark'] != '忘打卡' && $rem['remark'] != '外出' && $rem['remark'] != '入职报到' && $rem['remark'] != '其他') {

                                                            if (empty($flowData) or date('H', strtotime($data_9)) > '12' or $val1['remark'] == '迟到') {
                                                                $n1++;
                                                                if ($minute <= 30) {
                                                                    $t1++;
                                                                    //大于5次算旷工半天
                                                                    if ($t1 >= 5) {
                                                                        $kuanggong = $kuanggong + 4;
                                                                    }
                                                                    if ($val1['remark'] == '忘打卡' && $n3 < 5 && $n3 > 0) {
                                                                        //4次忘打卡不扣款
                                                                    } else {
                                                                        //扣款
                                                                        $koukuan = $koukuan + $minute * 2;
                                                                    }
                                                                } elseif ($minute >= 31 && $minute <= 119) {
                                                                    $t2++;
                                                                    //大于5次算旷工半天
                                                                    if ($t2 >= 5) {
                                                                        $kuanggong = $kuanggong + 4;
                                                                    }
                                                                    if ($val1['remark'] == '忘打卡' && $n3 < 5 && $n3 > 0) {
                                                                        //4次忘打卡不扣款
                                                                    } else {
                                                                        //扣款
                                                                        $koukuan = $koukuan + $minute * 4;
                                                                    }
                                                                } elseif ($minute >= 120 && $minute <= 239) {
                                                                    $t3++;
                                                                    //迟到2小时以上，旷工半天
                                                                    $kuanggong = $kuanggong + 4;
                                                                } elseif ($minute >= 240) {
                                                                    $t4++;
                                                                    //迟到4小时以上，旷工一天
                                                                    $kuanggong = $kuanggong + 8;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            } elseif ($val1['register_type'] == 2) {
                                                if (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {

                                                    if($val['level'] == 1) {//基层才统计迟到、早退
                                                        if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) < strtotime($time2)) {

                                                            //查询是否是外出
                                                            $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                                                            if ($rem['remark'] != '忘打卡' && $rem['remark'] != '外出' && $rem['remark'] != '其他') {

                                                                $minute = floor((strtotime($time2) - strtotime(date('H:i:' . '59', strtotime($val1['register_time'])))) % 86400 / 60);//获取早退分钟数
                                                                if (strlen($flowData['data_9']) == 13) {
                                                                    $data_9 = $flowData['data_9'] . ':00:00';
                                                                }elseif(strlen($flowData['data_9']) == 16){
                                                                    $data_9 = $flowData['data_9'] . ':00';
                                                                } else {
                                                                    $data_9 = $flowData['data_9'];
                                                                }
                                                                if (empty($flowData) or date('H', strtotime($data_9)) < '12') {
                                                                    $n2++;//早退
                                                                    if ($minute <= 30) {
                                                                        $t1++;
                                                                        //大于5次算旷工半天
                                                                        if ($t1 >= 5) {
                                                                            $kuanggong = $kuanggong + 4;
                                                                        }
                                                                        if ($val1['remark'] == '忘打卡' && $n3 < 5 && $n3 > 0) {
                                                                            //4次忘打卡不扣款
                                                                        } else {
                                                                            //扣款
                                                                            $koukuan = $koukuan + $minute * 2;
                                                                        }
                                                                    } elseif ($minute >= 31 && $minute <= 119) {
                                                                        $t2++;
                                                                        //大于5次算旷工半天
                                                                        if ($t2 >= 5) {
                                                                            $kuanggong = $kuanggong + 4;
                                                                        }
                                                                        if ($val1['remark'] == '忘打卡' && $n3 < 5 && $n3 > 0) {
                                                                            //4次忘打卡不扣款
                                                                        } else {
                                                                            //扣款
                                                                            $koukuan = $koukuan + $minute * 4;
                                                                        }
                                                                    } elseif ($minute >= 120 && $minute <= 239) {
                                                                        $t3++;
                                                                        //早退2小时以上，旷工半天
                                                                        $kuanggong = $kuanggong + 4;
                                                                    } elseif ($minute >= 240) {
                                                                        $t4++;
                                                                        //早退4小时以上，旷工一天
                                                                        $kuanggong = $kuanggong + 8;
                                                                    }
                                                                }
                                                            }
                                                        }

                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif (M('attend_holiday')->field('HOLIDAY_NAME')->where("'" . $J . "'>=BEGIN_DATE and '" . $J . "'<=END_DATE")->find()) {
                            } /*elseif (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {
                                $rema = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                                if($val['level'] == 1) {
                                    if (empty($flowData) && $rema['remark'] != '忘打卡' && $rema['remark'] != '外出' && $rema['remark'] != '其他') {
                                        $n3 = $n3 + 2;
                                    }
                                }
                            }*/
                        }
                    }
                if($n3>=5 && $n3<=7){
                    $koukuan = $koukuan + ($n3-4)*10;
                }elseif($n3>=8 && $n3<=10){
                    $shijia = $shijia + 0.5;
                }elseif($n3>10){
                    for($i=11;$i<=$n3;$i++){
                        $kuanggong = $kuanggong + 4;
                    }
                }
                $quanq = 0;
                if($n1 == 0 && $n2==0 && $n3==0 && $bingjia == 0 && $shijia == 0 && $kuanggong == 0 && $chanjia == 0 && date('Y-m-d',strtotime($val['entry_time']))<$date &&  $val['level'] == 1){//&& $val['departure_time']== '0000-00-00 00:00:00'
                    $quanq = 200;
                    $userData[$key]['quanqin'] = '是';
                    $userData[$key]['quanq'] = $quanq;
                }else{

                    $userData[$key]['quanqin'] = '否';

                    /*if($val['level'] == 1 && $val['id'] !=318){//基础层且不是全勤

                        //判断是否走流程或者流程是否通过
                        $liuData = M('oa_49')->field('b.nowsort')->join("a join boss_oa_liuchen b on b.mid=49 and b.alldata=a.id")->where("a.xdae2a3_1='".$date."' and a.xdae2a3_2='".$e_date."' and a.xdae2a3_0='".$val['real_name']."'")->find();

                        if($liuData){//有流程
                            if($liuData['nowsort'] <=1){
                                echo $val['real_name']." 考勤异常流程未通过，通过后方可导出";exit;//流程还没通过 2017.08.01
                            }
                        }else{
                            echo $val['real_name']." 没有走考勤异常说明，请联系本人";exit;//没流程 怎么提示 2017.08.01
                        }

                    }*/
                }
                if($n3>10){
                    $cd = ($n3-10)/2;//未打卡大于10次，迟到一次算旷工半天
                }else{
                    $cd = 0;
                }
                $sjcq = $ycq_s - $shijia - $bingjia - $chanjia - $cd;
                $userData[$key]['cd'] = $n1;
                $userData[$key]['zt'] = $n2;
                $userData[$key]['wdk'] = $n3;
                $userData[$key]['t1'] = $t1;
                $userData[$key]['t2'] = $t2;
                $userData[$key]['t3'] = $t3;
                $userData[$key]['t4'] = $t4;
                $userData[$key]['bj'] = $bingjia;
                $userData[$key]['sj'] = $shijia;
                $userData[$key]['cj'] = $chanjia;
                $userData[$key]['kh'] = $kanhujia;
                $userData[$key]['hj'] = $hunjia;
                $userData[$key]['nj'] = $nianjia;
                $userData[$key]['sang'] = $sangjia;
                $userData[$key]['brj'] = $burujia;
                $userData[$key]['cqjc'] = $chanqianjia;
                $userData[$key]['sy'] = $shengyujia;
                $userData[$key]['dx'] = $daixinjia;
                $userData[$key]['tx'] = $tiaoxiujia;
                $userData[$key]['jb'] = $jiabanDay;
                $userData[$key]['kuanggong'] = $kuanggong/8;

                $day_kuan = $stand_salary/21.75;
                //司龄
                if($val['company_age']<10){
                    $b_kuan = $day_kuan*0.3;
                }elseif($val['company_age']>=10 && $val['company_age']<20){
                    $b_kuan = $day_kuan*0.2;
                }elseif($val['company_age']>=20 && $val['company_age']<30){
                    $b_kuan = $day_kuan*0.1;
                }elseif($val['company_age']>=30){
                    $b_kuan = $day_kuan*0.05;
                }
                if($ycq_s<21.75){
                    $wcq_koukuan = $day_kuan*(21.75 - $ycq_s + $lizhi );
                }else{
                    $wcq_koukuan = 0;
                }

                $koukuan_heji = $day_kuan*$shijia + $b_kuan*$bingjia  + $koukuan + $day_kuan*$chanjia;//+ ($kuanggong/8) *3*$day_kuan
                if($koukuan_heji){
                    $userData[$key]['koukuan'] = round($koukuan_heji,2);//考勤扣款合计=病假扣款+事假扣款+产假扣款+迟到/早退扣款+未刷卡扣款+旷工扣款(目前差产假 产假计算方式等同入职，数实际出勤天数)
                }else{
                    $userData[$key]['koukuan'] ='';
                }
                if($wcq_koukuan>0 or $koukuan_heji>0){
                    $userData[$key]['zongji'] = round(($wcq_koukuan + $koukuan_heji),2);// 考勤扣款总计=未出勤扣款+考勤扣款合计
                }else{
                    $userData[$key]['zongji'] = '';
                }
                $userData[$key]['sjcq'] = $sjcq;

                //新增信息到接口表
                $ResData = array();
                $ac_date = date('Y-m',strtotime($date));
                $ResData['user_id'] = $val['id'];
                $ResData['date'] = $ac_date;
                $ResData['attendance_cha'] = round(($wcq_koukuan + $koukuan_heji),2);//考勤扣款
                $ResData['actual_att'] = $sjcq;//实际出勤天数
                $ResData['full_att'] = $quanq;

                //判断对应月份有没有考勤数据
                $a_c = M('attendance_charge');
                $acData = $a_c->field('id,date')->where("user_id=".$val['id'])->find();
                if($acData['date'] == $ac_date){

                    $ResData['id'] = $acData['id'];
                    $a_c->save($ResData);//修改
                }else{

                    $a_c->add($ResData);//新增
                }

            }
        }

        $title = array('id'=>'序号','name'=>'部门','real_name'=>'姓名','title'=>'职务',
            'entry_time'=>'入职日期','departure_time'=>'离职时间','ycq'=>'应出勤天数','sjcq'=>'实际出勤天数','bj'=>'病假(d)',
            'sj'=>'事假(d)','kuanggong'=>'旷工(d)','cj'=>'产假(d)','kh'=>'看护假(d)','hj'=>'婚假(d)','nj'=>'年休假(d)','sang'=>'丧假(d)','brj'=>'哺乳假时数(d)','cqjc'=>'产检假(d)','sy'=>'计划生育假(d)','dx'=>'带薪休假(d)','tx'=>'调休(h)','jb'=>'加班(h)','o'=>'上月存休(h)','p'=>'累计存休(h)','t1'=>'30分内(次)','t2'=>'31-119分内(次)','t3'=>'120-239分内（次）','t4'=>'≥240分以上（次）','cd'=>'迟到次数','zt'=>'早退次数','wdk'=>'未刷卡次数','koukuan'=>'考勤扣款合计','quanqin'=>'享受全勤奖否','quanq'=>'全勤奖','zongji'=>'考勤扣款总计','x'=>'员工签字','y'=>'备注');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($userData, $title, date('Y-m',strtotime($date)).'月考勤记录');
    }

    public function RemarkUpdate(){//考勤异常原因的类型添加
        $res = I('post.res');
        $res = explode(",",$res);
        $ar = M('attend_remark');
        if($res){
            foreach($res as $val){
                if($val){
                    $r = $ar->field('id')->where("name='".$val."'")->find();
                    if(empty($r)){
                        $ad['name'] = $val;
                        if($ar->add($ad) === false){
                            $this->ajaxReturn(array('msg'=>'添加失败'));
                        }/*else{
                            $this->ajaxReturn(array('msg'=>'添加成功','go'=>'/Attend/export'));
                        }*/
                    }
                }
            }
        }else{
            $this->ajaxReturn(array('msg'=>'数据有问题请重新填写'));exit;
        }

    }

    public function get_checkin_remark(){//获取备注
        $OP = I('get.OP');
        $REGISTER_TYPE = I('get.REGISTER_TYPE');
        $LOG_DATE = I('get.LOG_DATE');
        $REGISTER_TIME = I('get.REGISTER_TIME');
        $rem = M('attend_no_duty_remark');

        $user = M('user')->field('id,uid,username')->where("id=".UID."")->find();

        if ( $OP == "2" )
        {
            $add_data = array();
            $add_data['REMARK'] = I('get.CONTENT');
            M('attend_duty')->where("USER_ID='{$user['username']}' and REGISTER_TYPE='{$REGISTER_TYPE}' and to_days(REGISTER_TIME)=to_days('{$LOG_DATE} {$REGISTER_TIME}')")->save($add_data);

        }
        if($OP == 1){
            $query1 = M('user_ext')->field('DUTY_TYPE')->where("uid=".UID."")->find();
            if($query1){
                $DUTY_TYPE = $query1['duty_type'];
            }else{
                $DUTY_TYPE = 2;
            }

            $query = M('attend_config')->field('*')->where("DUTY_TYPE='".$DUTY_TYPE."'")->find();
            $temp = "DUTY_TYPE".$REGISTER_TYPE;
            $DUTY_TYPE = $query[$temp];

            $query = M('attend_duty')->field('REGISTER_TIME,REMARK')->where("USER_ID='".$user['username']."' and REGISTER_TYPE='{$REGISTER_TYPE}' and to_days(REGISTER_TIME)=to_days('{$LOG_DATE} {$REGISTER_TIME}')")->find();
            //echo M('attend_duty')->getLastSql();exit;
            $flowData = M('oa_44')->field('a.x95f845_8 as data_9,a.x95f845_9 as data_10,a.x95f845_6 as data_7,a.x95f845_7 as data_8,a.x95f845_3 as run_id')->join('a join boss_oa_liuchen b on a.id=b.alldata and b.mid=44')->where("'".$LOG_DATE."'>=date_format(a.x95f845_8,'%Y-%m-%d') and '".$LOG_DATE."'<=date_format(a.x95f845_9,'%Y-%m-%d') and a.nowuserid='".$user['id']."' and b.status=2")->find();
            if ($query['remark']) {

                $CONTENT = $query['remark'];
            }else{//if(empty($query['register_time']))

                $remk = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$LOG_DATE}' and DUTY_TYPE ={$REGISTER_TYPE}")->find();
                if ($remk) {
                    $CONTENT = $remk['remark'];
                }else {

                    if ($flowData['data_7']) {

                        //匹配流程里面的数据，然后添加到备注里面去
                        $CONTENT = $flowData['data_7'] . $flowData['run_id'] . '(' . $flowData['data_8'] . ')';

                        if($query){//有打卡数据 备注添加到duty表中

                            $duty_remark['REMARK'] = $CONTENT;
                            M('attend_duty')->where("USER_ID='".$user['username']."' and REGISTER_TYPE='{$REGISTER_TYPE}' and to_days(REGISTER_TIME)=to_days('{$LOG_DATE} {$REGISTER_TIME}')")->save($duty_remark);

                        }else{//无打卡数据添加到no_remark
                            $add_data = array();
                            $add_data['USER_ID'] = $user['username'];
                            $add_data['DUTY_TYPE'] = $REGISTER_TYPE;
                            $add_data['LOG_DATE'] = $LOG_DATE;
                            $add_data['REMARK'] = $CONTENT;
                            $add_data['CREATE_TIME'] = date('y-m-d h:i:s', time());
                            $rem->add($add_data);
                        }


                    }
                }
            }
        }

        if($REGISTER_TIME == "" || $REGISTER_TIME == " 0000-00-00"){

            $query = $rem->field('RID')->where("USER_ID='{$user['username']}' and LOG_DATE='{$LOG_DATE}' and DUTY_TYPE ={$REGISTER_TYPE}")->find();

            if ( $OP == "2" )
            {
                $CONTENT = I('get.CONTENT');
                $add_data = array();
                if($query['rid']){
                    $add_data['REMARK'] = $CONTENT;
                    $rem->where("RID=".$query['rid']."")->save($add_data);
                } else {
                    $add_data['USER_ID'] = $user['username'];
                    $add_data['DUTY_TYPE'] = $REGISTER_TYPE;
                    $add_data['LOG_DATE'] = $LOG_DATE;
                    $add_data['REMARK'] = $CONTENT;
                    $add_data['CREATE_TIME'] = date('y-m-d h:i:s',time());
                    $rem->add($add_data);
                }
            }

            $ROW = $rem->field('REMARK')->where("USER_ID='{$user['username']}' and LOG_DATE='{$LOG_DATE}' and DUTY_TYPE ={$REGISTER_TYPE}")->find();
            if(!empty($ROW['remark'])){
                $CONTENT = $ROW['remark'];
            }



        }
        echo $CONTENT;exit;
        //$this->ajaxReturn($CONTENT);exit;
    }

    function holiday_name( $DAY ){
        $NAME = "";
        $query = M('attend_holiday')->field('HOLIDAY_NAME')->where("BEGIN_DATE <='".$DAY."' and END_DATE>='{$DAY}'")->find();

        if ($query){
            $NAME = $query['holiday_name'];
        }
        return $NAME;
    }

    /*获取打卡备注信息*/
    function get_checkinfo(){
        $NUM1 = 0;//迟到
        $NUM2 = 0;//早退
        $NUM3 = 0;//未打卡
        $attend = M('attend_duty');
        $configModel = M('attend_config');
        $DATE1 = I('get.date1');
        $DATE2 = I('get.date2');

        if(!isset($DATE1) || $DATE1 == "0000-00-00" || $DATE1 == ""){
            echo "<font color=\"red\">起始日期不能为空</font>";
            exit( );
        }

        if(!isset($DATE2) || $DATE2 == "0000-00-00" || $DATE2 == ""){
            echo "<font color=\"red\">截止日期不能为空</font>";
            exit( );
        }

        $us =  M('user')->field('username')->where("id=".UID)->find();
        $USER_ID =$us['username'];
        if($USER_ID == ""){
            echo "<font color=\"red\">关联考勤数据异常</font>";;
            exit( );
        }

        if ( $DATE1 != "" )
        {
            $TIME_OK = $this->is_date( $DATE1 );
            if ( !$TIME_OK )
            {
                message( _( "错误" ), _( "起始日期格式不对，应形如 1999-1-2" ) );
                exit( );
            }
        }
        if ( $DATE2 != "" )
        {
            $TIME_OK = $this->is_date( $DATE2 );
            if ( !$TIME_OK )
            {
                message( _( "错误" ), _( "截止日期格式不对，应形如 1999-1-2" ) );
                exit( );
            }
        }
        if ( $this->compare_date( $DATE1, $DATE2 ) == 1 )
        {
            message( _( "错误" ), _( "查询的起始日期不能晚于截止日期" ) );
            exit( );
        }
        $WHERE_STR = "";
        $QUSER_ID = "";

        /*if($QUSER_NAME != ""){
            $USER_ID = $QUSER_NAME;
            $query = "select USER_ID from user where USER_NAME like '%".$USER_ID."%'";
            $cursor = exequery( $connection, $query );
            if(mysql_num_rows($cursor)>0) $USER_ID = "";
            while( $ROW = mysql_fetch_array( $cursor ) ){
                $QUSER_ID .= $ROW['USER_ID'].",";
            }
        }*/

        /*if($QPRIV_ID != ""){
            $WHERE_STR .= " and USER.USER_PRIV = '{$QPRIV_ID }' ";
        }*/

        /*if($QDEPT_ID != ""){
            $query = "select POST_PRIV,POST_DEPT from user where USER_ID = '{$LOGIN_USER_ID}'";
            $cursor5 =  exequery($connection,$query);
            $ROW4 = mysql_fetch_array($cursor5);
            $POST_PRIV = $ROW4['POST_PRIV'];
            $POST_DEPT = $ROW4['POST_DEPT'];
            if($POST_PRIV == "0"){
                if($QDEPT_ID != $LOGIN_DEPT_ID){
                    Message("警告","你所查询的部门不在管理范围");
                    exit();
                }
            } else if ($POST_PRIV == "2"){
                $DEPT_ARY = explode(',',$POST_DEPT);
                if(!in_array($QUSER_ID, $DEPT_ARY)){
                    Message("警告","你所查询的部门不在管理范围");
                    exit();
                }
            }

            $WHERE_STR .= " and USER.DEPT_ID = '{$QDEPT_ID }' ";
        }*/

        if($QUSER_ID == "" && $WHERE_STR == "") $QUSER_ID = $USER_ID;

        if($QUSER_ID != ""){
            $WHERE_STR .= "find_in_set(a.username,'".$QUSER_ID."')>0 ";
        }

        $ROW4 = M('user')->field('a.username,a.real_name,b.name')->join('a join boss_user_department b on a.dept_id=b.id')->where($WHERE_STR)->select();
        $USERS_TEM = array( );
        $DUTY_INFO_ARR = array( );
        foreach($ROW4 as $val)
        {
            $USER_NAME = $val['real_name'];
            $DEPT_NAME = $val['name'];
            $USER_ID = $val['username'];
            $DAYS_TEM = array( );
            $USER_INFO = array(
                "USER_NAME" => $USER_NAME,
                "DEPT_NAME" => $DEPT_NAME
            );
            $USERS_TEM[$USER_ID]['INFO'] = $USER_INFO;
            $J = $DATE1;
            for ( ;	$J <= $DATE2;	$J = date( "Y-m-d", strtotime( $J ) + 86400 )	)
            {
                $DUTY_ARR = array( );
                $attendData = $attend->field('*')->where("USER_ID = '".$val['username']."' and to_days(REGISTER_TIME)=to_days('".$J."')")->order('REGISTER_TIME desc')->select();
                $STATE = false;
                foreach($attendData as $key=>$val2)
                {
                    if ( $val2['remark']  != "" ){
                        $STATE = true;
                    }
                    $DUTY_ARR[$val2['register_type']] = array(
                        "DUTY_TYPE" => $val2['duty_type'],
                        "REGISTER_TIME" => $val2['register_time'],
                        "REGISTER_IP" => $val2['register_ip'],
                        "REMARK" => str_replace( "\n", "<br>", $val2['remark'] )
                    );
                }

                foreach ( $DUTY_ARR as $tem )
                {
                    $DUTY_TYPE = $tem['duty_type'];
                }
                if ( $DUTY_TYPE == "" )
                {
                    $DUTY_TYPE = $this->get_default_type( $USER_ID );
                }
                if ( $DUTY_TYPE == "" || $DUTY_TYPE == 0 )
                {
                    $DUTY_TYPE = 2;
                }

                $cf = $configModel->field('*')->where("DUTY_TYPE=".$DUTY_TYPE)->find();
                if ($cf)
                {
                    $DUTY_NAME = $cf['duty_name'];
                    $GENERAL = $cf['general'];
                    $DUTY_TYPE_ARR = array( );
                    $I = 1;
                    for ( ;	$I <= 6;	++$I	)
                    {
                        if ( $cf["duty_time".$I] != "" )
                        {
                            $DUTY_TYPE_ARR['TYPE'][$I] = array(
                                "DUTY_TIME" => $cf["duty_time".$I],
                                "DUTY_TYPE" => $cf["duty_type".$I]
                            );
                        }
                    }
                    $DUTY_TYPE_ARR['NAME'] = $DUTY_NAME;
                }

                if ( !isset( $DUTY_INFO_ARR[$DUTY_TYPE] ) )
                {
                    $DUTY_INFO_ARR[$DUTY_TYPE] = $DUTY_TYPE_ARR;
                }

                if(!$STATE){
                    foreach ( $DUTY_TYPE_ARR['TYPE'] as $REGISTER_TYPE => $DUTY_TYPE_ONE ){
                        $query =M('attend_no_duty_remark')->field('*')->where("USER_ID='{$USER_ID}' and LOG_DATE='{$J}' and DUTY_TYPE =".$DUTY_TYPE_ONE['DUTY_TYPE']."")->find();
                        if($query){
                            $STATE = true;
                        }
                    }
                }
                if(!$STATE) continue;

                $OUGHT_TO = 1;
                $SHOW_HOLIDAY = "";
                if ( ( $IS_HOLIDAY = $this->check_holiday( $J ) ) != 0 )
                {
                    $SHOW_HOLIDAY .= "<font color='#008000'>"._( "节假日" )."</font>";
                    $OUGHT_TO = 0;
                }
                else if ( ( $IS_HOLIDAY1 = $this->check_holiday1( $J, $GENERAL ) ) != 0 )
                {
                    $SHOW_HOLIDAY .= "<font color='#008000'>"._( "公休日" )."</font>";
                    $OUGHT_TO = 0;
                }
                if ( $SHOW_HOLIDAY != "" || $SHOW_HOLIDAY2 != "" )
                {
                    $CLASS = "TableContent";
                }
                else
                {
                    $CLASS = "TableData";
                }

                $DAYS_TEM[$J]['CLASS'] = $CLASS;
                $DAYS_TEM[$J]['DUTY_TYPE'] = $DUTY_TYPE;
                $REGISTERS_TEM = array( );
                $REMARK_ARY = array( );
                $HAS_DUTY_DAY = 0;
                foreach ( $DUTY_TYPE_ARR['TYPE'] as $REGISTER_TYPE => $DUTY_TYPE_ONE )
                {
                    $START_OR_END = $DUTY_TYPE_ONE['DUTY_TYPE'];
                    $DUTY_TIME_OUGHT = $DUTY_TYPE_ONE['DUTY_TIME'];
                    $DUTY_ONE_ARR = $DUTY_ARR[$REGISTER_TYPE];
                    $HAS_DUTY = 0;
                    if ( is_array( $DUTY_ONE_ARR ) && !empty( $DUTY_ONE_ARR ) )
                    {
                        foreach ( $DUTY_ONE_ARR as $KEY => $VALUE )
                        {
                            $$KEY = $VALUE;
                        }
                        $HAS_DUTY = 1;
                        $HAS_DUTY_DAY = 1;
                    }
                    $SHOW_HOLIDAY2 = "";
                    /*if ( ( $IS_LEAVE = check_leave( $USER_ID, $J, $DUTY_TYPE_ARR['TYPE'][$REGISTER_TYPE]['DUTY_TIME'] ) ) != "0" )
                    {
                        $SHOW_HOLIDAY2 .= "<font color='#008000'>"._( "请假" ).( "-".$IS_LEAVE."</font>" );
                        $OUGHT_TO = 0;

                    }
                    else if ( ( $IS_OUT = check_out( $USER_ID, $J, $DUTY_TYPE_ARR['TYPE'][$REGISTER_TYPE]['DUTY_TIME'] ) ) != 0 )
                    {
                        $SHOW_HOLIDAY2 .= "<font color='#008000'>"._( "外出" )."</font>";
                        $OUGHT_TO = 0;
                    }
                    else */
                if ( $OUGHT_TO != 0 )
                {
                    $OUGHT_TO = 1;
                }
                    $SHOW_STR = "";
                    $REMACK_STR = "";
                    if ( $HAS_DUTY == 1 )
                    {
                        $REGISTER_TIME2 = $DUTY_ONE_ARR['REGISTER_TIME'];
                        $REGISTER_TIME = $DUTY_ONE_ARR['REGISTER_TIME'];
                        $REGISTER_TIME = strtok( $REGISTER_TIME, " " );
                        $REGISTER_TIME = strtok( " " );
                        if ( $START_OR_END == "1" && $this->compare_time( $REGISTER_TIME, $DUTY_TIME_OUGHT ) == 1 )
                        {
                            $SHOW_STR .= $REGISTER_TIME." <font color=red><b>"._( "迟到" )."</b></font>";
                            $NUM1++;
                        }
                        else if ( $START_OR_END == "2" && $this->compare_time( $REGISTER_TIME, $DUTY_TIME_OUGHT ) == -1 )
                        {
                            $SHOW_STR .= $REGISTER_TIME." <font color=red><b>"._( "早退" )."</b></font>";
                            $NUM2++;
                        }
                        else
                        {
                            $SHOW_STR .= $REGISTER_TIME;
                        }
                        if ( $SHOW_HOLIDAY != "" )
                        {
                            $SHOW_STR .= _( "（" ).$SHOW_HOLIDAY._( "）" );
                        }
                        else if ( $SHOW_HOLIDAY2 != "" )
                        {
                            $SHOW_STR .= _( "（" ).$SHOW_HOLIDAY2._( "）" );
                        }
                        if ( $REMARK != "" )
                        {
                            $REMACK_STR .= $REMARK;
                        }
                    }
                    else if ( $HAS_DUTY == 0 && $OUGHT_TO == 1 )
                    {
                        $SHOW_STR .= _( "未打卡" );
                        $NUM3++;

                        $ROW2 = M('attend_no_duty_remark')->field('*')->where("USER_ID='{$USER_ID}' and LOG_DATE='{$J}' and DUTY_TYPE =".$DUTY_TYPE_ONE['DUTY_TYPE']."")->find();
                        if($ROW2){
                            $REMARK = $ROW2['remark'];
                            if($REMARK != ""){
                                $REMACK_STR .= $REMARK;
                                $state = true;
                            }
                        }

                    }
                    else if ( $SHOW_HOLIDAY != "" )
                    {
                        $SHOW_STR .= $SHOW_HOLIDAY;
                    }
                    else if ( $SHOW_HOLIDAY2 != "" )
                    {
                        $SHOW_STR .= $SHOW_HOLIDAY2;
                    }
                    else
                    {
                        $SHOW_STR .= _( "未打卡" );
                    }
                    $REGISTERS_TEM[$REGISTER_TYPE] = $SHOW_STR;
                    $REMARK_ARY[$REGISTER_TYPE] = $REMACK_STR;
                }
                $DAYS_TEM[$J]['REMARK'] = $REMARK_ARY;
                $DAYS_TEM[$J]['REGISTERS'] = $REGISTERS_TEM;
                $DAYS_TEM[$J]['DUTY_TYPE'] = $DUTY_TYPE;
                $DAYS_TEM[$J]['HAS_DUTY_DAY'] = $HAS_DUTY_DAY;
            }
            $USERS_TEM[$USER_ID]['DAYS'] = $DAYS_TEM;
        }
        foreach ( $DUTY_INFO_ARR as $DUTY_TYPE => $DUTY_ARR ){
            ob_start( );
            $check_type = $DUTY_ARR['NAME'];
        }

        if ( 0 < count( $DUTY_INFO_ARR ) )
        {
            $table_head = array( );
            $list_str = '';
            foreach ( $DUTY_INFO_ARR as $DUTY_TYPE => $DUTY_ARR )
            {
                ob_start( );
                $check_type = $DUTY_ARR['NAME'];
                $list_str .= "\r\n<table width=\"100%\" height=\"auto\" id=\"list_table\" style=\"width:83%;float:left;margin-left:120px;\">\r\n\t<tr>\r\n\t\t<th>";
                $list_str .= _( "日期" );
                $list_str .= "</th>\r\n\t\t";
                foreach ( $DUTY_ARR['TYPE'] as $INFO )
                {
                    if ( $INFO['DUTY_TYPE'] == 1 )
                    {
                        $TYPE_NAME = _( "上班" );
                    }
                    else
                    {
                        $TYPE_NAME = _( "下班" );
                    }
                    $list_str .= "<th>".$TYPE_NAME."（".$INFO['DUTY_TIME']."）"."</th>";
                }
                $list_str .= "</tr>";
                $table_head[$DUTY_TYPE] = ob_get_contents( );
                ob_clean( );
                break;
            }
            $table_line = array( );
            if ( 0 < count( $USERS_TEM ) )
            {
                foreach ( $USERS_TEM as $USER_ID => $USER_DATA )
                {
                    $USER_INFO = $USER_DATA['INFO'];
                    if ( 0 < count( $USER_DATA['DAYS'] ) )
                    {
                        foreach ( $USER_DATA['DAYS'] as $DATE => $DATE_ARR )
                        {
                            $has_duty_day_tem = $DATE_ARR['HAS_DUTY_DAY'];
                            ob_start( );
                            $list_str .= "\t\t<tr>\r\n\t\r\n<td>";
                            $list_str .= $DATE;
                            $list_str .= "(";
                            $list_str .= $this->get_week( $DATE );
                            $list_str .= ")</td>\r\n\t\t\t";
                            foreach ( $DATE_ARR['REGISTERS'] as $REGISTER_TYPE => $SHOW_STR )
                            {
                                $list_str .= "\t\t\t<td ";
                                $list_str .= $has_duty_day_tem == 0 ? "style=\"color:red\"" : "";
                                $list_str .= " >";
                                $list_str .= $SHOW_STR;
                                $list_str .= "</td>\r\n";
                            }
                            $list_str .= "</tr>";
                            $table_line[$DATE_ARR['DUTY_TYPE']][] = ob_get_contents( );

                            if(count($DATE_ARR['REMARK'])>0){
                                $list_str .= "<tr style=\"background-color:#f3f3f3;\"><td>考勤说明</td>";
                                foreach ( $DATE_ARR['REMARK'] as $REGISTER_TYPE => $REMARK_STR ){
                                    $list_str .= "<td>".$REMARK_STR."</td>";
                                }
                                $list_str .= "</tr>";
                            }
                            ob_clean( );
                        }
                    }
                }
            }
            $list_str .= "</table>";
        }
        echo $list_str;exit;
        //$this->ajaxReturn($list_str);
    }

    function get_week($DATE){
        $datearr = explode("-",$DATE);     //将传来的时间使用“-”分割成数组
        $year = $datearr[0];       //获取年份
        $month = sprintf('%02d',$datearr[1]);  //获取月份
        $day = sprintf('%02d',$datearr[2]);      //获取日期
        $hour = $minute = $second = 0;   //默认时分秒均为0
        $dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
        $shuchu = date("w",$dayofweek);      //获取星期值
        $weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
        return $weekarray[$shuchu];
    }

    public function is_date( $str )
    {
        $YEAR = "";
        $MONTH = "";
        $DAY = "";
        $len = strlen( $str );
        $offset = 0;
        $i = strpos( $str, "-", $offset );
        $YEAR = substr( $str, $offset, $i - $offset );
        $offset = $i + 1;
        if ( $len < $offset )
        {
            return FALSE;
        }
        if ( $i )
        {
            $i = strpos( $str, "-", $offset );
            $MONTH = substr( $str, $offset, $i - $offset );
            $offset = $i + 1;
            if ( $len < $offset )
            {
                return FALSE;
            }
            if ( $i )
            {
                $DAY = substr( $str, $offset, $len - $offset );
            }
        }
        if ( $YEAR == "" || $MONTH == "" || $DAY == "" )
        {
            return FALSE;
        }
        if ( !checkdate( intval( $MONTH ), intval( $DAY ), intval( $YEAR ) ) )
        {
            return FALSE;
        }
        return TRUE;
    }

    function compare_date( $DATE1, $DATE2 )
    {
        $STR = strtok( $DATE1, "-" );
        $YEAR1 = $STR;
        $STR = strtok( "-" );
        $MON1 = $STR;
        $STR = strtok( "-" );
        $DAY1 = $STR;
        $STR = strtok( $DATE2, "-" );
        $YEAR2 = $STR;
        $STR = strtok( "-" );
        $MON2 = $STR;
        $STR = strtok( "-" );
        $DAY2 = $STR;
        if ( $YEAR2 < $YEAR1 )
        {
            return 1;
        }
        if ( $YEAR1 < $YEAR2 )
        {
            return -1;
        }
        if ( $MON2 < $MON1 )
        {
            return 1;
        }
        if ( $MON1 < $MON2 )
        {
            return -1;
        }
        if ( $DAY2 < $DAY1 )
        {
            return 1;
        }
        if ( $DAY1 < $DAY2 )
        {
            return -1;
        }
        return 0;
    }

    function compare_time( $TIME1, $TIME2 )
    {
        $STR = strtok( $TIME1, ":" );
        $HOUR1 = $STR;
        $STR = strtok( ":" );
        $MIN1 = $STR;
        $STR = strtok( ":" );
        $SEC1 = $STR;
        $STR = strtok( $TIME2, ":" );
        $HOUR2 = $STR;
        $STR = strtok( ":" );
        $MIN2 = $STR;
        $STR = strtok( ":" );
        $SEC2 = $STR;
        if ( $HOUR2 < $HOUR1 )
        {
            return 1;
        }
        if ( $HOUR1 < $HOUR2 )
        {
            return -1;
        }
        if ( $MIN2 < $MIN1 )
        {
            return 1;
        }
        if ( $MIN1 < $MIN2 )
        {
            return -1;
        }
        if ( $SEC2 < $SEC1 )
        {
            return 1;
        }
        if ( $SEC1 < $SEC2 )
        {
            return -1;
        }
        return 0;
    }

    function check_holiday( $DAY )
    {
        $IS_HOLIDAY = 0;
        $ROW = M('attend_holiday')->field('*')->where("BEGIN_DATE <='".$DAY."' and END_DATE>='{$DAY}'")->find();
        if ($ROW)
        {
            $IS_HOLIDAY = 1;
        }
        return $IS_HOLIDAY;
    }

    function check_holiday1( $DAY, $GENERAL )
    {
        $IS_HOLIDAY1 = 0;
        $WEEK = date( "w", strtotime( $DAY ) );
        if ( $this->find_id( $GENERAL, $WEEK ) )
        {
            $IS_HOLIDAY1 = 1;
        }
        return $IS_HOLIDAY1;
    }

    function  find_id( $GENERAL, $WEEK ){
        if(strpos($GENERAL,$WEEK) === false){
            return '';
        }else{
            return 1;
        }
    }

    /*function check_leave( $USER_ID, $DAY, $DUTY_TIME )
    {
        global $connection;
        $IS_LEAVE = 0;
        $query = "select * from ATTEND_LEAVE where USER_ID='".$USER_ID."' and (ALLOW='1' or ALLOW='3') and LEAVE_DATE1<='{$DAY} {$DUTY_TIME}' and LEAVE_DATE2>='{$DAY} {$DUTY_TIME}'";
        $cursor = exequery( $connection, $query );
        if ( $ROW = mysql_fetch_array( $cursor ) )
        {
            $IS_LEAVE = 1;
            $LEAVE_TYPE2 = $ROW['LEAVE_TYPE2'];
            $LEAVE_TYPE2_STR = get_hrms_code_name( $LEAVE_TYPE2, "ATTEND_LEAVE" );
        }
        if ( $LEAVE_TYPE2_STR != "" )
        {
            return $LEAVE_TYPE2_STR;
        }
        return $IS_LEAVE;
    }*/

    /*function check_out( $USER_ID, $DAY, $DUTY_TIME )
    {
        global $connection;
        $IS_OUT = 0;
        $query = "select * from ATTEND_OUT where USER_ID='".$USER_ID."' and ALLOW='1' and to_days(SUBMIT_TIME)=to_days('{$DAY}') and OUT_TIME1<='".substr( $DUTY_TIME, 0, strrpos( $DUTY_TIME, ":" ) )."' and OUT_TIME2>='".substr( $DUTY_TIME, 0, strrpos( $DUTY_TIME, ":" ) )."'";
        $cursor = exequery( $connection, $query );
        if ( $ROW = mysql_fetch_array( $cursor ) )
        {
            $IS_OUT = 1;
        }
        return $IS_OUT;
    }*/
    function get_default_type( $USER_ID )
    {
        $DUTY_TYPE = "";
        $ROW = M('user_ext')->field('DUTY_TYPE')->where(" USER_ID='".$USER_ID."'")->find();
        if ($ROW)
        {
            $DUTY_TYPE = $ROW['duty_type'];
        }
        return $DUTY_TYPE;
    }

    public function workDays( $start , $end ){
    $end < $start && exit;
    $double =  ($end - $start)/(7*24*3600);
    $double = floor($double);
    $start = date('w',$start);
    $end   = date('w',$end);
    $end = $start > $end ? $end + 5 : $end;
    return $double * 5 + $end - $start+1;
    }

    function get_weekend_days($start_date,$end_date,$is_workday = false){

        if (strtotime($start_date) > strtotime($end_date)) list($start_date, $end_date) = array($end_date, $start_date);
        $start_reduce = $end_add = 0;
        $start_N = date('N',strtotime($start_date));
        $start_reduce = ($start_N == 7) ? 1 : 0;
        $end_N = date('N',strtotime($end_date));
        in_array($end_N,array(6,7)) && $end_add = ($end_N == 7) ? 2 : 1;
        $alldays = abs(strtotime($end_date) - strtotime($start_date))/86400 + 1;
        $weekend_days = floor(($alldays + $start_N - 1 - $end_N) / 7) * 2 - $start_reduce + $end_add;
        if ($is_workday){
            $workday_days = $alldays - $weekend_days;
            return $workday_days;
        }
        return $weekend_days;
    }

}