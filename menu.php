<?php

$appid = 'wxc2a93035c13274f0';
$appsecret = '11592ee02e79879a36d3e9f50593e431';
	
	$result = file_get_contents('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret);
	$result = json_decode($result,true);
	
	$url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$result['access_token'];
	
	$data = '
			{
				"button" : [
					{
						"type" : "view",
						"name" : "我要订餐",
						"url" : "http://sxjx.smdouyou.com/"
					},
					{
						"type" : "view",
						"name" : "商户充值",
						"url" : "http://sxjx.smdouyou.com/index.php/Home/Store/chongzhi.html"
					}
				]
			}
	';
	
	$result = sendRequest($url,$data);
	print_r($result);
	
	function sendRequest($url,$data){
		$options = array(
			'http' => array(
				'method' => 'POST',
				'content' => $data,
				'timeout' => 15 * 60,
				'header' => 'Content-type:application/json;encoding=utf-8'
			)
		);
		$context = stream_context_create($options);
		$result = file_get_contents($url,false,$context);
		return $result;
	}