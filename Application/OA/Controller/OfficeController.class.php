<?php
/**
* 办公用品分类,办公用品申请
*/
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
use Common\Service;
class OfficeController extends BaseController
{
	private $officeCateSer;
	private $is_office_adminer=false;
	function _initialize(){
		parent::_initialize();
		$this->officeCateSer = !$this->officeCateSer ? new Service\OfficeCateService() : $this->officeCateSer;
		$this->isAdmin();
	}

	/**
	 * 检查当前用户是否为超级管理员或者行政主管兼前台
	 * @return boolean [description]
	 */
	function isAdmin(){
		// print_r($_SESSION["userinfo"]);exit;
		$is_root = isSurperAdmin(UID);
		$duty_name = $_SESSION["userinfo"]["duty_name"];
		if(($is_root==1) || $duty_name=="行政主管兼前台"){
			$this->is_office_adminer=true;
		}
		$this->assign("is_office_adminer",$this->is_office_adminer);
	}

	/**
	 * 申请列表
	 * @return [type] [description]
	 */
	function applyList(){
		$map["name"]       = I("name");
		$map["time_sdate"] = I("time_sdate");
		$map["time_edate"] = I("time_edate");
		$this->assign("map",$map);
		$where = " where a.is_delete=0  AND u.`id`>0 ";
		//如果不是超级管理员，就读取自己的申请
		if(!$this->is_office_adminer){
			$where .= " and a.uid=".UID;
		}
		if($map["name"]){
			$where .= " and p.name like '%".$map["name"]."%'";
		}
		$last_startTime = " 00:00:00";
		$last_endTime   = " 23:59:59";
		//时间查询
		if($map["time_sdate"] && !$map["time_edate"]){
			$where .= " and a.dateline >='".$map["time_sdate"]." 00:00:00'";
		}
		if(!$map["time_sdate"] && $map["time_edate"]){
			$where .= " and a.dateline <='".$map["time_edate"]." 23:59:59'";
		}
		if($map["time_sdate"] && $map["time_edate"]){
			$where .= " and a.dateline >='".$map["time_sdate"]." 00:00:00' and a.dateline <='".$map["time_edate"]." 23:59:59'";
		}

		//排序
		$order      = " order by";
		$order_applyno     = empty($_REQUEST['order_no']) ? "desc" : $_REQUEST['order_no'];
		$order_depart     = empty($_REQUEST['order_depart']) ? "desc" : $_REQUEST['order_depart'];
		if($order_depart=="asc"){
			$order .= " a.depart_id asc";
			$order_depart = "desc";
		}else{
			$order .= " a.depart_id desc";
			$order_depart = "asc";
		}
		if($order_applyno=="asc"){
			$order .= " ,a.apply_no asc";
			$order_applyno = "desc";
		}else{
			$order .= " ,a.apply_no desc";
			$order_applyno = "asc";
		}
		$this->assign("order_applyno",$order_applyno);
		$this->assign("order_depart",$order_depart);
		$offSer = new \OA\Service\OfficeService();
		$data = $offSer->applyListSer($where,$order);
		// print_r($data);exit;
		$this->assign("list",$data["data"][0]);
		$this->assign("page",$data["page"]);
		$this->assign("total_data",$data["total"][0]);
		$this->assign("get_apply_status",C('OPTION.apply_status'));
		$this->display();
	}


