<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/1
 * Time: 16:34
 */
namespace Home\Controller;
use Common\Controller\BaseController;
/*
 *风控中心
 * 账户白名单
 * Class WhiteListController
 * @package Home\Controller
 */
class WhiteListController extends BaseController {

    public function index(){

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->display();
    }

    public function getList($where) {

        $ml = M('white_list');


        /*//将认款、OA信息添加到白名单表中 start 1.认款信息
        set_time_limit(300);
        $advertiser = M('pay')->field('paymentname,adddate,money,remarks2,opening_bank,bank_no')->where("paymentname <>''")->select();
        foreach($advertiser as $key=>$val){
            if($val['paymentname']){
                $white_list = $ml->field('opening_bank')->where("name='".$val['paymentname']."' && opening_bank='".$val['receivablesname']."' && money='".$val['money']."'")->find();
                if(empty($white_list)){//为空则添加
                    $add = array();
                    $add['name'] = $val['paymentname'];
                    $add['opening_bank'] = $val['opening_bank'];
                    $add['bank_no'] = $val['bank_no'];
                    $add['date'] = $val['adddate'];
                    $add['addtime'] = date('Y-m-d H:i:s',time());
                    $add['money'] = $val['money'];
                    $add['remark'] = $val['remarks2'];
                    $add['type'] = 1;
                    $ml->add($add);
                }
            }

        }
        //OA信息
        $sup = M('flow_data_432')->field('data_9,data_16,data_17,pay_date,data_22,run_id,data_202')->where("data_30='已支付'")->select();
        foreach($sup as $key=>$val){
            $white_list = $ml->field('opening_bank')->where("name='".$val['data_9']."' && opening_bank='".$val['data_16']."' && bank_no='".$val['data_17']."' && money='".$val['data_22']."'")->find();
            if(empty($white_list)){//为空则添加
                $add = array();
                $add['name'] = $val['data_9'];
                $add['opening_bank'] = $val['data_16'];
                $add['bank_no'] = $val['data_17'];
                $add['date'] = $val['pay_date'];
                $add['addtime'] = date('Y-m-d H:i:s',time());
                $add['money'] = $val['data_22'];
                $add['remark'] =$val['data_202'];//摘要
                $add['oa'] =$val['run_id'];//OA号
                $add['type'] = 2;
                $ml->add($add);
            }
        }
        //将认款、OA信息添加到白名单表中 end*/

        //初始化列表
        $where = '1=1';
        $id = I('get.id');
        if($id){
            $where .= " and id=".$id;
        }
        $type = I('get.type');
        if($type){
            $where .= " and type=".$type;
        }
        $name = I('get.name');
        if($name){
            $where .= " and name like '%".$name."%'";
        }
        $opening_bank = I('get.opening_bank');
        if($opening_bank){
            $where .= " and opening_bank like '%".$opening_bank."%'";
        }
        $bank_no = I('get.bank_no');
        if($bank_no){
            $where .= " and bank_no='".$bank_no."'";
        }
        $sdate = I('get.sdate');
        if($sdate){
            $where .= " and date like '%".$sdate."%'";
        }/*else{
            $where .= " and date like '%".date('Y-m')."%'";
        }*/

        /*当月异常收支账户数量、金额*/
        $ycData = $ml->field('count(id) as count_id,sum(money) as money')->where("status=0 and ".$where)->find();
        $this->assign('ycData',$ycData);

        /*当月相同支出金额笔数*/
        $out_m = 0;
        $out_oa = $ml->field('money,COUNT(id) as oa_count,id,group_concat(id) as wid')->where("type=2 and ".$where)->group('money')->having('COUNT(id)>1')->select();
        $wid = "";
        foreach($out_oa as $key=>$val){
            $out_m++;
            $wid .= $val['wid'].",";
        }
        $wid = rtrim($wid,",");
        $this->assign('wid',$wid);
        $this->assign('out_m',$out_m);

        /*统计收支账户总数*/
        $in_count = $out_count = 0;
        $countS = $ml->field('count(id) as count_id,type')->where()->group('name,type')->select();//$where
        $a = 0;$b = 0;
        foreach($countS as $key=>$val){
            if($val['type'] == 1){
                $a++;
                $in_count = $val['count_id'];
            }elseif($val['type'] == 2){
                $b++;
                $out_count = $val['count_id'];
            }
        }
        $this->assign('in_count',$a);
        $this->assign('out_count',$b);

        $white_list = $ml->field('name,opening_bank,bank_no,type')->group('name')->order('id desc')->where($where)->page($_GET['p'], C('LIST_ROWS'))->select();

        foreach($white_list as $key=>$val){
            if($val['type'] == 1){
                $white_list[$key]['type'] = '广告主';
            }elseif($val['type'] == 2){
                $white_list[$key]['type'] = '供应商';
            }elseif($val['type'] == 3){
                $white_list[$key]['type'] = '费用';
            }
        }
        $subQuery = $ml->field('name')->group('name')->where($where)->buildSql();
        $this->totalPage = $ml->table($subQuery.' aa')->where()->count();
        //$Row = $ml->where($where)->count();
        //$this->totalPage =$Row;
        return $white_list;
    }

    /*财务导入付款方信息*/
    /*public function export_a(){
        $ml = M('white_list');

        if(empty($_FILES['file']['tmp_name'])){
            $this->assign('data','请选择上传文件');
            $this->display('Public/alertpage');
            return;
        }
        $info=$this->uplaodfile('file',UPLOAD_ORTHER_EXCEL_PATH);
        if(!is_array($info)){
            $this->assign('data','上传文件失败');
            $this->display('Public/alertpage');
            return;
        }
        $file_name=UPLOAD_ORTHER_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        foreach ($data as $key => $value) {
            if($value['客户名称'] && $value['客户账户'] && $value['客户账号']){
                $white_list = $ml->field('opening_bank')->where("name='".$value['客户名称']."' && opening_bank='".$value['客户账户']."' && bank_no='".$value['客户账号']."'")->find();
                if(empty($white_list)){//为空则添加
                    $add = array();
                    $add['name'] = $value['客户名称'];
                    $add['opening_bank'] = $value['客户账户'];
                    $add['bank_no'] = $value['客户账号'];
                    if($value['类别'] == '广告主'){
                        $type = 1;
                    }elseif($value['类别'] == '供应商'){
                        $type = 2;
                    }elseif($value['类别'] == '费用'){
                        $type = 3;
                    }
                    $add['type'] = $type;
                    $ml->add($add);
                    $msg = $ml->getError();
                }
            }
        }
        if(empty($msg)){
            $msg = '导入成功';
        }
        $this->assign('data',$msg);
        $this->display('Public/alertpage');
        return;
    }*/


    /*导出*/
    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $list = $this->lists($this, $where);
        foreach($list as $key=>$val){
            $list[$key]['bank_no'] = "\t".$val['bank_no'];
        }
        $title = array('name' => '客户名称', 'opening_bank' => '客户账户', 'bank_no' => '客户账号', 'type' => '类别');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '账户白名单'.date('Y-m-d-H:i:s',time()));
    }
}