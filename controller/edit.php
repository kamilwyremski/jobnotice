<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2022 by IT Works Better https://itworksbetter.net
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

if(!isset($settings['base_url'])){
	die('Access denied!');
}

require_once('controller/add.php');
$controller = 'add';

if(!empty($_GET['code'])){$code = $_GET['code'];}else{$code = '';}

if(isset($_GET['id']) and $_GET['id']>0 and classified::checkPermissions($_GET['id'],$code)){

	$classified = classified::show($_GET['id'], 'edit');
	if($_GET['slug']!=$classified['slug']){
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: ".absolutePath('edit',$classified['id'],$classified['slug']));
		die('redirect');
	}
	$render_variables['classified'] = $classified;
	$settings['seo_title'] = trans('Edit classified').' - '.$settings['title'];
	$settings['seo_description'] = trans('Edit classified').' - '.$settings['description'];
}else{
	throw new noFoundException();
}
