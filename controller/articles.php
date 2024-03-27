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

if (!empty($_GET['slug'])) {
	throw new noFoundException();
}

if ($settings['enable_articles']) {
	$render_variables['articles'] = article::list(10);
	$settings['seo_title'] = trans('Articles') . ' - ' . $settings['title'];
	$settings['seo_description'] = trans('Articles') . ' - ' . $settings['description'];
} else {
	throw new noFoundException();
}
