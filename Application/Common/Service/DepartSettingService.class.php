<?php
namespace Common\Service;
use Think\Model;
class DepartSettingService
{
	
	/**
	 * 根据条件获取列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("user_department")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 根据条件获取列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getListCountByWhere($where_,$fields_=""){
		$list = M("user_department")->field($fields_)->where($where_)->count();
		return $list;
	}
	/**
	 * 获取所有
	 * @return [type] [description]
	 */
	function getAllTreeList($where_){
		$result = array();
		$alllist = M("user_department")->where($where_)->field("id,name,pid")->select();
		$result = $this->getChildList($alllist,0,$result,0);
		return $result;
	}

	/**
	 * 获取所有treeList 
	 * @return [type] [description]
	 */
	function getAllTreeListNoOptions($where_,$fields_="",$order_=""){
		$result  = array();
		$alllist = M("user_department")->where($where_)->field($fields_)->order($order_)->select();

		// foreach($alllist as $key=>$val){
		// 	if($val['pid'] == 160 && $val['id'] != 167){
		// 		$alllist[$key]['dep'] = $val['name'];
		// 	}else{
		// 		$alllist[$key]['dep'] = '重庆话语科技有限公司';
		// 	}
		// }
		$result = $this->getChildListNoOptions($alllist,0,$result,0);

		return $result;
	}

	/**
	 * 获取所有treeList
	 * @return [type] [description]
	 */
	function getAllTreeListNoOptionsLine($where_,$fields_="",$order_=""){
		$result = array();
		$alllist = M("user_department")->field("id,pid as parentid,name as topic,heads")->order($order_)->select();//->where($where_)
		//echo M()->getLastSql();exit;
		foreach($alllist as $key=>$val){
			if($val['id'] ==160 or $val['pid'] ==160){
				$alllist[$key]['topic'] = $val['topic'];
			}else{
				$alllist[$key]['topic'] = $val['topic'].'('.$val['heads'].')';
			}

		}
		$userList = M('user')->field("a.id,a.dept_id as parentid,a.real_name as topic,b.phone,c.name")->join("a left join boss_oa_hr_manage b on a.id=b.user_id left join boss_oa_position c on c.id=b.duty")->where('a.dept_id>0 and a.status=1')->select();
		foreach($userList as $key2=>$val2){
			$userList[$key2]['topic'] = $val2['topic'].'('.$val2['name'].' '.$val2['phone'].')';
		}
		$Res = $this->array_add($alllist,$userList);
		//print_r($aa);exit;
		$result = $this->getChildListNoOptionsLine($Res,0,$result,0);
		return $result;
	}
	
	/**
	 * [创建树形图 description]
	 * @return [type] [description]
	 */
	function getAllTreeListNoOptionsLine_img(){
		// $result = array();
		// $alllist = M("user_department")->field("id,pid as parentid,name as topic,heads")->order($order_)->select();
		// //echo M()->getLastSql();exit;
		// foreach($alllist as $key=>$val){
		// 	if($val['id'] ==160 or $val['pid'] ==160){
		// 		$alllist[$key]['topic'] = $val['topic'];
		// 	}else{
		// 		$alllist[$key]['topic'] = $val['topic'].'('.$val['heads'].')';
		// 	}

		// }
		// $userList = M('user')->field("a.id,a.dept_id as parentid,a.real_name as topic,b.phone,c.name")->join("a left join boss_oa_hr_manage b on a.id=b.user_id left join boss_oa_position c on c.id=b.duty")->where('a.dept_id>0 and a.status=1')->select();
		// foreach($userList as $key2=>$val2){
		// 	$userList[$key2]['topic'] = $val2['topic'].'('.$val2['name'].' '.$val2['phone'].')';
		// }
		// $Res = $this->array_add($alllist,$userList);
		// //print_r($aa);exit;
		// $result = $this->getChildListNoOptionsLine($Res,0,$result,0);


		//先读取公司，在读取公司下的部门
		$result = array();
		$alllist = M("user_department")->field("id,pid as parentid,name as topic,heads,type")->order($order_)->select();
		// foreach($alllist as $key=>$val){
		// 	if($val["type"]==0){
		// 		if($val['parentid']==0){
		// 			$alllist[$key]['parentid']
		// 		}
		// 	}
		// }

		// $userList = M('user')->field("a.id,a.dept_id as parentid,a.real_name as topic,b.phone,c.name")->join("a left join boss_oa_hr_manage b on a.id=b.user_id left join boss_oa_position c on c.id=b.duty")->where('a.dept_id>0 and a.status=1')->select();
		// foreach($userList as $key2=>$val2){
		// 	$userList[$key2]['topic'] = $val2['topic'].'('.$val2['name'].' '.$val2['phone'].')';
		// }
		// $Res = $this->array_add($alllist,$userList);
		// $result = $this->getChildListNoOptionsLine($Res,0,$result,0);
		return $result;
	}

