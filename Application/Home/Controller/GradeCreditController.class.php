<?php
/**广告主、供应商 实力、信用评级定时任务
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/26
 * Time: 16:17
 */
namespace Home\Controller;
use Common\Controller\BaseController;

class GradeCreditController extends BaseController {

    /*广告主、供应商 实力、信用评级定时任务*/
    public function index() {

        $pages = I('get.pages');
        if(empty($pages)){
            $page = 0;
        }else{
            $page = $pages*100;
        }
        //广告主
        $advModel = M('advertiser');
        $advData = $advModel->field('id,rating_level,penalty_deduction')->where('status=1')->order('id desc')->limit($page,100)->select();            //->limit($page,100)

        if($advData) {

            foreach ($advData as $key => $val) {
                switch ($val['rating_level']) {//等级
                    case 1:
                        $rating_level = 30;
                        break;
                    case 2:
                        $rating_level = 20;
                        break;
                    case 3:
                        $rating_level = 10;
                        break;
                    case 4:
                        $rating_level = 5;
                        break;
                }
                $penalty_deduction = $val['penalty_deduction'];//处罚扣分

                //合作时长
                $day = M('daydata')->field('min(adddate) as adddate')->where("adverid=" . $val['id'])->find();
                if($day) {
                    $a = (time() - strtotime($day['adddate'])) / 86400 / 360;
                    if ($a > 0 && $a < 1) {
                        $duration = 5;
                    } elseif ($a >= 1 && $a < 2) {
                        $duration = 10;
                    } elseif ($a >= 2 && $a < 3) {
                        $duration = 15;
                    } elseif ($a >= 3) {
                        $duration = 20;
                    }else{
                        $duration = 0;
                    }
                }

                //月均合作流水
                $mon = $this->getMonthNum(date('Y-m-d'), $day['adddate']);
                $dayData = M('daydata')->field('sum(newmoney) as newmoney')->where("status not in (0,1,9) and adverid=" . $val['id'])->find();
                if($dayData) {
                    $monAvg = $dayData['newmoney'] / $mon;
                    if ($monAvg > 0 && $monAvg < 50000) {
                        $dataAvg = 0;
                    } elseif ($monAvg >= 50000 && $monAvg < 200000) {
                        $dataAvg = 10;
                    } elseif ($monAvg >= 200000 && $monAvg < 500000) {
                        $dataAvg = 20;
                    } elseif ($monAvg >= 500000 && $monAvg < 1000000) {
                        $dataAvg = 35;
                    } elseif ($monAvg >= 1000000) {
                        $dataAvg = 50;
                    }else{
                        $dataAvg = 0;
                    }
                }
                //合作评级 = 实力评级+合作时长+月均合作流水+处罚扣分
                $advGrade = $rating_level + $duration + $dataAvg + $penalty_deduction;

                if ($advGrade < 60) {
                    $advG = 'D';
                } elseif ($advGrade >= 60 && $advGrade < 70) {
                    $advG = 'C';
                } elseif ($advGrade >= 70 && $advGrade < 80) {
                    $advG = 'B';
                } elseif ($advGrade >= 80 && $advGrade < 90) {
                    $advG = 'A';
                } elseif ($advGrade >= 90 && $advGrade <= 100) {
                    $advG = 'S';
                }
                $data['ad_grade'] = $advG;
                $data['val_grade'] = $advGrade;

                //信用评级=合作评级分数+加分项+违约减分项
                $advD = $advModel->field('a.`name`,b.id,b.settle_cycle,b.receivables_day,max(c.adddate) as adddate')
                    ->join('a left join boss_product b on a.id=b.ad_id LEFT JOIN boss_daydata c ON c.comid=b.id')
                    ->where("a.id=" . $val['id'])->group("c.comid,DATE_FORMAT(c.adddate,'%Y-%m')")->select();
                $jaifen = 0;
                $jianifen = 0;
                foreach ($advD as $val2) {
                    if ($val2['adddate']) {
                        switch ($val2['settle_cycle']) {
                            case 1:
                                $d = date('Y-m-d', strtotime($val2['adddate'] . ' +' . $val2['receivables_day'] . 'day'));
                                break;
                            case 2:
                                $d = date('Y-m-d', strtotime($val2['adddate'] . ' +' . $val2['receivables_day'] . 'day'));
                                break;
                            case 3:
                                $d = date('Y-m-d', strtotime($val2['adddate'] . ' +' . $val2['receivables_day'] . 'day'));
                                break;
                        }
                        $firstday = date('Y-m-01', strtotime($d));
                        $pay = M('pay')->field('adddate')->where("paymentname='" . $val2['name'] . "' and adddate>='" . $firstday . "' and adddate<='" . $d . "'")->find();
                        if ($pay) {
                            $jaifen++;
                        } else {
                            $jianifen++;
                        }
                    }
                }
                $jia = (intval($jaifen / 3)) * 5;//连续3次均按时付款 +5分
                $jian = $jianifen * 5;//历史每逾期一次 -5分
                if ($jianifen >= 3) {
                    $jian = $jian + 10; //连续3次及以上逾期 额外-10分
                }
                $ad_credit = $advGrade + $jia - $jian;
                if ($ad_credit < 60) {
                    $advC = 'D';
                } elseif ($ad_credit >= 60 && $ad_credit < 70) {
                    $advC = 'C';
                } elseif ($ad_credit >= 70 && $ad_credit < 80) {
                    $advC = 'B';
                } elseif ($ad_credit >= 80 && $ad_credit < 90) {
                    $advC = 'A';
                } elseif ($ad_credit >= 90) {
                    $advC = 'S';
                }
                $data['ad_credit'] = $advC;
                $data['val_credit'] = $ad_credit;
                $advModel->where('id=' . $val['id'])->save($data);
            }
        }

        /*供应商*/
        $supModel = M('supplier');
        $supData = $supModel->field('id')->where('status=1')->order('id desc')->limit($page,100)->select();//->limit($page,100)
        if($supData) {
            foreach ($supData as $key => $val2) {
                //合作时长
                $days = M('daydata_out')->field('min(adddate) as adddate')->where("superid=" . $val2['id'])->find();
                if($days) {
                    $a = (time() - strtotime($days['adddate'])) / 86400 / 360;
                    if ($a > 0 && $a < 1) {
                        $duration = 5;
                    } elseif ($a >= 1 && $a < 2) {
                        $duration = 10;
                    } elseif ($a >= 2 && $a < 3) {
                        $duration = 20;
                    } elseif ($a >= 3) {
                        $duration = 30;
                    } else {
                        $duration = 0;
                    }
                }

                //月均毛利
                $mon = $this->getMonthNum(date('Y-m-d'), $days['adddate']);
                //$dayData = M('daydata_out')->field('sum(newmoney) as newmoney')->where("status not in (0,1,9) and superid=" . $val2['id'])->find();
                $dayData = M('daydata_inandout')->field('( sum(if(in_status not in (0,9),in_newmoney,0)) - sum(if(out_status not in (0,9),out_newmoney,0)) ) as newmoney')->where(" out_superid=" . $val2['id'])->find();//status not in (0,1,9) and
                if($dayData) {
                    $monAvg = $dayData['newmoney'] / $mon;
                    if ($monAvg > 0 && $monAvg < 50000) {
                        $dataAvg = 20;
                    } elseif ($monAvg >= 50000 && $monAvg < 200000) {
                        $dataAvg = 30;
                    } elseif ($monAvg >= 200000 && $monAvg < 500000) {
                        $dataAvg = 40;
                    } elseif ($monAvg >= 500000 && $monAvg < 1000000) {
                        $dataAvg = 60;
                    } elseif ($monAvg >= 1000000) {
                        $dataAvg = 70;
                    } else {
                        $dataAvg = 0;
                    }
                }
                //评级标准来源于两方面：合作时长+月均毛利
                $supGrade = $duration + $dataAvg;
                if ($advGrade < 60) {
                    $supG = 'D';
                } elseif ($supGrade >= 60 && $supGrade < 70) {
                    $supG = 'C';
                } elseif ($supGrade >= 70 && $supGrade < 80) {
                    $supG = 'B';
                } elseif ($supGrade >= 80 && $supGrade < 90) {
                    $supG = 'A';
                } elseif ($supGrade >= 90 && $supGrade <= 100) {
                    $supG = 'S';
                }
                $data['grade'] = $supG;

                //供应商信用评级:主要判断标准为是否有作弊行为(目前系统没有核减原因)
                //$data['credit'] = '';
                $supModel->where('id=' . $val2['id'])->save($data);
            }
        }
        echo "广告主、供应商 合作评级、信用评级定时任务执行完成";exit;
    }

    function getMonthNum( $date1, $date2, $tags='-' ){
        $date1 = explode($tags,$date1);
        $date2 = explode($tags,$date2);
        if(abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1])>1){
            return abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]) - 1;//业务要求少算一个月
        }elseif(abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]) == 0){
            return 1;
        }else{
            return abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
        }
    }
}