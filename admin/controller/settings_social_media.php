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

	if (!_ADMIN_TEST_MODE_ and isset($_POST['action']) and $_POST['action'] == 'save_settings_social_media' and !empty($_POST['facebook_lang']) and checkToken('admin_save_settings_social_media')) {

		settings::saveArrays(
			['url_facebook', 'facebook_lang', 'facebook_api', 'facebook_secret', 'google_id', 'google_secret'],
			['social_facebook', 'social_pinterest', 'social_twitter', 'social_linkedin', 'facebook_side_panel', 'allow_comments_fb_profile', 'allow_comments_fb_article', 'facebook_login', 'google_login']
		);
		getSettings();
		$render_variables['alert_success'][] = trans('Changes have been saved');
	}

	$title = trans('Setting social networks') . ' - ' . $title_default;
}
