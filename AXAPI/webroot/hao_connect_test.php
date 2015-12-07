<?php

	require_once(__dir__.'/'.'../lib/HaoConnect/HaoConnect.php');

	$tmpResult = HaoConnect::get('user/get_my_detail');

	var_export($tmpResult);


	$tmpResult = HaoConnect::post('user/login',array('account'=>'13774298448','password'=>md5('123456')));

	var_export($tmpResult);
