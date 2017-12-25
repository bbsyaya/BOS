<?php

/**
* 系统操作
*/
namespace Common\Service;
class SysOperationLogService
{
	/**
	 * 写日志
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function writeLog($data){
		$data["uid"]       = $data["uid"];
		$data["remark"]    = $data["remark"];
		$data["type"]      = $data["type"];
		$data["dateline"]  = date("Y-m-d H:i:s",time());
		$data["customize"] = $data["customize"];
		$row               = M("sys_operation_log")->add($data);
	}
}
?>