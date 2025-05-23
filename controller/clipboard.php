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

if ($user->getId()) {

	if (isset($_POST['action']) and $_POST['action'] == 'clipboard_remove' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('clipboard_remove')) {
		clipboard::remove($_POST['id']);
	}

	$render_variables['classifieds'] = classified::list($settings['limit_page'], $_GET, 'clipboard');

	$settings['seo_title'] = trans('Clipboard') . ' - ' . $settings['title'];
	$settings['seo_description'] = trans('Clipboard') . ' - ' . $settings['description'];

} else {
	header("Location: " . path('login') . "?redirect=" . path('clipboard'));
	die('redirect');
}
