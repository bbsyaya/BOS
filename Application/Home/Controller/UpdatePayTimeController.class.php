<?php
/**修改流程里面的出纳付款时间
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-07-13
 * Time: 14:21
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class UpdatePayTimeController extends BaseController{

    public function index()
    {

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list', $list);
        $this->display();
    }

    public function getList($where)
    {
        $ml = M('oa_66');
        $where = 'yy_status=0';
        $run_id = I('get.run_id');
        if($run_id){
            $where .= " and x739c8a_1='".$run_id."'";
        }

        //DATE_FORMAT(b.overtime,'%Y-%m-%d') as pay_date
        $listData = $ml->field("b.id,b.overtime as pay_date,a.x739c8a_1 as run_id")
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2')
            ->group('a.id desc')->where($where)->page($_GET['p'],10)->select();

        $Row = $ml->field('b.id')
            ->join('a
                LEFT JOIN boss_oa_tixing b ON a.x739c8a_1=b.liuchenid AND b.jiedianid=791
                JOIN boss_oa_liuchen c on c.liuchenid=a.x739c8a_1 AND c.`status`=2')
            ->group('a.id desc')->where($where)->buildSql();

        $this->totalPage = $ml->table($Row.' aa')->where()->count();
        return $listData;
    }

    public function update(){
        $ml = M('oa_tixing');
        $sid = I('post.sid');
        $sid = implode(',',$sid);//ID
        $outdata = I('post.outdata');
        $id_s = 0;
        $id_add = '';
        foreach($outdata as $val){
            $data = array();
            $data['id'] = $val[0];
            $data['overtime'] = $val[1];
            if($ml->save($data) === false){
                $id_s++;
                $id_add .= $val[0].",";
            }
        }
        if($id_s == 0){
            $this->ajaxReturn("TRUE");exit;
        }else{
            $this->ajaxReturn("修改失败，ID为".$id_add);exit;
        }
    }
}
