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

if ($user->logged_in) {

	if (isset($_POST['action'])) {
		if ($_POST['action'] == 'add_document' and !empty($_POST['name']) and isset($_FILES['file']) and $_FILES['file']["type"] and checkToken('add_document')) {

			if (document::add($user->getId(), $_POST['name'])) {
				$render_variables['alert_success'][] = trans('The document has been added correctly');
			} else {
				$render_variables['alert_danger'][] = trans('There was an error adding the document');
			}

		} elseif ($_POST['action'] == 'remove_document' and checkToken('remove_document') and isset($_POST['id']) and $_POST['id'] > 0) {

			if (document::checkPermissions($_POST['id'])) {
				document::remove($_POST['id']);
				$render_variables['alert_success'][] = trans('Successfully deleted');
			}
		}
	}

	$render_variables['documents'] = document::list($user->getId());

	$render_variables['allowed_extensions'] = document::$extensions;

	$settings['seo_title'] = trans('My documents') . ' - ' . $settings['title'];
	$settings['seo_description'] = trans('My documents') . ' - ' . $settings['description'];
} else {
	header("Location: " . path('login') . "?redirect=" . path('my_documents'));
	die('redirect');
}