	/**
	 * [创建树形图 description]
	 * @param  [type]  $alllist [description]
	 * @param  integer $pid     [description]
	 * @param  [type]  &$result [description]
	 * @param  integer $no      [description]
	 * @return [type]           [description]
	 */
	private function getChildList_img($alllist,$pid = 0,&$result,$no=0){
		$no++;
		$list = array();
		foreach ($alllist as $k => $v) {
			if($v["pid"] == $pid){
				$list[] = $v;
			}else{
				continue;
			}
		}
		foreach ($list as $k => $v) {
			$prev_str = "|&nbsp;&nbsp;&nbsp;&nbsp;";
			$v["name"] = str_repeat($prev_str,$no)."|__".$v["name"];
			$result[] = $v;
			$this->getChildList_img($alllist,$v["id"],$result,$no);
		}
		return $result;
	}


	function getChildListNoOptionsLine_img($alllist,$pid = 0,&$result,$no=0){
		$no++;
		$list = array();
		foreach ($alllist as $k => $v) {
			if($v["pid"] == $pid){
				$list[] = $v;
			}else{
				continue;
			}
		}
		foreach ($list as $k => $v) {
			$result[] = $v;
			$this->getChildListNoOptionsLine_img($alllist,$v["id"],$result,$no);
		}
		return $result;
	}



	public function array_add($a1,$a2){
		$n = 0;
		foreach ($a1 as $key => $value) {
			$re[$n] = $value;
			$n++;
		}
		foreach ($a2 as $key => $value) {
			$re[$n] = $value;
			$n++;
		}
		return $re;
	}

	/**
	 * 获取tree 
	 * @param  [type]  $alllist [description]
	 * @param  integer $pid     [description]
	 * @param  [type]  &$result [description]
	 * @param  integer $no      [description]
	 * @return [type]           [description]
	 */
	function getChildListNoOptionsLine($alllist,$pid = 0,&$result,$no=0){
		$no++;
		$list = array();
		foreach ($alllist as $k => $v) {
			if($v["pid"] == $pid){
				$list[] = $v;
			}else{
				continue;
			}
		}
		foreach ($list as $k => $v) {
			$result[] = $v;
			$this->getChildListNoOptionsLine($alllist,$v["id"],$result,$no);
		}
		return $result;
	}

	/**
	 * 获取tree 
	 * @param  [type]  $alllist [description]
	 * @param  integer $pid     [description]
	 * @param  [type]  &$result [description]
	 * @param  integer $no      [description]
	 * @return [type]           [description]
	 */
	function getChildListNoOptions($alllist,$pid = 0,&$result,$no=0){
		$no++;
		$list = array();
		foreach ($alllist as $k => $v) {
			if($v["pid"] == $pid){
				$list[] = $v;
			}else{
				continue;
			}
		}
		foreach ($list as $k => $v) {
			$result[] = $v;
			$this->getChildList($alllist,$v["id"],$result,$no);
		}
		return $result;
	}

	/**
	 * 获取tree 
	 * @param  [type]  $alllist [description]
	 * @param  integer $pid     [description]
	 * @param  [type]  &$result [description]
	 * @param  integer $no      [description]
	 * @return [type]           [description]
	 */
	function getChildList($alllist,$pid = 0,&$result,$no=0){
		$no++;
		$list = array();
		foreach ($alllist as $k => $v) {
			if($v["pid"] == $pid){
				$list[] = $v;
			}else{
				continue;
			}
		}
		foreach ($list as $k => $v) {
			$prev_str = "|&nbsp;&nbsp;&nbsp;&nbsp;";
			$v["name"] = str_repeat($prev_str,$no)."|__".$v["name"];
			$result[] = $v;
			$this->getChildList($alllist,$v["id"],$result,$no);
		}
		return $result;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneByWhere($where_,$fields_){
		$list = M("user_department")->field($fields_)->where($where_)->find();
		return $list;
	}
	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveData($where_,$data){
		$row = M("user_department")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addData($data){
		$row = M("user_department")->add($data);
		return $row;
	}

	/**
	 * 获取公司树形list
	 * @return [type] [description]
	 */
	function getCompanyIDByNameTree(){
		$where_["type"] = 1;
		$alllist = M("user_department")->where($where_)->field("id,name,pid")->select();
		$newList = array();
		foreach ($alllist as $k => $v) {
			$newList[$v["name"]] = $v;
		}
		return $newList;
	}

	/**
	 * 删除数据
	 * @param [type] $data [description]
	 */
	function deleteDataByWhere($where_){
		$row = M("user_department")->where($where_)->delete();
		return $row;
	}

	/**
	 * [getDepartChildsIdsBypid description]
	 * @param  [type] $pid [description]
	 * @return [type]      [description]
	 */
	function getDepartChildsIdsBypid($pid){
		$sql = "SELECT 
					  b.id AS cid 
					FROM
					  `boss_user_department` AS a 
					  LEFT JOIN `boss_user_department` AS b 
					    ON a.id = b.`pid` 
					WHERE a.id = {$pid} ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if(!$list){ return false; }
		$ids = "";
		foreach ($list as $k => $v) {
			$ids .= $v["cid"].",";
		}
		unset($sql);
		unset($model);
		unset($list);
		return $ids;
	}
	

}
?>