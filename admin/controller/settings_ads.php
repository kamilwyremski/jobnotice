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

	if (!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action'] == 'save_settings_ads' and checkToken('admin_save_settings_ads')) {

		settings::saveArrays(['ads_1', 'ads_2', 'ads_3', 'ads_4', 'ads_side_1', 'ads_side_2']);
		getSettings();
		$render_variables['alert_success'][] = trans('Changes have been saved');
	}

	$title = trans('Ads settings') . ' - ' . $title_default;
}
