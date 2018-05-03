<?php

function checkSchool(){
	if(session('school_id') == '' && ACTION_NAME != 'school'){
		header('location:'.U('Index/school'));
		exit;
	}
}

/*
*	是否有活动
*/
function checkActivity($goods_id,$store_id){
	$result = false;
	$list = M('store_activity')->field('type,data')->where('('.time().' >= begin_time and '.time().' <= end_time) and store_id = '.$store_id)->select();
	foreach($list as $key => $value){
		if($value['type'] == 4){
			$ids = explode(',',$value['data']);
			foreach($ids as $k => $v){
				$v = explode('-',$v);
				if($v[0] == $goods_id){
					$result = array('type' => $value['type'],'data' => $value['data']);
					break;
				}
			}
		}else{
			$ids = explode(',',$value['data']);
			if(in_array($goods_id,$ids)){
				$result = array('type' => $value['type'],'data' => $value['data']);
				break;
			}
		}
	}
	return $result;
}

function goodsList($category){
	if(isset($category['data'])){
		if($category['type'] == 4){
			$ids = array();
			$data = explode(',',$category['data']);
			foreach($data as $key => $value){
				$value = explode('-',$value);
				$ids[] = $value[0];
			}
			$ids = implode(',',$ids);
		}else{
			$ids = $category['data'];
		}
		$list = M('store_category_goods')->where('id in ('.$ids.')')->select();
	}else{
		$list = M('store_category_goods')->where('category_id = '.$category['id'])->select();
	}
	$html = '';
	foreach($list as $key => $value){
		$find = M('cart')->where('user_id = '.getUserId().' and goods_id = '.$value['id'])->find();
		$display = $find ? 'style="display:block"' : '';	
		
		/*
		*	计算加价
		*	自营产品不加价
		*/
		$jiajia = M('store')->where('id = '.$value['store_id'])->getField('jiajia');
		if($jiajia > 0 && $value['ziying'] == 0 && checkActivity($value['id'],$value['store_id']) == false){
			$value['price'] = number_format($value['price'] + $value['price'] * $jiajia / 100,2);
		}

		/*
		*	显示价格
		*/
		if(isset($category['data'])){
			switch($category['type']){
				case 4:
					$price_activity = getGoodsDataPrice($value['id'],$category['data']);
					$price = '<span>现价：'.number_format($price_activity,2).'&nbsp;&nbsp;<font style="color:#666;font-size:12px;text-decoration:line-through;">原价：￥'.$value['price'].'</font></span>';
					break;
				default:
					$price = '<span>￥'.$value['price'].'</span>';
			}
		}else{
			$price = '<span>￥'.$value['price'].'</span>';
		}

		$litpic = $value['litpic'] == '' ? '<img src="/Public/Home/images/store_logo_null.png" class="litpic" />' : '<img src="/Public/Home/images/store_logo_null.png" data-original="http://static.upload.smdouyou.com/'.$value['litpic'].'" class="litpic" />';
		$html .= '
			<li>
				'.$litpic.'
				<div class="store_content_list_content">
					<h2>'.$value['goods_name'].'</h2>
					<p>销售&nbsp;'.$value['sale'].'&nbsp;份</p>
					'.$price.'
				</div>
				<div class="guige" '.($value['status'] == 1 ? 'style="display:block;"' : 'style="display:none;"').'>已售罄</div>
				<div class="store_content_list_action" '.($value['status'] == 1 ? 'style="display:none;"' : 'style="display:block;"').'>
					<i class="jian" '.$display.' goods_id="'.$value['id'].'"><img src="/Public/Home/images/jian.png" /></i>
					<span class="num" '.$display.' goods_id="'.$value['id'].'">'.$find['number'].'</span>
					<i class="jia" goods_id="'.$value['id'].'"><img src="/Public/Home/images/jia.png" /></i>
				</div>
			</li>
		';
	}
	return $html;
}

function getGoodsDataPrice($goods_id,$arr){
	$arr = explode(',',$arr);
	$result = 0;
	foreach($arr as $k => $v){
		$v = explode('-',$v);
		if($v[0] == $goods_id) $result = $v[1];
	}
	return $result;
}

function getUserId(){
	$find = M('users')->field('id')->where('openid = "'.session('openid').'"')->find();
	return $find['id'];
}

