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
		if($_POST['action']=='add_type' and !empty($_POST['name']) and checkToken('admin_add_type')){

			$type_tmp = type::showBySlug(slug($_POST['name']));
			if($type_tmp){
				$render_variables['alert_danger'][] = trans('Type already exists');
			}else{
	      type::add($_POST);
				$render_variables['alert_success'][] = trans('Successfully added').' '.strip_tags($_POST['name']);
			}

		}elseif($_POST['action']=='edit_type' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_type')){

      type::edit($_POST['id'],$_POST);
			$render_variables['alert_success'][] = trans('Changes have been saved');

		}elseif($_POST['action']=='remove_type' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_type')){

      type::remove($_POST['id']);
			$render_variables['alert_danger'][] = trans('Successfully deleted');

		}elseif($_POST['action']=='position' and isset($_POST['id']) and isset($_POST['position']) and checkToken('position') and (isset($_POST['+']) or isset($_POST['-']))){

			if(isset($_POST['+'])){
				$plusminus = '+';
			}else{
				$plusminus = '-';
			}
			setPosition('type',$_POST['id'],$_POST['position'],$plusminus);

		}elseif($_POST['action']=='arrange_alphabetically' and checkToken('arrange_alphabetically')){

			arrangeAlphabetically('type');

		}
	}

	$render_variables['types'] = type::list();

	$title = trans('Types').' - '.$title_default;

}
