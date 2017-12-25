<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
/**
 * 用户管理
 */
class UserController extends BaseController {
	/**
	 * 后台用户管理
	 */
    public function index(){
    	// echo implode(',', $_SESSION['userinfo']['fun_config']);
    	// exit();
	    // $options = array(
		   //  array('type'=>'button', 'id'=>'', 'class'=>'J_addData', 'title'=>'新增', 'url'=>''),
		   //  array('type'=>'a', 'id'=>'', 'class'=>'', 'title'=>'返回人事管理', 'url'=>'/OA/HrManage/hrList.html')
	    // );
	    
	    // $this->assign('toolOptions', $options);

	    $searchOptions = array(
		    array('title'=>'部门名称','name'=>'dept_id','type'=>'select','options'=>M('user_department')->getField('id,name'), 'value'=>$_REQUEST['dept_id']),
		    array('title'=>'姓名','name'=>'real_name','type'=>'text', 'value'=>$_REQUEST['real_name']),
		    array('title'=>'工号','name'=>'employee_number','type'=>'text', 'value'=>$_REQUEST['employee_number']),
		    array('title'=>'职位','name'=>'position_id','type'=>'select','options'=>M('data_dic')->where('dic_type=1')->getField('id,name'), 'value'=>$_REQUEST['position_id']),
	    );
	    $this->assign('searchOptions', $searchOptions);

	    //条件
	    $where = array();
	    $deptId = I('get.dept_id',0,'intval');
		if($deptId>0) {
			$where['dept_id'] = $deptId;
		}
	    $realName = I('get.real_name');
	    if($realName) {
		    $where['real_name'] = array('LIKE',"%{$realName}%");
	    }
	    $employeeNumber = I('get.employee_number',0,'intval');
	    if($employeeNumber>0) {
		    $where['employee_number'] = array('LIKE',"%{$employeeNumber}%");
	    }
	    $positionId = I('get.position_id',0,'intval');
	    if($positionId>0) {
		    $where['position_id'] = $positionId;
	    }

	    $list = $this->lists('User', $where);
	    $this->assign('list', $list);
	    $this->assign('op_user_status', C('OPTION.user_status'));
	    $this->display();

    }

    public function auth(){
    	// echo implode(',', $_SESSION['userinfo']['fun_config']);
    	// exit();
	    // $options = array(
		   //  array('type'=>'button', 'id'=>'', 'class'=>'J_addData', 'title'=>'新增', 'url'=>''),
		   //  array('type'=>'a', 'id'=>'', 'class'=>'', 'title'=>'返回人事管理', 'url'=>'/OA/HrManage/hrList.html')
	    // );
	    
	    // $this->assign('toolOptions', $options);

	    $searchOptions = array(
		    array('title'=>'姓名','name'=>'real_name','type'=>'text', 'value'=>$_REQUEST['real_name']),
		    array('title'=>'工号','name'=>'employee_number','type'=>'text', 'value'=>$_REQUEST['employee_number']),
		    array('title'=>'职位','name'=>'position_id','type'=>'select','options'=>M('data_dic')->where('dic_type=1')->getField('id,name'), 'value'=>$_REQUEST['position_id']),
	    );
	    $this->assign('searchOptions', $searchOptions);

	    //条件
	    $where = array();
	    $deptId = I('get.dept_id',0,'intval');
		if($deptId>0) {
			$where['dept_id'] = $deptId;
		}
	    $realName = I('get.real_name');
	    if($realName) {
		    $where['real_name'] = array('LIKE',"%{$realName}%");
	    }
	    $employeeNumber = I('get.employee_number',0,'intval');
	    if($employeeNumber>0) {
		    $where['employee_number'] = array('LIKE',"%{$employeeNumber}%");
	    }
	    $positionId = I('get.position_id',0,'intval');
	    if($positionId>0) {
		    $where['position_id'] = $positionId;
	    }
	    $data_u=M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
	    if($data_u['jgdept']!=''){
	    	$arr_jg=explode(',', $data_u['jgdept']);
	    }
		$arr_jg[]=$data_u['dept_id'];
		$where_jg[]="dept_id in (".implode(',', $arr_jg).")";
		foreach ($arr_jg as $key => $value) {
			$where_jg[]="find_in_set($value,jgdept)";
		}
		$where['_string']="(".implode(' || ', $where_jg).")";
	    $list = $this->lists('User', $where);
	    $this->assign('list', $list);
	    $this->assign('op_user_status', C('OPTION.user_status'));
	    $this->display();

    }

    public function add() {

		$this->showtable();
		$default["status"] = 1;
		$this->assign('usergroup', 30);
		$this->assign('data', $default);
	    $this->ajaxReturn($this->fetch('edit'));
    }