function getTimes(){
	$begin_time = strtotime(date('Y-m-d 7:00'));
	$end_time = strtotime(date('Y-m-d 21:00'));
	$step = ($end_time - $begin_time) / (60 * 30);
	$html = '<option value="1">尽快送达</option>';
	
	for($i = 0;$i < $step;$i++){
		if($begin_time - time() > 60 * 60) $html .= '<option value="'.date('H:i',$begin_time).'">'.date('H:i',$begin_time).'</option>';
		$begin_time += (60 * 30);
	}
	$html .= '<option value="'.date('H:i',$end_time).'">'.date('H:i',$end_time).'</option>';
	return $html;
}

function getActivityPrice($store,$goods_id,$goods_number,$goods_price,$jiajia){
	$goods = M('store_category_goods')->field('price')->where('id = '.$goods_id)->find();
	$list = M('store_activity')->field('type,data')->where('('.time().' >= begin_time and '.time().' <= end_time) and store_id = '.$store['id'])->select();
	$price = 0;
	$goods_price_up = 0;
	$goods_price_real = 0;
	$discounts_price = 0;
	foreach($list as $key => $value){
		if($value['type'] == 4){
			$ids = explode(',',$value['data']);
			foreach($ids as $k => $v){
				$v = explode('-',$v);
				if($v[0] == $goods_id){
					$price = number_format($goods_number * $v[1],2);
					$goods_price_real = $price;
					$discounts_price = ($goods['price'] - $v[1]) * $goods_number;
					break;
				}
			}
		}else{
			$ids = explode(',',$value['data']);
			if(in_array($goods_id,$ids)){
				if($value['type'] == 2){	// 第二份半价
					if($goods_number >= 2){
						$price = number_format(($goods_number * $goods_price) - ($goods_price / 2),2);
						$goods_price_real = $price;
						$discounts_price = $goods_price / 2;
					}
				}else if($value['type'] == 3){	// 买一送一
					if($goods_number >= 2){
						$price = number_format($goods_number * $goods_price - $goods_price,2);
						$goods_price_real = $price;
						$discounts_price = $goods_price;
					}
				}
				break;
			}
		}
	}
	if($price <= 0){
		$price = $goods_number * $goods_price;
		if($jiajia > 0){
			$goods_price_up = $goods_number * $goods['price'] * $jiajia / 100;
			$goods_price_real = $goods_number * $goods['price'];
		}else{
			$goods_price_real = $goods_number * $goods['price'];
		}
	}
	return array('price' => number_format($price,2),'goods_price_up' => number_format($goods_price_up,2),'goods_price_real' => number_format($goods_price_real,2),'discounts_price' => number_format($discounts_price,2));
}

