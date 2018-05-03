<?php

namespace Home\Controller;

use Think\Controller;

class StoreController extends Controller{
	
	
    public function index(){
		checkSchool();	// 检查是否登录
		$store = M('store')->where('id = '.$_REQUEST['id'])->find();
		$store['is_close'] = isClose($store['id']);	// 判断店铺是否营业
		storeFlowRate($store['id']);	// 写入店铺流量

		/*
		*	推广扣款
		*/
		if($store['is_close'] == 0 && $_REQUEST['type'] == 'seo'){
			$store_ranking_log = array(
				'store_id' => $store['id'],
				'user_id' => getUserId(),
				'school_id' => session('school_id'),
				'price'	=> $store['ranking'],
				'add_time' => time()
			);
			M('store_ranking_log')->add($store_ranking_log);
			M('store')->where('id = '.$store['id'])->setDec('price',$store['ranking']);
			storePriceLog($store['id'],$store['ranking'],1,'推广扣款');	// 写入流水
		}

		/*
		*	获取活动分类
		*/
		$activity_category = getActivityCategory($store['id']);

		/*
		*	获取商家分类
		*/
		$category = M('store_category')->field('id,name')->where('store_del = 0 and store_id = '.$_REQUEST['id'])->order('sort asc')->select();

		/*
		*	购物车数量
		*/
		$cart = M('cart')->field('sum(number) as sum')->where('user_id = '.getUserId().' and store_id = '.$_REQUEST['id'])->find();

		$this->assign('category',array_merge($activity_category,$category));
		$this->assign('store',$store);
		$this->assign('cart',!$cart['sum'] ? 0 : $cart['sum']);
		$this->display();
	}
	
	public function updateCart(){
		$goods = M('store_category_goods')->where('id = '.$_REQUEST['goods_id'])->find();
		$jiajia = M('store')->where('id = '.$goods['store_id'])->getField('jiajia');
		if($jiajia > 0 && $goods['ziying'] == 0) $goods['price'] += $goods['price'] * $jiajia / 100;
		if($_REQUEST['type'] == 'jia'){
			$find = M('cart')->where('goods_id = '.$_REQUEST['goods_id'].' and user_id = '.getUserId())->find();
			if($find){
				M('cart')->where('id = '.$find['id'])->setInc('number',$_REQUEST['number']);
				$number = $find['number'] + $_REQUEST['number'];
			}else{
				$data['store_id'] = $_REQUEST['store_id'];
				$data['number'] = $_REQUEST['number'];
				$data['goods_id'] = $_REQUEST['goods_id'];
				$data['user_id'] = getUserId();
				M('cart')->add($data);
				$number = $_REQUEST['number'];
			}
			$count = M('cart')->field('sum(number) as sum')->where('user_id = '.getUserId().' and store_id = '.$_REQUEST['store_id'])->find();
			$arr['count'] = !$count['sum'] ? 0 : $count['sum'];
			$arr['status'] = 'success';
			$arr['number'] = $number;
			$arr['price'] = $number * $goods['price'];
			die(json_encode($arr));
		}else if($_REQUEST['type'] == 'jian'){
			$find = M('cart')->where('goods_id = '.$_REQUEST['goods_id'].' and user_id = '.getUserId())->find();
			if($find['number'] > 1){
				M('cart')->where('id = '.$find['id'])->setDec('number',$_REQUEST['number']);
				$number = $find['number'] - $_REQUEST['number'];
			}else{
				M('cart')->where('id = '.$find['id'])->delete();
				$number = 0;
			}
			$count = M('cart')->field('sum(number) as sum')->where('user_id = '.getUserId().' and store_id = '.$_REQUEST['store_id'])->find();
			$arr['count'] = !$count['sum'] ? 0 : $count['sum'];
			$arr['status'] = 'success';
			$arr['number'] = $number;
			$arr['price'] = $number * $goods['price'];
			die(json_encode($arr));
		}
	}
	
	public function clearCart(){
		M('cart')->where('user_id = '.getUserId().' and store_id = '.$_REQUEST['store_id'])->delete();
		$arr['status'] = 'success';
		die(json_encode($arr));
	}
	
