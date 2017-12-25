<?php
namespace Home\Model;
use Think\Model;
class DaydataLogModel extends Model {
	function adddata($data){
		//$reid = $this->add($data);
		import("Org.Util.Hgmongodb");
		$mongo          = new \Hgmongodb(); 

		//增加
		$mongo->connect();//连接mongodb，这是一个触发式的连接
		$params = array(
			"db_table"=>"boss3_www.bos_daydatalog",
			"datas" =>array()
		);
		
		$params["datas"][0]['dataid'] = $data['dataid'];
		$params["datas"][0]['remark'] = $data['remark'];
		$params["datas"][0]['datatype'] = $data['datatype'];
		$params["datas"][0]['addtime'] = $data['addtime'];
		$params["datas"][0]['username'] = $data['username'];
		$params["datas"][0]['olddata'] = $data['olddata'];

		//$params["datas"][0][] = $data;
		// print_r($params);
		// for ($i=1; $i < 2; $i++) { 
		// 	$one               = array();
		// 	$one["id"]         =$i;
		// 	$one["dataid"]     =rand(1,100);
		// 	$one["remark"]     ="sjljdld_".rand(8,100);
		// 	$one["datatype"]   =rand(1,2);
		// 	$one["addtime"]    =time();
		// 	$one["username"]   ="zhjangsan_".rand(1,100);
		// 	$one["olddata"]    = rand(0,50);
		// 	$params["datas"][0][] = $one;
		// 	unset($one);
		// }
		
		// print_r($params);exit;

		$row = $mongo->newInsert($params);
		return $reid;
	}
	function getdata($where){
		return $this->where($where)->select();
	}
}