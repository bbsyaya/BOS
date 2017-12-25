<?php
/**
 * 职位管理
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class DutyController extends BaseController {
	private $dutySer;
	function _initialize(){
		parent::_initialize();
		$this->dutySer = !$this->dutySer ? new Service\DutyService() : $this->dutySer;
	}

	/**
	 * [list description]
	 * @return [type] [description]
	 */
	function index(){
		$vname   = I("name");
		$where = array();
		if($vname){
			$where["name"] = array("like","%{$vname}%");
		}
		$map["name"] = $vname;
		$this->assign("map",$map);

		$count    = $this->dutySer->getDutyCountByWhere($where);
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$fields   = "id,name";
		$list     = $this->dutySer->getDutyListByWhere($where,$fields,"dateline desc",$page->firstRow,$page->listRows);
		$this->assign("list",$list);
		$this->assign("page",$page->show());
		$this->display();
	}

	public function add() {
	    $this->ajaxReturn($this->fetch('edit'));
    }

    public function edit() {
    	$id = I("id");
    	$one = $this->dutySer->getDutyOneByWhere(array("id"=>$id));
    	$this->assign("data",$one);
	    $this->ajaxReturn($this->fetch('edit'));
    }

    /**
     * 保存数据
     * @return [type] [description]
     */
    function saveData(){
    	$data["name"] = trim(I("name"));
    	$id = I("id");
    	if($id){
    		$this->dutySer->saveDuty(array("id"=>$id),$data);
    	}else{
    		$this->dutySer->addDutyData($data);
    	}
    	$this->ajaxReturn(array("msg"=>"保存成功"));
    }


    function delete(){
    	$id = I("id");
    	$this->dutySer->deleteDutyData(array("id"=>$id));
    	$this->ajaxReturn(array("msg"=>"操作成功"));
    }
}
?>