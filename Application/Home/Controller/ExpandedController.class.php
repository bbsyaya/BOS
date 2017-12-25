<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/2/7
 * Time: 14:33
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class ExpandedController extends BaseController {
    protected $totalPage = 0;

    public function index(){
        $options = array(
            array('type'=>'a', 'id'=>'', 'class'=>'', 'title'=>'新增', 'url'=>U('edit')),
            /*array('type'=>'a', 'id'=>'doExport', 'class'=>'', 'title'=>'????', 'url'=>'javascript:;'),
            array('type'=>'a', 'id'=>'doFinanceExport', 'class'=>'', 'title'=>'?????', 'url'=>'javascript:;'),*/
        );
        $this->assign('toolOptions', $options);

        $searchOptions = array(
            array('title'=>'产品名称','name'=>'pro_name','type'=>'text'),
            array('title'=>'广告主名称','name'=>'adv_name','type'=>'text')
        );
        $this->assign('searchOptions', $searchOptions);

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('data', $list);
        $this->display();
    }

    public function getList(){

        if(!empty(I('get.pro_name')))$wheres[]="b.name like '%".I('get.pro_name')."%'";
        if(!empty(I('get.adv_name')))$wheres[]="c.name like '%".I('get.adv_name')."%'";
        $where=implode(' && ',$wheres);
        $data=M('notexpanded')->field('a.*,b.`name` AS pro_name,c.`name` AS adv_name,d.real_name')->join('a
		JOIN boss_product b ON a.comid=b.id
		JOIN boss_advertiser c ON c.id=a.advid
		JOIN boss_user d ON d.id=a.sale_id')->where($where)->order('a.id desc')->page($_GET['p'], C('LIST_ROWS'))->select();
        //echo M('notexpanded')->getLastSql();exit;

        $this->totalPage = M('notexpanded')->join('a
		JOIN boss_product b ON a.comid=b.id
		JOIN boss_advertiser c ON c.id=a.advid
		JOIN boss_user d ON d.id=a.sale_id')->where($where)->count();
        return $data;
    }

    public function edit(){
        $id = I('get.id', 0);
        if ($id > 0) {
            $data=M('notexpanded')->field('a.*,b.`name` AS pro_name,b.id as pro_id,c.`name` AS adv_name,c.id as adv_id,d.real_name,d.id as uid')->join('a
            JOIN boss_product b ON a.comid=b.id
            JOIN boss_advertiser c ON c.id=a.advid
            JOIN boss_user d ON d.id=a.sale_id')->where("a.id=".$id."")->order('a.id desc')->find();
            $this->assign('data',$data);
        }
        $userlist2=M('user')->field('a.id,a.real_name,b.name as groupname,d.title as posttype')->join('a join boss_user_department b on a.dept_id=b.id join boss_auth_group_access c on a.id=c.uid join boss_auth_group d on c.group_id=d.id')->where('d.id IN (6,7)')->group('a.id')->select();
        $this->assign('userlist2',$userlist2);

        $advList = M('advertiser')->field('id,name')->where('status=1')->order('id desc')->select();
        $this->assign('advList',$advList);
        $proList = M('product')->field('id,name')->where('status=1')->order('id desc')->select();
        $this->assign('proList',$proList);
        $this->display();
    }

    public function update(){
        $editId = $supId = I('post.id', 0, 'intval');
        $goUrl = $supId > 0 ? Cookie('__forward__') : U('Expanded/index');

        $supModel = M('notexpanded');

        if ($supModel->create() === false) {
            $this->ajaxReturn(array('msg'=>$supModel->getStrError()));
        }
        if ($supId > 0) {
            if ($supModel->save() === false) {
                $this->ajaxReturn(array('msg'=>$supModel->getStrError()));
            }else{
                $retMsg = '修改成功';
                $this->ajaxReturn(array('msg'=>$retMsg, 'go'=>$goUrl));
            }
        } else {
            $editId = $insertId = $supModel->add();
            if ($insertId === false) {
                $this->ajaxReturn(array('msg'=>$supModel->getStrError()));
            }else{
                $retMsg = '新增成功';
                $this->ajaxReturn(array('msg'=>$retMsg, 'go'=>$goUrl));
            }
        }

    }
}