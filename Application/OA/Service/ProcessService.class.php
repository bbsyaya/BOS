<?php
/**
* 流程业务逻辑
*/
namespace OA\Service;
use Think\Model;
class ProcessService 
{
	/**
	 * [获取流程的所有流程list description]--
	 * @param  [type] $processid [description]
	 * @return [type]            [description]
	 */
	function getProcessAllListSer($processid){
		if(empty($processid)){
			return false;
		}
		//得到当前流程信息
		$sql = "SELECT 
				  id,
				  mid,
				  nowsort,
				  isok,
				  liuchenid,
				  STATUS,name,alldata,shenghejilu
				FROM
				  `boss_oa_liuchen` 
				WHERE liuchenid = '".$processid."'  ";
		$model          = new \Think\Model();
		$liuchengOne = $model->query($sql);
		$liuchengOne = $liuchengOne[0];
		if(!$liuchengOne){
			return false;
		}

		//得到当前流程的所有节点
		$sql = "SELECT 
				  id,
				  pid,
				  sort,
				  tiaojian,
				  NAME,
				  nextjdlist,
				  autouser 
				FROM
				  `boss_oa_jiedian` 
				WHERE pid = '".$liuchengOne["mid"]."' ORDER BY sort ASC ";
				// print_r($sql);exit;
		$allJiedianList = $model->query($sql);
		if(!$allJiedianList){
			return false;
		}
		//得到节点的排序sort
		$data_arr   = $this->getnextjiedian(0,$liuchengOne,array());
		// print_r($data_arr);exit;
		$newProcess = array();
		//默认第一个节点
		$newProcess[0]["name"] = "开始节点";
		if($liuchengOne["nowsort"]==0){
			$newProcess[0]["isshenhe"] = 1;//是否审核
			$newProcess[0]["isnow"]    = 1;//当前节点
		}
		//已排序好的sort和当前节点配对
		foreach ($data_arr as $k => $v) {
			foreach ($allJiedianList as $ka => $va) {
				if($va["sort"] == $v){
					$newProcess[$k+1]["name"] = $va["name"];
					if($v==$liuchengOne["nowsort"]){

						//修改newProcess在当前节点之前的是否审核状态
						foreach ($newProcess as $kb => $vb) {
							$newProcess[$kb]["isshenhe"] = 1;
							$newProcess[$kb]["isnow"]    = 0;
						}

						$newProcess[$k+1]["isshenhe"] = 1;//是否审核
						$newProcess[$k+1]["isnow"]    = 1;//当前节点
					}
				}
			}
		}
		return $newProcess;
	}


	/**
	 * 获取流程的所有流程list
	 * @param  [type] $processid [流程id]
	 * @return [type]            [description]
	 */
	function getProcessAllListSer_tgd($processid){
		if(empty($processid)){
			return false;
		}
		//得到当前流程信息
		$sql = "SELECT 
				  id,
				  mid,
				  nowsort,
				  isok,
				  liuchenid,
				  STATUS,name,alldata,shenghejilu
				FROM
				  `boss_oa_liuchen` 
				WHERE liuchenid = {$processid}  ";
		$model          = new \Think\Model();
		$liuchengOne = $model->query($sql);
		$liuchengOne = $liuchengOne[0];
		if(!$liuchengOne){
			return false;
		}
		$this->_dataTable_mid_ = $liuchengOne["mid"];
		$this->_liucheng_alldata_ = $liuchengOne["alldata"];
		//得到当前流程的所有节点
		$sql = "SELECT 
				  id,
				  pid,
				  sort,
				  tiaojian,
				  NAME,
				  nextjdlist,
				  autouser 
				FROM
				  `boss_oa_jiedian` 
				WHERE pid = '".$liuchengOne["mid"]."' ORDER BY sort ASC ";
				// print_r($sql);exit;
		$allJiedianList = $model->query($sql);
		if(!$allJiedianList){
			return false;
		}
		$newProcess = array();
		$newProcess = $this->getJieDian($allJiedianList,$liuchengOne);
		// print_r($newProcess);exit;
		return $newProcess;
	}

