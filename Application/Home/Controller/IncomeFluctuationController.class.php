<?php
/** 收入波动需求
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-07-17
 * Time: 9:14
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class IncomeFluctuationController extends BaseController {

    public function index(){

        $this->linelist=M('business_line')->field('name,id')->where('status=1')->select();
        $this->adverlist=M('advertiser')->field('name,id')->where("ad_type>0 and status=1")->select();
        $this->comlist=M('product')->field('name,id')->where('status=1')->select();
        $this->userlist=M('user')->field('a.real_name,a.id')->join('a join boss_auth_group_access b on a.id=b.uid and b.group_id=4')->where('status=1')->select();

        $where = array();
        $list = $this->lists($this, $where);
        if($list){
            $this->assign('list',$list);
        }

        $this->display();
    }

    public function getList($where) {
        $ml = M('daydata');

        $days = I('get.days');
        if($days){
            $start_time = date('Y-m-d',strtotime(-$days.'day'));
            $end_time = date('Y-m-d');
        }else{
            $start_time = date('Y-m-d',strtotime((-60).'day'));//默认60天
            $end_time = date('Y-m-d');
        }
        //查询金额为0的广告主id
        $prData = $ml->field('adverid')->where("adddate>='".$start_time."' AND adddate<='".$end_time."'")->group('adverid')->having('SUM(newmoney)=0')->select();

        $adverid = '';
        foreach($prData as $val_ad){
            $adverid .= $val_ad['adverid'].",";
        }
        $adverid = rtrim($adverid,",");

        if($adverid){

            $gross_profit = I('get.gross_profit');
            if($gross_profit){

                $having = 'sum(a.newmoney) >='.$gross_profit;
            }else{
                $having = 'sum(a.newmoney) >=300000';//金额默认30万
            }

            if(!empty(I('get.lineid'))){
                $w=array();
                foreach (I('get.lineid') as $key => $value) {
                    $w[]="a.lineid=".$value;
                }
                $where[]="(".implode(' || ',$w).")";
            }
            if(!empty(I('get.adverid'))){
                $w=array();
                foreach (I('get.adverid') as $key => $value) {
                    $w[]="a.adverid=".$value;
                }
                $where[]="(".implode(' || ',$w).")";
            }
            if(!empty(I('get.comid'))){
                $w=array();
                foreach (I('get.comid') as $key => $value) {
                    $w[]="a.comid=".$value;
                }
                $where[]="(".implode(' || ',$w).")";
            }
            if(!empty(I('get.salerid'))){
                $w=array();
                foreach (I('get.salerid') as $key => $value) {
                    $w[]="a.salerid=".$value;
                }
                $where[]="(".implode(' || ',$w).")";
            }

            if(count($where)>0)$where=implode(' && ', $where);

            //数据权限
            $arr_name=array();
            $arr_name['line']=array('a.lineid');
            $arr_name['user']=array('a.salerid');
            $ruleser=new Service\RuleService();
            $myrule_data=$ruleser->getmyrule_data($arr_name);
            $where[]= $myrule_data;


            //列求和
            /*求和*/
            $sumData = $ml->field('SUM(a.newmoney) as inmoney,SUM(f.newmoney) as outmoney,(SUM(a.newmoney)-SUM(f.newmoney)) as mlr')->join('a
                    LEFT JOIN boss_advertiser b ON a.adverid=b.id
                    LEFT JOIN boss_product c on c.id=a.comid
                    LEFT JOIN boss_business_line d ON d.id=a.lineid
                    LEFT JOIN boss_user e ON e.id=a.salerid
                    LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
                ->where($where)->find();
            $this->assign('sumData', $sumData);

            $white_list = $ml->field('d.`name` as bl_name,b.`name` as adv_name,c.`name` as pro_name,e.real_name,SUM(a.newmoney) as inmoney,SUM(f.newmoney) as outmoney,(SUM(a.newmoney)-SUM(f.newmoney)) as mlr,convert( (SUM(a.newmoney)-SUM(f.newmoney))/SUM(a.newmoney) ,decimal(10,2)) as mll')->join('a
                    LEFT JOIN boss_advertiser b ON a.adverid=b.id
                    LEFT JOIN boss_product c on c.id=a.comid
                    LEFT JOIN boss_business_line d ON d.id=a.lineid
                    LEFT JOIN boss_user e ON e.id=a.salerid
                    LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
                ->group('a.comid')->where($where)->page($_GET['p'], C('LIST_ROWS'))->having($having)->select();

             foreach($white_list as $key=>$val){
                 $white_list[$key]['mll'] = ($val['mll']*100).'%';
                 if(empty($val['outmoney'])){
                     $white_list[$key]['mlr'] =$val['inmoney'];
                     $white_list[$key]['mll'] ='100%';
                 }
             }

            //总数
            $subQuery =$ml->field('a.id')->join('a
                    LEFT JOIN boss_advertiser b ON a.adverid=b.id
                    LEFT JOIN boss_product c on c.id=a.comid
                    LEFT JOIN boss_business_line d ON d.id=a.lineid
                    LEFT JOIN boss_user e ON e.id=a.salerid
                    LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
                ->group('a.comid')->where($where)->buildSql();
            $this->totalPage = $ml->table($subQuery.' aa')->where()->count();

            return $white_list;
        }
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        $title = array('bl_name' => '业务线', 'adv_name' => '广告主', 'pro_name' => '产品', 'inmoney' => '收入', 'outmoney' => '成本', 'mlr' => '毛利额', 'mll' => '毛利润', 'real_name' => '销售');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '收入波动'.date('Y-m-d-H:i:s',time()));
    }
}