<?php
namespace OA\Controller;
use Common\Controller\BaseController;
use Common\Service;
class IndexController extends BaseController {
	public function index(){
		$p=I('get.p');
		$allnum=M('prompt_information')->where("find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && (a_link like '%/OA%' or a_link like '%会议提醒%') && status in (0,3) ")->count();

		if($p>=ceil($allnum/10))$p=ceil($allnum/10);
		if($p<1)$p=1;
		$str=($p-1)*10;
		$data_prom=M('prompt_information')->field('a.id,a.content,c.real_name,left(a.date_time,10) as date_time,a.status,a.oa_number')->join('a left join boss_oa_liuchen b on a.oa_number=b.liuchenid left join boss_user c on b.adduser=c.id')->where("find_in_set(".$_SESSION['userinfo']['uid'].",a.send_user) && a.status in (0,3) && (a.a_link like '%/OA%' or a.a_link like '%会议提醒%')")->group('a.id')->order("a.id desc")->limit($str.',10')->select();

		foreach($data_prom as $key=>$val){
			if(strlen($val['oa_number'])<5){
				$userData = M('user')->field('real_name')->where("id=".$val['oa_number'])->find();
				$data_prom[$key]['real_name'] = $userData['real_name'];
			}
		}

		$this->promlist=$data_prom;
		//$allnum=M('prompt_information')->where("find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && a_link like '%/OA%'")->count();
		$this->assign('page_html',getpagedata($allnum));
		$this->assign('num',$allnum);


		$id=$_SESSION['userinfo']['uid'];
		$this->data_lichen=M('oa_liuchen_m')->where("type>0 && (find_in_set($id,userlist) || userlist=0)")->select();
		$data_user=M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();

		$data_auth=M('auth_group_access')->join('a join boss_auth_group b on a.group_id=b.id')->where("a.uid=".$_SESSION['userinfo']['uid'])->find();
		$data_user['auth_name']=$data_auth['title'];
		$this->data_user=$data_user;
		if($data_user['oa_link']!='')$data_link=M('oa_liuchen_m')->where("id in (".$data_user['oa_link'].")")->select();
		else $data_link=array();
		$this->oldlink=$data_link;


		$this->data_userout=M('oa_liuchen')->where("mid=55 && status=2 && adduser=".$_SESSION['userinfo']['uid'])->find();
		/*//获取最新5条公告、制度等 2017.10.12
		$notify_type = C('OPTION.notify_type');
		$notify = M('notify');
		$notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name')->join('a left join boss_user b on a.FROM_ID=b.id')->where("a.state=1")->order('a.NOTIFY_ID desc')->limit(5)->select();
		foreach($notifyData as $key=>$val){
			$notifyData[$key]['TYPE_ID'] = $notify_type[$val['type_id']];
			if($val['to_id']){
				$dept = M('user_department')->field('name')->where("id in (".rtrim($val['to_id'],",").")")->select();
				$name = '';
				foreach($dept as $v){
					$name .= $v['name'].",";
				}
				$notifyData[$key]['user'] = rtrim($name,",");
			}
			if($val['user_id']){
				$us = M('user')->field('real_name')->where("id in (".rtrim($val['user_id'],",").")")->select();
				$name = '';
				foreach($us as $v2){
					$name .= $v2['real_name'].",";
				}
				$notifyData[$key]['user'] = rtrim($name,",");
			}
			if($val['end_date'] >0 or $val['end_date'] <date('Y-m-d')){//结束日期小于等于0表示未终止
				$notifyData[$key]['status'] = '终止';
			}else{
				$notifyData[$key]['status'] = '生效';
			}
		}
		$this->assign("notify",$notifyData);*/

		$this->display();
		
	}

	public function find_notify(){
		//获取最新7条公告、制度等 2017.10.12
		$id = I('get.id');
		$notify_type = C('OPTION.notify_type');
		$notify = M('notify');
		$notifyData = $notify->field('a.NOTIFY_ID,a.TYPE_ID,a.TO_ID,a.USER_ID,a.SUBJECT,a.SEND_TIME,a.BEGIN_DATE,a.END_DATE,a.PUBLISH,b.real_name,a.ATTACHMENT_ID,a.ATTACHMENT_NAME,a.SEND_TIME,a.CONTENT,a.NAME')->join('a left join boss_user b on a.FROM_ID=b.id')->where("a.state=1")->order('a.NOTIFY_ID desc')->limit(7)->select();
		if($id ==1){
			echo M()->getLastSql();exit;
		}
		foreach ($notifyData as $key => $val) {
			$notifyData[$key]['begin_date'] = date('Y-m-d',strtotime($val['send_time']));
			$notifyData[$key]['TYPE_ID'] = $notify_type[$val['type_id']];
			if ($val['to_id']) {
				$dept = M('user_department')->field('name')->where("id in (" . rtrim($val['to_id'], ",") . ")")->select();
				$name = '';
				foreach ($dept as $v) {
					$name .= $v['name'] . ",";
				}
				$notifyData[$key]['user'] = rtrim($name, ",");
			}
			if ($val['user_id']) {
				$us = M('user')->field('real_name')->where("id in (" . rtrim($val['user_id'], ",") . ")")->select();
				$name = '';
				foreach ($us as $v2) {
					$name .= $v2['real_name'] . ",";
				}
				$notifyData[$key]['user'] = rtrim($name, ",");
			}
			if ($val['end_date'] > 0 or $val['end_date'] < date('Y-m-d')) {//结束日期小于等于0表示未终止
				$notifyData[$key]['status'] = '终止';
			} else {
				$notifyData[$key]['status'] = '生效';
			}
			if(strpos($val['attachment_id'],'@',0)){
				$attachment_id = explode('@',$val['attachment_id']);
				$id_string=explode('_',$attachment_id[1]);
				$a = $id_string[0];
				$b = rtrim($id_string[1], ",");
				$hz = substr($val['name'], strrpos($val['name'], '.')+1);
				$notifyData[$key]['attachment_name'] = "./upload/notify/".$a."/".$b.".".$hz;
			}

		}
		if($id ==2){
			var_dump($notifyData);exit;
		}
		$this->ajaxReturn($notifyData);exit;
	}

