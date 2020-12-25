<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2021 by IT Works Better https://itworksbetter.net
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

if($admin->is_logged()){

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='add_option' and !empty($_POST['name']) and isset($_POST['kind']) and checkToken('admin_add_option')){
			option::add($_POST);
			header('Location: ?controller=options');
		}elseif($_POST['action']=='edit_option' and isset($_POST['id']) and $_POST['id']>0 and isset($_POST['kind']) and (!empty($_POST['name'])) and checkToken('admin_edit_option')){
			option::edit($_POST['id'],$_POST);
			header('Location: ?controller=options');
		}
	}

	if(isset($_GET['id']) and $_GET['id']>0){
		$render_variables['option'] = option::show($_GET['id']);
	}

	$render_variables['option_kinds'] = option::getKinds();
	$render_variables['categories'] = category::listAll();

	$title = trans('Option').' - '.$title_default;

}
