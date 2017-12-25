<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/02/20
 * Time: 15:12
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class AreController extends BaseController {

    //统计当年1月至当前月 暂停广告主个数
    public function index(){
        $DataList = array();
        $now_m = date('m');//当前月份
        for($i=1;$i<=$now_m;$i++){
            if($i<10){
                $mon = '0'.$i;
            }else{
                $mon = $i;
            }
            $start_time = date("Y-m-d", strtotime("-2 months", strtotime(date('Y-'.$mon.'-d'))));//上上个月
            $end_time = date("Y-m-d", strtotime("-1 months", strtotime(date('Y-'.$mon.'-d'))));//上个月

            $BeginDate = date('Y-m-01', strtotime($start_time));//上上个月第一天
            $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));//上上个月最后一天

            $s_BeginDate = date('Y-m-01', strtotime($end_time));//上个月第一天
            $s_EndDate = date('Y-m-d', strtotime("$s_BeginDate +1 month -1 day"));//上个月最后一天
            //echo $BeginDate.'--'.$EndDate."\n";
            //echo $s_BeginDate.'--'.$s_EndDate."\n";
            //查询
            $dayList = M()->query("SELECT COUNT(*) AS zt_count from(SELECT adverid FROM boss_daydata WHERE adddate >='".$BeginDate."' AND adddate <='".$EndDate."' AND adverid NOT IN (SELECT adverid FROM boss_daydata WHERE adddate >='".$s_BeginDate."' AND adddate <='".$s_EndDate."' GROUP BY adverid) GROUP BY adverid) b");//统计当年1月至当前月 暂停广告主个数
            //echo M()->getLastSql();exit;
            if($dayList){
                foreach($dayList as $val){
                    $DataList[$mon] = $val['zt_count'];
                }

            }else{
                $DataList[$mon] = 0;
            }
        }
        print_r($DataList);exit;
    }

}

