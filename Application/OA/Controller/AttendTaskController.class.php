<?php
/**
 * 月初的时候执行，将上个月的考勤数据插入表中
 * 考勤定时任务
 * 数据：考勤扣款、实际出勤天数、全勤奖
 * User: owq
 * Date: 2017/5/16
 * Time: 14:03
 */
namespace OA\Controller;
use Common\Controller\BaseController;
class AttendTaskController extends BaseController
{
    /*
     * 考勤数据查询
     */
    public function index(){
        //$beginDate = date('Y-m-01', strtotime($date));
        //$endDate = date('Y-m-d', strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day'));
        $last= strtotime("-1 month", time());
        $endDate = date("Y-m-t", $last);//上个月最后一天
        $beginDate = date('Y-m-01', $last);//上个月第一天
        $current_mon = date('Y-m', $last);//年月

        /*$endDate = '2017-05-31';//上个月最后一天
        $beginDate = '2017-05-01';//上个月第一天
        $current_mon = '2017-05';//年月*/
        $user_ext = M('user_ext');
        $configModel = M('attend_config');
        $attend = M('attend_duty');
        $user = M('oa_hr_manage');
        $a_c = M('attendance_charge');
        $userData = $user->field('user_id,entry_time,level')->where("status !=1 and DATE_FORMAT(d.entry_time,'%Y-%m-%d')<='".$endDate."'")->select();

        foreach($userData as $key=>$val) {

            if($val['entry_time']>$beginDate){//当月入职的情况
                $cha = $this->get_weekend_days($val['entry_time'],$endDate,true);
                $J = $val['entry_time'];
                $ycq_s =$cha;
            }else{
                $ycq_s = 21.75;
                $J = $beginDate;
            }

            $n1 = 0;//迟到
            $n2 = 0;//早退
            $n3 = 0;//未打卡
            $t1 = 0;//30分内
            $t2 = 0;//31-119分内
            $t3 = 0;//120-239分内
            $t4 =0;//240分以上
            $bingjia = 0;
            $shijia = 0;
            $chanjia = 0;
            $kuanggong = 0;
            $koukuan = 0;
            for (; $J <= $endDate; $J = date("Y-m-d", strtotime($J) + 86400)) {

                if ($val['user_id']) {
                    $USER_ID = M('user')->field('username,real_name,id')->where("id=" . $val['user_id'])->find();
                    //获取流程里面的数据

                    $flowData = M('oa_44')->field('a.x95f845_8 as data_9,a.x95f845_9 as data_10,a.x95f845_6 as data_7,a.x95f845_7 as data_8,a.x95f845_3 as run_id')->join('a join boss_oa_liuchen b on a.id=b.alldata and b.mid=44')->where("'".$J."'>=date_format(a.x95f845_8,'%Y-%m-%d') and '".$J."'<=date_format(a.x95f845_9,'%Y-%m-%d') and a.nowuserid='".$USER_ID['id']."'")->find();
                    if ($flowData) {

                        if(strlen($flowData['data_9']) == 13 && strlen($flowData['data_10']) == 13 ){
                            $data_9 = $flowData['data_9'] . ':00:00';
                            $data_10 = $flowData['data_10'] . ':00:00';
                        }else{
                            $data_9 = $flowData['data_9'];
                            $data_10 = $flowData['data_10'];
                        }
                        $c_day = (strtotime(date('Y-m-d', strtotime($data_10))) - strtotime(date('Y-m-d', strtotime($data_9)))) / 86400;
                        $hour = floor((strtotime($data_10) - strtotime($data_9)) % 86400 / 3600);

                        if ($hour > 3 && $hour < 8) {
                            $hour = 4;
                        } elseif ($c_day>=0) {
                            $hour = 8;
                        }
                        switch ($flowData['data_7']) {
                            case '病假':
                                $bingjia =$bingjia + $hour;
                                break;
                            case '事假':
                                $shijia =$shijia + $hour;
                                break;
                            case '产假':
                                $chanjia =$chanjia + $hour;
                                break;
                        }
                    }

                    $ue = $user_ext->field('DUTY_TYPE')->where("UID=" . $val['user_id'])->find();//获取打卡类型
                    if ($ue['duty_type']) {//获取打卡时间
                        $duty_type = $ue['duty_type'];
                    }else{
                        $duty_type = 2;
                    }
                    $cf = $configModel->field('DUTY_TIME1,DUTY_TIME2')->where("DUTY_TYPE=" . $duty_type)->find();
                    $time1 = $cf['duty_time1'];
                    $time2 = $cf['duty_time2'];

                    $attendData = $attend->field('*')->where("USER_ID = '" . $USER_ID['username'] . "' and to_days(REGISTER_TIME)=to_days('" . $J . "')")->order('REGISTER_TIME desc')->select();

                    if (count($attendData) == 1) {//只打了一次卡

                        if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                        } else {

                            if ($attendData[0]['register_type'] == 1) {
                                $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=1")->find();
                                if($val['level'] == 1) {
                                    if (empty($flowData) && ($rem['remark'] != '外出' && $rem['remark'] != '忘打卡')) {
                                        $n3 = $n3 + 1;
                                    }
                                }

                            } elseif ($attendData[0]['register_type'] == 2) {
                                $rema = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                                //
                                if($val['level'] == 1) {
                                    if (empty($flowData) && ($rema['remark'] != '外出' && $rema['remark'] != '忘打卡')) {
                                        $n3 = $n3 + 1;
                                    }
                                }
                            }
                        }
                    } elseif (count($attendData) > 1) {

                        foreach ($attendData as $key1 => $val1) {
                            if (!empty($val1['register_time'])) {
                                if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                                }else {
                                    if ($val1['register_type'] == 1) {

                                        if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) > strtotime($time1)) {

                                            $minute = floor((strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) - strtotime($time1)) % 86400 / 60);
                                            //是否需要判断迟到有没有走流程 start $flowData为请休假流程数据
                                            if(strlen($flowData['data_9']) == 13){
                                                $data_9 = $flowData['data_9'] . ':00:00';
                                            }else{
                                                $data_9 = $flowData['data_9'];
                                            }
                                            if($val['level'] == 1) {//基层才统计迟到、早退
                                                //查询是否忘打卡
                                                $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=1")->find();
                                                if ($rem['remark'] != '忘打卡' && $rem['remark'] != '外出') {
                                                    if (empty($flowData) or date('H', strtotime($data_9)) > '12') {
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
                                        if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                                        } else {
                                            if($val['level'] == 1) {//基层才统计迟到、早退
                                                if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) < strtotime($time2)) {

                                                    //查询是否是外出
                                                    $rem = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                                                    if ($rem['remark'] != '忘打卡' && $rem['remark'] != '外出') {

                                                        $minute = floor((strtotime($time2) - strtotime(date('H:i:' . '59', strtotime($val1['register_time'])))) % 86400 / 60);//获取早退分钟数
                                                        if (strlen($flowData['data_9']) == 13) {
                                                            $data_9 = $flowData['data_9'] . ':00:00';
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
                    } elseif (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {
                        $rema = M('attend_no_duty_remark')->field('REMARK')->where("USER_ID='" . $USER_ID['username'] . "' and LOG_DATE='" . $J . "' and DUTY_TYPE=2")->find();
                        if($val['level'] == 1) {
                            if (empty($flowData) && $rema['remark'] != '外出') {
                                $n3 = $n3 + 2;
                            }
                        }
                    }

                }
            }

            //新增信息到表中
            $ResData = array();
            $ResData['user_id'] = $val['user_id'];
            $ResData['date'] = $current_mon;
            if($n3>=5 && $n3<=7){
                $koukuan = $koukuan + ($n3-4)*10;
            }
            if($n1 == 0 && $n2==0 && $n3==0 && $bingjia == 0 && $shijia == 0 && $kuanggong == 0 && $chanjia == 0 && date('Y-m-d',strtotime($val['entry_time']))<$beginDate &&  $val['level'] == 1){
                $ResData['full_att'] = 200;//全勤
            }else{
                $ResData['full_att'] = 0;
            }
            if($n3>10){//未打卡大于10次，迟到一次算旷工半天
                $sjcq = $ycq_s - $shijia/8 - $bingjia/8 - ($n3-10)/2;
            }else{
                $sjcq = $ycq_s - $shijia/8 - $bingjia/8;
            }
            $ResData['attendance_cha'] = $koukuan;//考勤扣款
            $ResData['actual_att'] = $sjcq;//实际出勤天数

            //判断对应月份有没有考勤数据
            $acData = $a_c->field('id,date')->where("user_id=".$val['user_id'])->find();
            if($acData['date'] == $current_mon){
                $ResData['id'] = $acData['id'];
                //修改
                if ($a_c->save($ResData) === false) {
                    $this->ajaxReturn($a_c->getError());
                }
            }else{
                //新增
                if ($a_c->add($ResData) === false) {
                    $this->ajaxReturn($a_c->getError());
                }
            }
        }

    }

    public function get_weekend_days($start_date,$end_date,$is_workday = false){

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