    public function edit() {

		$uid = I('get.id', 0, 'intval');
		if ($uid > 0) {
			$this->assign('data', D('User')->getDetail($uid));
			$this->assign('usergroup', M('auth_group_access')->where('uid='.$uid)->getField('group_id'));

			$this->showtable();
			$this->ajaxReturn($this->fetch('edit'));
		} else {
			$this->ajaxReturn('参数错误');
		}

    }


    private function showtable () {
	    $dept = M('user_department')->field('*,name AS title')->select();
	    $this->assign('op_dept', D('Common/Tree')->toFormatTree($dept));
	    $this->assign('op_position', M('data_dic')->where('dic_type=1')->getField('id,name'));
	    $this->assign('op_groups', M('auth_group')->where('status=1')->getField('id,title'));
	    $this->assign('op_user_status', C('OPTION.user_status'));
	    $this->assign('op_user_gender', C('OPTION.user_gender'));
    }


    /**
     * 用户详情
     */
    public function detail() {

    	$id = I('get.id', 0, 'intval');
	    if ($id > 0) {
	    	$dept = M('user_department')->getField('id,name');
		    $this->assign('data', D('User')->getDetail($id));
		    $this->assign('dept',$dept);
		    $this->assign('usergroup', M('auth_group_access')->where('uid='.$id)->getField('group_id'));
		    $this->showtable();
		    $this->ajaxReturn($this->fetch('detail'));

	    }

    }