	/**
	 * 添加办公用品
	 */
	function addProduct(){
		$id             = I("id");
		$data["name"]   = trim(I("officename"));
		$data["format"] = trim(I("format"));
		$data["price"]  = trim(I("price"));
		$data["stock"]  = trim(I("stock"));
		$data["remark"] = trim(I("remark"));
		$data["unit"]   = trim(I("unit"));
		$ret            = array("msg"=>"操作失败","code"=>500);
		if($id){
			$row = $this->officeCateSer->saveProductData(array("id"=>$id),$data);
			if($row){
				$ret = array("msg"=>"修改成功","code"=>200);
			}else{
				$ret = array("msg"=>"未做任何修改","code"=>200);
			}
		}else{
			//检查是否存在
			$where["name"]   = $data["name"];
			$where["format"] = $data["format"];
			$off_one         = $this->officeCateSer->getProductListCountByWhere($where);
			if($off_one>0){
				$ret = array("msg"=>"系统含有【".$data["name"]."--".$data["format"]."】的办公用品，请检查","code"=>500);
				$this->ajaxReturn($ret);
				exit;
			}
			$data["dateline"] = date("Y-m-d H:i:s",time());
			$data["uid"]      = UID;
			$row = $this->officeCateSer->addProduct($data);
			if($row){
				$ret = array("msg"=>"添加成功","code"=>200);
			}
		}
		$this->ajaxReturn($ret);
	}

	/**
	 * 办公用品列表
	 * @return [type] [description]
	 */
	function productList(){
		$map["name"]       = I("name");
		$map["format"]     = I("format");
		$map["time_sdate"] = I("time_sdate");
		$map["time_edate"] = I("time_edate");
		$this->assign("map",$map);
		$where["status"] = 0;
		$where_sql = "p.status=0";
		if($map["name"]){
			$where["name"] = array("like","%".$map["name"]."%");
			$where_sql .= " and p.name like '%".$map["name"]."%'";
		}
		if($map["format"]){
			$where["format"] = array("like","%".$map["format"]."%");
			$where_sql .= " and p.format like '%".$map["format"]."%'";
		}
		if($map["time_sdate"] && !$map["time_edate"]){
			$where["dateline"] = array("EGT",$map["time_sdate"]);
			$where_sql .= " and p.dateline >='".$map["time_sdate"]."'";
		}
		if(!$map["time_sdate"] && $map["time_edate"]){
			$where["dateline"] = array("ELT",$map["time_edate"]);
			$where_sql .= " and p.dateline <='".$map["time_edate"]."'";
		}
		if($map["time_sdate"] && $map["time_edate"]){
			$where["dateline"] = array("EGT",$map["time_sdate"]);
			$where["dateline"] = array("ELT",$map["time_edate"]);
			$where_sql .= " and p.dateline >='".$map["time_sdate"]."' and p.dateline <='".$map["time_edate"]."'";
		}
		$count    = $this->officeCateSer->getProductListCountByWhere($where);
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$list     = $this->officeCateSer->getProductListByWhere_sql($where_sql,"dateline desc",$page->firstRow,$page->listRows);
		$this->assign("list",$list);
		$this->assign("page",$page->show());
		$this->display();
	}

	/**
	 * 添加申领
	 */
	function addApply(){
		$data["apply_no"]      = I("apply_no");
		$data["product_id"]  = I("product_id");
		$data["uid"]         = UID;
		$data["dateline"]    = date("Y-m-d H:i:s",time());
		$uid = $data["uid"];
		$sql = "select 
				  d.id,
				  d.name 
				from
				  `boss_user` as u 
				  left join `boss_user_department` as d 
				    on u.dept_id = d.id 
				where u.id = {$uid}";
		$model = new \Think\Model();
		$userOne = $model->query($sql);
		$data["depart_id"]   = $userOne[0]["id"];
		$data["depart_name"] = $userOne[0]["name"];
		$data["price"]       = I("price");
		$data["total_money"] = $data["apply_no"]*$data["price"];
		$row = $this->officeCateSer->addAppData($data);
		$ret = array("msg"=>"添加失败","code"=>500);
		if($row){
			$ret = array("msg"=>"添加成功","code"=>200);
		}
		$this->ajaxReturn($ret);
	}

