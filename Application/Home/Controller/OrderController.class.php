<?php

namespace Home\Controller;

use Think\Controller;

class OrderController extends Controller{
	
	
	public function index(){
		checkSchool();
		$this->display();
	}
	
	public function changeStatus(){
		$data['status'] = $_REQUEST['status'];
		$order = M('order')->where('id = '.$_REQUEST['id'])->find();
		$store = M('store')->where('id = '.$order['store_id'])->find();
		if($data['status'] == 8){	// 确认收货
			/*
			if($order['peisong_id'] > 0){
				$price = 0;
				$peisong = M('peisong')->where('id = '.$order['peisong_id'])->find();
				if($peisong['shenfen'] == 1){	// 全职
					if($store['category_id'] == 1){
						$price = $order['pei_price_total'] + 1;
					}else{
						$price = 1;
					}
				}else if($peisong['shenfen'] == 2){	// 兼职
					if($peisong['jingyan'] <= 50){
						$price = 1;
					}else{
						$price = 1.5;
					}
					// if($order['songda_time'] - time() > 1800) $price = 0; // 超过30分钟扣除佣金
				}else if($peisong['shenfen'] == 3){
					$price = 1;
				}
				M('peisong')->where('id = '.$order['peisong_id'])->setInc('price',$price);
			}
			*/
			setOrderStatus($order['id'],'用户确认收货');
			
		}else if($data['status'] == 3){	// 取消订单
			
			if($store['category_id'] == 1 && $store['is_chongzhi'] == 0){	// 封闭式
				M('store')->where('id = '.$store['id'])->setInc('price',1);
			}else if($store['category_id'] == 2){	// 开放式
				M('store')->where('id = '.$store['id'])->setDec('price',$order['pay_price_total'] - 1);
			}
			
			// 恢复用户账户余额
			M('users')->where('id = '.getUserId())->setInc('money',$order['pay_price_total']);
			setOrderStatus($order['id'],'用户取消订单');
			
		}
		M('order')->where('id = '.$_REQUEST['id'])->save($data);
		$arr['status'] = 'success';
		die(json_encode($arr));
	}
	
	public function getOrderList(){
		$pagesize = 10;
		$limit = ($_REQUEST['page'] - 1) * $pagesize.','.$pagesize;
		$where[] = 'user_id = '.getUserId();
		if($_REQUEST['index'] == 1) $where[] = 'pay_status = 0';
		if($_REQUEST['index'] == 2) $where[] = 'pay_status = 1 and (status = 1 OR status = 4 OR status = 5)';
		if($_REQUEST['index'] == 3) $where[] = 'pay_status = 1 and status = 6';
		if($_REQUEST['index'] == 4) $where[] = 'pay_status = 1 and (status = 7 OR status = 8 OR status = 9)';
		if($_REQUEST['index'] == 5) $where[] = 'pay_status = 1 and status = 8';
		$list = M('order')->field('id,add_time,store_id,type,status,pay_status')->where(implode(' and ',$where))->order('id desc')->limit($limit)->select();
		foreach($list as $key => $value){
			$store = M('store')->field('store_name,logo')->where('id = '.$value['store_id'])->find();
			$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
			if($value['type'] == 1){
				$value['store_name'] = '洗衣';
				$value['store_url'] = U('Index/xiyi');
			}else if($value['type'] == 2){
				$value['store_name'] = '快递';
				$value['store_url'] = U('Index/kuaidi');
			}else{
				$value['store_name'] = $store['store_name'];
				$value['store_url'] = U('Store/index',array('id' => $value['store_id']));
			}
			$value['store_logo'] = $value['type'] == 1 || $value['type'] == 2 ? '/Public/Home/images/logo.jpg' : $store['logo'];
			$value['order_url'] = U('Order/order_detail',array('order_id' => $value['id']));
			$value['msg_url'] = U('Order/msg',array('order_id' => $value['id']));
			$list[$key] = $value;
		}
		$arr['list'] = $list;
		die(json_encode($arr));
	}
	
