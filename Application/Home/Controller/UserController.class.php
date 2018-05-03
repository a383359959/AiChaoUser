<?php

namespace Home\Controller;

use Think\Controller;

class UserController extends Controller{
	
    public function index(){
		checkSchool();
		$user = M('users')->where('id = '.getUserId())->find();
		$user['user_info'] = unserialize($user['user_info']);
		$this->assign('user',$user);
		$this->display();
	}
	
	public function address(){
		$list = M('users_address')->where('user_id = '.getUserId())->order('id desc')->select();
		foreach($list as $key => $value){
			$school_address = array();
			if($value['school_id'] > 0) $school_address[] = M('school')->where('id = '.$value['school_id'])->getField('name');
			if($value['school_address_id'] > 0) $school_address[] = M('school_address')->where('id = '.$value['school_address_id'])->getField('name');
			$school_address[] = $value['address'];
			$value['address'] = implode('&nbsp;',$school_address);
			$list[$key] = $value;
		}
		$this->assign('list',$list);
		$this->display();
	}
	
	public function address_add(){
		if($_REQUEST['form_submit'] == 'ok'){
			$data['user_id'] = getUserId();
			$data['name'] = $_REQUEST['name'];
			$data['telephone'] = $_REQUEST['telephone'];
			$data['address'] = $_REQUEST['address'];
			$data['school_address_id'] = $_REQUEST['school_address_id'];
			$data['school_id'] = session('school_id');
			M('users_address')->add($data);
			if(!empty($_REQUEST['is_new'])){
				header('location:'.U('Store/checkout',array('store_id' => $_REQUEST['store_id'])));
			}else{
				header('location:'.U('address',array('store_id' => $_REQUEST['store_id'],'status' => $_REQUEST['status'])));
			}
		}
		$school_address = M('school_address')->where('school_id = '.session('school_id'))->order('sort asc')->select();
		$this->assign('school_address',$school_address);
		$this->display();
	}
	
	public function address_edit(){
		if($_REQUEST['form_submit'] == 'ok'){
			$data['name'] = $_REQUEST['name'];
			$data['telephone'] = $_REQUEST['telephone'];
			$data['school_address_id'] = $_REQUEST['school_address_id'];
			$data['address'] = $_REQUEST['address'];
			$data['school_id'] = session('school_id');
			M('users_address')->where('id = '.$_REQUEST['id'])->save($data);
			header('location:'.U('address',array('store_id' => $_REQUEST['store_id'],'status' => $_REQUEST['status'])));
		}
		$school_address = M('school_address')->where('school_id = '.session('school_id'))->order('sort asc')->select();
		$row = M('users_address')->where('id = '.$_REQUEST['id'])->find();
		$this->assign('row',$row);
		$this->assign('school_address',$school_address);
		$this->display();
	}
	
	public function address_del(){
		$order_food = M('order_food')->where('users_address_id = '.$_REQUEST['id'])->find();
		$order_supermarket = M('order_supermarket')->where('users_address_id = '.$_REQUEST['id'])->find();
		if($order_food || $order_supermarket) die('<meta charset="utf-8" /><script>alert("此地址被占用，不可删！");window.location.href="'.U('address').'";</script>');
		M('users_address')->where('id = '.$_REQUEST['id'])->delete();
		header('location:'.U('address'));
	}
	
	public function task(){
		$list = M('order')->where('pei_user = '.getUserId())->order('pei_add_time desc')->select();
		foreach($list as $key => $value){
			$find = M('store')->where('id = '.$value['store_id'])->find();
			$value['store_name'] = $find['store_name'];
			$value['store_address'] = $find['address'];
			$value['logo'] = $find['logo'];
			$list[$key] = $value;
		}
		$this->assign('list',$list);
		$this->display();
	}
	
	public function task_del(){
		$data['task_del'] = 1;
		M('order')->where('id = '.$_REQUEST['id'])->save($data);
		$arr['status'] = 'success';
		die(json_encode($arr));
	}
	
	public function tasklist(){
		$list = M('order')->where('task_del = 0 and pei_user = '.getUserId())->order('pei_add_time desc')->select();
		foreach($list as $key => $value){
			$find = M('store')->where('id = '.$value['store_id'])->find();
			$value['store_name'] = $find['store_name'];
			$value['store_address'] = $find['address'];
			$value['logo'] = $find['logo'];
			$value['order_price'] = getOrderPrice($value['id']);
			$value['goods'] = getTaskGoods($value,1);
			$value['pei_status'] = $value['pei_status'] > 0 ? '已完成' : '未完成';
			$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
			$list[$key] = $value;
		}
		$arr['list'] = $list;
		die(json_encode($arr));
	}
	
	public function setTask(){
		$data['pei_status'] = 1;
		M('order')->where('id = '.$_REQUEST['order_id'])->save($data);
		setStatus('兼职人员已确认',$_REQUEST['order_id']);
		$arr['status'] = 'success';
		die(json_encode($arr));
	}
	
	public function tixian(){
		$list = M('users_cash')->where('user_id = '.getUserId())->order('add_time desc')->select();
		$this->assign('list',$list);
		$this->display();
	}
	
	public function tixian_add(){
		if($_REQUEST['form_submit'] == 'ok'){
			$data['user_id'] = getUserId();
			$data['alipay_accounts'] = $_REQUEST['alipay_accounts'];
			$data['alipay_name'] = $_REQUEST['alipay_name'];
			$data['price'] = $_REQUEST['price'];
			$data['add_time'] = time();
			$data['status'] = 1;
			M('users_cash')->add($data);
			M('users')->where('id = '.getUserId())->setDec('money',$_REQUEST['price']);
			header('location:'.U('tixian'));
		}
		$user = M('users')->where('id = '.getUserId())->find();
		$this->assign('user',$user);
		$this->display();
	}
	
}