	/**
	 * 直接修改为已发放状态
	 * @return [type] [description]
	 */
	function updateStock(){
		$ids = I("ids");
		$where["id"] = array("in",$ids);
		$row = $this->officeCateSer->saveAppData($where,array("status"=>1));
		$ret = array("code"=>500,"msg"=>"没有数据被更新，请检查");
		if($row) $ret = array("code"=>200,"msg"=>"处理成功");
		$this->ajaxReturn($ret);
	}

	/**
	 * 修改状态-发放
	 * @return [type] [description]
	 */
	function updateStatus(){
		$ids = I("id");
		$ret = array("msg"=>"发放失败","code"=>500);
		//减掉库存
		$sql = "SELECT 
				  a.`product_id`,
				  a.`apply_no`,
				  a.`status`,
				  p.`stock`,
				  p.name,
				  a.id,
				  u.real_name 
				FROM
				  `boss_oa_office_apply` AS a 
				  LEFT JOIN `boss_oa_office_product` AS p 
				    ON a.`product_id` = p.`id` 
				  LEFT JOIN boss_user AS u 
				    ON a.`uid` = u.`id` 
				WHERE a.id IN ({$ids}) 
				  AND a.`status` = 0 ";
		$model = new \Think\Model();
		$list = $model->query($sql);
		if($list){
			$relt_str = "";
			foreach ($list as $k => $v) {
				if($v["status"]!=0)continue;
				$where_["id"] = $v["product_id"];
				$data_["stock"] = $v["stock"] - $v["apply_no"];
				if($data_["stock"]>=0){
					//修改库存
					$row = $this->officeCateSer->saveProductData($where_,$data_);
					//发放
					$where["id"] = $v["id"];
					$data["status"] = 1;
					$row = $this->officeCateSer->saveAppData($where,$data);
					$ret = array("msg"=>"发放成功","code"=>200);
				}else{
					$relt_str .= $v["real_name"]."的".$v["name"]."库存数量为".$v["stock"]."，库存不足发放失败;";
				}
			}
			if($relt_str)$ret["msg"] = $relt_str;
		}
		$this->ajaxReturn($ret);
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	function deleteItem(){
		$ids = I("ids");
		$where["id"] = array("in",$ids);
		$data["is_delete"] = 1;
		$row = $this->officeCateSer->saveAppData($where,$data);
		$ret = array("msg"=>"删除成功","code"=>200);
		$this->ajaxReturn($ret);
	}

	/**
	 * 导入办公用品
	 * @return [type] [description]
	 */
	function importDatado(){
		import("Org.Util.PHPExcel");
		import("Org.Util.PHPExcel.Reader.Excel5");
		import("Org.Util.PHPExcel.Reader.Excel2007");
		$upload = new \Think\Upload();// 实例化上传类
		//检查客户端上传文件参数设置
		$result = array("msg"=>"上传失败，请联系超级管理员","code"=>500,"data"=>"");
		//待删除的目录 除过当前天数
		//要保存的目录 今天
		//删除除过今天的所有目录
		for($t=1;$t<32;$t++){
			$day=$t;
			if($t<10)$day="0".$t;
			if($day!=date("d")){
				@deldir("./upload/excel/".$day."/");
			}
		}
		$upload->rootPath = "./upload/excel/".date("d")."/";//保存根路径
		$upload->savePath = "./upload/excel/".date("d")."/";//保存根路径
		$upload->saveRule = 'uniqid';//是否自动命名
		if (! file_exists ( $upload->savePath )) {
			mkdir ( $upload->savePath );
		}
		$upload->uploadReplace = true;
		$info = $upload->upload ();
		if (! $info) {
			$result["msg"] = $upload->getError ();
			$this->ajaxReturn ($result);
		}else{
			$file = $info["files"];
			$savename ['savename'] = $filePath = $upload->rootPath .$file["savepath"]. $file ['savename'];
			$savename ['name'] = $file ['name'];
			//读取excel数据
			$PHPReader = new \PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($filePath)){
				$PHPReader = new \PHPExcel_Reader_Excel5();
				if(!$PHPReader->canRead($filePath)){
					$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
					$this->ajaxReturn ($result);
					return ;
				}
			}
			
			$PHPExcel     = $PHPReader->load($filePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allColumn    = $currentSheet->getHighestColumn();
			$allRow       = $currentSheet->getHighestRow();

			$excelData = array();
			//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
			for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
			//从哪列开始，A表示第一列
				for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
					//数据坐标
					$address = $currentColumn . $currentRow;
					//读取到的数据，保存到数组$arr中
					$excelData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
				}
			}
			if(count($excelData)==0){
				unlink ($filePath);
				$result = array("msg"=>"上传文件不存在，请联系超级管理员","code"=>500,"data"=>"");
				$this->ajaxReturn($result);
			}
			//转换为数组
			$result1 = $this->officeCateSer->doExcelToArray($excelData);
			$logdata = implode(",", $result1["data"]);

			if($result1["status"]){
				unlink ($filePath);
				$result = array("msg"=>"导入成功","code"=>200,"data"=>$savename,"logdata"=>$logdata);
			}
			$this->ajaxReturn($result);
		} 
	}

	/**
	 * [导出采购]
	 * @return [type] [description]
	 */
	function exportPurchase(){
		$where             = " where a.is_delete=0";
		$map["name"]       = trim(I("name"));
		$map["time_sdate"] = trim(I("time_sdate"));
		$map["time_edate"] = trim(I("time_edate"));
		if($map["name"]){
			$where .= " and p.name like '%".$map["name"]."%'";
		}
		if($map["time_sdate"] && !$map["time_edate"]){
			$where .= " and a.dateline >='".$map["time_sdate"]." 00:00:00'";
		}
		if(!$map["time_sdate"] && $map["time_edate"]){
			$where .= " and a.dateline <='".$map["time_edate"]." 23:59:59'";
		}
		if($map["time_sdate"] && $map["time_edate"]){
			$where .= " and a.dateline >='".$map["time_sdate"]." 00:00:00' and a.dateline <='".$map["time_edate"]." 23:59:59'";
		}
		$excelSer = new \OA\Service\ExcelLogicService();
		$result = $excelSer->excelOfficeNeedBuy($where);
		if($result["msg"]){
			$this->success($result["msg"]);exit;
		}
	}

	/**
	 * 添加
	 */
	function add(){
		 $this->ajaxReturn($this->fetch('add'));
	}

	/**
	 * 编辑
	 * @return [type] [description]
	 */
	function editProduct(){
		$id = I("id");
		$one = $this->officeCateSer->getOneProductByWhere(array("id"=>$id));
		$this->assign("data",$one);
		$this->ajaxReturn($this->fetch('add'));
	}

	/**
	 * 删除
	 * @return [type] [description]
	 */
	function delProduct(){
		$ids       = I("id");
		//检查是否有人申请该商品
		$ids_array = explode(",", $ids);
		$result    = array("msg"=>"");
		$msg       = "系统有误，请联系管理员";
		if($ids_array){
			$msg       = "";
			foreach ($ids_array as $k => $v) {
				if($v){
					$has = $this->officeCateSer->getOneAppByWhere(array("product_id"=>$v,"is_delete"=>0),"id,product_id");
					$proOne = $this->officeCateSer->getOneProductByWhere(array("id"=>$v),"name,format");
					if($has){
						$msg .= "--".$proOne["name"]."[".$proOne["format"]."]已经有人申请了，暂时不能删除<br/>";
					}else{
						$one = $this->officeCateSer->saveProductData(array("id"=>$v),array("status"=>1));
						$msg .= "--".$proOne["name"]."[".$proOne["format"]."]删除成功<br/>";
					}
				}
			}
		}
		
		$result = array("msg"=>$msg);
		$this->ajaxReturn($result);
	}
}
?>