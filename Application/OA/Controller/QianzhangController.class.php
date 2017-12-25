<?php
namespace OA\Controller;
use Common\Controller\BaseController;
class QianzhangController extends BaseController {
	
	public function index(){
		$data=M('user')->where('id='.$_SESSION['userinfo']['uid'])->find();
		$this->img=substr($data['qzimgpath'],1);
		$this->display();
	}
	public function changeimg(){
		if(!empty($_FILES['file']['tmp_name'])){
			$info=$this->uplaodfile('file',UPLOAD_QZ_IMG_PATH);
	        if(!is_array($info)){
                $this->assign('data','上传依据失败');
                $this->display('Public/alertpage');
                return;
            }
	        $file_name=UPLOAD_QZ_IMG_PATH.$info['file']['savepath'].$info['file']['savename'];
	        M('user')->where('id='.$_SESSION['userinfo']['uid'])->save(array("qzimgpath"=>$file_name));
	        $this->success('上传成功！');
	    }else{
	    	$this->error('没有选择图片');
	    }
	}
	public function changepw(){
		$data=M('user')->where('id='.$_SESSION['userinfo']['uid'])->find();
		if($data['qzpw']=='' || $data['qzpw']==md5('hyqz'.I('post.oldpw'))){
			M('user')->where('id='.$_SESSION['userinfo']['uid'])->save(array("qzpw"=>md5('hyqz'.I('post.newpw'))));
			$this->success('修改成功！');
		}else{
			$this->error('原密码错误');
		}
	}
	public function check_pw(){
		//签章验证
		$data=M('user')->where('id='.$_SESSION['userinfo']['uid'])->find();
		if($data['qzpw']=='' || $data['qzpw']!=md5('hyqz'.I('post.pw'))){
			echo json_encode(array('type'=>0));
		}else{
			echo json_encode(array('type'=>1,'path'=>substr($data['qzimgpath'],1)));
		}
	}
}


