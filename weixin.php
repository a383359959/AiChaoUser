<?php

	header('Content-type: text/html; charset=utf-8');

	error_reporting(0);
	
	$conn = mysqli_connect('localhost','root','A383359959a','sxjx');
	mysqli_query($conn,'SET NAMES UTF8');
	file_put_contents('1.txt','asd');
	
	$postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
	
	if($postObj->return_code == 'SUCCESS'){
		$out_trade_no = explode('_',$postObj->out_trade_no);
		$sql = 'UPDATE `ythink_order` SET pay_status = 1,pay_time = "'.time().'" WHERE id = '.$out_trade_no[0];
		$query = mysqli_query($conn,$sql);
		
		$sql = 'INSERT INTO `ythink_order_msg` SET msg = "订单已付款",time = '.time().',order_id = '.$out_trade_no[0];
		$query = mysqli_query($conn,$sql);
		
		// 微信推送
		$path = dirname(__FILE__).'/ThinkPHP/Library/Think/WeiXinTemplate.class.php';
		include($path);
		
		$sql = 'SELECT * FROM `ythink_order` WHERE id = '.$out_trade_no[0];
		$query = mysqli_query($conn,$sql);
		$order = mysqli_fetch_assoc($query);
		
		$sql = 'SELECT * FROM `ythink_'.$order['table'].'` WHERE id = '.$order['foreign_key'];
		$query = mysqli_query($conn,$sql);
		$table = mysqli_fetch_assoc($query);
		
		$sql = 'SELECT * FROM `ythink_store` WHERE id = '.$order['store_id'];
		$query = mysqli_query($conn,$sql);
		$store = mysqli_fetch_assoc($query);
		
		/*
		*	改变账户余额
		*/
		if($store['category_id'] == 1 && $store['is_chongzhi'] == 0){	// 封闭式
			$sql = 'UPDATE `ythink_store` SET price = price - 1 WHERE id = '.$store['id'];
			mysqli_query($conn,'INSERT INTO `ythink_store_price_log` SET surplus_price = "'.($store['price'] - 1).'",store_id = '.$store['id'].',price = "1",add_time = '.time().',type = 1,order_id = '.$order['id']);
		}else if($store['category_id'] == 2){	// 开放式
			$p = $store['is_kou'] == 0 ? $order['pei_price_total'] - 1 : $order['pei_price_total'];
			$sql = 'UPDATE `ythink_store` SET price = price + '.$p.' WHERE id = '.$store['id'];
			mysqli_query($conn,'INSERT INTO `ythink_store_price_log` SET surplus_price = "'.($store['price'] + $p).'",store_id = '.$store['id'].',price = "'.$p.'",add_time = '.time().',type = 0,order_id = '.$order['id']);
		}
		
		$query = mysqli_query($conn,$sql);
		
		/*
		*	增加销量
		*/
		$sql = 'UPDATE `ythink_store` SET sale = sale + 1 WHERE id = '.$store['id'];
		$query = mysqli_query($conn,$sql);
		
		$sql = 'SELECT name,school_id FROM `ythink_school_address` WHERE id = '.$order['school_address_id'];
		$query = mysqli_query($conn,$sql);
		$school_address = mysqli_fetch_assoc($query);
		$sql = 'SELECT name FROM `ythink_school` WHERE id = '.$school_address['school_id'];
		$query = mysqli_query($conn,$sql);
		$school = mysqli_fetch_assoc($query);
		$address = $school['name'].' '.$school_address['name'].' ';
		$message_info = array();
		if($order['type'] == 1){
			$address .= $table['consignee_address'];
			$msg = array(
				'first' => array(
					'value' => '您有新的订单消息',
					'color' => '#333'
				),
				'tradeDateTime' => array(
					'value' => date('Y-m-d H:i:s',$order['pay_time']),
					'color' => '#333'
				),
				'orderType' => array(
					'value' => '洗衣',
					'color' => '#333'
				),
				'customerInfo' => array(
					'value' => $table['consignee_name'],
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
					'value' => '订单状态：订单已付款\n联系方式：'.$table['consignee_telephone'].'\n收货地址：'.$address,
					'color' => '#333'
				)
			);
			$message_info = array(
				'name' => $table['consignee_name'],
				'telephone' => $table['consignee_telephone'],
				'address' => $address
			);
		}else if($order['type'] == 2){
			$address .= $table['address'];
			$msg = array(
				'first' => array(
					'value' => '您有新的订单消息',
					'color' => '#333'
				),
				'tradeDateTime' => array(
					'value' => date('Y-m-d H:i:s',$order['pay_time']),
					'color' => '#333'
				),
				'orderType' => array(
					'value' => ($table['type'] == 1 ? '取快递' : '发快递'),
					'color' => '#333'
				),
				'customerInfo' => array(
					'value' => ($table['type'] == 1 ? $table['name'] : $table['sender_name']),
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
					'value' => '订单状态：订单已付款\n联系方式：'.($table['type'] == 1 ? $table['telephone'] : $table['sender_telephone']).'\n取件地址：'.$address,
					'color' => '#333'
				)
			);
			$message_info = array(
				'name' => ($table['type'] == 1 ? $table['name'] : $table['sender_name']),
				'telephone' => ($table['type'] == 1 ? $table['telephone'] : $table['sender_telephone']),
				'address' => $address
			);
		}else{
			$sql = 'SELECT * FROM `ythink_users_address` WHERE id = '.$table['users_address_id'];
			$query = mysqli_query($conn,$sql);
			$users_address = mysqli_fetch_assoc($query);
			$address .= $users_address['address'];
			$msg = array(
				'first' => array(
					'value' => '您有新的订单消息',
					'color' => '#333'
				),
				'tradeDateTime' => array(
					'value' => date('Y-m-d H:i:s',$order['pay_time']),
					'color' => '#333'
				),
				'orderType' => array(
					'value' => ($order['type'] == 3 ? '餐饮' : '商超'),
					'color' => '#333'
				),
				'customerInfo' => array(
					'value' => $users_address['name'],
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
					'value' => '订单状态：订单已付款\n联系方式：'.$users_address['telephone'].'\n取件地址：'.$address,
					'color' => '#333'
				)
			);
			$message_info = array(
				'name' => $users_address['name'],
				'telephone' => $users_address['telephone'],
				'address' => $address
			);
		}
		
		$weixin = new \Think\WeiXinTemplate();
		$weixin->send((string)$postObj->openid,$msg,$out_trade_no[0]);
	
		
		/*
		*	对商家进行推送
		*/
		$sql = 'SELECT * FROM `ythink_store_login_locking` WHERE `status` = 1 and user_id = '.$store['user_id'];
		
		$query = mysqli_query($conn,$sql);
		$store_user = mysqli_fetch_assoc($query);
		
		$message = '';
		if($order['type'] == 1){
			$message = '洗衣';
		}else if($order['type'] == 2){
			$message = '快递';
		}else if($order['type'] == 3){
			$message = '餐饮'.($table['pei_time'] == 1 ? '' : '【预约】');
		}else{
			$message = '商超'.($table['pei_time'] == 1 ? '' : '【预约】');
		}
		$msg = array(
			'first' => array(
				'value' => '您有新的订单消息',
				'color' => '#333'
			),
			'tradeDateTime' => array(
				'value' => date('Y-m-d H:i:s',$order['pay_time']),
				'color' => '#333'
			),
			'orderType' => array(
				'value' => $message,
				'color' => '#333'
			),
			'customerInfo' => array(
				'value' => $message_info['name'],
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
				'value' => '备注：'.$table['note'].'\n订单状态：买家订单已付款，请确认订单\n联系方式：'.$message_info['telephone'].'\n取件地址：'.$message_info['address'],
				'color' => '#333'
			)
		);
		
		$weixin = new \Think\WeiXinTemplate(1);
		$weixin->send($store_user['open_id'],$msg,$out_trade_no[0]);
		die('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
	}
	