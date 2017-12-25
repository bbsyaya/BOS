<?php
namespace OA\Controller;
use Think\Controller;
//从BOS向OA发送数据执行流程专用
class BostooaController extends Controller {
    public function setToOaApi(){//成本结算流程发起
        $error='';
        $allid=I('param.allid');
        $set_data_check=M('settlement_out')->where("id in ($allid) && status not in (1,2)")->find();
        if($set_data_check){
            echo json_encode(array('msg'=>'部分结算单处于不能发起OA流程的状态','status'=>2,'dataid'=>0));
            return;
        }
        $id_arr=explode(',', $allid);
        $is_ysc=false;
        $yjsnum=0;
        foreach ($id_arr as $key => $value) {
            //验证是否已开始流程
            $liuchen_data=M('oa_66')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=66')->where("find_in_set($value,a.x739c8a_13) && b.status!=0")->find();

            $liuchen_data2=M('oa_67')->field('b.liuchenid')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=67')->where("find_in_set($value,a.x29cc4e_17)")->find();

            if($liuchen_data || $liuchen_data2){
                $yjsnum++;
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
            if(count($id_arr)==$yjsnum){
                echo json_encode(array('msg'=>'所有结算单已经在流程中了','status'=>1,'dataid'=>$liuchenid));
                return;  
            }else{
                echo json_encode(array('msg'=>'部分结算单已经在流程中了','status'=>2,'dataid'=>$liuchenid));
                return;
            }
        }
        $allid=implode(',', $id_arr);
        $set_data=M('settlement_out')->field('a.*,b.real_name,c.payee_name as advname')->join("a join boss_user b on a.sangwuid=b.id join boss_supplier_finance c on a.addresserid=c.sp_id && c.bl_id=a.lineid")->where("a.id in ($allid)")->select();
        $superid=$set_data[0]['addresserid'];
        $lineid=$set_data[0]['lineid'];
        $supname=$set_data[0]['advname'];
        foreach ($set_data as $key => $value) {
            if(trim($value['advname'])!=trim($supname))$error='供应商不一致';
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
        $oaid=M('oa_oanum')->add(array('userid'=>I('param.userid')));
        $superinfo=M('supplier')->field('a.name,b.invoice_type,b.financial_tax,b.payee_name,b.bank_no,b.opening_bank')->join('a join boss_supplier_finance b on a.id=b.sp_id')->where("a.id=".$superid.' && b.bl_id='.$lineid)->find();

        $thisdata=array();
        $thisdata['x739c8a_1']=$oaid;
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
                $str=implode("','", $allyf);
                $where=" && x05b464_1 not in ('$str')";
            }else $where="";
            $yf_data=M('oa_65')->where("x05b464_10=".$superid." && x05b464_4>x05b464_9".$where)->order('id asc')->find();
            if(!$yf_data)break;
            $thismoney=$yf_data['x05b464_4']-$yf_data['x05b464_9'];
            $allyfmoney+=$thismoney;
            $allyf[]=$yf_data['x05b464_1'];
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
        $dataid=M('oa_66')->add($thisdata);
        if(!$dataid){
            echo json_encode(array('msg'=>'创建流程失败','status'=>2,'dataid'=>0));
            return;
        }
        $data=array();
        $data['alldata']=$dataid;//所有数据
        $data['adduser']=I('param.userid');
        $data['liuchenid']=$oaid;
        $data['status']=1;
        $data['addtime']=date('Y-m-d H:i:s');
        $data['nowsort']=0;
        $data['isok']=0;
        $data['name']=I('param.title');
        $res=M('oa_jiedian')->where("pid=66")->select();
        $alluser=array();
        foreach ($res as $key => $value) {
            $alluser[]=$value['userid'];
        }
        $data['alluser']=implode(',',$alluser);
        $data['mid']=66;
        $nowid=M('oa_liuchen')->add($data);
        
        $data['id']=$nowid;

        $nowsort=$this->getsort($data);
        
        $firstid=$this->addtixing($oaid,0);
        $tixing_data=M('oa_tixing')->where("id=".$firstid)->find();
        $this->actionlog('发起流程',$oaid,$tixing_data['jiedianid']);
        M('oa_tixing')->where("id=".$firstid)->save(array('userid'=>I('param.userid'),'is_check'=>1));
        $nextid=$this->addtixing($oaid,$nowsort);
        M('oa_tixing')->where("id=".$nextid)->save(array('userid'=>I('param.zuserid')));

        //通知审核人 2017.08.31
        $tiXing = M('oa_tixing');
        $Data = $tiXing->field("a.jiedianid,b.name")->join("a join boss_oa_liuchen b on a.liuchenid=b.liuchenid")->where("a.id=".$nextid)->find();

        $prompt_information = M('prompt_information');
        $addData = array();
        $addData['date_time'] = date('Y-m-d H:i:s');
        $addData['send_user'] = I('param.userid');
        $addData['content'] = "请审核流程 (OA号:".$oaid.")".$Data['name'];
        $addData['a_link'] = "/OA/Index/useing?lcid=".$oaid."&jdid=".$Data['jiedianid']."&txid=".$nextid."";
        $addData['oa_number'] = $oaid;
        $prompt_information->add($addData);
        echo json_encode(array('msg'=>'创建流程成功','status'=>1,'dataid'=>$oaid));
        //$this->success('为你跳转到流程查看页面','/OA/Index/lc_detail?id='.$oaid);
    }
    public function getsort($res=0){//判断当前行为应执行哪个流程(当前序列，流程信息)

      $sort=$res['nowsort'];

            $nowjd=M('oa_jiedian')->where("sort=$sort && pid={$res['mid']}")->find();
            
            if($nowjd['nextjdlist']==''){
                 return -1;
            }else $res_other_jiedian=M('oa_jiedian')->field('a.*')->join("a join boss_oa_liuchen_m b on a.pid=b.id")->where("a.id in (".$nowjd['nextjdlist'].")")->select();//找出所有下一节点
          $alldata=M('oa_'.$res['mid'])->where("id=".$res['alldata'])->find();//取所有表单数据
          $shenghejilu=json_decode($res['shenghejilu'],true);//取得所有审核记录
          foreach ($res_other_jiedian as $k => $v) {//循环判断执行条件
            if($v['tiaojian']=='')return $v['sort'];
            $tiaojian_arr=array();
            $res_tiaojian=M('oa_tiaojian')->where("jid=".$v['id'])->select();//此节点所有相关条件

            foreach ($res_tiaojian as $val) {
              if(substr($val['key'],0,1)=='x'){
                if(!is_numeric($val['val']))$d2='"'.$val['val'].'"';
                else $d2=$val['val'];
                if(!is_numeric($alldata[$val['key']]))$d1="'".$alldata[$val['key']]."'";
                else $d1=$alldata[$val['key']];
                if($val['type']=='=')$val['type']='==';
                $str='return '.$d1.$val['type'].$d2.';';
                $tiaojian_arr[$val['code']]=eval($str);
              }else{
                if($shenghejilu[$val['key']][0]==$val['val']){
                  $tiaojian_arr[$val['code']]=true;
                }else{
                  $tiaojian_arr[$val['code']]=false;
                }
              }
            }
            $t=str_replace('[', ' $tiaojian_arr[', $v['tiaojian']);
            $t=str_replace(']', '] ', $t);
            $p=htmlspecialchars_decode('return '.$t.';');
            $jg=eval($p);
            if($jg)return $v['sort'];
          }
          $return = -1;
      return $return;
     }
     public function actionlog($data,$lcid,$jdid){//记录行为日志
        M('oa_zixingjilu')->add(array('addtime'=>date('Y-m-d H:i:s'),'user'=>$_SESSION['userinfo']['uid'],'action'=>$data));
        $hq_data=M('oa_hqyj')->where(array('liuchenid'=>$lcid))->order('id desc')->find();
        $use_data=M('user')->where("id=".I('param.userid'))->find();
        if($hq_data['jiedianid']!=$jdid || $hq_data['userid']!=$_SESSION['userinfo']['uid'])M('oa_hqyj')->add(array('content'=>I('post.hqyj'),'username'=>$use_data['real_name'],'userid'=>I('param.userid'),'addtime'=>date('Y-m-d H:i:s'),'liuchenid'=>$lcid,'action'=>$data,'jiedianid'=>$jdid));
     }
     public function addtixing($liuchenid,$sort){//增加消息提示

      $res=M('oa_liuchen')->field('c.id,c.alltime,c.userid,c.sort,c.autouser')->join("a join boss_oa_jiedian c on c.pid=a.mid")->where("a.liuchenid='$liuchenid' && c.sort>=$sort")->order('c.sort asc')->find();
      if($sort==0)$userid=$_SESSION['userinfo']['uid'];
      elseif($res['autouser']!=0)$userid=$res['autouser'];//默认选择人
      else $userid=0;
      $data=array('liuchenid'=>$liuchenid,'jiedianid'=>$res['id'],'is_check'=>0,'is_butixin'=>0,'url'=>'','is_ok'=>0);
      $old=M('oa_tixing')->where($data)->find();
      if(!$old){
        $data['endtime']=time()+$res['alltime'];
        $data['addtime']=date('Y-m-d H:i:s');
        $id=M('oa_tixing')->add($data);
    }else $id=$old['id'];
        M('oa_liuchen')->where("liuchenid='".$liuchenid."'")->save(array('nowsort'=>$res['sort']));
        return $id;
     }
}