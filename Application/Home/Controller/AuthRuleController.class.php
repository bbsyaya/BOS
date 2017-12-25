<?php

namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 权限管理
 * Class AuthManagerController
 */
class AuthRuleController extends BaseController{

    /**
     * 权限管理首页
     */
    public function index() {

	    $options = array(
		    array('type'=>'button', 'id'=>'', 'class'=>'J_addData', 'title'=>'新增', 'url'=>''),
	    );
	    $this->assign('toolOptions', $options);

	    //搜索 title选项名称 name选项name value设置的值 options选项值 type页面控件类型
	    $searchOptions = array(
		    array('title'=>'权限名称','name'=>'title','type'=>'text','options'=>'', 'value'=>$_REQUEST['title']),
	    );
	    $this->assign('searchOptions', $searchOptions);

	    $where = array();
	    $pid = I('get.pid', 0, 'intval');
	    $title = I('get.title','');
	    if (!empty($title)) {
		    $where['title'] = array('like', "%{$title}%");
	    }
	    $where['pid'] = $pid;
	    $list = $this->lists('AuthRule', $where);
	    $this->assign('list', $list);
	    $this->assign('op_is_hide', array('1'=>'是','0'=>'否'));
	    $this->assign('op_function_status', C('OPTION.function_status'));
	    $parentName = '无';
	    if ($pid > 0) {
		    $parentName = D('AuthRule')->where('id='.$pid)->getField('title');
	    }
	    $this->assign('parentName', $parentName);

	    //是否有删除权限
	    $showdelete = false;
	    if(I("showdelete")=="showdelete023"){
	    	$showdelete = true;
	    }
	    $this->assign('showdelete', $showdelete);
	    $this->display();

    }


	public function add() {
		$this->showtable();
		$this->ajaxReturn($this->fetch('edit'));
	}


	private function showtable() {

		$RuleList = D('AuthRule')->field(true)->select();
		$root = array('id'=>0, 'title'=>'目录');
		array_unshift($RuleList, $root);
		$this->assign('op_rul_list', D('Common/Tree')->toFormatTree($RuleList));
		$this->assign('op_status', C('OPTION.function_status'));
		// $this->assign('op_is_hide', array('0'=>'否','1'=>'是'));

	}


	public function edit() {

		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$this->assign('data', D('AuthRule')->find($id));
			$this->showtable();
			$this->ajaxReturn($this->fetch('edit'));
		} else {
			$this->ajaxReturn('参数错误');
		}

	}


	//保存权限信息
	public function update() {

		$id = I('request.id', 0, 'intval');
		$groupModel = D('AuthRule');

		if(empty(I('request.title'))) $this->ajaxReturn('名称不能为空');
		if(empty(I('request.name'))) $this->ajaxReturn('规则url不能为空');

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


	public function delete() {
		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$ruleModel = D('auth_rule');
			//检查有无子级菜单
			if ($ruleModel->where('pid='.$id)->count() > 0) {
				$this->error('存在子级菜单，请先处理');
			}

			if ($ruleModel->delete($id) === false) {
				$this->error($ruleModel->getError());
			}
			$this->success('菜单已删除');
		} else {
			$this->error('参数错误');
		}
	}


}