	public function msg(){
		if($_REQUEST['form_submit'] == 'ok'){
			// 评价店铺
			$store_eval = array(
				'store_id' => $_REQUEST['store_id'],
				'user_id' => getUserId(),
				'order_id' => $_REQUEST['order_id'],
				'content' => $_REQUEST['store_content'],
				'is_reply' => 1,
				'add_time' => time(),
				'lv' => $_REQUEST['store_level']
			);
			M('store_eval')->add($store_eval);
			M('order')->where('id = '.$_REQUEST['order_id'])->save(array('shop_eval_lv' => $_REQUEST['store_level']));
			// 评价配送员
			if($_REQUEST['peisong_id'] > 0){
				$peisong_eval = array(
					'peisong_id' => $_REQUEST['peisong_id'],
					'order_id' => $_REQUEST['order_id'],
					'user_id' => getUserId(),
					'content' => $_REQUEST['peisong_content'],
					'is_reply' => 1,
					'add_time' => time(),
					'lv' => $_REQUEST['peisong_level']
				);
				M('peisong_eval')->add($peisong_eval);
				M('order')->where('id = '.$_REQUEST['order_id'])->save(array('peisong_eval_lv' => $_REQUEST['peisong_level']));
			}
			// 评价商品
			if(is_array($_REQUEST['goods_id'])){
				foreach($_REQUEST['goods_id'] as $key => $value){
					$goods_eval = array(
						'store_id' => $_REQUEST['store_id'],
						'user_id' => getUserId(),
						'order_id' => $_REQUEST['order_id'],
						'goods_id' => $value,
						'content' => $_REQUEST['goods_content'][$key],
						'is_reply' => 1,
						'add_time' => time(),
						'lv' => $_REQUEST['goods_level'][$key]
					);
					M('goods_eval')->add($goods_eval);
				}
			}else{
				$goods_eval = array(
					'store_id' => $_REQUEST['store_id'],
					'user_id' => getUserId(),
					'order_id' => $_REQUEST['order_id'],
					'goods_id' => $_REQUEST['goods_id'],
					'content' => $_REQUEST['goods_content'],
					'is_reply' => 1,
					'add_time' => time(),
					'lv' => $_REQUEST['goods_level']
				);
				M('goods_eval')->add($goods_eval);
			}
			setOrderStatus($order['id'],'用户评价成功');
			M('order')->where('id = '.$_REQUEST['order_id'])->save(array('status' => 9));
			die('<meta charset="utf-8" /><script>alert("评价成功！");window.location.href = "'.U('Order/index').'"</script>');
		}
		$order_id = $_REQUEST['order_id'];
		$order = M('order')->where('id = '.$order_id)->find();
		if($order['type'] == 1){
			$xiyitype = M('xiyitype')->where('id = '.$table['xiyitype_id'])->find();
			$school_address = M('school_address')->where('id = '.$order['school_address_id'])->find();
			$school = M('school')->where('id = '.$school_address['school_id'])->find();
			$order['store_name'] = $school['name'].'洗衣店';
			$order['title'] = '洗'.$xiyitype['name'];
			$order['goods_id'] = $table['xiyitype_id'];
		}else if($order['type'] == 2){
			$school_address = M('school_address')->where('id = '.$order['school_address_id'])->find();
			$school = M('school')->where('id = '.$school_address['school_id'])->find();
			$order['store_name'] = $school['name'].($table['type'] == 1 ? '取快递' : '发快递');
			$order['goods_id'] = $table['type'];
		}else{
			$goods = M('order_goods')->where('order_id = '.$order['id'])->select();
			$this->assign('goods',$goods);
		}
		$this->assign('order',$order);
		$this->display();
	}
	
	public function order_detail(){
		$order = M('order')->where('id = '.$_REQUEST['order_id'])->find();
		$order_msg = M('order_msg')->where('order_id = '.$order['id'])->order('id asc')->select();
		$this->assign('order_msg',$order_msg);
		$this->assign('order',$order);
		$this->display();
	}
	
	public function taskSuccess(){
		$order = M('order')->where('id = '.$_REQUEST['order_id'])->find();
		if($order['shipping_status'] == 0){
			$store = M('store')->where('id = '.$order['store_id'])->find();
			if($store['category_id'] == 1) $order['dashang_price'] = $order['goods_price'] + $order['pei_price'] + $order['dashang_price'];
			M('order')->where('id = '.$order['id'])->save(array('pei_status' => 2,'shipping_status' => 1));
			M('users')->where('id = '.$order['pei_user'])->setInc('money',$order['dashang_price']);
			setStatus('订单已完成',$order['id']);
		}
		$arr['status'] = 'success';
		die(json_encode($arr));
	}
	
	public function task(){
		$list = M('order')->where('pei_user = 0 and pei_time_timestamp - 15 * 60 > '.time().' and pay_status = 1 and pei_type = "兼职配送" and tuikuan = 0 and shipping_status = 0')->order('pei_time_timestamp asc')->select();
		foreach($list as $key => $value){
			$find = M('store')->where('id = '.$value['store_id'])->find();
			$user = M('users')->where('id = '.$value['user_id'])->find();
			$user_info = unserialize($user['user_info']);
			$value['store_name'] = $find['store_name'];
			$value['store_address'] = $find['address'];
			$value['logo'] = $find['logo'];
			$value['nickname'] = base64_decode($user_info['nickname']);
			$value['last_time'] = date('Y-m-d H:i:s',$value['pei_time_timestamp'] - 15 * 60);
			$list[$key] = $value;
		}
		$this->assign('list',$list);
		$this->display();
	}
	
