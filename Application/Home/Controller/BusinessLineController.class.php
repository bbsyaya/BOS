<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 业务线
 * Class BusinessLineController
 * @package Home\Controller
 */
class BusinessLineController extends BaseController {

	public function index () {

		$options = array(
			array('type'=>'button', 'id'=>'', 'class'=>'J_addData', 'title'=>'新增', 'url'=>''),
		);
		$this->assign('toolOptions', $options);

		//搜索 title选项名称 name选项name value设置的值 options选项值 type页面控件类型
		$searchOptions = array(
			array('title'=>'业务线名称','name'=>'name','type'=>'text','options'=>'', 'value'=>$_REQUEST['name']),
		);
		$this->assign('searchOptions', $searchOptions);

		$where = array();
		$name = I('get.name','');
		if (!empty($name)) {
			$where['name'] = array('like',"%{$name}%");
		}

		$list = $this->lists('BusinessLine', $where);
		$this->assign('list', $list);
		$this->assign('op_function_status', C('OPTION.function_status'));
		$this->display();

	}


	public function edit() {

		$id = I('get.id', 0, 'intval');
		if ($id > 0) {
			$this->assign('data', D('BusinessLine')->find($id));
		}
		$this->showTable();
		$this->ajaxReturn($this->fetch('edit'));

	}


	public function update() {

		$id = I('post.id', 0, 'intval');
		$Model = D('BusinessLine');
		if ($Model->create()===false) {
			$this->ajaxReturn($Model->getError());
		}
		if ($id > 0) {
			//修改
			$_meta = '修改';
			$r=$Model->save();
		} else {
			//新增
			$_meta = '新增';
			$r = $insertId = $Model->add();
			if($r !== false) {
				//更新业务线编码
				$_map['id'] = $insertId;
				$_map['bl_code'] = $Model->generalCode($insertId);
				if ($Model->save($_map) === false) { //更新失败删除刚添加的广告主
					$Model->delete($insertId);
					$this->error('编码更新失败'.$Model->getError());
				}
			}
		}
		if ($r === false) {
			$this->ajaxReturn($Model->getError());
		}

		$this->ajaxReturn('ok');
	}


	public function showTable() {

		$this->assign('op_type', D('DataDic')->where('dic_type=6')->getField('id,name'));
		$this->assign('op_sb', D('DataDic')->where('dic_type=4')->getField('id,name'));

	}


}


