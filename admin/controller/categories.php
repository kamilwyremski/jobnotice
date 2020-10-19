<?php
/************************************************************************
 * The script of website with job offers JobNotice
 * Copyright (c) 2020 by IT Works Better https://itworksbetter.net
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

	$category_id = 0;
	if(isset($_GET['category_id']) and $_GET['category_id']>0){
		$category = category::show($_GET['category_id'], true);
		if($category){
			$render_variables['category'] = $category;
			$category_id = $category['id'];
		}
	}

	if(!_ADMIN_TEST_MODE_ and isset($_POST['action'])){
		if($_POST['action']=='add_category' and !empty($_POST['name']) and checkToken('admin_add_category')){

			$category_tmp = category::showBySlug(slug($_POST['name']),$category_id);
			if($category_tmp){
				$render_variables['alert_danger'][] = trans('Category already exists');
			}else{
				category::add($_POST,$category_id);
				$render_variables['alert_success'][] = trans('Successfully added').' '.strip_tags($_POST['name']);
			}

		}elseif($_POST['action']=='edit_category' and isset($_POST['id']) and $_POST['id']>0 and !empty($_POST['name']) and checkToken('admin_edit_category')){

			category::edit($_POST,$_POST['id']);
			$render_variables['alert_success'][] = trans('Changes have been saved');

		}elseif($_POST['action']=='remove_category' and isset($_POST['id']) and $_POST['id']>0 and checkToken('admin_remove_category')){

			category::remove($_POST['id']);
			$render_variables['alert_danger'][] = trans('Successfully deleted');

		}elseif($_POST['action']=='reload_category' and isset($_POST['category']) and $_POST['category']>=0 and checkToken('admin_reload_category')){

			category::refreshAllSubcategories($_POST['category']);
			$render_variables['alert_success'][] = trans('Categories have been reloaded');

		}elseif($_POST['action']=='position' and isset($_POST['id']) and isset($_POST['position']) and checkToken('position') and (isset($_POST['+']) or isset($_POST['-']))){

			if(isset($_POST['+'])){
				$plusminus = '+';
			}else{
				$plusminus = '-';
			}
			setPosition('category',$_POST['id'],$_POST['position'],$plusminus, 'category_id='.$category_id);

		}elseif($_POST['action']=='arrange_alphabetically' and checkToken('arrange_alphabetically')){

			arrangeAlphabetically('category', 'category_id='.$category_id);

		}
	}

	$render_variables['categories'] = category::list($category_id);

	$title = trans('Categories').' - '.$title_default;
}