function getCart($store_id,$pei_type = ''){
	$total_price = 0;
	$goods_price_up = 0;
	$goods_price_real = 0;
	$discounts_price = 0;
	$goods_price_ziying = 0;
	$tr = '
		<tr>
			<td>商品名称</td>
			<td align="right" width="25%">数量</td>
			<td align="right" width="25%">价格</td>
		</tr>
	';
	$store = M('store')->where('id = '.$store_id)->find();
	$list = M('cart')->where('store_id = '.$store_id.' and user_id = '.getUserId())->select();
	foreach($list as $key => $value){
		$goods = M('store_category_goods')->where('id = '.$value['goods_id'])->find();
		
		/*
		*	商品价格加价
		*/
		$jiajia = 0;
		if($store['jiajia'] > 0 && $goods['ziying'] == 0 && checkActivity($goods['id'],$goods['store_id']) == false){
			$jiajia = $store['jiajia'];
			$goods['price'] += number_format($goods['price'] * $store['jiajia'] / 100,2);
		}
		$goods_price = getActivityPrice($store,$value['goods_id'],$value['number'],$goods['price'],$jiajia);
		$total_price += $goods_price['price'];
		$goods_price_up += $goods_price['goods_price_up'];
		$goods_price_real += $goods_price['goods_price_real'];
		$discounts_price += $goods_price['discounts_price'];
		if($goods['ziying'] == 1) $goods_price_ziying += $goods_price['price'];
		$tr .= '
			<tr>
				<td>'.$goods['goods_name'].'</td>
				<td align="right" width="25%">x'.$value['number'].'</td>
				<td align="right" width="25%">￥'.($goods['price'] * $value['number']).'</td>
			</tr>
		';
	}

	/*
	*	满多少减多少
	*/
	$store_activity = M('store_activity')->field('data')->where('type = 1 and store_id = '.$store_id)->find();
	if($store_activity){
		$priceArr = array(0,0);
		$data = explode(',',$store_activity['data']);
		foreach($data as $key => $value){
			$value = explode('-',$value);
			if($total_price >= $value[0] && $value[0] > $priceArr[0]) $priceArr = $value;
		}
		$discounts_price = $discounts_price + $priceArr[1];
		$total_price = $total_price - $priceArr[1];
	}

	/*
	*	打赏费
	*/
	if($store['dashang_price'] > 0){
		if($total_price > 15){
			$num = ceil(($total_price - 15) / 15);
			$store['dashang_price'] += ($num * 1);
		}
		$total_price += $store['dashang_price'];
		$tr .= '
			<tr>
				<td>打赏费</td>
				<td align="right" width="25%"></td>
				<td align="right" width="25%" class="dashang_price_main">￥'.number_format($store['dashang_price'],2).'</td>
			</tr>
		';
	}
	
	if($discounts_price > 0){
		$tr .= '
			<tr>
				<td style="color:red;">优惠立减</td>
				<td align="right" width="25%"></td>
				<td align="right" width="25%" style="color:red;">￥'.number_format($discounts_price,2).'</td>
			</tr>
		';
	}
	
	$html .= '
		<!-- 打赏金额 -->
		<input type="hidden" name="dashang_price" value="'.$store['dashang_price'].'" />

		<!-- 商品上浮价格 -->
		<input type="hidden" name="goods_price_up" value="'.$goods_price_up.'" />

		<!-- 商品真实价格 -->
		<input type="hidden" name="goods_price_real" value="'.$goods_price_real.'" />

		<!-- 自营商品价格 -->
		<input type="hidden" name="goods_price_ziying" value="'.$goods_price_ziying.'" />

		<!-- 优惠价格 -->
		<input type="hidden" name="discounts_price" value="'.$discounts_price.'" />

		<!-- 总价 -->
		<input type="hidden" name="total_price" value="'.$total_price.'" />
		
		<table width="100%" cellpadding="0" cellspacing="0">
			'.$tr.'
			<tr>
				<td>合计</td>
				<td align="right" width="25%"></td>
				<td align="right" width="25%">￥<span class="count">'.number_format($total_price,2).'</span></td>
			</tr>
		</table>
	';
	return $html;
}

function getOrderGoods($order){
	$order_goods = M('order_goods')->where('order_id = '.$order['id'])->select();
	$tr = '';
	foreach($order_goods as $key => $value){
		$tr .= '
			<tr height="40">
				<td>'.$value['goods_name'].'</td>
				<td width="25%" align="right">x'.$value['goods_number'].'</td>
				<td width="25%" align="right">￥'.number_format($value['goods_price'] * $value['goods_number'],2).'</td>
			</tr>
		';
	}
	$tr .= '
		<tr height="40">
			<td>打赏费</td>
			<td align="right" width="25%"></td>
			<td align="right" width="25%">￥'.$order['dashang_price'].'</td>
		</tr>
	';
	if($order['discounts_price'] > 0){
		$tr .= '
			<tr height="40">
				<td style="color:red;">优惠立减</td>
				<td align="right" width="25%"></td>
				<td align="right" width="25%" style="color:red;">￥'.number_format($order['discounts_price'],2).'</td>
			</tr>
		';
	}
	$tr .= '
		<tr height="40">
			<td>合计</td>
			<td align="right" width="25%"></td>
			<td align="right" width="25%">￥'.$order['total_price'].'</td>
		</tr>
	';
	$html = '<table width="100%" cellpadding="0" cellspacing="0">'.$tr.'</table>';
	return $html;
}

