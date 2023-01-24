<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 - 2023 by IT Works Better https://itworksbetter.net
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
		if($_POST['action']=='remove_classified' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_classified')){
			classified::remove($_POST['id']);
			if(isset($_POST['add_email_black_list']) and !empty($_POST['email'])){
				settings::addEmailToBlackList($_POST['email']);
			}
			if(isset($_POST['add_ip_black_list']) and !empty($_POST['ip'])){
				settings::addIpToBlackList($_POST['ip']);
			}
			$render_variables['alert_danger'][] = trans('The classified has been deleted');
		}elseif($_POST['action']=='deactivate_classified' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_deactivate_classified')){
			classified::deactivate($_POST['id']);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='activate_classified' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date_finish']) and checkToken('admin_activate_classified')){
			classified::activate($_POST['id'],$_POST['date_finish'],1);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='disable_promote_classified' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_disable_promote_classified')){
			classified::disablePromote($_POST['id']);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='enable_promote_classified' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['date']) and checkToken('admin_enable_promote_classified')){
			classified::enablePromote($_POST['id'],$_POST['date']);
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='remove_classifieds' and isset($_POST['classifieds']) and is_array($_POST['classifieds']) and checkToken('admin_action_classifieds')){
			foreach($_POST['classifieds'] as $key => $value){
				if($value>0){
					classified::remove($value);
				}
			}
			$render_variables['alert_danger'][] = trans('The classified has been deleted');
		}elseif($_POST['action']=='active_classifieds' and isset($_POST['classifieds']) and is_array($_POST['classifieds']) and checkToken('admin_action_classifieds')){
			foreach($_POST['classifieds'] as $key => $value){
				if($value>0){
					classified::activate($value,'',1);
				}
			}
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}elseif($_POST['action']=='deactive_classifieds' and isset($_POST['classifieds']) and is_array($_POST['classifieds'] and checkToken('admin_action_classifieds'))){
			foreach($_POST['classifieds'] as $key => $value){
				if($value>0){
					classified::deactivate($value);
				}
			}
			$render_variables['alert_success'][] = trans('Changes have been saved');
		}
	}

	$render_variables['classifieds'] = classified::list(50,$_GET,'admin');

	$title = trans('Classifieds').' - '.$title_default;

}
