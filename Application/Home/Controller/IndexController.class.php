<?php

namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller{

	public function jisuan(){
		$ids = M('order')->where('store_id = 287 and pay_time between 1521561600 and 1522511999')->getField('group_concat(id)');
		echo $ids;
		$order_goods = M('order_goods')->where('type = 4 and order_id in ('.$ids.')')->getField('sum(goods_number) * 3');
		dump($order_goods);
	}

    public function index(){
		if(!empty(session('openid'))){
			$find = M('users')->where('openid = "'.session('openid').'"')->find();
			$arr = session('user_info');
			$arr['nickname'] = base64_encode($arr['nickname']);
			if(!$find){
				$data['openid'] = session('openid');
				$data['user_info'] = serialize($arr);
				M('users')->add($data);
			}else{
				$data['user_info'] = serialize($arr);
				M('users')->where('openid = "'.session('openid').'"')->save($data);
			}
		}else{
			$weixin = new \Think\WeiXin();
			edit;
		}
		checkSchool();
		$school = M('school')->where('id = '.session('school_id'))->find();
		$banner = M('banner')->order('sort asc')->select();
		$supermarket = M('store')->where('school_id = '.session('school_id').' and type = 1')->find();
		$this->assign('school',$school);
		$this->assign('banner',$banner);
		$this->assign('supermarket',$supermarket);
		$this->display();
	}
	
	public function canyin(){
		$school = M('school')->where('id = '.session('school_id'))->find();
		$category = M('category')->where('parent_id = 2')->order('sort asc')->select();
		$this->assign('school',$school);
		$this->assign('category',$category);
		$this->display();
	}
	
	public function xiyi(){
		$store = M('store')->where('type = 3 and school_id = '.session('school_id'))->find();
		if(!$store) die('<meta charset="utf-8" /><script>alert("学校还未开通洗衣服务！");history.go(-1);</script>');
		$school = M('school')->where('id = '.session('school_id'))->find();
		$school_address = M('school_address')->where('school_id = '.session('school_id'))->order('sort asc')->select();
		$xiyitype = M('xiyitype')->order('sort asc')->select();
		$this->assign('school',$school);
		$this->assign('xiyitype',$xiyitype);
		$this->assign('school_address',$school_address);
		$this->display();
	}
	
	public function getXiYiPrice(){
		$price = 0;
		$find = M('xiyitype')->field('price')->where('id = '.$_REQUEST['value'])->find();
		if($find) $price = $find['price'];
		$arr['price'] = number_format($price,2);
		die(json_encode($arr));
	}
	
	public function setXiYi(){
		$store = M('store')->where('type = 3 and school_id = '.session('school_id'))->find();
		$pay_price_total = M('xiyitype')->where('id = '.$_REQUEST['xiyitype_id'])->getField('price');
		$order_laundry = array(
			'xiyitype_id' => $_REQUEST['xiyitype_id'],
			'school_address_id' => $_REQUEST['school_address_id'],
			'consignee_name' => $_REQUEST['consignee_name'],
			'consignee_telephone' => $_REQUEST['consignee_telephone'],
			'consignee_address' => $_REQUEST['consignee_address']
		);
		$foreign_key = M('order_laundry')->add($order_laundry);
		$order = array(
			'order_sn' => getOrderSn(),
			'store_id' => $store['id'],
			'type' => 1,
			'is_task' => 1,
			'foreign_key' => $foreign_key,
			'school_id' => session('school_id'),
			'user_id' => getUserId(),
			'add_time' => time(),
			'table' => 'order_laundry',
			'status' => 1,
			'school_address_id' => $order_laundry['school_address_id'],
			'pay_price_total' => $pay_price_total,
			'lng' => $_REQUEST['lng'],
			'lat' => $_REQUEST['lat'],
			'expect_time' => time() + 3000,
			'pei_type' => $store['pei_type']
		);
		$order_id = M('order')->add($order);
		setOrderStatus($order_id,'订单已确认');
		$school_address = M('school_address')->where('id = '.$order_laundry['school_address_id'])->find();
		$school_name = M('school')->where('id = '.$school_address['school_id'])->getField('name');
		$address = $school_name.' '.$school_address['name'].' '.$order_laundry['consignee_address'];
		$msg = array(
			'first' => array(
				'value' => '您有新的订单消息',
				'color' => '#333'
			),
			'tradeDateTime' => array(
				'value' => date('Y-m-d H:i:s',$order['add_time']),
				'color' => '#333'
			),
			'orderType' => array(
				'value' => '洗衣',
				'color' => '#333'
			),
			'customerInfo' => array(
				'value' => $order_laundry['consignee_name'],
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
				'value' => '订单状态：订单已确认\n联系方式：'.$order_laundry['consignee_telephone'].'\n收货地址：'.$address,
				'color' => '#333'
			)
		);
		$weixin = new \Think\WeiXinTemplate();
		$weixin->send(session('openid'),$msg,$order_id);
		$json = getOrder($order_id.'_'.time(),$order['pay_price_total']);
		die($json);
	}
	
	public function daiqu(){
		$store = M('store')->where('type = 4 and school_id = '.session('school_id'))->find();
		if(!$store) die('<meta charset="utf-8" /><script>alert("学校还未开通代取快递服务！");history.go(-1);</script>');
		$school = M('school')->where('id = '.session('school_id'))->find();
		$school_address = M('school_address')->where('school_id = '.session('school_id'))->order('sort asc')->select();
		$xiyitype = M('xiyitype')->order('sort asc')->select();
		$this->assign('school',$school);
		$this->assign('xiyitype',$xiyitype);
		$this->assign('school_address',$school_address);
		$this->display();
	}
	
	public function setKuaiDi(){
		$store_ = M('store')->where('type = 4 and school_id = '.session('school_id'))->find();
		$pay_price_total = $order_express['weight'] == 1 ? 3 : 5;
		if($_REQUEST['type'] == 1){
			$order_express = array(
				'store_id' => $store['id'],
				'type' => $_REQUEST['type'],
				'weight' => $_REQUEST['weight'],
				'express_number' => $_REQUEST['express_number'],
				'express_name' => $_REQUEST['express_name'],
				'id_card' => $_REQUEST['id_card'],
				'name' => $_REQUEST['name'],
				'telephone' => $_REQUEST['telephone'],
				'school_address_id' => $_REQUEST['school_address_id'],
				'address' => $_REQUEST['address']
			);
		}else{
			$order_express = array(
				'type' => $_REQUEST['type'],
				'weight' => $_REQUEST['weight'],
				'sender_name' => $_REQUEST['sender_name'],
				'sender_telephone' => $_REQUEST['sender_telephone'],
				'school_address_id' => $_REQUEST['school_address_id'],
				'address' => $_REQUEST['address'],
				'recipient_name' => $_REQUEST['recipient_name'],
				'recipient_telephone' => $_REQUEST['recipient_telephone'],
				'recipient_address' => $_REQUEST['recipient_address'],
			);
		}
		$foreign_key = M('order_express')->add($order_express);
		$order = array(
			'order_sn' => getOrderSn(),
			'store_id' => $store['id'],
			'type' => 2,
			'is_task' => 1,
			'foreign_key' => $foreign_key,
			'school_id' => session('school_id'),
			'user_id' => getUserId(),
			'add_time' => time(),
			'table' => 'order_express',
			'status' => 1,
			'school_address_id' => $order_express['school_address_id'],
			'pay_price_total' => $pay_price_total,
			'lng' => $_REQUEST['lng'],
			'lat' => $_REQUEST['lat'],
			'expect_time' => time() + 3000,
			'pei_type' => $store['pei_type']
		);
		$order_id = M('order')->add($order);
		setOrderStatus($order_id,'订单已确认');
		$school_address = M('school_address')->where('id = '.$order_express['school_address_id'])->find();
		$school_name = M('school')->where('id = '.$school_address['school_id'])->getField('name');
		$address = $school_name.' '.$school_address['name'].' '.$order_express['address'];
		$msg = array(
			'first' => array(
				'value' => '您有新的订单消息',
				'color' => '#333'
			),
			'tradeDateTime' => array(
				'value' => date('Y-m-d H:i:s',$order['add_time']),
				'color' => '#333'
			),
			'orderType' => array(
				'value' => ($_REQUEST['type'] == 1 ? '取快递' : '发快递'),
				'color' => '#333'
			),
			'customerInfo' => array(
				'value' => ($_REQUEST['type'] == 1 ? $order_express['name'] : $order_express['sender_name']),
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
				'value' => '订单状态：订单已确认\n联系方式：'.($_REQUEST['type'] == 1 ? $order_express['telephone'] : $order_express['sender_telephone']).'\n取件地址：'.$address,
				'color' => '#333'
			)
		);
		$weixin = new \Think\WeiXinTemplate();
		$weixin->send(session('openid'),$msg,$order_id);
		$json = getOrder($order_id.'_'.time(),$order['pay_price_total']);
		die($json);
	}
	
	public function youhui(){
		$school = M('school')->where('id = '.session('school_id'))->find();
		$this->assign('school',$school);
		$this->display();
	}
	
	public function youhui_list(){
		$ids = M('store_activity')->where('('.time().' >= begin_time and '.time().' <= end_time) and type = '.$_REQUEST['type'])->getField('group_concat(store_id)');
		$list = array();
		if($ids){
			$limit = (($_REQUEST['page'] - 1) * 10).',10';
			if($_REQUEST['category_id']) $where['type_id'] = $_REQUEST['category_id'];
			$list = M('store')->field('id,logo,store_name,sale,price_qisong,dashang_price,address,ranking,ranking_status,price,ranking_budget')->where('id in ('.$ids.') and type = 2 and school_id = '.$_REQUEST['school_id'])->limit($limit)->order('ranking desc,sort asc,id desc')->select();
			foreach($list as $key => $value){
				$value['is_close'] = isClose($value['id']);
				$ranking = M('store_ranking_log')->field('ifnull(sum(price),0) as ranking_price')->where('store_id = '.$value['id'])->find();
				$value['activity'] = getActivityHtml($value['id']);
				$value['ranking_price'] = $ranking['ranking_price'];
				if($value['ranking_status'] == 1 && $value['price'] > 0 && $value['ranking'] > 0 && $value['ranking_price'] <= $value['ranking_budget']){
					$value['url'] = U('Store/index',array('id' => $value['id'],'type' => 'seo'));
				}else{
					$value['url'] = U('Store/index',array('id' => $value['id']));
				}
				$list[$key] = $value;
			}
		}
		$arr['list'] = $list;
		die(json_encode($arr));
	}
	
	public function index_list(){
		$limit = (($_REQUEST['page'] - 1) * 10).',10';
		if($_REQUEST['category_id']) $where['type_id'] = $_REQUEST['category_id'];
		$field = array('a.id','a.logo','a.store_name','a.sale','a.price_qisong','a.dashang_price','a.address','a.ranking','a.ranking_status','a.price','a.ranking_budget');
		$list = M('store as a')->field(implode(',',$field))->where('a.type = 2 and a.school_id = '.$_REQUEST['school_id'])->limit($limit)->order('ranking desc,business_status asc,sort asc,id desc')->select();
		foreach($list as $key => $value){
			$value['is_close'] = isClose($value['id']);
			$ranking = M('store_ranking_log')->field('ifnull(sum(price),0) as ranking_price')->where('store_id = '.$value['id'])->find();
			$value['activity'] = getActivityHtml($value['id']);
			$value['ranking_price'] = $ranking['ranking_price'];
			if($value['ranking_status'] == 1 && $value['price'] > 0 && $value['ranking'] > 0 && $value['ranking_price'] <= $value['ranking_budget']){
				$value['url'] = U('Store/index',array('id' => $value['id'],'type' => 'seo'));
			}else{
				$value['url'] = U('Store/index',array('id' => $value['id']));
			}
			$list[$key] = $value;
		}
		$arr['list'] = $list;
		die(json_encode($arr));
	}
	
	public function school(){
		if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
			session('school_id',$_REQUEST['id']);
			header('location:'.U('index'));
		}
		$list = M('school')->order('sort asc')->select();
		$this->assign('list',$list);
		$this->display();
	}
	
}