	public function loadCart(){
		$cart = M('cart')->where('user_id = '.getUserId().' and store_id = '.$_REQUEST['store_id'])->select();
		$html = '';
		$price = 0;
		foreach($cart as $key => $value){
			$goods = M('store_category_goods')->where('id = '.$value['goods_id'])->find();
			$jiajia = M('store')->where('id = '.$value['store_id'])->getField('jiajia');
			if($jiajia > 0 && $goods['ziying'] == 0) $goods['price'] = $goods['price'] + $goods['price'] * $jiajia / 100;
			$html .= '
				<li goods_id="'.$value['goods_id'].'">
					<div class="cart_list_name">'.$goods['goods_name'].'</div>
					<div class="cart_list_price" goods_id="'.$value['goods_id'].'">￥'.number_format($goods['price'] * $value['number'],2).'</div>
					<div class="cart_list_action">
						<i class="jian" style="display:block" goods_id="'.$value['goods_id'].'"><img src="/Public/Home/images/jian.png"></i>
						<span class="num" style="display:block">'.$value['number'].'</span>
						<i class="jia" goods_id="'.$value['goods_id'].'"><img src="/Public/Home/images/jia.png"></i>
					</div>
					<div class="clear"></div>
				</li>
			';
			if($goods['ziying'] == 0) $price += $goods['price'] * $value['number'];
		}
		$data['html'] = $html;
		$data['price'] = $price;
		die(json_encode($data));
	}
	
	public function checkout(){
		$store = M('store')->where('id = '.$_REQUEST['store_id'])->find();
		if($_REQUEST['address_id']){
			$address = M('users_address')->where('id = '.$_REQUEST['address_id'])->find();
		}else{
			$address = M('users_address')->where('user_id = '.getUserId())->find();
		}
		$this->assign('pei_time',getTimes());
		$this->assign('cart',getCart($store['id']));
		$this->assign('address',$address);
		$this->assign('store',$store);
		$this->assign('user',M('users')->where('id = '.getUserId())->find());
		$this->assign('order_sn',getOrderSn());
		$this->display();
	}
	
	public function checkoutCart(){
		$json = array(
			'html' => getCart($_REQUEST['store_id'],$_REQUEST['pei_type'])
		);
		die(json_encode($json));
	}
	
	public function checkCart(){
		$is_close = isClose($_REQUEST['store_id']);
		if($is_close == 1) die(json_encode(array('status' => 'error','msg' => '店铺已休息！')));

		$cartCount = M('cart')->where('store_id = '.$_REQUEST['store_id'].' and user_id = '.getUserId())->count();
		if($cartCount <= 0) die(json_encode(array('status' => 'error','msg' => '购物车不能为空！')));

		/*
		*	写入订单
		*/
		$address = getAddress($_REQUEST['address_id']);
		$orderData = array(
			'order_sn' => $_REQUEST['order_sn'],
			'type' => 3,
			'status' => 1,
			'store_id' => $_REQUEST['store_id'],
			'school_id' => session('school_id'),
			'user_id' => getUserId(),
			'add_time' => time(),
			'pei_type' => 1,
			'note' => $_REQUEST['note'],
			'dashang_price' => $_REQUEST['dashang_price'],
			'total_price' => $_REQUEST['total_price'],
			'pay_type' => $_REQUEST['pay_type'],
			'goods_price_real' => $_REQUEST['goods_price_real'],
			'goods_price_up' => $_REQUEST['goods_price_up'],
			'goods_price_ziying' => $_REQUEST['goods_price_ziying'],
			'name' => $address['name'],
			'telephone' => $address['telephone'],
			'address' => $address['address'],
			'store_name' => M('store')->where('id = '.$_REQUEST['store_id'])->getField('store_name'),
			'delivery_time' => ($_REQUEST['pei_time'] == 0 ? 0 : strtotime($_REQUEST['pei_time'])),
			'order_time' => getOrderTime($_REQUEST['store_id']),
			'discounts_price' => $_REQUEST['discounts_price']
		);
		$order_id = M('order')->add($orderData);

		/*
		*	清空购物车，写入商品表
		*/
		$cart = M('cart')->field('id,goods_id,number')->where('store_id = '.$_REQUEST['store_id'].' and user_id = '.getUserId())->select();
		foreach($cart as $key => $value){
			$activity = checkActivity($value['goods_id'],$_REQUEST['store_id']);
			$row = M('store_category_goods')->field('price,goods_name,ziying')->where('id = '.$value['goods_id'])->find();
			$param = array(
				'order_id' => $order_id,
				'goods_id' => $value['goods_id'],
				'goods_price' => $row['price'],
				'goods_name' => $row['goods_name'],
				'goods_number' => $value['number'],
				'goods_attr' => '',
				'ziying' => $row['ziying'],
				'type' => $activity == false ? 0 : $activity['type'],
				'data' => $activity == false ? '' : $activity['data']
			);
			M('order_goods')->add($param);
			M('cart')->where('id = '.$value['id'])->delete();
		}

		setOrderStatus($order_id,'订单已确认');
		$json = getOrder($order_id.'_'.time(),$orderData['total_price']);
		// $json = getOrder($order_id.'_'.time(),0.01);
		$json = json_decode($json,true);
		die(json_encode(array('status' => 'success','json' => $json)));
	}

