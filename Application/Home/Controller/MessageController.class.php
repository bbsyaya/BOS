<?php 
/**
* 
*/
namespace Home\Controller;
use Common\Controller\BaseController;
use Common\Service;
class MessageController extends BaseController
{
	/**
	 * 进入垃圾站
	 * @return [type] [description]
	 */
	function rubbishList(){
		$ser      = new Service\PromptInformationService();
		$where    = "find_in_set(".$_SESSION['userinfo']['uid'].",send_user) && a_link not like '%/OA%' and status=2";
		$content = trim(I("hd_content"));
		if($content){
			$where .= " and content like '%".$content."%'";
		}
		$count    = $ser->getPromptInformationCountByWhere($where);
		$listRows = C('LIST_ROWS') > 0 ? C('LIST_ROWS') : 10;
		$page     = new \Think\Page($count, $listRows);
		$list     = $ser->getPromptInformationListByWhere($where,"id,date_time,content,status,a_link,oa_number,exp_time","id desc",$page->firstRow,$page->listRows);
		$this->assign("content",$content);
		unset($where);
		$this->assign("promlist",$list);
		$this->assign("page",$page->show());
		$this->display();
	}
	/**
	 * 修改状态
	 * @return [type] [description]
	 */
	function updateStatus(){
		$ids         = I("ids");
		$status      = I("status");
		$ser         = new Service\PromptInformationService();
		$where["id"] = array("in",$ids);
		$row         = $ser->savePromptInformationData($where,array("status"=>$status));
		$result      = array("code"=>500);
		if($row){
			$result      = array("code"=>200);
		}
		$this->ajaxReturn($result);
	}

	/**
	 * 发送通知
	 * 
	 */
	public function add(){
		if(I('get.id')!=''){
			$msg=M('prompt_information')->where("id=".I('get.id'))->find();
			if($msg['add_user']==$_SESSION['userinfo']['uid']){
				//我发送的，可以编辑
				$this->ismy=1;
				$this->data=M('msg_fromuser')->where("id=".$msg['make_id'])->find();
				$this->autojb=explode(',', $this->data['weekday']);
				$this->touser=explode(',', $this->data['touser']);
				$this->user=M("user")->where("status=1")->select();
			}else{
				//读信息，只能查看和回复
				$this->isshowmsg=1;
				M('prompt_information')->where("id=".I('get.id'))->save(array('status'=>1));
				if($msg['add_user'] && $msg['send_user']){
					$userlist=$msg['add_user'].','.$msg['send_user'];
				}else{
					$userlist=$msg['send_user'];
				}
				$user_arr=explode(',', $msg['send_user']);
				$user=M('user')->where("id in ($userlist)")->select();
				foreach ($user as $key => $value) {
					if($value['id']==$msg['add_user']){
						$msg['add_user']=$value['real_name'];
					}
					if(in_array($value['id'], $user_arr)){
						$user_arr_name[]=$value['real_name'];
					}
				}
				if($msg['add_user']==0)$msg['add_user']='系统';
				$msg['send_user']=$user_arr_name;
				$this->msg=$msg;
			}
			
		}elseif(I('get.toid')!=''){
			//回复消息
			$this->ishf=1;
			$msg=M('prompt_information')->where("id=".I('get.toid'))->find();
			$user=M('user')->where("id=".$msg['add_user'])->find();
			$this->adduser=$user['real_name'];
			$this->user=M("user")->where("status=1")->select();
			$this->msg=$msg;
		}else{
			//创建信息
			$this->ismy=1;
			$this->user=M("user")->where("status=1")->select();
		}
		
		$this->display();
	}
	public function addmsg_do(){

		if(!empty($_FILES['file'])){
            $info=$this->uplaodfile('file',UPLOAD_BASIS_IMG_PATH);
            if(!is_array($info)){

                echo json_encode(array('status'=>2,'msg'=>'上传附件失败','filename'=>''));
                return;
            }
            $filename=UPLOAD_BASIS_IMG_PATH.$info['file']['savepath'].$info['file']['savename'];
            echo json_encode(array('status'=>1,'msg'=>'上传附件成功','filename'=>$filename,'name'=>$info['file']['name']));
        }else echo json_encode(array('status'=>2,'msg'=>'没有上传内容','filename'=>''));
	}
	public function addmsgfrom_do(){
		$post=I('post.');
		if($post['content']==''){
			$msg='内容不能为空';
		}
		if($post['touser']==''){
			$msg='收件人不能为空';
		}
		if($msg){
			echo json_encode(array('status'=>2,'msg'=>$msg));
			exit();
		}
		$post['touser']=implode(',', $post['touser']);
		$post['weekday']=implode(',', $post['weekday']);
		$post['uid']=$_SESSION['userinfo']['uid'];
		$id=M('msg_fromuser')->add($post);
		if($id)echo json_encode(array('status'=>1,'msg'=>'发送成功'));
		else echo json_encode(array('status'=>2,'msg'=>'发送失败','sql'=>I('post.')));
	}
	public function hfmsg_do(){
		$post=I('post.');
		if($post['content']==''){
			$msg='内容不能为空';
		}
		if($post['touser']==''){
			$msg='收件人不能为空';
		}
		if($msg){
			echo json_encode(array('status'=>2,'msg'=>$msg));
			exit();
		}
		$msg=array();
		$msg['date_time']=date('Y-m-d H:i:s');
		$msg['send_user']=implode(',', I('post.touser'));
		$msg['content']=I('post.content');
		$msg['add_user']=$_SESSION['userinfo']['uid'];
		$msg['msg_type']='1';
		$msg['hfmsg_id']=I('post.hfmsg_id');
		$msg['file']=I('post.filename');
		$msg['fileoldname']=I('post.fileoldname');
		$id=M('prompt_information')->add($msg);
		if($id)echo json_encode(array('status'=>1,'msg'=>'发送成功'));
		else echo json_encode(array('status'=>2,'msg'=>'发送失败','sql'=>I('post.')));
	}
	public function changemsgfrom_do(){
		$post=I('post.');
		if($post['content']==''){
			$msg='内容不能为空';
		}
		if($post['touser']==''){
			$msg='收件人不能为空';
		}
		if($msg){
			echo json_encode(array('status'=>2,'msg'=>$msg));
			exit();
		}
		$post['touser']=implode(',', $post['touser']);
		$post['weekday']=implode(',', $post['weekday']);
		$post['uid']=$_SESSION['userinfo']['uid'];
		$id=M('msg_fromuser')->where("id=".$post['id'])->save($post);
		if($id)echo json_encode(array('status'=>1,'msg'=>'修改成功'));
		else echo json_encode(array('status'=>2,'msg'=>'修改失败','sql'=>I('post.')));
	}
	public function stopmsg(){
		$id=M('msg_fromuser')->where("id=".I('post.id'))->save(array('status'=>0));
		if($id)echo json_encode(array('status'=>1,'msg'=>'成功停止'));
		else echo json_encode(array('status'=>2,'msg'=>'失败了','sql'=>I('post.')));
	}
}

?>