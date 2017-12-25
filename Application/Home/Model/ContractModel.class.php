<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/13
 * Time: 15:19
 */
namespace Home\Model;
use Think\Model;
class ContractModel extends Model {

    public $totalPage = 0;

    protected $trueTableName =   'boss_flow_data_434';

    public function getList($where) {
        $prefix = C('DB_PREFIX')."contract_file";//C('DB_PREFIX') 获取表前缀
        // $order_ = 'data_111<=CURDATE()+INTERVAL 1 MONTH AND data_111>=NOW() DESC';
        $order_ = "id desc";
        $adList = $this->field('*')->where($where)->order($order_)->page($_GET['p'],10)->select();
        $Row    = $this->where($where)->order('id desc')->count();

        foreach($adList as $key=>$val){
           /* if($val['data_50'] ==''){
                $adList[$key]['status_s'] = '未存档';
            }elseif($val['data_50'] !=''){
                $adList[$key]['status_s'] = '流程正常完结';
            }else*/if($val['data_111'] > date('Y-m-d') && $val['data_111']<=date("Y-m-d",strtotime("+1months"))){
                $adList[$key]['status_s'] = '即将到期';
            }elseif($val['data_111']< date('Y-m-d')){
                $adList[$key]['status_s'] = '已到期';
            }else{
                $adList[$key]['status_s'] = '正常';
            }
            if(is_numeric($val['data_107']) or $val['data_107'] == 0){
                $adList[$key]['data_107'] = $val['data_1'];
            }

            if((!empty($val['data_111']) && ($val['data_111'] <= date("Y-m-d",strtotime("+1months"))) && ($val['data_111'] > date('Y-m-d'))) or ($val['data_111'] < date('Y-m-d'))){// 即将到期合同提醒：提醒责任人
                if (preg_match("/[\x7f-\xff]/", $val['data_111'])) {
                    $user_name = rtrim($val['data_107'], ",");//是汉字名字

                }else{
                    $user_name = rtrim($val['data_1'], ",");
                }
                if($user_name){
                    $uid = M('user')->field('id')->where("real_name = '".$user_name."'")->find();//要提醒的人
                    //判断是添加还是修改
                    $count = M('prompt_information')->where("oa_number=".$val['run_id']."")->count();
                    if($count<1 && $uid['id']){

                        $cm = M('prompt_information');
                        $map = array();
                        $map['date_time'] = date('Y-m-d H:i:s',time());
                        $map['send_user'] = $uid['id'];
                        if(($val['data_111'] <= date("Y-m-d",strtotime("+1months"))) && ($val['data_111'] > date('Y-m-d'))){
                            $map['content'] = '你的合同即将到期，OA号为'.$val['run_id'].',请处理';
                        }elseif($val['data_111'] < date('Y-m-d')){
                            $map['content'] = '你的合同已到期，OA号为'.$val['run_id'].',请处理';
                        }
                        $map['a_link'] = '/Contract/index?id='.$val['id'];
                        $map['oa_number'] = $val['run_id'];
                        if ($cm->add($map) === false) {
                            //$this->ajaxReturn($cm->getError());
                        }else{
                        }
                    }
                }
            }
            //2017.01.04 合同附件可能有多个
            $file_data = M('contract_file')->field('id as cfid,file_cont')->where("cid =".$val['id']."")->select();
            /*$cfid = "";
            $file_cont = "";
            foreach($file_data as $f_val){
                $cfid .= $f_val['cfid'].",";
                $file_cont .= $f_val['file_cont'].",";
            }
            $adList[$key]['cfid'] = rtrim($cfid,",");
            $adList[$key]['file_cont'] = rtrim($file_cont,",");*/
            $adList[$key]['con_file'] = $file_data;
        }
        $this->totalPage =$Row;

        //没有数据
        if ($this->totalPage == 0) {
            return array();
        }

        return $adList;

    }

    function  getContract(){

        //$prefix = C('DB_PREFIX')."contract_file";//C('DB_PREFIX') 获取表前缀
        $wheres[]="(data_6 <> '' or data_7 <> '') and (data_139='销售合同' or data_139='推广合同')";
        if(!empty(I('get.run_id')))$wheres[]="run_id=".I('get.run_id');
        if(!empty(I('get.contract_category')))$wheres[]="data_8='".I('get.contract_category')."'";
        if(!empty(I('get.contract_type')))$wheres[]="data_112='".I('get.contract_type')."'";
        if(!empty(I('get.contract_nature')))$wheres[]="data_139='".I('get.contract_nature')."'";
        if(!empty(I('get.aciName')))$wheres[]="data_6 like '%".I('get.aciName')."%'";
        if(!empty(I('get.deptName')))$wheres[]="data_2 like '%".I('get.deptName')."%'";

        if(I('get.status') == '未存档')$wheres[]="data_50 = ''";
        elseif(I('get.status') == '流程正常完结')$wheres[]="data_50 <> ''";
        elseif(I('get.status') == '即将到期')$wheres[]="data_111 > '".date('Y-m-d')."' and data_111<='".date("Y-m-d",strtotime("+1months"))."'";
        elseif(I('get.status') == '已到期')$wheres[]="data_111< '".date('Y-m-d')."'";

        if(!empty(I('get.validity_period')))$wheres[]="data_110 <='".I('get.validity_period')."'";
        if(!empty(I('get.user_name')))$wheres[]="data_107 like '%".I('get.user_name')."%'";

        if(!empty(I('get.approval_status')) && I('get.approval_status')==1)$wheres[]="data_50 <> '' and data_50 !='流程作废'";
        elseif(!empty(I('get.approval_status')) && I('get.approval_status')==2)$wheres[]="data_50 = ''";
        elseif(!empty(I('get.approval_status')) && I('get.approval_status')==3)$wheres[]="data_50='流程作废'";
        if(!empty(I('get.sb_name')))$wheres[]="data_7=".I('get.sb_name');
        if(count($wheres)>0)$where=implode(' && ', $wheres);
        else $where='';

        $getList = $this->field('id,run_id,run_name,data_1,data_7,data_2,data_8,data_112,data_139,data_106,data_110,data_111,data_6,data_107,status,data_50,data_612')->where($where)->order('id desc')->select();

        foreach($getList as $key=>$val){
            if(is_numeric($val['data_107']) or $val['data_107'] == 0){
                $getList[$key]['data_107'] = $val['data_1'];
            }

            //2017.01.04 合同附件可能有多个，目前显示的是一个
            $file_data = M('contract_file')->field('id as cfid,file_cont')->where("cid =".$val['id']."")->select();
            $cfid = "";
            $file_cont = "";
            foreach($file_data as $f_val){
                $cfid = $f_val['cfid'];
                $file_cont = $f_val['file_cont'];
            }
            $adList[$key]['cfid'] = $cfid;
            $adList[$key]['file_cont'] = $file_cont;
        }
        return $getList;
    }


}