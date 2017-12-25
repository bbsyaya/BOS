<?php
namespace Common\Controller;
use Think\Controller;
use Home\Model\AuthRuleModel;
use Common\Service;
class BaseController extends Controller {//公用方法

    public function _initialize(){

    	$this->setReferUrl();
		$this->optiondata = C('OPTION');
		define('UID', is_login());
		if( !UID ){//登录页面
			$this->redirect('/Home/Public/login');
		}
		//功能权限判断，如果失败则后退
		//$this->check_rule_fun();

		$menusList = $this->getMenus();
		// print_r($menusList);exit;

		// $menusList = $this->initCheckVoteCount($menusList);
		$this->assign('__MENU__', $menusList);
		$_SESSION["USER_HAS_AUTH_MENU_".UID] = $menusList;
		//判断一级菜单的第一项是否授权
		// if($_REQUEST["fmenu"]==="fmenu"){
		// 	if($menusList["child"]){
		// 		$this->isredirect_($menusList["child"],'/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME);
		// 	}
		// }
		$current_url = '/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME;
		$this->assign("currentUrl",$current_url);
	}

	/**
	 * 获取来源地址
	 */
	function setReferUrl(){
		$refer = C("WEB_URL") . $_SERVER['REQUEST_URI'];
		$_SESSION['REFER_URL']=$refer;
	}

	/**
	 * 初始化检查是否有新的投票消息
	 * @return [type] [description]
	 */
	function initCheckVoteCount($menusList){
		$new_menulist = $menusList;
		if($new_menulist["child"]){
			foreach ($new_menulist["child"] as $k => $v) {
				if(strpos($v["name"],"/myParticList")){
					//检查我是否有新投票
					$depart_id = $_SESSION["userinfo"]["depart_id"];
					$voteSer   = new  Service\VoteService();
					$newCount  = $voteSer->checkIsHasNewVote($depart_id,UID);
					if($newCount>0){
						$new_menulist["child"][$k]["title"] = $v["title"]."<i class=\"new-tis\">".$newCount."</i>";
					}
				}else{continue; }
			}
		}
		return $new_menulist;
	}