function getOrderSn(){
	$yCode = array('AI', 'BI', 'CI', 'DI', 'EI', 'FI', 'GI', 'HI', 'II', 'JI', 'KI', 'LI', 'MI', 'NI', 'OI', 'PI', 'QI', 'RI', 'SI', 'TI', 'UI', 'VI', 'WI', 'XI', 'YI', 'ZI', 'AS', 'BS', 'CS', 'DS', 'ES', 'FS', 'GS', 'HS', 'IS', 'JS', 'KS', 'LS', 'MS', 'NS', 'OS', 'PS', 'QS', 'RS', 'SS', 'TS', 'US', 'VS', 'WS', 'XS', 'YS', 'ZS', 'AT', 'BT', 'CT', 'DT', 'ET', 'FT', 'GT', 'HT', 'IT', 'ZT', 'KT', 'LT', 'MT', 'NT', 'OT', 'PT', 'QT', 'RT', 'ST', 'TT', 'TU');
	$orderSn = $yCode[intval(date('s'))] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(10000, 99999));
	return $orderSn;
}

function getOrder($order_sn,$price,$notify_url = 'http://sxjx.smdouyou.com/index.php/Home/Store/weixinNotifyUrl.html'){
	$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	$arr = array(
		'appid' => C('APPID'),
		'mch_id' => C('MCHID'),
		'nonce_str' => getNonceStr(),
		'body' => '购买',
		'out_trade_no' => $order_sn,
		'total_fee' => $price * 100,
		'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
		'notify_url' => $notify_url,
		'trade_type' => 'JSAPI',
		'openid' => session('openid')
	);
	$arr['sign'] = sign($arr);
	$data = ToXml($arr);
	$result = postData($url,$data);
	$options = array(
		'appId' => C('APPID'),
		'timeStamp' => (string)time(),
		'nonceStr' => getNonceStr(),
		'package' => 'prepay_id='.$result['prepay_id'],
		'signType' => 'MD5',
	);
	$options['paySign'] = sign($options);
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

function setOrderStatus($order_id,$msg){
	$data['order_id'] = $order_id;
	$data['msg'] = $msg;
	$data['time'] = time();
	return M('order_msg')->add($data);
}

function checkActivityStore($activity_id){
	$find = M('store_category_goods')->field('store_id')->where('activity_id = '.$activity_id)->find();
	return ($find ? true : false);
}

function activityStoreList($activity_id){
	$list = M('store_activity')->field('id,store_id')->where('status = 2 and activity_id = '.$activity_id)->select();
	if(empty($list)){
		return false;
	}else{
		$store_ids = array();
		foreach($list as $key => $value){
			$check = checkActivityStore($value['id']);
			if($check === true) $store_ids[] = $value['store_id'];
		}
		return implode(',',$store_ids);
	}
}

function getTimePeriod($time_period){
	$is_close = 1;
	$time_period = explode(',',$time_period);
	foreach($time_period as $key => $value){
		$times = explode('-',$value);
		$begin_time = strtotime(date('Y-m-d').' '.$times[0]);
		$end_time = strtotime(date('Y-m-d').' '.$times[1]);
		if(time() > $begin_time && time() < $end_time){
			$is_close = 0;
			break;
		}
	}
	return $is_close;
}

function isClose($store_id){
	$is_close = 0;
	$school = M('school')->field('time_period')->where('id = '.session('school_id'))->find();
	if($school['time_period'] != '') $is_close = getTimePeriod($school['time_period']);
	// dump($is_close);
	if($is_close == 1) return $is_close;
	$store = M('store')->field('business_status,school_id,time_period,price,is_chongzhi,is_hezuo')->where('id = '.$store_id)->find();
	// if($store['price'] <= 0 && $store['is_chongzhi'] == 0) return 1;	// 如果需要充值，而且余额不足，则关闭店铺
	if($store['is_hezuo'] == 1) return 1;	// 如果店铺不是合作商家，始终关闭
	if($store['business_status'] == 1 || $store['business_status'] == 2) return 1;	// 不是营业中，全部关闭
	return getTimePeriod($store['time_period']);
}

/*
*	获取订单地址
*/
function getAddress($address_id){
	$arr = array();
	$info = M('users_address')->field('name,telephone,address,school_address_id,school_id')->where('id = '.$address_id)->find();
	if($info['school_id'] > 0) $arr[] = M('school')->where('id = '.$info['school_id'])->getField('name');
	if($info['school_address_id'] > 0) $arr[] = M('school_address')->where('id = '.$info['school_address_id'])->getField('name');
	$arr[] = $info['address'];
	$info['address'] = implode(' ',$arr);
	return $info;
}

function checkZiYing($category_id){
	$arr = array(
		'goods_count' => 0,
		'ziying_count' => 0
	);
	$list = M('store_category_goods')->where('category_id = '.$category_id)->select();
	foreach($list as $key => $value){
		if($value['ziying'] == 1){
			$arr['ziying_count']++;
		}else{
			$arr['goods_count']++;
		}
	}
	return $arr;
}

/*
*	第几次下单
*/
function getOrderTime($store_id){
	$count = M('order')->where('(`status` = 7 or `status` = 8 or `status` = 9) and store_id = '.$store_id)->count();
	return ($count + 1);
}

function getActivityHtml($store_id){
	$list = M('store_activity')->where('((begin_time = 0 and end_time = 0) or ('.time().' >= begin_time and '.time().' <= end_time)) and store_id = '.$store_id)->select();
	$html = '';
	foreach($list as $key => $value){
		if($value['type'] == 1){	// 满减
			$arr = array();
			$data = explode(',',$value['data']);
			foreach($data as $k => $v){
				$v = explode('-',$v);
				$arr[] = '满'.$v[0].'元，减'.$v[1].'元';
			}
			$html .= '
			<div class="activity_box">
				<div class="icon"><font>减</font></div>
				<div class="icon_name">'.implode('；',$arr).'</div>
				<div class="clear"></div>
			</div>
			';
		}else if($value['type'] == 2){	// 第二份半价
			$html .= '
			<div class="activity_box">
				<div class="icon" style="background:#f27273;"><font>半</font></div>
				<div class="icon_name">第二份半价</div>
				<div class="clear"></div>
			</div>
			';
		}else if($value['type'] == 3){	// 买一送一
			$html .= '
			<div class="activity_box">
				<div class="icon" style="background:#3cc790;"><font>送</font></div>
				<div class="icon_name">买一送一</div>
				<div class="clear"></div>
			</div>
			';
		}else if($value['type'] == 4){	// 限时抢购
			$html .= '
			<div class="activity_box">
				<div class="icon" style="background:#8b79e3;"><font>抢</font></div>
				<div class="icon_name">限时抢购</div>
				<div class="clear"></div>
			</div>
			';
		}
	}
	return $html;
}

/*
*	写入流水
*	注意：一定要写在钱变动后；
*/
function storePriceLog($store_id,$price,$type,$desc = '',$order_id = 0){
	$store = M('store')->field('price')->where('id = '.$store_id)->find();
	$data = array(
		'store_id' => $store_id,
		'add_time' => time(),
		'price' => $price,
		'order_id' => $order_id,
		'type' => $type,
		'surplus_price' => $store['price'],
		'desc' => $desc
	);
	M('store_price_log')->add($data);
}

/*
*	写入流量
*/
function storeFlowRate($store_id){
	$data = array(
		'user_id' => getUserId(),
		'store_id' => $store_id,
		'add_time' => time()
	);
	M('store_flow_rate')->add($data);
}

/*
*	获取活动分类
*/
function getActivityCategory($store_id){
	$list = M('store_activity')->field('type,data')->where('('.time().' >= begin_time and '.time().' <= end_time) and store_id = '.$store_id)->select();
	foreach($list as $key => $value){
		switch($value['type']){
			case 2:
				$name = '第二份半价';
				break;
			case 3:
				$name = '买一送一';
				break;
			case 4:
				$name = '限时抢购';
				break;
		}
		$value['name'] = $name;
		$list[$key] = $value;
	}
	return $list;
}

/*
*	订单扣费
*/
function storeKoufei($store_id,$order_id,$koufei_price,$total_price,$type = 0){
	if(strpos($koufei_price,'%') !== false){	// 按照百分比扣费
		$point = str_replace('%','',$koufei_price);
		$total_price = $total_price * $point / 100;
	}else{	// 一单扣指定金额
		$total_price = $koufei_price;
	}
	if($type == 0){
		M('store')->where('id = '.$store_id)->setDec('price',$total_price);
		storePriceLog($store_id,$total_price,1,'订单扣费',$order_id);
	}else{
		M('store')->where('id = '.$store_id)->setInc('price',$total_price);
		storePriceLog($store_id,$total_price,0,'订单扣费 - 退款',$order_id);
	}
}