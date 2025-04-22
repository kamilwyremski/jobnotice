<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2025 by IT Works Better https://itworksbetter.net
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

if (!empty($_GET['slug'])) {
	throw new noFoundException();
}

$contact_page = info::showByName('contact');
$render_variables['contact_page'] = $contact_page;

if (isset($_POST['action']) and $_POST['action'] == 'send_message' and !empty($_POST['name']) and (!empty($_POST['email']) or $user->getId()) and !empty($_POST['message']) and (isset($_POST['captcha']) or isset($_POST['recaptcha_response'])) and (isset($_POST['rules']) or $user->getId())) {

	if (!settings::checkCaptcha($_POST)) {
		$error['captcha'] = trans('Invalid captcha code. Show thay you are not robot!');
	} elseif (!$user->getId() and !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$error['email'] = trans('Incorrect e-mail address');
	}
	if (settings::checkEmailBlackList($_POST['email']) or settings::checkIpBlackList(getClientIp())) {
		$error = true;
	}
	if (!empty($_FILES['attachment']['name']) and $_FILES['attachment']['size'] > $settings['attachment_max_size'] * 1024) {
		$error['attachment'] = trans('The attachment exceeds the allowed size') . ' ' . $settings['attachment_max_size'] . 'kB';
	}

	if (isset($error)) {
		$render_variables['error'] = $error;
		$render_variables['alert_danger'][] = trans('The message was not sent');
		$render_variables['input'] = ['name' => $_POST['name'], 'email' => $_POST['email'], 'message' => $_POST['message']];
	} else {
		if ($user->getId()) {
			$email = $user->email;
		} else {
			$email = $_POST['email'];
		}
		if (mail::send('contact_form', $settings['email'], ['name' => $_POST['name'], 'email' => $email, 'message' => strip_tags($_POST['message']), 'user_id' => $user->getId()])) {
			$render_variables['alert_success'][] = trans('The message was correctly sent');
		} else {
			$render_variables['alert_danger'][] = trans('The message was not sent');
		}
	}
}

$settings['seo_title'] = $contact_page['name'] . ' - ' . $settings['title'];
if ($contact_page['description']) {
	$settings['seo_description'] = $contact_page['description'];
} else {
	$settings['seo_description'] = $contact_page['name'] . ' - ' . $settings['description'];
}
if ($contact_page['keywords']) {
	$settings['seo_keywords'] = $contact_page['keywords'];
}