	/*
	 * token
	 * */
	public function token(){
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12);
		$gcm  = "/finanInter";
		$key = "1qaz#EDC5tgb&UJM";
		$middle = base64_encode($gcm.$key);
		$date_time = date('YmdHi',time());
		$date_time = base64_encode($date_time.'L');
		$token = $uuid.$middle.$date_time;
		return $token;
	}

    public function exceltoarray($file_name,$exceltype){//excel转array
    	import("Org.Util.PHPExcel");
        $objReader = \PHPExcel_IOFactory::createReader($exceltype);
        $objPHPExcel = $objReader->load($file_name,$encode='utf-8');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = ord($sheet->getHighestColumn()); // 取得总列数
        for($i=1;$i<=$highestRow;$i++)
                {   
                	$data=array();
                	for($j=65;$j<=$highestColumn;$j++){
                		if($i==1){
                			$key_arr[chr($j)]=$objPHPExcel->getActiveSheet()->getCell(chr($j).$i)->getValue();
                		}else{
                			$data[$key_arr[chr($j)]]=$objPHPExcel->getActiveSheet()->getCell(chr($j).$i)->getValue();
                		}
                	}
                	if($i!=1)$return[]=$data;
                } 
        return $return;
    }

    public function uplaodfile($name,$dir){

    	if(!empty($_FILES[$name]['tmp_name'])){
    		$upload = new \Think\Upload();// 实例化上传类
		    $upload->maxSize   =     10000000000 ;// 设置附件上传大小
		    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg' , 'xlsx', 'zip' , 'rar', 'xls','pdf','txt','doc','docx');// 设置附件上传类型

		    $upload->rootPath  =     $dir; // 设置附件上传根目录
		    $upload->savePath  =     ''; // 设置附件上传（子）目录

		    // 上传文件 
		    $upload->__set('saveName',time().rand(100,999));
		    $info   =   $upload->upload();
		    if(!$info) {// 上传错误提示错误信息
		        return $upload->getError();
		    }else{// 上传成功
                return $info;	        
		    }
    	}else{
    		return '没有上传数据';
    	}
    }

	/**
	 * 权限检测
	 * @param string  $rule    检测的规则
	 * @param string  $mode    check模式
	 * @return boolean
	 */
	public function checkRule($rule, $type=AuthRuleModel::RULE_URL, $mode='url') {
		static $Auth = null;
		if (!$Auth) {
			$Auth = new \Think\Auth();
		}
		return $Auth->check($rule,UID,$type,$mode);
	}


	/**
	 * 列表
	 * @param       $model
	 * @param array $where
	 * @param bool  $field
	 * @return mixed
	 */
	protected function lists ($model,$where=array(), $field=true) {
		if(is_string($model)){
			$model  =   D($model);
		}

		$datas = $model->getList($where, $field);
		$total = $model->totalPage;
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page = new \Think\Page($total, $listRows);
		if($total>$listRows){
			$page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
		}
		$p =$page->show();
		$this->assign('_page', $p? $p: '');
		$this->assign('_total',$total);

		return $datas;
	}
	public function getpagelist($total){
		$page = new \Think\Page($total, 10);
        if($total>10){
            $page->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        }
        $p =$page->show();
        $this->assign('_page', $p? $p: '');
	}

	/**
	* 获取目录信息
	* @return array
	*/
	final function getMenus($controller=CONTROLLER_NAME, $action=ACTION_NAME) {
		//$menus =   session('MENU_LIST.'.$controller.'/'.$action);
		$menus = "";
		if(empty($menus)){
			$optionName = strtolower(CONTROLLER_NAME.'/'.ACTION_NAME);
			// 获取主菜单
			
			$menus['main']=$this->getmenu(0);

			// // 查找当前子菜单
			// $pid = M('auth_rule')->where("pid >0 AND name like '%/{$controller}/".ACTION_NAME."%'")->order('sort desc')->getField('pid');
			// // print_r(M('auth_rule')->getLastsql());exit;
			// if($pid){
			// 	// 查找当前主菜单
			// 	$nav =  M('auth_rule')->find($pid);
			// 	if($nav['pid']){
			// 		$nav    =   M('auth_rule')->find($nav['pid']);
			// 	}

			// 	//获取当前用户所有的权限菜单
			// 	$current_user_all_menus_ids = $this->getCurrentUserMenusList();
			// 	foreach ($menus['main'] as $key => $item) {
			// 		// 获取当前主菜单的子菜单项
			// 		if($item['id'] == $nav['id']){
			// 			$menus['main'][$key]['class'] ='menuFocus';
			// 			$menus['_nav1']               = $item['title'];
			// 			$where                        =   array();
			// 			$where['pid']                 =   $item['id'];
			// 			//查询2级菜单
			// 			$has_ids = $this->getCurrentUserMenus($where,$current_user_all_menus_ids);
			// 			unset($where);
			// 			$map["id"]  = array("in",$has_ids);
			// 			$map['pid'] =   $item['id'];
			// 			$menuList   = M('auth_rule')->where($map)->field('id,pid,title,name')->order('sort desc')->select();
			// 			unset($map);
			// 			foreach ($menuList as $key=>$subItem) {
							
			// 				if($optionName  == strtolower($subItem['name'])) {
			// 					$menus['_nav2']          = $subItem['title'];
			// 					$menuList[$key]['class'] = 'focus';
			// 				}
			// 			}
			// 			$menus['child'] = $menuList;
			// 		}
			// 	}
			// 	unset($nav);
			// 	unset($current_user_all_menus_ids);
			// }
			session('MENU_LIST.'.$controller.'/'.$action, $menus);
		}
		$thisconamestr = '/'.MODULE_NAME.'/';
		$thisconamelen = strlen($thisconamestr);
		foreach ($menus['main'] as $key => $value) {
			if(substr($value['name'], 0,$thisconamelen)!=$thisconamestr)unset($menus['main'][$key]);
		}

		return $menus;
	}

	public function getmenu($id){

		$menus    =   M('auth_rule')->join('a left join boss_auth_rule b on b.is_hide = 0 && a.id=b.pid')->where("a.pid=$id && a.is_hide=0 && a.name like '/".MODULE_NAME."%'")->group('a.id')->order('a.sort desc')->field('a.id,a.title,a.name,b.id as bid,a.pid,a.img,a.sort')->select();
		//$menus    =   M('user')->limit('0,1')->select();

		
		foreach ($menus as $key => $item) {
			// 判断菜单权限
			if (!checkRule_xq(strtolower($item['id'])) ) {
				unset($menus[$key]);
				continue;
			}
			if($optionName  == strtolower($item['name'])){
				$menus[$key]['class'] = 'menuFocus';
			}
			if($item['bid']!='')$menus[$key]['child']=$this->getmenu($item['id']);
		}	
		return $menus;
	}

	/**
	* //判断当前连接是否在子菜单中，如果没有，跳转到子菜单第一项
	* @param  [type] $menusList [description]
	* @return [type]            [description]
	*/	
	private function isredirect_($menusList,$url_name){
		$is_has = false;
		foreach ($menusList as $k => $v) {
			if(strtolower($url_name)==strtolower($v["name"])){
				$is_has = true;
			}
		}
		if($is_has==false){
			if($menusList[0]["name"]){
				$this->redirect($menusList[0]["name"]);
				exit;
			}
		}
	}


