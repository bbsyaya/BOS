<?php
namespace OA\Controller;
use Think\Controller;
class AjaxController extends Controller {
	public function getnowdata(){
		$res=M('oa_oanum')->add(array('userid'=>$_SESSION['userinfo']['uid']));
		echo json_encode(array('nowuser'=>$_SESSION['userinfo']['realname'],'nowuserid'=>$_SESSION['userinfo']['uid'],'nowbumen'=>$_SESSION['userinfo']['depart_name'],'nowdate'=>date('Y-m-d'),'nowid'=>$res,'nowgw'=>$_SESSION['userinfo']['duty_name'],'nowgs'=>$_SESSION['userinfo']['c_name']));
	}
	public function showhetonginfo(){
		//查询是否有相同合同
		$res=M('oa_45')->where("x72668e_6='".I('post.kehu')."' && x72668e_7='".I('post.zt')."' && x72668e_8='".I('post.type')."'")->find();
		echo $res['liuchenid'];
	}
	public function showallhetong(){
		if(!empty(I('post.name')))$where="b.name like '%".I('post.name')."%' || b.liuchenid='".I('post.name')."'";
		else $where="";
		//查询所有业务合同
		$data=M('oa_45')->field('a.*,b.liuchenid as oaid,b.name,b.addtime')->join("a join boss_oa_liuchen b on a.id=b.alldata && b.mid=45 join boss_oa_liuchen_m c on b.mid=c.id")->where($where)->select();
		foreach ($data as $key => $value) {
			$data[$key]['name']=$value['name']."(".$value['addtime'].")";
			$data[$key]['url']='/OA/Index/lc_detail?id='.$value['oaid'];
            $data[$key]['id']=$value['oaid'];
		}
		echo json_encode(array('type'=>'radio','data'=>$data));
	}
	public function getmyjkinfo(){
		//获取所有当前用户借款单
		$data=M('oa_60')->field('a.*,b.liuchenid as oaid,c.name,b.addtime')->join("a join boss_oa_liuchen b on a.id=b.alldata && b.mid=60 join boss_oa_liuchen_m c on b.mid=c.id")->where("b.adduser=".$_SESSION['userinfo']['uid'])->select();
		foreach ($data as $key => $value) {
            $data[$key]['id']=$value['x782c92_3'];
			$data[$key]['name']=$value['name']."(".$value['addtime'].")";
			$data[$key]['url']='/OA/Index/lc_detail?id='.$value['oaid'];
		}
		echo json_encode(array('type'=>'radio','data'=>$data));
	}
	public function getoldjkmoney(){
		//获取剩余金额
		$data=M('oa_60')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=60')->where("b.liuchenid='".I('post.id')."'")->find();
		$allmoney=$data['x782c92_5'];
		$datainfo=M('oa_40')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=40')->where("b.liuchenid='".I('post.id')."'")->select();//所有此款已报销金额
		foreach ($datainfo as $key => $value) {
			$allmoney-=$value['xb27546_10'];
		}
		echo json_encode(array('money'=>$allmoney));
	}
	public function getoldyufuid(){
		//获取预付单号
		$data=M("oa_62")->field('a.*,b.liuchenid as oaid,c.name,b.addtime')->join("a join boss_oa_liuchen b on a.id=b.alldata && b.mid=62 join boss_oa_liuchen_m c on b.mid=c.id")->select();
		foreach ($data as $key => $value) {
			$data[$key]['name']=$value['name']."(".$value['addtime'].")";
			$data[$key]['url']='/OA/Index/lc_detail?id='.$value['oaid'];
		}
		echo json_encode(array('type'=>'radio','data'=>$data));
	}
	public function getalluser(){
		//获取所有人员列表
		if(!empty(I('post.name')))$wheres[]="real_name like '%".I('post.name')."%'";
		$wheres[]="status=1";
		$data=M('user')->field("real_name as name,id as uid")->where(implode(' && ', $wheres))->select();
		foreach ($data as $key => $value) {
			$data[$key]['id']=$value['name'];
		}
		echo json_encode(array('type'=>'radio','data'=>$data));
	}
	public function getsuperlastdata(){
        //查询最近三次支付记录
		$data=M('oa_66')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=66')->field('b.liuchenid as id,b.name,b.addtime')->where("a.x739c8a_20=".I('get.id'))->order('b.id desc')->limit('0,3')->select();
		$count=3-count($data);
		if($count>0){
			$data_old=M('oa_67')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=67')->field('b.liuchenid as id,b.name,b.addtime')->where("a.x29cc4e_23=".I('get.id'))->order('b.id desc')->limit('0,'.$count)->select();
			foreach ($data_old as $key => $value) {
				$data[]=$value;
			}
		}
		echo json_encode($data);
	}
	public function checksetoutlist(){
        //获取结算单列表
        if(!empty(I('post.name')))$wheres[]="b.payee_name like '%".I('post.name')."%'";
        $wheres[]="a.status in (1,2)";
		$data=M('settlement_out')->field('a.*,b.payee_name as supname,c.real_name')->join('a join boss_supplier_finance b on a.addresserid=b.sp_id && a.lineid=b.bl_id join boss_user c on a.sangwuid=c.id')->where(implode(' && ', $wheres))->select();
		foreach ($data as $key => $value) {
			$data[$key]['name']=$value['id'].'</td><td>'.$value['supname'].'</td><td>'.$value['strdate'].'~'.$value['enddate'].'</td><td>'.$value['real_name'].'</td><td>'.$value['settlementmoney'];
			$data[$key]['url']='/Home/Makesettlement/settlementIn?id='.$value['id'];
		}
		echo json_encode(array('type'=>'checkbox','data'=>$data,'title'=>'<tr><td></td><td>单号</td><td>付款方</td><td>时间</td><td>商务</td><td>金额</td></tr>'));
	}
	public function getsetdetail(){
        //重新发起成本支付流程
		$allid=I('param.id');
        $id_arr=explode(',', $allid);
        $is_ysc=false;
        foreach ($id_arr as $key => $value) {
            //验证是否已开始流程
            $liuchen_data=M('oa_66')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=66')->where("find_in_set($value,a.x739c8a_13) && b.status!=0")->find();
            $liuchen_data2=M('oa_67')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=67')->where("find_in_set($value,a.x29cc4e_17)")->find();
            if($liuchen_data || $liuchen_data2){
                $oldliuchenid=$liuchen_data['liuchenid'];
                if(!$oldliuchenid)$oldliuchenid=$liuchen_data2['liuchenid'];
                if(I('param.liuchenid')==$oldliuchenid)continue;
                $is_ysc=true;
                if(empty($liuchenid)){
                    if($liuchen_data)$liuchenid=$liuchen_data['liuchenid'];
                    if($liuchen_data2)$liuchenid=$liuchen_data2['liuchenid'];
                }else{
                    if($liuchen_data)$liuchenid2=$liuchen_data['liuchenid'];
                    if($liuchen_data2)$liuchenid2=$liuchen_data2['liuchenid'];
                    if($liuchenid!=$liuchenid2){
                        echo json_encode(array('msg'=>'部分结算单已经在流程中了','status'=>2,'dataid'=>0));
                        return;
                    }
                }
            }
        }
        if($is_ysc){
            echo json_encode(array('msg'=>'所有结算单已经在流程中了','status'=>2,'dataid'=>$liuchenid));
            return;
        }
        $allid=implode(',', $id_arr);
        $set_data=M('settlement_out')->field('a.*,b.real_name,c.payee_name as advname')->join("a join boss_user b on a.sangwuid=b.id join boss_supplier_finance c on a.addresserid=c.sp_id && c.bl_id=a.lineid")->where("a.id in ($allid)")->select();
        $superid=$set_data[0]['addresserid'];
        $lineid=$set_data[0]['lineid'];
        $supname=$set_data[0]['advname'];
        foreach ($set_data as $key => $value) {
            if($value['advname']!=$supname)$error='供应商不一致';
            $com_arr=explode(',', $value['allcomname']);
            $com_arr=array_unique($com_arr);
            $set_data[$key]['comname']=implode(' | ', $com_arr);
            /*
            $com_data=M('product')->field('a.name')->join("a join boss_charging_logo b on a.id=b.prot_id")->where("b.id in (".$value['alljfid'].")")->group('a.id')->select();
            foreach ($com_data as $k => $v) {
                $set_data[$key]['comname'].=$v['name'].',';
            }
            */
        }

        if($error!=''){
            echo json_encode(array('msg'=>$error,'status'=>2,'dataid'=>0));
            return;
        }
        $info_data=M('settlement_out')->field('a.*,b.real_name,c.name as ztname,d.name as linename,a.lineid,e.name as ztname')->join("a join boss_user b on a.sangwuid=b.id join boss_data_dic c on a.jsztid=c.id join boss_business_line d on a.lineid=d.id join boss_data_dic e on a.jsztid=e.id")->where("a.id=".$set_data[0]['id'])->find();

        //创建OA流程
        $superinfo=M('supplier')->field('a.name,b.invoice_type,b.financial_tax,b.payee_name,b.bank_no,b.opening_bank')->join('a join boss_supplier_finance b on a.id=b.sp_id')->where("a.id=".$superid.' && b.bl_id='.$lineid)->find();

        $thisdata=array();
        $thisdata['x739c8a_2']=$info_data['linename'];
        $thisdata['x739c8a_0']=$info_data['ztname'];
        $thisdata['x739c8a_3']=$superinfo['payee_name'];
        $thisdata['x739c8a_4']=C('option.invoice_type')[$superinfo['invoice_type']];
        $thisdata['x739c8a_5']=$superinfo['financial_tax'];
        $thisdata['x739c8a_10']=$superinfo['payee_name'];
        $thisdata['x739c8a_11']=$superinfo['bank_no'];
        $thisdata['x739c8a_12']=$superinfo['opening_bank'];
        $allmoney=0;
        $id_arr=array();
        $comname_arr=array();
        $bu_arr=array();
        $date_arr=array();
        $money_arr=array();
        $zt_arr=array();
        foreach ($set_data as $k => $v) {
            $allmoney+=$v['notaxmoney'];
            $id_arr[]=$v['id'];
            $comname_arr[]=$v['comname'];
            $bu_arr[]=$v['real_name'];
            $date_arr[]=$v['strdate'].' ~ '.$v['enddate'];
            $money_arr[]=$v['notaxmoney'];
        }
        $thisdata['x97eb27_12']=$allmoney;
        $allyf=array();
        $allyfmoney=0;
        while (1) {
            if(count($allyf)>0){
                $str=implode(',', $allyf);
                $where=" && id not in ($str)";
            }else $where="";
            $yf_data=M('oa_65')->where("x05b464_10=".$superid." && x05b464_4>x05b464_9".$where)->order('id asc')->find();
            if(!$yf_data)break;
            $thismoney=$yf_data['x05b464_4']-$yf_data['x05b464_9'];
            $allyfmoney+=$thismoney;
            $allyf[]=$yf_data['id'];
            if($allyfmoney>=$allmoney)break;
        }
        $thisdata['x739c8a_7']=$allyfmoney;
        $thisdata['x739c8a_6']=implode(',', $allyf);
        if(count($allyf)>0 && $allyfmoney>0){
            //有预付款
            if($allyfmoney>=$allmoney){
                //全部预付
                $thisdata['x739c8a_8']=$allmoney;
                $thisdata['x739c8a_9']=0;
            }else{
                //部分预付
                $thisdata['x739c8a_8']=$allyfmoney;
                $thisdata['x739c8a_9']=$allmoney-$allyfmoney;
            }
        }else{
            //无预付款
            $thisdata['x739c8a_8']=0;
            $thisdata['x739c8a_9']=$allmoney;
        }
        $thisdata['x739c8a_13']=implode(',', $id_arr);
        $thisdata['x739c8a_14']=implode(',', $comname_arr);
        $thisdata['x739c8a_15']=implode(',', $bu_arr);
        $thisdata['x739c8a_16']=implode(',', $date_arr);
        $thisdata['x739c8a_17']=implode(',', $money_arr);
        $thisdata['x739c8a_20']=$superid;
        echo json_encode(array('status'=>1,'data'=>$thisdata));
	}
    public function getallbumen(){
        //获取部门列表
        $data=M('user_department')->select();
        foreach ($data as $key => $value) {
            $data[$key]['id']=$value['name'];
        }

        echo json_encode(array('type'=>'checkbox','data'=>$data));
    }
    public function getsuper(){
        //选择供应商
        if(!empty(I('post.name')))$wheres[]="b.payee_name like '%".I('post.name')."%'";
        $wheres[]="(a.type=2 || a.type=3)";
        $data=M('supplier')->field('a.id,b.payee_name as name')->join("a join boss_supplier_finance b on a.id=b.sp_id")->where(implode(' && ', $wheres))->group('a.id')->select();
        echo json_encode(array('type'=>'radio','data'=>$data));
    }
    public function getsupfinfo(){
        //获取供应商财务信息
        $data=M('supplier')->join("a join boss_supplier_finance b on a.id=b.sp_id")->where("a.id=".I('param.id')." && b.bl_id=".I('param.lineid'))->find();
        echo json_encode(array('x05b464_3'=>$data['payee_name'],'x05b464_6'=>$data['payee_name'],'x05b464_7'=>$data['bank_no'],'x05b464_8'=>$data['opening_bank']));
    }
    public function getbankinfo(){
        //获取银行账号
        $data=M('oa_40')->where("xb27546_0='".$_SESSION['userinfo']['realname']."'")->select();
        foreach ($data as $key => $value) {
            $data[$key]['name']=$value['xb27546_18'].'</td><td>'.$value['xb27546_17'];
            $data[$key]['id']=$value['xb27546_18'];
        }
        //echo json_encode(array('type'=>'radio','data'=>$data,'title'=>'<tr><td></td><td>银行账号</td><td>开户行</td></tr>'));
    }

    //首页切换快捷方式
    public function index_changealink(){
        $data_user=M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
        if($data_user['oa_link']!='')$arr_link=explode(',', $data_user['oa_link']);
        else $arr_link=array();
        if(in_array(I('param.toid'), $arr_link)){
            echo json_encode(array('type'=>'2','msg'=>"已存在"));
            return;
        }
        foreach ($arr_link as $key => $value) {
            if($value==I('param.fromid')){
                $arr_link[$key]=I('param.toid');
            }
        }
        M('user')->where("id=".$_SESSION['userinfo']['uid'])->save(array('oa_link'=>implode(',', $arr_link)));
        echo json_encode(array('type'=>'1','msg'=>"切换成功"));
    }


    public function getallline(){
        //获取业务线列表
        $data=M('business_line')->where("status=1")->select();
        echo json_encode($data);
    }
    public function getortheroastatus(){
        $id=I('post.id');
        $data=M('oa_liuchen')->where("adduser=$id && status=1")->find();
        if($data)echo json_encode(array('msg'=>'<span style="color:red;">请注意：员工发起的OA流程没有全部结束</span>'));
        else echo json_encode(array('msg'=>'<span">员工发起的OA流程已全部结束</span>'));
    }
}