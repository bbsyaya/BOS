<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/24
 * Time: 9:29
 */
namespace Cron\Controller;
use Common\Controller\BaseController;

class CostTaskController extends BaseController
{

    //成本支付定时任务
    public function index()
    {

        //查询流程表更新成本对账单数据状态
        $contModel = M('flow_data_432');
        $data = $contModel->field('id,data_150,data_160,data_22,data_6')->where(" data_11='结算款' AND data_30='已支付' and change_status=0")->order('id desc')->limit(10)->select();
        M()->startTrans();//开始事务
        foreach($data as $val){
            $bill_id = $val['data_150'];//结算单id data_150或data_160
            $bill_id = rtrim($bill_id, ",");
            $bill_money = $val['data_22'];//金额
            //1.修改成本结算单的状态 (假如流程里面存的还是结算单id)
            $outModel = M('settlement_out');

            $map = array();
            $map['id']= array('in',$bill_id);
            $map['status'] = 4;//已结算
            if( $outModel->save($map) === false){
                M()->rollback();//事务回滚
                $this->ajaxReturn($outModel->getError());
            }else{
                $change = array();
                $change['id'] = $val['id'];
                $change['change_status'] = 1;
                $contModel->save($change);
                //同步成本结算单状态给其他平台(接口) start
                $bill_ids = explode(",",$bill_id);
                foreach($bill_ids as $val){//获取结算单id
                    if(!empty($val)) {
                        $outData = $outModel->field("id,superid,sangwuid,lineid,alljfid,strdate,enddate")->where("id=" . $val . " ")->find();
                        //成本结算单参数
                        $sid = $outData['id'];
                        $superid = $outData['superid'];
                        $businessid = $outData['sangwuid'];
                        $sbid = $outData['lineid'];
                        $strdate = $outData['strdate'];
                        $enddate = $outData['enddate'];
                        if ($superid && $businessid && $sbid && $strdate && $enddate) {
                            $alldataid = $this->editdataforcom($superid, $businessid, $sbid, $strdate, $enddate);
                            $res = postDatatoorther($alldataid,4,$sid);
                            if(!$res){
                                M()->rollback();//事务回滚
                            }
                        }
                    }
                }

                //end

                //$this->ajaxReturn("TRUE");
                //在成本结算单中查询出计费标识id字符串和日期
                $out_data = $outModel->field('superid,sangwuid,lineid,strdate,enddate,alljfid')->where("FIND_IN_SET(id,'".$bill_id."') ")->select();
                foreach($out_data as $od){

                    $alldataid=$this->editdataforcom($od['superid'],$od['sangwuid'],$od['lineid'],$od['strdate'],$od['enddate'],$od['alljfid']);
                    foreach ($alldataid as $key => $value) {
                        $id_arr[]=$value['id'];
                    }
                    $id_str=implode(',',$id_arr);

                    //2.修改成本数据的状态
                    if(M('daydata_out')->where("id in ($id_str)")->save(array('status'=>4)) === false){
                        M()->rollback();//事务回滚
                    }
                    if(M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>4)) === false){
                        M()->rollback();//事务回滚
                    }

                }
            }
        }
        M()->commit();//事务确认

        //预付款核销
        $contModel = M('flow_data_433');
        $yfData = $contModel->field('id,data_150,data_48')->where("data_30='已备案' and change_status=0 ")->order('id desc')->select();
        foreach($yfData as $val){
            $outModel = M('settlement_out');
            //修改结算单的状态
            $map = array();
            $map['id']= $val['data_150'];
            $map['status'] = 4;//已结算
            if( $outModel->save($map) === false){

                $this->ajaxReturn($outModel->getError());
            }else{
                $change = array();
                $change['id'] = $val['id'];
                $change['change_status'] = 1;
                $contModel->save($change);
            }

            //修改成本数据状态
            $out_data = $outModel->field('superid,sangwuid,lineid,strdate,enddate,alljfid')->where("id =".$val['data_150']." ")->find();
            $alldataid=$this->editdataforcom($out_data['superid'],$out_data['sangwuid'],$out_data['lineid'],$out_data['strdate'],$out_data['enddate'],$out_data['alljfid']);
            foreach ($alldataid as $key => $value) {
                $id_arr[]=$value['id'];
            }
            $id_str=implode(',',$id_arr);

            //2.修改成本数据的状态
            M('daydata_out')->where("id in ($id_str)")->save(array('status'=>4));
            M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>4));
        }

    }

    public function editdataforcom($supid,$sangwuid,$lineid,$strtime,$endtime,$alljfid=''){
        if($alljfid)$where=" && jfid in ($alljfid)";
        else $where='';
        return M('daydata_out')->field('id,jfid,adddate,lineid')->where("status!=9 && superid in ($supid) && businessid in ($sangwuid) && lineid=$lineid && adddate>='$strtime' && adddate<='$endtime'".$where)->select();
    }
}