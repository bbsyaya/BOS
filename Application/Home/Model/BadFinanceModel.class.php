<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/20
 * Time: 15:55
 */
namespace Home\Model;
use Think\Model;
use Common\Service;
class BadFinanceModel extends Model {

    protected $trueTableName =   'boss_flow_data_423';
    public $totalPage = 0;
    public function getlist(){
        $wheres[]="1=1";
        if(!empty(I('get.name')))$wheres[]="data_6 like '%".I('get.name')."%'";
        if(!empty(I('get.pl_name')))$wheres[]="data_21 like '%".I('get.pl_name')."%'";
        if(!empty(I('get.rec_p')))$wheres[]="data_1 like '%".I('get.rec_p')."%'";
        if(!empty(I('get.arrival_date')))$wheres[]="data_10 = '".I('get.arrival_date')."'";
        if( I('get.status') == '已审核')$wheres[]="data_50 = '已办理'";
        else $wheres[]="data_50 = ''";

        //数据权限
        $arr_name=array();
        $arr_name['user']=array('data_1');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name,0);
        $wheres[]= $myrule_data;


        if(count($wheres)>0)$where=implode(' and ', $wheres);
        else $where='';

        //坏账
        $data = $this->field('run_id,data_6,data_38,data_40,data_1,data_151,data_3,data_21,data_50')->where($where)->order('id desc')->page($_GET['p'],10)->select();
        //echo $this->getLastSql();exit;
        //echo $this->field('*')->where($where)->order('id desc')->page($_GET['p'],10)->getLastsql();exit;
        $Row = $this->field('*')->where($where)->count();

        $this->totalPage =$Row;

        return $data;
    }

    function getBadList(){
        $wheres[]="1=1";
        if(!empty(I('get.name')))$wheres[]="data_6 like '%".I('get.name')."%'";
        if(!empty(I('get.pl_name')))$wheres[]="data_21 like '%".I('get.pl_name')."%'";
        if(!empty(I('get.rec_p')))$wheres[]="data_1 like '%".I('get.rec_p')."%'";
        if(!empty(I('get.arrival_date')))$wheres[]="data_10 = '".I('get.arrival_date')."'";
        if(!empty(I('get.status')) && I('get.status')=='已审核')$wheres[]="data_50 = '已办理'";
        elseif(!empty(I('get.status')) && I('get.status')=='未审核')$wheres[]="data_50 = ''";
        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';

        //坏账
        $data = $this->field('run_id,data_6,data_38,data_40,data_1,data_151,data_3,data_21,data_50')->where($where)->order('id desc')->select();
        return $data;
    }

}