<?php
/**测试未通过产品披露
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/26
 * Time: 10:14
 */
namespace Home\Controller;
use Common\Controller\BaseController;
class RiskStopProductController extends BaseController {
    protected $totalPage = 0;

    public function index() {

        $where = array();
        $list = $this->lists($this, $where);

        $this->assign('list', $list);
        $this->display();

    }

    public function getList($where, $field) {

        $adIds = I('get.adid','');
        if(!empty($adIds)) {
            $adArr = M('advertiser')->where("id IN ({$adIds})")->field('id,name')->select();
            $where['d.id'] = array('in', $adIds);
        }
        $proIds = I('get.proid','');
        if(!empty($proIds)) {
            $proArr = M('product')->where("id IN ({$proIds})")->field('id,name')->select();
            $where['c.id'] = array('in', $proIds);
        }
        $salerIds = I('get.salerid','');
        if(!empty($salerIds)) {
            $salerArr = M('user')->where("id IN ({$salerIds})")->field('id,real_name AS name')->select();
            $where['c.saler_id'] = array('in', $salerIds);
        }
        $where['_string'] = "h.id NOT IN (3423,3441,3215,3440) and DATEDIFF(DATE_FORMAT(h.add_date,'%Y-%m-%d'),g.sdate)>0";
        $this->assign('adNames', empty($adArr)?'[]':json_encode($adArr,JSON_UNESCAPED_UNICODE));//
        $this->assign('proNames', empty($proArr)?'[]':json_encode($proArr,JSON_UNESCAPED_UNICODE));
        $this->assign('salerNames', empty($salerArr)?'[]':json_encode($salerArr,JSON_UNESCAPED_UNICODE));

        $ml = M('product');
        //求和
        $countData = $ml
            ->field("
sum(if(a.status>0 && a.status<9,a.newmoney,0)) AS money,
sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
sum(if(f.status>0 && f.status<9,f.newmoney,0)) AS out_money,
sum(if(f.status=4,f.newmoney,0)) AS pay_money")
            ->join('c JOIN
(select a.id,b.add_date  from boss_product a
join (SELECT pr_id,max(add_date) as add_date  FROM boss_product_state WHERE  cooperate_state IN (2,3) group by pr_id having count(id)>1) b
on a.id=b.pr_id and a.cooperate_state=3) h on h.id=c.id
left join `boss_daydata` a on h.id=a.comid
JOIN boss_advertiser d ON c.ad_id = d.id
LEFT JOIN boss_user e ON e.id = a.salerid
left JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where  b.cooperate_state =3 GROUP BY a.comid ) g ON g.comid=c.id
LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
            ->where($where)
            ->find();
        $this->assign('countData',$countData);

        //列表
        $resData = $ml
            ->field("DATEDIFF(DATE_FORMAT(h.add_date,'%Y-%m-%d'),g.sdate) as days,
sum(if(a.status>0 && a.status<9,a.newmoney,0)) AS money,
sum(if(a.status in (1,2,3,4),a.newmoney,0))/sum(if(a.status>0 && a.status<9,a.newmoney,0)) as sr_qrl,
sum(if(a.status in (1,2,3,4),a.newmoney,0)) as whk_money,
sum(if(f.status>0 && f.status<9,f.newmoney,0)) AS out_money,
sum(if(f.status in (1,2,3),f.newmoney,0))/sum(if(f.status>0 && a.status<9,f.newmoney,0)) as cb_qrl,
(sum(if(a.status>0 && a.status<9,a.newmoney,0)) - sum(if(f.status>0 && a.status<9,f.newmoney,0)) )/sum(if(a.status>0 && a.status<9,a.newmoney,0)) as lrl,
sum(if(f.status=4,f.newmoney,0)) AS pay_money,
c.name AS pro_name,d.name AS ad_name,e.real_name AS saler_name")
            ->join('c JOIN
(select a.id,b.add_date  from boss_product a
join (SELECT pr_id,max(add_date) as add_date  FROM boss_product_state WHERE  cooperate_state IN (2,3) group by pr_id having count(id)>1) b
on a.id=b.pr_id and a.cooperate_state=3) h on h.id=c.id
left join `boss_daydata` a on h.id=a.comid
JOIN boss_advertiser d ON c.ad_id = d.id
LEFT JOIN boss_user e ON e.id = a.salerid
left JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where  b.cooperate_state =3 GROUP BY a.comid ) g ON g.comid=c.id
LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
            ->where($where)
            ->group('a.comid')
            ->order('sum(if(a.status>0 && a.status<9,a.newmoney,0)) desc')
            ->page($_GET['p'],  C('LIST_ROWS'))->select();
            foreach($resData as $key=>$val){
                $resData[$key]['sr_qrl'] = round($val['sr_qrl']*100,2).'%';
                $resData[$key]['cb_qrl'] = round($val['cb_qrl']*100,2).'%';
                $resData[$key]['lrl'] = round($val['lrl']*100,2).'%';
            }
        $subQuery = $ml
            ->field("c.id")
            ->join('c JOIN
(select a.id,b.add_date  from boss_product a
join (SELECT pr_id,max(add_date) as add_date  FROM boss_product_state WHERE  cooperate_state IN (2,3) group by pr_id having count(id)>1) b
on a.id=b.pr_id and a.cooperate_state=3) h on h.id=c.id
left join `boss_daydata` a on h.id=a.comid
JOIN boss_advertiser d ON c.ad_id = d.id
LEFT JOIN boss_user e ON e.id = a.salerid
left JOIN (SELECT comid,MIN(a.adddate) AS sdate FROM `boss_daydata` a join boss_product b on a.comid=b.id where  b.cooperate_state =3 GROUP BY a.comid ) g ON g.comid=c.id
LEFT JOIN boss_daydata_out f ON f.jfid=a.jfid AND f.adddate=a.adddate')
            ->where($where)
            ->group('a.comid')
            ->buildSql();

        $this->totalPage = $count_all = $ml->table($subQuery.' aa')->where()->count();
        $this->assign('count_all',$count_all);
        $this->assign('countSum',$countData['money']);
        return $resData;
    }

    public function export(){
        $where = array();
        C('LIST_ROWS', ''); //不分页
        $data = $this->lists($this, $where);

        $list=array(array('days','测试周期'),array('pro_name','产品'),array('ad_name','广告主'),array('saler_name','销售'),array('money','产生总收入'),array('sr_qrl','收入确认率'),array('out_money','产生总成本'),array('cb_qrl','成本确认率'),array('lrl','利润率'),array('whk_money','未回款金额'),array('pay_money','已支付成本'));
        $this->downloadlist($data,$list,'测试未通过产品');
    }
}