    /**
     * 检查用oa账号，真实姓名在员工表是否存在，不允许存在相同的
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function _checkHrInfoRepeat($hrSer_,$data){
    	$hrSer     = !$hrSer_ ? new Service\HrManageService() : $hrSer_;
    	$where["username_pinyin"] = $data["username_pinyin"];
    	$where["user_name"] = $data["user_name"];
    	$one = $hrSer->getOneHRByWhere($where,"id");
    	$has = false;
    	if($one) $has = true;
    	return $has;
    }


    //修改用户信息
	public function update() {
		$hrSer                    = new Service\HrManageService();
		$editId                   = $id = I('post.id', 0, 'intval');
		
		//同步员工表hr_manage表
		$data_["user_name"]       = trim($_REQUEST["real_name"]);
		$data_["username_pinyin"] = trim($_REQUEST["username"]);
		$data_["depart_id"]       = $_REQUEST["dept_id"];
		$data_["sex"]             = $_REQUEST["gender"];
		$data_["phone"]           = trim($_REQUEST["mobile"]);
		$data_["qq"]              = trim($_REQUEST["qq"]);
		$data_["nation"]          = trim($_REQUEST["ethnic_group"]);
		$data_["profession"]      = trim($_REQUEST["major"]);
		$data_["passby"]          = trim($_REQUEST["address"]);
		
		$data_["post_email"]      = $data_["username_pinyin"]."@yandui.com";
		
		//修改用户group_id--角色
		$data_["group_id"]        = trim($_REQUEST["group_id"]);

		//检查是否修改部门和角色，同时修改用户表的data_updated，fun_updated
		$userSer            = new Service\UserService();
		$udata["uid"]       = $editId;
		$udata["depart_id"] = $data_["depart_id"];
		$udata["group_id"]  = $data_["group_id"];
		$userSer->isNeedSetUserGNDataUpdateTime($udata);
		unset($udata);

		//同步部门名称
		$depSer                     = new Service\DepartSettingService();
		$depInfo                    = $depSer->getOneByWhere(array("id"=>$data_["depart_id"]),"name,pid");
		$data_["depart_name"]       = $depInfo["name"];
		//更新一级部门id
		$leve_id                    = getLeveDepartId($data_["depart_name"],$data_["depart_id"],$depInfo["pid"]);
		$data_["leve_depart_id"]    = $leve_id;
		$_REQUEST["leve_depart_id"] = $leve_id;
		//end 同步员工表hr_manage表

		//添加
		if(!$id){
			//检查用oa账号，真实姓名在员工表是否存在，不允许存在相同的
			$has = $this->_checkHrInfoRepeat($hrSer,$data_);
			if($has){
				$this->ajaxReturn('员工表已存在相同的OA账号和真实姓名，请重新填写');exit;
			}
		}

		$userModel = D('user');
		if ($userModel->create()===false) {
			$this->ajaxReturn($userModel->getError());
		}

		if ($id > 0) {
			//修改
			$meta = '修改';
			$r    =$userModel->save($_REQUEST);
		} else {
			//新增
			$meta                 = '添加';
			$_REQUEST['password'] = boss_md5(123456, UC_AUTH_KEY);
			$r                    = $editId = $userModel->add($_REQUEST);
			if($r !== false){
				//删除用户工号
				$_map['id']              = $editId;
				$_map['employee_number'] = $userModel->generalCode($editId);

				//新增时同步 user表中的uid(表示oa对应) --update 2017-07-06 10:35 tgd
				$user_info_      = M("user")->field("uid,id")->where(array("id"=>$editId))->find();
				$employee_number = 0;
				if($user_info_){
					if(empty($user_info_["uid"])){
						$user_["uid"]    = $user_info_["id"];
						$employee_number = $user_["uid"];
						M("user")->where(array("id"=>$user_info_["id"]))->save($user_);
						unset($user_);
					}else{
						$employee_number = $user_info_["uid"];
					}
					unset($user_info_);
				}
				
				//同步员工表hr_manage表
				$data_["job_no"]         = $employee_number;
				$data_["user_id"]        = $editId;
				$data_["body_no"]        = time();//默认值，避免出错
				//同步员工表hr_manage表

				if ($userModel->save($_map) === false) { //更新失败删除刚添加的广告主
					$userModel->delete($editId);
					$this->ajaxReturn($userModel->getError());
				}
			}
		}
		$is_has = false;
		if ($id > 0) {
		//检查修改时，hr_mangage表是否有数据
			$hr_one = $hrSer->getOneHRByWhere(array("user_id"=>$id),"id");
			if($hr_one) $is_has = true;
		}
		//同步员工表hr_manage表
		if (($id > 0) && ($is_has==true)) {
			//修改时同步 user表中的uid(表示oa对应) --update 2017-07-06 10:35 tgd
			$user_info_      = M("user")->field("uid,id")->where(array("id"=>$id))->find();
			$employee_number = 0;
			if($user_info_){
				if(empty($user_info_["uid"])){
					$user_["uid"]    = $user_info_["id"];
					$employee_number = $user_["uid"];
					M("user")->where(array("id"=>$user_info_["id"]))->save($user_);
					unset($user_);
				}else{
					$employee_number = $user_info_["uid"];
				}
				unset($user_info_);
				$data_["job_no"]         = $employee_number;
			}
			$where_["user_id"] = $id;
			$row               = $hrSer->saveHRData($where_,$data_);
		}else{
			if($id>0){
				$data_["job_no"]    = $id;
				$data_["user_id"]   = $id;
				$data_["body_no"]   = time();//默认值，避免出错
			}
			$data_["creat_uid"] = UID;
			$data_["status"]    = 0;
			$data_["dateline"]  = date("Y-m-d H:i:s",time());
			$row                = $hrSer->addHRData($data_);
		}
		//同步员工表hr_manage表

		if ($r === false) {
			$this->ajaxReturn($userModel->getError());
		}

		//修改用户组
		$AuthGroup = D('AuthGroup');
		$authGroups = I('post.group_id');
		if( $authGroups && !$AuthGroup->checkId($authGroups)){
			$this->ajaxReturn($AuthGroup->error);
		}
		if ( !$AuthGroup->addToGroup($editId, $authGroups) ){
			$this->ajaxReturn($AuthGroup->getError());
		}

		$this->ajaxReturn('ok');
	}



	/**
	 * 重置密码
	 */
	public function resetPwd() {

		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$initpwd = boss_md5(C('INIT_PASSWORD'), UC_AUTH_KEY);
			$res = M('user')->where('id='.$id)->save(array('password'=>$initpwd));
			if ($res) {
				$this->ajaxReturn('密码已重置');
			} else {
				$errorMsg = M('user')->getError();
				if ($errorMsg == '') {
					$this->ajaxReturn('密码已重置');
				} else {
					$this->ajaxReturn($errorMsg);
				}
			}

		} else {
			$this->ajaxReturn('参数错误');
		}

	}


	public function mycenter() {

		$this->display();
	}


	/**
	 * 修改密码提交
	 */
	public function submitPassword(){
		//获取参数
		$password   =   I('post.old');
		empty($password) && $this->ajaxReturn('请输入原密码');
		$data['password'] = I('post.password');
		empty($data['password']) && $this->ajaxReturn('请输入新密码');
		$repassword = I('post.repassword');
		empty($repassword) && $this->ajaxReturn('请输入确认密码');

		if($data['password'] !== $repassword){
			$this->ajaxReturn('您输入的新密码与确认密码不一致');
		}

		$model    = D('User');
		$res    =   $model->updatePassword(UID, $password, $data);
		if($res === false){
			$this->ajaxReturn($model->getError());
		} else {
			$this->ajaxReturn('ok');

		}
	}


}