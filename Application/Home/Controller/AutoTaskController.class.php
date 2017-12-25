<?php
/**
 * 自动任务
 */
namespace Home\Controller;
use Common\Controller\BaseController;
use Home\Service;
use Think\Model;
use Common\Service as comService;
class AutoTaskController{
 	/**
 	 * 检查是否有收入，成本逾期数据
 	 * @return [type] [description]
 	 */
 	function synCheckOverdueData(){
 		if(trim(I("token"))=="token023"){
 			//检查当年的收入，成本逾期数据，通知商务或者销售登录boss系统进行检查填写逾期原因
			$riskSer      = new \Home\Service\OverdueDataService();
			$riskSer->synCheckOverdueDataSer();
 		}
 	}

 	/**
 	 * 合同即将到期
 	 * @return [type] [description]
 	 */
 	function synContractExpire(){
 		if(trim(I("token"))=="token023"){
 			//到期前1个月以内，每周一提醒一次，共计约4次
 			$riskSer = new \Home\Service\ContractService();
			$riskSer->synContractExpireSer();
			echo "Over";
 		}
 	}

 	/**
 	 * 已到期合同通知
 	 * @return [type] [description]
 	 */
 	function synExpiredContract(){
 		if(trim(I("token"))=="token023"){
 			//到期后每天提醒一次，直至处理完毕
 			$riskSer = new \Home\Service\ContractService();
			$riskSer->synExpiredContractSer();
			echo "Over";
 		}
 	}

 	/**
 	 * 逾期测试产品通知
 	 * 1.测试到期当天的通知
 	 * 2.测试逾期7天/金额超过5万的通知
 	 * @return [type] [description]
 	 */
 	function synOverdueTestProductTip(){
 		if(trim(I("token"))=="token023"){
 			//到期后每天提醒一次，直至处理完毕
 			$riskSer = new \Home\Service\ProductService();
			$riskSer->synOverdueTestProductTipSer();
			echo "Over";
 		}
 	}

 	/**
 	 * [产品未出数据提醒通知 description]--分页读取
 	 * 提醒商务人员所负责的产品/计费标识未出数据的情况，及时反馈给供应商
 	 * 系统通知条件：如一款产品或单个计费标识在7个工作日内未产生数据，且该计费标识的推广状态为正在推广，则需要通知该产品或计费标识对应的商务人员
 	 * @return [type] [description]
 	 */
 	function synProductNotReminded(){
 		if(trim(I("token"))=="token023"){
			$riskSer  = new \Home\Service\ProductService();
			$count    = $riskSer->getsynProductNotRemindedCount();
			if(!$count){ echo "over";exit;}
			//采用分页的方式
			$listRows = 100;
			$page     = new \Think\Page($count, $listRows);
			for ($i=1; $i <= $page->totalPages ; $i++) { 
				$firstRow = ($i-1)*$listRows;
				$limit = "limit {$firstRow},{$listRows}";
				unset($firstRow);
				$riskSer->synProductNotRemindedSer($limit);
			}
			echo "Over";
 		}
 	}

 	/**
 	 * [回款到账通知 description]
 	 * @return [type] [description]
 	 */
 	function synReturnReceiptNotice(){
 		if(trim(I("token"))=="token023"){
 			$riskSer  = new \Home\Service\PayService();
			$count    = $riskSer->getPayCountByWhere(array("status"=>1),"id");
			if(!$count){ echo "over";exit;}
			//采用分页的方式
			$listRows = 100;
			$page     = new \Think\Page($count, $listRows);
			for ($i=1; $i <= $page->totalPages ; $i++) { 
				$firstRow = ($i-1)*$listRows;
				$limit = "limit {$firstRow},{$listRows}";
				unset($firstRow);
				//查找当前认款方（广告主名称）下的销售，通知他去认款，如果没有通知相应业务线的后勤人员
				$riskSer->synReturnReceiptNoticeSer($limit);
			}
			echo "Over";
 		}
 	}

 	/**
 	 * 每天执行--同步员工司龄
 	 * @return [type] [description]
 	 */
 	function synHrUserCompanyAge(){
 		if(trim(I("token"))=="token023"){
 			$riskSer  = new comService\HrManageService();
			$riskSer->synHrUserCompanyAgeSer();
			echo "Over";
 		}
 	}


 	//每小时执行-发送信息
 	function makemsg(){
 		if(trim(I("token"))=="makeallmsg_xq1110"){

 			$weekday=date('w');
 			$time_h=date('H');
 			$data=M('msg_fromuser')->where("status=1 && msgtime=$time_h && weekday='0'")->select();//仅发送一次的信息
 			foreach ($data as $key => $value) {
 				$msg=array();
 				$msg['date_time']=date('Y-m-d H:i:s');
 				$msg['send_user']=$value['touser'];
 				$msg['content']=$value['content'];
 				$msg['add_user']=$value['uid'];
 				$msg['msg_type']='1';
 				$msg['make_id']=$value['id'];
 				$msg['file']=$value['filename'];
 				$msg['fileoldname']=$value['fileoldname'];
 				$id=M('prompt_information')->add($msg);
 				if($id)$ok_arr[]=$value['id'];
 			}
 			if(count($ok_arr)>0)M('msg_fromuser')->where("id in (".implode(',', $ok_arr).")")->save(array('weekday'=>-1));
 			$data_xh=M('msg_fromuser')->where("status=1 && msgtime=$time_h && find_in_set($weekday,weekday)")->select();//定时发送的信息
 			foreach ($data_xh as $key => $value) {
 				$msg=array();
 				$msg['date_time']=date('Y-m-d H:i:s');
 				$msg['send_user']=$value['touser'];
 				$msg['content']=$value['content'];
 				$msg['add_user']=$value['uid'];
 				$msg['msg_type']='1';
 				$msg['make_id']=$value['id'];
 				$msg['file']=$value['filename'];
 				$msg['fileoldname']=$value['fileoldname'];
 				$id=M('prompt_information')->add($msg);
 			}
			echo "Over";
			
 		}
 	}

 	//每天执行-数据确认通知

 	function makedatastatusmsg(){
 		if(trim(I("token"))=="makedatastatusmsg_xq"){
 			$riskSer      = new \Home\Service\OverdueDataService();
			$riskSer->makedatastatusmsg();
 			echo "Over";

 		}
 	}
} 
?>