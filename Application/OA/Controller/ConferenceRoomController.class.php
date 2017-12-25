<?php
namespace OA\Controller;
use Common\Controller\BaseController;
class ConferenceRoomController extends BaseController {
	//会议室申请
	public function index(){
		if(!empty(I('get.day')))$day=strtotime(I('get.day'));
		else $day=time();
		$this->nowday=date('Y-m-d(星期N)',$day);
		$this->lastday=date('Y-m-d',$day-3600*24);
		$this->nextday=date('Y-m-d',$day+3600*24);
		$this->display();
	}
}


