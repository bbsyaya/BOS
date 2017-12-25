<?php
/**
 * Created by PhpStorm.
 * User: owq
 * Date: 2017/4/24
 * Time: 11:27
 */
namespace OA\Controller;
use Common\Controller\BaseController;
/**
 * 文件柜管理
 * Class AttendController
 * @package Home\Controller
 */
class FileCabinetsController extends BaseController {
    public $totalPage = 0;
    public function index(){
        $where = array();
        $sort_id = I('get.sort_id');
        if($sort_id){
            $where['SORT_ID'] = $sort_id;
        }

        $s_id = I('get.ids');
        if($s_id){
            $where[] = "SORT_ID in ($s_id)";
        }

        $name = I('get.name');
        if($name){
            $where[] = "SUBJECT like '%".$name."%'";
        }
        $notify_type = I('get.notify_type');
        if($notify_type){
            $where['TYPE_ID'] =$notify_type;
        }
        $status_type = I('get.status_type');
        if($status_type){
            if($status_type == 1){
                $where[] = 'END_DATE <=0';
            }elseif($status_type == 2){
                $where[] = 'END_DATE >0';
            }
        }
        $notify_type = C('OPTION.notify_type');
        $status_type = C('OPTION.status_type');
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->assign('notify_type',$notify_type);
        $this->assign('status_type',$status_type);
        if($s_id) {
            $this->assign('s_id', $s_id);
        }
        $this->display();
    }

    public function getList($where) {

        $fc = M('file_content');
        $fcData = $fc->field('CONTENT_ID,SORT_ID,SUBJECT,SEND_TIME,ATTACHMENT_NAME')->where($where)->page($_GET['p'],10)->select();
        $Row = $fc->where($where)->count();
        $this->totalPage =$Row;
        return $fcData;
    }

    //新增或修改
    public function update(){
        $advModel = M('file_content');
        $cid = I('post.cid');
        if($cid){//修改
            if ($advModel->save() === false) {
                $this->ajaxReturn(array('msg'=>$advModel->getError()));
            }

        }else{//新增
            $cid = $advModel->add();
            if ($cid === false) {
                $this->ajaxReturn(array('msg'=>$advModel->getError()));
            }
        }
        //notifyFile <input type="file" id="notifyFile"  name="notifyFile" multiple="multiple" />
        if(!empty($_FILES[notifyFile]['tmp_name'])){ //是否上传操作
            $qualiInfo = $this->uplaodfile('notifyFile', UPLOAD_CONTRACT);
            if (!is_array($qualiInfo)) {
                $this->ajaxReturn($qualiInfo);
            }

            $filepath = UPLOAD_CONTRACT .$qualiInfo['notifyFile']['savepath'].$qualiInfo['notifyFile']['savename'];
            //$this->ajaxReturn($filepath);

            //保存路径
            $map = array();
            $map['NOTIFY_ID']= $cid;
            $map['ATTACHMENT_ID']= $cid;
            $map['ATTACHMENT_NAME'] = $filepath;
            if ($advModel->save($map) === false) {
                $this->ajaxReturn($advModel->getError());
            }else{
                //$this->ajaxReturn("上传成功");
            }

        }

    }

    public function delete(){//删除
        $adModel = M('file_content');
        $fs = M('file_sort');
        $cid = I('get.cid');
        $nameData = $adModel->field('a.SORT_ID,a.ATTACHMENT_NAME,b.SORT_PARENT,b.SORT_NAME')->join('a join boss_file_sort b on a.SORT_ID=b.SORT_ID')->where("a.CONTENT_ID=".$cid)->find();
        $fileName = $nameData['attachment_name'];//目录
        /*if($nameData['sort_parent']<=0){
            $fileName = $nameData['sort_name'].'/'.$nameData['attachment_name'];//目录
        }elseif($nameData['sort_parent']>0){
            $fsData = $fs->field('SORT_PARENT,SORT_NAME')->where("SORT_ID=".$nameData['sort_parent'])->find();
            if($fsData['sort_parent']<=0){
                $fileName = $fsData['sort_parent'].'/'.$nameData['sort_name'].'/'.$nameData['attachment_name'];//目录
            }elseif($fsData['sort_parent']>0){
            }
        }*/
        unlink($fileName);//删除文件
        if($cid){
            if ($adModel->where('CONTENT_ID='.$cid)->delete() === false) {
                $this->ajaxReturn($adModel->getError());
            }else{
                $this->ajaxReturn("TRUE");
            }
        }
    }

    public function getGroupRuleTree() {//Tree
        $idData = I('get.chkIds');
        $rules = M('file_sort')->field('SORT_ID as id,SORT_PARENT as pid,SORT_NAME as name')->where()->select();
        foreach($rules as $key=>$val){
            if(!empty($idData) && in_array($val['id'], $idData)){
                $rules[$key]['checked'] = true;
            }else {
                $rules[$key]['checked'] = false;
            }
        }
        $this->ajaxReturn($rules);
    }

