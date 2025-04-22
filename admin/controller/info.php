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

if ($admin->is_logged()) {

	if (isset($_POST['action'])) {
		if (!_ADMIN_TEST_MODE_ and $_POST['action'] == 'remove_info' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('admin_remove_info')) {

			info::remove($_POST['id']);
			$render_variables['alert_danger'][] = trans('Successfully deleted');

		} elseif ($_POST['action'] == 'position' and isset($_POST['id']) and isset($_POST['position']) and checkToken('position') and (isset($_POST['+']) or isset($_POST['-']))) {

			if (isset($_POST['+'])) {
				$plusminus = '+';
			} else {
				$plusminus = '-';
			}
			setPosition('info', $_POST['id'], $_POST['position'], $plusminus);

		} elseif ($_POST['action'] == 'arrange_alphabetically' and checkToken('arrange_alphabetically')) {

			arrangeAlphabetically('info');

		}
	}

	$render_variables['info'] = info::list();

}