public function downloadlist($data,$list,$filename2=''){//导出 $list格式array(array('id','用户ID'),array('name','名称'))
	if(I('get.xq')=='a'){
		echo count($data);
		exit();
	}
	if(count($data)>3000){
		foreach ($list as $key => $value) {
			$title[$value[0]]=$value[1];
		}

		$csvObj = new \Think\Csv();
		$csvObj->put_csv($data, $title, $filename2);
		exit();
	}
	import("Org.Util.PHPExcel");
	$asc=65;
	$ascarr=array();
	foreach ($list as $key => $value) {
		if($asc<=90)$str=chr($asc);
		else{
			$str='A'.chr($asc-26);
		}
		$ascarr[$str]['zd']=$value[0];
		$ascarr[$str]['name']=$value[1];
		$asc++;
	}


// 创建一个处理对象实例 
   $objExcel = new \Org\Util\PHPExcel(); 

// 创建文件格式写入对象实例, uncomment 
   //$objWriter = new PHPExcel_Writer_Excel5($objExcel);     // 用于其他版本格式 
   //or
//
//

//$objWriter->setOffice2003Compatibility(true); 


//设置文档基本属性 
   $objProps = $objExcel->getProperties(); 
   $objProps->setCreator("Zeal Li"); 
   $objProps->setLastModifiedBy("Zeal Li"); 
   $objProps->setTitle("Office XLS Test Document"); 
    $objProps->setSubject("Office XLS Test Document, Demo"); 
   $objProps->setDescription("Test document, generated by PHPExcel."); 
   $objProps->setKeywords("office excel PHPExcel"); 
    $objProps->setCategory("Test"); 
   //设置当前的sheet索引，用于后续的内容操作。 
//一般只有在使用多个sheet的时候才需要显示调用。 
//缺省情况下，PHPExcel会自动创建第一个sheet被设置SheetIndex=0 
   $objExcel->setActiveSheetIndex(0); 

    $objActSheet = $objExcel->getActiveSheet(); 

//设置当前活动sheet的名称 
    $objActSheet->setTitle('列表'); 


//设置单元格内容 

//由PHPExcel根据传入内容自动判断单元格内容类型 
    $num=1;
    foreach ($ascarr as $key => $value) {
    	$weizhi=$key.$num;
    	$objActSheet->setCellValue($weizhi, $value['name']);
    }
    foreach ($data as $key => $value) {
    	$num++;
    	foreach ($ascarr as $k => $v) {
	    	$weizhi=$k.$num;
	    	$objActSheet->setCellValue($weizhi, $value[$v['zd']]);
	    }
    }

   //合并单元格 
    //$objActSheet->mergeCells('B1:C22'); 

   //分离单元格 
   // $objActSheet->unmergeCells('B1:C22'); 
   
//设置单元格样式 

   //设置宽度 
  // $objActSheet->getColumnDimension('B')->setAutoSize(true); 
  // $objActSheet->getColumnDimension('A')->setWidth(30); 

  //  $objStyleA5 = $objActSheet->getStyle('A5'); 
   
//设置单元格内容的数字格式。 

//如果使用了 PHPExcel_Writer_Excel5 来生成内容的话， 
//这里需要注意，在 PHPExcel_Style_NumberFormat 类的 const 变量定义的 
//各种自定义格式化方式中，其它类型都可以正常使用，但当setFormatCode 
   //为 FORMAT_NUMBER 的时候，实际出来的效果被没有把格式设置为"0"。需要 
   //修改 PHPExcel_Writer_Excel5_Format 类源代码中的 getXf($style) 方法， 
//在 if ($this->_BIFF_version == 0x0500) { （第363行附近）前面增加一 
   //行代码: 
//if($ifmt === '0') $ifmt = 1; 

//设置格式为PHPExcel_Style_NumberFormat::FORMAT_NUMBER，避免某些大数字 
//被使用科学记数方式显示，配合下面的 setAutoSize 方法可以让每一行的内容 
   //都按原始内容全部显示出来。 

//添加一个新的worksheet 
//   $objExcel->createSheet(); 
 //  $objExcel->getSheet(1)->setTitle('测试2'); 

$objWriter = new \PHPExcel_Writer_Excel5($objExcel); // 用于 2007 格式 
   $outputFileName = $filename2.".xls"; 
//到文件 
//$objWriter->save('./upload/'.$outputFileName); 
//or 
//到浏览器 
 ob_clean();  
   header("Content-Type: application/octet-stream;charset=utf-8"); 
   header('Content-Disposition:inline;filename="'.$outputFileName.'"'); 
   header("Content-Transfer-Encoding: binary"); 
   header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
   header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
   header("Pragma: no-cache"); 
   $objWriter->save('php://output'); 

	}


	/*  OA部分 */

	public function getpage($count,$num=10){
	    $p=I('get.p');
	    if($p>=ceil($count/$num))$p=ceil($count/$num);
	    if(!$p || $p<1)$p=1;
	    return (($p-1)*10).','.$num;
	}
	public function actionlog($data,$lcid,$jdid){//记录行为日志
        M('oa_zixingjilu')->add(array('addtime'=>date('Y-m-d H:i:s'),'user'=>$_SESSION['userinfo']['uid'],'action'=>$data));
        $hq_data=M('oa_hqyj')->where(array('liuchenid'=>$lcid))->order('id desc')->find();
		if($hq_data['jiedianid']!=$jdid || $hq_data['userid']!=$_SESSION['userinfo']['uid'])M('oa_hqyj')->add(array('content'=>I('post.hqyj',''),'username'=>$_SESSION['userinfo']['realname'],'userid'=>$_SESSION['userinfo']['uid'],'addtime'=>date('Y-m-d H:i:s'),'liuchenid'=>$lcid,'action'=>$data,'jiedianid'=>$jdid));
		else M('oa_hqyj')->where("id=".$hq_data['id'])->save(array('content'=>I('post.hqyj',''),'username'=>$_SESSION['userinfo']['realname'],'userid'=>$_SESSION['userinfo']['uid'],'addtime'=>date('Y-m-d H:i:s'),'liuchenid'=>$lcid,'action'=>$data,'jiedianid'=>$jdid));
     }
     public function addtixing($liuchenid,$sort){//增加消息提示

      $res=M('oa_liuchen')->field('c.id,c.alltime,c.userid,c.sort,c.autouser')->join("a join boss_oa_jiedian c on c.pid=a.mid")->where("a.liuchenid='$liuchenid' && c.sort>=$sort")->order('c.sort asc')->find();
      if($sort==0)$userid=$_SESSION['userinfo']['uid'];
      elseif($res['autouser']!=0)$userid=$res['autouser'];//默认选择人
      else $userid=0;
      $data=array('liuchenid'=>$liuchenid,'jiedianid'=>$res['id'],'is_check'=>0,'is_butixin'=>0,'url'=>'','is_ok'=>0);
      $old=M('oa_tixing')->where($data)->find();

      if(!$old){
      	$data['endtime']=time()+$res['alltime'];
      	$data['addtime']=date('Y-m-d H:i:s');
      	$data['userid']=$userid;
      	$id=M('oa_tixing')->add($data);
      }else $id=$old['id'];
        M('oa_liuchen')->where("liuchenid='".$liuchenid."'")->save(array('nowsort'=>$res['sort']));
        return $id;
     }
     public function getsort($res=0){//判断当前行为应执行哪个流程(当前序列，流程信息)

      $sort=$res['nowsort'];

        	$nowjd=M('oa_jiedian')->where("sort=$sort && pid={$res['mid']}")->find();
        	
        	if($nowjd['nextjdlist']==''){
				 return -1;
        	}else $res_other_jiedian=M('oa_jiedian')->field('a.*')->join("a join boss_oa_liuchen_m b on a.pid=b.id")->where("a.id in (".$nowjd['nextjdlist'].")")->select();//找出所有下一节点
          $alldata=M('oa_'.$res['mid'])->where("id=".$res['alldata'])->find();//取所有表单数据
          $shenghejilu=json_decode($res['shenghejilu'],true);//取得所有审核记录
          foreach ($res_other_jiedian as $k => $v) {//循环判断执行条件
          	if($v['tiaojian']=='')return $v['sort'];
            $tiaojian_arr=array();
            $res_tiaojian=M('oa_tiaojian')->where("jid=".$v['id'])->select();//此节点所有相关条件

            foreach ($res_tiaojian as $val) {
              if(substr($val['key'],0,1)=='x'){
              	if(!is_numeric($val['val']))$d2='"'.$val['val'].'"';
              	else $d2=$val['val'];
              	if(!is_numeric($alldata[$val['key']]))$d1="'".$alldata[$val['key']]."'";
              	else $d1=$alldata[$val['key']];
              	if($val['type']=='=')$val['type']='==';
              	$str='return '.$d1.$val['type'].$d2.';';
              	$tiaojian_arr[$val['code']]=eval($str);
              }else{
                if($shenghejilu[$val['key']][0]==$val['val']){
                  $tiaojian_arr[$val['code']]=true;
                }else{
                  $tiaojian_arr[$val['code']]=false;
                }
              }
            }
            $t=str_replace('[', ' $tiaojian_arr[', $v['tiaojian']);
            $t=str_replace(']', '] ', $t);
            $p=htmlspecialchars_decode('return '.$t.';');
            $jg=eval($p);
            if($jg)return $v['sort'];
          }
          $return = -1;
      return $return;
     }
     //判断是否拥有功能权限
     public function check_rule_fun(){
     	$data_rule=M('auth_rule')->where("name='/".MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME."'")->find();
     	if(!in_array($data_rule['id'], $_SESSION['userinfo']['fun_config']) && $data_rule['ischeckrule']==1){
     		echo "<script>alert('你没有权限使用此功能！".MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME."');history.go(-1);</script>";
     		exit();
     	}
     }


}
