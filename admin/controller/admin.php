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

	if(isset($_POST['action']) and !_ADMIN_TEST_MODE_){

		if($_POST['action'] == 'admin_change_user' and !empty($_POST['new_username']) and !empty($_POST['new_password']) and !empty($_POST['repeat_new_password']) and checkToken('admin_change_user')){

			try{
				$admin->changeUser($_POST);
				$render_variables['alert_success'][] = trans('The data have been updated');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_remove_logs' and checkToken('admin_remove_logs')){

			$admin->removeLogs();
			$render_variables['alert_success'][] = trans('Logs logon to the Admin Panel has been successfully removed');

		}elseif($_POST['action'] == 'admin_add_user' and !empty($_POST['username']) and !empty($_POST['password']) and !empty($_POST['repeat_password']) and checkToken('admin_add_user')){

			try{
				$admin->addUser($_POST);
				$render_variables['alert_success'][] = trans('Added new user');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_remove_user' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_user')){

			try{
				$admin->removeUser($_POST['id']);
				$render_variables['alert_success'][] = trans('User has been deleted');
			}catch(Exception $e) {
				$render_variables['alert_danger'][] = $e->getMessage();
			}

		}elseif($_POST['action'] == 'admin_logout_all' and checkToken('admin_logout_all')){

			$admin->logOutAll();

		}

	}

	$render_variables['admin_users'] = $admin->listUsers();
	$render_variables['admin_logs'] = $admin->listLogs();

	$title = trans('Admin Panel Settings').' - '.$title_default;

}
