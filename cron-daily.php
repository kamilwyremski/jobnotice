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

require_once (realpath(dirname(__FILE__)) . '/config/config.php');

function cron_daily()
{
	global $settings, $db;

	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'admin_session WHERE date<(NOW() - INTERVAL 1 DAY)');

	$sth = $db->query('SELECT * FROM ' . _DB_PREFIX_ . 'photo WHERE classified_id=0 AND date<(NOW() - INTERVAL 1 DAY)');
	foreach ($sth as $row) {
		unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['thumb']);
		unlink(_FOLDER_PHOTOS_ . $row['folder'] . $row['url']);
	}
	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'photo WHERE classified_id=0 AND date<(NOW() - INTERVAL 1 DAY)');

	$db->query('UPDATE ' . _DB_PREFIX_ . 'reset_password SET active=0 WHERE active=1 AND date<(NOW() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'user WHERE active=0 AND date<(NOW() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'session_classified WHERE date<(NOW() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'session_user WHERE date<(NOW() - INTERVAL 1 DAY)');

	$db->query('DELETE FROM ' . _DB_PREFIX_ . 'newsletter WHERE active=0 AND date<(NOW() - INTERVAL 1 DAY)');

	$sth = $db->query('SELECT * FROM ' . _DB_PREFIX_ . 'classified WHERE promoted=1 AND promoted_date_finish<CURDATE()');
	foreach ($sth as $row) {
		mailQueue::add('finish_promote', $row['email'], ['classified_name' => $row['name'], 'classified_url' => absolutePath('classified', $row['id'], $row['slug']), 'user_id' => $row['user_id']], 3);
	}
	$db->query('UPDATE ' . _DB_PREFIX_ . 'classified SET promoted=0 WHERE promoted=1 AND promoted_date_finish<CURDATE()');

	$sth = $db->query('SELECT id FROM ' . _DB_PREFIX_ . 'classified WHERE active=0 AND date_finish<(CURDATE() - INTERVAL ' . $settings['days_to_remove'] . ' DAY)');
	foreach ($sth as $row) {
		classified::remove($row['id']);
	}

	$classifieds_deactivate = [];
	$sth = $db->query('SELECT * FROM ' . _DB_PREFIX_ . 'classified WHERE active=1 AND date_finish<CURDATE()');
	foreach ($sth as $row) {
		classified::deactivate($row['id']);
		$classifieds_deactivate[$row['email']][] = $row;
	}
	foreach ($classifieds_deactivate as $email => $classifieds) {
		if ($classifieds[0]['user_id']) {
			mailQueue::add('classifieds_finish', $email, ['classifieds_list' => $classifieds, 'user_id' => $classifieds[0]['user_id']], 4);
		} else {
			mailQueue::add('classifieds_finish_not_logged', $email, ['classifieds_list' => $classifieds], 4);
		}
	}

	if ($settings['generate_sitemap']) {
		sitemap::generateMain();
	}
}
cron_daily();