	public function setTask(){
		$order = M('order')->where('id = '.$_REQUEST['order_id'])->find();
		$user = M('users')->where('id = '.$order['user_id'])->find();
		$b_user = M('users')->where('id = '.getUserId())->find();
		if($user['id'] == getUserId()){
			$arr['status'] = 'error';
			$arr['msg'] = '自己不能抢自己下的单';
		}else if(!$b_user['is_pei']){
			$arr['status'] = 'error';
			$arr['msg'] = '您没有接单权限';
		}else if(time() > ($order['pei_time_timestamp'] - 15 * 60)){
			$arr['status'] = 'error';
			$arr['msg'] = '该订单已下线';
			$arr['remove'] = 1;
		}else if($order['pei_user']){
			$arr['status'] = 'error';
			$arr['msg'] = '该订单已被抢';
			$arr['remove'] = 1;
		}else if($order['tuikuan'] == 1){
			$arr['status'] = 'error';
			$arr['msg'] = '该订单已取消';
			$arr['remove'] = 1;
		}else{
			$data['pei_user'] = getUserId();
			$data['pei_add_time'] = time();
			M('order')->where('id = '.$order['id'])->save($data);
			setStatus('订单已被抢',$order['id']);
			$arr['status'] = 'success';
			$arr['msg'] = '抢单成功';
		}
		die(json_encode($arr));
	}
	
	public function pay(){
		$order = M('order')->where('id = '.$_REQUEST['order_id'])->find();
		$store = M('store')->where('id = '.$order['store_id'])->find();
		$table = M($order['table'])->where('id = '.$order['foreign_key'])->find();
		$is_close = isClose($order['store_id']);
		$address = getAddress($order['id']);
		if($is_close == 1){
			$json = array(
				'status' => 'error',
				'msg' => '店铺休息中'
			);
			die(json_encode($json));
		}else{
			if($_REQUEST['pay_type'] == 0){
				$json = getOrder($order['id'].'_'.time(),$order['pay_price_total']);
			}else{
				$data = array(
					'pay_status' => 1,
					'pay_time' => time(),
				);
				M('order')->where('id = '.$order['id'])->save($data);
				setOrderStatus($order['id'],'订单已付款');
				if($store['category_id'] == 1 && $store['is_chongzhi'] == 0){	// 封闭式
					$storeData = array(
						'price' => $store['price'] - 1,
						'sale' => $store['sale'] + 1
					);
					$store_price_log = array(
						'surplus_price' => $store['price'] - 1,
						'store_id' => $store['id'],
						'price' => 1,
						'add_time' => time(),
						'type' => 1,
						'order_id' => $order['id']
					);
				}else if($store['category_id'] == 2){	// 开放式
					$p = $store['is_kou'] == 0 ? $order['pei_price_total'] - 1 : $order['pei_price_total'];
					$storeData = array(
						'price' => $store['price'] + $p,
						'sale' => $store['sale'] + 1
					);
					$store_price_log = array(
						'surplus_price' => $store['price'] + $p,
						'store_id' => $store['id'],
						'price' => $p,
						'add_time' => time(),
						'type' => 0,
						'order_id' => $order['id']
					);
				}
				M('store')->where('id = '.$store['id'])->save($storeData);
				M('store_price_log')->add($store_price_log);
				$msg = array(
					'first' => array(
						'value' => '您有新的订单消息',
						'color' => '#333'
					),
					'tradeDateTime' => array(
						'value' => date('Y-m-d H:i:s'),
						'color' => '#333'
					),
					'orderType' => array(
						'value' => ($order['type'] == 3 ? '餐饮' : '商超'),
						'color' => '#333'
					),
					'customerInfo' => array(
						'value' => $address['name'],
						'color' => '#333'
					),
					'orderItemName' => array(
						'value' => '订单编号',
						'color' => '#333'
					),
					'orderItemData' => array(
						'value' => $order['order_sn'],
						'color' => '#333'
					),
					'remark' => array(
						'value' => '备注：'.$table['note'].'\n订单状态：买家订单已付款，请确认订单\n联系方式：'.$address['telephone'].'\n收货地址：'.$address['address'],
						'color' => '#333'
					)
				);
				$store_user = M('store_login_locking')->where('`status` = 1 and user_id = '.$store['user_id'])->find();
				$weixin = new \Think\WeiXinTemplate();
				$weixin->send($store_user['open_id'],$msg,$order['id']);
				$json = array(
					'pay_status' => 1
				);
			}
		}
		die($json);
	}
	
	public function tousu(){
		if($_REQUEST['form_submit'] == 'ok'){
			$data = array(
				'order_id' => $_REQUEST['order_id'],
				'content' => $_REQUEST['content'],
				'user_id' => getUserId(),
				'add_time' => time()
			);
			M('tousu')->add($data);
			M('order')->where('id = '.$_REQUEST['order_id'])->save(array('is_tousu' => 1));
			die('<meta charset="UTF-8" /><script>alert("投诉提交成功！");window.location.href="'.U('index').'"</script>');
		}
		$order = M('order')->where('id = '.$_REQUEST['order_id'])->find();
		$this->assign('order',$order);
		$this->display();
	}
	
}