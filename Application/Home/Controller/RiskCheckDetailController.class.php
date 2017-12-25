<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/7
 * Time: 10:41
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 核检数据明细 2017/6/5)
 * Class RiskCheckController
 * @package Home\Controller
 */
class RiskCheckDetailController extends BaseController {
    protected $totalPage = 0;
    public function index() {

        $this->adverlist=M('advertiser')->field('name,id')->select();
        $this->comlist=M('product')->field('name,id')->select();
        $this->salelist=M('user')->field('real_name as name,id')->where("status=1")->select();
        $this->swlist=M('user')->field('real_name as name,id')->where("status=1")->select();
        $this->linelist=M('business_line')->field('name,id')->where('status=1')->select();
        $this->superlist=M('supplier')->field('name,id')->select();
        //$this->zt=M('data_dic')->field('name,id')->where("dic_type=4")->select();

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('ret', $list);
        $this->display();
    }

    /*明细*/
    public function getList(){

        $mon = empty(I('get.mon'))?date('Y-m',time()):trim(I('get.mon'));
        $pl_id = I('get.pl_id');
        $this->assign('mon', $mon);
        $this->assign('pl_id', $pl_id);
        if($mon){

            $where = "1=1";
            if($mon){
                $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')='".$mon."'";
            }else{
                $where .= " AND DATE_FORMAT(d.adddate, '%Y-%m')='".date('Y-m',time())."'";
            }
            $adIds = I('get.adIds','');
            if(!empty($adIds)) {
                $where .= " AND d.adverid IN ($adIds)";
            }
            $proIds = I('get.proid','');
            if(!empty($proIds)) {
                $where .= " AND c.id IN ($proIds)";
            }

            $salerIds = I('get.saler','');
            if(!empty($salerIds)) {
                $where .= " AND d.`salerid` IN ($salerIds)";
            }
            $supIds = I('get.sup','');
            if(!empty($supIds)) {
                $where .= " AND e.superid IN ($supIds)";
            }
            $busmanIds = I('get.busman','');
            if(!empty($busmanIds)) {
                $where .= " AND e.businessid IN ($busmanIds)";
            }
            $blIds = I('get.bl','');
            if(!empty($blIds)) {
                $where .= " AND d.lineid IN ($blIds)";
            }
            //查询总裁办数据
            if($pl_id==100){
                $pl_id = "34,36,40,42,44,45";
            }
            if($pl_id){
                $where .= " AND d.lineid IN ($pl_id)";
            }
            $rea_son = I('get.rea_son');
            if($rea_son){
                $where .= " AND (d.reason ='".$rea_son."' or e.reason ='".$rea_son."')";
            }

            //排序
            $order = I('get.order');
            if($order){
                if($order == 'in_money'){
                    $orders = '(d.money - d.newmoney)';
                }elseif($order == 'in_money_desc'){
                    $orders = '(d.money - d.newmoney) desc';
                }elseif($order == 'out_money'){
                    $orders = '(e.money - e.newmoney)';
                }elseif($order == 'out_money_desc'){
                    $orders = '(e.money - e.newmoney) desc';
                }

            }
            if(empty($orders)){
                $orders = 'd.id desc';
            }
            $listwhere = $where . " AND (d.id > 0 || e.id > 0)";
            $model = M('charging_logo');
            $ret = $model
                ->field('
                d.reason,
                e.reason as out_reason,
                ad.name as aci_name,
                sup.name as sup_name,
                bus.name as bus_name,
                c.name as pro_name,
                ua.real_name as sale_name,
                ub.real_name as sw_name,
			      d.comid,
				  e.superid,
				  d.`salerid`,
				  e.`businessid`,
				  DATE_FORMAT(d.adddate, \'%Y-%m\') AS ym,
				  SUM(d.money) AS in_money,
				  SUM(e.money) AS out_money,
				  SUM(d.newmoney) AS in_newmoney,
				  SUM(e.newmoney) AS out_newmoney,
				  (SUM(d.newmoney)-SUM(e.newmoney)) as real_money,
				  sum(if(d.status>1 && d.status!=9,d.newmoney,0))/sum(if(d.status!=0 && d.status!=9,d.newmoney,0)) as inquerenlv,
			sum(if(e.status>1 && e.status!=9,e.newmoney,0))/sum(if(e.status!=0 && e.status!=9,e.newmoney,0)) as outquerenlv')
                ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id && d.status != 0
				  JOIN boss_advertiser ad
				    ON a.ad_id = ad.id
				  JOIN boss_product c
				    ON a.prot_id = c.id
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
				    )
				  JOIN boss_supplier sup
				     ON sup.id=b.sup_id
				  JOIN boss_business_line bus
				     ON bus.id=IF(
				      d.lineid IS NULL,
				      e.lineid,
				      d.lineid
				    )
				  LEFT JOIN boss_user ua
				      ON ua.id=d.`salerid`
                LEFT JOIN boss_user ub
				      ON ub.id=e.`businessid`
				     ")
                ->group("d.id,
                      e.lineid,
                      d.adverid,
                      a.prot_id,
                      e.superid")
                ->order($orders)
                ->where($listwhere)
                ->page($_GET['p'], C('LIST_ROWS'))
                ->select();

            $ret = $this->_checkPriceList_detail($ret);
            $subQuery = $model
                ->field('a.id')
                ->join("
				   a
				  LEFT JOIN boss_daydata d
				    ON d.jfid = a.id && d.status != 0
				  JOIN boss_advertiser ad
				    ON a.ad_id = ad.id
				  JOIN boss_product c
				    ON a.prot_id = c.id
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
				    )
				  JOIN boss_supplier sup
				     ON sup.id=b.sup_id
				  JOIN boss_business_line bus
				     ON bus.id=IF(
				      d.lineid IS NULL,
				      e.lineid,
				      d.lineid
				    )
				  LEFT JOIN boss_user ua
				      ON ua.id=d.`salerid`
                LEFT JOIN boss_user ub
				      ON ub.id=e.`businessid`
				     ")
                ->group("d.id,
                      e.lineid,
                      d.adverid,
                      a.prot_id,
                      e.superid")
                ->where($listwhere)
                ->buildSql();
            $this->totalPage = $model->table($subQuery.' a')->where()->count();

            // //第二次筛选数据
            // foreach ($ret as $k => $v) {
            //     $ret[$k]["in_money"] = number_format($v["in_money"],2,".",",");
            //     $ret[$k]["inCheckMoney"] = number_format($v["inCheckMoney"],2,".",",");
            //     $ret[$k]["inCheckMoney"] = number_format($v["inCheckMoney"],2,".",",");
            // }
            return $ret;
        }
    }

    private function _checkPriceList_detail($datas=array()) {
        if (!empty($datas)) {
            foreach ($datas as $key=>$val) {
                $datas[$key]['in_money']       = round($datas[$key]['in_money'],2);
                $datas[$key]['in_newmoney']    = round($datas[$key]['in_newmoney'],2);
                $datas[$key]['out_money']      = round($datas[$key]['out_money'],2);
                $datas[$key]['out_newmoney']   = round($datas[$key]['out_newmoney'],2);
                // $datas[$key]['inquerenlv']  = round($datas[$key]['inquerenlv'],2);
                // $datas[$key]['outquerenlv'] = round($datas[$key]['outquerenlv'],2);
                
                $in_a                          = round($datas[$key]['inquerenlv'],2);
                $datas[$key]['inquerenlv']     = $in_a>0?($in_a*100)."%":0;
                $out_a                         = round($datas[$key]['outquerenlv'],2);
                $datas[$key]['outquerenlv']    = $out_a>0?($out_a*100)."%":0;
                
                unset($in_a);
                unset($out_a);
                
                //原始预估收入
                $inMoney                       = (float)$datas[$key]['in_money'];
                //确认收入
                $inNewMoney                    = (float)$datas[$key]['in_newmoney'];
                
                $datas[$key]['in_money']       = number_format($val['in_money'], 2, '.', ',');
                $datas[$key]['in_newmoney']    = number_format($val['in_newmoney'], 2, '.', ',');
                
                //收入核减金额 预估收入-确认收入
                $inCheckMoney                  = bcsub($inMoney, $inNewMoney, 2);
                //收入核减率
                $inCheckRatio                  = (float)bcdiv($inCheckMoney,$inMoney,2);
                //原始预估成本
                $outMoney                      = (float)$datas[$key]['out_money'];
                //确认成本
                $outNewMoney                   = (float)$datas[$key]['out_newmoney'];
                
                $datas[$key]['out_money']      = number_format($val['out_money'], 2, '.', ',');
                $datas[$key]['out_newmoney']   = number_format($val['out_newmoney'], 2, '.', ',');

                //成本核减金额 预估成本-确认成本；
                $outCheckMoney                 = bcsub($outMoney, $outNewMoney, 2);
                //成本核减率
                $outCheckRatio                 = (float)bcdiv($outCheckMoney,$outMoney,2);
                
                $realMoney                     = (float)$datas[$key]['real_money'];
                $realRatio                     = (float)bcdiv($realMoney,$inNewMoney,2);
                
                $closeData                     = M('closing')->field('(sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) - (sum(if(out_status!=0 && out_status!=9,out_newmoney,0)))) as real_newmoney,sum(if(in_status!=0 && in_status!=9,in_newmoney,0)) as in_newmoney')->where("in_comid=".$val['comid']." && DATE_FORMAT(adddate, '%Y-%m')='".$val['ym']."'")->find();
                $closeRatio                    = (float)bcdiv($closeData['real_newmoney'],$closeData['in_newmoney'],2);

                $datas[$key]['incheckmoney']   =  number_format($inCheckMoney, 2, '.', ',');
                $datas[$key]['incheckratio']   =  $inCheckRatio>0?($inCheckRatio*100)."%":"";
                $datas[$key]['outcheckmoney']  = number_format($outCheckMoney, 2, '.', ',');
                $datas[$key]['outcheckratio']  = $outCheckRatio>0?($outCheckRatio*100)."%":"";
                $datas[$key]['realmoney']      =  number_format($realMoney, 2, '.', ',');
                $datas[$key]['realratio']      = $realRatio>0?($realRatio*100)."%":"";
                $datas[$key]['closemoney']     = number_format($closeData['real_newmoney'], 2, '.', ',');
                $datas[$key]['closeratio']     = $closeRatio>0?($closeRatio*100)."%":"";
            }
        }
        return $datas;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        $title = array('aci_name' => '广告主', 'sup_name' => '供应商', 'bus_name' => '业务线', 'pro_name' => '产品','sale_name' => '销售','sw_name' => '商务','in_money' => '原始收入','incheckmoney' => '收入核检额','incheckratio' => '收入核检率','out_money' => '原始成本','outcheckmoney' => '成本核检额','outcheckratio'=>'成本核检率','inquerenlv'=>'收入确认率','outquerenlv'=>'成本确认率','realmoney'=>'即时利润额','realratio'=>'即时利润率','closemoney'=>'关账利润额','closeratio'=>'关账利润率','reason'=>'收入核检原因','out_reason'=>'成本核检原因');
        $csvObj = new \Think\Csv();
        
        $csvObj->put_csv($list, $title, '核减数据明细'.date('Y-m-d H:i:s',time()));
    }
}