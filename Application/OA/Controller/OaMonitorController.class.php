<?php
namespace OA\Controller;
use Think\Controller;
use Common\Controller\BaseController;
/**
 * 流程监控(每个流程)
 * Class OaMonitor
 * @package OA\Controller
 */
class OaMonitorController extends BaseController
{

    public function index()
    {
        $where = array();
        $list = $this->lists($this, $where);
        $this->assign('list', $list);
        $this->display();
    }

    public function getList($where, $field) {

        $liuChen = M('oa_liuchen');
        $BDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $EDate = date('Y-m-d', strtotime("$BDate +1 month -1 day"));

        $Data = $liuChen->field("b.`name`,COUNT(a.id) as sum_count,GROUP_CONCAT(a.id) as aid,GROUP_CONCAT(liuchenid) as liuchenid")->join("a
JOIN boss_oa_liuchen_m b ON a.mid=b.id")->where("a.status=2 and DATE_FORMAT(a.addtime,'%Y-%m-%d')>='".$BDate."' and  DATE_FORMAT(a.addtime,'%Y-%m-%d')<='".$EDate."' AND b.id<67")->group('a.mid')->select();

        foreach($Data as $key=>$val){

            //流程平均用时(一次查的是一类流程数据)
            $minData = $liuChen->field("TIMESTAMPDIFF(MINUTE,a.addtime,MAX(b.overtime) ) as min_c")->join("a
JOIN boss_oa_tixing b ON b.liuchenid=a.liuchenid")->where("a.id in (".$val['aid'].")")->group("a.liuchenid")->select();

            $min_c = 0;
            foreach($minData as $val2){
                $min_c = $min_c + $val2['min_c'];
            }
            $Data[$key]['avg_count'] = round($min_c/60/$val['sum_count'],2);

            /*//部门平均耗时(部门和时间)
            $zData = M('oa_tixing')->field("TIMESTAMPDIFF(MINUTE,addtime,overtime) as min_z,jiedianid,userid")->where("liuchenid in (".$val['liuchenid'].")")->group("jiedianid,liuchenid")->order("TIMESTAMPDIFF(MINUTE,addtime,overtime) DESC")->limit(1)->find();

            //根据userid查部门
            $dep = M('user_department')->field('name')->join("a join boss_user b on a.id=b.dept_id")->where("b.id=".$val3['userid'])->find();
            $zhs_depart = $dep['name'];//部门
            $zhs_time = round($zData['min_z']/60/$val['sum_count'],2);//时间
            $Data[$key]['depart'] = $zhs_depart;//平均部门
            $Data[$key]['avg_time'] = $zhs_time;//平均耗时*/

            $zData = M('oa_tixing')->field("SUM(TIMESTAMPDIFF(MINUTE,a.addtime,a.overtime)) as min_z,a.jiedianid,a.userid,c.name")->where("liuchenid in (".$val['liuchenid'].")")->join("a join boss_user b on a.userid=b.id left join boss_user_department c on c.id=b.dept_id")->group("b.dept_id")->order("SUM(TIMESTAMPDIFF(MINUTE,a.addtime,a.overtime)) DESC")->limit(1)->find();
            $zhs_depart = $zData['name'];//部门
            $zhs_time = round($zData['min_z']/60/$val['sum_count'],2);//时间
            $Data[$key]['depart'] = $zhs_depart;//平均部门
            $Data[$key]['avg_time'] = $zhs_time;//平均耗时

            $avg_count[$key] = $zhs_time;
        }
        array_multisort($avg_count, SORT_DESC, $Data);
        return $Data;
    }

    /*每个流程各个节点用时情况*/
    public function avg_detail(){
        $liuchenid = I('get.liuchenid');
        $count_id = I('get.count_id');
        $this->assign("liuchenid",$liuchenid);
        $this->assign("count_id",$count_id);
        $this->display();
    }

    public function  avg_liuchen(){
        $tiXing = M('oa_tixing');
        $liuchenid = I('get.liuchenid');
        $count_id = I('get.count_id');
        /*$liuChen = M('oa_liuchen');
        $id = I('get.id');
        $BDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $EDate = date('Y-m-d', strtotime("$BDate +1 month -1 day"));

        $Data = $liuChen->field("COUNT(id) as sum_count,GROUP_CONCAT(liuchenid) as liuchenid")->where("status=2 and DATE_FORMAT(addtime,'%Y-%m-%d')>='".$BDate."' and  DATE_FORMAT(addtime,'%Y-%m-%d')<='".$EDate."' AND mid=".$id)->find();

        $deData = $tiXing->field("TIMESTAMPDIFF(MINUTE,addtime,overtime) as min_z,userid")->where("liuchenid in (".$Data['liuchenid'].")")->group('jiedianid,liuchenid')->select();*/

        $deData = $tiXing->field("sum(TIMESTAMPDIFF(MINUTE,addtime,overtime)) as min_z,userid")->where("liuchenid in (".$liuchenid.")")->group('jiedianid')->select();

        $avgTime = array();$deps = array();
        foreach($deData as $key=>$val){
            $dep = M('user_department')->field('name')->join("a join boss_user b on a.id=b.dept_id")->where("b.id=".$val['userid'])->find();
            $zhs_time = round($val['min_z']/60/$count_id,2);//时间
            $avgTime[] = $zhs_time;
            $deps[] = $dep['name'];
        }
        $ret = array(
            'deps'=>$deps,
            'avgTime'=>$avgTime
        );
        $this->ajaxReturn($ret);
    }

    /*本月流程数量详情*/
    public function count_detail(){
        $liuchenid = I('get.liuchenid');
        $model = M('oa_liuchen');
        if($liuchenid){

            $countData = $model->field("a.addtime,a.name,b.real_name,c.name as depart_name,TIMESTAMPDIFF(MINUTE,a.addtime,MAX(d.overtime) ) as min_c")->join("a left join boss_user b on a.adduser=b.id left join boss_user_department c on c.id=b.dept_id left join boss_oa_tixing d ON a.liuchenid=d.liuchenid")->where("a.liuchenid in (".$liuchenid.")")->group("a.liuchenid")->select();
            foreach($countData as $key=>$val){
                $countData[$key]['min_c'] = round($val['min_c']/60,2);
            }
            $this->assign('list',$countData);
            $this->display();
        }
    }

    /*总耗时TOP部门明细*/
    public function top_detail(){

        $liuchenid = I('get.liuchenid');
        $count_id = I('get.count_id');

        /*// 1.根据节点id分组
        $zData = M('oa_tixing')->field("TIMESTAMPDIFF(MINUTE,addtime,overtime) as min_z,jiedianid,userid")->where("liuchenid in (".$liuchenid.")")->group("jiedianid")->order("TIMESTAMPDIFF(MINUTE,addtime,overtime) DESC")->limit(5)->select();
        ;
        foreach($zData as $key3=>$val3){

            //根据userid查部门
            $dep = M('user_department')->field('name')->join("a join boss_user b on a.id=b.dept_id")->where("b.id=".$val3['userid'])->find();
            $zData[$key3]['top_dep'] = $dep['name'];
            $zData[$key3]['top_avg_time'] = round($val3['min_z']/60/$count_id,2);
        }*/

        // 1.根据部门id分组
        $zData = M('oa_tixing')->field("SUM(TIMESTAMPDIFF(MINUTE,a.addtime,a.overtime)) as min_z,a.jiedianid,a.userid,c.name")->where("liuchenid in (".$liuchenid.")")->join("a join boss_user b on a.userid=b.id left join boss_user_department c on c.id=b.dept_id")->group("b.dept_id")->order("SUM(TIMESTAMPDIFF(MINUTE,a.addtime,a.overtime)) DESC")->limit(5)->select();

        foreach($zData as $key3=>$val3){

            $zData[$key3]['top_dep'] = $val3['name'];
            $zData[$key3]['top_avg_time'] = round($val3['min_z']/60/$count_id,2);
        }

        $this->assign('list',$zData);
        $this->display();
    }


}
