<?php

namespace Home\Controller;

use Think\Controller;

class SupermarketController extends Controller{
	
    public function index(){
		$main = M('store')->where('school_id = '.session('school_id').' and type = 1')->find();
		
		
		
		
		$this->store_id = $main['id'];
		$this->assign('store_id',$this->store_id);
		$this->assign('checkout','/index.php/Home/Store/checkout/store_id/'.$this->store_id.'.html?table=order_supermarket');
		checkSchool();
		$store = M('store')->where('id = '.$this->store_id)->find();
		$begin_time = strtotime(date('Y-m-d').' '.$store['begin_time']);
		$end_time = strtotime(date('Y-m-d').' '.$store['end_time']);
		$store['is_close'] = time() > $begin_time && time() < $end_time ? 0 : 1;
		if($_REQUEST['debug'] == 'true') $store['is_close'] = 0;
		$cart = M('cart')->field('sum(number) as sum')->where('user_id = '.getUserId().' and store_id = '.$this->store_id)->find();
		$goods = M('store_category_goods')->where('store_id = '.$this->store_id.' and youhui != ""')->order('youhui asc')->select();
		$arr = array();
		foreach($goods as $key => $value){
			switch($value['youhui']){
				case 1:
					$arr[] = array(
						'id' => 'dierfenbanjia',
						'name' => '第二份半价',
						'store_id' => $this->store_id
					);
					break;
				case 2:
					$arr[] = array(
						'id' => 'maiyisongyi',
						'name' => '买一送一',
						'store_id' => $this->store_id
					);
					break;
				case 3:
					$arr[] = array(
						'id' => 'tiantiantejia',
						'name' => '天天特价',
						'store_id' => $this->store_id
					);
					break;
				case 4:
					$arr[] = array(
						'id' => 'xianshiqianggou',
						'name' => '限时抢购',
						'store_id' => $this->store_id
					);
					break;
			}
		}
		$category = M('store_category')->where('store_id = '.$this->store_id)->order('sort asc')->select();
		$this->assign('category',array_merge($arr,$category));
		$this->assign('store',$store);
		$this->assign('cart',!$cart['sum'] ? 0 : $cart['sum']);
		$this->display();
	}
	
