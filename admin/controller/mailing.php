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

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='send_mailing' and !empty($_POST['type']) and !empty($_POST['subject']) and isset($_POST['message']) and checkToken('admin_send_mailing')){
			mail::prepareMailing($_POST);
		}elseif($_POST['action']=='cancel_mailing' and checkToken('admin_cancel_mailing')){
			mailQueue::cancelMailing();
		}
	}

	$mails_queue = mailQueue::countQueue();
	if($mails_queue){
		$render_variables['alert_danger'][] = trans('Warning! Mailing is in progress').'. '.trans('Mails in queue').': '.$mails_queue;
		$render_variables['mails_queue'] = $mails_queue;
	}
}
