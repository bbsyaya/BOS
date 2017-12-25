<?php
/**
* 系统信息
*/
namespace Common\Service;
class PromptInformationService
{
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getPromptInformationListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("prompt_information")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取个数
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getPromptInformationCountByWhere($where_,$fields_=""){
		$list = M("prompt_information")->field($fields_)->where($where_)->count();
		return $list;
	}
	
	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_=""){
		$list = M("prompt_information")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("prompt_information")->add($data);
		return $row;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function savePromptInformationData($where_,$data){
		$row = M("prompt_information")->where($where_)->save($data);
		return $row;
	}

	

}
?>