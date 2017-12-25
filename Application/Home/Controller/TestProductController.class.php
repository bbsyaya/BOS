<?php
namespace Home\Controller;
use Common\Service;
use Common\Controller\BaseController;

/**
 * 测试产品监控
 * Class RiskTestProductController
 * @package Home\Controller
 */
class TestProductController extends BaseController {
    protected $totalPage = 0;

    public function index() {

        $date = I('get.date','');
        $date = empty($date) ? date('Y-m') : $date;

        $map['procode'] = trim(I("procode"));
        $this->assign("map",$map);
        $ps = M('product_state');
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.bl_id');
        $arr_name['user']=array('b.saler_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        if($myrule_data!='')$where= " && $myrule_data";
        else $where="";


        //先获取当月入库新增测试产品
        $prIdData = $ps->field('min(a.id) AS id,a.pr_id,a.cooperate_state')->join("a JOIN boss_product b ON a.pr_id=b.id")->where("DATE_FORMAT(b.add_time,'%Y-%m')>='{$date}' AND DATE_FORMAT(b.add_time,'%Y-%m')<='{$date}'".$where)->group('a.pr_id')->select();
        $pr_id = "";
        foreach($prIdData as $key=>$val){
            if($val['cooperate_state'] == 2){
                $pr_id .= $val['pr_id'].",";
            }
        }
        $pr_id = rtrim($pr_id,",");


        //数据权限
        $arr_name=array();
        $arr_name['line']=array('bl_id');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        if($myrule_data!='')$where= " && $myrule_data";
        else $where="";


        //停推变为测试
        $prData = M()->query("SELECT pr_id  FROM boss_product_state WHERE cooperate_state IN (2,3) and DATE_FORMAT(add_date,'%Y-%m')>='{$date}' AND DATE_FORMAT(add_date,'%Y-%m')<='{$date}' $where group by pr_id having count(id)>1");
        $adid = '';
        if($prData){
            foreach($prData as $key=>$val){
                $adid .=$val['pr_id'].",";
            }
            //$y_adid = rtrim($adid,",");
        }
        if($adid){
            $pr_id = $adid.$pr_id;
        }
        //获取新增测试产品的最终类型 根据类型分组求和
        $zs = 0;//正式
        $cs = 0;//测试
        $zt = 0;//停推
        if($pr_id){
            $sum = $ps->field('COUNT(a.id) AS count_id,a.pr_id,a.cooperate_state')->join("a JOIN (SELECT MAX(id) AS id FROM boss_product_state WHERE pr_id in (".$pr_id.") $where GROUP BY pr_id) b ON a.id=b.id")->group('a.cooperate_state')->select();
            //echo $ps->getLastSql();exit;
            foreach($sum as $key=>$val){
                if($val['cooperate_state'] == 1){
                    $zs = $val['count_id'];
                }elseif($val['cooperate_state'] == 2){
                    $cs = $val['count_id'];
                }elseif($val['cooperate_state'] == 3){
                    $zt = $val['count_id'];
                }
            }
        }

        $where = array();
        $list = $this->lists($this, $where);

        $this->assign('zs',$zs);
        $this->assign('cs',$cs);
        $this->assign('zt',$zt);

        //$this->assign('op_order_test_type',C('OPTION.order_test_type'));
        $this->assign('list', $list);
        $this->display();

    }


    //获取列表
    /**
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getList($where, $field) {

        $date = I('get.date','');
        $date = empty($date) ? date('Y-m') : $date;
        $ps = M('product_state');
        //先获取当月入库新增测试产品
        $prIdData = $ps->field('min(a.id) AS id,a.pr_id,a.cooperate_state')->join("a JOIN boss_product b ON a.pr_id=b.id")->where("DATE_FORMAT(b.add_time,'%Y-%m')>='{$date}' AND DATE_FORMAT(b.add_time,'%Y-%m')<='{$date}'")->group('a.pr_id')->select();
        $pr_id = "";
        foreach($prIdData as $key=>$val){
            if($val['cooperate_state'] == 2){
                $pr_id .= $val['pr_id'].",";
            }
        }
        $pr_id = rtrim($pr_id,",");

        //停推变为测试
        $prData = M()->query("SELECT pr_id  FROM boss_product_state WHERE cooperate_state IN (2,3) and DATE_FORMAT(add_date,'%Y-%m')>='{$date}' AND DATE_FORMAT(add_date,'%Y-%m')<='{$date}' group by pr_id having count(id)>1");
        $adid = '';
        if($prData){
            foreach($prData as $key=>$val){
                $adid .=$val['pr_id'].",";
            }
            //$y_adid = rtrim($adid,",");
        }

        if($adid){
            $pr_id = $adid.$pr_id;
        }


        //列表
        if($pr_id) {

            $adIds = I('get.adid','');
            if(!empty($adIds)) {
                $adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
                $where['d.id'] = array('in', $adIds);
            }
            $proIds = I('get.proid','');
            if(!empty($proIds)) {
                $proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
                $where['c.id'] = array('in', $proIds);
            }
            $procode = trim(I('procode'));
            if(!empty($procode)) {
                $proArr = M('product')->where("code like '%{$procode}%'")->field('id,name')->select();
                if(I("showsql")=="showsql023"){
                    print_r(M('product')->getLastSql());exit;
                }
                $where['c.id'] = array('in', $proIds);
            }
            $salerIds = I('get.salerid','');
            if(!empty($salerIds)) {
                $salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name AS name')->select();
                $where['c.saler_id'] = array('in', $salerIds);
            }

            $Row = $ps->join("a JOIN (SELECT MAX(id) AS id FROM boss_product_state WHERE pr_id in (" . $pr_id . ") GROUP BY pr_id) b ON a.id=b.id LEFT JOIN boss_product c ON a.pr_id=c.id LEFT JOIN boss_advertiser d ON d.id=c.ad_id LEFT JOIN boss_user e ON e.id=c.saler_id")->where($where)->count();

            /*if($_GET['p'] == 1){
                $_GET['p'] = 0;
            }elseif($_GET['p'] ==2){
                $_GET['p'] = 10;
            }elseif($_GET['p'] ==3){
                $_GET['p'] = 20;
            }elseif($_GET['p'] ==4){
                $_GET['p'] = 30;
            }
            $pr_id = array_slice(explode(',',$pr_id),$_GET['p'],10);
            $pr_id = implode(',',$pr_id);*/

            $mxData = $ps->field('a.pr_id,c.cooperate_state,c.`name` as pro_name,c.code as procode,d.`name` AS aci_name,e.real_name')->join("a JOIN (SELECT MAX(id) AS id FROM boss_product_state WHERE pr_id in (" . $pr_id . ") GROUP BY pr_id) b ON a.id=b.id LEFT JOIN boss_product c ON a.pr_id=c.id LEFT JOIN boss_advertiser d ON d.id=c.ad_id LEFT JOIN boss_user e ON e.id=c.saler_id")->where($where)->order('a.pr_id desc')->page($_GET['p'],10)->select();
             if(I("showsql")=="showsql023"){
                            print_r($ps->getLastSql());
                            print_r("<br>");
            }
            if($mxData) {
                foreach ($mxData as $key => $val) {

                    //不管什么状态都显示总金额
                    $dayData = M('daydata')->field('SUM(newmoney) AS newmoney')->where("comid=" . $val['pr_id'])->find();
                    if(I("showsql")=="showsql023"){
                        print_r(M('daydata')->getLastSql());
                        print_r("<br>");
                    }
                    $mxData[$key]['money_two'] = empty($dayData['newmoney'])?0:$dayData['newmoney'];


                    if ($val['cooperate_state'] == 3) {
                        
                        $mxData[$key]['status'] = '停推';

                    } elseif ($val['cooperate_state'] == 2) {
                        $mxData[$key]['status'] = '仍在测';
                        $proData = M('product')->field('id,`name`,saler_id,order_test_type,order_test_quota')->where("id=" . $val['pr_id'])->find();
                        if ($proData['order_test_type'] == 1) {//测试类型时间
                            $day = M('daydata')->field('adddate')->where("comid=" . $val['pr_id'])->group('adddate')->limit(1)->find();
                            if (strtotime(date('Y-m-d', time())) > strtotime($day['adddate'] . ' +' . $proData['order_test_quota'] . ' day')) {
                                $mxData[$key]['daoqi'] = "已到期";
                                if($proData['order_test_quota']){
                                    $mxData[$key]['tianshu'] = (strtotime(date('Y-m-d', time())) - strtotime($day['adddate'] . ' +' . $proData['order_test_quota'] . ' day')) / 86400;
                                }else{
                                    $mxData[$key]['tianshu'] ='产品未选择测试天数';
                                }

                            } else {
                                $mxData[$key]['daoqi'] = "未到期";
                            }
                        } elseif ($proData['order_test_type'] == 2) {
                            $dayNum = M('daydata')->field('SUM(newdata) AS newdata')->where("comid=" . $val['pr_id'])->find();
                            if ($dayNum['newdata'] > $val['order_test_quota']) {
                                $mxData[$key]['daoqi'] = "已到期";
                                $mxData[$key]['liangji'] = $dayNum['newdata'] - $val['order_test_quota'];
                            } else {
                                $mxData[$key]['daoqi'] = "未到期";
                            }
                        } elseif ($proData['order_test_type'] == 3) {
                            $dayMon = M('daydata')->field('SUM(newmoney) AS newmoney')->where("comid=" . $val['pr_id'])->find();
                            if ($dayMon['newmoney'] > $val['order_test_quota']) {
                                $mxData[$key]['daoqi'] = "已到期";
                                $mxData[$key]['jine'] = $dayMon['newmoney'] - $val['order_test_quota'];
                            } else {
                                $mxData[$key]['daoqi'] = "未到期";
                            }
                        }
                    } elseif ($val['cooperate_state'] == 1) {
                        $mxData[$key]['status'] = '正式上量';
                        $mxData[$key]['daoqi'] = "正式上量";
                    }
                }

            }

        }

        if(I("showsql")=="showsql023"){
            exit;
        }
        $this->totalPage =$Row;
        $this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));
        $this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
        $this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));

        return $mxData;

    }


    /**
     * 导出数据
     */
    public function export() {

        $where = array();
        C('LIST_ROWS', ''); //不分页
        $list = $this->lists($this, $where);

        $title = array('pro_name'=>'产品名称','aci_name'=>'广告主','real_name'=>'责任销售','money_two'=>'产生总金额',
            'daoqi'=>'是否到期','tianshu'=>'逾期天数','liangji'=>'逾期量级','jine'=>'逾期金额','status'=>'状态');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '测试产品统计'.date('Y-m-d H:i:s',time()));

    }

}


