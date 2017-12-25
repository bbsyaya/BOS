<?php

namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 权限角色管理控制器
 * Class AuthManagerController
 */
class AuthManagerController extends BaseController{

    /**
     * 权限管理首页
     */
    public function index(){

	    $options = array(
		    array('type'=>'button', 'id'=>'', 'class'=>'J_addData', 'title'=>'新增', 'url'=>''),
	    );
	    $this->assign('toolOptions', $options);

	    //搜索 title选项名称 name选项name value设置的值 options选项值 type页面控件类型
	    $searchOptions = array(
		    array('title'=>'角色名称','name'=>'title','type'=>'text','options'=>'', 'value'=>$_REQUEST['title']),
	    );
	    $this->assign('searchOptions', $searchOptions);

	    $where = array();
	    $title = I('get.title','');
	    if (!empty($title)) {
		    $where['title'] = array('like', "%{$title}%");
	    }
	    $list = $this->lists('AuthGroup', $where);
	    $this->assign('list', $list);
	    $this->assign('op_data_range', C('OPTION.data_range'));
	    $this->assign('op_status', C('OPTION.function_status'));
	    $this->display();
    }


	public function add() {

		$this->showtable();
		$this->ajaxReturn($this->fetch('edit'));
	}


	private function showtable() {

		$this->assign('op_status', C('OPTION.function_status'));
		$this->assign('op_data_range', C('OPTION.data_range'));
	}


	public function edit() {

		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$this->assign('data', M('auth_group')->find($id));
			$this->showtable();
			$this->ajaxReturn($this->fetch('edit'));
		} else {
			$this->ajaxReturn('参数错误');
		}

	}

	//保存权限信息
	public function update() {

		$id = I('request.id', 0, 'intval');
		$groupModel = M('auth_group');
		if (empty(I('request.title'))) $this->ajaxReturn('角色名称不能为空');
		if ($id > 0) {
			//修改
			if($groupModel->save($_REQUEST) === false) {
				$this->ajaxReturn($groupModel->getError());
			} else {
				$this->ajaxReturn('ok');
			}
		} else {
			//新增
			$interId = $groupModel->add($_REQUEST);
			if ($interId) {
				$this->ajaxReturn('ok');
			} else {
				$this->ajaxReturn($groupModel->getError());
			}

		}

	}


	/*
	 * 修改权限
	 */
	public function udpateRule() {
		if (intval($_POST['id']) > 0) {
			if(isset($_POST['rules'])){
				sort($_POST['rules']);
				$_POST['rules']  = implode( ',' , array_unique($_POST['rules']));
				$agModel = M('auth_group');
				if ($agModel->save($_POST) === false) {
					$this->ajaxReturn('保存失败' . $agModel->getError());
				} else {
					$this->ajaxReturn('已保存');
				}
			}
		}
		$this->ajaxReturn('参数错误');
	}


	//获取用户组对应的权限
	public function getGroupRuleTree() {
		$groupId = I('get.id');
		if (!empty($groupId)) {
			$rules = M('auth_rule')->field('id,pid,title as name')->where('status=1')->select();
			$groupRule = M('auth_group')->where('id='.$groupId)->getField('rules');
			$groupRuleArr = explode(',',$groupRule);
			foreach($rules as $key => $val) {
				if (in_array($val['id'], $groupRuleArr)) {
					$rules[$key]['checked'] = true;
				} else {
					$rules[$key]['checked'] = false;
				}
			}
			$this->ajaxReturn($rules);

		}

	}


	/**
	 * 删除，设置为删除状态
	 */
	public function delete() {
		$groupId = I('get.id');
		/*
		if ($groupId > 0) {
			$map['id'] = $groupId;
			$map['status'] = -1;
			if (M('auth_group')->save($map) === false) {
				$this->error(M('auth_group')->getError());
			} else {
				$this->success('用户组已删除');
			}
		}*/

		if ($groupId > 0) {
			$adModel = M('auth_group');
			$status = $adModel->where('id='.$groupId)->getField('status');
			$_map['status'] = (int)$status > 0 ? 0 : 1;
			$_map['id'] = $groupId;
			if ($adModel->save($_map) === false) {
				$this->error($adModel->getError());
			}
			$_msg = $status==1 ? '禁用' : '启用';
			$ret = array(
				'msg'=>'用户组已'.$_msg,
				'status'=>$_map['status'],
			);
			$this->ajaxReturn($ret);
		} else {
			$ret = array(
				'msg'=>'参数错误',
				'status'=>-1,
			);
			$this->ajaxReturn($ret);
		}

	}


	/**
	 * 启用用户组
	 */
	public function resume() {
		$groupId = I('get.id', 0 , 'intval');
		if ($groupId > 0) {
			$map['id'] = $groupId;
			$map['status'] = 1;
			if (M('auth_group')->save($map) === false) {
				$this->error(M('auth_group')->getError());
			} else {
				$this->success('用户组已启用');
			}
		}
	}


	/**
	 * 用户授权 ,
	 */
	public function userAuthGroup() {
		$uid = I('post.uid', 0, 'intval');
		if ($uid <= 0) {
			$this->ajaxReturn('参数错误');
		}
		$op = I('post.op', '');
		$authGroups = I('post.auth_groups', '');
		if ($op == 'show') {
			$groups = M('auth_group')->where('status=1')->getField('id,title');
			//用户属于的组 auth_groups
			$userGroups = M('auth_group_access')->where('uid='.$uid)->getField('group_id',true);
			$this->assign('groups', $groups);
			$this->assign('usergroups', $userGroups);
			$this->assign('uid', $uid);
			$this->ajaxReturn($this->fetch('userauth'));

		} else {

			$AuthGroup = D('AuthGroup');
			if(is_numeric($uid)){
				if ( is_administrator($uid) ) {
					$this->ajaxReturn('该用户为超级管理员');
				}
				if( !M('user')->find($uid) ){
					$this->ajaxReturn('用户不存在');
				}
			}

			if( $authGroups && !$AuthGroup->checkId($authGroups)){
				$this->ajaxReturn($AuthGroup->error);
			}
			if ( $AuthGroup->addToGroup($uid, $authGroups) ){
				$this->ajaxReturn('操作成功');
			}else{
				$this->ajaxReturn($AuthGroup->getError());
			}

		}

	}


}
