<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2024 by IT Works Better https://itworksbetter.net
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

if (!isset($settings['base_url'])) {
	die('Access denied!');
}

if (isset($_POST['action']) and $_POST['action'] == 'login' and !empty($_POST['session_code']) and !empty($_POST['username']) and !empty($_POST['password'])) {

	try {
		$admin->login($_POST);
		header('Location: ' . $_SERVER['REQUEST_URI']);
		die('redirect');
	} catch (Exception $e) {
		$render_variables['alert_danger'][] = $e->getMessage();
	}
}

if (!$admin->is_logged()) {
	$render_variables['session_code'] = $admin->newSessionCode();
}
