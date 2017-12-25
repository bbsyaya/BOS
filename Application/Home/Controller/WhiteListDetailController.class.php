<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/8
 * Time: 11:38
 */
namespace Home\Controller;
use Common\Controller\BaseController;
/*
 *风控中心
 * 账户白名单 当月相同支出金额笔数明细
 * Class WhiteListController
 * @package Home\Controller
 */
class WhiteListDetailController extends BaseController {
    public function index(){

        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list',$list);
        $this->display();
    }
    public function getList($where,$group) {
        $ml = M('white_list');
        $where = '1=1';
        $group = '';
        $type = I('get.type');
        if($type){
            $this->assign('type',$type);
            $where .= " and type=".$type;
            $group = "name";
        }
        $status = I('get.status');
        $date = I('get.date');
        $this->assign('status',$status);
        $this->assign('date',$date);
        if($status){

            $where .= " and status=0 and date like '%".$date."%'";
        }

        $wid = I('get.wid');
        if($wid){
            $where .= " and id in (".$wid.")";
        }
        $this->assign('wid',$wid);

        $white_list = $ml->field('*')->order('id desc')->where($where)->page($_GET['p'], C('LIST_ROWS'))->select();//group($group)->

        $subQuery = $ml->field('id')->where($where)->buildSql();//->group($group)
        $this->totalPage = $ml->table($subQuery.' aa')->where()->count();
        return $white_list;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', '');
        $wid = I('get.wid');
        if($wid){
            $where .= " and id in (".$wid.")";
        }
        $type = I('get.type');
        if($type){
            $where .= " and type=".$type;
            $group = "name";
        }
        $status = I('get.status');
        $date = I('get.date');
        if($status){

            $where .= " and status=0 and date like '%".$date."%'";
        }

        $list = $this->lists($this, $where,$group);

        foreach($list as $key=>$val){
            if($val['type'] == 1){
                $list[$key]['oa'] = '';
                $list[$key]['money1'] = $val['money'];
                $list[$key]['name1'] = $val['opening_bank'];
                $list[$key]['opening_bank'] = '';
                $list[$key]['bank_no'] = '';

            }elseif($val['type'] == 2){
                $list[$key]['name2'] = $val['name'];
                $list[$key]['money2'] = $val['money'];
            }
        }
        $title = array('date' => '日期', 'oa' => 'OA号', 'remark' => '摘要', 'money1' => '收入金额','money2' => '支出金额','name1' => '我司账户','name2' => '客户名称','opening_bank' => '客户账户','bank_no' => '客户账号');
        $csvObj = new \Think\Csv();
        $csvObj->put_csv($list, $title, '白名单明细'.date('Y-m-d-H:i:s',time()));
    }
}