	public function add(){
		if(!empty(I('get.type')))$wheres[]="type = ".I('get.type');
		else $wheres[]="type = 1";
		$id=$_SESSION['userinfo']['uid'];
		$wheres[]="(find_in_set($id,userlist)  $where || userlist=0)";
		$count=M('oa_liuchen_m')->where(implode(' && ', $wheres))->count();
		$pagedata=$this->getpage($count);
		$this->getpagelist($count);
		$this->data=M('oa_liuchen_m')->where(implode(' && ', $wheres))->limit($pagedata)->select();
		$this->display();
	}

	/**
	 * 发起流程
	 * @return [type] [description]
	 */
	public function begin(){
		$this->data=M('oa_liuchen_m')->where("id=".I('get.id'))->find();
		$this->alldata=json_encode(I('get.'));
		$this->display();
	}
	public function addDo(){
		if(!empty($_FILES['file']['tmp_name'])){
			$info=$this->uplaodfile('file',UPLOAD_OA_FILE_PATH);
	        if(!is_array($info)){
                $this->error('上传依据失败');
                return;
            }
	        $file_name=UPLOAD_OA_FILE_PATH.$info['file']['savepath'].$info['file']['savename'];
	        $data['filename']=$info['file']['name'];
	        $data['file']=substr($file_name,1);
	    }
		$data_d=array();
		foreach (I('post.') as $key => $value) {
			if($key!='thisliuchenid' && !is_array($value) && $key!='name')$data_d[$key]=stripslashes($value);
			elseif(is_array($value))$data_d[$key]='@@'.implode('@@', $value);
		}
		$dataid=M('oa_'.I('post.thisliuchenid'))->add($data_d);
		$data['alldata']=$dataid;//所有数据
		$data['adduser']=$_SESSION['userinfo']['uid'];
		$data['addtime']=date('Y-m-d H:i:s');
		$data['nowsort']=0;
		$data['status']=1;
		$data['isok']=0;
		$data['name']=I('post.name');
		$res=M('oa_jiedian')->where("pid=".I('post.thisliuchenid'))->select();
		$alluser=array();
		foreach ($res as $key => $value) {
			$alluser[]=$value['userid'];
		}
		$data['alluser']=implode(',',$alluser);
		$data['mid']=I('post.thisliuchenid');
		$data['liuchenid']=I('post.this_lcid');
		//判断OA号是否重复
		$process = M('oa_liuchen')->field('liuchenid')->where("liuchenid='".I('post.this_lcid')."'")->find();
		if($process['liuchenid']){
			$this->error('请勿重复提交','/OA/Index/loading');
		}
		$nowid=M('oa_liuchen')->add($data);
		$data['id']=$nowid;
		$nowsort=$this->getsort($data);
		$nextid=$this->addtixing(I('post.this_lcid'),0);
		$tixing_data=M('oa_tixing')->where("id=".$nextid)->find();
        $this->actionlog('发起流程',I('post.this_lcid'),$tixing_data['jiedianid']);
		echo '<script>window.location="/OA/Index/xuanzeuser?lcid='.$data['liuchenid'].'&sortid='.$nowsort.'&txid='.$nextid.'"</script>';
	}
	public function xuanzeuser(){//选择经办人
		//$nextid=$this->addtixing(I('get.lcid'),I('get.sortid'));

		$res=M('oa_liuchen')->field('c.id,c.alltime,c.userid,c.sort,c.autouser,a.mid,a.alldata,a.adduser')->join("a join boss_oa_jiedian c on c.pid=a.mid")->where("a.liuchenid='".I('get.lcid')."' && c.sort>=".I('get.sortid'))->order('c.sort asc')->find();
		$this->data=M('oa_jiedian')->field('name,userid as alluid,bumenlist,jiaoselist,autouser,autojb')->where("id=".$res['id'])->find();
		if($this->data['autouser']!=0 && $this->data['autouser']!=''){
			$this->autouser=$this->data['autouser'];
		}elseif($this->data['autouser']==0){
			$userbumen_data=M('user')->where("id=".$res['adduser'])->find();
			$user_data=M('user_department')->where("id=".$userbumen_data['leve_depart_id'])->find();
			$this->autouser=$user_data['heads_id'];
		}
		/*成本支付流程选择经办人*/
		if($res['mid']==66 && $res['id']==798){
			$data_66=M('oa_66')->where("id=".$res['alldata'])->find();
			$user_arr=explode(',', $data_66['x739c8a_15']);
			if($data_66['x739c8a_2']=='优效分发平台'){
				if($res['adduser']==655)$user_arr[]='黄榜杰';
				if($res['adduser']==154)$user_arr[]='蔡静';
			}
			$userstr=implode("','", $user_arr);
			$user_data2=M('user')->field('group_concat(id) as str')->where("real_name in ('$userstr')")->find();
			$this->autojb=explode(',',$user_data2['str']);
		}
		/*成本支付流程选择经办人结束*/
		/*离职选择交接人经办*/
		elseif($res['mid']==55 && $res['id']==725){
			$data_55=M('oa_55')->where("id=".$res['alldata'])->find();
			$user_data2=M('user')->where("real_name ='".$data_55['x13166f_9']."'")->find();
			$this->autojb=$user_data2['id'];
		}
		/*离职选择交接人经办结束*/
		elseif($this->data['autojb']==='0' && $this->data['autouser']==0){
			$this->autojb=$user_data['heads_id'];
		}elseif($this->data['autojb']==='0'){
			$user_data=M('user_department')->where("id=".$_SESSION['userinfo']['depart_id'])->find();
			$this->autojb=$user_data['heads_id'];
		}else{
			$this->autojb=explode(',', $this->data['autojb']);
		}
		$userlist=$this->data['alluid'];
		if($userlist=='0')unset($userlist);
		if($this->data['name']=='开始节点'){
			$lc_data=M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->find();
			$userlist=$lc_data['adduser'];
		}
		$bumenlist=$this->data['bumenlist'];
		$jiaoselist=$this->data['jiaoselist'];
		if($userlist!='')$wheres[]="id in ($userlist)";
		if($bumenlist!='')$wheres[]="bid in ($bumenlist)";
		if($jiaoselist!='')$wheres[]="jid in ($jiaoselist)";
		$this->alluid=M('user')->where(implode(' || ', $wheres))->select();
		$this->display();
	}
	public function xuanzeuserDo(){//选择经办人执行
		if(!empty(I('get.txid')))M('oa_tixing')->where("id=".I('get.txid'))->save(array('is_check'=>1,'overtime'=>date('Y-m-d H:i:s')));
		$prompt_information = M('prompt_information');
		$nextid=$this->addtixing(I('get.lcid'),I('get.sortid'));

		/*特殊流程判断*/

		$lc_data=M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->find();

		/*如果是成本支付，向其他平台同步数据*/
		
		if($lc_data['mid']==66){
			$tx_data=M('oa_tixing')->where("id=$nextid")->find();
			$setstatus=0;
			switch ($tx_data['jiedianid']) {
				case '789'://回到开始结点
					$setstatus=1;//结算单状态
					$datastatus=2;//数据状态
					$msg='流程未通过审核';
					break;
				case '800'://通过部门负责人到达财务
					$setstatus=2;
					$datastatus=3;
					$msg='流程到达财务';
					break;
			}
			if($setstatus>0){
				$data_66=M('oa_66')->where("id=".$lc_data['alldata'])->find();
				$setid_arr=explode(',', $data_66['x739c8a_13']);
				foreach ($setid_arr as $key => $value) {
					$res=M('settlement_out')->where("id=".$value)->save(array('status'=>$setstatus));
					$set_data=M('settlement_out')->where("id=".$value)->find();
			        $alldataid=D('Home/DaydataOut')->editdataforcom($set_data['superid'],$set_data['sangwuid'],$set_data['lineid'],$set_data['strdate'],$set_data['enddate'],$set_data['alljfid']);
			        foreach ($alldataid as $k => $v) {
			            $id_arr[]=$v['id'];
			        }
			        $id_str=implode(',',$id_arr);
					$res1=M('daydata_out')->where("id in ($id_str)")->save(array('status'=>$datastatus));
					foreach ($id_arr as $key => $val) {
		                $logid=D('Home/DaydataLog')->adddata(array('dataid'=>$val,'remark'=>$msg.' '.I('get.lcid'),'datatype'=>2,'addtime'=>date('Y-m-d H:i:s'),'username'=>''));
		            }
	        		M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>$datastatus));
	        		$postres=postDatatoorther($alldataid,$setstatus,$value,$setstatus);
	        		if($postres->status==2){
	        			echo "<script>layer.msg('同步失败');history.go(-1);</script>";
	                    exit();
	                }

				}
			}
		}

		/*如果是成本支付，向其他平台同步数据结束*/
		

		/*如果是合同流程并且到达运营存档步骤，向申请人和行政部发送提醒*/
		if($lc_data['mid']==45){
			$tx_data=M('oa_tixing')->where("id=$nextid")->find();
			if($tx_data['jiedianid']=='670'){
				$addData = array();
				$addData['date_time'] = date('Y-m-d H:i:s');
				$addData['send_user'] = $lc_data['adduser'].',798';
				$addData['content'] = "OA号:".I('get.lcid')."合同已经可以打印盖章，请尽快处理";
				$addData['a_link'] = "/OA/Index/useing?lcid=".I('get.lcid')."&jdid=".$Data['jiedianid']."&txid=".$nextid."";
				$addData['oa_number'] = I('get.lcid');
				$prompt_information->add($addData);
				unset($addData);
			}
		}

		/*如果是合同流程并且到达运营存档步骤，向申请人和行政部发送提醒结束*/

		/*特殊流程判断结束*/


		$userlist=implode(',', I('get.juserlist'));
		M('oa_tixing')->where("id=".$nextid)->save(array('userid'=>I('get.userid'),'juserlist'=>$userlist,'juser_detail'=>$userlist));

		//通知审核人 2017.08.31
		$tiXing = M('oa_tixing');
		$Data = $tiXing->field("a.jiedianid,b.name")->join("a join boss_oa_liuchen b on a.liuchenid=b.liuchenid")->where("a.id=".$nextid)->find();

		
		//标注待通知信息已办理--处理当前流程上一节点已阅读状态 udpate 10-12
		if(I('get.lcid')){
			$info_ = $prompt_information->field("id")->where(array("oa_number"=>I('get.lcid')))->find();
			if($info_){
				$prompt_information->where(array("id"=>$info_["id"]))->save(array("status"=>1));
			}
		}
		
		$addData = array();
		$addData['date_time'] = date('Y-m-d H:i:s');
		$addData['send_user'] = I('get.userid').','.$userlist;
		$addData['content'] = "请审核流程 (OA号:".I('get.lcid').")".$Data['name'];
		$addData['a_link'] = "/OA/Index/useing?lcid=".I('get.lcid')."&jdid=".$Data['jiedianid']."&txid=".$nextid."";
		$addData['oa_number'] = I('get.lcid');
		$prompt_information->add($addData);
		echo "<script>top.window.location='/OA/Index/main.html?mainurl=/OA/Index/loading'</script>";
	}
	public function loading(){//待办流程
		//判断当前节点应在审核通过后
		$id=$_SESSION['userinfo']['uid'];
		if(!empty(I('get.type')) && I('get.type')==2){
			$wheres2[]="(a.is_check=1 || (a.is_check=0 && find_in_set($id,a.juserlist) && not find_in_set($id,a.juser_detail)))";
		}elseif(!empty(I('get.type')) && I('get.type')==3){
			$wheres2[]="b.adduser=".$_SESSION['userinfo']['uid'];
		}elseif(!empty(I('get.type')) && I('get.type')==4 && $_SESSION['userinfo']['uid']==669){
			$wheres2[]="b.mid=45";
		}else{
			$wheres2[]="if($id != a.userid,find_in_set($id,a.juser_detail),1) && b.status>0 && a.is_check=0";
		}
		if(!empty(I('get.oaid'))){
			$wheres2[]="a.liuchenid like '%".I('get.oaid')."%'";
		}
		if(!empty(I('get.type2'))){
			$wheres2[]="c.type=".I('get.type2');
		}
		if(!empty(I('get.status'))){
			if(I('get.status')==3)$wheres2[]="b.status=0";
			else $wheres2[]="b.status=".I('get.status');
		}
		if(!empty(I('get.name')))$wheres2[]="b.name like '%".I('get.name')."%'";
		if(!empty(I('get.user')))$wheres2[]="d.real_name like '%".I('get.user')."%'";
		if(!empty(I('get.oatime'))){
			$wheres2[]="b.addtime >= '".I('get.oatime')."' && b.addtime <= '".I('get.oatime2')." 23:59:59'";
		}
		
		if($id && I('get.type')!=3 && I('get.type')!=4)$wheres2[]="($id = a.userid || find_in_set($id,a.juserlist))";
		
		$con_data=M()->query("select count(*) as num from (select a.id from boss_oa_tixing a join boss_oa_liuchen b on a.liuchenid=b.liuchenid join boss_oa_liuchen_m c on b.mid=c.id join boss_user d on b.adduser=d.id join boss_oa_jiedian e on c.id=e.pid && e.sort=b.nowsort left join boss_oa_tixing f on f.liuchenid=a.liuchenid && f.jiedianid=e.id && f.is_check=0 where ".implode(' && ', $wheres2)." group by b.id)t");
		$count=$con_data[0]['num'];
		$pagedata=$this->getpage($count);
		$this->getpagelist($count); 
		$res=M('oa_tixing')->field('a.liuchenid,f.jiedianid,b.name,b.addtime,b.status,max(a.id) as id,a.is_check,e.name as thissortname,b.adduser,f.userid,f.juser_detail,c.name as mname')->join('a join boss_oa_liuchen b on a.liuchenid=b.liuchenid join boss_oa_liuchen_m c on b.mid=c.id join boss_user d on b.adduser=d.id join boss_oa_jiedian e on c.id=e.pid && e.sort=b.nowsort left join boss_oa_tixing f on f.liuchenid=a.liuchenid && f.jiedianid=e.id && f.is_check=0')->where(implode(' && ', $wheres2))->group('b.id')->order('a.addtime desc')->limit($pagedata)->select();
		$alluser=array();
		foreach ($res as $k => $v) {
			if(!empty(I('get.type')) && I('get.type')==2){
				$tx_data=M('oa_tixing')->where("is_check=1 && liuchenid='".$v['liuchenid']."'")->order("id desc")->find();
				if($tx_data['id']==$v['id']){
					$t_data=M('oa_tixing')->where("is_check=0 && liuchenid='".$v['liuchenid']."'")->find();
					if($t_data['isunset']!=1)$res[$k]['isunset']=1;
				}
			}elseif(!empty(I('get.type')) && I('get.type')==3){

			}else{
				if($v['thissortname']=='开始节点'){
					$res[$k]['isdel']=1;
				}
			}
			if($v['userid'])$alluser[]=$v['userid'];
			if($v['adduser'])$alluser[]=$v['adduser'];
			if($v['juser_detail']!='')$alluser=array_merge($alluser,explode(',', $v['juser_detail']));
		}
		$alluser=array_unique($alluser);
		if(count($alluser)>0){
			$allusername=M('user')->where("id in (".implode(',', $alluser).")")->select();
			foreach ($allusername as $key => $value) {
				$allusername2[$value['id']]=$value['real_name'];
			}
			foreach ($res as $k => $v) {
				$res[$k]['adduser']=$allusername2[$res[$k]['adduser']];
				$res[$k]['thisuser']='主办：'.$allusername2[$res[$k]['userid']];
				if($v['juser_detail']!=''){
					$arr=explode(',', $v['juser_detail']);
					foreach ($arr as $key => $value) {
						$arr[$key]=$allusername2[$value];
					}
					$res[$k]['thisuser'].='&nbsp;经办：'.implode(',', $arr);
				}
				
			}
		}

		$this->data=$res;
		$this->display();
	}
	public function useing(){
		$this->display();		
	}
	public function useing_if(){
		$res=M('oa_liuchen_m')->field('c.name,a.id,a.tablestyle,b.sort,c.shenghejilu,c.alldata,a.tablekeylist,c.nowsort,b.tszdlist,c.file,c.filename,b.yaodian,b.name as jname,a.name as printname')->join("a join boss_oa_jiedian b on a.id=b.pid join boss_oa_liuchen c on c.mid=a.id")->where("b.id=".I('get.jdid')." && c.liuchenid='".I('get.lcid')."'")->find();
		// print_r(M('oa_liuchen_m')->getLastsql());exit;
		if(!$res){
			print_r("系统无对应oa流程数据表，请检查数据是否有误");
			exit;
		}
		if(preg_match_all('/存档/',$res['jname'],$n))$this->iscd=1;//是否是存档步骤
		$tabledata=M('oa_liuchen')->join("a join boss_oa_{$res['id']} b on a.alldata=b.id")->where("a.liuchenid='".I('get.lcid')."'")->find();
		$tx_data=M('oa_tixing')->where("id=".I('get.txid'))->find();
		if(trim($tx_data['userid'])==$_SESSION['userinfo']['uid'] && ($tx_data['juser_detail']=='' || $tx_data['juser_detail']==$tx_data['userid'])){
			//主办

			$this->assign('tj_ok',1);
		}elseif($tx_data['juser_detail']!=''){
			$juser_arr=explode(',', $tx_data['juser_detail']);
			$juser_listarr=explode(',', $tx_data['juserlist']);
			if(in_array($_SESSION['userinfo']['uid'],$juser_arr)){
				//经办
				$this->assign('tj_ok',2);
			}elseif(in_array($_SESSION['userinfo']['uid'],$juser_listarr)){
				//经办已办
				$this->assign('tj_ok',3);
			}else{
				$this->assign('tj_ok',0);
			}
		}else{
			$this->assign('tj_ok',0);
		}
		//判断当前节点是否已经办理
		if($tx_data["is_check"]==1){
			$this->assign('tj_ok',3);
		}

		foreach ($tabledata as $key => $value) {
			if(substr($value, 0,2)=='@@')$tabledata[$key]=explode('@@', substr($value,2));
			else $tabledata[$key]=htmlspecialchars_decode($value);
		}
		$this->tabledata=json_encode($tabledata);
		$this->alldata=$tabledata;
		if($tabledata['shenghejilu']!=''){
			$shenghejilu=json_decode($tabledata['shenghejilu'],true);
			foreach ($shenghejilu as $key => $value) {
				$shenghearr[]=$key;
			}
			$nameandkey=M('oa_jiedian')->where("id in (".implode(',', $shenghearr).")")->select();
			foreach ($shenghejilu as $k => $v) {
				foreach ($nameandkey as $key => $value) {
					if($value['id']==$k){
						$arr=array('name'=>$value['name'],'val'=>$v[0]);
						if(!empty($v[2]))$arr['is_qz']=$v[2];
						$shenghedata[$v[1]]=$arr;
					}
				}
			}
			$this->assign('shenghedata',$shenghedata);
		}
		$this->hqyjlist=M('oa_hqyj')->where("liuchenid='".I('get.lcid')."' && content!=''")->order('id desc')->select();
		$this->blh=M('oa_hqyj')->where("liuchenid='".I('get.lcid')."'")->order('id desc')->select();
		$this->res=$res;
		if($res['tszdlist']!='')$this->tszdlist=json_encode(explode(',', $res['tszdlist']));
		else $this->tszdlist=json_encode(array());
		$data_dic=M('data_dic')->where("code='aabbcc'")->find();
		if($data_dic['name']!=1){
			$this->assign('youknow',0);
		}

		//获取节点流程list
		$proSer         = new \OA\Service\ProcessService();
		$processId      = trim(I('get.lcid'));
		$processAllList = $proSer->getProcessAllListSer($processId);
		$this->assign("processAllList",$processAllList);
		$this->display();		
	}
	public function check_ok(){
		unset($_POST['none']);
		//记录审核历史
		M('oa_tixing')->where("id=".I('post.txid'))->save(array('isunset'=>1));
		$res=M('oa_liuchen')->where("liuchenid='".I('post.lcid')."'")->find();
		$jiedian_data=M('oa_jiedian')->where("id=".I('post.jdid'))->find();
		if($jiedian_data['tszdlist']!=''){
			$tszdlist=explode(',', $jiedian_data['tszdlist']);
			foreach ($tszdlist as $key => $value) {
				$data_arr[$value]=I('post.'.$value);
			}
			M('oa_'.$res['mid'])->where("id=".$res['alldata'])->save($data_arr);
		}
		if($res['shenghejilu']!='')$shenghejilu=json_decode($res['shenghejilu'],true);
		else $shenghejilu=array();
		if(I('post.type')==1 && $res['nowsort']==0){
			//重新发起流程

		        $data_lichen['filename']=I('post.filename');
		        $data_lichen['file']=I('post.filepath');
		        
		    
		    $this->actionlog('重新开始流程',I('post.lcid'),I('post.jdid'));
		    $data_lichen['name']=I('post.name');
		    unset($_POST['name']);
		    M('oa_liuchen')->where("liuchenid='".I('post.lcid')."'")->save($data_lichen);
			M('oa_'.$res['mid'])->where("id=".$res['alldata'])->save(I('post.'));
			//判断下一步执行节点
			$nowsort=$this->getsort($res);
		}elseif(I('post.type')==1){
			if(!empty(I('post.filename'))){

		        $data_lichen['chundangpath']=I('post.filepath');
		        $data_lichen['chundanghao']=I('post.chundanghao');
		        M('oa_'.$res['mid'])->where("id=".$res['alldata'])->save($data_lichen);
		    }
		    
			if(I('post.is_qz')!=''){
				$shenghejilu[I('post.jdid')]=array('同意',time(),I('post.is_qz'));
			}else{
				$shenghejilu[I('post.jdid')]=array('同意',time());
			}
			$this->actionlog('通过审核',I('post.lcid'),I('post.jdid'));
			$res['shenghejilu']=json_encode($shenghejilu);
			M('oa_liuchen')->where("liuchenid='".I('post.lcid')."'")->save(array('shenghejilu'=>json_encode($shenghejilu)));
			//改变提醒状态
			//判断下一步执行节点
			$nowsort=$this->getsort($res);
		}else{
			$shenghejilu[I('post.jdid')]=array('不同意',time());
			$this->actionlog('不通过审核',I('post.lcid'),I('post.jdid'));
			M('oa_liuchen')->where("liuchenid='".I('post.lcid')."'")->save(array('shenghejilu'=>json_encode($shenghejilu)));
			//改变提醒状态
			
			//判断下一步执行节点
			$nowsort=0;
		}

		if($nowsort==-1){
			M('oa_liuchen')->where("liuchenid='".I('post.lcid')."'")->save(array('status'=>2));
			M('oa_tixing')->where("id=".I('post.txid'))->save(array('is_check'=>1,'overtime'=>date('Y-m-d H:i:s')));
			if($res['mid']==66){
				//付款流程结束
				$data_66=M('oa_66')->where("id=".$res['alldata'])->find();
				if($data_66['x739c8a_6']!='' && $data_66['x739c8a_8']>0){
					//有预付款核销
					$idarr=explode(',', $data_66['x739c8a_6']);
					$allmoney=$data_66['x739c8a_8'];
					foreach ($idarr as $key => $value) {
						$data_65=M('oa_65')->field('a.id,a.x05b464_9,a.x05b464_4')->join('a join boss_oa_liuchen b on a.id=b.alldata && b.mid=65')->where("b.liuchenid='".$value."'")->find();
						if(!$data_65)continue;
						if($data_65['x05b464_4']-$data_65['x05b464_9']>=$allmoney){
							//全部核销
							M('oa_65')->where("id=".$data_65['id'])->save(array('x05b464_9'=>$data_65['x05b464_9']+$allmoney));
							break;
						}else{
							$nowhxmoney=$data_65['x05b464_4']-$data_65['x05b464_9'];
							M('oa_65')->where("id=".$data_65['id'])->save(array('x05b464_9'=>$data_65['x05b464_4']));//此预付款已核销完
							$allmoney-=$nowhxmoney;
						}
					}
				}
				$setid_arr=explode(',', $data_66['x739c8a_13']);
				foreach ($setid_arr as $key => $value) {
					$res=M('SettlementOut')->where("id=".$value)->save(array('status'=>4));
					$set_data=M('SettlementOut')->where("id=".$value)->find();
			        $alldataid=D('Home/DaydataOut')->editdataforcom($set_data['superid'],$set_data['sangwuid'],$set_data['lineid'],$set_data['strdate'],$set_data['enddate'],$set_data['alljfid']);
			        foreach ($alldataid as $k => $v) {
			            $id_arr[]=$v['id'];
			        }
			        $id_str=implode(',',$id_arr);
					$res1=M('DaydataOut')->where("id in ($id_str)")->save(array('status'=>4));
            		M('daydata_inandout')->where("out_id in ($id_str)")->save(array('out_status'=>4));
            		postDatatoorther($alldataid,4,$value,4);
				}
			}elseif ($res['mid']==63) {
				//退款流程结束
				$data_63=M('oa_63')->where("id=".$res['alldata'])->find();
				if(substr($data_63['x2a1540_14'], 0,3)=='adv'){
					//预收退款
					$data_ysk=M('beforepay_ggz_all')->where("adverid=".substr($data_63['x2a1540_14'], 3))->find();
					M('beforepay_ggz_all')->where("adverid=".substr($data_63['x2a1540_14'], 3))->save(array('dhxmoney'=>$data_ysk['dhxmoney']-$data_63['x2a1540_8'],'yhxmoney'=>$data_ysk['yhxmoney']+$data_63['x2a1540_8']));
				}else{
					//认款退款
					M('pay')->where("id=".$data_63['x2a1540_14'])->save(array('yrkmoney'=>$data_63['x2a1540_8'],'wrkmoney'=>0,'status'=>3));
				}
			}elseif($res['mid']==55){
				//离职流程结束，进行权限交接
				$data_55=M('oa_55')->where("id=".$res['alldata'])->find();
				M('product')->where("saler_id=".$res['adduser'])->save(array('saler_id'=>$data_55['x13166f_9']));
				M('charging_logo_assign')->where("business_uid=".$res['adduser']." && status=1")->save(array('business_uid'=>$data_55['x13166f_9']));
				$data_oa=M('oa_jiedian')->where("find_in_set(".$res['adduser'].",'userid') && (find_in_set(".$res['adduser'].",'autojb') || autouser=".$res['adduser'].")")->select();
				foreach ($data_oa as $key => $value) {
					$user_arr=explode(',', $value['userid']);
					foreach ($user_arr as $k => $v) {
						if($v==$res['adduser'])$user_arr[$k]=$data_55['x13166f_9'];
					}
					$savedata['userid']=implode(',', $user_arr);
					if($value['autojb']!=''){
						$jb_arr=explode(',', $value['autojb']);
						foreach ($jb_arr as $k => $v) {
							if($v==$res['adduser'])$jb_arr[$k]=$data_55['x13166f_9'];
						}
						$savedata['autojb']=implode(',', $jb_arr);
					}
					M('oa_jiedian')->where("id=".$value['id'])->save($savedata);
				}

				$data=M('userout')->where("id=".I('post.id'))->find();
        		$rule=M('user')->field("a.fun_per as uf,a.data_per as ud,c.fun_per as bf,c.data_per as bd,b.rules as jf,b.data_per as jd,a.id as userid")->join("a join boss_auth_group b on a.group_id=b.id join boss_user_department c on a.dept_id=c.id")->where("a.id in (".$res['adduser'].",".$data_55['x13166f_9'].")")->select();
        		foreach ($rule as $key => $value) {
        			if($value['userid']==$data_55['x13166f_9'])$touserrule=$value;
        			else $fromuserrule=$value;
        		}
        		$arr_alldiff_f=array();
        		$arr_alldiff_d=array();
        		foreach ($fromuserrule as $key => $value) {
        			if($key=='userid')continue;
        			$arr_fromuser=explode(',', $value);
        			$arr_touser=explode(',', $touserrule[$key]);
        			$arr_inter=array_intersect($arr_fromuser,$arr_touser);
        			$arr_diff=array_diff($arr_fromuser, $arr_inter);
        			if(substr($key, 1,1)=='f')$arr_alldiff_f=array_merge($arr_alldiff_f,$arr_diff);
        			else $arr_alldiff_d=array_merge($arr_alldiff_d,$arr_diff);
        		}

        		M('user')->where("id = ".$data_55['x13166f_9'])->save(array('fun_per'=>$touserrule['uf'].','.implode(',', $arr_alldiff_f),'data_per'=>$touserrule['ud'].','.implode(',', $arr_alldiff_d)));

			}

			//将所有相关提醒设为已读
			$prompt_information = M('prompt_information');
			if(I('post.lcid')){
				$info_ = $prompt_information->where(array("oa_number"=>I('post.lcid')))->save(array("status"=>1));
			}
			//将所有未知原因导致的相关待办理设为已办理
			M('oa_tixing')->where("liuchenid=".I('post.lcid')." && is_check=0")->save(array('is_check'=>1));
			echo json_encode(array('status'=>2,'url'=>'/OA/Index/main.html?mainurl=/OA/Index/loading'));
		}else{
			echo json_encode(array('status'=>1,'url'=>'/OA/Index/xuanzeuser?lcid='.$res['liuchenid'].'&sortid='.$nowsort.'&txid='.I('post.txid')));
		}
	}
	public function check_ok2(){
		//记录审核历史
		M('oa_tixing')->where("id=".I('post.txid'))->save(array('isunset'=>1));
		$data=M('oa_tixing')->where("id=".I('post.txid'))->find();
		if($data['juser_detail']!=''){
			$arr=explode(',', $data['juser_detail']);
			$newarr=array();
			foreach ($arr as $key => $value) {
				if($value!=$_SESSION['userinfo']['uid'])$newarr[]=$value;
			}
			$this->actionlog('通过审核',I('post.lcid'),I('post.jdid'));
			if(count($newarr)>0)M('oa_tixing')->where("id=".I('post.txid'))->save(array('juser_detail'=>implode(',', $newarr)));
			else M('oa_tixing')->where("id=".I('post.txid'))->save(array('juser_detail'=>''));
			echo json_encode(array('status'=>1,'msg'=>'办理成功'));
		}else{
			echo json_encode(array('status'=>2,'msg'=>'您已经办理过了'));
		}
	}
	public function unsetoa(){
		//撤销流程
		M('oa_tixing')->where("liuchenid='".I('get.lcid')."' && is_check=0")->delete();
		M('oa_tixing')->where("id=".I('get.txid'))->save(array('is_check'=>0));
		$data_tx=M('oa_tixing')->join('a join boss_oa_jiedian b on a.jiedianid=b.id')->where("a.id=".I('get.txid'))->find();
		M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->save(array('nowsort'=>$data_tx['sort']));
		$this->success('撤销完成');
	}
	public function check_del(){
		M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->save(array('isok'=>1));//流程结束
		echo '流程结束';
	}
	public function lc_detail(){//流程详细页
		$this->display();		
	}
	public function lc_detail_if(){
		$res=M('oa_liuchen_m')->field('c.name,a.id,a.tablestyle,c.shenghejilu,c.alldata,a.tablekeylist,c.nowsort,c.file,c.filename,a.name as printname')->join("a join boss_oa_liuchen c on c.mid=a.id")->where("c.liuchenid='".I('get.id')."'")->find();
		// print_r(M('oa_liuchen_m')->getLastsql());exit;
		$tabledata=M('oa_liuchen')->join("a join boss_oa_{$res['id']} b on a.alldata=b.id")->where("a.liuchenid='".I('get.id')."'")->find();
		foreach ($tabledata as $key => $value) {
			if(substr($value, 0,2)=='@@')$tabledata[$key]=explode('@@', substr($value,2));
			else $tabledata[$key]=htmlspecialchars_decode($value);
		}
		$this->tabledata=json_encode($tabledata);
		if($tabledata['chundanghao']!=''){
			$this->cdpath=$tabledata['chundangpath'];
			$this->cdhao=$tabledata['chundanghao'];
			$this->iscd=1;
		}
		if($tabledata['shenghejilu']!=''){
			$shenghejilu=json_decode($tabledata['shenghejilu'],true);
			foreach ($shenghejilu as $key => $value) {
				$shenghearr[]=$key;
			}
			$nameandkey=M('oa_jiedian')->where("id in (".implode(',', $shenghearr).")")->select();
			foreach ($shenghejilu as $k => $v) {
				foreach ($nameandkey as $key => $value) {
					if($value['id']==$k){
						$arr=array('name'=>$value['name'],'val'=>$v[0]);
						if(!empty($v[2]))$arr['is_qz']=$v[2];
						$shenghedata[$v[1]]=$arr;
					}
				}
			}
			$this->assign('shenghedata',$shenghedata);
		}
		
		$this->res=$res;
		$this->hqyjlist=M('oa_hqyj')->where("liuchenid='".I('get.id')."' && content!=''")->order('id desc')->select();
		$this->blh=M('oa_hqyj')->where("liuchenid='".I('get.id')."'")->order('id desc')->select();
		$data_dic=M('data_dic')->where("code='aabbcc'")->find();
		if($data_dic['name']!=1){
			$this->assign('youknow',0);
		}
		//获取节点流程list
		$proSer         = new \OA\Service\ProcessService();
		$processId      = trim(I('get.id'));
		$processAllList = $proSer->getProcessAllListSer($processId);
		$this->assign("processAllList",$processAllList);
		$this->display();
	}
	public function deloa(){
		//作废流程 
		$lc_data=M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->find();
		if($lc_data['adduser']!=$_SESSION['userinfo']['uid'])$this->error('你不是发起人，不要乱点');
		$id=M('oa_liuchen')->where("liuchenid='".I('get.lcid')."'")->save(array('status'=>0));
		if($id)$this->success('已作废');
		$this->error('失败了');
	}

	public function aabbcc(){
		$data_dic=M('data_dic')->where("code='aabbcc'")->find();
		if($data_dic['name']==1){
			M('data_dic')->where("code='aabbcc'")->save(array('name'=>0));
		}else{
			M('data_dic')->where("code='aabbcc'")->save(array('name'=>1));
		}

	}

	/**
	 * 主框架
	 * @return [type] [description]
	 */
	public function main(){
		$data_id = I("data_id");
		$pid = I("pid");
		$this->assign("pid",$pid);
		$mainurl = trim(I("mainurl"));
		$mainurl = str_replace("?_","&",$mainurl);
		$this->assign("mainurl",$mainurl);

		//获取信息条数
		$ser        = new Service\PromptInformationService();
		$where      = "find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && status=0 && a_link like '%/OA%'";
		$prompcount = $ser->getPromptInformationCountByWhere($where);
		$this->assign("prompcount",$prompcount);

		//获取当前用户oa左边菜单栏
		$ser        = new Service\AuthAccessService();
		$authTree = $ser->getAuthLeftMenuSer(UID,"OA");
		$this->assign("authTree",$authTree);
		// print_r($authTree);exit;

		//判断行政办公管理权限
		$officeTree = $ser->getAuthOffice(UID,233);
		$this->assign("officeTree",$officeTree);

		$this->display();

	}
	public function statustoorther(){
		postDatatoorther(array(array('lineid'=>I('get.lineid'))),I('get.status'),I('get.setid'),I('get.status'));
	}

	/**
	 * 获取当前用户所有的权限菜单
	 * @return [type] [description]
	 */
	function getMyHasMenus(){
		//读取当前用户所拥有的OA二级菜单
		$ser      = new Service\AuthAccessService();
		$authTree = $ser->getOAChildMenu(UID,"OA");
		$res      = array("code"=>500,"data"=>array());
		if($authTree){
			$res = array("code"=>200,"data"=>$authTree);
		}
		$this->ajaxReturn($res);
		// $this->assign("data_lichen",$authTree);
	}

	/**
	 * 读取当前用户设置的快捷连接菜单
	 * @return [type] [description]
	 */
	function getMyOALink(){
		$data_user = M("user")->field("oa_link,id")->where(array("id"=>UID))->find();
		$data_link=array();
		if($data_user['oa_link'] != ''){
			$data_link = M('auth_rule')->where("id in (".$data_user['oa_link'].")")->select();
		}

		$res      = array("code"=>500,"data"=>array());
		$newOAMenu = array();
		if($data_link){
			foreach ($data_link as $k => $v) {
				$one         = array();
				$one["name"] = $v["title"];
				$one["type"] = 1;
				$one["url"]  = $v["name"];
				$one["id"]   = $v["id"];
				$one["mid"]  = $v["img"];
				$one["pid"]  = $v["pid"];
				$newOAMenu[] = $one;
			}
			$res = array("code"=>200,"data"=>$newOAMenu);
		}else{
			//如果没有直接获取当前用户的oa菜单的排在前4位的菜单
			$ser      = new Service\AuthAccessService();
			$authTree = $ser->getOAChildMenu(UID,"OA");
			$authTree = array_slice($authTree, 0, 4);
			$res = array("code"=>200,"data"=>$authTree);
		}
		$this->ajaxReturn($res);
	}
	public function uploadfile(){
		if(!empty($_FILES['myfile']['tmp_name'])){
			$info=$this->uplaodfile('myfile',UPLOAD_OA_FILE_PATH);
	        if(!is_array($info)){
                $this->error('上传依据失败');
                return;
            }
	        $file_name=UPLOAD_OA_FILE_PATH.$info['myfile']['savepath'].$info['myfile']['savename'];
	        $data['filename']=$info['myfile']['name'];
	        $data['file']=substr($file_name,1);
	        echo json_encode(array('data'=>$data,'status'=>'true'));
	    }else{
	    	echo json_encode(array('msg'=>'没有发现文件','status'=>'error'));
	    }
	}
}


