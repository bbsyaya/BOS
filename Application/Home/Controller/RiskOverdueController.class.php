<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/6/12
 * Time: 16:03
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 逾期数据(风控新需求2017.06.12)
 * Class RiskOverdueProductController
 * @package Home\Controller
 */
class RiskOverdueController extends BaseController {
    public function index(){
        $this->display();
    }

    public function AjaxRisk(){
        $lineid = I('get.lineid');
        $time_s = I('get.time_s');
        $time_e = I('get.time_e');
        $dayModel = M('daydata');
        $where = "1=1";

        if($lineid){
            $where .=" and a.lineid=".$lineid;
        }
        if($time_s){
            $where .=" and DATE_FORMAT(a.adddate,'%Y-%m')>='".$time_s."' and DATE_FORMAT(a.adddate,'%Y-%m')<='".$time_e."'";
        }else{
            $where .= " and DATE_FORMAT(a.adddate,'%Y-%m')>='".date('Y-01')."' and DATE_FORMAT(a.adddate,'%Y-%m')<='".date('Y-m')."'";
        }
        //数据权限
        $arr_name=array();
        $arr_name['line']=array('a.lineid');
        $arr_name['user']=array('a.salerid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " and $myrule_data";

        /*汇总*/
        $sumData = $dayModel->field("sum(if(a.status=1,a.newmoney,0)) as newmoney,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(b.status=1,b.newmoney,0)) as out_money,sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money")->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate')->where($where)->select();

        /*明细*/
        $resData = $dayModel->field("DATE_FORMAT(a.adddate,'%m') as month,DATE_FORMAT(a.adddate,'%Y-%m') as year_y,sum(if(a.status=1,a.newmoney,0)) as newmoney,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(b.status=1,b.newmoney,0)) as out_money,sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money")->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate')->where($where)->group("DATE_FORMAT(a.adddate,'%Y-%m')")->order("DATE_FORMAT(a.adddate,'%Y-%m') desc")->select();
        // echo $dayModel->getLastSql();exit;
        $this->ajaxReturn(array('sumData'=>$sumData,'resData'=>$resData));exit;

    }

    /*每个月明细*/
    public function detail(){
        $mon     = I('get.mon');
        $wqr_sr  = I('get.wqr_sr');
        $wqr_cb  = I('get.wqr_cb');
        $whk_sr  = I('get.whk_sr');
        $whk_cb  = I('get.whk_cb');
        //判断是否是销售，
        $role_type = $this->getUserType();
        //$sumS    = array('a'=>$wqr_sr,'b'=>$wqr_cb,'c'=>$whk_sr,'d' =>$whk_cb);
        $resData = $this->sel_detail($mon,$role_type);
        //var_dump($resData);exit;
        $this->assign('op_settlement_cycle', C('OPTION.settlement_cycle'));
        $this->assign('list',$resData);
        $this->assign('mon',$mon);
        //$this->assign('sumS',$sumS);
        $this->assign('role_type',$role_type);

        $this->linelist=M('business_line')->field('name,id')->select();
        $this->comlist=M('product')->field('name,id')->select();
        $this->zt=M('data_dic')->field('name,id')->where("dic_type=4")->select();
        $this->adverlist=M('advertiser')->field('name,id')->where("ad_type>0")->select();
        $this->superlist=M('supplier')->field('name,id')->select();
        $this->userlist=M('user')->field('real_name,id')->select();
        $this->jflist=M('charging_logo')->field('name,id')->where("status=1")->select();

        $this->display();
    }

    /**
     * 填写逾期未确认成本原因
     * @return [type] [description]
     */
    function fillinReason(){
        $data["reason_yq"] = trim(I("reason_yq"));
        $where["id"]    = trim(I("out_id"));
        $row = M("daydata_out")->where($where)->save($data);
        $this->ajaxReturn();
    }

     /**
     * 填写逾期未确认收入原因
     * @return [type] [description]
     */
    function fillinShouruReason(){
        $data["reason_yq"] = trim(I("reason_yq"));
        $where["id"]    = trim(I("out_id"));
        $row = M("daydata")->where($where)->save($data);
        $this->ajaxReturn();
    }

    /**
     *
     * @return [type] [description]
     */
    private function getUserType(){
        $duty_id   = $_SESSION["userinfo"]["duty_id"];
        $realname  = $_SESSION["userinfo"]["realname"];
        $role_type = 0;
        // $duty_id = "营销专员";
        //刘霞=-数据主管，黄榜杰-运营主管,蔡静-运营专员 这三个单独开，只能看到自己的逾期数据
        //商务和销售只能看到自己的逾期数据
        if($_REQUEST["showsql"]=="showsql023"){
            print_r("duty_id==".$duty_id);
            print_r("<br>");
            print_r("realname==".$realname);
            print_r("<br>");
        }
        $sql = "SELECT `id` FROM `boss_oa_position` WHERE ( NAME LIKE '%销%' OR NAME LIKE '%商务%' )";
        $model = new \Think\Model();
        $list = $model->query($sql);
        $posi_ids = "";
        foreach($list as $k=>$v){
            $posi_ids .= $v["id"].",";
        }
        $posi_ids =explode(",",$posi_ids);
        if($_REQUEST["showsql"]=="showsql023"){
            print_r($posi_ids);
            print_r("<br>");
        }
        if(in_array($duty_id, $posi_ids)){
            $role_type = 3;
        }
        if($realname=="刘霞" || $realname=="黄榜杰" || $realname=="蔡静"){
            $role_type = 3;
        }

        return $role_type;
    }

    /**
     * [sel_detail description]
     * @param  [type] $mon      [description]
     * @param  string $is_saler [description]
     * @return [type]           [description]
     */
    public function sel_detail($mon,$is_saler=0){

        $dayModel = M('daydata');
        $where = "(a.newmoney>0 or b.newmoney>0) and (a.status in (1,2,3,4) or b.status in (1,2,3) ) and b.id>0";

        if($mon){

            /*根据条件查产品ID*/
            $prData = $dayModel
                ->field('a.comid,a.adddate,b.settle_cycle')
                ->join('a left join boss_product b on a.comid=b.id
                        left join boss_daydata_out c on c.jfid=a.jfid and c.adddate=a.adddate')
                ->where("DATE_FORMAT(a.adddate,'%Y-%m') = '".$mon."' and (a.newmoney>0 or c.newmoney>0) and (a.status in (1,2,3,4) or c.status in (1,2,3))")
                ->group('a.comid')
                ->select();

            // print_r($dayModel->getLastSql());exit;
            $prId = '';
            foreach($prData as $key=>$val){
                if($val['settle_cycle'] == 1){//周
                    if( strtotime("+1week",strtotime($val['adddate']))< strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }elseif($val['settle_cycle'] == 2){//半月
                    if( strtotime($val['adddate'].' +15 day') < strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }elseif($val['settle_cycle'] == 3){//月
                    if( strtotime("+1months",strtotime($val['adddate']))< strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }elseif($val['settle_cycle'] == 4){//季度
                    if( strtotime("+3months",strtotime($val['adddate']))< strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }elseif($val['settle_cycle'] == 6){//季度
                    if( strtotime("+2months",strtotime($val['adddate']))< strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }elseif($val['settle_cycle'] == 7){//季度
                    if( strtotime($val['adddate'].' +1 day') < strtotime(date("Y-m-d")) ){
                        $prId .= $val['comid'].",";
                    }
                }
            }
            $prId = rtrim($prId,',');
            if($prId) {
                $where .= " and a.comid in (" . $prId . ") and DATE_FORMAT(a.adddate,'%Y-%m') = '" . $mon . "'";
                if($_REQUEST["showsql"]=="showsql023"){
                    print_r("<br>");
                    print_r($is_saler);
                    print_r("<br>");
                }
                //当前是销售、商务，市场db
                if($is_saler==3){
                    $where .= " and a.salerid=".UID;
                }

                if(!empty(I('get.lineid'))){
                    $w=array();
                    foreach (I('get.lineid') as $key => $value) {
                        $w[]="a.lineid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }
                if(!empty(I('get.adverid'))){
                    $w=array();
                    foreach (I('get.adverid') as $key => $value) {
                        $w[]="a.adverid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }
                if(!empty(I('get.superid'))){
                    $w=array();
                    foreach (I('get.superid') as $key => $value) {
                        $w[]="b.superid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }
                if(!empty(I('get.comid'))){
                    $w=array();
                    foreach (I('get.comid') as $key => $value) {
                        $w[]="a.comid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }

                if(!empty(I('get.salerid'))){
                    $w=array();
                    foreach (I('get.salerid') as $key => $value) {
                        $w[]="a.salerid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }
                if(!empty(I('get.supid'))){
                    $w=array();
                    foreach (I('get.supid') as $key => $value) {
                        $w[]="b.businessid=".$value;
                    }
                    $wheres[]="(".implode(' || ',$w).")";
                }
                if(count($wheres)>0)$where .=' and '.implode(' && ', $wheres);

                $having = '
        ( sum(if(a.status=1,a.newmoney,0))>0 or sum(if(a.status in (1,2,3,4),a.newmoney,0))>0 or sum(if(b.status=1,b.newmoney,0)) >0 or
        sum(if(a.status in (1,2,3),a.newmoney,0)) >0)';

                //汇总
                $sumS =
                    $dayModel
                        ->field("sum(if(a.status=1,a.newmoney,0)) as newmoney,
                    sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
                    sum(if(b.status=1,b.newmoney,0)) as out_money,
                    sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money

                    ")
                        ->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate
                        left join boss_advertiser adv on adv.id=a.adverid
                        left join boss_product pro on pro.id=a.comid
                        left join boss_user sale on sale.id=a.salerid
                        left join boss_data_dic AS bd ON bd.dic_type=4 and bd.id=a.ztid
                        left join boss_supplier sup on sup.id=b.superid
                        left join boss_business_line bl on bl.id=a.lineid')
                        ->where($where)
                        ->having($having)
                        ->find();
                $this->assign('sumS',$sumS);

                $resData =
                    $dayModel
                        ->field("
                    DATE_FORMAT(a.adddate,'%Y-%m') as adddate,a.adddate as yq_date_detail,adv.name as adv_name,pro.name as pro_name,sale.real_name as sale_name,pro.settle_cycle,
                    bd.name as dic_name,sup.name as sup_name,bl.name as bl_name,
                    sum(if(a.status=1,a.newmoney,0)) as newmoney,
                    sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
                    sum(if(b.status=1,b.newmoney,0)) as out_money,
                    sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money,
                    b.reason_yq,b.id as out_id,a.`id` AS in_id,a.`reason_yq` AS reason_yq_in
                    ")
                        ->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate
                        left join boss_advertiser adv on adv.id=a.adverid
                        left join boss_product pro on pro.id=a.comid
                        left join boss_user sale on sale.id=a.salerid
                        left join boss_data_dic AS bd ON bd.dic_type=4 and bd.id=a.ztid
                        left join boss_supplier sup on sup.id=b.superid
                        left join boss_business_line bl on bl.id=a.lineid')
                        ->where($where)
                        ->group("a.comid,DATE_FORMAT(a.adddate,'%Y-%m')")
                        ->having($having)
                        ->select();//->order("a.adddate desc")

                if($_REQUEST["showsql"]=="showsql023"){
                    print_r("<br>");
                    echo $dayModel->getLastSql();exit;
                }

                /*foreach($resData as $key=>$val){
                    if($val['newmoney']>0 || $val['whk_money']>0 || $val['out_money']>0 || $val['out_whk_money']>0){
                        $resData[$key]['adddate'] = $val['adddate'];
                        $resData[$key]['adv_name'] = $val['adv_name'];
                        $resData[$key]['pro_name'] = $val['pro_name'];
                        $resData[$key]['sale_name'] = $val['sale_name'];
                        $resData[$key]['settle_cycle'] = $val['settle_cycle'];
                        $resData[$key]['settle_cycle_a'] = C('OPTION.settlement_cycle')[$val['settle_cycle']];
                        $resData[$key]['dic_name'] = $val['dic_name'];
                        $resData[$key]['sup_name'] = $val['sup_name'];
                        $resData[$key]['bl_name'] = $val['bl_name'];
                        $resData[$key]['newmoney'] = $val['newmoney'];
                        $resData[$key]['whk_money'] = $val['whk_money'];
                        $resData[$key]['out_money'] = $val['out_money'];
                        $resData[$key]['out_whk_money'] = $val['out_whk_money'];
                    }
                }*/
                return $resData;
            }
        }
    }

    public function  export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->AjaxRisk_export($where);
        $title = array('month' => '月份', 'newmoney' => '未确认收入', 'out_money' => '未确认成本', 'whk_money' => '未回款收入', 'out_whk_money' => '未付款成本');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '逾期数据'.date('Y-m-d-H:i:s',time()));
    }

    public function AjaxRisk_export(){
        $lineid = I('get.lineid');
        $time_s = I('get.time_s');
        $time_e = I('get.time_e');
        $dayModel = M('daydata');
        $where = "1=1";

        if($lineid){
            $where .=" and a.lineid=".$lineid;
        }
        if($time_s){
            $where .=" and DATE_FORMAT(a.adddate,'%Y-%m')>='".$time_s."' and DATE_FORMAT(a.adddate,'%Y-%m')<='".$time_e."'";
        }else{
            $where .= " and DATE_FORMAT(a.adddate,'%Y-%m')>='".date('Y-01')."' and DATE_FORMAT(a.adddate,'%Y-%m')<='".date('Y-m')."'";
        }
        /*汇总*/
        $sumData = $dayModel->field("sum(if(a.status=1,a.newmoney,0)) as newmoney,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(b.status=1,b.newmoney,0)) as out_money,sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money")->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate')->where($where)->select();

        /*明细*/
        $resData = $dayModel->field("DATE_FORMAT(a.adddate,'%m') as month,DATE_FORMAT(a.adddate,'%Y-%m') as year_y,sum(if(a.status=1,a.newmoney,0)) as newmoney,sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,sum(if(b.status=1,b.newmoney,0)) as out_money,sum(if(a.status in (1,2,3),a.newmoney,0)) as out_whk_money")->join('a left join boss_daydata_out b on a.jfid=b.jfid and a.adddate=b.adddate')->where($where)->group("DATE_FORMAT(a.adddate,'%Y-%m')")->order("DATE_FORMAT(a.adddate,'%Y-%m') desc")->select();
        //echo $dayModel->getLastSql();exit;
        return $resData;

    }

    public function export_detail(){
        $where = array();
        C('LIST_ROWS', '');
        $mon = I('get.mon');
        $resData = $this->sel_detail($mon);
        $title = array('adddate' => '年月', 'adv_name' => '广告主', 'sup_name' => '供应商', 'bl_name' => '业务线', 'pro_name' => '产品', 'dic_name' => '结算主体', 'sale_name' => '销售', 'settle_cycle_a' => '结算周期', 'newmoney' => '逾期未确认收入', 'whk_money' => '逾期未回款收入', 'out_money' => '逾期未确认成本', 'out_whk_money' => '逾期未支付成本');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($resData, $title, '逾期数据明细'.date('Y-m-d-H:i:s',time()));
    }

}