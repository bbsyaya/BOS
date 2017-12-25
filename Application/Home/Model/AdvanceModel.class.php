<?php
/**预付
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/20
 * Time: 9:31
 */
namespace Home\Model;
use Think\Model;
class AdvanceModel extends Model {
    protected $trueTableName =   'boss_flow_data_432';
    public $totalPage = 0;
    public function getlist(){
        $wheres[]="a.data_11='预付款'";//AND a.data_30='已支付'
        //if(!empty(I('get.name')))$wheres[]="g.name like '%".I('get.name')."%'";
        if(!empty(I('get.name'))){
            $data_id = M()->query("SELECT ID from boss_a_supplier WHERE NAME like '%".I('get.name')."%' ");
            if(!empty($data_id)){
                $valid = "";
                foreach($data_id as $val_id){
                    $valid .= $val_id['id'].",";
                }
                $valid = rtrim($valid, ",");
                $wheres[]="a.data_40  in (".$valid.")";
            }else{
                $wheres[]="g.name like '%".I('get.name')."%'";
            }

        }
        if(!empty(I('get.pl_name')))$wheres[]="a.data_21 like '%".I('get.pl_name')."%'";
        if(!empty(I('get.data_1')))$wheres[]="a.data_1 like '%".I('get.data_1')."%'";
        if(!empty(I('get.pay_date')))$wheres[]="a.data_19 = '".I('get.pay_date')."'";
        if(!empty(I('get.status')) && I('get.status') ==2)$wheres[]="a.data_30 = '已支付'";
        elseif(I('get.status') ==1) $wheres[]="a.data_30 = ''";
        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';

        //已支付的预付款
        $data = $this->field('sum(a.data_22) as MoneySum,a.id,a.run_id,g.name,a.data_22,a.data_21,a.data_1,a.data_3,a.data_19,a.data_30,a.data_40,a.data_11,a.pay_money,a.pay_num,a.pay_date')->join('a join boss_supplier g on a.data_40=g.id')->where($where)->group('a.data_21,a.data_40')->order('a.id desc')->page($_GET['p'],10)->select();
        //echo $this->getLastSql();exit;

        foreach($data as $key=>$val){
            if($val['run_id']<= 47404){

                $ids = rtrim($val['data_40'], ",");
                //echo $ids;exit;
                $a_data = M()->query("SELECT NAME from boss_a_supplier WHERE ID =".$ids." ");
                $a_name = "";
                //print_r($a_data);exit;
                foreach($a_data as $a_val){
                    $a_name = $a_val['name'];
                }
                //echo $a_name;exit;
                $data[$key]['name'] =$a_name;
            }
        }

        $ca_ch = $this->field('a.id')->join('a join boss_supplier g on a.data_40=g.id')->where($where)->group('a.data_21,a.data_40')->buildSql();

        $this->totalPage = $this->table($ca_ch . ' bc')->where()->count();

        return $data;
    }

    function  getDataList(){

        $wheres[]="a.data_11='预付款' AND a.data_30='已支付'";
        //if(!empty(I('get.name')))$wheres[]="g.name like '%".I('get.name')."%'";
        if(!empty(I('get.name'))){
            $data_id = M()->query("SELECT ID from boss_a_supplier WHERE NAME like '%".I('get.name')."%' ");
            if(!empty($data_id)){
                $valid = "";
                foreach($data_id as $val_id){
                    $valid .= $val_id['id'].",";
                }
                $valid = rtrim($valid, ",");
                $wheres[]="a.data_40  in (".$valid.")";
            }else{
                $wheres[]="g.name like '%".I('get.name')."%'";
            }

        }
        if(!empty(I('get.pl_name')))$wheres[]="a.data_21 like '%".I('get.pl_name')."%'";
        if(!empty(I('get.data_1')))$wheres[]="a.data_1 like '%".I('get.data_1')."%'";
        if(!empty(I('get.pay_date')))$wheres[]="a.data_19 = '".I('get.pay_date')."'";
        if(!empty(I('get.status')) && I('get.status') ==2)$wheres[]="a.data_30 = '已支付'";
        elseif(I('get.status') ==1) $wheres[]="a.data_30 = ''";
        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';

        //已支付的预付款
        $data = $this->field('sum(a.data_22) as MoneySum,a.id,a.run_id,g.name,a.data_22,a.data_21,a.data_1,a.data_3,a.data_19,data_30,data_40,data_11')->join('a join boss_supplier g on a.data_40=g.id')->where($where)->group('a.data_21,a.data_40')->order('a.id desc')->select();

        foreach($data as $key=>$val){
            if($val['run_id']<= 47404){

                $ids = rtrim($val['data_40'], ",");
                //echo $ids;exit;
                $a_data = M()->query("SELECT NAME from boss_a_supplier WHERE ID =".$ids." ");
                $a_name = "";
                //print_r($a_data);exit;
                foreach($a_data as $a_val){
                    $a_name = $a_val['name'];
                }
                //echo $a_name;exit;
                $data[$key]['name'] =$a_name;
            }
        }

        return $data;
    }

}