	/**
	 * 递归获取节点名称
	 * @param  [type] $allJiedianList [description]
	 * @return [type]                 [description]
	 */
	private function getJieDian($allJiedianList,$currentProcess){
		// print_r($allJiedianList);exit;
		$newProcess  = array();
		foreach ($allJiedianList as $k => $v) {
			$data = array();
			if($v["sort"]==0){
				//读取第一个节点
				$data["name"] = $v["name"];
				$data["isshenhe"] = 1;//是否审核
				$data["isnow"]    = $v["sort"]==$currentProcess["nowsort"]?1:0;//当前节点
				array_push($newProcess,$data);
			}else{
				//读取其他节点
				$data["name"] = $this->getNextJieDianName($allJiedianList[$k-1],$currentProcess);
				if($data["name"]){

					//判断是否重复
					$ishas = false;
					foreach ($newProcess as $ka => $va) {
						if($allJiedianList[$k-1]["name"]==$data["name"]){
							$ishas = true;
							break;
						}else{
							continue;
						}
					}
					if(!$ishas){
						//判断是否为当前节点
						if($allJiedianList[$k-1]["sort"]<=$currentProcess["nowsort"]){
							$data["isshenhe"] = 1;//是否审核
							$data["isnow"]    = $allJiedianList[$k-1]["sort"]==$currentProcess["nowsort"]?1:0;//当前节点
						}
						array_push($newProcess,$data);
					}else{
						continue;
					}
				}
			}
			
		}
		return $newProcess;
		
	}


	/**
	 * 根据数据表判断判断当前节点是否满足。获取下一个节点的名称
	 * @return [type] [description]
	 */
	private function getNextJieDianName($currentJiedian,$currentProcess=""){
		$jiedianSort           = $this->getJiedianSort($currentJiedian,$currentProcess);
		$liuchengInfo_["name"] = "";
		if($jiedianSort>=0){
			//获取当前节点名称
			$liuchengInfo_ = M("oa_jiedian")->field("name")->where(array("pid"=>$currentProcess["mid"],"sort"=>$jiedianSort))->find();
		}
		
		return $liuchengInfo_["name"];
	}

	/**
	 * [getnextjiedian description]
	 * @param  [type] $sort       [description]
	 * @param  [type] $liuchenobj [description]
	 * @param  [type] $arr        [description]
	 * @return [type]             [description]
	 */
	public function getnextjiedian($sort,$liuchenobj,$arr){
		$data_begin = M('oa_jiedian')->where("sort=$sort && pid=".$liuchenobj["mid"])->find();
		$nextsort   = $this->getJiedianSort($data_begin,$liuchenobj);
		$arr[]      = $nextsort;
		if($nextsort!=-1){
			return $this->getnextjiedian($nextsort,$liuchenobj,$arr);
		}else{
			return $arr;
		}
	}


	/**
	 * 获取当前节点的sort
	 * @param  [type] $currentJiedian [description]
	 * @param  string $currentProcess [description]
	 * @return [type]                 [description]
	 */
	function getJiedianSort($currentJiedian,$currentProcess=""){
		// print_r($currentJiedian["sort"]<=$currentProcess["nowsort"]);
		$jiedianSort = -1;
		if($currentJiedian['nextjdlist']==""){
			return $jiedianSort;
		}
		$res_other_jiedian = M('oa_jiedian')->field('a.*')->join("a join boss_oa_liuchen_m b on a.pid=b.id")->where("a.id in (".$currentJiedian['nextjdlist'].")")->select();//找出所有下一节点

		// print_r(M('oa_jiedian')->getLastsql());exit;
		//获取当前流程数据信息
		$alldata     = M('oa_'.$currentProcess['mid'])->where("id=".$currentProcess['alldata'])->find();//取所有表单数据
		$shenghejilu = json_decode($currentProcess['shenghejilu'],true);//取得所有审核记录
		// print_r($shenghejilu);exit;
		//当前节点的下一个节点的集合,循环判断执行条件
		foreach ($res_other_jiedian as $k => $v) {
			if($v['tiaojian'] == ''){
				return $v['sort'];
			}
			$tiaojian_arr = array();
			$res_tiaojian = M('oa_tiaojian')->where("jid=".$v['id'])->select();//此节点所有相关条件


			foreach ($res_tiaojian as $val) {
				if(substr($val['key'],0,1) == 'x'){
					if(!is_numeric($val['val'])){
						$d2 = '"'.$val['val'].'"';
					}else{
						$d2 = $val['val'];
					}
					if(!is_numeric($alldata[$val['key']])){
						$d1 = "'".$alldata[$val['key']]."'";
					}else{
						$d1 = $alldata[$val['key']];
					}
					if($val['type'] == '='){
						$val['type'] = '==';
					}
					$str                        = 'return '.$d1.$val['type'].$d2.';';
					$tiaojian_arr[$val['code']] = eval($str);
				}else{
					//默认上一步同意
					if($val['key']==$currentJiedian['id'])$tiaojian_arr[$val['code']]=true;
				}
				
			}
			$t  = str_replace('[', ' $tiaojian_arr[', $v['tiaojian']);
			$t  = str_replace(']', '] ', $t);

			$p  = htmlspecialchars_decode('return '.$t.';');
			$jg = eval($p);
			if($jg){
				return $v['sort'];
			}
		}
		return $jiedianSort;
	}
}
 ?>