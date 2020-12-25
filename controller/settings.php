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

if(!empty($_GET['slug'])){
	throw new noFoundException();
}

if($user->logged_in){

	if(isset($_POST['action'])){
		if($_POST['action']=='save_avatar' and isset($_FILES['avatar']) and $_FILES['avatar']["type"] and checkToken('save_avatar')){

			$user->saveAvatar();

		}elseif($_POST['action']=='remove_avatar' and checkToken('remove_avatar')){

			user::removeAvatar($user->getId());
			$user->user_data['avatar'] = '';

		}elseif($_POST['action']=='save_description' and isset($_POST['description']) and checkToken('save_description')){

			$user->saveDescription($_POST['description']);

		}elseif($_POST['action']=='save_user_data' and isset($_POST['address']) and isset($_POST['phone']) and checkToken('save_user_data')){

			$user->saveUserData($_POST);

		}elseif($_POST['action']=='change_password' and !empty($_POST['old_password']) and !empty($_POST['new_password']) and !empty($_POST['repeat_new_password']) and checkToken('change_password')){

			try{
				$user->changePassword($_POST);
				$render_variables['alert_success'][] = trans('The password has been correctly updated');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
				$render_variables['input'] = $_POST;
			}
		}
	}

	$user->getAllData();

	$render_variables['states'] = state::listAll();

	$settings['seo_title'] = trans('Settings').' - '.$settings['title'];
	$settings['seo_description'] = trans('Settings').' - '.$settings['description'];
}else{
	header("Location: ".path('login')."?redirect=".path('settings'));
	die('redirect');
}
