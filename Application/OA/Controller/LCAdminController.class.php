<?php
namespace OA\Controller;
use Common\Controller\BaseController;
use Common\Service;
class LCAdminController extends BaseController {
	private $DepartSettingSer;
	function _initialize(){
		parent::_initialize();
		$this->DepartSettingSer = !$this->DepartSettingSer ? new Service\DepartSettingService() : $this->DepartSettingSer;
	}
	public function index(){
		echo '<a href="/OA/LCAdmin/creatoa">创建流程</a>';
	}
	public function creatoa(){
		if(!empty(I('get.name')))$wheres[]="name like '%".I('get.name')."%'";
		$count=$data=M('oa_liuchen_m')->where(implode(' && ', $wheres))->count();
		$pagedata=$this->getpage($count);
		$this->getpagelist($count);
		$data=M('oa_liuchen_m')->where(implode(' && ', $wheres))->limit($pagedata)->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function add(){
		$this->dedata=$this->DepartSettingSer->getAllTreeList('type=0');
		$this->jsdata=M('oa_position')->select();
		$usdata=M('user')->where("status=1")->select();
		$this->usdata2=array_merge(array(array('id'=>0,'real_name'=>'本部门经理')),$usdata);
		$usdata=array_merge(array(array('id'=>0,'real_name'=>'全体员工')),$usdata);
		$this->usedata=$usdata;
		$this->display();
	}

	public function addDo(){
				
		$allkj2=json_decode(htmlspecialchars_decode(I('post.allkj')),true);//控件列表，根据此列表建新表存储数据
		$content=htmlspecialchars_decode(I('post.tablestyle'));
		foreach ($allkj2 as $key => $value) {
			$str=$value['code'];
			if(preg_match_all("/$str/", $content, $a))$allkj[]=$value;
		}

		M()->startTrans();
		$id=M('oa_liuchen_m')->add(array('name'=>I('post.name'),'userlist'=>I('post.userlist'),'bumenlist'=>I('post.bumenlist'),'jiaoselist'=>I('post.jiaoselist'),'tablestyle'=>$content,'addtime'=>date('Y-m-d H:i:s'),'madeuser'=>$_SESSION['userinfo']['uid'],'tablekeylist'=>json_encode($allkj)));
		$creattablesql="create table boss_oa_$id(id int AUTO_INCREMENT PRIMARY KEY,";
		foreach ($allkj as $key => $value) {
			$code=$value['code'];
			$name=$value['name'];
			if($value['type']=='text')$creattablearr[]="$code varchar(255) COMMENT '$name'";
			if($value['type']=='select')$creattablearr[]="$code varchar(63) COMMENT '$name'";
			if($value['type']=='radio')$creattablearr[]="$code varchar(32) COMMENT '$name'";
			if($value['type']=='checkbox')$creattablearr[]="$code varchar(32) COMMENT '$name'";
			if($value['type']=='date')$creattablearr[]="$code date COMMENT '$name'";
			if($value['type']=='user')$creattablearr[]="$code int COMMENT '$name'";
		}
		$creattablesql.=implode(',', $creattablearr);
		$creattablesql.=')DEFAULT CHARSET=utf8';
		M()->execute($creattablesql);
		$allcs=I('post.');
		$oldarr=array();
		$allsort=array();
		M('oa_jiedian')->add(array('userlist'=>I('post.userlist'),'bumenlist'=>I('post.bumenlist'),'jiaoselist'=>I('post.jiaoselist'),'autouser'=>-1,'alltime'=>0,'sort'=>0,'pid'=>$id,'name'=>'开始节点'));
		foreach ($allcs as $key => $value) {//循环节点
			if($key=='name' || $key=='tablestyle')continue;
			if(preg_match_all('/\d+/', $key, $arr)){
				$str=$arr[0][0];
				if(in_array($str,$oldarr))continue;
				if(in_array($allcs['sort'.$str],$allsort)){
					M()->rollback(); 
					echo '执行顺序重复设置';
					exit();
				}
				$allsort[]=$allcs['sort'.$str];
				$oldarr[]=$str;
				M('oa_jiedian')->add(array('userid'=>$allcs['userid'.$str],'bumenlist'=>$allcs['bumenlist'.$str],'jiaoselist'=>$allcs['jiaoselist'.$str],'autouser'=>$allcs['autouser'.$str],'autojb'=>$allcs['autojb'.$str],'alltime'=>$allcs['alltime'.$str],'sort'=>$allcs['sort'.$str],'pid'=>$id,'name'=>$allcs['tname'.$str]));
				unset($allcs['userid'.$str]);
				unset($allcs['alltime'.$str]);
				unset($allcs['sort'.$str]);
				unset($allcs['tname'.$str]);
			}
		}
		M()->commit();
		echo '添加完成<script>window.location="/OA/LCAdmin/creatoa"</script>';
	}

	public function edit(){
		$data=M('oa_liuchen_m')->where("id=".I('get.id'))->find();
		$this->data=$data;
		$this->list=M('oa_jiedian')->where("pid=".I('get.id'))->order('sort')->select();
		$num=0;
		foreach ($this->list as $key => $value) {
			if($value['sort']>$num){
				$num=$value['sort'];
			}
		}
		$this->num=$num+1;
		$this->dedata=$this->DepartSettingSer->getAllTreeList('type=0');
		$this->jsdata=M('oa_position')->select();
		$usdata=M('user')->where("status=1")->select();
		$this->usdata2=array_merge(array(array('id'=>0,'real_name'=>'本部门经理')),$usdata);
		$usdata=array_merge(array(array('id'=>0,'real_name'=>'全体员工')),$usdata);

		$this->usedata=$usdata;
		$this->display();
	}

	public function editDo(){
		if(I('post.tablestyle')!=''){
			$allkj2=json_decode(htmlspecialchars_decode(I('post.allkj')),true);
			$content=htmlspecialchars_decode(I('post.tablestyle'));
			foreach ($allkj2 as $key => $value) {
				$str=$value['code'];
				if(preg_match_all("/$str/", $content, $a))$allkj[]=$value;
			}
			M()->startTrans();
			$res_old=M('oa_liuchen_m')->where("id=".I('post.id'))->find();
			$allkj_old=json_decode($res_old['tablekeylist'],true);
			$allkj_new=$allkj;
			foreach ($allkj_old as $k => $value){  
			    foreach ($allkj_new as $key => $val){  
			        if($value==$val){  
			            unset($allkj_old[$k]);
			            unset($allkj_new[$key]);
			            continue;
			        }  
			    }  
			} 
			foreach ($allkj_new as $key => $value) {
				$sql="alter table boss_oa_".I('post.id')." add ";
				$code=$value['code'];
				$name=$value['name'];
				if($value['type']=='text')$sql.="$code varchar(255) COMMENT '$name'";
				if($value['type']=='select')$sql.="$code varchar(63) COMMENT '$name'";
				if($value['type']=='radio')$sql.="$code varchar(32) COMMENT '$name'";
				if($value['type']=='checkbox')$sql.="$code varchar(32) COMMENT '$name'";
				if($value['type']=='date')$sql.="$code date COMMENT '$name'";
				if($value['type']=='user')$sql.="$code int COMMENT '$name'";
				M()->execute($sql);
			}
			/*
			foreach ($allkj_old as $key => $value) {
				$code=$value['code'];
				$sql="alter table boss_oa_".I('post.id')." drop $code";
				M()->execute($sql);
			}
			*/
			M('oa_liuchen_m')->where("id=".I('post.id'))->save(array('name'=>I('post.name'),'userlist'=>I('post.userlist'),'bumenlist'=>I('post.bumenlist'),'jiaoselist'=>I('post.jiaoselist'),'tablestyle'=>$content,'addtime'=>date('Y-m-d H:i:s'),'madeuser'=>$_SESSION['userinfo']['uid'],'tablekeylist'=>json_encode($allkj)));
		}else{
			M()->startTrans();
			M('oa_liuchen_m')->where("id=".I('post.id'))->save(array('name'=>I('post.name'),'userlist'=>I('post.userlist'),'bumenlist'=>I('post.bumenlist'),'jiaoselist'=>I('post.jiaoselist'),'addtime'=>date('Y-m-d H:i:s'),'madeuser'=>$_SESSION['userinfo']['uid']));
		}
		$id=I('post.id');
		$list=M('oa_jiedian')->where("pid=".I('post.id'))->select();
		$allcs=I('post.');
		$oldarr=array();
		$allsort=array();
		foreach ($allcs as $key => $value) {
			if(in_array($key,array('name','tablestyle','userlist','jiaose','bumen','allkj')))continue;//不参与组合的条件
			
			if(preg_match_all('/\d+/', $key, $arr)){
				$str=$arr[0][0];
				if(in_array($str,$oldarr))continue;
				if(in_array($allcs['sort'.$str],$allsort)){
					M()->rollback(); 
					echo '执行顺序重复设置';
					exit();
				}
				$allsort[]=$allcs['sort'.$str];
				$oldarr[]=$str;
				//echo $allcs['userid'.$str].' '.$allcs['alltime'.$str].' '.$allcs['sort'.$str].'<br/>';
				if(!empty($allcs['oldid'.$str])){
					M('oa_jiedian')->where("id=".$allcs['oldid'.$str])->save(array('userid'=>$allcs['userid'.$str],'bumenlist'=>$allcs['bumenlist'.$str],'jiaoselist'=>$allcs['jiaoselist'.$str],'autouser'=>$allcs['autouser'.$str],'autojb'=>$allcs['autojb'.$str],'alltime'=>$allcs['alltime'.$str],'sort'=>$allcs['sort'.$str],'pid'=>$id,'name'=>$allcs['tname'.$str]));
					foreach ($list as $k => $v) {
						if($v['id']==$allcs['oldid'.$str])unset($list[$k]);
					}
					unset($allcs['oldid'.$str]);
					unset($allcs['userid'.$str]);
					unset($allcs['alltime'.$str]);
					unset($allcs['sort'.$str]);
					unset($allcs['tname'.$str]);
					continue;
				}
				M('oa_jiedian')->add(array('userid'=>$allcs['userid'.$str],'bumenlist'=>$allcs['bumenlist'.$str],'jiaoselist'=>$allcs['jiaoselist'.$str],'autouser'=>$allcs['autouser'.$str],'alltime'=>$allcs['alltime'.$str],'sort'=>$allcs['sort'.$str],'pid'=>$id,'name'=>$allcs['tname'.$str]));
				unset($allcs['userid'.$str]);
				unset($allcs['alltime'.$str]);
				unset($allcs['sort'.$str]);
				unset($allcs['tname'.$str]);
			}
		}
		$idarr=array();
		foreach ($list as $k => $v) {
			$idarr[]=$v['id'];
		}
		if(count($idarr)>0)M('oa_jiedian')->where("id in (".implode(',',$idarr).")")->delete();
		M()->commit();
		$this->success('ok');
	}
	public function edittiaojian(){//编辑执行条件
		$this->data=M('oa_liuchen_m')->where("id=".I('get.id'))->find();
		$this->list=M('oa_jiedian')->where("pid=".I('get.id'))->select();
		$num=0;
		foreach ($this->list as $key => $value) {
			if($value['sort']>$num){
				$num=$value['sort'];
			}
		}
		$this->num=$num+1;
		$this->display();
	}
	public function edittiaojian_detail(){//执行条件设置页
		$this->data=M('oa_jiedian')->where("id=".I('get.id'))->find();
		$this->ortherdata=M('oa_jiedian')->where("pid=".$this->data['pid'])->select();
		$name=array();
		foreach ($this->ortherdata as $key => $value) {
			$name[$value['id']]=$value['name'];
		}
		$inputlist=M('oa_liuchen_m')->where("id=".$this->data['pid'])->find();
		$tablekeylist=json_decode($inputlist['tablekeylist'],true);
		foreach ($tablekeylist as $key => $value) {
			$tablekeylist[$value['code']]=$value['name'];
		}
		if(!empty($inputlist['tablekeylist']))$this->inputlist=json_decode($inputlist['tablekeylist'],true);
		$tiaojian=M('oa_tiaojian')->where("jid=".I('get.id'))->select();
		foreach ($tiaojian as $key => $v) {
			if($v['val']=='同意' || $v['val']=='不同意'){
				$tiaojian[$key]['key']=$name[$v['key']];
			}else{
				$tiaojian[$key]['key']=$tablekeylist[$v['key']];
			}
		}
		$this->tiaojian=$tiaojian;
		if($this->data['nextjdlist']!=''){
			$this->nextjdlist=M('oa_jiedian')->where("id in (".$this->data['nextjdlist'].")")->select();
		}
		if($this->data['tszdlist']!=''){
			$tszdlist_data=explode(',', $this->data['tszdlist']);
			$tszdlist=array();
			foreach ($tszdlist_data as $key => $value) {
				$tszdlist[]=array('name'=>$tablekeylist[$value],'code'=>$value);
			}
			$this->tszdlist=$tszdlist;
		}
		$this->display();
	}
	public function addtiaojian(){//添加执行条件
		$res=M('oa_tiaojian')->where("jid=".I('post.id'))->order("code desc")->find();
		$id=M('oa_tiaojian')->add(array('jid'=>I('post.id'),'key'=>I('post.key'),'val'=>I('post.key2'),'type'=>htmlspecialchars_decode(I('post.key1')),'code'=>$res['code']+1));
		if($id>0)echo json_encode(array('type'=>1,'msg'=>'添加成功','code'=>$res['code']+1,'id'=>$id));
		else echo json_encode(array('type'=>2,'msg'=>'添加失败'));
	}
	public function changetiaojian(){//修改条件判定公式
		M('oa_jiedian')->where("id=".I('get.id'))->save(array('tiaojian'=>I('get.tiaojian')));
		$this->success('ok');
	}
	public function deltiaojian(){//删除条件
		M('oa_tiaojian')->where("id=".I('get.id'))->delete();
		echo json_encode(array('type'=>1,'msg'=>'删除成功'));
	}
	public function addnextjd(){//添加下一节点
		$res=M('oa_jiedian')->where("id=".I('post.id'))->find();
		if($res['nextjdlist']!='')$arr=explode(',', $res['nextjdlist']);
		else $arr=array();
		if(in_array(I('post.name'), $arr)){
			echo json_encode(array('type'=>0,'msg'=>'重复添加'));
		}else{
			$arr[]=I('post.name');
			M('oa_jiedian')->where("id=".I('post.id'))->save(array('nextjdlist'=>implode(',', $arr)));
			echo json_encode(array('type'=>1,'msg'=>'添加成功'));
		}
	}
	public function delnextjd(){//删除下一节点
		$res=M('oa_jiedian')->where("id=".I('get.pid'))->find();
		$arr=explode(',', $res['nextjdlist']);
		foreach ($arr as $key => $value) {
			if($value!=I('get.id'))$newarr[]=$value;
		}
		if(count($newarr)>0)$str=implode(',', $newarr);
		else $str='';
		M('oa_jiedian')->where("id=".I('get.pid'))->save(array('nextjdlist'=>$str));
		echo json_encode(array('type'=>1,'msg'=>'删除成功'));
	}
	public function addtszd(){//添加特殊字段
		$res=M('oa_jiedian')->where("id=".I('post.id'))->find();
		if($res['tszdlist']!='')$arr=explode(',', $res['tszdlist']);
		else $arr=array();
		if(in_array(I('post.name'), $arr)){
			echo json_encode(array('type'=>0,'msg'=>'重复添加'));
		}else{
			$arr[]=I('post.name');
			M('oa_jiedian')->where("id=".I('post.id'))->save(array('tszdlist'=>implode(',', $arr)));
			echo json_encode(array('type'=>1,'msg'=>'添加成功'));
		}
	}
	public function deltszd(){
		//删除特殊字段
		$res=M('oa_jiedian')->where("id=".I('get.pid'))->find();
		$arr=explode(',', $res['tszdlist']);
		foreach ($arr as $key => $value) {
			if($value!=I('get.id'))$newarr[]=$value;
		}
		if(count($newarr)>0)$str=implode(',', $newarr);
		else $str='';
		M('oa_jiedian')->where("id=".I('get.pid'))->save(array('tszdlist'=>$str));
		echo json_encode(array('type'=>1,'msg'=>'删除成功'));
	}
	public function changetiaojian_text(){
		M('oa_jiedian')->where("id=".I('post.id'))->save(array('yaodian'=>I('post.yaodian')));
		$this->success('修改成功');
	}
	public function edittiaojian_text(){
		$this->data=M('oa_jiedian')->where("id=".I('get.id'))->find();
		$this->display();
	}
}


