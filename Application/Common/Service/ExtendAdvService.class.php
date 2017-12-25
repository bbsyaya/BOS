<?php
/**
* service 父类
*/
namespace Common\Service;
use Think\Model;
class ExtendAdvService
{
	/**
	 * 根据条件获取分类列表--待拓展广告主
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow="",$use_sql=false){
		$list = array();
		if($use_sql){
			$sql = "SELECT 
					  {$fields_}
					FROM
					  `boss_expand_adver` AS e 
					  LEFT JOIN `boss_user` AS us ON us.id = e.`create_uid` 
					  LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					      (SELECT 
					        MAX(id) AS id 
					      FROM
					        boss_expand_follow where type_id=1
					      GROUP BY expand_id)
						and type_id=1
						) AS ef 
					    ON ef.expand_id = e.id 
					{$where_} 
					 {$order_}
					LIMIT  {$firstRow}, {$lastRow} ";
					// print_r($sql);exit;
		    $model = new \Think\Model();
		    $list = $model->query($sql);
		}else{
			$list = M("expand_adver")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		}
		return $list;
	}

	/**
	 * 获取条数
	 * @param  [type] $where_ [description]
	 * @param  string $order_ [description]
	 * @return [type]         [description]
	 */
	function getListCountByWhere($where_,$use_sql=false){
		$list = 0;
		if($use_sql){
			$sql = "SELECT 
					  count(1) as no 
					FROM
					  `boss_expand_adver` AS e 
				    LEFT JOIN `boss_user` AS us ON us.id = e.`create_uid`
				    LEFT JOIN 
					    (SELECT 
					      * 
					    FROM
					      boss_expand_follow a 
					    WHERE a.id IN 
					      (SELECT 
					        MAX(id) AS id 
					      FROM
					        boss_expand_follow 
					      GROUP BY expand_id)) AS ef 
					    ON ef.expand_id = e.id 
					    {$where_}";
		    $model = new \Think\Model();
		    $list = $model->query($sql);
		    $list = $list[0]["no"];
		}else{
			$list = M("expand_adver")->field("id")->where($where_)->count();
		}
		return $list;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_){
		$list = M("expand_adver")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveData($where_,$data){
		$row = M("expand_adver")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("expand_adver")->add($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addFollowData($data){
		$row = M("expand_follow")->add($data);
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
	function getFollowListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow="",$use_sql=false){
		$list = array();
		if($use_sql){
			$sql = "SELECT 
					 {$fields_}
					FROM
					  `boss_expand_follow` AS e 
					   LEFT JOIN `boss_user` AS us ON us.id=e.`follow_uid`
					    {$where_} {$order_} limit {$firstRow},{$lastRow}";
		    $model = new \Think\Model();
		    $list = $model->query($sql);
		}else{
			$list = M("expand_follow")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		}
		return $list;
	}


	/**
	 * 获取条数
	 * @param  [type] $where_ [description]
	 * @param  string $order_ [description]
	 * @return [type]         [description]
	 */
	function getFollowListCountByWhere($where_,$use_sql=false){
		$list = 0;
		if($use_sql){
			$sql = "SELECT 
					  count(1) as no 
					FROM
					  `boss_expand_follow` AS e  {$where_}";
		    $model = new \Think\Model();
		    $list = $model->query($sql);
		    $list = $list[0]["no"];
		}else{
			$list = M("expand_follow")->field("id")->where($where_)->count();
		}
		return $list;
	}
}
?>