    public function udpateRule(){//添加子文件夹
        $f_name = I('post.name');
        $ids = I('post.ids');
        $advModel = M('file_sort');
        foreach($ids as $val){
            $addData = array();
            $addData['SORT_PARENT'] = $val;
            $addData['SORT_NAME'] = $f_name;
            $addData['SORT_TYPE'] = 1;
            if ($advModel->add($addData) === false) {
                $retMsg = $advModel->getError();
            }else{
                $retMsg = '新增成功';
            }
        }
        $goUrl = U('index');
        $this->success($retMsg,$goUrl);
    }

    public function  uploadImg(){//上传附件

        $advModel = M('file_content');

        if(!empty($_FILES[notifyFile]['tmp_name'])){ //是否上传操作
            //判断是否有文件夹
            if(!file_exists(UPLOAD_ANNEX)){
                mkdir(UPLOAD_ANNEX);//创建文件夹
            }else {

                $qualiInfo = $this->uplaodfile('notifyFile', UPLOAD_ANNEX);
                if (!is_array($qualiInfo)) {
                    $this->ajaxReturn($qualiInfo);
                }

                $filepath = UPLOAD_ANNEX . $qualiInfo['notifyFile']['savepath'] . $qualiInfo['notifyFile']['savename'];
                //$this->ajaxReturn($filepath);
                $cid = I('post.cid');

                //保存路径
                $map = array();
                $map['SORT_ID'] = $cid;
                $map['SUBJECT'] = $_FILES['notifyFile']['name'];
                $map['SEND_TIME'] = date('Y-m-d H:i:s', time());
                $map['ATTACHMENT_NAME'] = $filepath;
                $map['CREATER'] = UID;
                if ($advModel->add($map) === false) {
                    $this->ajaxReturn($advModel->getError());
                } else {
                    $this->ajaxReturn("上传成功");
                }

            }
        }
    }

    //创建文本文档
    public function create_txt(){
        $file_content = M('file_content');
        $sort_id = I('post.sort_id');//外键
        $name = I('post.SUBJECT');
        $nr = I('post.nerong');
        $sort_id = explode(",",$sort_id);
        $CONTENT_ID = I('post.cid');//主键
        echo $CONTENT_ID;exit;
        /*$file_c = $name.".txt";
        $file = fopen($file_c,'w');
        fwrite($file,$nr);*/

        if(empty($CONTENT_ID)){
            $li = date('His',time());
            $file_c = './upload/annex/file/'.$li.'.txt';
            file_put_contents($file_c,iconv("UTF-8", "GBK", $nr));
        }else{
            //编辑时删除文件重新建,只是内容改变
            $resD = $file_content->field('ATTACHMENT_NAME')->where('CONTENT_ID='.$CONTENT_ID)->find();
            $ups = $resD['attachment_name'];
            unlink($resD['attachment_name']);
            file_put_contents($ups,iconv("UTF-8", "GBK", $nr));
        }

        foreach($sort_id as $val){
            $add = array();
            $add['SORT_ID'] = $val;
            $add['SUBJECT'] = $name;
            $add['CONTENT'] = $nr;
            if(empty($CONTENT_ID)) {
                $add['SEND_TIME'] = date('Y-m-d H:i:s', time());
                $add['ATTACHMENT_NAME'] = $file_c;
            }
            $add['CREATER'] = UID;
            if($CONTENT_ID){
                if ($file_content->where('CONTENT_ID='.$CONTENT_ID)->save($add) === false) {
                    $retMsg = $this->ajaxReturn($file_content->getError());
                }else{
                    $retMsg = '修改成功';
                }
            }else{
                if ($file_content->add($add) === false) {
                    $retMsg = $this->ajaxReturn($file_content->getError());
                }else{
                    $retMsg = '新增成功';
                }
            }
        }
        $goUrl = U('index');
        $this->success($retMsg,$goUrl);
    }

    /*编辑查询*/
    public function up_sel(){
        $cid = I('get.cid');
        $file_content = M('file_content');
        $resData = $file_content->field('CONTENT_ID,SUBJECT,ATTACHMENT_NAME')->where('CONTENT_ID='.$cid)->find();
        $file_path = $resData['attachment_name'];
        if(file_exists($file_path)) {
            $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
            $str = str_replace("\r\n", "<br />", $str);
        }
        $res = array('cid'=>$resData['content_id'],'subject'=>$resData['subject'],'nr'=>iconv("GBK","UTF-8", $str));
        $this->ajaxReturn($res);
    }

    public function edit(){
        $sort_id = I('get.sort_id');
        $cid = I('get.cid');
        if($cid){
            $file_content = M('file_content');
            $file_contentData = $file_content->field('CONTENT_ID,SORT_ID,SUBJECT,CONTENT')->where("CONTENT_ID=".$cid)->find();
            $this->assign('upInfo', $file_contentData);
        }
        //$this->assign('sort_id',$sort_id);
        //$this->assign('cid',$cid);
        $this->display();
    }
}