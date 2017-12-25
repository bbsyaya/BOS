<?php
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class IndexController extends BaseController {
	public function index(){
		$data_user=M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
		if(date('Y-m-d H',$data_user['last_tanmutime'])!=date('Y-m-d H')){
			$this->is_tanmu=1;
			M('user')->where("id=".$_SESSION['userinfo']['uid'])->save(array('last_tanmutime'=>time()));
		}
		$wcl=M('data_dic')->where("code='tanmu_wcl'")->find();
		$this->wc=$wcl['name'];
		$this->data_user=$data_user;
		$this->display();
	}

	/**
	 * boss页面首页
	 * @return [type] [description]
	 */
	public function bos_index(){
		$data_user       = M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
		$data_auth=M('auth_group_access')->join('a join boss_auth_group b on a.group_id=b.id')->where("a.uid=".$_SESSION['userinfo']['uid'])->find();
		$data_user['auth_name']=$data_auth['title'];
		$this->data_user = $data_user;
		// if($data_user['bos_link']!='')$data_link=M('auth_rule')->field('a.*,b.title as btitle')->join('a join boss_auth_rule b on a.pid=b.id')->where("a.id in (".$data_user['bos_link'].")")->select();
		// else $data_link=array();
		// $this->data_link = $data_link;

		//update 0922 --只读取未删除的信息
		$content = trim(I("hd_content"));
		$where_  = "";
		if($content){
			$where_ = " and a.content like '%".$content."%'";
		}
		
		$allnum = M('prompt_information')->where("find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && a_link not like '%/OA%' and status <>2".str_replace('a.', '', $where_))->count();
		$p=I('get.p');

		if($p >= ceil($allnum/10)) $p = ceil($allnum/10);
		if($p < 1) $p = 1;
		$str = ($p-1)*10;
		$data_prom = M('prompt_information')->field('a.msg_type,a.a_link,a.content,a.status,a.id,a.exp_time,left(a.date_time,10) as date_time,if(b.id is null,"系统",b.real_name) as add_user')->join('a left join boss_user b on a.add_user=b.id')->where("find_in_set(".$_SESSION['userinfo']['uid'].",a.send_user) && a.a_link not like '%/OA%' and a.status <>2".$where_)->group('a.id')->order("a.status asc,a.id desc")->limit($str.',10')->select();
		foreach ($data_prom as $k => $v) {
			$data_prom[$k]["isExp_css"] = "";
			if(!empty($v["exp_time"]) && strtotime($v["exp_time"])<time()){
				$data_prom[$k]["isExp_css"] = "isExp";
				$data_prom[$k]["content"]   = str_replace("将", "已",$v["content"]);
			}
			//发送形式
			$data_prom[$k]['msg_type']=str_replace(array('1','2','3',','),array('站内','邮件','短信','+'),$v['msg_type']);
		}
		$this->promlist = $data_prom;
		$this->promnum  = M('prompt_information')->where("find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && status=0 && a_link not like '%/OA%'".str_replace('a.', '', $where_))->count();
		$this->assign('page_html',getpagedata($allnum));
		$this->assign("content",$content);
		unset($where_);
		$this->display();
	}

	function menu_add(){
		//$uid
		$aid = $_POST['aid'];
		if(!empty($aid)){

			$cm = M('user_menu');

			$uid = $cm->where("uid=".UID."")->count();
			if($uid < 1){
				$map = array();
				$map['aid']= $aid;
				$map['uid'] = UID;
				if ($cm->add($map) === false) {
					$this->ajaxReturn($cm->getError());
				}else{
					$this->ajaxReturn("TRUE");
				}
			}else{

				$map = array();
				$map['aid']= $aid;
				$map['uid'] = UID;
				if ($cm->where("uid=".UID."")->save($map) === false) {
					$this->ajaxReturn($cm->getLastSql());
				}else{
					$this->ajaxReturn("TRUE");
				}
			}

		}
	}

	/**
	 * 主框架
	 * @return [type] [description]
	 */
	function main(){
		$mainurl = trim(I("mainurl"));
		$this->assign("mainurl",$mainurl);

		$data_user       = M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
		$this->data_user = $data_user;
		//获取信息条数
		$ser        = new Service\PromptInformationService();
		$where      = "find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && status=0 && a_link like '%/OA%'";
		$prompcount = $ser->getPromptInformationCountByWhere($where);
		$this->assign("prompcount",$prompcount);

		//获取当前用户oa左边菜单栏
		$ser        = new Service\AuthAccessService();
		$authTree = $ser->getAuthLeftMenuSer(UID,"Home");
		$this->assign("authTree",$authTree);
		// print_r($authTree);exit;
		$this->display();
	}

	/**
	 * 加载用户拥有的权限菜单
	 * @return [type] [description]
	 */
	function initLoadUserHasAuth(){
		$list = $_SESSION["USER_HAS_AUTH_MENU_".UID];
		$res  = array("code"=>0,"data"=>array());
		if($list["main"]){
			$newList = array();
			foreach ($list["main"] as $k => $v) {
				foreach ($v["child"] as $ka => $va) {
					$one = array();
					$one["id"] = $va["id"];
					$one["text"] = $va["title"];
					$one["type"] = $v["title"];
					$one["img"] = "/Public/icon/BOS/".$va["id"].".png";
					$one["url"] = $va["name"];

					$newList[] = $one;
				}
			}
			$res  = array("code"=>200,"data"=>$newList);
		}
		$this->ajaxReturn($res);
	}

	/**
	 * 加载用户设置的快捷方式
	 * @return [type] [description]
	 */
	function initLoadBossLink(){
		$data_user       = M('user')->where("id=".$_SESSION['userinfo']['uid'])->find();
		$res             = array("code"=>0,"data"=>array());
		if($data_user['bos_link'] != ''){
			$data_link = M('auth_rule')->field('a.*,b.title as btitle')->join('a join boss_auth_rule b on a.pid=b.id')->where("a.id in (".$data_user['bos_link'].") && a.id in (".implode(',', $_SESSION['userinfo']['fun_config']).")")->select();
			if($data_link){
				$res  = array("code"=>200,"data"=>$data_link);
			}
			unset($data_link);
		}else{
			//没设置bos_link,取当前4个
			$list = $_SESSION["USER_HAS_AUTH_MENU_".UID];
			if($list["main"]){
				$newList = array();
				foreach ($list["main"] as $k => $v) {
					foreach ($v["child"] as $ka => $va) {
						$one = array();
						$one["id"] = $va["id"];
						$one["title"] = $va["title"];
						$one["btitle"] = $v["title"];
						$one["img"] = "/Public/icon/BOS/".$va["id"].".png";
						$one["url"] = $va["name"];

						$newList[] = $one;
					}
				}
				$newList = array_slice($newList, 0, 4);
				$res  = array("code"=>200,"data"=>$newList);
			}
		}
		unset($data_user);
		$this->ajaxReturn($res);
	}





}


