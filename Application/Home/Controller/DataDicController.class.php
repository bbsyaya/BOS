<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 数据字典
 */
class DataDicController extends BaseController {

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
			array('title'=>'字典名称','name'=>'name','type'=>'text','options'=>'', 'value'=>$_REQUEST['name']),
		);
		$this->assign('searchOptions', $searchOptions);

		$where = array();
		$tid = I('get.tid', 0, 'intval');
		$name = I('get.name','');
		if (!empty($name)) {
			$where['name'] = array('like', "%{$name}%");
		}
		$model = 'DataDic';
		if ($tid == 0) { //类型
			$model = 'DataDicType';
		} else { //字典数据
			$where['dic_type'] = $tid;
			$this->assign('typeName', D('DataDicType')->where('id='.$tid)->getField('name'));
		}
		$list = $this->lists($model, $where);
		$this->assign('list', $list);
		$this->assign('op_function_status', C('OPTION.function_status'));
		Cookie('__forward__',$_SERVER['REQUEST_URI']);
		$this->display();

	}


	/**
	 * 添加
	 */
	public function add() {

		$this->showtable();
		$tid = I('get.tid', 0, 'intval');
		//表单设置tid
		$this->assign('data',array('dic_type'=>$tid));
		$this->ajaxReturn($this->fetch('edit'));
	}


	private function showtable() {
		$tid = (int)$_GET['tid'];
		if ($tid > 0) {
			$this->assign('typename', D('DataDicType')->where('id='.$tid)->getField('name'));
		}
		$this->assign('op_status', C('OPTION.function_status'));
	}


	public function edit() {

		$id = I('get.id', 0, 'intval');
		if ($id <= 0) {
			$this->ajaxReturn('参数错误');
		}
		$tid = I('get.tid', 0, 'intval');
		$this->showtable();

		if ($tid > 0) {
			//字典数据
			$this->assign('data', D('DataDic')->where(array('id'=>$id,'dic_type'=>$tid))->find());
		} else {
			//字典类型
			$this->assign('data', D('DataDicType')->find($id));
		}

		$this->ajaxReturn($this->fetch('edit'));

	}

	//保存权限信息
	public function update() {

		$id = I('request.id', 0, 'intval');
		$tid = I('request.tid', 0, 'intval');

		if (empty(I('request.name'))) $this->ajaxReturn('名称不能为空');

		if ($tid > 0) {
			$_REQUEST['dic_type'] = $tid;
			$dicModel = D('DataDic');
		} else {
			$dicModel = D('DataDicType');
		}
		if ($id > 0) {
			//修改
			if($dicModel->save($_REQUEST) === false) {
				$this->ajaxReturn($dicModel->getError());
			} else {
				$this->ajaxReturn('ok');
			}
		} else {
			//新增
			$interId = $dicModel->add($_REQUEST);
			if ($interId) {
				$this->ajaxReturn('ok');
			} else {
				$this->ajaxReturn($dicModel->getError());
			}

		}

	}


/*	public function changeStatus() {
		$id = I('get.id', 0, 'intval');
		$tid = I('get.tid', 0, 'intval');
		$status = I('get.status');
		$map['id'] = $id;
		if ($tid > 0) {
			$dicModel = D('DataDic');
			$map['tid'] = $tid;
		} else {
			$dicModel = D('DataDicType');
		}

		if ($id > 0 && in_array($status, array('-1','0','1'))) {
			$map['status'] = $status>0 ? 0 : 1;
			if ($dicModel->where($map)->save() === false) {
				$this->error($dicModel->getError());
			}
			$this->success('状态已修改');
		} else {
			$this->error('参数错误');
		}
	}*/



}