	public function updateCart(){
		$goods = M('store_category_goods')->where('id = '.$_REQUEST['goods_id'])->find();
		if($_REQUEST['type'] == 'jia'){
			$find = M('cart')->where('goods_id = '.$_REQUEST['goods_id'].' and user_id = '.getUserId())->find();
			if($find){
				M('cart')->where('id = '.$find['id'])->setInc('number',$_REQUEST['number']);
				// echo M('cart')->_sql();
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
			$html .= '
				<li goods_id="'.$value['goods_id'].'">
					<div class="cart_list_name">'.$goods['goods_name'].'</div>
					<div class="cart_list_price" goods_id="'.$value['goods_id'].'">￥'.($goods['price'] * $value['number']).'</div>
					<div class="cart_list_action">
						<i class="jian" style="display:block" goods_id="'.$value['goods_id'].'"><img src="/Public/Home/images/jian.png"></i>
						<span class="num" style="display:block">'.$value['number'].'</span>
						<i class="jia" goods_id="'.$value['goods_id'].'"><img src="/Public/Home/images/jia.png"></i>
					</div>
					<div class="clear"></div>
				</li>
			';
			$price += $goods['price'] * $value['number'];
		}
		$data['html'] = $html;
		$data['price'] = $price;
		die(json_encode($data));
	}
	
	public function checkout(){
		$store = M('store')->where('id = '.$_REQUEST['store_id'])->find();
		$address = M('users_address')->where('user_id = '.getUserId())->find();
		$this->assign('pei_time',getTimes($store['begin_time'],$store['end_time'],$store['pei_time']));
		$this->assign('cart',getCart($store['id']));
		$this->assign('address',$address);
		$this->assign('store',$store);
		$this->display();
	}
	
	public function checkCart(){
		$find = M('cart')->where('store_id = '.$_REQUEST['store_id'].' and user_id = '.getUserId())->find();
		if($find){
			die('1');
		}else{
			die('0');
		}
	}
	
	public function pay(){
		$store = M('store')->where('id = '.$_REQUEST['store_id'])->find();
		$data['order_sn'] = date('YmdHis');
		$data['user_id'] = getUserId();
		$data['store_id'] = $_REQUEST['store_id'];
		$data['is_youhui'] = $_REQUEST['is_youhui'];
		$data['man'] = $_REQUEST['man'];
		$data['jian'] = $_REQUEST['jian'];
		$data['add_time'] = time();
		$data['msg_status'] = '订单已提交';
		$data['goods_price'] = $_REQUEST['goods_price'];
		$data['pei_price'] = $_REQUEST['pei_price'];
		$data['dabao_price'] = $_REQUEST['dabao_price'];
		$data['dashang_price'] = $_REQUEST['dashang_price'];
		$data['contact_name'] = $_REQUEST['contact_name'];
		$data['contact_telephone'] = $_REQUEST['contact_telephone'];
		$data['contact_address'] = $_REQUEST['contact_address'];
		$data['pei_type'] = $_REQUEST['pei_type'];
		$data['pei_time'] = $_REQUEST['pei_time'] == '尽快送达' ? date('H:i',(time() + 30 * 60)) : $_REQUEST['pei_time'];
		$data['pei_time_timestamp'] = strtotime(date('Y-m-d').' '.$data['pei_time']);
		$data['note'] = $_REQUEST['note'];
		$order_id = M('order')->add($data);
		M('store')->where('id = '.$_REQUEST['store_id'])->setInc('sale');
		$cart = M('cart')->where('store_id = '.$_REQUEST['store_id'].' and user_id = '.getUserId())->select();
		foreach($cart as $key => $value){
			$row = M('store_category_goods')->where('id = '.$value['goods_id'])->find();
			$param = array(
				'order_id' => $order_id,
				'goods_id' => $value['goods_id'],
				'goods_price' => $row['price'],
				'goods_name' => $row['goods_name'],
				'goods_number' => $value['number'],
				'goods_attr' => ''
			);
			M('order_goods')->add($param);
			M('cart')->where('id = '.$value['id'])->delete();
		}
		$price = $_REQUEST['goods_price'] + $_REQUEST['pei_price'] + $_REQUEST['dabao_price'] + $_REQUEST['dashang_price'];
		// 优惠满减
		if($_REQUEST['man'] != '' && $_REQUEST['jian'] != '' && $_REQUEST['is_youhui'] == 1) $price = getManJian($store['man'],$store['jian'],$price);
		$this->assign('wxpay',$this->getOrder($data['order_sn'],$price));
		$this->assign('price',$price);
		$this->assign('store',$store);
		$this->display();
	}
	
	function getOrder($order_sn,$price){
		$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
		$arr = array(
			'appid' => C('APPID'),
			'mch_id' => C('MCHID'),
			'nonce_str' => $this->getNonceStr(),
			'body' => '购买',
			'out_trade_no' => $order_sn,
			'total_fee' => $price * 100,
			'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
			'notify_url' => 'https://api.smdouyou.com/weixin.php',
			'trade_type' => 'JSAPI',
			'openid' => $_SESSION['openid']
		);
		$arr['sign'] = $this->sign($arr);
		$data = $this->ToXml($arr);		
		$result = $this->postData($url,$data);
		$options = array(
			'appId' => C('APPID'),
			'timeStamp' => (string)time(),
			'nonceStr' => $this->getNonceStr(),
			'package' => 'prepay_id='.$result['prepay_id'],
			'signType' => 'MD5',
		);
		$options['paySign'] = $this->sign($options);
		return json_encode($options);
	}

	function ToXml($arr){
		$xml = '<xml>';
		foreach ($arr as $key => $value){
			if(is_numeric($value)){
				$xml .= '<'.$key.'>'.$value.'</'.$key.'>';
			}else{
				$xml .= '<'.$key.'><![CDATA['.$value.']]></'.$key.'>';
			}
		}
		$xml .= '</xml>';
		return $xml; 
	}

	function getNonceStr(){
		$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';  
		$string = '';
		for ($i = 0;$i < 32;$i++ ){  
			$string .= substr($chars,mt_rand(0,strlen($chars) - 1),1);  
		}
		return $string;
	}

	function sign($arr){
		ksort($arr);
		$string = '';
		foreach ($arr as $key => $value){
			if($key != 'sign' && $value != '' && !is_array($value)) $string .= $key.'='.$value.'&';
		}
		$string = trim($string,'&');
		$string .= '&key='.C('KEY');
		$string = md5($string);
		$string = strtoupper($string);
		return $string;
	}

	function postData($url,$data){
		$options = array(
			'http' => array(
				'method' => 'POST',
				'content' => $data,
				'timeout' => 15 * 60,
				'header' => 'Content-type:application/xml;encoding=utf-8'
			)
		);
		$context = stream_context_create($options);
		$result = file_get_contents($url,false,$context);
		$result = json_decode(json_encode(simplexml_load_string($result,'SimpleXMLElement',LIBXML_NOCDATA)),true);
		return $result;
	}
	
}