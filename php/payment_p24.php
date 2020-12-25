<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2021 by IT Works Better https://itworksbetter.net
 * Project by Kamil Wyremski https://wyremski.pl
 *
 * All right reserved
 *
 * *********************************************************************
 * THIS SOFTWARE IS LICENSED - YOU CAN MODIFY THESE FILES
 * BUT YOU CAN NOT REMOVE OF ORIGINAL COMMENTS!
 * ACCORDING TO THE LICENSE YOU CAN USE THE SCRIPT ON ONE DOMAIN. DETECTION
 * COPY SCRIPT WILL RESULT IN A HIGH FINANCIAL PENALTY AND WITHDRAWAL
 * LICENSE THE SCRIPT
 * *********************************************************************/

require_once('../config/config.php');

if(!$settings['pay_by_p24']){
	die();
}

if(isset($_POST['action']) and $_POST['action']=='new_payment' and isset($_POST['item_id']) and $_POST['item_id']>0 and !empty($_POST['type'])){

	$payment_data = payment::new('p24',$_POST['item_id'],$_POST['type']);
	if($payment_data){

		$md5sum = md5($payment_data['id']."|".$settings['p24_merchant_id']."|".($payment_data['amount']*100)."|".$settings['p24_currency']."|".$settings['p24_crc']);

		if($settings['p24_sandbox']){
			$form = '<form action="https://sandbox.przelewy24.pl/trnDirect" method="post" id="form">';
		}else{
			$form = '<form action="https://secure.przelewy24.pl/trnDirect" method="post" id="form">';
		}
		$form .='<input type="hidden" name="p24_sign" value="'.$md5sum.'" />
			<input type="hidden" name="p24_session_id" value="'.$payment_data['id'].'" />
			<input type="hidden" name="p24_merchant_id" value="'.$settings['p24_merchant_id'].'" />
			<input type="hidden" name="p24_pos_id" value="'.$settings['p24_pos_id'].'" />
			<input type="hidden" name="p24_amount" value="'.($payment_data['amount']*100).'" />
			<input type="hidden" name="p24_currency" value="'.$settings['p24_currency'].'" />
			<input type="hidden" name="p24_description" value="'.$payment_data['description'].'" />
			<input type="hidden" name="p24_country" value="PL" />
			<input type="hidden" name="p24_email" value="'.$payment_data['email'].'" />
			<input type="hidden" name="p24_url_return" value="'.$payment_data['url'].'&status=OK" />
			<input type="hidden" name="p24_url_status" value="'.$settings['base_url'].'/php/payment_p24.php" />
			<input type="hidden" name="p24_api_version" value="3.2" />
		</form>';
		$form .= '<script>document.getElementById("form").submit();</script>';
		echo($form);
	}

}elseif(($_SERVER['REMOTE_ADDR']=='91.216.191.181' or $_SERVER['REMOTE_ADDR']=='91.216.191.182' or $_SERVER['REMOTE_ADDR']=='91.216.191.183' or $_SERVER['REMOTE_ADDR']=='91.216.191.184' or $_SERVER['REMOTE_ADDR']=='91.216.191.185') && !empty($_POST)){

	$sth = $db->prepare('SELECT * FROM '._DB_PREFIX_.'payment WHERE id=:id AND status="new" LIMIT 1');
	$sth->bindValue(':id', $_POST['p24_session_id'], PDO::PARAM_STR);
	$sth->execute();
	$payment_p24 = $sth->fetch(PDO::FETCH_ASSOC);

	if($payment_p24){

		$arg = [
			'p24_merchant_id' => $settings['p24_merchant_id'],
			'p24_pos_id' => $settings['p24_pos_id'],
			'p24_session_id' => $payment_p24['id'],
			'p24_amount' => ($payment_p24['amount']*100),
			'p24_currency' => 'PLN',
			'p24_order_id' => $_POST['p24_order_id'],
			'p24_sign' => md5($payment_p24['id']."|".$_POST['p24_order_id']."|".($payment_p24['amount']*100)."|".$settings['p24_currency']."|".$settings['p24_crc'])
		];

		$req = array();
		foreach($arg as $k=>$v) $req[] = $k."=".urlencode($v);

		$ch = curl_init();
		if($settings['p24_sandbox']){
			curl_setopt($ch, CURLOPT_URL, "https://sandbox.przelewy24.pl/trnVerify");
		}else{
			curl_setopt($ch, CURLOPT_URL, "https://secure.przelewy24.pl/trnVerify");
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,join("&",$req));
		$result = curl_exec ($ch);
		$info = curl_getinfo($ch);
		curl_close ($ch);
		if($info["http_code"]==200) {
			$res     = array();
			$X       = explode("&", $result);
			foreach($X as $val) {
				$Y           = explode("=", $val);
				$res[trim($Y[0])] = urldecode(trim($Y[1]));
			}
			if(isset($res["error"]) and $res["error"] == 0){

				payment::check($payment_p24['id'],($_POST['p24_amount']/100),$_POST);

			}else{

				$_POST['errors'] = $res["errorMessage"];

				payment::check($payment_p24['id'],0,$_POST);

			}
		}
	}
}
