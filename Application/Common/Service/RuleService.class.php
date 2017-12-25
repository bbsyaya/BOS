<?php
/**
* 投票业务逻辑层
*/
namespace Common\Service;
class RuleService
{

	     //获取当前用户的数据权限,以条件字符串形式返回
     /*参数组成
		arr_name:array(业务线字段名数组，其余需判断的字段名数组)
		is_zh:是否ID关联
     */
     public function getmyrule_data($arr_name,$is_id=1){

     	$rule=$_SESSION['userinfo']['data_config'];
     	$where=array();
     	if($is_id!=1){
     		$data_line=M('business_line')->select();
     		foreach ($data_line as $key => $value) {
     			$data_line_name[$value['id']]=$value['name'];
     		}
     		$data_user=M('user')->select();
     		foreach ($data_user as $key => $value) {
     			$data_user_name[$value['id']]=$value['real_name'];
     		}
     		foreach ($rule as $key => $value) {
     			$arr_v=explode('_', $value);
	     		if(substr($value, 0,1)=='l'){
	     			//业务线数据权限
	     			foreach ($arr_name['line'] as $k => $v) {
	     				$where[]=$v.'= "'.$data_line_name[$arr_v[1]].'"';
	     			}
	     		}elseif(substr($value, 0,1)=='u'){
	     			//个人数据权限
	     			foreach ($arr_name['user'] as $k => $v) {
	     				$where[]=$v.'= "'.$data_user_name[$arr_v[1]].'"';
	     			}
	     		}
	     	}
     	}else{

     		foreach ($rule as $key => $value) {
     			$arr_v=explode('_', $value);
	     		if(substr($value, 0,1)=='l'){
	     			//业务线数据权限
	     			$rule_line[]=$arr_v[1];
	     		}elseif(substr($value, 0,1)=='u'){
	     			//个人数据权限
					$rule_user[]=$arr_v[1];
	     		}
	     	}
	     	if(count($arr_name['line'])>0){
	     		foreach ($arr_name['line'] as $key => $value) {
		     		if(count($rule_line)>0)$where[]=$value.' in ('.implode(',', $rule_line).')';
		     	}
	     	}
	     	if(count($arr_name['user'])>0){
		     	foreach ($arr_name['user'] as $key => $value) {
		     		if(count($rule_user)>0)$where[]=$value.' in ('.implode(',', $rule_user).')';
		     	}
		    }
     	}
     	if(count($where)>0)return '('.implode(' || ', $where).')';
     	else return '1=0';
     }


}
?>