<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Think\Model;
use Common\Service;
class TargetController extends BaseController {
	public function setTarget(){//目标设置
		$data=D('Target')->getdatalist();
		$getData = D('Target')->getSum();
		$userlist2=M('user')->field('a.id,a.real_name,b.name as groupname,d.title as posttype')->join('a join boss_user_department b on a.dept_id=b.id join boss_auth_group_access c on a.id=c.uid join boss_auth_group d on c.group_id=d.id')->group('a.id')->select();
		$this->assign('userlist2',$userlist2);
		$this->assign('data',$data);
		$this->assign('getSum',$getData);
		$this->grouplist=M('user_department')->select();
		$this->display();
	}

	public function projectTarget(){//项目目标设置
		$this->display();
	}

	public function projectShow(){
		$mon = I('get.mon');
		if($mon){
			$years = $mon;
		}else{
			$years = date('Y');
		}
		$sum_data = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years."")->find();
		$fenFa = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years." and bl_id=1")->find();
		$ssp = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years." and bl_id=2")->find();
		$yingXiao = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years." and bl_id=47")->find();
		$shangJia = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years." and bl_id=48")->find();
		$faXing = M('project_target')->field('round(sum(money),2) as money')->where("years=".$years." and bl_id=46")->find();

		//月份明细
		$monthData = M('project_target')->field('round(sum(money),2) as sum_money,months')->where("years=".$years."")->group('months')->select();//每个月对应的总金额
		foreach($monthData as $key=>$val){
			$monthData[$key]['months'] = $val['months'];

			//每个月业务线明细
			$mxData = M('project_target')->field('sum(money) as money,bl_id,id')->where("years=".$years." and months=".$val['months']."")->group('bl_id')->select();
			foreach($mxData as $key2=>$val2){
				if($val2['bl_id'] == 1){
					$monthData[$key]['ff'] = $val2['money'];
				}elseif($val2['bl_id'] == 2){
					$monthData[$key]['ssp'] = $val2['money'];
				}elseif($val2['bl_id'] == 47){
					$monthData[$key]['yx'] = $val2['money'];
				}elseif($val2['bl_id'] == 48){
					$monthData[$key]['sj'] = $val2['money'];
				}elseif($val2['bl_id'] == 46){
					$monthData[$key]['fx'] = $val2['money'];
				}
				$monthData[$key]['bl_id'] = $val2['bl_id'];
				$monthData[$key]['id'] = $val2['id'];
			}
		}
		$this->ajaxReturn(array('sums'=>$sum_data['money'],'ff'=>$fenFa['money'],'ssp'=>$ssp['money'],'yx'=>$yingXiao['money'],'sj'=>$shangJia['money'],'fx'=>$faXing['money'],'data'=>$monthData));

	}

	public function save(){//修改
		$uid       = UID;
		$time      = date('y-m-d h:i:s',time());
		$save_data = I('get.save_data');
		$pt        = M('project_target');
		$i         =0;
		$res       = "";
		foreach($save_data as $key=>$val){
			$Data = $pt->field('id')->where("bl_id=".$val["bl_id"]." and years=".$val["year"]." and months=".$val["month"]."")->find();
			if(!empty($Data)) {
				$upData = array('money'=>$val["money"],'uid'=>$uid,'addtime'=>$time);
				$row = M('project_target')->where(array("bl_id"=>$val["bl_id"],"years"=>$val["year"],"months"=>$val["month"]))->save($upData);
				if($row){
					$i++;
				} else {
					$res .= '修改：'.$pt->getError() . ",";
				}
			}else{
				$addData            = array();
				$addData['bl_id']   = $val["bl_id"];
				$addData['years']   = $val["year"];
				$addData['months']  = $val["month"];
				$addData['money']   = $val["money"];
				$addData['uid']     = $uid;
				$addData['addtime'] = $time;
				if ($pt->add($addData) === false) {
					$res .= '新增'.$pt->getError() . ",";
				}else{
					$i++;
				}
			}
		}
		if($i>=12){
			$this->ajaxReturn("TRUE");
		}else{
			$this->ajaxReturn($res);
		}
	}

	/*项目目标设置导入*/
	public function uploadProject(){
		$y = I('post.year_ye');//年份
		$time = date('Y-m-d h:i:s',time());
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
		$keyvaluearray=array('月份'=>'months','优效SSP平台'=>2,'优效分发平台'=>1,'优效营销平台'=>47,'优效商家平台'=>48,'优效发行平台'=>46);
		$tarr=array(
			'1月'=>1,
			'2月'=>2,
			'3月'=>3,
			'4月'=>4,
			'5月'=>5,
			'6月'=>6,
			'7月'=>7,
			'8月'=>8,
			'9月'=>9,
			'10月'=>10,
			'11月'=>11,
			'12月'=>12
		);
		$msg='导入成功';
		$pt = M('project_target');
		$res = array();
		foreach ($data as $key => $value) {
			$blData = array();
			$mon = $tarr[$value['月份']];

			foreach ($value as $k => $v) {
				if((int)$keyvaluearray[$k] >0){
					$blData[$keyvaluearray[$k]]['bl_id'] = $keyvaluearray[$k];
					$blData[$keyvaluearray[$k]]['money'] = $v;
					$blData[$keyvaluearray[$k]]['months'] = $mon;
				}
			}
			$res[] = $blData;

		}
		foreach($res as $key=>$val){

			foreach($val as $key2=>$vl){

				$add_data = array();
				$add_data['uid'] = UID;
				$add_data['addtime'] = $time;
				$add_data['years'] = $y;
				$add_data['months'] = $vl['months'];
				$add_data['bl_id'] = $vl['bl_id'];
				$add_data['money'] = $vl['money'];
				$re = $pt->field('id')->where("years=" . $y. " && months=" .$vl['months']. " && bl_id=".$vl['bl_id']."")->find();
				//判断新增或修改
				if ($re['id']>0){
					$pt->where("id=".$re['id']."")->save($add_data);
				}else{
					$pt->add($add_data);
				}
			}

		}

		$this->assign('data',$msg);
		$this->display('Public/alertpage');
		return;
	}

	public function downloadtargetlist(){
		$data=D('Target')->getdatalist();
        foreach ($data as $key => $value) {
            $data[$key]['date']=$value['strdate'].'至'.$value['enddate'];
        }
        $list=array(array('id','序号'),array('real_name','姓名'),array('groupname','所属部门'),array('title','岗位类型'),array('postrule','岗位指标'),array('type','考核类型'),array('target','考核指标'),array('month','所属月份'));
        $this->downloadlist($data,$list,'目标设置列表');
	}
	public function uploadTargetForExcel(){//导入目标
		$info=$this->uplaodfile('file',UPLOAD_ORTHER_EXCEL_PATH);

        if(!is_array($info)){
            $this->assign('data','上传文件失败');
            $this->display('Public/alertpage');
            return;
        }
        $grouplist=M('user_department')->select();
        $garr=array();
        foreach ($grouplist as $key => $value) {
        	$garr[$value['name']]=$value['id'];
        }
        $userinfo=M('user')->field('a.id,a.real_name,b.name')->join('a join boss_user_department b on a.dept_id=b.id')->select();
        $uarr=array();
        foreach ($userinfo as $key => $value) {
        	$uarr[$value['real_name'].$value['name']]=$value['id'];
        }
    	$file_name=UPLOAD_ORTHER_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        $keyvaluearray=array('所属部门'=>'groupid','考核类型'=>'type','指标'=>'target','姓名'=>'uid','所属月份'=>'month','岗位目标'=>'rule');
       	$tarr=array(
			'毛利润'=>1,
			'收入流水'=>2,
			'成本流水'=>3
		);
		$msg='导入成功';
		$error=array();
        foreach ($data as $key => $value) {
            $newdata=array();
            foreach($value as $k => $v){
            	if($keyvaluearray[$k]=='rule'){
            		$rule=$v;
            		continue;
            	}
                $newdata[$keyvaluearray[$k]]=$v;
            }
            if($newdata['target']=='')continue;
            $uname=$newdata['uid'];
            $newdata['uid']=$uarr[$newdata['uid'].$newdata['groupid']];
            if($newdata['uid']==''){
            	$error[]=$uname;
            	continue;
            }
            $newdata['type']=$tarr[$newdata['type']];
            $res=M('target')->where("uid=".$newdata['uid']." && month='".$newdata['month']."'")->find();
            if(!$res)$id=M('target')->add($newdata);
            elseif($res['month']>=date('Ym'))$id=M('target')->where("id=".$res['id'])->save($newdata);
            else $error[]=$uname;
            M('userrule')->where('uid='.$newdata['uid'])->save(array("postrule"=>$rule));
            var_dump($id);
        }
        if(count($error)>0)$msg='姓名为：'.implode(',',$error).'的数据导入失败，没有找到相应员工或已过可修改时间';
        $this->assign('data',$msg);
        $this->display('Public/alertpage');
        return;
	}
	public function addTarget(){//新增目标
		//echo I('post.uid');exit;
		if(empty(I('post.uid')))$_POST['uid']=I('post.id');
		$data=D('Target')->getonedata("uid=".I('post.uid'));
		
		if($data)$this->error('此员工已有目标，请勿重复新增');
		foreach (I('post.month') as $key => $value) {
			if($value=='')continue;
			$data=D('Target')->getonedata("uid=".I('post.uid')." && month='".$value."'");
			if($data)D('Target')->edit("id=".$data['id'],array('target'=>I('post.target')[$key],'type'=>I('post.type')[$key]));
			else D('Target')->adddata(array('target'=>I('post.target')[$key],'uid'=>I('post.uid'),'month'=>$value,'type'=>I('post.type')[$key]));
		}
		D('Target')->deldata("uid=".I('post.uid')." && month not in ('".implode("','",I('post.month'))."')");

			$res=D('Userrule')->getonedata("uid=".I('post.uid'));
			if(!$res){
				$group=M('user')->where("id=".I('post.uid'))->find();
				$id=D('Userrule')->adddata(array('postrule'=>I('post.postrule'),'uid'=>I('post.uid'),'groupid'=>$group['dept_id']));
			}else $id=D('Userrule')->edit("id=".$res['id'],array('postrule'=>I('post.postrule')));
		

        $this->assign('data','操作成功');
        $this->display('Public/alertpage');
		echo "<script language='javascript'>window.location.href='/Target/setTarget'</script>";exit;
		//echo "操作成功";exit;
	}
	public function changeTarget(){//修改目标
		if(empty(I('post.uid')))$_POST['uid']=I('post.id');
		$data=D('Target')->getonedata("uid=".I('post.uid'));
		foreach (I('post.month') as $key => $value) {
			if($value=='')continue;
			$data=D('Target')->getonedata("uid=".I('post.uid')." && month='".$value."'");
			if($data)D('Target')->edit("id=".$data['id'],array('target'=>I('post.target')[$key],'type'=>I('post.type')[$key]));
			else D('Target')->adddata(array('target'=>I('post.target')[$key],'uid'=>I('post.uid'),'month'=>$value,'type'=>I('post.type')[$key]));
		}
		D('Target')->deldata("uid=".I('post.uid')." && month not in ('".implode("','",I('post.month'))."')");
		$res=D('Userrule')->getonedata("uid=".I('post.uid'));
		if(!$res){
			$group=M('user')->where("id=".I('post.uid'))->find();
			$id=D('Userrule')->adddata(array('postrule'=>I('post.postrule'),'uid'=>I('post.uid'),'groupid'=>$group['dept_id']));
		}else $id=D('Userrule')->edit("id=".$res['id'],array('postrule'=>I('post.postrule')));
		$this->assign('data','操作成功');
        $this->display('Public/alertpage');

	}
	public function getOneUserTargetDataApi(){
		$userruledata=M('user')->field('a.id,a.real_name,b.name as groupname,d.title as posttype,e.postrule')->join('a join boss_user_department b on a.dept_id=b.id join boss_auth_group_access c on a.id=c.uid join boss_auth_group d on c.group_id=d.id left join boss_userrule e on a.id=e.uid')->where("a.id=".I('post.id'))->group('a.id')->find();
		$usertargetdata=D('Target')->getdata("uid=".I('post.id'));
		echo json_encode(array('udata'=>$userruledata,'tdata'=>$usertargetdata));
	}

	public function delOneUserTarget(){//删除当前员工所有目标信息
		$id=D('Target')->deldata("uid=".I('get.id'));
		if($id){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
	}
	public function setUserrule(){//部门提成规则设置
		$grouplist = D('Userrule')->getgrouplist("a.usertype=1");
		$id        = array();
		foreach ($grouplist as $key => $value) {
			$id[] = $value['groupid'];
		}
		if(count($id)>0) $where = "id in (".implode(',',$id).")";
		else $where = '1=0';
		// $grouplist2       = M('user_department')->where($where)->select();
		// $this->grouplist3 = M('user_department')->select();

		$data = D('Userrule')->getdatalist();
		$this->assign('grouplist',$grouplist);
		// $this->assign('grouplist2',json_encode($grouplist2));
		$this->assign('data',$data);
		$this->display();
	}

	public function uploadUserruleForExcel(){//导入部门规则
		$info=$this->uplaodfile('file',UPLOAD_ORTHER_EXCEL_PATH);

        if(!is_array($info)){
            $this->assign('data','上传文件失败');
            $this->display('Public/alertpage');
            return;
        }
    	$file_name=UPLOAD_ORTHER_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
    	$grouplist=M('user_department')->select();
        $garr=array();
        foreach ($grouplist as $key => $value) {
        	$garr[$value['name']]=$value['id'];
        }
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        $keyvaluearray=array('所属部门'=>'groupid','考核类型'=>'type','最低毛利指标'=>'minmoney','提成基数/系数'=>'rule');
        $tarr=array(
			'毛利润'=>1,
			'收入流水'=>2,
			'成本流水'=>3
		);
		$msg='导入成功';
		$error=array();
        foreach ($data as $key => $value) {
            $newdata=array();
            foreach($value as $k => $v){
                $newdata[$keyvaluearray[$k]]=$v;
            }
            if($newdata['rule']=='')continue;

            $newdata['usertype']=1;
            $uname=$newdata['groupid'];
            $newdata['groupid']=$garr[$newdata['groupid']];
            if($newdata['groupid']==''){
            	$error[]=$uname;
            	continue;
            }
            $newdata['type']=$tarr[$newdata['type']];
            $res=M('userrule')->where("groupid=".$newdata['groupid']." && usertype=1")->find();
            if(!$res)$id=M('userrule')->add($newdata);
            else $id=M('userrule')->where("id=".$res['id'])->save($newdata);
        }
		if(count($error)>0)$msg='名称为：'.implode(',',$error).'的数据导入失败，没有找到相应部门';
        $this->assign('data',$msg);
        $this->display('Public/alertpage');
        return;

	}
	public function uploadOneUserruleForExcel(){
		$info=$this->uplaodfile('file',UPLOAD_ORTHER_EXCEL_PATH);

        if(!is_array($info)){
            $this->assign('data','上传文件失败');
            $this->display('Public/alertpage');
            return;
        }
        $grouplist=M('user_department')->select();
        $garr=array();
        foreach ($grouplist as $key => $value) {
        	$garr[$value['name']]=$value['id'];
        }
        $userinfo=M('user')->field('a.id,a.real_name,b.name')->join('a join boss_user_department b on a.dept_id=b.id')->select();
        $uarr=array();
        foreach ($userinfo as $key => $value) {
        	$uarr[$value['real_name'].$value['name']]=$value['id'];
        }
    	$file_name=UPLOAD_ORTHER_EXCEL_PATH.$info['file']['savepath'].$info['file']['savename'];
        if(substr($info['file']['savename'],-4)=='xlsx')$exceltype='Excel2007';
        else $exceltype='Excel5';
        $data=$this->exceltoarray($file_name,$exceltype);
        $keyvaluearray=array('所属部门'=>'groupid','考核类型'=>'type','提成系数'=>'rule','姓名'=>'uid');
       	$tarr=array(
			'毛利润'=>1,
			'收入流水'=>2,
			'成本流水'=>3
		);
		$msg='导入成功';
		$error=array();
        foreach ($data as $key => $value) {
            $newdata=array();
            foreach($value as $k => $v){
                $newdata[$keyvaluearray[$k]]=$v;
            }
            if($newdata['rule']=='')continue;
            $newdata['rule']=$newdata['rule']*100;
            $newdata['usertype']=2;
            $uname=$newdata['uid'];
            $newdata['uid']=$uarr[$newdata['uid'].$newdata['groupid']];
            if($newdata['uid']==''){
            	$error[]=$uname;
            	continue;
            }
            $newdata['groupid']=$garr[$newdata['groupid']];
            $newdata['type']=$tarr[$newdata['type']];
            $res=M('userrule')->where("uid=".$newdata['uid']." && usertype=2")->find();
            if(!$res)$id=M('userrule')->add($newdata);
            else $id=M('userrule')->where("id=".$res['id'])->save($newdata);
        }
		if(count($error)>0)$msg='姓名为：'.implode(',',$error).'的数据导入失败，没有找到相应员工';
        $this->assign('data',$msg);
        $this->display('Public/alertpage');
        return;
	}
	public function addUserrule(){
		$rulearr=array();
		foreach (I('post.num') as $key => $value) {
			if($value=='')continue;
			$rulearr[]=$value.','.I('post.money')[$key];
		}
		if(!empty(I('post.id'))){
			$id=D('Userrule')->edit("id=".I('post.id'),array('groupid'=>I('post.groupid'),'minmoney'=>I('post.minmoney'),'type'=>I('post.type'),'rule'=>implode('|', $rulearr),'usertype'=>1));
		}else $id=D('Userrule')->adddata(array('groupid'=>I('post.groupid'),'minmoney'=>I('post.minmoney'),'type'=>I('post.type'),'rule'=>implode('|', $rulearr),'usertype'=>1));
		if($id){
			$this->assign('data','操作成功');
	        $this->display('Public/alertpage');
	        return;
        }else{
        	$this->assign('data','操作失败');
	        $this->display('Public/alertpage');
	        return;
        }
	}

	public function setOneUserrule(){//个人提成规则设置
		$data["depart_id"] = trim(I("depart_id"));
		$data["name_u"]    = trim(I("name_u"));
		$this->assign("map",$data);

		$where = " where uu.usertype=2";
		if($data["depart_id"]){
			$where .= " and uu.groupid=".$data["depart_id"];
		}
		if($data["name_u"]){
			$where .= " and uu.uid=".$data["name_u"];
		}

		//数据权限
        $arr_name=array();
        $arr_name['user']=array('uu.uid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        $where .= " and $myrule_data";


		$sql = "SELECT 
				uu.id,
				hr.`user_name`,
				dep.`name`,
				po.`name` as duty,
				uu.`rule`,
				uu.`in_num`,
				uu.`out_num`
				 FROM `boss_userrule` AS uu
				LEFT JOIN `boss_oa_hr_manage` AS hr ON uu.`uid`=hr.`user_id`
				LEFT JOIN `boss_user_department` AS dep ON dep.id=hr.`leve_depart_id`
				LEFT JOIN `boss_oa_position` AS po ON po.id=hr.`duty`";
		$sql_count = "SELECT 
				count(1) as no
				 FROM `boss_userrule` AS uu
				LEFT JOIN `boss_oa_hr_manage` AS hr ON uu.`uid`=hr.`user_id`
				LEFT JOIN `boss_user_department` AS dep ON dep.id=hr.`leve_depart_id`
				LEFT JOIN `boss_oa_position` AS po ON po.id=hr.`duty`";
		$model     = new \Think\Model();
		$sql_count .= $where;
		$count     = $model->query($sql_count);
		$count     = $count[0]["num"];

		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$sql      .= $where." limit ".$page->firstRow.",".$page->listRows;
		$list     = $model->query($sql);
		$this->assign("list",$list);
		$this->assign("page",$page->show());
		$this->display();
	}

	/**
	 * 添加个人提成规则
	 */
	public function addOneUserrule(){
		$id = trim(I('post.rule_id'));
		//公共参数
		$data["minmoney"] = trim(I('post.minmoney'));
		$data["type"]     = trim(I('post.type'));
		$data["rule"]     = trim(I('post.num'));
		$data["groupid"]  = trim(I("leve_depart_id"));
		$data["usertype"] = 2;
		$data["uid"]      = trim(I('post.uid'));
		$data["in_num"]   = trim(I('in_num'));
		$data["out_num"]  = trim(I('out_num'));
		if($id>0){
			$id = M("userrule")->where(array("id"=>$id))->save($data);//个人--编辑
		}else{
			$res = M("userrule")->field("id")->where(array("uid"=>I('post.uid')))->find();
			if($res){
				$id = M("userrule")->where(array("id"=>$res["id"]))->save($data);//个人--编辑
			}else{
				$id = M("userrule")->add($data);//个人--添加
			}
		} 
		unset($data);
		if($id){
			$this->assign('data','操作成功');
	        $this->display('Public/alertpage');
	        return;
        }else{
        	$this->assign('data','操作失败');
	        $this->display('Public/alertpage');
	        return;
        }
	}

	/**
	 * [获取个人提成规则 description]
	 * @return [type] [description]
	 */
	public function editsetOneUser(){//
		$id = trim(I("id"));
		$sql = "SELECT 
					uu.id,
					hr.`user_name`,
					dep.`name` as depart_name,
					po.`name` as duty,
					uu.`rule`,
					uu.`in_num`,
					uu.`out_num`,
					uu.uid
				 FROM `boss_userrule` AS uu
				LEFT JOIN `boss_oa_hr_manage` AS hr ON uu.`uid`=hr.`user_id`
				LEFT JOIN `boss_user_department` AS dep ON dep.id=hr.`leve_depart_id`
				LEFT JOIN `boss_oa_position` AS po ON po.id=hr.`duty`
				WHERE uu.usertype=2 and uu.id={$id}";
		$model = new \Think\Model();
		$one = $model->query($sql);
		$one = $one[0];

		$this->assign("data",$one);
		$this->display();
	}

	public function getUserDataApi(){//获取个人提成规则
		$data=D('Userrule')->getonedata("id=".I('post.id'));
		
		$data['rule']=tieredprice_decode($data['rule'],1);
		echo json_encode($data);
	}

	public function delOneUserrule(){//删除个人提成规则
		$id=D('Userrule')->deldata("id=".I('get.id'));
		if($id){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
	}

	/**
	 * //业绩统计
	 * @return [type] [description]
	 */
	public function performanceStatistics(){
		//销售
		if(empty(I('get.year'))){
			$_GET['year']  = date('Y');
			$_GET['month'] = date('m');
		}
		$bumenlist    = M('user_department')->select();
		$data_saler   = D('Daydata')->getPerformanceStatisticsList();
		$allmoney     = 0;
		$mubiao_saler = array();
		$siji_saler   = array();
		$lv_saler     = array();
		$name_saler   = array();
		foreach ($data_saler as $key => $value) {
			$allmoney       +=$value['inmoney'];
			$mubiao_saler[] =twonum($value['target']);
			$siji_saler[]   =(float)twonum($value['inmoney']);
			$lv_saler[]     =($value['target']==0)?0:$value['inmoney']/$value['target']*100;
			$name_saler[]   =$value['real_name'];
		}

		//销售成本获取
		$cb_list = $this->getGeRenOut();
		foreach ($data_saler as $key => $value) {
			$data_saler[$key]['gongxianlv'] = ($value['inmoney']/$allmoney)*100;
			//预估提成=收入流水提成+毛利提成+成本提成
			//毛利=收入-成本
			$chengBen                     = $cb_list[$value["salerid"]]["out_newmoney"];
			$data_saler[$key]["chengBen"] = $chengBen;
			$maoli                        = $value["inmoney"]-$chengBen;
			$data_saler[$key]["maoli"]    = $maoli;
			$chengBen_                    = $chengBen*$value["out_num"]/100;
			$maoli_                       = $maoli*$value["rule"]/100;
			$shouru_                      = $value["inmoney"]*$value["in_num"]/100;
			$yugutichen                   = $chengBen_+$maoli_+$shouru_;
			unset($chengBen);
			unset($maoli);
			unset($chengBen_);
			unset($maoli_);
			unset($shouru_);
			$data_saler[$key]["yugutichen"] = $yugutichen;//预估提成

		}
		$this->assign('imgdata_saler',array('mubiao_saler'=>json_encode($mubiao_saler),'siji_saler'=>json_encode($siji_saler),'lv_saler'=>'['.implode(',',$lv_saler).']','name_saler'=>json_encode($name_saler)));
		$this->assign('bumen',$bumenlist);
		$this->assign('data_saler',$data_saler);
		//商务
		$data_super        = D('DaydataOut')->getPerformanceStatisticsList();
		//var_dump($data_super);
		$data_super_orther = D('Daydata')->getOrtherSuperData();//获取有收入无成本划归此商务的部分利润
		$nullsuperuserarr  = array(1=>633,31=>633,2=>485);
		foreach ($data_super_orther as $k_or => $v_or) {
			if(empty($v_or['userid'])){
				$v_or['userid'] = $nullsuperuserarr[$v_or['lineid']];
			}
			$orthermoney[$v_or['userid']] += $v_or['money'];
		}
		foreach ($data_super as $key => $value) {
			$data_super[$key]['inmoney']    =$value['inmoney']+$orthermoney[$value['userid']];
			$data_super[$key]['maoli']      =$value['maoli']+$orthermoney[$value['userid']];
			$data_super[$key]['wanchenlv']  =twonum(($data_super[$key]['maoli']/$data_super[$key]['target'])*100);
			$data_super[$key]['maolilv']    =twonum(($data_super[$key]['maoli']/$data_super[$key]['inmoney'])*100);

			$data_super[$key]['yugutichen'] =twonum($data_super[$key]['maoli']*$data_super[$key]['rule']);
		}
		$allmoney     =0;
		$mubiao_super =array();
		$siji_super   =array();
		$lv_super     =array();
		$name_super   =array();
		foreach ($data_super as $key => $value) {
			$allmoney       +=$value['maoli'];
			$mubiao_super[] =twonum($value['target']);
			$siji_super[]   =(float)twonum($value['maoli']);
			
			$lv_super[]     =($value['target']==0)?0:$value['inmoney']/$value['target']*100;
			$name_super[]   =$value['real_name'];
		}

		foreach ($data_super as $key => $value) {
			$data_super[$key]['gongxianlv']=($value['maoli']/$allmoney)*100;
		}
		foreach ($lv_saler as $key => $value) {
			if($value==INF)$lv_saler[$key]=0;
		}
		$this->assign('imgdata_super',array('mubiao_saler'=>json_encode($mubiao_super),'siji_saler'=>json_encode($siji_super),'lv_saler'=>json_encode($lv_super),'name_saler'=>json_encode($name_super)));
		$this->assign('bumen',$bumenlist);
		//print_r($data_super);exit;
		$this->assign('data_super',$data_super);
		$this->display();
	}

	/**
	 * 获取个人成本
	 * @return [type] [description]
	 */
	private function getGeRenOut(){
		$year = (!empty(I('get.year')))?I('get.year'):date('Y');
    	$month = (!empty(I('get.month')))?I('get.month'):date('m');
    	if(strlen($month)==1) $month = '0'.$month;
		$sql = "SELECT 
				  in_salerid,
				  SUM(out_newmoney) as total_out_money
				FROM
				  `boss_daydata_inandout` 
				WHERE adddate like '{$year}-{$month}%' 
				GROUP BY in_salerid ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list) return false;
		$list_ = array();
		foreach ($list as $k => $v) {
			$list_[$v["in_salerid"]]["out_newmoney"] = $v["total_out_money"];
		}
		unset($list);
		unset($sql);
		unset($model);
		return $list_;
	}



	//销售贡献
	public function salergxl(){
		$gongxian_saler=D('Daydata')->getgongxian();
		$allmoney_saler=array();
		$data_name_saler=array();
		$salername_arr=array();
		$salermonth=array();
		foreach ($gongxian_saler as $key => $value) {
			$allmoney_saler[$value['month']]+=$value['inmoney'];
			if(!in_array($value['month'],$salermonth))$salermonth[]=$value['month'];
		}
		foreach ($gongxian_saler as $key => $value) {
			$gongxian_saler[$key]['gxl']=$value['inmoney']/$allmoney_saler[$value['month']]*100;
		}
		foreach ($gongxian_saler as $key => $value) {
			if(empty($data_name_saler[$value['real_name']]))$salername_arr[]=$value['real_name'];
			$data_name_saler[$value['real_name']][$value['month']]=$value['gxl'];
		}
		$this->assign('salermonth',$salermonth);
		$this->assign('salername_arr',$salername_arr);
		$this->assign('data_name_saler',$data_name_saler);
		$this->display();
	}
	//商务贡献
	public function busergxl(){
		$gongxian_super=D('DaydataOut')->getgongxian();
		$allmoney_super=array();
		$data_name_super=array();
		$supername_arr=array();
		$supermonth=array();
		foreach ($gongxian_super as $key => $value) {
			$allmoney_super[$value['month']]+=$value['inmoney'];
			if(!in_array($value['month'],$supermonth))$supermonth[]=$value['month'];
		}
		foreach ($gongxian_super as $key => $value) {
			$gongxian_super[$key]['gxl']=$value['inmoney']/$allmoney_super[$value['month']]*100;
		}
		foreach ($gongxian_super as $key => $value) {
			if(empty($data_name_super[$value['real_name']]))$supername_arr[]=$value['real_name'];
			$data_name_super[$value['real_name']][$value['month']]=$value['gxl'];
		}
		$this->assign('supermonth',$supermonth);
		$this->assign('supername_arr',$supername_arr);
		$this->assign('data_name_super',$data_name_super);
		$this->display();
	}
	public function bumentichen(){
		if(!empty(I('get.year')))$y=I('get.year');
		else $y=date('Y');
		
		if(!empty(I('get.month'))){
			$m=I('get.month');
			if(strlen($m)==2)$time2=$y.'-'.$m;
			else $time2=$y.'-0'.$m;
		}else{
			$m=date('m');
			$time2=$y.date('-m');
		}
		$time=$y;

		$allgxdata=array();
		$userrule=M('userrule')->where("usertype=1")->select();
		foreach ($userrule as $key => $value) {
			$rulearr[$value['groupid']]=$value;
		}

		//数据权限
        $arr_name=array();
        $arr_name['line']=array('a.in_lineid','a.out_lineid');
        $arr_name['user']=array('a.in_salerid','a.out_businessid');
        $ruleser=new Service\RuleService();
        $myrule_data=$ruleser->getmyrule_data($arr_name);
        if($myrule_data!='')$where= " && $myrule_data";
        else $where="";

		$bumendata=M('daydata_inandout')->field('left(a.adddate,7) as date,sum(if(a.in_status in (0,9) || a.in_newmoney is null,0,a.in_newmoney)-if(a.out_status in (0,9) || a.out_newmoney is null,0,a.out_newmoney)) AS money, c.id,c.name')->join('a left join boss_user b on a.in_salerid=b.id
left join boss_user d on a.out_businessid=d.id join boss_user_department c on b.dept_id=c.id || d.dept_id=c.id')->where("a.adddate like '$time%'".$where)->group('c.id,left(a.adddate,7)')->select();
		
		foreach ($bumendata as $key => $value) {
			$alldata[$value['date']][$value['id']]=$value;
		}
		foreach ($alldata as $k => $v) {
			$allmoney_bumen=0;
			$bumenarr=array();
			foreach ($v as $key => $value) {
				$allmoney_bumen+=$value['money'];
				$bumenarr[$value['id']]=array('money'=>$value['money'],'name'=>$value['name'],'min'=>$rulearr[$value['id']]['minmoney'],'type'=>$rulearr[$value['id']]['type'],'xs'=>$this->getxs($rulearr[$value['id']]['rule'],$bumenarr[$value['id']]['money']+$value['money']));
			}
			foreach ($bumenarr as $key => $value) {
				$mon=substr($k, 5,2);
				$allgxdata[$value['name']][$mon]=round($value['money']/$allmoney_bumen*100,2);
				if($v['date']==$time2){
					$bumenarr[$key]['tichen']=($value['money']-$value['min'])*$value['xs'];
					$bumenarr[$key]['gxlv']=round(($value['money']/$allmoney_bumen)*100,2);
				}
			}
			if($k==$time2)$bumenarr_e=$bumenarr;

		}
		$allpmoney=M('project_target')->field('sum(a.money) as money,b.dept_id')->join("a join boss_business_line b on a.bl_id=b.id")->where("a.years=$time && a.months=$m")->group('b.dept_id')->select();
		foreach ($bumenarr_e as $key => $value) {
			foreach ($allpmoney as $k => $v) {
				if($key==$v['dept_id'])$bumenarr_e[$key]['wclv']=round(($value['money']/$v['money'])/100,2);
			}
		}
		$this->assign('bumenarr',$bumenarr_e);

		$this->assign('allgxdata',$allgxdata);
		$this->display();
	}
	public function getxs($data,$money){
		$res=tieredprice_decode($data,1);
		foreach ($res as $key => $value) {
			if($money>=$value[0])$return=$value[1];
		}
		return $return;
	}
	public function downloadperformancelist_saler(){
		$data=D('Daydata')->getPerformanceStatisticsList();
		foreach ($data as $key => $value) {
			$data[$key]['gongxianlv']=($value['inmoney']/$allmoney)*100;
		}
		$list=array(array('id','序号'),array('real_name','销售人员'),array('postrule','岗位指标'),array('target','考核指标'),array('inmoney','收入流水'),array('kaipiaolv','收入开票率'),array('huikuanlv','回款率'),array('yejiwanchenlv','业绩完成率'),array('rule','个人提成系数'),array('yugutichen','预估流水提成'),array('gongxianlv','销售业绩贡献率'));
        $this->downloadlist($data,$list,'销售业绩列表');
	}
	public function downloadperformancelist_super(){
		$data=D('DaydataOut')->getPerformanceStatisticsList();
		foreach ($data as $key => $value) {
			$data[$key]['gongxianlv']=($value['maoli']/$allmoney)*100;
		}
		$list=array(array('id','序号'),array('real_name','商务人员'),array('postrule','岗位指标'),array('target','考核指标'),array('maoli','实际毛利润'),array('wanchenlv','业绩完成率'),array('inmoney','收入流水'),array('outmoney','成本流水'),array('maolilv','毛利润率'),array('qurenlv','成本数据确认率'),array('rule','个人提成系数'),array('yugutichen','预估流水提成'),array('gongxianlv','毛利贡献率'));
        $this->downloadlist($data,$list,'销售业绩列表');
	}

	/**
	 * 加载考核人员
	 * @return [type] [description]
	 */
	function lazyRuleUsers(){
		$sql = "SELECT 
				  u.id,
				  u.`real_name`,
				  de.`name` as depart_name,hr.leve_depart_id,
				  p.`name` as duty 
				FROM
				  `boss_user` AS u 
				  LEFT JOIN `boss_oa_hr_manage` AS hr 
				    ON u.id = hr.`user_id` 
				  LEFT JOIN `boss_user_department` AS de 
				    ON de.id = hr.`leve_depart_id` 
				  LEFT JOIN `boss_oa_position` AS p 
				    ON hr.`duty` = p.`id` 
				WHERE u.`status` = 1 ";
		$model = new \Think\Model();
		$list  = $model->query($sql);
		unset($sql);
		$this->ajaxReturn($list);
		unset($list);
	}

	/**
	 * 导入个人提成
	 * @return [type] [description]
	 */
	function importtcInfo(){
		$ruSer = new \Home\Service\ImportDataService();
		$result = $ruSer->importUserRules();
		$this->ajaxReturn($result);
	}
}