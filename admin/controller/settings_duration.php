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

if ($admin->is_logged()) {

	if (!_ADMIN_TEST_MODE_ and isset($_POST['action'])) {
		if ($_POST['action'] == 'add_day' and isset($_POST['length']) and $_POST['length'] > 0 and checkToken('admin_add_day')) {

			duration::add($_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');

		} elseif ($_POST['action'] == 'edit_day' and isset($_POST['id']) and $_POST['id'] > 0 and isset($_POST['length']) and $_POST['length'] > 0 and checkToken('admin_edit_day')) {

			duration::edit($_POST['id'], $_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');

		} elseif ($_POST['action'] == 'remove_day' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('admin_remove_day')) {

			duration::remove($_POST['id']);
			$render_variables['alert_danger'][] = trans('Successfully deleted');

		} elseif ($_POST['action'] == 'save_settings_duration' and checkToken('admin_save_settings_duration')) {

			settings::saveArrays(['days_refresh', 'days_before_refresh', 'days_default', 'days_to_remove'], ['allow_refresh_classifieds']);
			getSettings();
			$render_variables['alert_success'][] = trans('Changes have been saved');

		}
	}

	$render_variables['durations'] = duration::list();

	$title = trans('The length of the display') . ' - ' . $title_default;
}
