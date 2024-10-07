<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2017 - 2020 by Kamil Wyremski
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

if ($admin->is_logged()) {

	if (!_ADMIN_TEST_MODE_ and isset($_POST['action'])) {
		if ($_POST['action'] == 'activate_newsletter' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('activate_newsletter')) {
			newsletter::activate($_POST['id']);
			$render_variables['alert_success'][] = trans('Email address has been activated');
		} elseif ($_POST['action'] == 'remove_newsletter' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('remove_newsletter')) {
			newsletter::remove($_POST['id']);
			if (isset($_POST['add_email_black_list']) and !empty($_POST['email'])) {
				settings::addEmailToBlackList($_POST['email']);
			}
			if (isset($_POST['add_ip_black_list']) and !empty($_POST['ip'])) {
				settings::addIpToBlackList($_POST['ip']);
			}
			$render_variables['alert_danger'][] = trans('User has been deleted');
		} elseif ($_POST['action'] == 'remove_newsletters' and isset($_POST['newsletters']) and is_array($_POST['newsletters']) and checkToken('admin_action_newsletter')) {
			foreach ($_POST['newsletters'] as $key => $value) {
				if ($value > 0) {
					newsletter::remove($value);
				}
			}
			$render_variables['alert_danger'][] = trans('User has been deleted');
		} elseif ($_POST['action'] == 'activate_newsletters' and isset($_POST['newsletters']) and is_array($_POST['newsletters']) and checkToken('admin_action_newsletter')) {
			foreach ($_POST['newsletters'] as $key => $value) {
				if ($value > 0) {
					newsletter::activate($value);
				}
			}
			$render_variables['alert_success'][] = trans('Email address has been activated');
		}
	}

	$render_variables['newsletter'] = newsletter::list(100);

	$title = trans('Newsletter') . ' - ' . $title_default;
}
