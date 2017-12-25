<?php
namespace Home\Controller;
use Common\Controller\BaseController;
class ExpandedScaleController extends BaseController {
    protected $totalPage = 0;

    public function index(){

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('data', $list);

        $linelist=M('business_line')->field('id,name')->where('status=1')->select();
        $this->assign('linelist',$linelist);

        $userlist2=M('user')->field('a.id,a.real_name')->join('a join boss_user_department b on a.dept_id=b.id join boss_auth_group_access c on a.id=c.uid join boss_auth_group d on c.group_id=d.id')->where('d.id IN (4,5)')->group('a.id')->select();
        $this->assign('userlist2',$userlist2);
        $this->display();
    }

    public function getList(){
        //已拓展规模列表2017.01.19
        $adv_name = I('get.adv_name');
        $pr_name = I('get.pr_name','');
        $cooperate_state = I('get.cooperate_state','');
        $dengji = I('get.dengji','');
        $sale_id = I('get.sale_id','');
        $linelist = I('get.linelist','');
        $source_type = I('get.source_type','');


        $first_t = date('Y-m-01',strtotime("-1 month"));
        $end_t = date('Y-m-t',strtotime("-1 month"));
        $where = "inda.adddate>='".$first_t."' AND inda.adddate<='".$end_t."'";
        //改成字符串，原生sql
        if($_GET['id']){
            $where .= " and id=".$_GET['id']." ";
        }
        if (!empty($adv_name)) {
            $where .= " and adv.name like '%".$adv_name."%' ";
        }
        if ($pr_name) {
            $where .= " and pro.name like '%".$pr_name."%' ";
        }
        if ($cooperate_state) {
            $where .= " and pro.cooperate_state=".$cooperate_state." ";
        }
        /*if ($dengji) {
            if($dengji == 'S'){
                $where .= " and newmoney >=500000 ";
            }elseif($dengji == 'A'){
                $where .= " and newmoney >=100000 and newmoney <500000 ";
            }elseif($dengji == 'B'){
                $where .= " and newmoney >=50000 and newmoney <100000 ";
            }elseif($dengji == 'C'){
                $where .= " and newmoney >=10000 and newmoney <50000 ";
            }elseif($dengji == 'D'){
                $where .= " and newmoney <10000 ";
            }

        }*/
        if ($sale_id) {
            $where .= " and pro.saler_id=$sale_id ";
        }

        $isRead = getCurrentUserIsOnlyReadMyselfData($_SESSION["userinfo"]["uid"],$_SESSION["userinfo"]["realname"]);
        if($isRead){
            $spidStr = $_SESSION["userinfo"]["uid"];
            $where .= " and pro.saler_id={$spidStr} ";
        }


        // print_r($where);exit;
        if ($linelist) {
            $where .= " and pro.bl_id=$linelist ";
        }

        if ($source_type) {
            $where .= " and pro.source_type=$source_type ";
        }

        $inData = M('daydata')->field('inda.comid,ROUND(SUM(inda.newmoney),2) AS newmoney,pro.`name` AS pr_name,pro.cooperate_state,pro.charging_mode,pro.price,bu.real_name,adv.address,adv.`name` AS adv_name,bbl.`name` AS bl_name,pro.source_type')->join('inda
JOIN boss_product pro ON pro.id=inda.comid
JOIN boss_user bu ON bu.id=pro.saler_id
JOIN boss_advertiser adv ON adv.id=pro.ad_id
JOIN boss_business_line bbl ON bbl.id=pro.bl_id')->where($where)->group('inda.comid DESC')->page($_GET['p'], C('LIST_ROWS'))->select();
        //echo  M('daydata')->getLastSql();exit;
        foreach($inData as $key=>$val){
            if($val['comid']){
                $dockData = M('product_contacts')->field('name,mobile')->where('pro_id='.$val['comid'].'')->find();
                $inData[$key]['dock_name'] = $dockData['name'];
                $inData[$key]['dock_mobile'] = $dockData['mobile'];
            }
            if($val['cooperate_state'] == 1){
                $inData[$key]['cooperate_state'] = '正式上量';
            }elseif($val['cooperate_state'] == 2){
                $inData[$key]['cooperate_state'] = '测试';
            }elseif($val['cooperate_state'] == 3){
                $inData[$key]['cooperate_state'] = '停推';
            }else{
                $inData[$key]['cooperate_state'] ='无';
            }
            if($val['newmoney'] >=500000){
                $inData[$key]['dj'] ='S';
                $inData[$key]['weihu'] ='总监及以上';
            }elseif($val['newmoney'] >=100000 && $val['newmoney']<500000){
                $inData[$key]['dj'] ='A';
                $inData[$key]['weihu'] ='高级经理';
            }elseif($val['newmoney'] >=50000 && $val['newmoney']<100000){
                $inData[$key]['dj'] ='B';
                $inData[$key]['weihu'] ='主管及经理';
            }elseif($val['newmoney'] >=10000 && $val['newmoney']<50000){
                $inData[$key]['dj'] ='C';
                $inData[$key]['weihu'] ='专员';
            }elseif($val['newmoney']<10000){
                $inData[$key]['dj'] ='D';
                $inData[$key]['weihu'] ='专员';
            }
            if($val['source_type'] == 1){
                $inData[$key]['source_type'] ='官方';
            }elseif($val['source_type'] == 2){
                $inData[$key]['source_type'] ='代理';
            }
            if($val['charging_mode'] == 1){
                $inData[$key]['charging_mode'] ='CPA';
            }elseif($val['charging_mode'] == 2){
                $inData[$key]['charging_mode'] ='CPC';
            }elseif($val['charging_mode'] == 3){
                $inData[$key]['charging_mode'] ='CPM';
            }elseif($val['charging_mode'] == 4){
                $inData[$key]['charging_mode'] ='CPS';
            }elseif($val['charging_mode'] == 5){
                $inData[$key]['charging_mode'] ='CPT';
            }elseif($val['charging_mode'] == 6){
                $inData[$key]['charging_mode'] ='CPD';
            }
        }

        $ca_ch = M('daydata')->field('inda.comid,ROUND(SUM(inda.newmoney),2) AS newmoney,pro.`name` AS pr_name,pro.cooperate_state,pro.charging_mode,pro.price,bu.real_name,adv.address,adv.`name` AS adv_name,bbl.`name` AS bl_name,pro.source_type')->join(' inda
JOIN boss_product pro ON pro.id=inda.comid
JOIN boss_user bu ON bu.id=pro.saler_id
JOIN boss_advertiser adv ON adv.id=pro.ad_id
JOIN boss_business_line bbl ON bbl.id=pro.bl_id
			')->where($where)
            ->group('inda.comid')
            ->buildSql();
        $this->totalPage = M('daydata')->table($ca_ch.' bc')->where()->count();

        return $inData;
    }

}

