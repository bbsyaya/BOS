<?php
/**
* 投票业务逻辑层
*/
namespace Common\Service;
class VoteService
{
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_vote")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取条数
	 * @param  [type] $where_ [description]
	 * @param  string $order_ [description]
	 * @return [type]         [description]
	 */
	function getListCountByWhere($where_){
		$list = M("oa_vote")->field("id")->where($where_)->count();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("oa_vote")->add($data);
		return $row;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_){
		$list = M("oa_vote")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveData($where_,$data){
		$row = M("oa_vote")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addVoteProblemsData($data){
		$row = M("oa_vote_problems")->add($data);
		return $row;
	}
	
	/**
	 * delete数据
	 * @param [type] $data [description]
	 */
	function deleteVoteProblemsData($where_){
		$row = M("oa_vote_problems")->where($where_)->delete();
		return $row;
	}
	/**
	 * delete数据
	 * @param [type] $data [description]
	 */
	function deleteVoteProblemsDetailData($where_){
		$row = M("oa_vote_problems_detail")->where($where_)->delete();
		return $row;
	}
	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addVoteProblemsDetailData($data){
		$row = M("oa_vote_problems_detail")->add($data);
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
	function getVoteProblemsListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_vote_problems")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
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
	function getVoteProblemsDetailListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_vote_problems_detail")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * [检查是否有新投票]
	 * @return [type] [description]
	 */
	function checkIsHasNewVote($dept_id,$uid){
		$model = new \Think\Model();
		$where = " and v.end_time>='".date("Y-m-d H:i:s",time())."' and v.status = 1";
		$sql  =  "SELECT 
					v.vote_uids
				FROM
					`boss_oa_vote` AS v 
				WHERE v.departs_ids LIKE '%,{$dept_id},%' {$where}"; 
		$list  = $model->query($sql);
		$count = 0;
		// print_r($list);exit;
		foreach ($list as $k => $v) {
			$ids = explode(",",$v["vote_uids"]);
			if(in_array($uid, $ids)){
				continue;
			}else{
				$count++;
			}
		}
		return $count;
	}
}
?>