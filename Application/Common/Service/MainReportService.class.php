<?php
/**
* MainReportService.class
*/
namespace Common\Service;
use Think\Model;
class MainReportService
{
	
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getmain_reportListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("main_report")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}


	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getmain_reportListByWhere_usesql($where_,$fields_="",$order_="",$limit_=""){
		$sql = 'SELECT {$fields_}
					FROM
					  `main_report` AS t 
					  LEFT JOIN `boss_user` AS u 
					    ON t.`pri_uid` = u.`id` 
					{$where_} {$order_} {$limit_}';
		$model = new \Think\Model();
		$list = $model->query($sql);
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getmain_reportCountByWhere($where_,$fields_=""){
		$list = M("main_report")->field($fields_)->where($where_)->count();
		return $list;
	}
	
	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_=""){
		$list = M("main_report")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("main_report")->add($data);
		return $row;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function savemain_reportData($where_,$data){
		$row = M("main_report")->where($where_)->save($data);
		return $row;
	}


	//+++++++++++main task+++++++++++++++++++
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getmain_taskListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("main_task")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getmain_taskCountByWhere($where_,$fields_=""){
		$list = M("main_task")->field($fields_)->where($where_)->count();
		return $list;
	}
	
	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getmain_taskOneByWhere($where_,$fields_=""){
		$list = M("main_task")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addmain_taskData($data){
		$row = M("main_task")->add($data);
		return $row;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function savemain_taskData($where_,$data){
		$row = M("main_task")->where($where_)->save($data);
		return $row;
	}


	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getmain_taskListByWhere_usesql($where_,$fields_="",$order_="",$limit_=""){
		$sql = 'SELECT '.$fields_.'
					FROM
					  `boss_main_task` AS t 
					  LEFT JOIN `boss_user` AS u 
					    ON t.`pri_uid` = u.`id` 
					'.$where_.$order_.$limit_;
		$model = new \Think\Model();
		$list = $model->query($sql);
		foreach ($list as $k => $v) {
			$list[$k]["ctime"] = date("Y-m-d",strtotime($v["ctime"]));
		}
		return $list;
	}
}

?>