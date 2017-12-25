<?php
/**
 * 月初的时候执行，将上个月的考勤数据插入表中
 * 考勤定时任务
 * 数据：考勤扣款、实际出勤天数、全勤奖
 * User: owq
 * Date: 2017/5/16
 * Time: 14:03
 */
namespace Home\Controller;
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
        $user_ext = M('user_ext');
        $configModel = M('attend_config');
        $attend = M('attend_duty');
        $user = M('hr_manage');
        $a_c = M('attendance_charge');
        $userData = $user->field('user_id')->where('status !=1')->select();

        foreach($userData as $key=>$val) {


            $J = $beginDate;
            $n1 = 0;//迟到
            $n2 = 0;//早退
            $n3 = 0;//未打卡
            $t1 = '';//30分内
            $t2 = '';//31-119分内
            $t3 = '';//120-239分内
            $t4 = '';//240分以上
            $bingjia = '';
            $shijia = '';
            $chanjia = '';
            $kuanggong = '';
            $koukuan = '';
            for (; $J <= $endDate; $J = date("Y-m-d", strtotime($J) + 86400)) {

                if ($val['user_id']) {
                    $USER_ID = M('user')->field('username,real_name')->where("id=" . $val['user_id'])->find();
                    //获取流程里面的数据
                    $flowData = M('flow_data_392')->field('data_9,data_10,data_7')->where("'" . $J . "'>=date_format(data_9,'%Y-%m-%d') and '" . $J . "'<=date_format(data_10,'%Y-%m-%d') and begin_user='" . $USER_ID['username'] . "'")->find();
                    if ($flowData) {

                        $c_day = (strtotime(date('Y-m-d', strtotime($flowData['data_10']))) - strtotime(date('Y-m-d', strtotime($flowData['data_9'])))) / 86400;
                        $hour = floor((strtotime($flowData['data_10']) - strtotime($flowData['data_9'])) % 86400 / 3600);
                        if ($hour > 3 && $hour < 8) {
                            $hour = 4;
                        } elseif ($hour >= 8) {
                            switch ($c_day) {
                                case 0:
                                    $hour = 8;
                                    break;
                                case 1:
                                    $hour = 16;
                                    break;
                                case 2:
                                    $hour = 24;
                                    break;
                                case 3:
                                    $hour = 32;
                                    break;
                                case 4:
                                    $hour = 40;
                                    break;
                            }
                        }
                        switch ($flowData['data_7']) {
                            case '病假':
                                $bingjia += $hour;
                                break;
                            case '事假':
                                $shijia += $hour;
                                break;
                            case '产假':
                                $chanjia += $hour;
                                break;
                        }
                    }

                    $ue = $user_ext->field('DUTY_TYPE')->where("UID=" . $val['user_id'])->find();//获取打卡类型
                    if(empty($ue['duty_type'])){
                        $ue['duty_type'] = 2;
                    }
                    if ($ue['duty_type']) {//获取打卡时间
                        $cf = $configModel->field('DUTY_TIME1,DUTY_TIME2')->where("DUTY_TYPE=" . $ue['duty_type'])->find();
                        $time1 = $cf['duty_time1'];
                        $time2 = $cf['duty_time2'];
                    }

                    $attendData = $attend->field('*')->where("USER_ID = '" . $USER_ID['username'] . "' and to_days(REGISTER_TIME)=to_days('" . $J . "')")->order('REGISTER_TIME desc')->select();

                    if (count($attendData) == 1) {
                        if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                        } else {
                            if ($attendData[0]['register_type'] == 1) {
                                $n3++;
                            } elseif ($attendData[0]['register_type'] == 2) {
                                $n3++;
                            }
                        }
                    } elseif (count($attendData) > 1) {
                        foreach ($attendData as $key1 => $val1) {
                            if (!empty($val1['register_time'])) {
                                if ($val1['register_type'] == 1) {

                                    if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) > strtotime($time1)) {
                                        $n1++;
                                        $minute = floor((strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) - strtotime($time1)) % 86400 / 60);
                                        //是否需要判断迟到有没有走流程 start $flowData为请休假流程数据
                                        if (empty($flowData) or date('H', strtotime($flowData['data_9'])) >= 12) {
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
                                } elseif ($val1['register_type'] == 2) {
                                    if (date('N', strtotime($J)) == 6 || date('N', strtotime($J)) == 7) {
                                    } else {
                                        if (strtotime(date('H:i:' . '59', strtotime($val1['register_time']))) < strtotime($time2)) {
                                            $n2++;
                                        }
                                    }
                                }
                            }
                        }
                    } elseif (M('attend_holiday')->field('HOLIDAY_NAME')->where("'" . $J . "'>=BEGIN_DATE and '" . $J . "'<=END_DATE")->find()) {
                    } elseif (date('N', strtotime($J)) != 6 && date('N', strtotime($J)) != 7) {
                        $n3 = $n3 + 2;
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
            if($n1 == 0 && $n2==0 && $n3==0 && $bingjia == '' && $shijia == '' && $kuanggong == '' && $chanjia == ''){
                $ResData['full_att'] = 200;//全勤
            }else{
                $ResData['full_att'] = 0;
            }
            if($n3>10){//未打卡大于10次，迟到一次算旷工半天
                $sjcq = 21.75 - $shijia/8 - $bingjia/8 - ($n3-10)/2;
            }else{
                $sjcq = 21.75 - $shijia/8 - $bingjia/8;
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
                }else{
                    //$this->ajaxReturn("TRUE");
                }
            }else{
                //新增
                if ($a_c->add($ResData) === false) {
                    $this->ajaxReturn($a_c->getError());
                }
            }
        }

    }
}