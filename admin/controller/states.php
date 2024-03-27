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

	$state_id = 0;
	if (isset($_GET['state_id']) and $_GET['state_id'] > 0 and is_numeric($_GET['state_id'])) {
		$state = state::showById($_GET['state_id']);
		if ($state) {
			$render_variables['state'] = $state;
			$state_id = $state['id'];
		}
	}

	if (!_ADMIN_TEST_MODE_ and isset($_POST['action'])) {
		if ($_POST['action'] == 'add_state' and !empty($_POST['name']) and checkToken('admin_add_state')) {

			$state_tmp = state::showBySlug(slug($_POST['name']), $state_id);
			if ($state_tmp) {
				$render_variables['alert_danger'][] = trans('State already exists');
			} else {
				state::add($_POST, $state_id);
				$render_variables['alert_success'][] = trans('Successfully added') . ' ' . strip_tags($_POST['name']);
			}

		} elseif ($_POST['action'] == 'edit_state' and isset($_POST['id']) and $_POST['id'] > 0 and !empty($_POST['name']) and checkToken('admin_edit_state')) {

			state::edit($_POST['id'], $_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');

		} elseif ($_POST['action'] == 'remove_state' and isset($_POST['id']) and $_POST['id'] > 0 and checkToken('admin_remove_state')) {

			state::remove($_POST['id']);
			$render_variables['alert_danger'][] = trans('Successfully deleted');

		} elseif ($_POST['action'] == 'position' and isset($_POST['id']) and isset($_POST['position']) and checkToken('position') and (isset($_POST['+']) or isset($_POST['-']))) {

			if (isset($_POST['+'])) {
				$plusminus = '+';
			} else {
				$plusminus = '-';
			}
			setPosition('state', $_POST['id'], $_POST['position'], $plusminus, 'state_id=' . $state_id);

		} elseif ($_POST['action'] == 'arrange_alphabetically' and checkToken('arrange_alphabetically')) {

			arrangeAlphabetically('state', 'state_id=' . $state_id);

		}
	}

	$render_variables['states'] = state::list($state_id);

	$title = trans('States') . ' - ' . $title_default;

}
