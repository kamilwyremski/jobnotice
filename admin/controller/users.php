<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2017 - 2020 by Kamil Wyremski
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
		if($_POST['action']=='activate_user' and isset($_POST['id']) and $_POST['id']>0 and checkToken('activate_user')){
			user::activate($_POST['id']);
			$render_variables['alert_success'][] = trans('Account has been activated, you can now log in');
		}elseif($_POST['action']=='set_moderator' and isset($_POST['id']) and $_POST['id']>0 and checkToken('set_moderator')){
			user::setModerator($_POST['id']);
			$render_variables['alert_success'][] = trans('Moderator rights have been successfully granted');
		}elseif($_POST['action']=='unset_moderator' and isset($_POST['id']) and $_POST['id']>0 and checkToken('unset_moderator')){
			user::unSetModerator($_POST['id']);
			$render_variables['alert_success'][] = trans('Moderator rights have been successfully removed');
		}elseif($_POST['action']=='remove_user' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_user')){
			user::remove($_POST['id']);
			if(isset($_POST['add_email_black_list']) and !empty($_POST['email'])){
				settings::addEmailToBlackList($_POST['email']);
			}
			if(isset($_POST['add_ip_black_list']) and !empty($_POST['register_ip'])){
				settings::addIpToBlackList($_POST['register_ip']);
			}
			if(isset($_POST['add_ip_black_list']) and !empty($_POST['activation_ip'])){
				settings::addIpToBlackList($_POST['activation_ip']);
			}
			$render_variables['alert_danger'][] = trans('User has been deleted');
		}elseif($_POST['action']=='remove_users' and isset($_POST['users']) and is_array($_POST['users'])  and checkToken('admin_action_users')){
			foreach($_POST['users'] as $key => $value){
				if($value>0){
					user::remove($value);
				}
			}
			$render_variables['alert_danger'][] = trans('User has been deleted');
		}elseif($_POST['action']=='activate_users' and isset($_POST['users']) and is_array($_POST['users']) and checkToken('admin_action_users')){
			foreach($_POST['users'] as $key => $value){
				if($value>0){
					user::activate($value);
				}
			}
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='set_moderators' and isset($_POST['users']) and is_array($_POST['users']) and checkToken('admin_action_users')){
			foreach($_POST['users'] as $key => $value){
				if($value>0){
					user::setModerator($value);
				}
			}
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='unset_moderators' and isset($_POST['users']) and is_array($_POST['users']) and checkToken('admin_action_users')){
			foreach($_POST['users'] as $key => $value){
				if($value>0){
					user::unSetModerator($value);
				}
			}
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}
	}

  $render_variables['users'] = user::list(100,$_GET);

	$title = trans('Users').' - '.$title_default;
}
