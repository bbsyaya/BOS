<?php
/**
* 财务日志表 tgd 20170330
*/
namespace Common\Service;
use Think\Model;
class FinanceLogService
{
	/**
	 * 写日志
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function writeLog($data){
		$data["postUrl"]  = $data["postUrl"];
		$data["postData"] = $data["postData"];
		$data["data"]     = $data["data"];
		$data["dateline"] = date("Y-m-d H:i:s",time());
		$row = M("finance_log")->add($data);
	}
}
?>