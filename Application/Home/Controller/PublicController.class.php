<?php
namespace Home\Controller;
use Think\Controller;
class PublicController extends Controller {

	/**
	 * 用户登录
	 */
	public function login($username = null, $password = null){

		if(!empty(I('post.username'))){
			$uModel = D('User');
			$uid = $uModel->login($username, $password);
			if($uid > 0){ //
				$url_ = "/Home/Index/index";
				$go_url = empty($_SESSION['REFER_URL'])?$url_:$_SESSION['REFER_URL'];
				// $go_url = $url_;
				echo json_encode(array('status'=>1,'msg'=>'登陆成功',"url"=>$go_url));
			} else { //登录失败

				switch($uid) {
					case -1: $error = '请检查用户名或密码是否错误！'; break; //禁用
					case -2: $error = 'boss密码或者OA密码错误！'; break;
					case -3: $error = '请输入账号！'; break;
					case -4: $error = '请输入密码！'; break;
					default: $error = '未知错误！'; break; // 0-接口参数错误
				}
				$num=$_SESSION['loginerrornum']?$_SESSION['loginerrornum']:0;
				$_SESSION['loginerrornum']=$num+1;
				if($num>=5)$error = '请联系系统管理员！';
				echo json_encode(array('status'=>0,'msg'=>$error));
			}
		} else {
			if(is_login()){
				$this->redirect('Index/index');
			}else{
				$this->display();
			}
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			D('User')->logout();
			session('[destroy]');
			$this->redirect('Public/login');
		} else {
			$this->redirect('login');
		}
	}

	/**
	 * [上传]
	 * @return [type] [description]
	 */
	public function uploadss(){
		$dir       = "./upload/charlog/";
		$info      = $this->_uplaodfile_public("files",$dir);
		$file_path = $dir.$info["files"]["savepath"].$info["files"]["savename"];
		$file_path = ltrim($file_path,".");
		$list = array("msg"=>"上传失败","data"=>$file_path,"status"=>0);
		if($info){
			$list["msg"] = "上传成功";
			$list["status"] = 1;
		}
		$this->ajaxReturn($list);
	}



	function _uplaodfile_public($name,$dir){
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     10000000000 ;// 设置附件上传大小
		// $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg' , 'xlsx', 'zip' , 'rar', 'xls',"apk","rar","1");// 设置附件上传类型

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
	}

	/**
	 * 显示日志
	 * @return [type] [description]
	 */
	function showLog(){
		$list = explode(",", trim($_REQUEST["datalog"]));
		if($_SESSION["SHOWLOG_LIST"]){
			$list = $_SESSION["SHOWLOG_LIST"];
			$list = explode(",", trim($list));
		}
		foreach ($list as $k => $v) {
			echo $v."<br/>";
		}
	}

	/**
	 * 临时处理
	 * @return [type] [description]
	 */
	function showRediectMsg(){
		// $this->success("原域名bos3.yandui.com即将关闭，请切换到最新域名：it.yandui.com 谢谢！","http://it.boss127.com",4);
		$this->success("原域名bos3.yandui.com即将关闭，请切换到最新域名：it.yandui.com 谢谢！","/",4);
	}

	/**
	 * [layeralert description]
	 * @return [type] [description]
	 */
	function layerAlert(){
		$this->assign("url",trim(I("url")));
		$this->assign("title",trim(I("title")));
		$this->display();
	}

	
}