	public function weixinNotifyUrl(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		$postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
		$data = json_decode(json_encode($postObj),true);
		if($postObj->return_code == 'SUCCESS' && $data['sign'] == sign($data)){
			$out_trade_no = explode('_',$postObj->out_trade_no);
			$order_id = $out_trade_no[0];

			/*
			*	如果订单支付完成则中断
			*/
			$pay_status = M('order')->where('id = '.$order_id)->getField('pay_status');
			if($pay_status == 1) die('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');

			$order = M('order')->field('total_price,discounts_price,dashang_price,goods_price_ziying,goods_price_up,store_id,goods_price_real,name,order_sn,telephone,address,user_id')->where('id = '.$order_id)->find();
			$store = M('store')->field('id,is_koufei,koufei_price,clientid,is_daozhang')->where('id = '.$order['store_id'])->find();
			$user = M('users')->field('openid')->where('id = '.$order['user_id'])->find();
			$order_goods = M('order_goods')->field('goods_id,goods_number')->where('order_id = '.$order_id)->select();
			$order_index = M('order')->where('datediff(FROM_UNIXTIME(pay_time,\'%Y-%m-%d %H%:%i:%s\'),now()) = 0 and pay_status = 1 and store_id = '.$store['id'])->count();
			$price = $order['total_price'] - $order['dashang_price'];

			/*
			*	更新订单付款状态
			*/
			M('order')->where('id = '.$order_id)->save(array('pay_status' => 1,'pay_time' => time(),'index' => ($order_index + 1)));
			setOrderStatus($order_id,'订单已付款');

			/*
			*	商家金额到账
			*/
			if($store['is_daozhang'] == 0){
				M('store')->where('id = '.$store['id'])->setInc('price',$price);
				storePriceLog($store['id'],$price,0,'商品金额',$order_id);
			}

			/*
			*	订单扣费
			*/
			if($store['is_koufei'] == 0 && !empty($store['koufei_price'])) storeKoufei($store['id'],$order_id,$store['koufei_price'],$price);

			/*
			*	商品添加销量
			*/
			foreach($order_goods as $key => $value){
				M('store_category_goods')->where('id = '.$value['goods_id'])->setInc('sale',$value['goods_number']);
			}

			/*
			*	店铺添加销量
			*/
			M('store')->where('id = '.$store['id'])->setInc('sale',1);

			/*
			*	用户微信推送
			*/
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
					'value' => '餐饮',
					'color' => '#333'
				),
				'customerInfo' => array(
					'value' => $order['name'],
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
					'value' => '订单状态：订单已付款\n联系方式：'.$order['telephone'].'\n取件地址：'.$order['address'],
					'color' => '#333'
				)
			);
			$message_info = array(
				'name' => $order['name'],
				'telephone' => $order['telephone'],
				'address' => $order['address']
			);
			$weixin = new \Think\WeiXinTemplate();
			$weixin->send($user['openid'],$msg,$order_id);

			/*
			*	商户推送
			*/
			Vendor('Push.Push');
			if(!empty($store['clientid'])){
				$_config = array(
					'title' => '爱超商家端',
					'content' => '您有新的订单，请及时处理。'
				);
				$push = new \Push($store['clientid'],$_config);
				$push->pushMessageToSingle();
			}

			/*
			*	终止微信推送
			*/
			die('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
		}
	}

	public function chongzhi(){
		if(empty(session('openid'))) $weixin = new \Think\WeiXin('http://sxjx.smdouyou.com/index.php/Home/Store/chongzhi.html');
		// die(session('openid'));
		$this->display();
	}

	public function chongzhi_search(){
		$rs = M('store')->where('username = "'.$_REQUEST['username'].'" and password = "'.md5($_REQUEST['password']).'"')->find();
		if($rs) die(json_encode($rs));
		if(!$rs) die(json_encode(array('status' => 'error')));
	}

	public function chongzhi_pay(){
		$notify_url = 'http://sxjx.smdouyou.com/index.php/Home/Store/chongzhiPayNotifyUrl.html';
		$json = getOrder('C_'.$_REQUEST['store_id'].'_'.time(),$_REQUEST['account'],$notify_url);
		$json = json_decode($json,true);
		die(json_encode(array('status' => 'success','json' => $json)));
	}

	public function chongzhiPayNotifyUrl(){
		$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
		$postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
		if($postObj->return_code == 'SUCCESS'){
			$out_trade_no = explode('_',$postObj->out_trade_no);
			$store_id = $out_trade_no[1];
			$price = $postObj->total_fee / 100;
			M('store')->where('id = '.$store_id)->setInc('price',$price);
			storePriceLog($store_id,$price,0,'充值');
		}

		/*
		*	终止微信推送
		*/
		die('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
	}
	
}