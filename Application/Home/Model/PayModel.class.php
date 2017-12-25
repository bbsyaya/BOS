<?php
namespace Home\Model;
use Think\Model;
class PayModel extends Model {
	public function adddata($data){
		$res=M('pay')->add($data);
		return $res;
	}
	public function getMoneyListData($type=0){
		$p=I('get.p');
		if($p<1)$p=1;
		$str=($p-1)*10;
		if(!empty(I('get.payername')))$wheres[]="paymentname like '%".I('get.payername')."%'";
		if(!empty(I('get.receivablesname')))$wheres[]="receivablesname like '%".I('get.receivablesname')."%'";
		if(!empty(I('get.status')))$wheres[]="status=".I('get.status');
		if(!empty(I('get.strtime')))$wheres[]='adddate >= "'.I('get.strtime').'"';
		if(!empty(I('get.endtime')))$wheres[]='adddate <= "'.I('get.endtime').'"';
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		if($type==1)$data=M('pay')->where($where)->select();
			else $data=M('pay')->where($where)->order('id desc')->limit($str.',10')->select();
		return $data;
	}
	public function getSum(){
		if(!empty(I('get.payername')))$wheres[]="paymentname like '%".I('get.payername')."%'";
		if(!empty(I('get.receivablesname')))$wheres[]="receivablesname like '%".I('get.receivablesname')."%'";
		if(!empty(I('get.status')))$wheres[]="status=".I('get.status');
		if(!empty(I('get.strtime')))$wheres[]='adddate >= "'.I('get.strtime').'"';
		if(!empty(I('get.endtime')))$wheres[]='adddate <= "'.I('get.endtime').'"';
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		$sumData=M('pay')->field('round(sum(money),2) as money,round(sum(yrkmoney),2) as yrkmoney,round(sum(wrkmoney),2) as wrkmoney')->where($where)->select();
		return $sumData;
	}
	public function getPcount(){
		if(!empty(I('get.payername')))$wheres[]="paymentname like '%".I('get.payername')."%'";
		if(!empty(I('get.receivablesname')))$wheres[]="receivablesname like '%".I('get.receivablesname')."%'";
		if(!empty(I('get.status')))$wheres[]="status=".I('get.status');
		if(!empty(I('get.strtime')))$wheres[]='adddate >= "'.I('get.strtime').'"';
		if(!empty(I('get.endtime')))$wheres[]='adddate <= "'.I('get.endtime').'"';
		if(count($wheres)>0)$where=implode(' && ', $wheres);
		else $where='';
		$data=M('pay')->where($where)->count();
		return $data;
	}
	public function getnum($where=''){
		$res=M('pay')->where($where)->count();
		return $res;
	}
	public function getonedata($where){
		$M=M('pay');
		$res=$M->where($where)->find();
		return $res;
	}
	public function edit($where='',$data=array()){
		$res=M('pay')->where($where)->save($data);
		return $res;
	}
	public function gettongbudata(){

            $sk_data = array();
            if(!empty(I('post.type'))){
                if(I('post.type')==1)$ztname='上饶网聚';
                if(I('post.type')==2)$ztname='重庆趣玩';
                if(I('post.type')==3)$ztname='上海趣比';
                $where=" && a.receivablesname like '%$ztname%'";
            }elseif(!empty(I('post.id'))){
                $where=" && a.id in (".I('post.id').")";
            }else $where="";
            $skData = M()->query("select a.id,a.adddate,a.receivablesname,money,a.paymentname,b.ad_code
from boss_pay a left join boss_advertiser b on a.paymentname=b.name where a.ischeck=0 $where group by a.id");
            /*$skData = $skModel->field('bp.adddate,bp.id,bp.receivablesname,adv.ad_code,adv.`name`,bl.id AS bl_id,bl.`name` AS bl_name,b_code.`code` AS bd_code,br.money,br.skjsdid,br.type,br.lineid')->join('AS br
LEFT JOIN boss_pay AS bp ON br.payid=bp.id
LEFT JOIN boss_settlement_in AS bs ON br.skjsdid=bs.id
LEFT JOIN boss_advertiser AS adv ON adv.id=bs.advid
LEFT JOIN boss_product AS pro ON pro.id=bs.comid
LEFT JOIN boss_business_line AS bl ON bl.id=pro.bl_id
LEFT JOIN boss_data_dic AS b_code ON b_code.dic_type=4 and b_code.id=bs.jsztid')->where(''.$where.'')->order('br.id desc')->select();
*/
            foreach($skData as $key=>$val){
                $sk_data[$key]['id'] = $val['id'];
                $sk_data[$key]['dDate'] = $val['adddate'];
                $sk_data[$key]['BankCode'] = $val['id'];
                
                $sk_data[$key]['Money'] =$val['money'];
                $sk_data[$key]['CusName']=$val['paymentname'];
                if($val['receivablesname']=='上海趣比'){
                    $sk_data[$key]['BankAccID'] = '上海趣比9710';
                    $sk_data[$key]['AccID'] = '005';
                }elseif($val['receivablesname']=='重庆趣玩'){
                    $sk_data[$key]['BankAccID'] = '重庆趣玩8284';
                    $sk_data[$key]['AccID'] = '001';
                }elseif($val['receivablesname']=='上饶网聚'){
                    $sk_data[$key]['BankAccID'] = '上饶网聚农行6796';
                    $sk_data[$key]['AccID'] = '003';
                }
                if(!empty($val['ad_code'])){
                    $sk_data[$key]['CusCode']=$val['ad_code'];
                }else{
                    $id=M('advertiser')->add(array('name'=>$val['paymentname']));
                    $advModel = D('Advertiser');
                    $ad_code = $advModel->generalCode($insertId);
                    M('advertiser')->where("id=$id")->save(array('ad_code'=>$ad_code));
                    $sk_data[$key]['CusCode']=$ad_code;
                }
                /*if($val['type'] == 1){//结算款
                    $sk_data[$key]['CusCode'] =$val['ad_code'];
                    $sk_data[$key]['CusName'] =$val['name'];
                    if(!empty($val['bl_id'])){
                        $sk_data[$key]['ItemCode'] =$val['bl_id'];
                    }else{
                        $sk_data[$key]['ItemCode'] = '0';
                    }
                    if(!empty($val['bl_name'])){
                        $sk_data[$key]['ItemName'] =$val['bl_name'];
                    }else{
                        $sk_data[$key]['ItemName'] = '0';
                    }


                }elseif($val['type'] == 2){//预收款
                    $adv_data = $adv_model->field('ad_code,`name`')->where('id='.$val['skjsdid'].'')->select();
                    foreach($adv_data as $adv_code){
                        if(!empty($adv_code['ad_code'])){
                            $sk_data[$key]['CusCode'] =$adv_code['ad_code'];
                        }else{
                            $sk_data[$key]['CusCode'] = '0';
                        }
                        if(!empty($adv_code['name'])){
                            $sk_data[$key]['CusName'] =$adv_code['name'];
                        }else{
                            $sk_data[$key]['CusName'] = '0';
                        }

                    }
                    if(!empty($val['lined'])){
                        $pl_data = $pl_model->field('id AS bl_id,`name` AS bl_name')->where('id='.$val['lined'].'')->select();
                        foreach($pl_data as $pd){
                            if(!empty($pd['bl_id'])){
                                $sk_data[$key]['ItemCode'] =$pd['bl_id'];
                            }else{
                                $sk_data[$key]['ItemName'] ='0' ;
                            }

                        }
                    }else{
                        $sk_data[$key]['ItemCode'] ='0';
                        $sk_data[$key]['ItemName'] ='0';
                    }
                }
                */
                

            }
            return $sk_data;
	}
}