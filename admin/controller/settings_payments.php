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

if(!isset($settings['base_url'])){
	die('Access denied!');
}

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action']=='save_settings_payments' and checkToken('admin_save_settings_payments')){

		settings::saveArrays(
			['currency','add_cost','promote_days','promote_cost','dotpay_id','dotpay_pin','dotpay_currency','paypal_email','paypal_lc','paypal_currency','p24_merchant_id','p24_pos_id','p24_crc','p24_currency'],
			['promote_only_by_author','pay_by_dotpay','dotpay_test_mode','pay_by_paypal','paypal_test_mode','pay_by_p24','p24_sandbox']
		);
		getSettings();
		$render_variables['alert_success'][] = trans('Changes have been saved');
	}

	$title = trans('Payment settings').' - '.$title_default;
}
