<?php

namespace YThink;

class WeiXin{
	
	public $appid = 'wx45fae74da5a3cb9d';
	
	public $appsecret = 'a887c59a755cc58e78c85732ff7a54e7';
	
	public function __construct(){
		if(isset($_REQUEST['code'])){
			$access_token = $this->access_token();
			if($access_token){
				$info = $this->get_user_info($access_token);
				if($info){
					$_SESSION['user_info'] = $info;
					header('location:https://api.smdouyou.com/');
				}
			}else{
				die('token换取失败！');
			}
		}else{
			if(!empty($_SESSION['openid'])){
				
			}else{
				$this->auth();
			}
		}
	}
	
	/*
	*	授权
	*/
	public function auth(){
		$url = 'https://api.smdouyou.com/index.php';
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appid.'&redirect_uri='.urlencode($url).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
		header('location:'.$url);
	}
	
	/*
	*	access_token
	*/
	public function access_token(){
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$_REQUEST['code'].'&grant_type=authorization_code';
		$result = file_get_contents($url);
		$result = json_decode($result,true);
		if($result['access_token']){
			$_SESSION['openid'] = $result['openid'];
			$_SESSION['access_token'] = $result['access_token'];
			$_SESSION['expires_in'] = $result['expires_in'];
			$_SESSION['refresh_token'] = $result['refresh_token'];
			return $result['access_token'];
		}else{
			return false;
		}
	}
	
	/*
	*	获取用户信息
	*/
	public function get_user_info($access_token){
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$_SESSION['openid'].'&lang=zh_CN';
		$result = file_get_contents($url);
		$result = json_decode($result,true);
		return $result;
	}
	
	
	
///////////////////////////////////////////////////////
	
	//推送消息
	public function wexin_push($access_token){
		
		$url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
		// $result = file_get_contents($url);
		// $result = json_decode($result,true);
		// return $result;
		
		$dataArr = array(
				
		);
		
		$this->curlPost($url,$dataArr);
		
	}
	
    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    public function curlPost($url,$postFields){
        $postFields = http_build_query($postFields);
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        return $result;
    }

// touser	是	接收者openid

// template_id	是	模板ID

// url	否	模板跳转链接

// miniprogram	否

// 跳小程序所需数据，不需跳小程序可不用传该数据

// appid	是

// 所需跳转到的小程序appid（该小程序appid必须与发模板消息的公众号是绑定关联关系）

// pagepath	是

// 所需跳转到小程序的具体页面路径，支持带参数,（示例index?foo=bar）

// data	是	模板数据

// color	否	模板内容字体颜色，不填默认为黑色

	
}