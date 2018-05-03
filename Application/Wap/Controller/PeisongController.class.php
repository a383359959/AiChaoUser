<?php

namespace Wap\Controller;

use Think\Controller;

class PeisongController extends Controller{

	public function index(){
		$begin_time = strtotime(I('begin_date').' 00:00:00');
		$end_time = strtotime(I('end_date').' 23:59:59');
		$peisong = M('peisong')->where('school_id = '.I('school_id'))->select();
		foreach($peisong as $key => &$value){
			$value['order_count'] = M('order')->where('pay_time between '.$begin_time.' and '.$end_time.' and `status` > 6 and peisong_id = '.$value['id'])->count();
			$value['haoping'] = M('peisong_eval')->where('add_time between '.$begin_time.' and '.$end_time.' and (lv = 4 or lv = 5) and peisong_id = '.$value['id'])->count();
			$value['chaping'] = M('peisong_eval')->where('add_time between '.$begin_time.' and '.$end_time.' and (lv = 1 or lv = 2) and peisong_id = '.$value['id'])->count();
			$value['chaoshi'] = M('order')->where('pay_time between '.$begin_time.' and '.$end_time.' and `status` > 6 and songcan_time > 30 and peisong_id = '.$value['id'])->count();
		}
		$this->assign('peisong',$peisong);
		$this->display();
	}
	
	public function detail(){
		$begin_time = strtotime(I('begin_date').' 00:00:00');
		$end_time = strtotime(I('end_date').' 23:59:59');
		$peisong = M('peisong')->where('id = '.I('peisong_id'))->find();
		$list = M('order')->field('count(id) as order_count,FROM_UNIXTIME(pay_time,"%Y-%m-%d") as datetime')->where('pay_time between '.$begin_time.' and '.$end_time.' and peisong_id = '.$peisong['id'])->group('datetime')->order('datetime asc')->select();
		foreach($list as $key => &$value){
			$begin_time = strtotime($value['datetime'].' 00:00:00');
			$end_time = strtotime($value['datetime'].' 23:59:59');
			$value['order_count'] = M('order')->where('pay_time between '.$begin_time.' and '.$end_time.' and `status` > 6 and peisong_id = '.$peisong['id'])->count();
			$value['haoping'] = M('peisong_eval')->where('add_time between '.$begin_time.' and '.$end_time.' and (lv = 4 or lv = 5) and peisong_id = '.$peisong['id'])->count();
			$value['chaping'] = M('peisong_eval')->where('add_time between '.$begin_time.' and '.$end_time.' and (lv = 1 or lv = 2) and peisong_id = '.$peisong['id'])->count();
			$value['chaoshi'] = M('order')->where('pay_time between '.$begin_time.' and '.$end_time.' and `status` > 6 and songcan_time > 30 and peisong_id = '.$peisong['id'])->count();
			$value['date'] = date('m-d',$begin_time);
		}
		$this->assign('peisong',$peisong);
		$this->assign('list',$list);
		$this->display();
	}
}