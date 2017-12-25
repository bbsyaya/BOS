<?php
/**
* 办公用品分类
*/
namespace Common\Service;
class OfficeCateService extends BaseService
{
	
	/**
	 * 根据条件获取分类列表
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getAppListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_office_apply")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 获取条数
	 * @param  [type] $where_ [description]
	 * @param  string $order_ [description]
	 * @return [type]         [description]
	 */
	function getAppListCountByWhere($where_){
		$list = M("oa_office_apply")->field("id")->where($where_)->count();
		return $list;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneAppByWhere($where_,$fields_){
		$list = M("oa_office_apply")->field($fields_)->where($where_)->find();
		return $list;
	}
	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveAppData($where_,$data){
		$row = M("oa_office_apply")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addAppData($data){
		$row = M("oa_office_apply")->add($data);
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
	function getProductListByWhere($where_,$fields_="",$order_="",$firstRow="",$lastRow=""){
		$list = M("oa_office_product")->field($fields_)->where($where_)->order($order_)->limit($firstRow.",".$lastRow)->select();
		return $list;
	}

	/**
	 * 根据条件获取分类列表——use sql
	 * @param  [type] $where_   [description]
	 * @param  [type] $order_   [description]
	 * @param  [type] $firstRow [description]
	 * @param  [type] $lastRow  [description]
	 * @return [type]           [description]
	 */
	function getProductListByWhere_sql($where_,$order_="",$firstRow="",$lastRow=""){
		$sql = "SELECT 
				  p.*
				FROM
				  `boss_oa_office_product` AS p where {$where_} order by {$order_} limit {$firstRow},{$lastRow}";
	    $model = new \Think\Model();
	    $list = $model->query($sql);
	    return $list;

	}

	/**
	 * 获取条数
	 * @param  [type] $where_ [description]
	 * @param  string $order_ [description]
	 * @return [type]         [description]
	 */
	function getProductListCountByWhere($where_){
		$list = M("oa_office_product")->field("id")->where($where_)->count();
		return $list;
	}

	/**
	 * 添加数据
	 * @param [type] $data [description]
	 */
	function addProduct($data){
		$row = M("oa_office_product")->add($data);
		return $row;
	}

	/**
	 * 获取一个对象
	 * @param  [type] $where_  [description]
	 * @param  [type] $fields_ [description]
	 * @return [type]          [description]
	 */
	function getOneProductByWhere($where_,$fields_){
		$list = M("oa_office_product")->field($fields_)->where($where_)->find();
		return $list;
	}

	/**
	 * 保存数据
	 * @param  [type] $where_ [description]
	 * @param  [type] $data   [description]
	 * @return [type]         [description]
	 */
	function saveProductData($where_,$data){
		$row = M("oa_office_product")->where($where_)->save($data);
		return $row;
	}

	/**
	 * 添加导入操作 20160406
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
    public function doExcelToArray($data){
        $returnArray = array(
						"status" =>"false",
						"data"   =>array()
        	);
        if(is_array($data)&&count($data)>0){
			$badArray   = array();//没查库就错误的数组
			$rightArray = array();//最后成功的数组
			$toDbArray  = array();//要去查库的数组
            foreach($data as $k=>$v){
                $isDoDb=true;
                $msgStr="";
                if($isDoDb){//操作开关为真的时候，插入可以查库的数组
                    $toDbArray[$k]=$v;
                }
                else{//操作开关为假的时候，这行执行完啦，整理错误
                    $badArray[]=array($k,$msgStr);
                }
            }
			$doExcelDbResult       = $this->makeExcelToProductsTableList($toDbArray);
			$returnArray["status"] = $doExcelDbResult["status"];
			$returnArray["data"]   = $doExcelDbResult["data"];
        }else{
            $returnArray["status"] = false;
        }
        return $returnArray;
    }

	/**
	* 将excel数据整理成表格形式
	*/
	public function makeExcelToProductsTableList($data){
		$rel  = array(
			"status" =>true,
			"data"    =>array(),
			);
		$list    = array();
		$row     = 0;
		$preName = "";
		foreach ($data as $k => $v) {
			if(!$v["A"] && !$v["B"] && !$v["C"]){
				continue;
			}
			$name = trim($v["B"]);
			if(!$name){
				$name = $preName;
			}else{
				$preName = $name;
			}
			$one["name"]   = $name;
			$one["format"] = trim($v["C"]);
			$one["price"]  = trim($v["E"]);
			$one["unit"]   = trim($v["D"]);
			$one["remark"] = trim($v["H"]);
			$msg           = "导入失败";

			//检查是否存在，存在就修改，不存在就添加
			$where   = "";
			$where   = "name='".$one["name"]."' and format='".$one["format"]."'";
			$off_one =  M("oa_office_product")->field("id")->where($where)->find();
			if($off_one){
				//编辑
				$where_u = "id=".$off_one["id"];
				$row     = $this->saveProductData($where_u,$data);
				unset($where_u);
				if($row){
					$msg = $one["name"]."-规格：".$one["format"]."已存在-导入成功";
				}else{
					$msg = $one["name"]."-规格：".$one["format"]."已存在-没做任何修改";
				}
			}else{
				//添加
				$one["dateline"] = date("Y-m-d H:i:s",time());
				$one["uid"]      = UID;
				$one["stock"]    = 0;
				$row = $this->addProduct($one);
				if($row) $msg = "导入成功";
			}
			$rel["data"][] = $one["name"]."-规格：".$one["format"].$msg;
		}
		if($row)$rel["status"]  = true;
		return $rel;
	}

}
?>