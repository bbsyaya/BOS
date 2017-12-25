<?php
namespace Home\Controller;
use Common\Controller\BaseController;

/**
 * 日志管理
 */
class LogManagerController extends BaseController {
	/**
	 * 列表页
	 */

    public function index() {

	    $where = array();
		$resource = I('get.resource',0,'intval');
	    $where['resource'] = $resource > 0 ? $resource : 1;

	    $date = I('get.date','');
	    $where['date'] = empty($date) ? '*' : $date;

	    $level = I('get.level','');
	    if(!empty($level)) {
		    $where['level'] = $level;
	    }

	    $module = I('get.module','');
	    if(!empty($module)) {
		    $where['module'] = $module;
	    }

	    $kw = I('get.kw','');
	    $where['kw'] = empty($kw) ? null : $kw;

	    $list = $this->lists('LogManager', $where);

	    $this->assign('list', $list);
	    $this->assign('op_module',C('LOG_MODULE'));
	    $this->assign('op_level', C('SEASLOG_LEVEL'));
	    $this->assign('op_resource', D('LogManager')->resource);
	    $this->assign('getlevel', $_GET['level']);
	    $this->display();
    }


}