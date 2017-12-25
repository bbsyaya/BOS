<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/5
 * Time: 15:44
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 核检数据(风控新需求 2017/6/5)
 * Class RiskCheckController
 * @package Home\Controller
 */
class RiskCheckController extends BaseController {
    public function index() {

        /*$t1=date('m.1');//本月1号
        $t2=date("m.d");//当前日期
        $t3=date("m.1",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月1号
        $t4=date("m.d",strtotime(date('Y').'-'.(date("m")-1).'-'.date('d')));//上月今天
        $t5=date("m.d",strtotime(date('Y').'-'.date("m").'-0'));//上月末
        $t6=date('Y-m-d',time()-3600*24*7);//7天前
        $t7=date('Y-m-d',time()-3600*24*30);//30天前
        $t8=date("Y-01-01");
        $t9=date("Y-m-d");
        $this->date_arr=array($t1,$t2,$t3,$t4,$t5,$t6,$t7,$t8,$t9);*/

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list', $list);

        $linelist=M('business_line')->field('id,name')->where('status=1')->select();
        $this->assign('linelist',$linelist);

        $this->display();
    }

    //获取列表
    public function getList($where, $field) {

        $where = "1=1";
        $time_s = I('get.time_s');
        if($time_s){
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')='".$time_s."'";
        }else{
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')>='".date('Y-01',time())."' && DATE_FORMAT(d.adddate, '%Y-%m')<='".date('Y-m',time())."'";
        }
        $lineid = I('get.lineid','');
        $this->assign('lineid', $lineid);

        if(!empty($lineid)) {
            $where .= " AND d.lineid IN ($lineid)";
        }
        $rea_son = I('get.rea_son','');
        if(!empty($proIds)) {
            $where .= " AND c.id IN ($rea_son)";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('d.lineid','e.lineid');
        $arr_name['user']=array('d.salerid','e.businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " && $myrule_data";


        $listwhere = $where . " AND (d.id > 0 || e.id > 0)";

        $model = M('charging_logo');
        $ret = $model
            ->field('
				  DATE_FORMAT(d.adddate, \'%Y-%m\') AS ym,
				  SUM(d.money) AS in_money,
				  SUM(e.money) AS out_money,
				  SUM(d.newmoney) AS in_newmoney,
				  SUM(e.newmoney) AS out_newmoney,
				  sum(if(d.status>1 && d.status!=9,d.newmoney,0))/sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) as inquerenlv,
			sum(if(e.status>1 && e.status!=9,e.newmoney,0))/sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as outquerenlv	  ')
            ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id  && d.status != 0
				  LEFT JOIN boss_daydata_out e
				    ON e.jfid = a.id && d.adddate = e.adddate && e.status != 0
				  LEFT JOIN boss_charging_logo_assign b
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
            ->group("DATE_FORMAT(d.adddate, '%Y-%m')")
            ->order("DATE_FORMAT(d.adddate, '%Y-%m') desc")
            ->where($listwhere)
            ->page($_GET['p'], C('LIST_ROWS'))
            ->select();
            if(I("showsql")=="showsql023"){
                print_r($model->getLastSql());
                exit;
            }
            // echo $model->getLastSql();exit;
        $ret = $this->_checkPriceList($ret);
        return $ret;
    }

    private function _checkPriceList($datas=array()) {
        if (!empty($datas)) {
            foreach ($datas as $key=>$val) {
                $datas[$key]['in_money']         = round($datas[$key]['in_money'], 2);
                $datas[$key]['in_newmoney']      = round($datas[$key]['in_newmoney'], 2);
                $datas[$key]['out_money']        = round($datas[$key]['out_money'], 2);
                $datas[$key]['out_newmoney']     = round($datas[$key]['out_newmoney'],2);
                $in_a                            = round($datas[$key]['inquerenlv'],2);
                $datas[$key]['inquerenlv']       = $in_a>0?($in_a*100)."%":0;
                $out_a                           = round($datas[$key]['outquerenlv'],2);
                $datas[$key]['outquerenlv']      = $out_a>0?($out_a*100)."%":0;
                
                unset($in_a);
                unset($out_a);
                
                //原始预估收入
                $inMoney                         = (float)$datas[$key]['in_money'];
                //确认收入
                $inNewMoney                      = (float)$datas[$key]['in_newmoney'];
                
                $datas[$key]['in_money']         = number_format($val['in_money'], 2, '.', ',');
                $datas[$key]['in_newmoney']      = number_format($val['in_newmoney'], 2, '.', ',');
                
                
                //收入核减金额 预估收入-确认收入
                $inCheckMoney                    = bcsub($inMoney, $inNewMoney, 2);
                //收入核减率
                $inCheckRatio                    = (float)bcdiv($inCheckMoney,$inMoney,2);
                //原始预估成本
                $outMoney                        = (float)$datas[$key]['out_money'];
                //确认成本
                $outNewMoney                     = (float)$datas[$key]['out_newmoney'];
                
                $datas[$key]['out_money']        = number_format($val['out_money'], 2, '.', ',');
                $datas[$key]['out_newmoney']     = number_format($val['out_newmoney'], 2, '.', ',');
                
                //成本核减金额 预估成本-确认成本；
                $outCheckMoney                   = bcsub($outMoney, $outNewMoney, 2);
                //成本核减率
                $outCheckRatio                   = (float)bcdiv($outCheckMoney,$outMoney,2);
                
                //利润变动额 （预估收入-预估成本）-（确认收入-确认成本）
                $profitChange                    = bcsub(bcsub($inMoney, $outMoney, 2),bcsub($inNewMoney, $outNewMoney, 2));
                //利润变动率 利润变动额/（预估收入-预估成本）、
                $_tmp                            = bcsub($inMoney, $outMoney, 2);
                $profitChangeRaio                = $_tmp==0 ? 0 : bcdiv($profitChange, $_tmp, 2);
                
                $datas[$key]['inCheckMoney']     =  number_format($inCheckMoney, 2, '.', ',');
                $datas[$key]['inCheckRatio']     =  $inCheckRatio>0?($inCheckRatio*100)."%":"";
                $datas[$key]['outCheckMoney']    = number_format($outCheckMoney, 2, '.', ',');
                $datas[$key]['outCheckRatio']    = $outCheckRatio>0?($outCheckRatio*100)."%":"";
                $datas[$key]['profitChange']     =  number_format($profitChange, 2, '.', ',');
                $datas[$key]['profitChangeRaio'] = $profitChangeRaio>0?($profitChangeRaio*100)."%":"";
            }
        }
        return $datas;
    }

    /*通过业务线查询核减概览数据*/
    public function AjaxRisk(){

        $lineid = I('get.lineid');
        $where = "1=1";
        $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')>='".date('Y-01',time())."' && DATE_FORMAT(d.adddate, '%Y-%m')<='".date('Y-m',time())."'";
         //查询总裁办数据
        if($lineid==100){
            $lineid = "34,36,40,42,44,45";
        }
        if(!empty($lineid)) {
            $where .= " AND d.lineid IN ($lineid)";
        }
        $listwhere = $where . " AND (d.id > 0 || e.id > 0)";
        $model = M('charging_logo');
        $ret = $model
            ->field('
				  DATE_FORMAT(d.adddate, \'%Y-%m\') AS ym,
				  SUM(d.money) AS in_money,
				  SUM(e.money) AS out_money,
				  SUM(d.newmoney) AS in_newmoney,
				  SUM(e.newmoney) AS out_newmoney,
				  sum(if(d.status>1 && d.status!=9,d.newmoney,0))/sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) as inquerenlv,
			sum(if(e.status>1 && e.status!=9,e.newmoney,0))/sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as outquerenlv	  ')
            ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id  && d.status != 0
				  LEFT JOIN boss_daydata_out e
				    ON e.jfid = a.id && d.adddate = e.adddate && e.status != 0
				  LEFT JOIN boss_charging_logo_assign b
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
            ->group("DATE_FORMAT(d.adddate, '%Y-%m')")
            ->order("DATE_FORMAT(d.adddate, '%Y-%m') desc")
            ->where($listwhere)
            ->select();
        $ret = $this->_checkPriceList($ret);
        $this->ajaxReturn($ret);exit;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        $title = array('ym' => '月份', 'in_money' => '原始收入', 'inCheckMoney' => '收入核检', 'in_newmoney' => '确认收入','inCheckRatio' => '收入核减率','out_money' => '原始成本','outCheckMoney' => '成本核检','out_newmoney' => '确认成本','outCheckRatio' => '成本核减率','inquerenlv' => '收入确认率','outquerenlv' => '成本确认率');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '核减预览'.date('Y-m-d-H:i:s',time()));
    }

    /*关账与实时利润分析*/
    public function AjaxAnalysis(){
        $lineid = I('get.lineid_a');

        $where = "1=1";
        $time_s = I('get.time_s_a');
        if($time_s){
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')='".$time_s."'";
        }else{
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')>='".date('Y-01',time())."' && DATE_FORMAT(d.adddate, '%Y-%m')<='".date('Y-m',time())."'";
        }
        $this->assign('lineid', $lineid);
        if(!empty($lineid)) {
            $where .= " AND d.lineid IN ($lineid)";
        }
        $rea_son = I('get.rea_son_a','');
        if(!empty($proIds)) {
            $where .= " AND c.id IN ($rea_son)";
        }

        $listwhere = $where . " AND (d.id > 0 || e.id > 0)";

        $model = M('charging_logo');
        $ret = $model
            ->field('
				  DATE_FORMAT(d.adddate, \'%Y-%m\') AS ym,
				  sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) AS in_newmoney,
				  sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as out_newmoney,
				  sum(if(d.status>1 && d.status!=9,d.newmoney,0))/sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) as inquerenlv,
			sum(if(e.status>1 && e.status!=9,e.newmoney,0))/sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as outquerenlv	  ')
            ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id  && d.status != 0
				  LEFT JOIN boss_daydata_out e
				    ON e.jfid = a.id && d.adddate = e.adddate && e.status != 0
				  LEFT JOIN boss_charging_logo_assign b
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
            ->group("DATE_FORMAT(d.adddate, '%Y-%m')")
            ->order("DATE_FORMAT(d.adddate, '%Y-%m') desc")
            ->where($listwhere)
            ->page($_GET['p'], C('LIST_ROWS'))
            ->select();
        foreach($ret as $key=>$val){

            $realMoney = bcsub($val['in_newmoney'], $val['out_newmoney'], 2);
            $ret[$key]['realMoney'] = $realMoney;
            $ret[$key]['realRatio'] = (float)bcdiv($realMoney,$val['in_newmoney'],2);
            $closeData = M('closing')->field('(sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) - sum(if(out_status!=0 && out_status!=9,out_newmoney,0)) ) as real_newmoney,sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) as in_newmoney')->where("DATE_FORMAT(adddate, '%Y-%m')='".$val['ym']."'")->find();
            $ret[$key]['closeMoney'] = round($closeData['real_newmoney'],2);
            $ret[$key]['closeRatio'] = (float)bcdiv($closeData['real_newmoney'],$closeData['in_newmoney'],2);
            $ret[$key]['inquerenlv'] = round($val['inquerenlv'],2);
            $ret[$key]['outquerenlv'] = round($val['outquerenlv'],2);
        }
        $this->ajaxReturn($ret);exit;
    }

    public function export_anal(){
        $where = array();
        //C('LIST_ROWS', '');
        $list = $this->AjaxAnalysis_export($where);
        $title = array('ym' => '月份', 'closeMoney' => '关账利润额', 'closeRatio' => '关账利润率', 'realMoney' => '即时利润额','realRatio' => '即时利润率','inquerenlv' => '收入确认率','outquerenlv' => '成本确认率');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '关账与实时利润分析'.date('Y-m-d-H:i:s',time()));
    }

    public function AjaxAnalysis_export($where){
        $lineid = I('get.lineid_a');

        $where = "1=1";
        $time_s = I('get.time_s_a');
        if($time_s){
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')='".$time_s."'";
        }else{
            $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')>='".date('Y-01',time())."' && DATE_FORMAT(d.adddate, '%Y-%m')<='".date('Y-m',time())."'";
        }
        $this->assign('lineid', $lineid);
        if(!empty($lineid)) {
            $where .= " AND d.lineid IN ($lineid)";
        }
        $rea_son = I('get.rea_son_a','');
        if(!empty($proIds)) {
            $where .= " AND c.id IN ($rea_son)";
        }

        $listwhere = $where . " AND (d.id > 0 || e.id > 0)";

        $model = M('charging_logo');
        $ret = $model
            ->field('
				  DATE_FORMAT(d.adddate, \'%Y-%m-%d\') AS ym,
				  sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) AS in_newmoney,
				  sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as out_newmoney,
				  sum(if(d.status>1 && d.status!=9,d.newmoney,0))/sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) as inquerenlv,
			sum(if(e.status>1 && e.status!=9,e.newmoney,0))/sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as outquerenlv	  ')
            ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id  && d.status != 0
				  LEFT JOIN boss_daydata_out e
				    ON e.jfid = a.id && d.adddate = e.adddate && e.status != 0
				  LEFT JOIN boss_charging_logo_assign b
				    ON b.cl_id = a.id && b.promotion_stime <= IF(
				      d.adddate IS NULL,
				      e.adddate,
				      d.adddate
				    ) && IF(
				      b.promotion_etime IS NULL,
				      1,
				      b.promotion_etime >= IF(
				        d.adddate IS NULL,
				        e.adddate,
				        d.adddate
				      )
				    ) ")
            ->group("DATE_FORMAT(d.adddate, '%Y-%m')")
            ->order("DATE_FORMAT(d.adddate, '%Y-%m') desc")
            ->where($listwhere)
            ->page($_GET['p'], C('LIST_ROWS'))
            ->select();
        foreach($ret as $key=>$val){

            $realMoney = bcsub($val['in_newmoney'], $val['out_newmoney'], 2);
            $ret[$key]['realMoney'] = $realMoney;
            $ret[$key]['realRatio'] = (float)bcdiv($realMoney,$val['in_newmoney'],2);
            $closeData = M('closing')->field('(sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) - sum(if(out_status!=0 && out_status!=9,out_newmoney,0)) ) as real_newmoney,sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) as in_newmoney')->where("DATE_FORMAT(adddate, '%Y-%m')='".$val['ym']."'")->find();
            $ret[$key]['closeMoney'] = round($closeData['real_newmoney'],2);
            $ret[$key]['closeRatio'] = (float)bcdiv($closeData['real_newmoney'],$closeData['in_newmoney'],2);
            $ret[$key]['inquerenlv'] = round($val['inquerenlv'],2);
            $ret[$key]['outquerenlv'] = round($val['outquerenlv'],2);
        }
        return $ret;
    }
}