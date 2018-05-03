<?php

	header('Content-type: text/html; charset=utf-8');

	error_reporting(0);
	
	$conn = mysqli_connect('localhost','root','A383359959a','waimai');
	mysqli_query($conn,'SET NAMES UTF8');
	
	$success_details = explode('|',$_POST['success_details']);
	
	foreach($success_details as $key => $value){
		if(!empty($value)){
			$value = explode('^',$value);
			$sql = 'UPDATE `ythink_store_cash` SET `status` = 2 WHERE id = '.$value[0];
			$query = mysqli_query($conn,$sql);
		}
	}
	
	die('success');